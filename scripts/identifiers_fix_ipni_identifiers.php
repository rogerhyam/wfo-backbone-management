<?php

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/User.php');
require_once('../include/UpdateResponse.php');

require_once('../include/NameMatches.php');
require_once('../include/NameMatcher.php');
require_once('../include/NameMatcherPlantList.php');


// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

// this is to fix the name mismatching i did when I imported
// the ipni ids the first time.

$counter = 0;
$matched = 0;
$missed = 0;

$offset = 0;
$matcher = new NameMatcherPlantList();

while(true){

    echo "\n--------- Offset $offset --------\n";
/*
    $sql = "SELECT distinct(n.id)
        FROM `names` as n
        JOIN `identifiers` as i on i.name_id = n.id and i.kind = 'ipni' AND i.modified > '2024-06-01'
        order by n.id
        limit 1000 offset $offset";

*/

    $sql = "SELECT distinct(n.id)
        from identifiers as i 
        JOIN `names` as n ON i.name_id = n.id and i.kind = 'ipni' and n.rank = 'genus'
        JOIN `kew`.`ipni` as ipni on i.`value` = ipni.id and ipni.rank_s_alphanum != 'gen.'
        limit 1000 offset $offset;";


    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    if(count($rows) == 0) break;

    foreach($rows as $row){

        $counter++;

        // the name we are dealing with
        $name = Name::getName($row['id']);
        $name_full = strip_tags($name->getFullNameString());
        echo "{$counter}\t{$name->getPrescribedWfoId()}\t{$name_full}\n";

        // get all the associated IPNI IDs that were 
        // added recently
        $response = $mysqli->query("SELECT i.id as identifer_id, ipni.id as ipni_id, concat_ws(' ', ipni.taxon_scientific_name_s_lower, ipni.authors_t) as taxon_name
                FROM identifiers AS i
                JOIN kew.ipni as ipni on i.`value` = ipni.id 
                where i.kind = 'ipni' 
                AND i.modified > '2024-06-01'
                AND i.name_id = {$row['id']};");
        $ipni_rows = $response->fetch_all(MYSQLI_ASSOC);
        $response->close();

        // work through the ipni ids and rematch them
        foreach($ipni_rows as $ipni_row){

            $remove_ipni_id = true;

            echo "\t{$ipni_row['ipni_id']}\t{$ipni_row['taxon_name']}";
            
            // does it match again?
            $matches = $matcher->stringMatch($ipni_row['taxon_name']);

            if($matches->names && count($matches->names) == 1){
                $matched_name = $matches->names[0];
                echo "\t{$matched_name->getPrescribedWfoId()}";
                echo "\t" . strip_tags($matched_name->getFullNameString());

                if($matched_name->getId() == $name->getId()){
                    $remove_ipni_id = false;
                }
            }

            if($remove_ipni_id){
                
                echo "\tBAD\n";

                if($name->getPreferredIpniId() == $ipni_row['ipni_id']){
                    $name->setPreferredIpniId(null);
                    $name->save();
                }

                $mysqli->query("DELETE FROM `identifiers` WHERE id = {$ipni_row['identifer_id']}");

            }else{
                echo "\tGOOD\n";
            }


        }

    }

    $offset = $offset + 1000;
}