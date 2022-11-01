<?php


// build the paths for this table
$table = @$_SESSION['selected_table'];

// alter the table to add the paths columns if they don't exist.
$response = $mysqli->query("SHOW COLUMNS FROM `rhakhis_bulk`.`$table` LIKE 'rhakhis_t_path'");
if($response->num_rows == 0){

    $mysqli->query("ALTER TABLE `rhakhis_bulk`.`$table` 
        ADD COLUMN `rhakhis_r_path` VARCHAR(1000) NULL AFTER `rhakhis_basionym`,
        ADD COLUMN `rhakhis_t_path` VARCHAR(1000) NULL AFTER `rhakhis_r_path`,
        ADD INDEX `r_path` (`rhakhis_r_path`(100)),
        ADD INDEX `t_path` (`rhakhis_t_path`(100));");

    if($mysqli->error){
        echo $mysqli->error;
        exit;
    }

}

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

echo "<p>Done</p>";


/**
 * 
 * Adds all the paths below the root
 * from the table
 * 
 */

 function table_add_name($wfo, $current_path, &$paths, $table){

    global $mysqli;

    // we append a / to the path if we aren't at the beginning
    if($current_path && substr($current_path, -1) != "/") $current_path .= "/";

    // every name has a path
    $paths[$wfo] = $current_path . $wfo; 

    // if the name has synonyms we add those
    $sql = "SELECT * FROM `rhakhis_bulk`.`$table` where rhakhis_accepted = '$wfo'";
    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
    foreach($rows as $row){
        $syn_wfo = $row['rhakhis_wfo'];
        $paths[$syn_wfo] = $current_path . '$' . $syn_wfo; 
    }

    // if the name has children we create rows for them
    $sql = "SELECT * FROM `rhakhis_bulk`.`$table` where rhakhis_parent = '$wfo'";
    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
    foreach($rows as $row){
        $child_wfo = $row['rhakhis_wfo'];
        table_add_name($child_wfo, $current_path . $wfo, $paths, $table);
    }

}