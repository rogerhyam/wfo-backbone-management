<?php
set_time_limit(0);

// This takes an wave approach to importing a taxonomy
// third attempt at getting something working!


$offset = @$_GET['offset'];
if(!$offset) $offset = 0;

$table = $_GET['table'];
$root_wfo = $_GET['root_taxon_wfo'];

$page_size = 1000;

$sql_from = "FROM `rhakhis_bulk`.`$table` WHERE rhakhis_t_path like '%$root_wfo%' AND ((rhakhis_r_path != rhakhis_t_path) or (rhakhis_t_path is not null and rhakhis_r_path is null) or (rhakhis_r_path is not null and rhakhis_t_path is null))";

// we are starting out
if($offset == 0){

    // any postponed WFO IDs to print out
    $_SESSION['import_wave_postponed'] = array();
    $_SESSION['import_wave_stats'] = array();
    $_SESSION['import_wave_stats']['differences'] = 0;
    $_SESSION['import_wave_stats']['processed'] = 0;
    $_SESSION['import_wave_stats']['changed'] = 0;

    // we build the paths each time to keep them fresh.
    if(!@$_GET['no_path_build']) build_paths();

    // count how many differences there are
    $sql = "SELECT count(*) as n $sql_from";
    $response = $mysqli->query($sql);
    $_SESSION['import_wave_stats']['differences'] = $response->fetch_all(MYSQLI_ASSOC)[0]['n'];

}

$sql = "SELECT *
    $sql_from
    ORDER BY length(rhakhis_r_path) DESC
    LIMIT $page_size OFFSET $offset ";
//echo $sql;
$response = $mysqli->query($sql);

echo "<h3>Import Taxonomy - Wave Strategy</h3>";

echo "<p><strong>Differences: </strong> {$_SESSION['import_wave_stats']['differences']} </p>";
echo "<p><strong>Processed: </strong> {$_SESSION['import_wave_stats']['processed']} </p>";
echo "<p><strong>Changed: </strong> {$_SESSION['import_wave_stats']['changed']} </p>";


echo "<p>Postponed: ".count($_SESSION['import_wave_postponed'])."</p>";

if($response->num_rows > 0){

    while($row = $response->fetch_assoc()){
        process_row($row, $table);
    }

    echo "<p>Working ... $offset</p>";

    // call for the next page
    $next_offset = $offset + $page_size;
    $uri = "index.php?action=taxonomy_import_wave&table=$table&root_taxon_wfo=$root_wfo&offset=$next_offset";
    echo "<script>window.location = \"$uri\"</script>";

}else{

    // we have finished
    echo "<p>We have finished this wave.</p>";
    
    if($_SESSION['import_wave_stats']['changed'] > 0){
        
        echo "<p>Keep doing waves until there are no more changes made.</p>";
        echo '<p><form method="GET" action="index.php">';
        echo '<input type="hidden" name="action" value="taxonomy_import_wave" />';
        echo "<input type=\"hidden\" name=\"table\" value=\"$table\" />";
        echo "<input type=\"hidden\" name=\"root_taxon_wfo\" value=\"$root_wfo\" />";
        echo "<input type=\"submit\" value=\"Next Wave\" onclick=\"this.disabled = true; this.form.submit(); \" />";
        echo '</form></p>';
    }else{
        echo "<p>It looks like there are no more changes. If you are happy with the postponed names then you should run the Impact Report again to see how the new state of Rhakhis compares to the data table.</p>";
    }

    echo "<p><a href=\"index.php?action=view&phase=taxonomy&task=taxonomy_impact&root_taxon_wfo=$root_wfo\">Go to Impact Report page.</a></p>";

    if(count($_SESSION['import_wave_postponed']) < 2000){
        echo "<h3>Postponed Names</h3>";
        foreach ($_SESSION['import_wave_postponed'] as $wfo => $reason) {
            echo "<p><strong>$wfo</strong> $reason</p>";
        }
    }else{
        echo "<p>When there are fewer than 2,000 postponed names they will be displayed here.</p>";
    }


}

function process_row($row, $table){

    global $ranks_table;

    //error_log($row['rhakhis_wfo']);
    $_SESSION['import_wave_stats']['processed']++;
    
    // OK let's do this!
    $name = Name::getName($row['rhakhis_wfo']);
    $taxon = Taxon::getTaxonForName($name);
    if(!$taxon->getId()) $taxon = null; // no existing taxon for name and we don't want to create one just now


    // update the rank if needed.
    if(
        $row['rhakhis_rank'] // we have a rank set it the table
        &&
        $row['rhakhis_rank'] != $name->getRank() // it isn't already the same as the existing on
        ){
            $name->setRank($row['rhakhis_rank']);
            $name->save();
    }

    // update status if needed
    if(
        $row['rhakhis_status']  // we have a status set in the table
        &&
        $row['rhakhis_status'] != $name->getStatus() // it is different from the current status
        ){
            $name->setStatus($row['rhakhis_status']);
            $name->save();
    }

    // how is it placed in the table
    // note that it either has a parent or an accepted - it can't have a path without this
    if($row['rhakhis_parent']){
        
        // it is an accepted taxon in the table

        if($taxon){
            // it is placed in rhakhis
            if($taxon->getAcceptedName() == $name){
                // it is placed as the accepted name in rhakhis
                
                // but does it have the same parent?
                if($taxon->getParent()->getAcceptedName()->getPrescribedWfoId() != $row['rhakhis_parent']){
                    move_taxon($row, $name, $taxon);
                }else{
                    // there is nothing to do here we have to wait
                    // till the higher taxa are sorted.

                    $_SESSION['import_wave_postponed'][$row['rhakhis_wfo']] = "Nothing to do. Difference is higher in taxonomy.";
                }

            }else{
                // it is placed as a synonym in rhakhis - we can unplace it read for raising
                $taxon->removeSynonym($name);
                raise_to_accepted_taxon($row, $name);
            }
        }else{
            // no taxon so it is not placed in rhakhis but it is accepted in the table so we can just raise it
            raise_to_accepted_taxon($row, $name);
        }

    }else{

        // it is a synonym in the table 

        if($taxon){
            
            // it is placed in rhakhis

            if($taxon->getAcceptedName() == $name){

                // it is an accepted name in rhakhis
                sink_into_synonymy($row, $name, $taxon);

            }else{

                // it is placed as a synonym in rhakhis 
                // does it have the same accepted name
                if($taxon->getAcceptedName()->getPrescribedWfoId() !=  $row['rhakhis_accepted']){
                    move_synonym($row, $name, $taxon);
                }else{
                    // there is nothing to do here the difference must be higher up 
                    // we have to wait till the higher taxa are sorted.
                    $_SESSION['import_wave_postponed'][$row['rhakhis_wfo']] = "Nothing to do. Difference is higher in taxonomy.";
                }
            }

        }else{
            // no taxon in rhakhis, it is unplaced, so we can just move it
            move_synonym($row, $name, $taxon);
        }

    }


}

function move_synonym($row, $name, $taxon = null){

    $new_accepted_name = Name::getName($row['rhakhis_accepted']);
    $new_accepted_taxon = Taxon::getTaxonForName($new_accepted_name);

    if($new_accepted_taxon->getId() && $new_accepted_taxon->getAcceptedName() == $new_accepted_name){
        // ok we are free to go
        if($taxon) $taxon->removeSynonym($name);
        $new_accepted_taxon->addSynonym($name);
        $_SESSION['import_wave_stats']['changed']++;
    }else{
        $_SESSION['import_wave_postponed'][$row['rhakhis_wfo']] = "Can't move synonym as new accepted taxon doesn't exist.";
    }

}

function sink_into_synonymy($row, $name, $taxon){

    // We can't sink something that has a large number of 
    // descendant taxa but if there are 
    // only a few then we can unplace the those
    $descendant_count = $taxon->getDescendantCount();
    if($descendant_count > 10 ){
        $_SESSION['import_wave_postponed'][$row['rhakhis_wfo']] = "Won't sink into synonym as it has more than 10 (actually $descendant_count) descendant taxa still.";
        return;
    }

    // OK to just call prune on it remove any synonyms or few descendants
    $taxon->prune();

    $new_accepted_name = Name::getName($row['rhakhis_accepted']);
    $new_accepted_taxon = Taxon::getTaxonForName($new_accepted_name);

    if($new_accepted_taxon->getId() && $new_accepted_taxon->getAcceptedName() == $new_accepted_name){
        // ok we are free to go
        $taxon->delete();
        $new_accepted_taxon->addSynonym($name);
        $_SESSION['import_wave_stats']['changed']++;
    }else{
        $_SESSION['import_wave_postponed'][$row['rhakhis_wfo']] = "Can't sink into synonymy as accepted taxon doesn't exist.";
    }

}

function move_taxon($row, $name, $taxon){

    global $ranks_table;

    $new_parent_name = Name::getName($row['rhakhis_parent']);
    $new_parent_taxon = Taxon::getTaxonForName($new_parent_name);

    // is the parent name really placed as an accepted name
    if($new_parent_taxon->getId() && $new_parent_taxon->getAcceptedName() == $new_parent_name){

        // is the rank acceptable 
        if(in_array( $name->getRank(), $ranks_table[$new_parent_name->getRank()]['children'])){
            // OK to move 
            $taxon->setParent($new_parent_taxon);
            $taxon->save();
            $_SESSION['import_wave_stats']['changed']++;
        }else{
            $_SESSION['import_wave_postponed'][$row['rhakhis_wfo']] = "Rank isn't suitable for parent taxon.";
        }     

    }

}

function raise_to_accepted_taxon($row, $name){

    // we assume name is free to move now - been unplaced
    
    // RAISING TO TAXON
    $new_parent_name = Name::getName($row['rhakhis_parent']);
    $new_parent_taxon = Taxon::getTaxonForName($new_parent_name);

    // is the parent name really placed as an accepted name
    if($new_parent_taxon->getId() && $new_parent_taxon->getAcceptedName() == $new_parent_name){

        // actually create the new taxon with the correct parent.
        $new_taxon = Taxon::getTaxonForName($name);
        $new_taxon->setParent($new_parent_taxon);
        $user = unserialize( @$_SESSION['user']);
        $new_taxon->setUserId($user->getId()); 
        $new_taxon->save();
        $_SESSION['import_wave_stats']['changed']++;

    }else{
        // parent isn't an accepted name so can't move it
        $_SESSION['import_wave_postponed'][$row['rhakhis_wfo']] = "Can't raise taxon because the parent ({$new_parent_taxon->getAcceptedName()->getPrescribedWfoId()}: {$new_parent_taxon->getAcceptedName()->getFullNameString()}) isn't a good taxon.";
    }

}

function build_paths(){

        global $mysqli;

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

        }else{

            // we clear all the path info 
            // or it can get confusing because we are only 
            // overwriting those that are now placed not those that haven't been placed.
            $mysqli->query("SET SQL_SAFE_UPDATES = 0;");
            $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_r_path` = NULL, `rhakhis_t_path` = NULL WHERE 1=1;");
            if($mysqli->error){
                echo $mysqli->error;
                exit;
            }
            $mysqli->query("SET SQL_SAFE_UPDATES = 1;");
            

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



}// build paths


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
        $paths[$syn_wfo] = $current_path . $wfo  . '$' . $syn_wfo; 
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