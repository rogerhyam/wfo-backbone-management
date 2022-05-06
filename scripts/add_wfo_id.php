<?php

/*
    enable the adding of a WFO ID to an existing 
    name and making it the proscribed ID for that name.

*/
require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/User.php');
require_once('../include/UpdateResponse.php');

echo "\nWFO ID Importer\n";

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

if(count($argv) != 3){

    echo "\nYou need to pass two arguments.\nThe first is the existing WFO ID and second the new one.\nThe new one will become the preferred for that name.\nThe first will be kept as a deduplicated one.\n";
    exit;
}

$wfo_existing = $argv[1];
$wfo_new = $argv[2];

if(!preg_match('/^wfo-[0-9]{10}$/', $wfo_existing)){
    echo "\nThe existing wfo isn't well formed: $wfo_existing \n\n";
    exit;
}

if(!preg_match('/^wfo-[0-9]{10}$/', $wfo_new)){
    echo "\nThe new wfo isn't well formed: $wfo_new \n\n";
    exit;
}

// see if new wfo really is new.
$name = Name::getName($wfo_new);

if($name->getId()){
    echo "\nNew WFO ID ($wfo_new) is in use for name: ". strip_tags($name->getFullNameString())  ." \n\n";

    print_r($name);

    exit;
}

$name = Name::getName($wfo_existing);

if(!$name->getId()){
    echo "\nNo name found for existing WFO ID ($wfo_new) \n\n";
    exit;
}
$name_string = strip_tags($name->getFullNameString());
echo "\nAdding $wfo_new to {$name_string}";

$name->setPrescribedWfoId($wfo_new);
$name->save();

$name_check = Name::getName($wfo_new);
if($name_check->getPrescribedWfoId() == $wfo_new){
    echo "\nNew WFO id is set.\n\n";
}else{
    echo "\nFailed\n\n";
}

