<?php

/*
    One off script to create taxonomy references 
    for the URLs in the 'references' column of botalista

*/

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/User.php');
require_once('../include/UpdateResponse.php');
require_once('../include/Reference.php');
require_once('../include/ReferenceUsage.php');

$_SESSION['user'] = serialize(User::loadUserForDbId(1));

$sql = "SELECT 
	`taxonID` as wfo,
	`references` as 'uri',
    substr(`references`, 46) as 'record_id'
FROM botalista_dump_2 
WHERE `references`
LIKE 'https://www.floredafriq%';";

// https://www.floredafriquecentrale.be/species/S562265

$result = $mysqli->query($sql);

$counter = 0;

while($row = $result->fetch_assoc()){

    Name::resetSingletons();

    // get the reference by uri first - check we don't over create.
    $ref = Reference::getReferenceByUri($row['uri']);
    if(!$ref){
        $ref = Reference::getReference(null);
        $ref->setLinkUri($row['uri']);
    }

    // generate a display text
    $display_text = "Flora of Central Africa record {$row['record_id']}";
    $comment = "Based on the initial data import";

    $ref->setDisplayText($display_text);
    $ref->setKind('database');
    $ref->setUserId(1);
    $ref->save();

    if(!$ref->getId()){
        print_r($ref);
        exit;
    }

    // get the name
    $name = Name::getName($row['wfo']);
    if(!$name){
        echo "\n No name {$row['wfo']} skipping.";
        $counter++;
        continue;
    }

    // check we haven't already got it
    $usages = $name->getReferences();
    foreach($usages as $usage){
        if($usage->reference->getId() == $ref->getId()){
            echo "\n Already got ref.";
            $counter++;
            continue 2;
        }
    }

    $name->addReference($ref, $comment, true);

    $counter++;

    echo "\n$counter\t{$row['wfo']}";

}


