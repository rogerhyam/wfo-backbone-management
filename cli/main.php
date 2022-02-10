<?php

require_once('include/functions.php');

// some variables
$ini_file_path = "rhakhis.ini";


echo "\nHi, I am Rhakhis CLI!\n";


// is there an ini file?
if(!file_exists($ini_file_path)){

    echo "I have created template file called rhakhis.ini for you.\n";
    echo "Please edit this file and add your api access key.\n";
    echo "Then run this script again to proceed.\n";
    include("default_rhakhis.ini");
    file_put_contents('rhakhis.ini', $default_rhakhis_ini);
    exit;

}


// did they pass a file?

// is there a working file?



include('usage.php');


