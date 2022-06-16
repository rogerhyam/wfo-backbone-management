<?php

$export_table_name = $_GET['export_table_name'];

$csv_file = "../bulk/csv/$export_table_name.export.csv";
$zip_file = "../bulk/csv/$export_table_name.export.zip";

$out = fopen($csv_file, 'w');

$response = $mysqli->query("SELECT * FROM `rhakhis_bulk`.`$export_table_name`;", MYSQLI_USE_RESULT);

// write the header
$fields = $response->fetch_fields();
$header = array();
foreach($fields as $field){
    $header[] = $field->name;
}
fputcsv($out, $header);

// write all the rows
while($row = $response->fetch_assoc()){
    fputcsv($out, $row);
}

fclose($out);

exec("gzip $csv_file");

 echo "all done";