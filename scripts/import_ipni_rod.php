<?php

/*

Used to mess with Rods data


Rod's citation

DOI Link derived from "IPNI with Literature" (Version 2023-05-26) DOI:10.5281/zenodo.7208699

*/

require_once('../config.php');




$in = fopen("../data/sources/rod_references.csv", 'r');

$header = fgetcsv($in);

print_r($header);
//exit;

while($line = fgetcsv($in)){

    /*
    [0] => id
    [1] => doi
    [2] => citation
    [3] => issued
)
    */

    $sql = "INSERT INTO kew.rod_references (ref_id, `doi`, `citation`, `issued`) VALUE ( ";
    $sql .= " '" . substr($mysqli->real_escape_string($line[0]), 0, 40) . "',";
    $sql .= " '" . $mysqli->real_escape_string($line[1]) . "',";
    $sql .= " '" . substr($mysqli->real_escape_string($line[2]), 0, 900) . "',";
    $sql .= " '" . $mysqli->real_escape_string($line[3]) . "')";

    $mysqli->query($sql);
    if($mysqli->error){
        echo $sql;
        echo $mysqli->error;
        exit;
    }else{
        echo $line[1] . "\n";
    }

}



fclose($in);