
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

    global $mysqli;

    $page = (int)$_GET['page'];
    $page_size = (int)$_GET['page_size'];
    $offset = $page_size * $page;

    // if we are on the first page then we initialize the messages session variable
    if($page == 0) $_SESSION['references_messages'] = array();

    echo "<p><strong>Offset: </strong>$offset | <strong>Page Size: </strong>$page_size</p>";

    $wfo_column = $_GET['wfo_column'];
    $label_column = $_GET['label_column'];
    $comment_column  = $_GET['comment_column'];
    $uri_column  = $_GET['uri_column'];
    $ref_kind = $_GET['ref_kind'];
    $uri_filter  = $_GET['uri_filter'];
    $subject_type = @$_GET['taxonomic'] ? 1:0;


    $sql = "SELECT * FROM `rhakhis_bulk`.`$table` WHERE `$wfo_column` IS NOT NULL";
    if($uri_filter) $sql .= " AND `$uri_column` LIKE 'uri_filter%'";
    $sql .= " LIMIT $page_size OFFSET $offset";

    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    if(count($rows) > 0){
        $params = $_GET;
        $params['page'] = ($page + 1);
        $uri = "index.php?" . http_build_query($params);
        $auto_render_next_page = "<script>window.location = \"$uri\"</script>";
    }else{

        echo "<h3>Messages</h3>\n<p>";
        echo implode("</p>\n<p>", $_SESSION['references_messages']);
        echo "</p>";
        $auto_render_next_page = "<p>Reached end of table. <a href=\"index.php?action=view&phase=linking\">Back to form.</a></p>";

    }

    foreach($rows as $row){

        $wfo = $row[$wfo_column];
        $uri = $row[$uri_column];
        $label = $row[$label_column];
        $comment = $row[$comment_column];

        // is it a good wfo?
        if(!preg_match('\\', $wfo)){
            $_SESSION['references_messages'][] = "'$wfo' doesn't look like a valid WFO ID so skipping it.";
            continue;
        }

        // get the name
        $name = Name::getName($wfo);
        if(!$name->geId()){
            $_SESSION['references_messages'][] = "Couldn't load name for '$wfo' so skipping it.";
            continue;
        }

        // is the uri a cool uri?
        if(!filter_var($uri, FILTER_VALIDATE_URL)){            
            $_SESSION['references_messages'][] = "'$uri' doesn't look like a valid URI so skipping row for $wfo";
            continue;
        }

        // do we have a label?
        if(!$label){
            $_SESSION['references_messages'][] = "'$wfo' - '$uri' - does not have a label text so skipping.";
            continue;
        }

        // get the reference if it exists
        $ref = Reference::getReferenceByUri($uri);
        if(!$ref){
            // no reference so lets make one
            $ref = new Reference();
            $user = unserialize($_SESSION['user']);
            $ref->setUserId($user->getId());
            $ref->setLinkUri($uri);
            $ref->setDisplayText($label);
            $ref->setKind($ref_kind);
            $ref->save();
        }

        // is the reference already in the name?
        $ref_usages = $name->getReferences();
        $found = false;
        foreach($ref_usages as $ref_use){
            if($ref_use->reference == $ref && $ref_use->subject_type == $subject_type){
                // we already have that reference as a taxon/name type.
                // update the comment if there is one
                if($ref_use->comment != $comment){

                    // update comment
                    // fixme

                }

                // no need to look further
                $found = true;
                break;
            }
        }
        
        if(!$found){

            // reference doesn't belong to name so add it
            // fixme

        }


    }


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
                    <option value="">~ Ignore ~</option>
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
                <select name="ref_kind">
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
                <input type="text" name="uri_filter" placeholder="start of uri"/>
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