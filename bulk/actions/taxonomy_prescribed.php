<?php


// this will run through the rhakhis_* fields and check that they are all 
// using the prescribed id for the name and not a deduplicated on.


$offset = @$_GET['offset'];
if(!$offset) $offset = 0;

$table = $_GET['table'];

$page_size = 300;

if($offset == 0) $_SESSION['taxonomy_prescribed'] = array();

$sql = "SELECT rhakhis_pk, rhakhis_wfo, rhakhis_parent, rhakhis_accepted, rhakhis_basionym FROM `rhakhis_bulk`.`$table` ORDER BY rhakhis_pk LIMIT $page_size OFFSET $offset ";
//echo $sql;
$response = $mysqli->query($sql);

if($response->num_rows > 0){

    echo "<h3>Prescribed WFO ID Check</h3>";
    echo "<p>Working ... $offset</p>";

    while($row = $response->fetch_assoc()){
        check_wfo_value($row, 'rhakhis_wfo', $table);
        check_wfo_value($row, 'rhakhis_parent', $table);
        check_wfo_value($row, 'rhakhis_accepted', $table);
        check_wfo_value($row, 'rhakhis_basionym', $table);
    }

    // call for the next page
    $next_offset = $offset + $page_size;
    $uri = "index.php?action=taxonomy_prescribed&table=$table&offset=$next_offset";
    echo "<script>window.location = \"$uri\"</script>";

}else{

    // we have finished
    echo "<p>We have finished. All WFO IDs used should be the prescribed ones, none of the deduplicated ones should be present.</p>";
    echo "<a href=\"index.php?action=view&phase=taxonomy\">Back to taxonomy</a>";

    foreach($_SESSION['taxonomy_prescribed'] as $old => $new){
        if($old != $new) echo "<p>$old updated to $new</p>";
    }

}


function check_wfo_value($row, $field_name, $table){

    global $mysqli;

    $wfo = $row[$field_name];

    // if there is no value set for that field continue
    if(!$wfo) return;

    // Have we done it before? - Saves loading the name again
    if(!isset($_SESSION['taxonomy_prescribed'][$wfo])){
         // load the name for that $wfo
        $name = Name::getName($wfo);
        $_SESSION['taxonomy_prescribed'][$wfo] = $name->getPrescribedWfoId();
    }

    // if the two ids are the same then we don't need to 
    // update anything
    if($_SESSION['taxonomy_prescribed'][$wfo] == $wfo) return;

    // ids are different
    $new_wfo = $_SESSION['taxonomy_prescribed'][$wfo];
    $pk = $row['rhakhis_pk'];
    
    $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `$field_name` = '$new_wfo' WHERE `rhakhis_pk` = $pk");
    
}

