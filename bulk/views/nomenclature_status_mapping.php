
<div style="width: 800px">
<h2>Status Mapping</h2>
<p style="color: green;">Only updates rhakhis_* fields in data table.</p>
<p>
    The second stage of reconciling the nomenclature is making sure that the values in the nomenclatural status column of the data table map onto values that Rhakhis understands.
    The correct values are then stored in the <strong>rhakhis_status</strong> column of the data table.
    We need to do this first because later we check that there are correct relationships between names
    (e.g. we can't have an illegitimate name as an accepted taxon in the taxonomy)
    and for that we need a controlled vocabulary of statuses.
    Any actually updating the status in Rhakhis comes at a later stage.
</p>

<table>
    <tr>
        <th>rhakhis_status Value</th>
        <th>Count</th>
        <th>Percent</th>
    </tr>
<?php   

    // get overall row counts
    $sql = "SELECT count(*) AS 'total', count(`rhakhis_status`) AS 'mapped' FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_skip` IS NULL OR `rhakhis_skip` < 1 ";
    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
    $total = number_format($rows[0]['total'], 0);
    $total_int = $rows[0]['total'];
    $mapped = number_format($rows[0]['mapped'], 0);
    $total_percent = number_format(($rows[0]['mapped']/$rows[0]['total'])*100, 2);

    // show distinct values in the rhakhis_status column
    $sql = "SELECT `rhakhis_status` as 'val', count(*) as 'n' FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_skip` IS NULL OR `rhakhis_skip` < 1 GROUP BY `rhakhis_status` ORDER BY `rhakhis_status`";
    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    foreach ($rows as $row) {

        $n = number_format($row['n'], 0);
        $percent = number_format($row['n']/$total_int * 100, 2);;

        echo "<tr>";
        echo "<td>{$row['val']}</td>";
        echo "<td style=\"text-align: right\" >$n</td>";
        echo "<td style=\"text-align: right\" >$percent%</td>";
        echo "</tr>";

    }

    echo "<tr><th style=\"text-align: right\">Total completed:</th><td>$mapped</td><td> $total_percent%</td></tr>";

    echo "<tr><td colspan=\"3\" style=\"text-align: right\"><a href=\"index.php?action=clear_rhakhis_col&table_name=$table&rhakhis_col=status&calling_action=view&calling_phase=nomenclature&calling_task=nomenclature_status_mapping\">Clear rhakhis_status</a></td></tr>";

    
?>
</table>


<p>Use the form below to analyse the values in the data and then update the rhakhis_status column in the data table. Unrecognized values are highlighted in pink and you can select a mapping to use.</p>
<p>This may take multiple passes. You may want to map some values from the taxonomic status column as well. Data suppliers can be very inconsistent.</p>

</div>

<form method="GET" action="index.php">
    <input type="hidden" name="phase" value="nomenclature" />
    <input type="hidden" name="task" value="nomenclature_status_mapping" />
    <select name="status_column">
        <option value="">~ select status column ~</option>
<?php
    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
    $cols = $response->fetch_all(MYSQLI_ASSOC);
    foreach($cols as $col){
        $selected = @$_GET['status_column'] == $col['Field'] ? 'selected' : '';
        echo "<option $selected value=\"{$col['Field']}\">{$col['Field']}</option>";
    }
?>
   </select>
   <input type="submit" />
</form>

<?php 

$status_col = @$_GET['status_column'];

// get out of here if we don't have a column set yet
if(!$status_col) exit;

// we have a status column so get the unique values for it.
$sql = "SELECT `$status_col` as 'val', count(*) as 'n' FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_skip` IS NULL OR `rhakhis_skip` < 1 GROUP BY `$status_col` ORDER BY `$status_col`";
$response = $mysqli->query($sql);

if($response->num_rows > 100){
    echo "<p>There are over 100 different values in this column. Have you chosen the right one? I certainly can't handle that!</p>";
    exit;
}
$rows = $response->fetch_all(MYSQLI_ASSOC);
$response->close();

// total up the values
$total = 0;
foreach($rows as $row){
    $total = $total + (int)$row['n'];
}

// we need a list of the values in the status controlled vocabulary.
$result = $mysqli->query("SHOW COLUMNS FROM `names` LIKE 'status'");
$row = $result->fetch_assoc();
$type = $row['Type'];
preg_match("/'(.*)'/i", $type, $matches);
$statuses = explode(',', $matches[1]);
array_walk($statuses, function(&$v){$v = str_replace("'", "", $v);});
$result->close();


echo '<form  method="GET" action="index.php">';
echo '<input type="hidden" name="action" value="update_status" />';
echo '<input type="hidden" name="status_column" value="'.$status_col.'" />';
echo '<input type="hidden" name="table" value="'.$table.'" />';

echo "<table>";
echo "<tr><th>Data Value</th><th>Number</th><th>Percent</th><th>Maps to..</th><th>View</th></tr>";
foreach($rows as $row){

    // is the value a status?
    $good_status = strtolower(trim($row['val']));
    if(!in_array($good_status, $statuses)){
        $good_status = false;
    }

    if($good_status) echo '<tr>';
    else echo '<tr style="background-color: pink">';

    echo "<td>{$row['val']}</td>";
    echo '<td style="text-align: right">' . number_format($row['n'], 0) . "</td>";
    echo '<td style="text-align: right">' . number_format(($row['n']/$total)*100 , 2) . "%</td>";

    if($good_status){
        echo '<td style="text-align: center">' . $good_status . "</td>";
        echo "<td>&nbsp;</td>";
    }else{

        echo "<td>";
        echo '<input type="hidden" name="unknown_status_values[]" value="'. $row['val']  .'" />';
        echo '<select name="mapped_status_values[]"/>';
        echo "<option value=\"\">~ ignore ~</option>";
        foreach($statuses as $status){
            echo "<option value=\"$status\">$status</option>";
        }

        echo '</select>';
        echo "</td>";

        echo "<td>";
        echo '<a href="index.php?action=view&phase=tables&task=tables_peek&search_field='. $status_col .'&search_value='.  urlencode($row['val']) .'" target="rhakhis_peek">Peek</>';
        echo "</td>";

    }

    echo "</tr>";
}
echo "<tr>";
echo '<td style="text-align: right" colspan="5" >';

echo 'Update rhakhis_status column with mapped values: <input type="submit" S"/>';

echo "</td>";
echo "</tr>";

echo "<table>";
echo "</form>";

?>