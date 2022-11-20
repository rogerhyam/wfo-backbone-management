<?php
    $table = @$_SESSION['selected_table'];
    if(!$table) echo '<p style="color: red;">You need to select a table before you can do anything here.</p>';

?>
<div>
<strong>Nomenclature: </strong>
<a href="index.php?action=view&phase=nomenclature&task=nomenclature_ranks">Ranks</a>
|
<a href="index.php?action=view&phase=nomenclature&task=nomenclature_status_mapping">Status Mapping</a>
|
<a href="index.php?action=view&phase=nomenclature&task=nomenclature_status_import">Status Import</a>
|
<a href="index.php?action=view&phase=nomenclature&task=nomenclature_authors">Authors</a>
|
<a href="index.php?action=view&phase=nomenclature&task=nomenclature_published_in">Published In</a>
|
<a href="index.php?action=view&phase=nomenclature&task=nomenclature_spelling">Fix Spelling</a>
</div>
<hr/>
<?php
    $task =  @$_GET['task'];
    if(!$task) $task = 'nomenclature_ranks';
    require_once('../bulk/views/'. $task . ".php");

/**
 * 
 * Process page worth of rows 
 * used in both nomenclature_authors and nomenclature_published_in
 * 
 */
function process_page($table){

    global $mysqli;

    $page = (int)$_GET['page'];
    $page_size = (int)$_GET['page_size'];
    $offset = $page_size * $page;

    /*
    $sql = "SELECT count(*) as total_rows, count(`rhakhis_skip`) as total_skips FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_wfo` is not null;";
    $response = $mysqli->query($sql);
    $row = $response->fetch_assoc();
    $response->close();

    echo '<style>th{text-align: right;}</style>';
    echo "<table>";
    echo "<tr><th>Total mapped rows:</th><td>". number_format($row['total_rows'], 0) . "</td></tr>";
    echo "<tr><th>Total skips:</th><td>". number_format($row['total_skips'], 0) . "</td></tr>";
    echo "</table>";
    
    */
    
    $sql = "SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_wfo` is not null LIMIT $page_size OFFSET $offset";

    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    // if we have more than 0 rows we may need to render the next page
    if(count($rows) > 0){

        $stop_here = false;
        foreach($rows as $row){
           $stop_here = process_row($row,$table);
           if($stop_here) break;
        }

        $params = $_GET;
        $params['page'] = ($page + 1);
        $uri = "index.php?" . http_build_query($params);

        if($stop_here){
            // we are pausing for input.
            $auto_render_next_page = "";
        }else{
            if(@$_GET['dry_run'] == 'yes'){
                $auto_render_next_page = "<p><a href=\"$uri\">Next Page -&gt;</a></p>";
            }else{
                $auto_render_next_page = "<script>window.location = \"$uri\"</script>";
            }
        }
    
    }else{
        $auto_render_next_page = "";
        echo "<hr/><h3>Reached end of table</h3><hr/>";
        render_form($table);
    }

    echo $auto_render_next_page;

}


?>
