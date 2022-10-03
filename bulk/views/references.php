
<div style="width: 1000px">
<h2>References</h2>

<?php
    $table = @$_SESSION['selected_table'];
    if(!$table){
        echo '<p style="color: red;">You need to select a table before you can do anything here.</p>';
        exit();
    }

    if(@$_GET['active_run']){
        run_references($table);   
    }else{
        render_form($table);
    }

function run_references($table){
    echo "doing shit";
}

function render_form($table){
    global $mysqli;
?>

<p>This tool enables the import of references from a table that has at least three columns: WFO ID, Label and URI. If the reference already exists (based on the URI) it will not be created or changed.<p>

<form>
    <input type="hidden" name="action" value="view" />
    <input type="hidden" name="phase" value="references" />
    <input type="hidden" name="active_run" value="true" />
    <input type="hidden" name="page" value="0" />
    <input type="hidden" name="page_size" value="1000" />

    <table style="width: 800px">
        <tr>
            <th>WFO ID Column</th>
            <td>
                <select name="wfo_column">
<?php
                    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
                    $cols = $response->fetch_all(MYSQLI_ASSOC);
                    foreach($cols as $col){
                        echo "<option value=\"{$col['Field']}\">{$col['Field']}</option>";
                    }
?>
                </select>
            </td>
            <td>The column in the table that contains the WFO ID of the name the references apply to.</td>
        </tr>
        <tr>
            <th>Label Column</th>
            <td>
                <select name="label_column">
<?php
                    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
                    $cols = $response->fetch_all(MYSQLI_ASSOC);
                    foreach($cols as $col){
                        echo "<option value=\"{$col['Field']}\">{$col['Field']}</option>";
                    }
?>
                </select>
            </td>
            <td>The column in the table that contains the string text for the reference. This is the reference citation and is the same everywhere the referenced is used.</td>
        </tr>
        <tr>
            <th>Comment Column</th>
            <td>
                <select name="comment_column">
<?php
                    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
                    $cols = $response->fetch_all(MYSQLI_ASSOC);
                    foreach($cols as $col){
                        echo "<option value=\"{$col['Field']}\">{$col['Field']}</option>";
                    }
?>
                </select>
            </td>
            <td>The column in the table that contains the string text for the reference for this name. This is a comment on how the reference relates to this name in particular. It can be different for every name-reference relationship.</td>
        </tr>
        <tr>
            <th>URI Column</th>
            <td>
                <select name="uri_column">
<?php
                    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
                    $cols = $response->fetch_all(MYSQLI_ASSOC);
                    foreach($cols as $col){
                        echo "<option value=\"{$col['Field']}\">{$col['Field']}</option>";
                    }
?>
                </select>
            </td>
            <td>The column in the table that contains the URI of the reference. This could be a DOI with the https prefix. Import of the reference will fail if this isn't a valid URI syntax.</td>
        </tr>
        <tr>
            <th>Reference Kind</th>
            <td>
                <select name="kind_column">
                    <option value="literature">Literature</option>
                    <option value="database">Database</option>
                    <option value="specimen">Specimen</option>
                    <option value="person">Person</option>
                </select>
            </td>
            <td>What does the reference point to?</td>
        </tr>
        <tr>
            <th>Taxonomic Reference</th>
            <td>
                <input type="checkbox" name="taxonomic" />
            </td>
            <td>The default is to presume reference is about nomenclature (gray box). Check this box if the reference is about placement of the name in the taxonomy (yellow box).</td>
        </tr>
        <tr>
            <th>URI Filter</th>
            <td>
                <input type="text" name="uri_filter" />
            </td>
            <td>If specified here, only rows where the URI starts with this string (case insensitive) will be imported. This may be useful when there are references of multiple kinds in the table and they need to be imported in batches. e.g. Using https://doi.org/ to separate literature references from database references.</td>
        </tr>
        <tr>
                <td colspan="3" style="text-align: right;"><input type="submit" value="Start Import Now"/></td>
        </tr>
    </table>

</form>

<?php
}
?>


</div>