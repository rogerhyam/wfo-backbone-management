<?php

set_time_limit(0);

// safety check the table name
$table_name = trim($_GET['table_name']);
$table_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $table_name);
$table_name = "`rhakhis_bulk`.`$table_name`"; // qualify it with the db so we don't do anything to rhakhis!!

// drop the table if it already exists
$mysqli->query("DROP TABLE IF EXISTS $table_name");

// open the file for input
$in = fopen('../bulk/csv/' . $_GET['file_in'] , 'r');

// read the header in 
$header_row = fgetcsv($in);

// create the table with all text fields
$sql = "CREATE TABLE $table_name (";

$first = true;
foreach($header_row as $header){
    $col_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $header);

    if($first) $first = false;
    else $sql .= ',';

    $sql .= "\n\t`$col_name` TEXT NULL";
}

// add the rhakhis_* fields if they don't already exist
if(!in_array('rhakhis_skip', $header_row)) $sql .= ",\n\t`rhakhis_skip` TINYINT NULL";
if(!in_array('rhakhis_wfo', $header_row)) $sql .= ",\n\t`rhakhis_wfo` VARCHAR(15) NULL";
if(!in_array('rhakhis_parent', $header_row)) $sql .= ",\n\t`rhakhis_parent` VARCHAR(15) NULL";
if(!in_array('rhakhis_accepted', $header_row)) $sql .= ",\n\t`rhakhis_accepted` VARCHAR(15) NULL";
if(!in_array('rhakhis_basionym', $header_row)) $sql .= ",\n\t`rhakhis_basionym` VARCHAR(15) NULL";

// we must have a primary key - we assume this doesn't exist as we never export it.
$sql .= ",\n\t`rhakhis_pk` int NOT NULL AUTO_INCREMENT";
$sql .= ",\n\tPRIMARY KEY (`rhakhis_pk`)";

$sql .= "\n);";

echo $sql;

$mysqli->query($sql);
if($mysqli->error){
    echo $mysqli->error;
    exit;
}

// read the rest of the lines into the table with insert statements.
while($row = fgetcsv($in)){

    $sql = "INSERT INTO $table_name (";

    $first = true;
    foreach($header_row as $header){
        $col_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $header);

        if($first) $first = false;
        else $sql .= ',';
        
        $sql .= "\n\t`$col_name`";
    }

    $sql .= "\n) VALUES (";

    $first = true;
    foreach($row as $val){
        $safe_val = $mysqli->real_escape_string($val);

        if($first) $first = false;
        else $sql .= ',';
        
        $sql .= "\n\t'$safe_val'";
    }

    $sql .= ")";

    $mysqli->query($sql);
    if($mysqli->error){
        echo $mysqli->error;
        exit;
    }

}

// redirect back to tables page
header('Location: index.php?action=view&phase=tables');

