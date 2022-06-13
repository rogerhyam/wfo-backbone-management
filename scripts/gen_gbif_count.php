<?php

/*
    Adds occurrence counts from GBIF for any unplaced SPECIES names
*/

require_once("../config.php");
require_once("../include/WfoDbObject.php");
require_once("../include/Name.php");
require_once("../include/Taxon.php");

// php -d memory_limit=1024M gen_family_html_file.php

// GBIF URI
$base_uri = "https://api.gbif.org/v1/occurrence/search?limit=0&kingdomKey=6&scientificName=";

// opts to tell GBIF who we are to be polite
$opts = [
    "https" => [
        "method" => "GET",
        "header" => "User-Agent: WFO-Rhakhis/0.9 (GBIF Gap analysis script) \r\n"
    ]
];
$context = stream_context_create($opts);

$sql = "SELECT 
n.id, n.name_alpha, g.id as count_id
FROM `names` as n
LEFT JOIN `taxon_names` as tn on n.id = tn.name_id
LEFT JOIN `gbif_occurrence_count` as g on n.id = g.name_id
WHERE tn.name_id is null
AND (g.modified is null OR g.modified < now() - INTERVAL 3 MONTH) 
AND n.`rank` = 'species'
AND n.`status` != 'deprecated'
AND n.name_alpha not in (
	SELECT n2.name_alpha FROM `names` AS n2 JOIN taxon_names AS tn2 ON n2.id = tn2.name_id WHERE n2.name_alpha = n.name_alpha
)
LIMIT 1000;";

$response = $mysqli->query($sql);
$rows = $response->fetch_all(MYSQLI_ASSOC);
$response->close();

foreach($rows as $row){
    
    $uri = $base_uri . urlencode($row['name_alpha']);
    $gbif_data = json_decode(file_get_contents($uri, false, $context));
    sleep(0.5); // don't do a denial of service attack!
    $count = (int)$gbif_data->count;

    if($row['count_id']){
        $mysqli->query("UPDATE `gbif_occurrence_count` SET `count` = $count WHERE `id` = {$row['count_id']};"); 
        echo $mysqli->error;  
    }else{
        $mysqli->query("INSERT INTO `gbif_occurrence_count` (`name_id`, `count`) VALUES ({$row['id']}, $count)");
        echo $mysqli->error;
    }

    echo "\n$count\t{$row['name_alpha']}";
    
}




