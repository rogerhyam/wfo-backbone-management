<?php

// run once script to import flora of nepal treatments.

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/User.php');
require_once('../include/UpdateResponse.php');
require_once('../include/Reference.php');
require_once('../include/ReferenceUsage.php');
require_once('../include/NameMatches.php');
require_once('../include/NameMatcher.php');

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));



$in = fopen('../data/treatments/flora_nepal_treatments.csv', 'r');
$out = fopen('../data/treatments/flora_nepal_treatments_out.csv', 'w');

while($line = fgetcsv($in)){

    $wfo_id = null;

    print_r($line);

    // does it already have a wfo-id
    if(!preg_match('/wfo-[0-9]{10}/',$line[3])){

        // let's look it up using the solr index
        $tester = new NameMatcher();
        $matches = $tester->stringMatch($line[1]);

        if(count($matches->names) == 1){
            $wfo_id = $matches->names[0]->getPrescribedWfoId();
            $line[3] = $wfo_id;
            $line[] = strip_tags($matches->names[0]->getFullNameString());
        }elseif(count($matches->names) == 0){
            $line[3] = '-';
            $line[] = 'nothing found';
        }else{
            $line[3] = '-';
            $line[] = count($matches->names) . ' found';
        }

//        print_r($matches);

    }else{
        $wfo_id = $line[3];
    }
    echo $wfo_id;
    echo "\n-------------------------------\n";
 
    fputcsv($out, $line);

}

fclose($in);
fclose($out);