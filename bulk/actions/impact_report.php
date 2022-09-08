<?php

// this generates a csv file describing the impact of a proposed taxonomic import.
// called repeatedly for each child

/*

1 process taxon
2 if it has children add it to the queue to be processed in another call

*/

$headers = array(
        'consequence',
        'rank ok',
        'status ok',
        'wfo',
        'name',
        'current rank',
        'current status',
        'current placement',
        'current placement wfo',
        'current placement name',
        'new placement',
        'new placement wfo',
        'new placement name',
        'new rank',
        'new status'
);

// are we called with the root taxon?
if(@$_GET['root_taxon_wfo']){

    // initialize the queue
    $_SESSION['impact_queue'] = array();

    // initialize the csv file
    $out = fopen("../bulk/csv/{$_GET['table']}_impact.csv", 'w');

    // on first open we write the headers
    fputcsv($out, $headers);

    $name = Name::getName($_GET['root_taxon_wfo']);
    process_name($name, $out, $headers);

}else{

    // if the queue is empty then stop
    if(count($_SESSION['impact_queue']) == 0){
        echo "<p>Impact report is complete and can be downloaded under the files tab.</p>";
        $uri = "index.php?action=view&phase=csv";
        echo "<script>window.location = \"$uri\"</script>";
        exit;
    }

   $wfo = array_shift($_SESSION['impact_queue']);

   // open csv file to append rows
   $out = fopen("../bulk/csv/{$_GET['table']}_impact.csv", 'a');

   process_children($wfo, $out, $headers);

}

fclose($out);
// call self to process the stuff that will have been put in the queue
$uri = "index.php?action=impact_report&table={$_GET['table']}";
$remaining = count($_SESSION['impact_queue']);
echo "<h2>Generating Impact Report</h2>";
echo "<p>Working ... Queue size: $remaining</p>";
echo "<p>Queue size will go up and down as we do a widthwise crawl of the taxonomic tree.</p>";
echo "<script>window.location = \"$uri\"</script>";

/**
 * 
 * Given a WFO ID it gets the children
 * of that taxon and puts their names through
 * 
 */
function process_children($wfo, $out, $headers){
    $name = Name::getName($wfo);
    $taxon = Taxon::getTaxonForName($name);
    $children = $taxon->getChildren();
    foreach($children as $kid){
        process_name($kid->getAcceptedName(), $out, $headers);
    }
}

function process_name($name, $out, $headers){

    global $mysqli;

    $out_row = array();

    // actually check the name
    $out_row['consequence'] = ''; // the status to be completed below as 0
    $out_row['rank_ok'] = ''; // rank_ok
    $out_row['status_ok'] = ''; // status_ok
    
    $out_row['wfo'] = $name->getPrescribedWfoId();
    $out_row['name'] = strip_tags($name->getFullNameString());
    $out_row['current rank'] = $name->getRank();
    $out_row['current status'] = $name->getStatus();    

    // if the name is a genus then we process each
    if( $name->getRank() == 'genus'){
        $finder = new UnplacedFinder($name->getPrescribedWfoId(), 0, 5000, true); // just a page of 5,000 and include deprecated names
        foreach($finder->unplacedNames as $unplaced){
            process_name($unplaced, $out, $headers);
        }
    }

    // is it an accepted name of a taxon?
    $rhakhis_status = null;
    $taxon = Taxon::getTaxonForName($name);
    if($taxon->getId()>0){

        if($taxon->getAcceptedName() == $name){

            $rhakhis_role = "accepted";

            $out_row['current placement'] = "accepted within";
            $out_row['current placement wfo'] = $taxon->getParent()->getAcceptedName()->getPrescribedWfoId();
            $out_row['current placement name'] = strip_tags($taxon->getParent()->getAcceptedName()->getFullNameString());


            // run through the synonyms
            $synonyms = $taxon->getSynonyms();
            foreach ($synonyms as $synonym) {
                process_name($synonym, $out, $headers);
            }

            // if we have children add OURSELVES to the queue
            $queue = $_SESSION['impact_queue'];
            if(count($taxon->getChildren()) > 0) $queue[] = $name->getPrescribedWfoId();
            $_SESSION['impact_queue'] = $queue;

        }else{

            $rhakhis_role = "synonym";

            $out_row['current placement'] = "synonym of";
            $out_row['current placement wfo'] = $taxon->getAcceptedName()->getPrescribedWfoId();
            $out_row['current placement name'] = strip_tags($taxon->getAcceptedName()->getFullNameString());

        }

    }else{

        $rhakhis_role = "unplaced";

        $out_row['current placement'] = "unplaced";
        $out_row['current placement wfo'] = ""; // placement wfo
        $out_row['current placement name'] = ""; // placement name

    }

    // get the row from the database
    $table = $_GET['table'];
    $response = $mysqli->query("SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_wfo` = '{$name->getPrescribedWfoId()}';");
    if($mysqli->error){
        echo $mysqli->error;
        exit;
    }
    $rows = $response->fetch_all(MYSQLI_ASSOC);

    if($rows){

        $data_row = $rows[0];

        if($rhakhis_role == "accepted"){

            // accepted in rhakhis

            if($data_row['rhakhis_parent']){
                // an accepted name in the data table
        
                $out_row['new placement'] = "accepted within";

                // are the parents the same?
                if($taxon->getParent()->getAcceptedName()->getPrescribedWfoId() == $data_row['rhakhis_parent']){
                    // parentage has not changed
                    $out_row['consequence'] = "no change";
                    $out_row['new placement wfo'] = $data_row['rhakhis_parent'];
                    $out_row['new placement name'] = strip_tags($taxon->getParent()->getAcceptedName()->getFullNameString());
                }else{
                    // parent has changed
                    $out_row['consequence'] = "move";
                    $out_row['new placement wfo'] = $data_row['rhakhis_parent'];
                    $new_parent_name = Name::getName($data_row['rhakhis_parent']);
                    $out_row['new placement name'] = strip_tags($new_parent_name->getFullNameString());
                }

            }elseif($data_row['rhakhis_accepted']){

                // a synonym in the data table
                $out_row['new placement'] = "synonym of";

                $out_row['consequence'] =  "sunk";
                $out_row['new placement wfo'] = $data_row['rhakhis_accepted'];
                $new_accepted_name = Name::getName($data_row['rhakhis_accepted']);
                $out_row['new placement name'] = strip_tags($new_accepted_name->getFullNameString());


            }else{

                // unplaced in the data table

                if($data_row['rhakhis_wfo'] == @$_GET['root_taxon_wfo']){
                    $out_row['consequence'] = "root";
                    $out_row['new placement'] = "root";
                }else{
                    $out_row['consequence'] = "removed";
                    $out_row['new placement'] = "unplaced";
                }
                $out_row['new placement wfo'] = ""; // new wfo
                $out_row['new placement name'] = ""; // new name

            }

        }elseif($rhakhis_role == "synonym"){

            // synonym in rhakhis

             if($data_row['rhakhis_parent']){

                // an accepted name in the data table
                $out_row['consequence'] = "raised";
                $out_row['new placement'] = "synonym of";
                $out_row['new placement wfo'] = $data_row['rhakhis_parent'];
                $new_parent_name = Name::getName($data_row['rhakhis_parent']);
                $out_row['new placement name'] = strip_tags($new_parent_name->getFullNameString());

            }elseif($data_row['rhakhis_accepted']){

                // a synonym in the data table

                // are both synonyms of the same thing?
                if($taxon->getAcceptedName()->getPrescribedWfoId() != $data_row['rhakhis_accepted']){

                    // Moved to a different accepted taxon 
                    $out_row['consequence'] =  "move";
                    $out_row['new placement'] = "synonym of";
                    $out_row['new placement wfo'] = $data_row['rhakhis_accepted'];
                    $new_accepted_name = Name::getName($data_row['rhakhis_accepted']);
                    $out_row['new placement name'] = strip_tags($new_accepted_name->getFullNameString());

                }else{
                    // no change - same accepted parent
                    $out_row['consequence'] = "no change";
                    $out_row['new placement'] = "synonym of";
                    $out_row['new placement wfo'] = $data_row['rhakhis_accepted'];
                    $out_row['new placement name'] = strip_tags($taxon->getAcceptedName()->getFullNameString());
                }


            }else{


                // unplaced in the data table
                if($data_row['rhakhis_wfo'] == @$_GET['root_taxon_wfo']){
                    $out_row['consequence'] = "root";
                    $out_row['new placement'] = "root";
                }else{
                    $out_row['consequence'] = "removed";
                    $out_row['new placement'] = "unplaced";
                }
                $out_row['new placement wfo'] = ""; // new wfo
                $out_row['new placement name'] = ""; // new name

            }


        }else{
            
            // unplaced in rhakhis
            if($data_row['rhakhis_parent']){

                // an accepted name in the data table
                $out_row['new placement'] = "accepted within";

                $out_row['consequence'] = "placed";
                $out_row['new placement wfo'] = $data_row['rhakhis_parent'];
                $new_parent_name = Name::getName($data_row['rhakhis_parent']);
                $out_row['new placement name'] = strip_tags($new_parent_name->getFullNameString());

            }elseif($data_row['rhakhis_accepted']){

                // a synonym in the data table
                $out_row['new placement'] = "synonym of";

                $out_row['consequence'] = "placed";
                $out_row['new placement wfo'] = $data_row['rhakhis_accepted'];
                $new_accepted_name = Name::getName($data_row['rhakhis_accepted']);
                $out_row['new placement name'] = strip_tags($new_accepted_name->getFullNameString());

            }else{

                // unplaced in the data table
                $out_row['new placement'] = "unplaced";
                $out_row['consequence'] = "no change";
                $out_row['new placement wfo'] = "";
                $out_row['new placement name'] = "";

            }
        }

        // add the rank and status in
        $out_row['new rank'] = $data_row['rhakhis_rank'];
        $out_row['new status'] = $data_row['rhakhis_status'];

    }else{

        // not in data table
        $out_row['new placement'] = "unplaced";
        $out_row['consequence'] = "missing";
        $out_row['new placement wfo'] = "";
        $out_row['new placement name'] = "";
        $out_row['new rank'] = "";
        $out_row['new status'] = "";

    }


    // once we have the output row we have all the info we need to check the new row and status will be correct
    check_rank($out_row, $table);
    check_status($out_row, $table);

    // finally write the thing out
    $csv_row = array();
    foreach ($headers as $header) {
        // will throw error if value isn't set in 
        // out_row - which is a good thing.
        $csv_row[] = $out_row[$header];
    }

    fputcsv($out, $csv_row);

}


function check_rank(&$out_row, $table){

    global $mysqli;
    global $ranks_table;

    // if the rank isn't set in the data file
    // we use the one in rhakhis
    $new_rank = $out_row['new rank'];
    if(!$new_rank) $new_rank = $out_row['current rank'];

    // rank must be appropriate for what the parent will have
    if($out_row['new placement'] == 'accepted within'){

        // get the parent and find out their rank
        $parent_wfo = $out_row['new placement wfo'];

        // is it in the data file?
        $response = $mysqli->query("SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_wfo` = '$parent_wfo';");
        if($mysqli->error){
            echo $mysqli->error;
            exit;
        }
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        if($rows){
            // we have it in the data file
            $parent_rank = $rows[0]['rhakhis_rank'];
        }

        // if we haven't got it from the data file then load it from rhakhis
        if(!$parent_rank){
            $parent_name = Name::getName($parent_wfo);
            $parent_rank = $parent_name->getRank();
        }

        if(in_array($new_rank, $ranks_table[$parent_rank]['children'])){
            // we are ok to be a child of that parent
            $out_row['rank ok'] = "OK";
        }else{
            // we are not OK to be a child of that parent
            $out_row['rank ok'] = "NO: $new_rank can't be a child of $parent_rank";
        }

    }else{
        // rank can be anything if they are not an accepted name
        $out_row['rank ok'] = "OK";
    }
 

}


function check_status(&$out_row, $table){

    // if the status isn't set in the data file
    // we use the one in rhakhis
    $new_status = $out_row['new status'];
    if(!$new_status) $new_status = $out_row['current status'];

    // is the new placement as an accepted name
    if($out_row['new placement'] == 'accepted within'){

        // it is going to be an accepted name
         if($new_status == 'valid' || $new_status == 'conserved' || $new_status == 'sanctioned'){
            $out_row['status ok'] = 'OK';
         }else{
            $out_row['status ok'] = 'NO: You must have a status of valid, conserved or sanctioned to be the accepted name of a taxon';
         }

    }elseif($out_row['new placement'] == 'synonym of'){

        // it is going to be a synonym so can be anything but deprecated
        if($new_status == 'deprecated'){
            $out_row['status ok'] = "NO: You can't have a status of 'deprecated' and be placed in the taxonomy";
        }else{
            $out_row['status ok'] = 'OK';
        }

    }else{
        // it can be anything
        $out_row['status ok'] = 'OK';
    }

    

}


