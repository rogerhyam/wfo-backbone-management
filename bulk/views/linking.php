<h3>Linking</h3>
<?php
    $table = $_SESSION['selected_table'];


if(@$_GET['active_run']){
    run_linking($table);   
}else{
    render_form($table);
}


function run_linking($table){

    global $mysqli;

    $page = (int)$_GET['page'];
    $page_size = (int)$_GET['page_size'];
    $offset = $page_size * $page;

    echo "<p><strong>Offset: </strong>$offset | <strong>Page Size: </strong>$page_size</p>";

    $id_column = $_GET['identifer_column'];
    $id_kind = $_GET['identifer_kind'];
    $id_prefix = trim($_GET['identifer_prefix']);

    $sql = "SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_wfo` IS NOT NULL LIMIT $page_size OFFSET $offset";

    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    if(count($rows) > 0){
        $params = $_GET;
        $params['page'] = ($page + 1);
        $uri = "index.php?" . http_build_query($params);
        $auto_render_next_page = "<script>window.location = \"$uri\"</script>";
    }else{
        $auto_render_next_page = "<p>Reached end of table. <a href=\"index.php?action=view&phase=linking\">Back to form.</a></p>";
    }

    foreach($rows as $row){

        // does it exist in the ids table?
        $id_value =  $id_prefix . trim($row[$id_column]);
        $response = $mysqli->query("SELECT * from `identifiers` WHERE `kind` = '$id_kind' AND `value` = '$id_value'");
        $id_rows = $response->fetch_all(MYSQLI_ASSOC);
        $response->close();

        if(count($id_rows) > 0){
            
            //  it does so is the name the same WFO ID as the row in the table?
            $name = Name::getName($id_rows[0]['name_id']);

            if($name->getPrescribedWfoId() == $row['rhakhis_wfo']){
                // name has same ID our work here is done.
                echo "<p><strong>$id_value: </strong> already bound to " . $name->getPrescribedWfoId() . " - " . $name->getFullNameString() . "</p>"; 
            }else{
                // bad shit - bound to a different wfo id
                echo "<p><strong style=\"color: red\">$id_value: </strong> already bound to " . $name->getPrescribedWfoId() . " - " . $name->getFullNameString() . " but row had WFO ID " . $row['rhakhis_wfo'] ." </p>";
                echo "<p>Stopping right here.</p>";
                exit;
            }

        }else{
            
            // it doesn't exist in the IDs table so we can add it
            $name = Name::getName($row['rhakhis_wfo']);
            $name->addIdentifier($id_value, $id_kind); // no need to save as this writes straight to the identifiers table.
            echo "<p><strong>$id_value: </strong> newly bound to " . $name->getPrescribedWfoId() . " - " . $name->getFullNameString() . "</p>"; 

        }

    }

    // load the next page or stop.
    echo $auto_render_next_page;

}

function render_form($table){
    global $mysqli;
?>


<p>This utility will run through the table and, for those rows that have been matched to a WFO ID, will add a local ID to Rhakhis thus making matching easier to do next time.</p>
<p>It will force uniqueness of the identifier:kind combination. We can't have the same identifier associated with multiple names.</p>
<p>There are a restricted list of kinds of identifiers you can have. Basically either TEN or that of a nomenclator.</p>
<form>
    <input type="hidden" name="action" value="view" />
    <input type="hidden" name="phase" value="linking" />
    <input type="hidden" name="active_run" value="true" />
    <input type="hidden" name="page" value="0" />
    <input type="hidden" name="page_size" value="1000" />

    <table style="width: 800px">
        <tr>
            <th>Table Column</th>
            <td>
                <select name="identifer_column">
<?php
                    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
                    $cols = $response->fetch_all(MYSQLI_ASSOC);
                    foreach($cols as $col){
                        echo "<option value=\"{$col['Field']}\">{$col['Field']}</option>";
                    }
?>
                </select>
            </td>
            <td>The column in the table that contains the 'local' (local to the data source that is) identifier for the name.</td>
        </tr>
        <tr>
            <th>Identifier Kind</th>
            <td>
                <select name="identifer_kind">
                    <option value="ten">TEN</option>
                    <option value="ipni">IPNI</option>
                    <option value="tropicos">Tropicos</option>
                    <option value="if">Index Fungorum</option>
                </select>
            </td>
            <td>You probably want to use TEN for this unless this is a dataset from a nomenclator. </td>
        </tr>
        <tr>
            <th>Prefix</th>
            <td>
                <input type="text" name="identifer_prefix" />
            </td>
            <td>If the local identifier is making no attempt to be globally unique (e.g. it is just a integer) then you should add a prefix to restrict it to the TEN. Recommendation is to use the LSID URN schema. 
                    The prefix would be "urn:lsid:institute.org:name:". (I can't believe I just recommended LSID!). This assumes the supplier has a domain name associated with them.
                    <strong>Obviously one has to keep a note of the prefix for future matching!</strong></td>
        </tr>
        <tr>
                <td colspan="3" style="text-align: right;"><input type="submit" value="Start Linking Run"/></td>
        </tr>
    </table>

</form>
<?php
} // end render_form();



