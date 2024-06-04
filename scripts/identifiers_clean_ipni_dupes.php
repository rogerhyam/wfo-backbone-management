<?php

require_once('../config.php');

$sql = "SELECT i.name_id, i.`value`, count(*) as n 
from `identifiers` as i 
left join `names` as n on i.id = n.preferred_ipni_id
where i.kind = 'ipni'
and n.preferred_ipni_id is null
group by i.name_id, i.`value` having n > 1
order by n desc;";

$response = $mysqli->query($sql);
$rows = $response->fetch_all(MYSQLI_ASSOC);
$response->close();

foreach($rows as $row){

    $name_id = $row['name_id'];
    $val = $row['value'];

    $response = $mysqli->query("SELECT * FROM `identifiers` as i WHERE i.kind = 'ipni' and i.`value` = '$val' and name_id = $name_id;");
    $dupes = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    $id_ids = array();
    foreach($dupes as $dupe) $id_ids[] = $dupe['id'];

    array_pop($id_ids);

    $mysqli->query("DELETE FROM `identifiers` WHERE id IN (".  implode(',', $id_ids)  .")");

    print_r($id_ids);


}


echo count($rows);