<?php

$root_wfo = $_GET['root_taxon_wfo'];
$table = $_GET['table'];

echo "<p>$root_wfo   $table</p>";


$paths = array();

rhakhis_add_name($root_wfo, '', $paths);
table_add_name($root_wfo, '', $paths, $table);

echo "<pre>";
print_r($paths);
echo "</pre>";

/**
 * 
 * Adds all the paths below the root
 * from the table
 * 
 */

 function table_add_name($wfo, $current_path, &$paths, $table){

    global $mysqli;
    
    // select the children of the root
    $sql = "SELECT * FROM `rhakhis_bulk`.`$table` where rhakhis_parent = '$wfo' || rhakhis_accepted = '$wfo'";
    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
    if($rows){

        foreach($rows as $row){
            if($row['rhakhis_parent']){
                // we have a parent so are a taxon child
                table_add_name($row['rhakhis_wfo'], $current_path . ">" . $wfo, $paths, $table);
            }else{
                // we don't have a parent so must be a synonym
                $paths[$row['rhakhis_wfo']]['table'] = $current_path . ":" . $row['rhakhis_wfo'];
            }
        }

    }else{
        // no children so this is the end of the line
        // can't be a synonym because we aren't called for those
        if($current_path) $current_path .= ">"; // spacer if we are not at root
        $paths[$wfo]['table'] = $current_path . $wfo;
    }

}

/**
 * 
 * Adds all the paths below the root
 * from rhakhis
 * 
 */
function rhakhis_add_name($wfo, $current_path, &$paths){

    global $mysqli;

    $sql = "SELECT i.`value` as 'wfo', tn.name_id, atn.name_id as accepted_name_id,  atn.name_id = tn.name_id as 'accepted'  FROM 
            taxon_names as tn join identifiers as i on i.name_id = tn.name_id
        join taxa as t on tn.taxon_id = t.id
        join taxon_names as atn on t.taxon_name_id = atn.id
        WHERE i.kind = 'wfo'
        AND t.parent_id in 
        (
            SELECT t.id FROM 
            taxon_names as tn join identifiers as i on i.name_id = tn.name_id
            join taxa as t on tn.taxon_id = t.id
            WHERE i.kind = 'wfo'
            AND i.`value` = '$wfo'
        )";
            
    // if I don't have children then I write myself out if 

    //$paths[$wfo] = array();

    $response = $mysqli->query($sql);
    if($response->num_rows > 0){

        while($row = $response->fetch_assoc()){

            if($row['accepted']){
                // we have children so aren't the end of a path
                rhakhis_add_name($row['wfo'], $current_path . ">" . $wfo, $paths);
            }else{
                // it is a synonym so has to be the end of the path
                if(!$current_path) $current_path = $wfo; // we are at the root
                $paths[$row['wfo']]['rhakhis'] = $current_path . ":" . $row['wfo'];
            }
        }

    }else{
        // no children so this is the end of the line
        // can't be a synonym because we aren't called for those
        $paths[$wfo]['rhakhis'] = $current_path . ">" . $wfo;
    }

}