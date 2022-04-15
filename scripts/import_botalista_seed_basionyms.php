<?php

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/UpdateResponse.php');
require_once('../include/Taxon.php');
require_once('../include/User.php');

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

// php -d memory_limit=10G import_botalista_seed_basionyms.php 2>&1

// work through all the rows - may take a while
$sql = "SELECT * FROM botalista_dump_2 where length(originalNameUsageID) >0";

$response = $mysqli->query($sql);

if($mysqli->error) echo $mysqli->error;

echo "Starting run \n";

$start = time();
$counter = 0;
$total = $response->num_rows;

while($row = $response->fetch_assoc()){

    // load a name based on the WFO-ID
    $name = Name::getName($row['taxonID']);
    if(!$name) continue;

    // counter display
    echo number_format($counter++, 0) . "\t"; 
    echo $name->getPrescribedWfoId();

    $elapsed_hrs = (time() - $start)/ (60*60);
    if($counter > 0 && $elapsed_hrs > 0){

        echo "\t" . number_format($elapsed_hrs, 3) . " hrs elapsed "; 

        $rate = $counter/$elapsed_hrs;
        echo "\t" . number_format($rate) . " rows/hour";

        $remaining_rows = $total - $counter;
        $remaining_time = $remaining_rows/$rate;
        echo "\t" . number_format($remaining_time, 3) . "hrs remaining";

    }

    // load the basionym 
    $basionym = Name::getName($row['originalNameUsageID']);
    if(!$basionym) continue; // malformed wfo id ?
    if(!$basionym->getId()){
        
        echo "We have not seen this basionym id before! " . $row['originalNameUsageID'];
        exit;
    }

    // then we simply set and save
    $name->setBasionym($basionym);
    $name->save();
    

    echo "\n";



}
