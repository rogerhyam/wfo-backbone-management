<h3>Linking</h3>
<p style="color: red;">Changes data in Rhakhis.</p>
<?php
    $table = @$_SESSION['selected_table'];


    if(!$table){
        echo '<p style="color: red;">You need to select a table before you can do anything here.</p>';
        exit();
    }

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

    $sql = "SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_wfo` IS NOT NULL ORDER BY `rhakhis_pk` LIMIT $page_size OFFSET $offset";

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

        // we are working with a name
        $name = Name::getName($row['rhakhis_wfo']);
        if(!$name) continue;

        $new_id = trim($row[$id_column]);
        if(!$new_id) continue;

        // does it exist in the ids table?
        $id_value =  $id_prefix . $new_id;
        
        // special case for ipni IDs - they must be full LSID
        if($id_kind == 'ipni' && !preg_match('/^urn:lsid:ipni.org:names:/', $id_value) ){
            $id_value = 'urn:lsid:ipni.org:names:'. $id_value;
        }

        // load all the ids of the same kind and value
        $response = $mysqli->query("SELECT * from `identifiers` WHERE `kind` = '$id_kind' AND `value` = '$id_value'");
        $id_rows = $response->fetch_all(MYSQLI_ASSOC);
        $response->close();

        // does this id/kind already exist
        if(count($id_rows) > 0){
            
            //  it does so is the name the same WFO ID as the row in the table?
            $name = Name::getName($id_rows[0]['name_id']);

            if($name->getPrescribedWfoId() == $row['rhakhis_wfo']){
                // name has same ID our work here is done.
                echo "<p><strong>$id_value: </strong> already bound to " . $name->getPrescribedWfoId() . " - " . $name->getFullNameString() . "</p>"; 
            }else{
                // bad shit - bound to a different wfo id
                echo "<p><strong style=\"color: red\">$id_value: </strong> already bound to " . $name->getPrescribedWfoId() . " - " . $name->getFullNameString() . " but row had WFO ID " . $row['rhakhis_wfo'] ." </p>";

                if(@$_GET['move_existing']){

                    echo "<p>...moving to {$row['rhakhis_wfo']} because 'Move existing IDs' was checked.</p>";

                    // remove these rows from the ids table so they are free to use
                    foreach($id_rows as $id_row){
                        $mysqli->query("DELETE from `identifiers` WHERE `id` = {$id_row['id']}");
                    } 

                    // add it to the name
                    $name->addIdentifier($id_value, $id_kind); // no need to save as this writes straight to the identifiers table.
                    echo "<p><strong>$id_value: </strong> now bound to " . $name->getPrescribedWfoId() . " - " . $name->getFullNameString() . "</p>";

                }else{
                    echo "<p>Stopping here as we can't proceed if two names have the same ID. If you would like to move IDs instead then check the 'Move existing IDs' box and run again.</p>";
                    exit;
                }

            }

        }else{

            // it doesn't exist in the IDs table so we can add it to the table and the name
            $name->addIdentifier($id_value, $id_kind); // no need to save as this writes straight to the identifiers table.
            echo "<p><strong>$id_value: </strong> newly bound to " . $name->getPrescribedWfoId() . " - " . $name->getFullNameString() . "</p>";

        }
    
        // Special case from IPNI IDs again
        // If we are adding an IPNI ID and the name doesn't already have a preferred IPNI ID then we add it in
        if($id_kind == 'ipni' && $name->getPreferredIpniId() == null){
            $name->setPreferredIpniId($id_value);
            $name->save();
        }

    }

    // load the next page or stop.
    echo $auto_render_next_page;

}

function render_form($table){
    global $mysqli;
?>

<p>This utility will run through the table and, for those rows that have been matched to a WFO ID, will add a local ID
    to Rhakhis thus making matching easier to do next time. There are a restricted kinds of identifiers you can have.
    Basically either 'TEN' or one of the nomenclators. If the identifier kind is IPNI and the name doesn't already have
    a preferred IPNI then this ID will be set as the
    preferred IPNI ID for the name. Subsequent IPNI IDs added will just be added as extra IDs.</p>
<form>
    <input type="hidden" name="action" value="view" />
    <input type="hidden" name="phase" value="linking" />
    <input type="hidden" name="active_run" value="true" />
    <input type="hidden" name="page" value="0" />
    <input type="hidden" name="page_size" value="1000" />

    <table style="width: 800px">
        <tr>
            <th>Table&nbsp;Column</th>
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
            <td>The column in the table that contains the 'local' (local to the data source that is) identifier for the
                name.</td>
        </tr>
        <tr>
            <th>Identifier&nbsp;Kind</th>
            <td>
                <select name="identifer_kind">
                    <option value="ten">TEN</option>
                    <option value="ipni">IPNI</option>
                    <option value="tropicos">Tropicos</option>
                    <option value="gbif">GBIF</option>
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
            <td>If the local identifier is making no attempt to be globally unique (e.g. it is just a integer) then you
                should add a prefix to restrict it to the TEN.
                Recommendation is to use something like "ten:example.org:family_name:local_identier";
                This assumes the supplier has a domain name associated with them.
                <strong>Obviously one has to keep a note of the prefix for future matching!</strong>
            </td>
        </tr>
        <tr>
            <th>Move&nbsp;existing&nbsp;IDs</th>
            <td style="text-align: center;">
                <input type="checkbox" name="move_existing" value="true" />
            </td>
            <td>Uniqueness of the identifier:kind combination is enforced.
                We can't have the same identifier associated with multiple names.
                If this option is checked then if an id is bound to a different name it will be moved to the name in the
                input table.
                If this option is not checked then the script will stop if it come across an ID in use for another name.
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: right;"><input type="submit" value="Start Linking Run" /></td>
        </tr>
    </table>

</form>
<?php
} // end render_form();