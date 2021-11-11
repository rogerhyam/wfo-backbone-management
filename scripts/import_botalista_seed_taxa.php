<?php

// php -d memory_limit=4G import_botalista_seed_taxa.php 2>&1


// this script will probably only be used at the initiation of the database
// using the data from Missouri originally destined for Botalista

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/Taxon.php');


// work through all the rows - may take a while
$sql = "SELECT * FROM botalista_dump_1 where length(acceptedNameUsageID) = 0 and taxonomicStatus = 'Accepted' limit 1000";

$response = $mysqli->query($sql);

if($mysqli->error) echo $mysqli->error;

echo "Starting run \n";

$start = time();
$counter = 0;
$total = $response->num_rows;

while($row = $response->fetch_assoc()){

    // counter
    echo number_format($counter++, 0) . "\t"; 
    echo $row['taxonID'];

    $elapsed_hrs = (time() - $start)/ (60*60);
    if($counter > 0 && $elapsed_hrs > 0){

        echo "\t" . number_format($elapsed_hrs, 3) . " hrs elapsed "; 

        $rate = $counter/$elapsed_hrs;
        echo "\t" . number_format($rate) . " rows/hour";

        $remaining_rows = $total - $counter;
        $remaining_time = $remaining_rows/$rate;
        echo "\t" . number_format($remaining_time, 3) . "hrs remaining";

    }
    echo "\n";

    // ignore some test records
    if(!preg_match('/wfo-[0-9]{10}/', $row['taxonID'])){
        //echo "\tIgnoring\n";
        continue;
    }

    $ancestry = get_ancestry($row['taxonID']);
    $ancestry = array_reverse($ancestry);

    foreach($ancestry  as $t){

        $taxon = $t[0];
        $parentNameUsageID = $t[1];

        echo "\t";
        echo $taxon->getAcceptedName()->getNameString();
        echo "\t" . $parentNameUsageID;
        echo "\n";

        if($parentNameUsageID){
            // we have a parent so we can add it
            $parent_name = Name::getName($parentNameUsageID);
            $parent_taxon = Taxon::getTaxonForName($parent_name);
            $taxon->setParent($parent_taxon);
        }else{
            // we don't have a parent so we add the root taxon
            $taxon->setParent(Taxon::getRootTaxon());
        }

        $taxon->setUserId(1);
        $taxon->setSource('Seed/botalista_1');
        $taxon->save();

    }


    //print_r($ancestry);

    // is this 

} // rows loop

function get_ancestry($wfo_id, $ancestry = array()){

    global $mysqli;

    // load the name for the wfo-id
    $name = Name::getName($wfo_id);
    // load the taxon for the name
    $taxon = Taxon::getTaxonForName($name);

    // if I have an id then I exist in the db and
    // we don't need to go higher up the tree
    if($taxon->getId()){
        return $ancestry;
    }    
    // do we have a parent?
    $result = $mysqli->query("SELECT parentNameUsageID from botalista_dump_1 WHERE taxonID = '$wfo_id'");
    $row = $result->fetch_assoc();
    if($row && $row['parentNameUsageID'] && preg_match('/wfo-[0-9]{10}/', $row['parentNameUsageID'])){
        // add self to the list
        $ancestry[] = array($taxon, $row['parentNameUsageID']);
        return get_ancestry($row['parentNameUsageID'], $ancestry);
    }else{
        $ancestry[] = array($taxon, null);
        return $ancestry;
    }

}
