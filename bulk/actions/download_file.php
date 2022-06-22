<?php
set_time_limit(0);

$filename = '../bulk/csv/' . $_GET['file_name'];

if(!file_exists($filename)){
    echo "$filename does not exist";
    exit;
}

header('Content-Disposition: attachment; filename="'. $_GET['file_name'] .'"');

$file=fopen($filename,'r');
while(!feof($file)){
    $line=fread($file,1024);
    echo $line;
}
fclose($file);
exit();

