<?php

// This takes an incremental approach to importing a taxonomy
// one change is made at a time.
// similar to the way the impact report is generated.


// are we called with the root taxon?
if(@$_GET['root_taxon_wfo']){

    // keep a handle on which root we are using
    $_SESSION['import_root'] = $_GET['root_taxon_wfo'];

    // the boundary taxon specifies where names can be moved from
    // typically it will be the root but it could be above the root
    // if, for example the a genus is being imported and it is OK to
    // move taxa within the family
    $_SESSION['import_boundary'] = $_GET['boundary_taxon_wfo'];

    // initialize the queue
    $_SESSION['import_incremental_queue'] = array();
   
    // initialize name tracker - to compare with file at end
    $_SESSION['import_incremental_track'] = array();

    // any warnings to print out
    $_SESSION['import_incremental_warnings'] = array();

    $name = Name::getName($_GET['root_taxon_wfo']);
    process_name($name);

}else{

    // if the queue is empty then consider stopping
    if(count($_SESSION['import_incremental_queue']) == 0){

        // look at all the names in the table 
        // below the root taxon and check they are not
        // out of bounds in rhakhis.
        crawl_table_tree($_SESSION['import_root']);

        echo "<p>Import is completed. The taxonomies should be in sync.</p>";
        echo "<p><a href=\"index.php?action=view&phase=taxonomy\">Back to taxonomy pages.</a></p>";

        foreach ($_SESSION['import_incremental_warnings'] as $warning) {
            echo "<p>$warning</p>\n";
        }

        exit;

    }else{
    
        // get the next wfo to process off the queue
        $wfo = array_shift($_SESSION['import_incremental_queue']);

        // this will prevent all the children and
        // all the unplaced names being loaded at once
        process_children($wfo);
        process_unplaced($wfo);

    }

}

// call self to process the stuff that will have been put in the queue
$uri = "index.php?action=taxonomy_import_incremental&table={$_GET['table']}";

echo "<h2>Doing Incremental Import of Taxonomy</h2>";
echo "<p>Working ... </p>";
$queue_size = count($_SESSION['import_incremental_queue']);
echo "<p>Queue size: ". number_format($queue_size, 0). "</p>";

$tracker_size = count($_SESSION['import_incremental_track']);
echo "Names processed: ". number_format($tracker_size, 0). "</p>";

echo "<p>Queue size will go up and down as we do a widthwise crawl of the taxonomic tree. Names with children or associated unplaced names are added to the list then processed with another call.</p>";

echo "<script>window.location = \"$uri\"</script>";


/**
 * We use this to crawl the tree in the 
 * data table to be sure we have accounted
 * for all the names in it.
 * Some of them may have been out of bounds
 * i.e. we crawled the tree in rhakhis before
 * if the name in the tree in the table i not below
 * the root in rhakhis but belongs to some other family 
 * it won't have been found.
 * 
 */

function crawl_table_tree($wfo){

    global $mysqli;

    // if we haven't processed it already then process it
    if(!in_array($wfo, $_SESSION['import_incremental_track'])){
        $name = Name::getName($wfo);
        if($name->getId()) process_name($name);
        else echo "<p>$wfo not found it Rhakhis</p>";
    }

    // we now process its children
    $table = $_GET['table'];
    $response = $mysqli->query("SELECT rhakhis_wfo FROM `rhakhis_bulk`.`$table` where rhakhis_parent = '$wfo'");
    while($row = $response->fetch_assoc()){
        crawl_table_tree($row['rhakhis_wfo']);
    }


}

/**
 * 
 * Given a WFO ID it gets the children
 * of that taxon and puts their names through
 * and their 
 * 
 */
function process_children($wfo){

    $name = Name::getName($wfo);
    $taxon = Taxon::getTaxonForName($name);
    if(!$taxon->getId()) return;
    $children = $taxon->getChildren();

    $processed_this_call = 0;
    foreach($children as $kid){

        // if we haven't done it before we consider doing it
        if(!in_array($kid->getAcceptedName()->getPrescribedWfoId(),  $_SESSION['import_incremental_track'])){
            process_name($kid->getAcceptedName());
        }      
        
    }

}

function process_unplaced($wfo){

    $name = Name::getName($wfo);
    // if the name is a genus then we process each
    if( $name->getRank() == 'genus' || $name->getRank() == 'species' ){
        $finder = new UnplacedFinder($name->getPrescribedWfoId(), 0, 5000, true); // just a page of 5,000 and include deprecated names
        foreach($finder->unplacedNames as $unplaced){

            // if we haven't done it before we consider doing it
            if(!in_array($unplaced->getPrescribedWfoId(),  $_SESSION['import_incremental_track'])){
                // if we haven't reached the max
                process_name($unplaced);
            } 
        }
    }
}

function process_name($name){

    global $mysqli;
    global $ranks_table;

    error_log($name->getPrescribedWfoId());
    
    // if we have finished processing a name we never do it again
    if(in_array($name->getPrescribedWfoId(), $_SESSION['import_incremental_track'])){
        // we only do names once or we loop
        echo "<p>Called for: {$name->getPrescribedWfoId()}: {$name->getFullNameString()} a second time.</p>";
        return;
    }

    // load the row from the table for this name - if there is one!
    $table = $_GET['table'];
    $response = $mysqli->query("SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_wfo` = '{$name->getPrescribedWfoId()}';");
    if($mysqli->error){
        echo $mysqli->error;
        exit;
    }
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    if(count($rows) > 1){
        echo "Multiple rows for {$name->getPrescribedWfoId()}";
        exit;
    }elseif(count($rows) < 1){
        $row = null;
    }else{
        $row = $rows[0];

        // if we have a row then we update the name's status and rank if needed

        // update the rank if needed.
        if(
            $row['rhakhis_rank'] // we have a rank set it the table
            &&
            $row['rhakhis_rank'] != $name->getRank() // it isn't already the same as the existing on
            ){
                $name->setRank($row['rhakhis_rank']);
                $name->save();
        }

        // update status if needed
        if(
            $row['rhakhis_status']  // we have a status set in the table
            &&
            $row['rhakhis_status'] != $name->getStatus() // it is different from the current status
            &&
            ($row['rhakhis_status'] == 'valid' || $row['rhakhis_status'] == 'conserved' || $row['rhakhis_status'] == 'sanctioned') // it is OK for an accepted name
            ){
                $name->setStatus($row['rhakhis_status']);
                $name->save();
        }

    } // row exists

    // is it an accepted name of a taxon in Rhakhis?
    $taxon = Taxon::getTaxonForName($name);
    if($taxon->getId()>0){


        // see if it is in bounds and so can be moved
        // the name is placed in the taxonomy but is it placed
        // below the root taxon and therefore available to be moved
        // if it isn't then it won't be freed up by the removal part of the 
        // import routine
        $in_bounds = false;
        $ancestors = $taxon->getAncestors();
        foreach($ancestors as $anc){
            if($anc->getAcceptedName()->getPrescribedWfoId() == $_SESSION['import_boundary']){
                $in_bounds = true;
                break;
            }
        }

        // edge case - if it is the root then it is in bounds
        if($taxon->getAcceptedName()->getPrescribedWfoId() == $_SESSION['import_root']){
            $in_bounds = true;
        }

        // it is placed within Rhakhis
        if($taxon->getAcceptedName() == $name){

            // it is accepted within Rhakhis

            // does it need moving
            if(!$row || $row['rhakhis_parent'] != $taxon->getParent()->getAcceptedName()->getPrescribedWfoId()){

                // it is in a different location in the table to in rhakhis
                
                // is it placed in the table?
                if(!$row || (!$row['rhakhis_parent'] && !$row['rhakhis_accepted'])){

                    // the root meets the criteria but we ignore it
                    if($name->getPrescribedWfoId() != $_SESSION['import_root']){

                        // it is unplaced in the table
                        // a name can only become unplaced if it doesn't have children or synonyms
                        $taxon->prune();
                        $taxon->delete();

                    }

                }else{

                    // it has a row in the table
                    if($row['rhakhis_parent']){

                        // it is accepted at another location

                        $new_parent_name = Name::getName($row['rhakhis_parent']);
                        $new_parent_taxon = Taxon::getTaxonForName($new_parent_name);

                        if($new_parent_taxon->getId() && $new_parent_taxon->getAcceptedName() == $new_parent_name){
                            $taxon->setParent($new_parent_taxon);
                        }else{
                            // new parent is either unplaced or placed as a synonym
                            // process that name then try and do this one again!
                            process_name($new_parent_name);
                            $new_parent_taxon = Taxon::getTaxonForName($new_parent_name);
                            $taxon->setParent($new_parent_taxon);
                        }
                    
                    }elseif($row['rhakhis_accepted']){

                        // we are sinking it into synonymy
                        // it must have no children or synonyms
                        // its destination must be an accepted taxon
                        $accepted_name = Name::getName($row['rhakhis_accepted']);
                        $accepted_taxon = Taxon::getTaxonForName($accepted_name);

                        // if it has children then putting it on the deferred is no good...
                        if($taxon->getChildCount() == 0 && count($taxon->getSynonyms()) == 0 && $accepted_taxon->getId() && $accepted_taxon->getAcceptedName() == $accepted_name){
                            $taxon->delete();
                            $accepted_taxon->addSynonym($name);
                        }else{

                            process_name($accepted_name);
                            
                            $accepted_taxon = Taxon::getTaxonForName($accepted_name);
                            
                            $taxon->prune();
                            $taxon->delete();

                            if($accepted_taxon->getId() && $accepted_taxon->getAcceptedName() == $accepted_name){
                                $accepted_taxon->addSynonym($name);
                            }else{
                                $_SESSION['import_incremental_warnings'][] = "Couldn't add {$name->getPrescribedWfoId()} : {$name->getFullNameString()} as a synonym because no taxon for {$accepted_name->getPrescribedWfoId()} : {$accepted_name->getFullNameString()} to add it to.";
                            }
                        
                        }

                    }else{

                        // it has a row but neither accepted nor parent in the table
                        // could be root - we ignore it
                        // could be unplaced.
                        if($name->getPrescribedWfoId() != $_SESSION['import_root']){
                            // not the root so remove it if we can
                            $taxon->prune();
                            $taxon->delete();
                        }

                    }

                }


            }

            // If we still have synonyms we run through them so they can be processed
            $synonyms = $taxon->getSynonyms();
            foreach ($synonyms as $synonym) {
                process_name($synonym);
            }

            // if we still have children add OURSELVES to the queue
            // so our children will be processed later
            $queue = $_SESSION['import_incremental_queue'];
            if(count($taxon->getChildren()) > 0) $queue[] = $name->getPrescribedWfoId();
            $_SESSION['import_incremental_queue'] = $queue;

        }else{

            // it is a synonym within Rhakhis

            // does it need moving
            if(!$row || $row['rhakhis_accepted'] != $taxon->getAcceptedName()->getPrescribedWfoId()){

                // it needs moving somewhere
                if(!$row || (!$row['rhakhis_parent'] && !$row['rhakhis_accepted'])){
                    // it doesn't occur in the table or doesn't have a placement so just needs to be removed
                    $taxon->removeSynonym($name);
                }else{

                    // it is in the table
                    if($row['rhakhis_parent']){

                        // it needs to raise it to be a taxon
                        $parent_name = Name::getName($row['rhakhis_parent']);
                        $parent_taxon = Taxon::getTaxonForName($parent_name);

                        // if the parent isn't ready yet then prepare it.
                        if(!$parent_taxon->getId() || $parent_taxon->getAcceptedName() != $parent_name){
                            process_name($parent_name);
                            $parent_taxon = Taxon::getTaxonForName($parent_name);
                        }
                        
                        // actually create the taxon
                        $new_taxon = Taxon::getTaxonForName($name);
                        $new_taxon->setParent($parent_taxon);
                        $user = unserialize( @$_SESSION['user']);
                        $new_taxon->setUserId($user->getId()); 
                        $new_taxon->save();

                    }else{

                        // we are moving it as a synonym
                        $accepted_name = Name::getName($row['rhakhis_accepted']);
                        $accepted_taxon = Taxon::getTaxonForName($accepted_name);

                        // if the parent isn't ready yet we process it to make it so
                        if(!$accepted_taxon->getId() || $accepted_taxon->getAcceptedName() != $accepted_name){
                            process_name($accepted_name);
                            $accepted_taxon = Taxon::getTaxonForName($accepted_name);
                        }

                        $taxon->removeSynonym($name);

                        // catch synonyms of synonyms - bad but occur...
                        if($accepted_taxon->getId() && $accepted_taxon->getAcceptedName() == $accepted_name){
                            $accepted_taxon->addSynonym($name);
                        }else{
                            $_SESSION['import_incremental_warnings'][] = "Couldn't add {$name->getPrescribedWfoId()} : {$name->getFullNameString()} as a synonym because no taxon for {$accepted_name->getPrescribedWfoId()} : {$accepted_name->getFullNameString()} to add it to.";
                        }
 
                    }

                }

            }

        }


 
    }else{

        // It is unplaced within rhakhis.

        // only need to do anything if there is a row in the table
        if($row && ($row['rhakhis_parent'] || $row['rhakhis_accepted'])){
            
            if($row['rhakhis_parent']){

                // we need to place it as an accepted name
                $parent_name = Name::getName($row['rhakhis_parent']);
                $parent_taxon = Taxon::getTaxonForName($parent_name);

                // if the parent isn't ready yet we call it to be processed
                if(!$parent_taxon->getId() || $parent_taxon->getAcceptedName() != $parent_name){
                    process_name($parent_name);
                    $parent_taxon = Taxon::getTaxonForName($parent_name);
                }

                // ok create the taxon
                $new_taxon = Taxon::getTaxonForName($name);
                $new_taxon->setParent($parent_taxon);
                $user = unserialize( @$_SESSION['user']);
                $new_taxon->setUserId($user->getId()); 
                $new_taxon->save();


            }else{

                // we need to place it as a synonym
                $accepted_name = Name::getName($row['rhakhis_accepted']);
                $accepted_taxon = Taxon::getTaxonForName($accepted_name);
            
                // if the parent isn't ready yet process that first
                if(!$accepted_taxon->getId() || $accepted_taxon->getAcceptedName() != $accepted_name){
                    process_name($accepted_name);
                    $accepted_taxon = Taxon::getTaxonForName($accepted_name);
                }

                // actually make it a synonym
                // we have to put a second catch because the creation of the accepted
                // name may have failed because people chain synonyms
                if($accepted_taxon->getId() && $accepted_taxon->getAcceptedName() == $accepted_name){
                    $accepted_taxon->addSynonym($name);
                }else{
                    $_SESSION['import_incremental_warnings'][] = "Couldn't add {$name->getPrescribedWfoId()} : {$name->getFullNameString()} as a synonym because no taxon for {$accepted_name->getPrescribedWfoId()} : {$accepted_name->getFullNameString()} to add it to.";
                }

            } // synonym in table

        } // found in table


    } // unplaced in rhakhis

    // we've finished processing this name
    // so we add it to the track if it isn't there already
    $track = $_SESSION['import_incremental_track'];
    if(!in_array($name->getPrescribedWfoId(), $track)){
        $track[] = $name->getPrescribedWfoId();
        $_SESSION['import_incremental_track'] = $track;
    }
    

} // process name




