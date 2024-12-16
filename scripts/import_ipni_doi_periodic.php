<?php

/*

    Run periodically to import DOIs of recently created IPNI entries.
    Finds the DOIs in recent comments. 
    Calls DOIs to get the citations.
    Adds them to the associated names.

    php -d memory_limit=1G import_ipni_doi_periodic.php

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

// we can pass in the number of days to go back in time
if(count($argv) > 1){
    $since_days = $argv[1];
}else{
    $since_days = 60;
}


echo "\nIPNI DOI reference importer - periodic";
echo "\n\tFinding DOIs in names added in the last $since_days days;";
echo "\n\t(You can pass the number of days to the script if needed)\n";

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

$offset = 0;

// work through all the refs we have
while(true){

    // keep resetting the singltons or we will run out of memory
    Name::resetSingletons();
    Reference::resetSingletons();

    // fetch the dois in the recently added names
    $sql = "SELECT 
            i.name_id as wfo_name_id,
            REGEXP_SUBSTR(remarks_s_lower, 'doi:10\.[0-9]{4,9}/[^ ]*') as doi,
            date_created_date
            FROM kew.ipni as ipni
            JOIN identifiers as i on i.`value` = ipni.id AND i.`kind` = 'ipni'   
            where remarks_s_lower like '%doi:%'
            AND date_created_date > now() - INTERVAL $since_days DAY
            order by date_created_date DESC
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
     
        // do we have a reference for this uri?
        $ref = Reference::getReferenceByUri($uri);

        if(!$ref){
            // no ref so create it

            $display = fetch_citation($uri, $ref_row['doi']);

            if(!$display){
                echo "\n\tNo citation retrieved.";
                continue;
            }

            echo "\n$display";

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
            $name->addReference($ref, "DOI link from IPNI data.", false);
            echo "\n\tAdded";
        }else{
            echo "\n\tAlready present";
        }

    }
    
    $offset += 1000;

    echo "\n------- $offset -------\n";

}

echo "\nComplete\n";

function fetch_citation($uri, $doi){

    // curl -LH "Accept: text/x-bibliography; style=apa" https://doi.org/10.9735/0976-9889.5.1.35-38
    $ch = curl_init($uri);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); //timeout in seconds

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Accept: text/x-bibliography; style=apa"
    ));

    //curl_setopt($ch, CURLOPT_HEADER, 1);
    $citation = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    // filter out things that don't look good    
    if(!$citation) return null;
    if($code == 200){

        // they send json we use the doi
        if(preg_match('/^{/', $citation)) return $doi;

        // they send HTML we use the doi
        if(preg_match('/<html/', $citation)) return $doi;

        // max length
        if(strlen($citation) > 1000) $citation = substr($citation, 0, 995) . " ...";

        // clean up the citation which may have markup in it
        $citation = str_replace('&Apos;', '&apos;', $citation);
        $citation = str_replace('&nbsp;', ' ', $citation);

        $citation = html_entity_decode($citation, ENT_QUOTES, 'UTF-8');
        $citation = strip_tags($citation); 

        // OK we have a string that looks good return that
        return $citation;
    
    }else{
        return null;
    }


}

