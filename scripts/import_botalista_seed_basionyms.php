<?php

require_once('../config.php');
require_once('../include/Name.php');


// work through all the rows - may take a while
$sql = "SELECT * FROM botalista_dump_1 where length(originalNameUsageID) >0";

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
