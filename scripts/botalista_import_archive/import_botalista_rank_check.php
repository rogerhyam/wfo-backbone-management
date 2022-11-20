<?php

// run over the botalista table and check all the rank relationships are kosha.
// php -d memory_limit=15G import_botalista_rank_check.php 2>&1


require_once('../config.php');



$rank_map = array(
    "phylum" => "phylum",
    "class" => "class",
    "subclass" => "subclass",
    "superorder" => "superorder",
    "order" => "order",
    "family" => "family",
    "subfamily" => "subfamily",
    "supertribe" => "supertribe",
    "tribe" => "tribe",
    "subtribe" => "subtribe",
    "genus" => "genus",
    "section" => "section",
    "subsection" => "subsection",
    "subgenus" => "subgenus",
    'series' => 'series',
    'subseries' => 'subseries',
    "species" => "species",
    "nothospecies"=> "species",
    "nothosubsp."=> "subspecies",
    "nothovar."=> "variety",
    "subspecies" => "subspecies",
    "proles" => "prole",
    "variety" => "variety",
    "convar." => "variety",
    "convariety" => "variety",
    "provar." => "variety",
    "subvariety" => "subvariety",
    "form" => "form",
    "forma" => "form",
    "subform" => "subform"
);


// work through all the rows - may take a while
$sql = "SELECT * FROM botalista_dump_2" ;
$response = $mysqli->query($sql);
if($mysqli->error) echo $mysqli->error;

while($row = $response->fetch_assoc()){

    // skip excluded.
    if($row['doNotProcess']) continue;

    // skip those without parents
    if(!$row['parentNameUsageID']) continue;

    // we have a parent
    $parent_wfo = $row['parentNameUsageID'];

    // get the rank in official form
    $rank = mb_strtolower(trim($row['taxonRank']));
    if(array_key_exists($rank, $rank_map)) {
        $rank = $rank_map[$rank];
    }else{
        echo "\nUnrecognised rank: {$row['taxonRank']}\n";
        exit;
    }

    // get the parent row
    $p_result = $mysqli->query("SELECT * FROM botalista_dump_2 WHERE taxonID = '$parent_wfo'");
    if($p_result->num_rows < 1){
        echo "\nMissing WFO ID $parent_wfo\n";
        continue;
    }
    $p_row = $p_result->fetch_assoc();
    $p_result->close();

    // get the parents official rank
    $p_rank = mb_strtolower(trim($p_row['taxonRank']));
    if(array_key_exists($p_rank, $rank_map)) {
        $p_rank = $rank_map[$p_rank];
    }else{
        echo "\nUnrecognised rank: {$p_row['taxonRank']}\n";
        exit;
    }

    // got two nice rank strings
    $p_rank_details = $ranks_table[$p_rank];

    //

    if(!in_array($rank, $p_rank_details['children'])){
        echo "\nFAIL";
        echo "\t{$row['taxonID']}\t$rank\t{$row['scientificName']}";
        echo "\tis in";
        echo "\t{$p_row['taxonID']}\t$p_rank\t{$p_row['scientificName']}";
    }

}



