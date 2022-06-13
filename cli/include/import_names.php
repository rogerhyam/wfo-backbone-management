<?php

require_once('config.php');

// download the file


// unzip it

// import it into sqlite

echo "\nGoing to import ... ";
exec('sqlite3 data/sqlite.db ".read import.sql"');
echo "\nAll done";



/*
$pdo->exec("DROP TABLE IF EXISTS `names`");
$pdo->exec("CREATE TABLE `names`( 
    wfo_id TEXT PRIMARY KEY,
    scientificNameAuthorship TEXT,
    taxonrank TEXT,
    nomenclaturalStatus TEXT,
    taxonomicStatus TEXT");
*/
