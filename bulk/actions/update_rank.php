<h2>Processing ... </h2>

<?php

// This is for updating the rhakhis_rank column of the current table

// set up our variables
$table = $_GET['table'];
$column = $_GET['rank_column'];

// ambiguous rank values
if(@$_GET['unknown_rank_values']){
    $unknown_ranks_mapped = array_combine($_GET['unknown_rank_values'], $_GET['mapped_rank_values']);
}else{
    $unknown_ranks_mapped = array();
}

// paging
$page = 0;
if(@$_GET['page']) $page = (int)$_GET['page'];
$page_size = 1000;
$offset = $page * $page_size;
$end = $offset + $page_size;

echo "<p>From $offset to $end</p>";

// load the rows

$sql = "SELECT `rhakhis_pk`, `$column` as 'rank' FROM `rhakhis_bulk`.`$table` LIMIT $page_size OFFSET $offset";
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
    $uri = "index.php?phase=nomenclature&task=nomenclature_ranks&rank_column=" . $column;
    $auto_render_next_page = "<script>window.location = \"$uri\"</script>";
}

// actually do the updating here

foreach($rows as $row){

    $good_rank = Name::isRankWord($row['rank']);

    // not got a good rank do we have a map for it.
    if(!$good_rank && isset($unknown_ranks_mapped[$row['rank']])){
        $good_rank = $unknown_ranks_mapped[$row['rank']];
    }

    if($good_rank){
        $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_rank` = '$good_rank' WHERE `rhakhis_pk` = {$row['rhakhis_pk']}");
    }
    
}

echo $auto_render_next_page;