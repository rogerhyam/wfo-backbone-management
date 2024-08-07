<?php

// this is a wrapper to give access to the kew sync database 
// as if it were a data provider for the wfo facet services

require_once('../config.php');


// have they passed a tdwg code?
if(@$_GET['tdwg_geo'] && preg_match('/^[0-9A-Z]+$/', $_GET['tdwg_geo'])){

    $code = 'TDWG:' . $_GET['tdwg_geo'];

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=tdwg_geo_{$_GET['tdwg_geo']}.csv");


    $response = $mysqli->query("SELECT w.wfo_id
            FROM kew.wcvp_geo as g 
            JOIN kew.wcvp as w on g.plant_name_id = w.plant_name_id and w.wfo_id is not null and w.taxon_rank in ('Species', 'Variety', 'Subspecies')
            WHERE g.locationid = '$code';");

    while($row = $response->fetch_assoc()){
        $wfo = $row['wfo_id'];
        if(!preg_match('/^wfo-[0-9]{10}$/', $wfo)) continue;
        echo $row['wfo_id'] . "\n";
    }

    exit;
}

// have they passed a tdwg code?
if(@$_GET['life_form']){

    $life_form = $_GET['life_form'];
    $life_form_safe = $mysqli->real_escape_string($life_form);

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=life_form_{$life_form}.csv");

    $response = $mysqli->query("SELECT wfo_id
            FROM kew.wcvp
            WHERE lifeform_description rlike '^{$life_form_safe}| {$life_form_safe}'
            AND taxon_rank IN ('Species', 'Variety', 'Subspecies')");

    while($row = $response->fetch_assoc()){
        $wfo = $row['wfo_id'];
        if(!preg_match('/^wfo-[0-9]{10}$/', $wfo)) continue;
        echo $row['wfo_id'] . "\n";
    }

    exit;
}




/*
    lifeform
    SELECT wfo_id, lifeform_description FROM kew.wcvp where lifeform_description rlike '^shrub| shrub';


*/

echo "You must pass a query...";