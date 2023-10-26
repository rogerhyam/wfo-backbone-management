<?php

require_once('../config.php');

$input_path = '/Users/rogerhyam/Downloads/specimen.csv';
$in = fopen($input_path, 'r');
$header = fgetcsv($in, 0, "\t", '"');

echo "\nIMPORTING ROD'S TYPES\n";

$mysqli->query("DROP TABLE IF EXISTS `kew`.`rod_types`;");

// this is a run once to import Rod's data on types
$create_sql = "CREATE TABLE `kew`.`rod_types` (
  `doi` text,
  `code` text,
  `gbif` int DEFAULT NULL,
  `occurrenceUrl` text,
  `occurrenceID` text,
  `title` text,
  `resource_type` text,
  `canonical` text,
  `stored_under_name` text,
  `type_status` text,
  `family` text,
  `collector` text,
  `date` text,
  `country` text,
  `herbarium` text,
  `names` text DEFAULT NULL,
  `url` text,
  `thumbnailUrl` text
);";

$mysqli->query($create_sql);

echo "\nTable created.";

$stmt = $mysqli->prepare("INSERT INTO `kew`.`rod_types`(
        `doi`,
        `code`,
        `gbif`,
        `occurrenceUrl`,
        `occurrenceID`,
        `title`,
        `resource_type`,
        `canonical`,
        `stored_under_name`,
        `type_status`,
        `family`,
        `collector`,
        `date`,
        `country`,
        `herbarium`,
        `names`,
        `url`,
        `thumbnailUrl`
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

$counter = 0;
while($line = fgetcsv($in, 0, "\t", '"')){
    $stmt->bind_param("ssisssssssssssssss", ...$line);
    $stmt->execute();
    $counter++;
    echo "\n$counter\t{$line[0]}";
}