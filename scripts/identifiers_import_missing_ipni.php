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

// get a list of names with multiple ipni IDs but no preferred one.


$counter = 0;
$matched = 0;

$offset = 0;
$matcher = new NameMatcherPlantList();

while(true){

    echo "\n--------- Offset $offset --------\n";

    $sql = "SELECT k.id, concat_ws(' ', k.taxon_scientific_name_s_lower, k.authors_t) as taxon_name, k.suppressed_b, k.top_copy_b
        from kew.ipni as k
        left join identifiers as i on i.`value` = k.id and i.kind = 'ipni'
        where i.id is null
        order by k.id
        limit 1000 offset $offset";

    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    if(count($rows) == 0) break;
    else $offset = $offset + 1000;

    foreach($rows as $row){

        $counter++;

        echo "{$row['id']}\t{$row['taxon_name']}";

        $matches = $matcher->stringMatch($row['taxon_name']);

        if($matches->names && count($matches->names) == 1){
            $matched++;
            $name = $matches->names[0];
            echo "\t{$name->getPrescribedWfoId()}";
            $name->addIdentifier($row['id'], 'ipni');
        }else{
            echo "\t ". count($matches->names) ." candidates found.";
        }

        $percent = number_format(($matched/$counter) * 100, 0);
        echo "\t$matched/$counter\t$percent%";

        echo "\n";
    }


}