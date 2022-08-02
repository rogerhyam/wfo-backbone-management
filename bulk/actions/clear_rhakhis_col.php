<?php
    
$table = $_GET['table_name'];
$rhakhis_col = 'rhakhis_' . $_GET['rhakhis_col'];
$calling_action = $_GET['calling_action'];
$calling_phase = $_GET['calling_phase'];
$calling_task = @$_GET['calling_task'];

$mysqli->query("SET SQL_SAFE_UPDATES = 0;");
$mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `$rhakhis_col` = null;");
$mysqli->query("SET SQL_SAFE_UPDATES = 1;");
if($mysqli->error){
    echo $mysqli->error;
    echo $sql;
}else{
    header("Location: index.php?action=$calling_action&phase=$calling_phase&task=$calling_task");
}
