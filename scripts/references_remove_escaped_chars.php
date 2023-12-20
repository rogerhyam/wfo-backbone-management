<?php

require_once('../config.php');

echo "\nRemoving escaped characters from references.\n";

    $sql = "SELECT *
    FROM promethius.references 
    WHERE kind = 'literature' 
    AND REGEXP_LIKE(display_text, '&[^ ]+;')
    OR display_text LIKE '%<%';";

$response = $mysqli->query($sql);
$rows = $response->fetch_all(MYSQLI_ASSOC);

echo "\t ". count($rows) . " references selected.\n";

$count = 0;

foreach($rows as $row){
    $id = $row['id'];
    $old = $row['display_text'];
    // weird error 
    $new = str_replace('&Apos;', '&apos;', $old);
    $new = html_entity_decode($new, ENT_QUOTES | ENT_XML1, 'UTF-8');
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