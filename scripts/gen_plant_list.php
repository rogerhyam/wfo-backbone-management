<?php

/*
    This will generate a file suitable for import
    into SOLR as an instance of the WFO Plant List.
    Basically it is a flattened dump of the entire dataset

*/

require_once("../config.php");
require_once("../include/WfoDbObject.php");
require_once("../include/Name.php");
require_once("../include/Taxon.php");
require_once("../include/Identifier.php");
require_once("../include/User.php");


// we build the output on the basis of the month and year
$version_name = date('Y-m');
$parts = explode('-', $version_name);
$version_year = $parts[0];
$version_month = $parts[1];

$out_file_path = "../data/versions/plant_list_$version_name.csv";

// open the file to dump it to
$out = fopen($out_file_path, "w");

// the field list to use as a header
// and to get everything in the right
// order in the output
$fields = array(
    "id",
    "classification_id",
    "classification_year",
    "classification_month",
    "role_flag", // deprecated, unplaced, accepted, synonym
    "prescribed_wfo_id",
    "wfo_ids",
    "other_ids",
    "full_name_string_html",
    "full_name_string_plain",
    "name_string",
    "genus_string",
    "species_string",
    "path_names",
    "nomenclatural_status",
    "editorial_status",
    "path_ids", // - tokenized in to all possible subpaths
    "rank",
    "parent_id",
    "child_ids",
    "accepted_id",
    "stats_*", //
    "reference_uris",
    "reference_labels",
    "reference_image_uris",
    "reference_id",
    "reference_type" // taxonomic, nomenclatural

);

fputcsv($out, $fields);

$result = $mysqli->query("SELECT id FROM `names`");
echo "Number rows: " . $result->num_rows;

$counter = 0;
while($row = $result->fetch_assoc()){

    echo number_format($counter, 0) . "\t";

    $name = Name::getName($row['id']);
    $name_row = process_name($name, $version_name);

    // get the fields in the right order 
    // and write them to the csv
    if($name_row){

        // add common fields
        $name_row["classification_id"] = $version_name;
        $name_row["classification_year"] = $version_year;
        $name_row["classification_month"] = $version_month;

        $csv_row = array();
        foreach($fields as $field){

            if(isset($name_row[$field])){
                $csv_row[] = $name_row[$field];
            }else{
                $csv_row[] = null;
            }
        }
        fputcsv($out, $csv_row);
    }

    // we clear down the singletons every
    // now and then so we don't run out of memory
    if($counter++ > 1000){
        Taxon::resetSingletons();
        Name::resetSingletons();
    }

}

fclose($out);


function process_name($name, $version_name){

    $out = array();

    echo $name->getPrescribedWfoId();
    echo "\n";

    $out['id'] = $name->getPrescribedWfoId() . '-' . $version_name;
    $out['prescribed_wfo_id'] = $name->getId();
    $out['full_name_string_html'] = $name->getFullNameString();
    $out['full_name_string_plain'] = strip_tags($name->getFullNameString());

/*

 "role_flag", // deprecated, unplaced, accepted, synonym
    "prescribed_wfo_id",
    "wfo_ids",
    "other_ids",
    "name_string",
    "genus_string",
    "species_string",
    "path_names",
    "nomenclatural_status",
    "editorial_status",
    "path_ids", // - tokenized in to all possible subpaths
    "rank",
    "parent_id",
    "child_ids",
    "accepted_id",
    "stats_*", //
    "reference_uris",
    "reference_labels",
    "reference_image_uris",
    "reference_id",
    "reference_type" // taxonomic, nomenclatural

*/


    return $out;

}