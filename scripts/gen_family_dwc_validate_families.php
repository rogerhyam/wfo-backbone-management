<?php

/*

    Check that each name occurs in only one family
    even if it occurs in multiple family dwc files.

    Loads the whole thing in memory!

    php -d memory_limit=10G gen_family_dwc_validate_families.php

*/

// get a list of all the files in the 
$downloads_dir = '../www/downloads/dwc/';
$file_list = glob($downloads_dir . "*.zip");

$families = array(); // this is going to get big!

$first_file = true;
foreach($file_list as $zip_path){

    // only do the family files - not the _uber files
    if(!preg_match('/_wfo-/', $zip_path)){
        echo "Skipping $zip_path \n";
        continue;
    }

    echo $zip_path;
    echo "\n";

    $zip = new ZipArchive;
    $zip->open(realpath($zip_path));
    $in = $zip->getStream('classification.csv');

    // if this is the first file we need to find out 
    // the column for the family name
    if($first_file){
        $first_file = false;
        $header = fgetcsv($in);
        $wfo_col_index = (int)array_search('taxonID', $header);
        $family_col_index = (int)array_search('family', $header);
    }else{
        fgetcsv($in); // just skip the header
    }

    // work through all the lines
    while($line = fgetcsv($in)){

        $wfo = $line[$wfo_col_index];
        $family = $line[$family_col_index];

        if(isset($families[$wfo])){

            if($families[$wfo] != $family){
                echo "wfo: $wfo has family $family when previously it had $families[$wfo]\n";
                print_r($line);
                exit;
            }

        }else{
            $families[$wfo] = $family;
        }

    }

}

echo "All complete\n";