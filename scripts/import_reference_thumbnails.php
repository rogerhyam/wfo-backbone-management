<?php

require_once('../config.php');
require_once('../include/Reference.php');
require_once('../include/UpdateResponse.php');
require_once('../include/AuthorTeam.php');
require_once('../include/AuthorTeamMember.php');
require_once('../include/SPARQLQueryDispatcher.php');
/*

    Run off a cron job this script will try and add thumbnails to references 
    of known types

    It runs in two modes. The default is to only add new thumbnails.
    The other is to update thumbnails.

    // set up table modification
    ALTER TABLE `references` 
    ADD COLUMN `thumbnail_last_check` TIMESTAMP NULL DEFAULT NULL AFTER `user_id`;



*/

$mode = "new";
if(count($argv) > 1 && $argv[1] == "update") $mode = "update";

if($mode == "new"){

    // get a list of the references that don't have thumbnails
    // and that we have never tried to add thumbnails to
    // in the order they were modified.
    $sql = "SELECT id FROM promethius.references 
    where thumbnail_uri is null
    and thumbnail_last_check is null
    and	(	link_uri like '%gbif.org%' OR link_uri like '%wikidata.org%' )
    order by modified asc
    limit 1000;";

}else{

    // get a list of references that do have thumbnails but 
    // that either have never been updated or 
    // have not been updated for a long time (nulls come first with asc sorting)
    $sql = "SELECT id FROM promethius.references 
    where thumbnail_uri is not null
    and	(	link_uri like '%gbif.org%' OR link_uri like '%wikidata.org%' )
    order by thumbnail_last_check asc
    limit 1000;";

}

$response = $mysqli->query($sql);
$rows = $response->fetch_all(MYSQLI_ASSOC);

foreach($rows as $row){
    $ref = Reference::getReference($row['id']);
    $ref->updateThumbnailUri();
    $ref->save();
}

