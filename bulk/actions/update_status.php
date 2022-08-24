<h2>Update Status: Processing ... </h2>

<?php


// This is for updating the rhakhis_rank column of the current table

// set up our variables
$table = $_GET['table'];
$column = $_GET['status_column'];

// ambiguous status values
if(@$_GET['unknown_status_values']){
    $unknown_statuses_mapped = array_combine($_GET['unknown_status_values'], $_GET['mapped_status_values']);
}else{
    $unknown_statuses_mapped = array();
}

// paging
$page = 0;
if(@$_GET['page']) $page = (int)$_GET['page'];
$page_size = 1000;
$offset = $page * $page_size;
$end = $offset + $page_size;

echo "<p>From $offset to $end</p>";

// load the rows

$sql = "SELECT `rhakhis_pk`, `$column` as 'status' FROM `rhakhis_bulk`.`$table` LIMIT $page_size OFFSET $offset";
$response = $mysqli->query($sql);
$rows = $response->fetch_all(MYSQLI_ASSOC);
$response->close();

// if we have more than 0 rows we may need to render the next page
if(count($rows) > 0){
    $params = $_GET;
    $params['page'] = ($page + 1);
    $uri = "index.php?" . http_build_query($params);
    $auto_render_next_page = "<script>window.location = \"$uri\"</script>";
}else{
    // send them back to the form.
    $uri = "index.php?phase=nomenclature&task=nomenclature_statuses&status_column=" . $column;
    $auto_render_next_page = "<script>window.location = \"$uri\"</script>";
}

// actually do the updating here

// get a list of OK statuses - repeating this code again!
$result = $mysqli->query("SHOW COLUMNS FROM `names` LIKE 'status'");
$row = $result->fetch_assoc();
$type = $row['Type'];
preg_match("/'(.*)'/i", $type, $matches);
$statuses = explode(',', $matches[1]);
array_walk($statuses, function(&$v){$v = str_replace("'", "", $v);});
$result->close();

foreach($rows as $row){

    // fixme with values from 
    $good_status = strtolower(trim($row['status']));
    if(!in_array($good_status, $statuses)){
        $good_status = false;
    }

    // not got a good status do we have a map for it.
    if(!$good_status && isset($unknown_statuses_mapped[$row['status']])){
        $good_status = $unknown_statuses_mapped[$row['status']];
    }

    if($good_status){
        $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_status` = '$good_status' WHERE `rhakhis_pk` = {$row['rhakhis_pk']}");
    }
    
}

echo $auto_render_next_page;