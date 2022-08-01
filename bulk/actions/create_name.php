<?php

// glue the name back together
// we take it apart again the the create function!
$name_parts =  array();
$name_parts[] = @$_GET['genus_string'];
$name_parts[] = @$_GET['species_string'];
$name_parts[] = @$_GET['name_string'];
$name_string = implode(' ', $name_parts);
$name_string = trim($name_string);

// do we want to force the creation of a homonym?
if(@$_GET['force_homonym']){
    $homonym_wfos = explode(',', $_GET['force_homonym']);
    $update = Name::createName($name_string, true, true, $homonym_wfos);
}else{
    $update = Name::createName($name_string, true, false);
}

// if the  update has failed stop and display results
if(!$update->success || !isset($update->names[0])){
    echo "<pre>";
    print_r($_GET);
    print_r($update);
    echo "</pre>";
    exit;
}
// ok the creation went OK. Let's get the name
$name = $update->names[0];
$name->setRank($_GET['rank_string']);
$name->setAuthorsString($_GET['authors_string']);
$name->save();

$wfo = $name->getPrescribedWfoId();

$table = $_SESSION['selected_table'];
$rhakhis_pk = $_GET['rhakhis_pk'];

$mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_wfo` = '$wfo' WHERE `rhakhis_pk` = $rhakhis_pk;");
if($mysqli->error){
    echo $mysqli->error;
    exit;
}else{

    echo "<h2>Name Created</h2>";

    // keep tabs on recently created names - will fill session.
    if(!isset($_SESSION['created_names'])) $_SESSION['created_names'] = serialize(array());
    $created_names = unserialize($_SESSION['created_names']);
    $created_names[$wfo] = $name->getFullNameString();
    $_SESSION['created_names'] = serialize($created_names);

    $uri =  get_rhakhis_uri($name->getPrescribedWfoId());

    echo "<p>";
    echo "<strong>{$name->getPrescribedWfoId()}</strong> ";
    echo $name->getFullNameString();

    if($uri){
        echo " [<a href=\"$uri\" target=\"rhakhis\">View in Rhakhis</a>]";
    }
    
    echo "</p>";


    // link to continue the matching process
    $params = $_GET;
    $params['action'] = $params['calling_action'];
    unset($params['rhakhis_pk']);
    unset($params['rhakhis_column']);
    unset($params['rhakhis_value']);
    unset($params['calling_action']);
    $query_string = http_build_query($params);
    echo "<p><a href=\"index.php?$query_string\">Continue Matching ...</a></p>";

}
