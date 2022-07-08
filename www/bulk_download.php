<?php
set_time_limit(0);

$filename = '../bulk/csv/' . $_GET['file_name'];

if(!file_exists($filename)){
    echo "$filename does not exist";
    exit;
}

header('Content-Disposition: attachment; filename="'. $_GET['file_name'] .'"');
header('Content-type: application/x-gzip');

readfile($filename);


/*
$file=fopen($filename,'r');
$chunk_size = 8 * (1024 * 1024);
while(!feof($file)){
    $line=fread($file, $chunk_size);
    echo $line;
    ob_flush(); 
    flush(); 
}
fclose($file);
exit;
*/

