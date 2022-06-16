<?php


$table = $_SESSION['selected_table'];
$rhakhis_pk = $_GET['rhakhis_pk'];
$rhakhis_column = $_GET['rhakhis_column'];
$rhakhis_value = $mysqli->real_escape_string($_GET['rhakhis_value']);


$mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `$rhakhis_column` = '$rhakhis_value' WHERE `rhakhis_pk` = $rhakhis_pk;");
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

