<?php

// imports the basionyms

set_time_limit(0);

$table = $_GET['table'];

$sql = "SELECT `rhakhis_wfo`, `rhakhis_basionym` FROM `rhakhis_bulk`.`$table` WHERE length(`rhakhis_basionym`) = 13;";

echo "Coming soon!";



