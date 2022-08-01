
<div style="width: 800px">
<h2>Ranks</h2>
<p style="color: green;">Only updates rhakhis_* fields in data table.</p>
<p>
    The first stage of reconciling the nomenclature is making sure that the values in the rank column of the data table map onto values that Rhakhis understands.
    The correct values are then stored in the <strong>rhakhis_rank</strong> column of the data table.
    We need to do this first because later we check that there are correct relationships between names
    (e.g. no genera that are children of species)
    and for that we need a controlled vocabulary of rank names.
    Any actually updating the ranks in Rhakhis comes at a later stage.
</p>

<ul>
<?php
    $sql = "SELECT count(*) AS 'total', count(`rhakhis_rank`) AS 'mapped' FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_skip` IS NULL OR `rhakhis_skip` < 1 ";
    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
    $total = number_format($rows[0]['total'], 0);
    $mapped = number_format($rows[0]['mapped'], 0);
    $percent = number_format(($rows[0]['mapped']/$rows[0]['total'])*100, 2);
    echo "<li>Total Un-skipped rows: $total</li>";
    echo "<li>Rows with rhakhis_rank completed: $mapped ($percent%)</li>";
?>
</ul>

<p>Use the form below to analyse the values in the data and then update the rhakhis_rank column in the data table. Unrecognized values are highlighted in pink and you can select a mapping to use.</p>

</div>

<form method="GET" action="index.php">
    <input type="hidden" name="phase" value="nomenclature" />
    <input type="hidden" name="task" value="nomenclature_ranks" />
    <select name="rank_column">
        <option value="">~ select rank column ~</option>
<?php
    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
    $cols = $response->fetch_all(MYSQLI_ASSOC);
    foreach($cols as $col){
        $selected = @$_GET['rank_column'] == $col['Field'] ? 'selected' : '';
        echo "<option $selected value=\"{$col['Field']}\">{$col['Field']}</option>";
    }
?>
   </select>
   <input type="submit" />
</form>

<?php 


$rank_col = @$_GET['rank_column'];

// get out of here if we don't have a column set yet
if(!$rank_col) exit;


// we have a rank column so get the unique values for it.
$sql = "SELECT `$rank_col` as 'val', count(*) as 'n' FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_skip` IS NULL OR `rhakhis_skip` < 1 GROUP BY `$rank_col` ORDER BY `$rank_col`";
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

echo "<form>";
echo '<input type="hidden" name="action" value="update_rank" />';
echo '<input type="hidden" name="rank_column" value="'.$rank_col.'" />';
echo '<input type="hidden" name="table" value="'.$table.'" />';

echo "<table>";
echo "<tr><th>Data Value</th><th>Number</th><th>Percent</th><th>Maps to..</th><th>View</th></tr>";
foreach($rows as $row){

    // is the value a rank?
    $rank = isRankWord( strtolower(trim($row['val'])) );

    if($rank) echo '<tr>';
    else echo '<tr style="background-color: pink">';

    echo "<td>{$row['val']}</td>";
    echo '<td style="text-align: right">' . number_format($row['n'], 0) . "</td>";
    echo '<td style="text-align: right">' . number_format(($row['n']/$total)*100 , 2) . "%</td>";

    if($rank){
        echo '<td style="text-align: center">' . $rank . "</td>";
        echo "<td>&nbsp;</td>";
    }else{

        echo "<td>";

        echo '<input type="hidden" name="unknown_rank_values[]" value="'. $row['val']  .'" />';
        echo '<select name="mapped_rank_values[]"/>';
        echo "<option value=\"\">~ ignore ~</option>";
        foreach($ranks_table as $r => $r_data){
            echo "<option value=\"$r\">$r</option>";
        }

        echo '</select>';
        echo "</td>";

        echo "<td>";
        echo '<a href="index.php?action=view&phase=tables&task=tables_peek&search_field='. $rank_col .'&search_value='.  urlencode($row['val']) .'" target="rhakhis_peek">Peek</>';
        echo "</td>";

    }

    echo "</tr>";
}
echo "<tr>";
echo '<td style="text-align: right" colspan="5" >';

echo 'Update rhakhis_rank column with mapped values: <input type="submit" />';

echo "</td>";
echo "</tr>";

echo "<table>";
echo "</form>";

?>
