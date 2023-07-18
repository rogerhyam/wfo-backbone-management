<?php

/*

This is a run once script to pull in BHL references scraped from IPNI.

php -d memory_limit=1G import_ipni_bhl.php

*/

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/User.php');
require_once('../include/UpdateResponse.php');
require_once('../include/Reference.php');
require_once('../include/ReferenceUsage.php');
require_once('../include/AuthorTeam.php');
require_once('../include/SPARQLQueryDispatcher.php');

echo "\nBHL reference importer\n";

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

$offset = 0;

// work through all the refs we have
while(true){

    // keep resetting the singltons or we will run out of memory
    Name::resetSingletons();
    Reference::resetSingletons();

    $sql = "SELECT 
        i.name_id as wfo_name_id, k.*, ipni.reference_t 
        FROM kew.protologs as k 
        join identifiers as i on i.`value` = k.name_id and i.kind = 'ipni'
        join kew.ipni on k.name_id = ipni.id
        where k.ref_url is not null
        order by k.ref_url
        limit 1000
        offset $offset";
    
    $response = $mysqli->query($sql);

    if($mysqli->error){
        echo $mysqli->error;
        exit;
    }
    if($response->num_rows == 0) break;
    $ref_rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    foreach($ref_rows as $ref_row){

        echo "\n{$ref_row['ref_url']}";

        // do we have a reference for this uri?
        $ref = Reference::getReferenceByUri($ref_row['ref_url']);
        if(!$ref){
            // no ref so create it
            $ref = Reference::getReference(null);
            $ref->setKind('literature');
            $ref->setLinkUri($ref_row['ref_url']);
            if($ref_row['thumb_url']) $ref->setThumbnailUri($ref_row['thumb_url']);
            $ref->setDisplayText($ref_row['reference_t']);  
            $ref->setUserId(1);
            $ref->save();
            echo "\n\tCreated:\t" . $ref->getId();
        }else{
            echo "\n\tExists:\t" . $ref->getId();
        }

        // we must have a reference now - fresh or old.
        // get the name it is joined to
        $name = Name::getName($ref_row['wfo_name_id']);
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
            $name->addReference($ref, "From IPNI metadata.", false);
            echo "\n\tAdded";
        }else{
            echo "\n\tAlready present";
        }

    }
    
    $offset += 1000;

    echo "\n------- $offset -------";

}

