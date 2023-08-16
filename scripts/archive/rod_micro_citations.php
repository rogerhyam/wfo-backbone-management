<?php 

// call Rod's microcitation parser

require_once("../config.php");
require_once("../include/WfoDbObject.php");
require_once("../include/Name.php");
require_once("../include/Taxon.php");
require_once("../include/Reference.php");

$rods_api_uri = "https://microcitation-parser.herokuapp.com/bhl.php?q=";

$offset = 0;

while(true){

    // try and free some memory between pages.
    Taxon::resetSingletons();
    Name::resetSingletons();
    Reference::resetSingletons();

    $sql = "SELECT id from `names` WHERE `status` != 'deprecated' and length(citation_micro) > 0 ORDER BY id LIMIT 1000 OFFSET $offset";
    echo "\n$sql\n";
    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    if(count($rows) == 0) break;
    
    foreach($rows as $row){

        $name = Name::getName($row['id']);
        $name_string = strip_tags($name->getFullNameString(false));
        $wfo = $name->getPrescribedWfoId();

        $uri = $rods_api_uri . urlencode($name->getCitationMicro());

        echo "\n\n$uri\n";
        echo "$wfo\t{$name_string}\t{$name->getCitationMicro()}\t";

        // skip it if it is already in the table.
        $response = $mysqli->query("SELECT * FROM `rhakhis_bulk`.`bhl_mapping` WHERE wfo_id = '$wfo'");
        if($response->num_rows > 0){
            echo "Done before\n";
            continue;
        } 
        $response->close();

        $json = file_get_contents($uri);
        $data = json_decode($json);

        $sql = "INSERT INTO `rhakhis_bulk`.`bhl_mapping` (`wfo_id`, `name_full`, `micro_citation`, `bhl_title_id`, `bhl_page_id`) VALUES (";
        
        $sql .= "'{$name->getPrescribedWfoId()}', ";
        $sql .= "'$name_string', ";
        $sql .= "'" . $mysqli->real_escape_string($name->getCitationMicro()) . "', ";

        if(isset($data->data->BHLTITLEID)){
            $sql .= "{$data->data->BHLTITLEID[0]}, ";
            echo $data->data->BHLTITLEID[0] . "\t";
        }else{
            $sql .= "NULL, ";
            echo "NULL\t";
        }

        if(isset($data->data->BHLPAGEID)){
            $sql .= "{$data->data->BHLPAGEID[0]} ";
            echo $data->data->BHLPAGEID[0] . "\t";
        }else{
            $sql .= "NULL ";
            echo "NULL\n";
        }
        
        $sql .= ")\n";

        $mysqli->query($sql);
        echo $mysqli->error;

    }

    $offset = $offset + 1000;
}
