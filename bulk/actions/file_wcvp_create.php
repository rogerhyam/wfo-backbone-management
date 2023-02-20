<?php

require_once('../include/NameMatcher.php');
require_once('../include/NameMatches.php');

header("Location: index.php?action=view&phase=csv");

$family = $_GET['family'];

// get the family
$response = $mysqli->query("SELECT * from `kew`.`wcvp` where `family` = '$family';");

// set up a file
$file_name = "wcvp_{$family}.csv";
$file_path = "../bulk/csv/$file_name";

$out = fopen($file_path, 'w');

// write the header
$fields = $response->fetch_fields();
$header = array();
foreach($fields as $field){
    $header[] = $field->name;
}

// add a rhakhis wfo id
$header[] = 'rhakhis_wfo';

fputcsv($out, $header);

// add a row at the top that is the family
$family_row = array_fill_keys($header, null);
$family_row['plant_name_id'] = 0;
$family_row['taxon_name'] = $family;
$family_row['taxon_rank'] = 'family';

// try and get some more details from rhakhis data
$matcher = new NameMatcher();
$matches = $matcher->stringMatch($family);
if(isset($matches->names) && count($matches->names) ==1){
    $family_row['wfo_id'] = $matches->names[0]->getPrescribedWfoId();
    $family_row['rhakhis_wfo'] = $matches->names[0]->getPrescribedWfoId();
    $family_row['taxon_authors'] = $matches->names[0]->getAuthorsString();
}

fputcsv($out, $family_row);

// write all the rows
while($row = $response->fetch_assoc()){

    $out_row = $row;
    
    // add a column at the end with the matching in it
    if(preg_match('/^wfo-[0-9]{10}$/',$row['wfo_id'])){
        $out_row['rhakhis_wfo'] = $row['wfo_id'];
    }else{
        $out_row['rhakhis_wfo'] = null;
    };

    // remove the accepted name pointing to themselves as accepted
    if($row['accepted_plant_name_id'] == $row['plant_name_id']){
        $out_row['accepted_plant_name_id'] = null;
    }

    // if this is a genus then attach it to the family
    if(strtolower($row['taxon_rank']) == 'genus'){
        $out_row['parent_plant_name_id'] = 0;
    }

    fputcsv($out, $out_row);
}

fclose($out);



