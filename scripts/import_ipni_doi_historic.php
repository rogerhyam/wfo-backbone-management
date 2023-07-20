<?php

/*

    This is a run once script to create historic dois

    php -d memory_limit=1G import_ipni_doi_historic.php

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

echo "\nIPNI DOI reference importer\n";

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

$offset = 0;

// work through all the refs we have
while(true){

    // keep resetting the singltons or we will run out of memory
    Name::resetSingletons();
    Reference::resetSingletons();

    $sql = "SELECT i.name_id as wfo_name_id, doi.doi, doi.apa_citation 
            FROM kew.ipni_doi as doi
            JOIN identifiers as i on i.`value` = doi.id and i.kind = 'ipni'
            WHERE length(doi.apa_citation) > 0
            ORDER BY doi.doi
            LIMIT 1000
            OFFSET $offset";
    
    $response = $mysqli->query($sql);

    if($mysqli->error){
        echo $mysqli->error;
        exit;
    }
    
    if($response->num_rows == 0) break;

    $ref_rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    foreach($ref_rows as $ref_row){

        echo "\n{$ref_row['doi']}";

        $uri = preg_replace('/^doi:/', 'https://doi.org/', $ref_row['doi'] );
        $display = $ref_row['apa_citation'];

        // no json
        if(preg_match('/^{/', $display)) continue;

        // no HTML
        if(preg_match('/<html/', $display)) continue;

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
            $name->addReference($ref, "DOI link generated from IPNI data.", false);
            echo "\n\tAdded";
        }else{
            echo "\n\tAlready present";
        }

    }
    
    $offset += 1000;

    echo "\n------- $offset -------";

}

echo "\nComplete\n";

