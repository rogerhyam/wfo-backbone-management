<?php

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/User.php');
require_once('../include/UpdateResponse.php');
require_once('../include/Reference.php');
require_once('../include/ReferenceUsage.php');
require_once('../include/AuthorTeam.php');
require_once('../include/SPARQLQueryDispatcher.php');


echo "\nPflanzenreich reference importer\n";

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

$response = $mysqli->query("SELECT * FROM kew.pflanzenreich_items");
$rows = $response->fetch_all(MYSQLI_ASSOC);
$response->close();

foreach($rows as $row){

    echo "\n{$row['name_alpha']}";

    // do we have a reference for this uri?
    $ref = Reference::getReferenceByUri($row['bhl_link']);
    if(!$ref){
        // no ref so create it
        $ref = Reference::getReference(null);
        $ref->setKind('literature');
        $ref->setLinkUri($row['bhl_link']);
        if($row['bhl_thumb']) $ref->setThumbnailUri($row['bhl_thumb']);
        $ref->setDisplayText($row['title']);  
        $ref->setUserId(1);
        $ref->save();
        echo "\n\tCreated:\t" . $ref->getId();
    }else{
        echo "\n\tExists:\t" . $ref->getId();
    }

    // we must have a reference now - fresh or old.
    // get the name it is joined to
    $name = Name::getName($row['name_id']);
    echo "\n\t" . $name->getPrescribedWfoId();

    // is it already attached?
    $already_there = false; 
    foreach($name->getReferences() as $usage){
        if($usage->reference->getId() == $ref->getId()){
            $already_there = true;
            break;
        }
    }

    // do we need to attach it to the name?
    if(!$already_there){
        $name->addReference($ref, "From parsing micro citation.", false);
        echo "\n\tAdded";
    }else{
        echo "\n\tAlready present";
    }

}


/*

https://www.biodiversitylibrary.org/api3?op=GetTitleMetadata&format=json&items=true&id=250&apikey=a3d3fd5f-e612-41b5-b72c-e4f36bcad8f7


get part metadata
https://www.biodiversitylibrary.org/api3?op=GetPartMetadata&format=json&items=true&id=68126&apikey=a3d3fd5f-e612-41b5-b72c-e4f36bcad8f7

Item metadata - for thumbnail

https://www.biodiversitylibrary.org/api3?op=GetItemMetadata&format=json&pages=false&id=181318&apikey=a3d3fd5f-e612-41b5-b72c-e4f36bcad8f7


*/






/*
$api_key = "a3d3fd5f-e612-41b5-b72c-e4f36bcad8f7";

$item_query_url =  "https://www.biodiversitylibrary.org/api3?op=GetItemMetadata&format=json&pages=false&apikey=a3d3fd5f-e612-41b5-b72c-e4f36bcad8f7&id=";

$response = $mysqli->query("SELECT * FROM sandbox.pflanzenreich_items");
echo $mysqli->error;
$rows = $response->fetch_all(MYSQLI_ASSOC);
$response->close();

$cache = array();

foreach($rows as $row){

    $bhl_id = str_replace('https://www.biodiversitylibrary.org/item/', '', $row['bhl_link']);
    echo "$bhl_id\n";

    $thumb_url = null;
    if(!isset($cache[$bhl_id])){

        $json = file_get_contents($item_query_url . $bhl_id);
        $data = json_decode($json);

        if($data->Status != 'ok'){
            print_r($data);
            exit;
        }
        $thumb_url = $data->Result[0]->ItemThumbUrl;
        $cache[$bhl_id] = $thumb_url;
    }else{
        $thumb_url = $cache[$bhl_id];
    }


    // set it in the database
    $mysqli->query("UPDATE sandbox.pflanzenreich_items SET bhl_thumb = '$thumb_url' WHERE name_id = {$row['name_id']} ");

}




$response = $mysqli->query("SELECT * FROM sandbox.pflanzenreich order by `year`");
echo $mysqli->error;
$rows = $response->fetch_all(MYSQLI_ASSOC);
$response->close();

foreach($rows as $row){

//    echo $row['name_alpha'] . "\n";

    $year = $row['year'];

    $response = $mysqli->query("SELECT * FROM sandbox.pflanzenreich_heft where `year` = '$year'");
    //if($mysqli->error) echo $mysqli->error; exit;
    $hefts = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    if(count($hefts) == 0){
        echo "\n No heft for year: $year"; 
        continue;
    }

    $heft = null;

    if(count($hefts) == 1){
        $heft = $hefts[0];
    }else{

        // more than one heft in that year.
        // can we find one that has the 
        $candidates = array();
        foreach($hefts as $candi){
            // is this heft number in the micro_citation
            $citation = $row['citation_micro'];
            $heft_number = $candi['Heft'];

            $pattern = '/[^0-9]' . $heft_number  . '[^0-9]/';
            if(preg_match($pattern, $citation)){
                $candidates[] = $candi;
            }
        }

        if(count($candidates) == 1){
            $heft = $candidates[0];
        }

    }

    if($heft){
        echo "Match:\t{$row['name_alpha']}\t{$row['citation_micro']}\t{$heft['Heft']}\n";
        $mysqli->query("UPDATE sandbox.pflanzenreich SET bhl_id = {$heft['BHL Item ID']} WHERE id = {$row['id']} ");
    }else{
        //echo "No match:\t{$row['name_alpha']}\t{$row['citation_micro']}\n";
    }


}

*/
