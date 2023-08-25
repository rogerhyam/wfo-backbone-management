<?php

/*
    This concatenates all the DwC files together into a single big file.

*/

// get a list of all the files in the 
$downloads_dir = '../www/downloads/dwc/';
$file_list = glob($downloads_dir . "*.zip");
$out_file_path =  $downloads_dir . "uber_taxonomy.csv";

$out = fopen($out_file_path, 'w');

$first_file = true;
foreach($file_list as $zip_path){

    // don't add our old selves!
    if(strpos($zip_path, 'All_Families.zip')) continue;

    echo $zip_path;
    echo "\n";

    $zip = new ZipArchive;
    $zip->open(realpath($zip_path));
    $in = $zip->getStream('taxonomy.csv');

    // we include the header from the first file
    // but skip in on subsequent files.    
    if(!$first_file){
       fgets($in);
    }else{
        $first_file = false;
    }

    // if we are not on the first file we skip the first line
    while($line = fgets($in)){
        fwrite($out, $line);
    }

}

fclose($out);

echo "\nCreating zip\n";
$zip = new ZipArchive();
$zip_path = $downloads_dir . "All_Families.zip";

if ($zip->open($zip_path, ZIPARCHIVE::CREATE)!==TRUE) {
    exit("cannot open <$zip_path>\n");
}

// create personalize versions of the provenance and meta files for inclusion.

$creation_datestamp = date('Y-m-d\Th:i:s');
$creation_date = date('Y-m-d');

$meta_path = $downloads_dir . "uber.meta.xml";
$meta = file_get_contents('darwin_core_meta.xml');
$meta = str_replace('{{family}}', "All Families", $meta);
$meta = str_replace('{{date}}', $creation_date, $meta);
file_put_contents($meta_path, $meta);

$eml_path = $downloads_dir . "uber.eml.xml";
$eml = file_get_contents('darwin_core_eml.xml');
$eml = str_replace('{{family}}',  "All Families", $eml);
$eml = str_replace('{{date}}', $creation_date, $eml);
$eml = str_replace('{{datestamp}}', $creation_datestamp, $eml);
$eml = str_replace('{{attribution}}', "This is a mashup of all family data. See individual family files for attribution data.", $eml);
file_put_contents($eml_path, $eml);

$zip->addFile($out_file_path, "taxonomy.csv");
$zip->addFile($eml_path, "eml.xml");
$zip->addFile($meta_path, "meta.xml");

if ($zip->close()!==TRUE) {
    exit("cannot close <$zip_path>\n". $zip->getStatusString());
}

echo "Removing temp files\n";
unlink($out_file_path);
unlink($meta_path);
unlink($eml_path);