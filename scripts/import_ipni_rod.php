<?php

/*

Used to mess with Rods data

Rod's citation

DOI Link derived from "IPNI with Literature" (Version 2023-05-26) DOI:10.5281/zenodo.7208699

28,500 - unique dois
151,754 - names they will be added to
89,698 - these already have references to BHL but links are different.
62,056 - new names that are linked to literature.

"Link imported from Rod Page (2023) doi:10.5281/zenodo.7974720"

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

echo "\nImport Rod's DOI references\n";

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

// work through Rod's list
$offset = 0;
while(true){

    // keep resetting the singltons or we will run out of memory
    Name::resetSingletons();
    Reference::resetSingletons();

    // take the next bite from the cake
    $response = $mysqli->query("SELECT * FROM kew.rod_dois where wfo_id is not null order by doi limit 1000 offset $offset;");
    if($response->num_rows == 0) break;
    $offset += 1000;
    $rows = $response->fetch_all(MYSQLI_ASSOC);

    // work through this slice of cake
    foreach ($rows as $ref_row) {

        $uri = $ref_row['doi'];
        $display = $ref_row['citation_full'];
        echo "\n$uri";

        if(!$ref_row['wfo_id']){
            echo "\tNo WFO ID set.";
            continue;
        }

        // no json
        if(preg_match('/^{/', $display)) $display = $ref_row['doi'];

        // no HTML
        if(preg_match('/<html/', $display)) $display = $ref_row['doi'];

        // max length
        if(strlen($display) > 1000) $display = substr($ref_row['apa_citation'], 0, 995) . " ...";
        
        // do we have a reference for this uri?
        $ref = Reference::getReferenceByUri($uri);

        if(!$ref){
            // no ref so create it
            $ref = Reference::getReference(null);
            $ref->setKind('literature');
            $ref->setLinkUri($uri);
            $ref->setDisplayText($display); // truncate at 1000  
            $ref->setUserId(1);
            $update_response = $ref->save();
            if(!$update_response->success){
                print_r($update_response);
                exit;
            }
            echo "\n\tCreated:\t" . $ref->getId();
        }else{
            echo "\n\tExists:\t" . $ref->getId();
        }


        // we must have a reference now - fresh or old.
        // get the name it is joined to
        $name = Name::getName($ref_row['wfo_id']);
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
            $name->addReference($ref, "DOI link imported from Page, R. (2023). doi:10.5281/zenodo.7974720", false);
            echo "\n\tAdded";
        }else{
            echo "\n\tAlready present";
        }

       

    }
    
    echo "\n------- $offset -------";

}