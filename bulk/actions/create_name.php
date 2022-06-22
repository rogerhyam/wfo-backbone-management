<?php

$name = Name::getName(-1);

$name->setRank($_GET['rank_string']);
$name->setStatus('unknown');

$name->setNameString($_GET['name_string']);
$name->setAuthorsString($_GET['authors_string']);
if(@$_GET['species_string']){
    $name->setSpeciesString($_GET['species_string']);
}
if(@$_GET['genus_string']){
    $name->setGenusString($_GET['genus_string']);
}

$name->save();

$wfo = $name->getPrescribedWfoId();

$table = $_SESSION['selected_table'];
$rhakhis_pk = $_GET['rhakhis_pk'];

$mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_wfo` = '$wfo' WHERE `rhakhis_pk` = $rhakhis_pk;");
if($mysqli->error){
    echo $mysqli->error;
}else{
    $params = $_GET;
    $params['action'] = $params['calling_action'];
    unset($params['rhakhis_pk']);
    unset($params['rhakhis_column']);
    unset($params['rhakhis_value']);
    unset($params['calling_action']);
    $query_string = http_build_query($params);
    header("Location: index.php?$query_string");
}
