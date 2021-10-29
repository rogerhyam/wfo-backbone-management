<?php

// this script will probably only be used at the initiation of the database
// using the data from Missouri originally destined for Botalista

require_once('../config.php');
require_once('../include/Name.php');


// work through all the rows that are not suppressed and create names for them
$sql = "SELECT * FROM botalista_dump_1 WHERE doNotProcess = 0 limit 1";

$response = $mysqli->query($sql);

if($mysqli->error) echo $mysqli->error;

while($row = $response->fetch_assoc()){

    // load a name based on the WFO-ID
    $name = Name::getName($row['taxonID']);

    echo "\n";
    echo $name->getPrescribedWfoId();

    $name->setUserId(1);
    $name->setSource('Seed/botalista_1');

    // start by normalising the rank as much as we can
    // rank map - make sure we only accept certain ranks
    $rank_map = array(
        "phylum" => "phylum",
        "class" => "class",
        "order" => "order",
        "family" => "family",
        "genus" => "genus",
        "section" => "section",
        "subgenus" => "subgenus",
        'series' => 'series',
        "species" => "species",
        "nothospecies"=> "species",
        "nothosubsp."=> "subspecies",
        "nothovar."=> "variety",
        "subspecies" => "subspecies",
        "variety" => "variety",
        "form" => "form",
        "forma" => "form"
    );

    $rank = strtolower(trim($row['taxonRank']));
    if(array_key_exists($rank, $rank_map)) {
        $rank = $rank_map[$rank];
    }else{
        echo "\nUnrecognised rank: {$row['taxonRank']}\n";
        exit;
    }
    $name->setRank($rank);

    // the name depends on the rank
    $scientificName = trim($row['scientificName']);
    switch ($rank) {

        // binomials
        case 'species':
            $parts = explode(' ', $scientificName);
            $name->setGenusString($parts[0]);
            $name->setNameString($parts[1]);
            $name->setSpeciesString(null);
            break;
        
        // trinomials
        case 'subspecies':
        case 'variety':
        case 'form':
            $parts = explode(' ', $scientificName);
            $name->setGenusString($parts[0]);
            $name->setSpeciesString($parts[1]);
            $name->setNameString(array_pop($parts)); // last element avoiding ssp. or var. etc
            break;
        
        // mononomials
        default:
            $name->setNameString($scientificName);
            $name->setGenusString(null);
            $name->setSpeciesString(null);
            break;
    }

    $name->setAuthorsString($row['scientificNameAuthorship']);

    $name->save();
    
    echo "\n";


    print_r($name);
//    print_r($row);



}




