<?php

require_once('../config.php');

// the names_log file will grow indefinitely
// this script will delete every record over 6 months old
// records older than 6 months can be recovered from the 
// solstice dumps if that should ever be necessary.
// not a long script!
/*

ALTER TABLE `promethius`.`names_log` 
ADD INDEX `modified` USING BTREE (`modified`) VISIBLE;

*/
echo "\nRemoving name logs over 6 months old.\n";
$mysqli->query("DELETE FROM names_log where modified < now() - interval 6 month;");
echo number_format($mysqli->affected_rows, 0) . " rows removed.\n";
echo "Done.\n";