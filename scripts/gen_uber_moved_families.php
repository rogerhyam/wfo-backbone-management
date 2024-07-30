<?php

    // Given two DwC Uber zip files this will
    // create a list of all the names that have moved

    // run with php -d memory_limit=10G gen_uber_moved_families.php

    echo "\nDwC Moved families check.\n\n";

    if(count($argv) != 3){
        // didn't pass a path so check all files in ../www/downloads/dwc/
        echo "\nYou need to pass the paths to two DwC zip files: OLD and NEW.\n";
        exit;
    }

    $old_path = $argv[1];
    if(!file_exists($old_path)){
        echo "Can't find $old_path \n";
        exit;
    }

    $new_path = $argv[2];
    if(!file_exists($new_path)){
        echo "Can't find $new_path \n";
        exit;
    }

    // read the whole of old file into an array - needs lots of memory
    echo "\tReading old file into memory.\n";

    $in = fopen('zip://' . $old_path . '#classification.csv' , 'r');
   
    if(!$in){
        echo "\tFailed to read classification.csv in zip file\n";
        exit;
    }else{
        echo "\tOpened in classification.csv in zip file\n";
    }

    $old_lookup = array();

    $header = fgetcsv($in);

    echo "\tRead header\n";

    $taxon_id_index = array_search('taxonID', $header);
    $family_index = array_search('family', $header);
    $rank_index = array_search('taxonRank', $header);
    $name_index = array_search('scientificName', $header);

    while($line = fgetcsv($in)){
        $old_lookup[$line[$taxon_id_index]] = $line[$family_index];
        echo number_format(count($old_lookup), 0) . "\t" . $line[$name_index] . "\n";
    }

    fclose($in);
    
    echo "\nDone reading old file. Starting reading new file.\n";



    $in = fopen('zip://' . $new_path . '#classification.csv' , 'r');
   
    if(!$in){
        echo "\tFailed to read classification.csv in zip file\n";
        exit;
    }else{
        echo "\tOpened in classification.csv in zip file\n";
    }

    $header = fgetcsv($in);

    echo "\tRead header\n";

    $taxon_id_index = array_search('taxonID', $header);
    $family_index = array_search('family', $header);
    $rank_index = array_search('taxonRank', $header);
    $name_index = array_search('scientificName', $header);

    $out = fopen('moved.csv', 'w');
    array_unshift($header, 'old_family');
    fputcsv($out, $header);

    $counter = 0;
    while($line = fgetcsv($in)){

        $wfo = $line[$taxon_id_index];
        $new_family = $line[$family_index];

        if(isset($old_lookup[$wfo]) && $old_lookup[$wfo] != $new_family){
            array_unshift($line,$old_lookup[$wfo]);
            fputcsv($out, $line);
        }
        $counter++;
        echo number_format($counter, 0) . "\t" . $line[$name_index] . "\n";
    }

    fclose($in);
    fclose($out);

?>