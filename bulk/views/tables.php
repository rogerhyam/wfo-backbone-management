<h2>Tables</h2>
<p>This is where you manage the tables you are working on.</p>
<table>
    <tr>
        <th>Table name</th>
        <th>Rows</th>
        <th>Skips</th>        
        <th>Matches</th> 
        <th>Columns</th> 
        <th>Actions</th> 
    <tr>

<?php

$response = $mysqli->query("SHOW TABLES FROM `rhakhis_bulk`;");
$tables = $response->fetch_all();
$response->close();


foreach($tables as $t){
    $table = $t[0];
    echo "<tr>";
    echo "<td>{$table}</td>";

    // get some stats
    $response = $mysqli->query("SELECT count(*) as num_rows, count(rhakhis_skip) as num_skips, count(rhakhis_wfo) as matches from `rhakhis_bulk`.`$table`;");
    $row = $response->fetch_assoc();
    echo "<td>". number_format($row['num_rows'], 0) . "</td>";
    echo "<td>" . number_format($row['num_skips'], 0) . "</td>";
    echo "<td>" . number_format($row['matches'], 0 );
    echo ' (' . number_format(((int)$row['matches']/(int)$row['num_rows'])*100, 0 ) . '%)';
    echo "</td>";

    // list the cols
    echo "<td><select>";
    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
    $cols = $response->fetch_all(MYSQLI_ASSOC);
    foreach($cols as $col){
        echo "<option>{$col['Field']}</option>";
    }
    echo "</select></td>";

    // actions
    echo "<td>";
    echo "<a href=\"index.php?action=db_select_table&table_name=$table\">Select</a>";
    echo " | <a href=\"index.php?action=db_export_table&export_table_name=$table\">Export</a>";
    echo " | Clear Skips";
    echo " | Delete";
    
    

    echo "</td>";

    echo "</tr>";
}

?>
    

</table>
