<?php

require_once('../config.php');

echo "\n-----------------------------------";
echo "\n Checking files for Zenodo upload\n";
echo "-----------------------------------\n";
// firstly check the metadatafile
$meta_path = "../data/versions/zenodo_metadata.json";
if(file_exists('../data/versions/zenodo_metadata.json')){
    echo "Metadata File:\t\t".date("d F  Y H:i:s.", filemtime($meta_path)). "\n";
}else{
    echo "Metadata File:\t\tMISSING!\n";
}

foreach($zenodo_file_map as $name => $path){
    if(file_exists($path)){
      echo "$name\t\t".date("d F Y H:i:s.", filemtime($path)). "\n";
    }else{
        echo "$name\t\tMISSING!\n";
    }
}