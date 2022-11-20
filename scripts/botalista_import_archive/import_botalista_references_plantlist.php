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
    substring(`references`, 43) as record_id
FROM botalista_dump_2 
WHERE `references` LIKE 'http://www.theplantlist.org/tpl1.1/record/%'
AND (length(parentNameUsageID) >0 OR length(acceptedNameUsageID) > 0);";

// 949228  1 - before fab import

$result = $mysqli->query($sql);

$counter = 0;
$existing_refs = 0;

while($row = $result->fetch_assoc()){

    Name::resetSingletons();
    Reference::resetSingletons();

    // get the name first and see if it has a taxonomy reference
    $name = Name::getName($row['wfo']);
    if(!$name){
        echo "\n No name {$row['wfo']} skipping.";
        $counter++;
        continue;
    }

    echo "\n$counter\t$existing_refs\t{$row['wfo']}";

     // get the references for the name
    $refs = $name->getReferences();
    foreach($refs as $usage){
        if($usage->subjectType == 'taxon'){
            echo "\tEXISTS";
            $existing_refs++;
            continue 2;
        }
    }

    echo "\tCREATING";

    // get the reference by uri first - check we don't over create.
    $ref = Reference::getReferenceByUri($row['uri']);
    if(!$ref){
        $ref = Reference::getReference(null);
        $ref->setLinkUri($row['uri']);
    }

    // generate a display text
    $display_text = "The Plant List v1.1 record {$row['record_id']}";
    $comment = "Based on the initial data import";

    $ref->setDisplayText($display_text);
    $ref->setKind('database');
    $ref->setUserId(1);
    $ref->save();

    if(!$ref->getId()){
        print_r($ref);
        exit;
    }

    // check we haven't already got it
    $usages = $name->getReferences();
    foreach($usages as $usage){
        if($usage->reference->getId() == $ref->getId() && $usage->subjectType == 'taxon'){
            echo "\t Already got ref.";
            $counter++;
            continue 2;
        }
    }

    $name->addReference($ref, $comment, true);

    $counter++;


}


