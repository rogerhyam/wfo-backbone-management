<?php
    
$table = $_GET['table_name'];

$mysqli->query("SET SQL_SAFE_UPDATES = 0;");
$mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_skip` = null;");
$mysqli->query("SET SQL_SAFE_UPDATES = 1;");
if($mysqli->error){
    echo $mysqli->error;
    echo $sql;
}else{
    header("Location: index.php?action=view&phase=tables");
}
