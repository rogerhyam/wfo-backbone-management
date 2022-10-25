<?php

// build the paths for this table
$table = @$_SESSION['selected_table'];

// get a list of the root taxa
$response = $mysqli->query("SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_parent` IS NULL AND `rhakhis_accepted` IS NULL AND `rhakhis_wfo` IS NOT NULL");
$roots = $response->fetch_all(MYSQLI_ASSOC);
$response->close();

// do the rhakhis paths
foreach($roots as $root){

    // add the root itself
    $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_r_path` = '{$root['rhakhis_wfo']}' WHERE `rhakhis_wfo` = '{$root['rhakhis_wfo']}'");

    // add the children nodes
    $name = Name::getName($root['rhakhis_wfo']);
    $paths = Taxon::getDescendantPaths($name, false); // relative paths
    foreach($paths as $wfo => $path){
        $full_path = $root['rhakhis_wfo'] . "/" . $path; // add the root to the path to give it context
        $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_r_path` = '$full_path' WHERE `rhakhis_wfo` = '$wfo'");
    }
}

// do the table paths
foreach($roots as $root){

    $paths = array();
    table_add_name($root['rhakhis_wfo'], '', $paths, $table);
    foreach($paths as $wfo => $path){
        $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_t_path` = '$path' WHERE `rhakhis_wfo` = '$wfo'");
    }

}

echo "<pre>";
print_r($paths);
echo "</pre>";

echo "<p>Done</p>";



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
                if($current_path && substr($current_path, -1) != "/") $current_path .= "/";
                table_add_name($row['rhakhis_wfo'], $current_path . $wfo, $paths, $table);
            }else{
                // we don't have a parent so must be a synonym
                $paths[$row['rhakhis_wfo']] = $current_path . "$" . $row['rhakhis_wfo'];
            }
        }

    }else{
        // no children so this is the end of the line
        // can't be a synonym because we aren't called for those
        if($current_path) $current_path .= "/"; // spacer if we are not at root
        $paths[$wfo]     = $current_path . $wfo;
    }

}