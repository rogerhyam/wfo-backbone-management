<?php

require_once("../config.php");
require_once("../include/WfoDbObject.php");
require_once("../include/Name.php");
require_once("../include/Taxon.php");
require_once("../include/User.php");
require_once("../include/Identifier.php");
require_once("../include/Reference.php");
require_once("../include/ReferenceUsage.php");
require_once("../include/UpdateResponse.php");

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

// get a list of the duplicate genera

$sql = "SELECT id, `name`
    FROM `names` as n2
    WHERE `rank` = 'genus'
    AND `name` in (
        SELECT `name` FROM `names` WHERE `rank` = 'genus' GROUP BY `name` HAVING count(*) > 1
    )
    order by name_alpha";

$response = $mysqli->query($sql);

$set = null;
$current_name = '';
while($row = $response->fetch_assoc()){

    // are we starting a new set
    if($row['name'] != $current_name){
        process_set($set);
        $set = array();
        $current_name = $row['name'];
    }

    $set[] = Name::getName($row['id']);

}

function process_set($set){

    // do nothing if we are passed nothing
    // how we start out actually
    if(!$set) return null;

    $good_name = null;
    $bad_name = null;

    foreach($set as $name){
        if(
            $name->getStatus() == 'deprecated'
            &&
            !$name->getCitationMicro()
            &&
            !$name->getAuthorsString()
        ){
            $bad_name = $name;
        }else{
            $good_name = $name;
        }

        if($good_name && $bad_name) break;
    }

    if($good_name && $bad_name){
        echo "\nkill:\t" . $bad_name->getPrescribedWfoId() . "\t" . strip_tags($bad_name->getFullNameString());
        echo "\nkeep:\t" . $good_name->getPrescribedWfoId() . "\t" . strip_tags($good_name->getFullNameString());
        $bad_name->deduplicate_into($good_name);
    }


}