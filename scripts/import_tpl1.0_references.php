<?php

/*
   import the IPNI records from the wfo dump as references

   this is probably a run once script

   php -d memory_limit=10G import_tpl1.0_references.php

*/
require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/User.php');
require_once('../include/UpdateResponse.php');
require_once('../include/Reference.php');
require_once('../include/ReferenceUsage.php');

echo "\nTPL 1.0 Ref Importer\n";

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

$offset = 0;

$sql = "SELECT 
	b.taxonID as wfo, 
    b.`references1.0` as linkUri,
    concat('The Plant List version 1.0, record: ', trim(substr(b.`references1.0`, 40))) as displayText
    FROM 
    botalista_dump_2 as b
    JOIN identifiers as i on i.`value` = b.taxonID
    JOIN `names` as n on n.prescribed_id = i.id
    where b.`references1.0` like 'http://www.theplantlist.org/tpl/record/%'";

$result = $mysqli->query($sql);

$counter = $offset; // start where we left off.
while($row = $result->fetch_assoc()){

    // get the reference by uri first - check we don't over create.
    $ref = Reference::getReferenceByUri($row['linkUri']);
    if(!$ref){
        $ref = Reference::getReference(null);
        $ref->setLinkUri($row['linkUri']);
    }

    $ref->setDisplayText($row['displayText']);
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

    $name->addReference($ref, null, false);

    $counter++;

    echo "\n$counter\t{$row['wfo']}";

}

