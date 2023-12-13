<?php

/*

This will prune off chained basionyms we may have
imported but not remove all.

logic goes comb_nov -1-> basionym -2-> chained_basionym.
if basionym does not have paranthetical authors I simply snip 2
if basionym does have parenthetical authors but chained_basionym does not I snip 1 and link comb_nov to chained_basionym

*/

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/User.php');
require_once('../include/UpdateResponse.php');

// we need to have a mock session to change stuff
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

$sql = "SELECT
	cni.`value` as 'com_nov_id',
    com_novs.name_alpha as com_novs_name, com_novs.authors as com_novs_authors,
    bi.`value` as basionym_id,
    basionyms.name_alpha as basionym_name, 
    basionyms.authors as basionym_authors,
    cbi.`value` as 'chained_basionym_id',
    chained_basionyms.name_alpha as 'chained_basionym_name',
    chained_basionyms.authors as 'chained_basionym_authors'
    FROM `names` as com_novs 
    JOIN `names` as basionyms on com_novs.basionym_id = basionyms.id
    JOIN `names` AS chained_basionyms on basionyms.basionym_id = chained_basionyms.id
    JOIN identifiers as bi ON basionyms.prescribed_id = bi.id
    JOIN identifiers as cni ON com_novs.prescribed_id = cni.id
    JOIN identifiers as cbi ON chained_basionyms.prescribed_id = cbi.id
    where basionyms.basionym_id is not null";

$response = $mysqli->query($sql);
$rows = $response->fetch_all(MYSQLI_ASSOC);


$count = 0;

foreach($rows as $row){

    echo "\n---- $count ----\n";

    print_r($row);

    if(!preg_match('/\(.+\)/', $row['basionym_authors'])){
        echo "No parenthetical authors so removing link to chained name\n";
        $name = Name::getName($row['basionym_id']);
        $name->setBasionym(null);
        $name->save();
    }else{
        echo "Parenthetical author so moving comb nov to chained basionym;";
        $com_nov = Name::getName($row['com_nov_id']);
        $chained_basionym = Name::getName($row['chained_basionym_id']);
        $com_nov->setBasionym($chained_basionym);
        $com_nov->save();
    }

    $count++;

}