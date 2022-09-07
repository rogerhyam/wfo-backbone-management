<h2>Internal Mapping</h2>
<p style="color: green;">Only updates rhakhis_* fields in data table.</p>

<p>Build the taxonomy of WFO IDs in the data table. It only works on rows that have been mapped to their WFO ID i.e. have a value in the rhakhis_wfo column.</p>

<p>At this stage we are not comparing anything to data in Rhakhis. </p>

<?php

    // report on the last import if there was one
    if(@$_SESSION['taxonomy_internal_mapping']){

        echo "<h3>Issues from last run</h3>";
        echo "<ol>";

        foreach ($_SESSION['taxonomy_internal_mapping'] as $error) {
            echo "<li>$error</li>";
        }       
        
         echo "</ol>";

    }

    // get the table colums
    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
    $cols = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
?>

<form>
    <input type="hidden" name="action" value="taxonomy_internal_mapping" />
    <input type="hidden" name="table" value="<?php echo $table ?>" />
    <style>
        th{ text-align: right;}
        table{
            width: 1000px;
        }
    </style>
    <table>
    <tr><td colspan="3" style="background-color: gray; color: white;"><strong>Mapping</strong></td></tr>

    <!-- Taxon ID -->

    <tr>
        <th>Taxon ID Column</th>
        <td>

    <select name="taxon_id_column">
<?php
    foreach($cols as $col){
        $selected = @$_GET['taxon_id_column'] == $col['Field'] ? 'selected' : '';
        echo "<option $selected value=\"{$col['Field']}\">{$col['Field']}</option>";
    }
?>
   </select>
        </td>
        <td>This is the column that identifiers the Name/Taxon concerned. The following columns you specify will reference the values in this column. Other rows are ignored.</td>
    <tr>

    <!-- Parent ID -->

    <tr>
        <th>Parent ID Column</th>
        <td>

    <select name="parent_id_column">
        <option value="IGNORE">~ Don't Map ~</option>;
<?php
    foreach($cols as $col){
        $selected = @$_GET['parent_id_column'] == $col['Field'] ? 'selected' : '';
        echo "<option $selected value=\"{$col['Field']}\">{$col['Field']}</option>";
    }
?>
   </select>
        </td>
        <td>This is the column that contains the identifier for the parent taxon. Note it is overridden by the accepted id column. Both can't have values. </td>
    <tr>

    <!-- Accepted Name ID -->

    <tr>
        <th>Accepted ID Column</th>
        <td>

    <select name="accepted_id_column">
        <option value="IGNORE">~ Don't Map ~</option>;
<?php
    foreach($cols as $col){
        $selected = @$_GET['accepted_id_column'] == $col['Field'] ? 'selected' : '';
        echo "<option $selected value=\"{$col['Field']}\">{$col['Field']}</option>";
    }
?>
   </select>
        </td>
        <td>If the row is a synonym then this column contains the id of the accepted taxon. If this has a value then any value in rhakhis_parent will be removed. Some datasets may not enforce the mutually exclusive nature of the fields but we do from here on in.</td>
    <tr>

    <!-- Basionym Name ID -->

    <tr>
        <th>Basionym ID Column</th>
        <td>

    <select name="basionym_id_column">
        <option value="IGNORE">~ Don't Map ~</option>;
<?php
    foreach($cols as $col){
        $selected = @$_GET['basionym_id_column'] == $col['Field'] ? 'selected' : '';
        echo "<option $selected value=\"{$col['Field']}\">{$col['Field']}</option>";
    }
?>
   </select>
        </td>
        <td>If this row is a comb nov of some kind then this column contains the basionym id. (Yes I know this is nomenclature not synonym but data-wise it fits here to make the links)</td>
    <tr>

    <!-- submit for a run -->
    <tr>
        <td style="text-align: right" colspan="5" >Start run: <input type="submit" /></td>
    </tr>

</table>




</form>



