<h2>Update Status: Processing ... </h2>
<?php

// This is for populating the rhakhis_* taxonomy columns

// set up our variables
$table = $_GET['table'];


$taxon_id_column = @$_GET['taxon_id_column'];
$parent_id_column = @$_GET['parent_id_column'];
$accepted_id_column = @$_GET['accepted_id_column'];
$basionym_id_column = @$_GET['basionym_id_column'];

// paging
$page = 0;
if(@$_GET['page']) $page = (int)$_GET['page'];
$page_size = 1000;
$offset = $page * $page_size;
$end = $offset + $page_size;

echo "<p>From $offset to $end</p>";

// load the rows

$sql = "SELECT * FROM `rhakhis_bulk`.`$table` LIMIT $page_size OFFSET $offset";
$response = $mysqli->query($sql);
$rows = $response->fetch_all(MYSQLI_ASSOC);
$response->close();

// if we have more than 0 rows we may need to render the next page
$form_uri = "index.php?action=view&phase=taxonomy&task=taxonomy_internal&taxon_id_column=$taxon_id_column&parent_id_column=$parent_id_column&accepted_id_column=$accepted_id_column&basionym_id_column=$basionym_id_column";
$back_link = "<p>&lt; <a href=\"$form_uri\">Back to form</a></p>";
if(count($rows) > 0){
    $params = $_GET;
    $params['page'] = ($page + 1);
    $uri = "index.php?" . http_build_query($params);
    $auto_render_next_page = "<script>window.location = \"$uri\"</script>";
}else{
    // send them back to the form.
    $form_uri = "index.php?action=view&phase=taxonomy&task=taxonomy_internal&taxon_id_column=$taxon_id_column&parent_id_column=$parent_id_column&accepted_id_column=$accepted_id_column&basionym_id_column=$basionym_id_column";
    $auto_render_next_page = "<script>window.location = \"$form_uri\"</script>";
}

// process them rows
foreach ($rows as $row) {
    
    $parent_wfo = null;
    $accepted_wfo = null;
    $basionym_wfo = null;
    $rhakhis_pk = $row['rhakhis_pk'];

    // parent wfo 
    if($parent_id_column != "IGNORE" && $row[$parent_id_column]){
        $parent_taxon_id_escaped = $mysqli->real_escape_string($row[$parent_id_column]);
        $sql = "SELECT rhakhis_wfo FROM `rhakhis_bulk`.`$table` WHERE `$taxon_id_column` = '$parent_taxon_id_escaped'";
        $response = $mysqli->query($sql);
        $parents = $response->fetch_all(MYSQLI_ASSOC);
        $response->close();
        if(count($parents) > 1){
            echo "<p>FAILURE: There are multiple rows with taxon id {$row[$parent_id_column]} so can't map parentage.</p>";
            $auto_render_next_page = null;
        }
        if(count($parents) < 1){
            echo "<p>WARNING: Can't find parent row with id '{$row[$parent_id_column]}' so can't map parentage for {$row['rhakhis_wfo']}. Maybe it is a synonym or root.</p>";
            $auto_render_next_page = null;
        }
        if(count($parents) == 1){
            // just right!
            if($parents[0]['rhakhis_wfo']){
                $parent_wfo = $parents[0]['rhakhis_wfo'];
            }else{
                echo "<p>FAILURE: Found parent row with id {$row[$parent_id_column]} but it doesn't have a WFO mapped in rhakhis_wfo yet.</p>";
                $auto_render_next_page = null;
            }
        }
    }

    // accepted wfo 
    if($accepted_id_column != "IGNORE" && $row[$accepted_id_column]){
        $accepted_taxon_id_escaped = $mysqli->real_escape_string($row[$accepted_id_column]);
        $response = $mysqli->query("SELECT rhakhis_wfo FROM `rhakhis_bulk`.`$table` WHERE `$taxon_id_column` = '$accepted_taxon_id_escaped'");
        $accepted_ones = $response->fetch_all(MYSQLI_ASSOC);
        $response->close();
        if(count($accepted_ones) > 1){
            echo "<p>FAILURE: There are multiple rows with taxon id {$row[$accepted_id_column]} so can't map accepted name to a single one for {$row['rhakhis_wfo']}..</p>";
            $auto_render_next_page = null;
        }
        if(count($accepted_ones) < 1){
            echo "<p>FAILURE: Can't find accepted row with id {$row[$accepted_id_column]} so can't map synonym for {$row['rhakhis_wfo']}..</p>";
            $auto_render_next_page = null;
        }
        if(count($accepted_ones) == 1){
            // just right!
            if($accepted_ones[0]['rhakhis_wfo']){
                $accepted_wfo = $accepted_ones[0]['rhakhis_wfo'];
                // we can't have a parent wfo as well
                $parent_wfo = null;
            }else{
                echo "<p>FAILURE: Found accepted name row with id {$row[$accepted_id_column]} but it doesn't have a WFO mapped in rhakhis_wfo yet so can't map {$row['rhakhis_wfo']}..</p>";
                $auto_render_next_page = null;
            }
        }
    }

    // basionym wfo 
    if($basionym_id_column != "IGNORE" && $row[$basionym_id_column]){

        // get the rows that the basionym points at
        $basionym_taxon_id_escaped = $mysqli->real_escape_string($row[$basionym_id_column]);
        $response = $mysqli->query("SELECT rhakhis_wfo FROM `rhakhis_bulk`.`$table` WHERE `$taxon_id_column` = '$basionym_taxon_id_escaped'");
        $basionym_ones = $response->fetch_all(MYSQLI_ASSOC);
        $response->close();

        
        if(count($basionym_ones) > 1){
            echo "<p>FAILURE: There are multiple rows with taxon id {$row[$basionym_id_column]} so can't map basionym to a single one.</p>";
            $auto_render_next_page = null;
        }
        if(count($basionym_ones) < 1){
            echo "<p>FAILURE: Can't find accepted row with id {$row[$basionym_id_column]} so can't map basionym.</p>";
            $auto_render_next_page = null;
        }
        if(count($basionym_ones) == 1){
            // just right!
            if($basionym_ones[0]['rhakhis_wfo']){
                $basionym_wfo = $basionym_ones[0]['rhakhis_wfo'];
            }else{
                echo "<p>FAILURE: Found basionym name row with id {$row[$basionym_id_column]} but it doesn't have a WFO mapped in rhakhis_wfo yet.</p>";
                $auto_render_next_page = null;
            }
        }
    }

    // got the values set so update the data table
    $stmt = $mysqli->prepare("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_parent`  = ?, `rhakhis_accepted` = ?, `rhakhis_basionym` = ? WHERE `rhakhis_pk` = ?");
    echo $mysqli->error;
    $stmt->bind_param(
        "sssi",
        $parent_wfo,
        $accepted_wfo,
        $basionym_wfo,
        $rhakhis_pk
    );
    $stmt->execute();

}

echo $auto_render_next_page;
echo $back_link;