<?php

require_once('../config.php');

// the names_log file will grow indefinitely
// this script will delete every record over 6 months old
// records older than 6 months can be recovered from the 
// solstice dumps if that should ever be necessary.

/*

ALTER TABLE `promethius`.`names_log` 
ADD INDEX `modified` USING BTREE (`modified`) VISIBLE;

*/


$sql = "DELETE FROM names_log ";