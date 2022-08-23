<?php

// this is used to remove duplicate names

$target_wfo = $_GET['target_wfo'];
$remove_wfo = $_GET['remove_wfo'];

// check they aren't the same WFO
if($target_wfo == $remove_wfo){
    echo "<h2>Error: Same WFOs submitted</h2>";
    echo "<p>$remove_wfo for removal</p>";
    echo "<p>$target_wfo for keeping</p>";
    exit;
}

// load the names
$target_name = Name::getName($target_wfo);
$remove_name = Name::getName($remove_wfo);

if(!$target_name->getId()){
    echo "<h2>Error: Couldn't load target name for $target_wfo</h2>";
    exit;
}

if(!$remove_name->getId()){
    echo "<h2>Error: Couldn't load removal name for $remove_wfo</h2>";
    exit;
}

if($target_name == $remove_name){
    echo "<h2>Error: WFO's resolve to the same name.</h2>";
    echo "<p>$remove_wfo for removal</p>";
    echo "<p>$target_wfo for keeping</p>";
    exit;
}

// everything funky let's do the removal.
if($remove_name->deduplicate_into($target_name)){
    header('Location: index.php?action=view&phase=deduplication&offset=' . @$_GET['offset']);
}else{
    echo "<p>Something went wrong. It will have been reported.</p>";
}
