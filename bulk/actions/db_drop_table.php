<?php

$drop_table_name = $_GET['drop_table_name'];

$response = $mysqli->query("DROP TABLE `rhakhis_bulk`.`$drop_table_name`;");
if($mysqli->error){
    echo $mysqli->error;
}else{
    header("Location: index.php?action=view&phase=tables");
}
