<?php

$table = $_GET['table'];


// we use the same mechanism as with generating the impact report
// there is a queue we use to navigate the taxonomic tree
// are we called with the root taxon?
if(@$_GET['root_taxon_wfo']){

    $_SESSION['import_root'] = $_GET['root_taxon_wfo'];

    // initialize the queues
    $_SESSION['import_removal_queue'] = array();
    $_SESSION['import_addition_queue'] = array();
    $_SESSION['import_addition_queue'][] = $_GET['root_taxon_wfo']; // prime the import queue with root taxon

    $name = Name::getName($_GET['root_taxon_wfo']);
    $taxon = Taxon::getTaxonForName($name);

    // we can just call prune() on a taxon to remove all its children 
    // and synonyms but this is likely to lead to a memory issue
    // because we will load the whole tree
    // instead we need a heuristic where add them to a LIFO queue
    // and work back to the root taxon
    enqueue_taxon($taxon);

    $uri = "index.php?action=taxonomy_import&table=$table";
    //$next_page = "<p><a href=\"$uri\">$uri</a></p>";
    $next_page = "<script>window.location = \"$uri\"</script>";

    // built the queue so call ourselves without the root taxon

}else{

    // do we have items in the queue?
    if($_SESSION['import_removal_queue']){

        // queued items so process them
        $next_wfo = array_pop($_SESSION['import_removal_queue']);
        $name = Name::getName($next_wfo);
        $taxon = Taxon::getTaxonForName($name);

        // remove all the kids of that taxon
        $taxon->prune();

        $uri = "index.php?action=taxonomy_import&table=$table";
        //$next_page = "<p><a href=\"$uri\">$uri</a></p>";
        $next_page = "<script>window.location = \"$uri\"</script>";

    }else{

        // nothing to pop from the removal queue
        // time to start adding things from the addition queue

        if($_SESSION['import_addition_queue']){

                $next_wfo = array_pop($_SESSION['import_addition_queue']);
                
                // load it from Rhakhis
                $name = Name::getName($next_wfo);
                $taxon = Taxon::getTaxonForName($name);

                // if we are doing the root then we make sure we have any 
                // synonyms added - these aren't covered by below logic because
                // we add synonyms when we create the taxon and the root isn't created
                if($next_wfo == $_SESSION['import_root']){
                    add_synonyms($taxon, $table);
                    add_homotypics($name, $table);
                }

                // get the children from the data table
                $response = $mysqli->query("SELECT rhakhis_wfo FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_parent` = '$next_wfo';");
                if($mysqli->error){
                    echo $mysqli->error;
                    exit;
                }
                $rows = $response->fetch_all(MYSQLI_ASSOC);

                // add each child to the taxon
                foreach ($rows as $row) {

                    $kid_wfo = $row['rhakhis_wfo'];

                    if(!$kid_wfo) continue; // do nothing if we don't have a mapping - shouldn't happen

                    // load the name
                    $kid_name = Name::getName($kid_wfo);

                    // create a taxon for that name
                    $kid_taxon = Taxon::getTaxonForName($kid_name);

                    // if this is already in use as a synonym (from somewhere else in the taxonomy) then the taxon returned 
                    // will be that of 

                    // it shouldn't have a parent yet 
                    if($kid_taxon->getParent() && $kid_taxon->getParent()->getAcceptedName() != $name){
                        echo "<h2>ERROR</h2>";
                        echo "<p>$kid_wfo is already in rhakhis with a different parent!!</p>";
                        echo "<p>This has probably happened because $kid_wfo is out of bounds (not below the root taxon {$_SESSION['import_root']} ) and so wasn't removed from the taxonomy at the start of import.</p>";
                        echo "<p>Stopping here. Building the new taxonomy is incomplete!</p>";
                        exit;
                    }

                    // we need a user
                    $user = unserialize( @$_SESSION['user']);
                    $kid_taxon->setUserId($user->getId()); 

                    $kid_taxon->setParent($taxon);
                    $integrity = $kid_taxon->checkRank();
                    if($integrity->success || $integrity->status == WFO_RANK_REBALANCE){
                        $kid_taxon->save();
                    }else{
                        echo "<p>$kid_wfo problems with rank</p>";
                        echo "<p>$integrity->message</p>";
                        exit;
                    }
                    $kid_taxon->save();
                    
                    // add the child's synonyms
                    add_synonyms($kid_taxon, $table);

                    // add the child's basionyms
                    add_homotypics($kid_name, $table);

                    // if the child has children add it to the queue so we can process them later
                    $response = $mysqli->query("SELECT count(*) as n FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_parent` = '$kid_wfo';");
                    if($mysqli->error){
                        echo $mysqli->error;
                        exit;
                    }
                    $rows = $response->fetch_all(MYSQLI_ASSOC);
                    if($rows[0]['n'] > 0){
                        $_SESSION['import_addition_queue'][] = $kid_wfo;
                    }

                }

                $uri = "index.php?action=taxonomy_import&table=$table";
                //$next_page = "<p><a href=\"$uri\">$uri</a></p>";
                $next_page = "<script>window.location = \"$uri\"</script>";

        }else{

            // nothing left to add - our work here is done
            $next_page = "<hr/><p><strong>Import complete</strong> <a href=\"index.php?action=view&phase=taxonomy&task=taxonomy_import&root_taxon_wfo={$_SESSION['import_root']}\">OK</a></p><hr/>";

        }

    }

}

echo "<h2>Importing taxonomy</h2>";

echo "<p>Queue sizes will go up and down as we do a widthwise crawl of the taxonomic tree for removal and addtion.</p>";

echo "<h3>Removal Queue</h3>";
render_queue($_SESSION['import_removal_queue']);
echo "<h3>Addition Queue</h3>";
render_queue($_SESSION['import_addition_queue']);

echo $next_page;

function render_queue($queue){

    echo "<ul>";
    if(!$queue) echo "<li>~ empty ~</li>";
    foreach($queue as $wfo){
        $name = Name::getName($wfo);
        echo "<li><strong>$wfo:</strong> {$name->getFullNameString()}</li>";
    }
    echo "</ul>";

}

/**
 * Add the synonyms in the data table
 * to the taxon in rhakhis
 */
function add_synonyms($taxon, $table){

    global $mysqli;

    $accepted_wfo = $taxon->getAcceptedName()->getPrescribedWfoId();

    $response = $mysqli->query("SELECT rhakhis_wfo FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_accepted` = '$accepted_wfo';");
    if($mysqli->error){
        echo $mysqli->error;
        exit;
    }
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    foreach ($rows as $row) {
       
        $syn_wfo = $row['rhakhis_wfo'];
        if(!$syn_wfo) continue; // shouldn't happen

        $syn_name = Name::getName($syn_wfo);
        $taxon->addSynonym($syn_name);

    }

}

/**
 * 
 * Add any basionyms missing from 
 * 
 */

function add_homotypics($name, $table){

    global $mysqli;

    $wfo = $name->getPrescribedWfoId();

    $response = $mysqli->query("SELECT rhakhis_wfo FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_basionym` = '$wfo';");
    if($mysqli->error){
        echo $mysqli->error;
        exit;
    }
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    foreach ($rows as $row) {
       
        $homo_wfo = $row['rhakhis_wfo'];
        if(!$homo_wfo) continue; // shouldn't happen

        $homo_name = Name::getName($homo_wfo);
        $homo_name->updateBasionym($name);

    }

}

function enqueue_taxon($taxon){

    // we add the taxon to the queue
    $_SESSION['import_removal_queue'][] = $taxon->getAcceptedName()->getPrescribedWfoId();

    // if it has more that 50 descendents
    // we add them to the queue LIFO queue so they are
    // pruned first
    $children = $taxon->getChildren();
    foreach($children as $kid){
        if($kid->getDescendantCount() > 50){
            enqueue_taxon($kid);
        }
    }
    
}

