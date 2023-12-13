<?php

require_once('../config.php');

$sql = "SELECT *
    FROM promethius.references 
    WHERE kind = 'literature' 
    AND display_text LIKE '%&amp;%' 
    OR display_text LIKE '%&lt;%'
    OR display_text LIKE '%<%'";

$response = $mysqli->query($sql);
$rows = $response->fetch_all(MYSQLI_ASSOC);


$count = 0;

foreach($rows as $row){
    $id = $row['id'];
    $old = $row['display_text'];
    $new = html_entity_decode($old);
    $new = strip_tags($new);
    $new_safe = $mysqli->real_escape_string($new);
    $mysqli->query("UPDATE `references` SET display_text = '$new_safe' WHERE id = $id ");
    echo "$count\t$id\n\t$old\n\t$new\n";
    if($mysqli->error){
        echo $mysqli->error;
        exit;
    }
    $count++;
}