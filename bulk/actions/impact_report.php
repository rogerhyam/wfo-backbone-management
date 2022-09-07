<?php

// this generates a csv file describing the impact of a proposed taxonomic import.
// called repeatedly for each child

/*

1 process taxon
2 if it has children add it to the queue to be processed in another call

*/

// are we called with the root taxon?
if(@$_GET['root_taxon_wfo']){

    // initialize the queue
    $_SESSION['impact_queue'] = array();

    // initialize the csv file
    $out = fopen("../bulk/csv/{$_GET['table']}_impact.csv", 'w');

    // on first open we write the headers
    fputcsv($out, array(

        'consequence',
        'rank_ok',
        'status_ok',
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
    ));

    $name = Name::getName($_GET['root_taxon_wfo']);
    process_name($name, $out);

}else{

    // if the queue is empty then stop
    if(count($_SESSION['impact_queue']) == 0){
        echo "<p>Impact report is complete and can be downloaded under the files tab.</p>";
        exit;
    }

   $wfo = array_shift($_SESSION['impact_queue']);

   // open csv file to append rows
   $out = fopen("../bulk/csv/{$_GET['table']}_impact.csv", 'a');

   process_children($wfo, $out);

}

fclose($out);
// call self to process the stuff that will have been put in the queue
$uri = "index.php?action=impact_report&table={$_GET['table']}";
echo "<script>window.location = \"$uri\"</script>";

/**
 * 
 * Given a WFO ID it gets the children
 * of that taxon and puts their names through
 * 
 */
function process_children($wfo, $out){
    $name = Name::getName($wfo);
    $taxon = Taxon::getTaxonForName($name);
    $children = $taxon->getChildren();
    foreach($children as $kid){
        process_name($kid->getAcceptedName(), $out);
    }
}

function process_name($name, $out){

    global $mysqli;

    $out_row = array();

    // actually check the name
    $out_row[] = ''; // the status to be completed below as 0
    $out_row[] = ''; // rank_ok
    $out_row[] = ''; // status_ok
    
    $out_row[] = $name->getPrescribedWfoId();
    $out_row[] = strip_tags($name->getFullNameString());
    $out_row[] = $name->getRank();
    $out_row[] = $name->getStatus();
    
    // FIXME - run through the unplaced
    // if we are doing this at the family level
    // then we don't want to do names that have genera that are 
    // in the data table or they will be pulled out twice.


    // is it an accepted name of a taxon?
    $rhakhis_status = null;
    $taxon = Taxon::getTaxonForName($name);
    if($taxon->getId()>0){

        if($taxon->getAcceptedName() == $name){

            $rhakhis_role = "accepted";

            $out_row[] = "accepted within";
            $out_row[] = $taxon->getParent()->getAcceptedName()->getPrescribedWfoId();
            $out_row[] = strip_tags($taxon->getParent()->getAcceptedName()->getFullNameString());


            // run through the synonyms
            $synonyms = $taxon->getSynonyms();
            foreach ($synonyms as $synonym) {
                process_name($synonym, $out);
            }

            // if we have children add OURSELVES to the queue
            $queue = $_SESSION['impact_queue'];
            if(count($taxon->getChildren()) > 0) $queue[] = $name->getPrescribedWfoId();
            $_SESSION['impact_queue'] = $queue;

        }else{

            $rhakhis_role = "synonym";

            $out_row[] = "synonym of";
            $out_row[] = $taxon->getAcceptedName()->getPrescribedWfoId();
            $out_row[] = strip_tags($taxon->getAcceptedName()->getFullNameString());

        }

    }else{


        $rhakhis_role = "unplaced";

        $out_row[] = "unplaced";
        $out_row[] = ""; // placement wfo
        $out_row[] = ""; // placement name

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

                $out_row[] = "accepted within";

                // are the parents the same?
                if($taxon->getParent()->getAcceptedName()->getPrescribedWfoId() == $data_row['rhakhis_parent']){
                    // parentage has not changed
                    $out_row[0] = "no change";
                    $out_row[] = $data_row['rhakhis_parent'];
                    $out_row[] = strip_tags($taxon->getParent()->getAcceptedName()->getFullNameString());
                }else{

                    // parent has changed
                    $out_row[0] = add_warning_flags($table, $data_row['rhakhis_parent'],  "move");
                    $out_row[] = $data_row['rhakhis_parent'];
                    $new_parent_name = Name::getName($data_row['rhakhis_parent']);
                    $out_row[] = strip_tags($new_parent_name->getFullNameString());
                }

            }elseif($data_row['rhakhis_accepted']){

                // a synonym in the data table
                $out_row[] = "synonym of";

                $out_row[0] = add_warning_flags($table, $data_row['rhakhis_accepted'],  "sunk");
                $out_row[] = $data_row['rhakhis_accepted'];
                $new_accepted_name = Name::getName($data_row['rhakhis_accepted']);
                $out_row[] = strip_tags($new_accepted_name->getFullNameString());


            }else{

                // unplaced in the data table
                
                if($data_row['rhakhis_wfo'] == @$_GET['root_taxon_wfo']){
                    $out_row[0] = "root";
                    $out_row[] = "root";
                }else{
                    $out_row[0] = "removed";
                    $out_row[] = "unplaced";
                }
                $out_row[] = ""; // new wfo
                $out_row[] = ""; // new name

            }

        }elseif($rhakhis_role == "synonym"){

            // synonym in rhakhis

             if($data_row['rhakhis_parent']){

                // an accepted name in the data table
                $out_row[0] = add_warning_flags($table, $data_row['rhakhis_parent'],  "raised");
                $out_row[] = $data_row['rhakhis_parent'];
                $new_parent_name = Name::getName($data_row['rhakhis_parent']);
                $out_row[] = strip_tags($new_parent_name->getFullNameString());

            }elseif($data_row['rhakhis_accepted']){

                // a synonym in the data table

              

                // are both synonyms of the same thing?
                if($taxon->getAcceptedName()->getPrescribedWfoId() != $data_row['rhakhis_accepted']){

                    // Moved to a different accepted taxon 
                    $out_row[0] = add_warning_flags($table, $data_row['rhakhis_accepted'],  "move");
                    $out_row[] = $data_row['rhakhis_accepted'];
                    $new_accepted_name = Name::getName($data_row['rhakhis_accepted']);
                    $out_row[] = strip_tags($new_accepted_name->getFullNameString());

                }else{
                    // no change - same accepted parent
                    $out_row[0] = "no change";
                    $out_row[] = $data_row['rhakhis_accepted'];
                    $out_row[] = strip_tags($taxon->getAcceptedName()->getFullNameString());
                }


            }else{

                // unplaced in the data table
                if($data_row['rhakhis_wfo'] == @$_GET['root_taxon_wfo']){
                    $out_row[0] = "root";
                    $out_row[] = "root";
                }else{
                    $out_row[0] = "removed";
                    $out_row[] = "unplaced";
                }
                $out_row[] = ""; // new wfo
                $out_row[] = ""; // new name

            }


        }else{
            
            // unplaced in rhakhis
            
            if($data_row['rhakhis_parent']){

                // an accepted name in the data table
                $out_row[] = "accepted within";

                $out_row[0] = add_warning_flags($table, $data_row['rhakhis_parent'],  "placed");
                $out_row[] = $data_row['rhakhis_parent'];
                $new_parent_name = Name::getName($data_row['rhakhis_parent']);
                $out_row[] = strip_tags($new_parent_name->getFullNameString());

            }elseif($data_row['rhakhis_accepted']){

                // a synonym in the data table
                $out_row[] = "synonym of";

                $out_row[0] = add_warning_flags($table, $data_row['rhakhis_accepted'],  "placed");
                $out_row[] = $data_row['rhakhis_accepted'];
                $new_accepted_name = Name::getName($data_row['rhakhis_accepted']);
                $out_row[] = strip_tags($new_accepted_name->getFullNameString());

            }else{

                // unplaced in the data table
                $out_row[] = "unplaced";
                $out_row[0] = "no change";
                $out_row[] = "";
                $out_row[] = "";

            }
        }

        // add the rank and status in
        $out_row[] = $data_row['rhakhis_rank'];
        $out_row[] = $data_row['rhakhis_status'];

    }else{
        $out_row[0] = "missing";
        $out_row[] = ""; // placement wfo
        $out_row[] = ""; // placement name
        $out_row[] = ""; // new rank
        $out_row[] = ""; // new status
    }


    // once we have the output row we have all the info we need to check the new row and status will be correct
    check_rank($out_row);
    check_status($out_row);



    fputcsv($out, $out_row);

}


function check_rank(&$out_row){

    $out_row[1] = "OK";

}


function check_status(&$out_row){

    $out_row[2] = 'OK';

}

/**
 * Add explanation marks to the 
 * status if the wfo does not represent 
 * an accepted taxon within the
 * data file
 */
function add_warning_flags($table, $wfo, $status){

    global $mysqli;

    // how confident are we that the new parent will be there?
    $response = $mysqli->query("SELECT * from `rhakhis_bulk`.`$table` WHERE `rhakhis_wfo` = '$wfo'");
    $rows = $response->fetch_all(MYSQLI_ASSOC);

    // it is not in the table or is there as a synonym
    if(!$rows || $rows[0]['rhakhis_accepted']) return "!! " . $status;
    
    // it is in the data table and isn't a synonym
    else return $status;

}

