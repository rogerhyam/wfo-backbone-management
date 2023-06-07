<div style="width: 800px">
<h2>Authors</h2>
<p style="color: red;">Changes data in Rhakhis.</p>
<p>
    Use this tool to copy data from the active table into the <strong>Authors</strong> field of Rhakhis. It works through the rows that have been matched to WFO IDs.
    Progress through the table is tracked by adding a skip to each row that has been compared. A skip is added to the row under the following conditions:</p>
<ul>
    <li>If there is no value in the data table when that row is examined.</li>
    <li>If the value in the table is the same as the value in Rhakhis.</li>
    <li>If Rhakhis is updated for that name.</li>
    <li>If the skip button is selected.</li>
</ul>

<?php 
    if(@$_GET['active_run']){
        process_page($table); // defined in nomenclature.php
    }else{
        render_form($table); 
    }
?>
</div>


<?php

/**
 * Process a single row
 * 
 * @return true will pause the page loader
 */
function process_row($row, $table){

    global $mysqli;
    
    // get out of here is we are skipping
    if($row['rhakhis_skip']) return false;
    if(!$row['rhakhis_wfo']) return false;

    // what's the incoming value
    $new_authors = trim($row[$_GET['authors_column']]);

    // get out of here if there is no new value
    if(!$new_authors){
        $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_skip` = 1 WHERE `rhakhis_pk` = {$row['rhakhis_pk']};");
        return false;
    }

    // load the name
    $name = Name::getName($row['rhakhis_wfo']);
    $rhakhis_authors = trim($name->getAuthorsString());

     // logic time..
    
    // if we are dry run then display a dumb table
    if($_GET['dry_run'] == 'yes'){
        echo "<table>";
        echo "<tr><th>". $name->getPrescribedWfoID() ."</th><td colspan=\"2\">".$name->getFullNameString()  ."</td></tr>";
        echo "<tr><th>Data Table:</th><td>$new_authors</td></tr>"; 
        echo "<tr><th>Rhakhis:</th><td>$rhakhis_authors</td></tr>"; 
        echo "</table>";
    }

    if($new_authors == $rhakhis_authors){
        
        // the values are the same
        if($_GET['dry_run'] == 'yes'){
            echo "<p style=\"color: green;\">Authors values are the same nothing to change.</p>";
        }else{
            // gets flagged as a skip so we don't have to deal with it again
            $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_skip` = 1 WHERE `rhakhis_pk` = {$row['rhakhis_pk']};");
        }
    
    }elseif(!$rhakhis_authors){

        // We have no authors in Rhakhis

        if($_GET['insert'] == 'yes' ||  $_GET['overwrite'] == 'auto'){

            // we have a permission to update

            if($_GET['dry_run'] == 'yes'){
                // tell them what we would do if we could
                if($_GET['insert'] == 'yes') echo "<p style=\"color: red;\">Always Insert is ON so would add data to Rhakhis.</p>";
                if($_GET['overwrite'] == 'auto') echo "<p style=\"color: red;\">Overwrite is ON so would add data to Rhakhis.</p>";
            }else{

                // actually do the inserting - field at a time

                if($_GET['overwrite'] == 'auto'){
                    // we are always inserting
                    $name->setAuthorsString($new_authors);
                }else{
                    // we are only inserting if missing
                    if(!$rhakhis_authors) $name->setAuthorsString($new_authors);
                }
                $response = $name->save();
                $response->consolidateSuccess();
                if(!$response->success){
                    echo "<p style=\"color: red;\">Failed to save '$new_authors' for {$name->getPrescribedWfoId()} : {$response->message}</p>";
                    return true; // stop
                }

                // we've reconciled the name so flag the row as skip
                $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_skip` = 1 WHERE `rhakhis_pk` = {$row['rhakhis_pk']};");
                return false;
            }

        }elseif($_GET['overwrite'] == 'auto'){

            if($_GET['dry_run'] == 'yes'){
                echo "<p style=\"color: orange;\">Overwrite is MOVE ON so we'd just go to the next row.</p>";
            }else{
                return false;
            }

        }else{

            // data is missing but we don't have a flag to automatically update
            if($_GET['dry_run'] == 'yes'){
                echo "<p style=\"color: orange;\">You would be asked about updating this Rhakhis name.</p>";
            }else{
                // actually ask what they want.
                render_ask_form($name, $new_authors, $table, $row['rhakhis_pk']);
                return true;
            }
            
        }

    }elseif($new_authors != $rhakhis_authors){

        // we have different data to that in rhakhis

        if($_GET['overwrite'] == 'yes'){

            // we are in auto

            if($_GET['dry_run'] == 'yes'){
                // just say what we would do
                echo "<p style=\"color: red;\">Overwrite is ON so would add data to Rhakhis.</p>";
            }else{
                // actually do the inserting
                $response = $name->updateAuthorsString($new_authors, null);
                $response->consolidateSuccess();
                if(!$response->success){
                    echo "<p style=\"color: red;\">Failed to save '$new_authors' for {$name->getPrescribedWfoId()} : {$response->message}</p>";
                    return true; // stop
                }
                $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_skip` = 1 WHERE `rhakhis_pk` = {$row['rhakhis_pk']};");
                return false;
            }

        }elseif($_GET['overwrite'] == 'move_on'){

            if($_GET['dry_run'] == 'yes'){
                // just say what we would do
                echo "<p style=\"color: green;\">Overwrite is MOVE ON so would just carry on to the next row.</p>";
            }else{
                // actually do the inserting
                return false;
            }

        }else{

            // we are not in auto
            if($_GET['dry_run'] == 'yes'){
                // just say what we would do
                echo "<p style=\"color: orange;\">Overwrite is OFF so you'd be asked what we should do.</p>";
            }else{
                // ask what they want.
                render_ask_form($name, $new_authors, $table, $row['rhakhis_pk']);
                return true;
            }
        }
    }

    return false;

}

function render_ask_form($name, $new_authors, $table, $row_id){

    $rhakhis_authors = $name->getAuthorsString();
    $rhakhis_authors_escaped = htmlentities($rhakhis_authors);
    $new_authors_escaped = htmlentities($new_authors);

    // build the skip query string
    $params = $_GET;
    $params['rhakhis_pk'] = $row_id;
    $params['rhakhis_column'] = 'rhakhis_skip';
    $params['rhakhis_value'] = 1;
    $params['calling_action'] = $params['action'];
    $params['action'] = 'set_rhakhis_value';
    $skip_query_string = http_build_query($params);

    $rhakhis_authors_escaped = htmlentities($rhakhis_authors);

    echo '<h3>Resolve Issue</h3>';
    echo '<style>th{text-align: right}</style>';
    echo '<form action="index.php" method="GET" />';
    echo '<input type="hidden" name="action" value="rhakhis_set_authors" />';
    echo '<input type="hidden" name="wfo" value="'. $name->getPrescribedWfoId() .'" />';
    echo '<input type="hidden" name="search_query" value="'.  http_build_query($_GET) .'" />';
    echo '<input type="hidden" name="table" value="'.  $table .'" />';
    echo '<input type="hidden" name="rhakhis_pk" value="'.  $row_id .'" />';
    echo "<table>";
    // fixme - 
    echo "<tr><th><a target=\"rhakhis\" href=\"". get_rhakhis_uri($name->getPrescribedWfoID()) . "\"/>". $name->getPrescribedWfoID() ."</a></th><td colspan=\"2\">".$name->getFullNameString()  ."</td></tr>";

    // Rhakhis values
    echo "<tr>";
    echo "<th>Rhakhis:</th>";
    echo "<td><a href=\"#\" onclick=\"document.getElementById('authors_string').value = '$rhakhis_authors_escaped'\">$rhakhis_authors</a></td>";
    echo "</tr>"; 
    echo "<tr>";

    // data table 
    echo "<tr>";
    echo "<th>Data Table:</th>";
    echo "<td><a href=\"#\" onclick=\"document.getElementById('authors_string').value = '$new_authors_escaped'\">$new_authors</a></td>";
    echo "</tr>";

    echo "<th>Update Rhakhis to:</th>";
    echo "<td><input type=\"text\" name=\"authors_string\" id=\"authors_string\" value=\"$new_authors_escaped\"  size=\"60\"/></td>";
    echo "</tr>"; 
    echo "<tr><td colspan=\"3\" style=\"text-align: right;\"> <input type=\"submit\" value=\"Update Rhakhis & Add Skip\" /></td></tr>"; 
    echo "<tr><td colspan=\"3\" style=\"text-align: right;\"><a href=\"index.php?$skip_query_string\">Skip</a></td></tr>"; 
    echo "</table>";

}

function render_form($table){

    global $mysqli;

    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
    $cols = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
?>

<form action="index.php" method="GET">
    <input type="hidden" name="action" value="view" />
    <input type="hidden" name="phase" value="nomenclature" />
    <input type="hidden" name="task" value="nomenclature_authors" />
    <input type="hidden" name="active_run" value="true" />
    <input type="hidden" name="page" value="0" />
    <input type="hidden" name="page_size" value="1000" />

    <style>
        th{ text-align: right;}
    </style>

    <table>
    <tr><td colspan="3" style="background-color: gray; color: white;"><strong>Mapping</strong></td></tr>
    <tr>
        <th>Authors Column</th>
        <td>

    <select name="authors_column">
<?php
    foreach($cols as $col){
        $selected = @$_GET['authors_column'] == $col['Field'] ? 'selected' : '';
        echo "<option $selected value=\"{$col['Field']}\">{$col['Field']}</option>";
    }
?>
   </select>
        </td>
        <td>You must select the column with the data in it.</td>
    <tr>

    <tr><td colspan="3" style="background-color: gray; color: white;" ><strong>Overwrite Rules</strong></td></tr>
    <tr>
        <th>Overwrite:</th>
        <td>
            <input type="radio" id="overwrite_yes" name="overwrite" value="yes"  ><label for="overwrite_yes">Auto</label><br/>
            <input type="radio" id="overwrite_no" name="overwrite" value="no" ><label for="overwrite_no">Ask</label><br/>
            <input type="radio" id="overwrite_no" name="overwrite" value="move_on" checked="true" ><label for="overwrite_no">Move&nbsp;On</label>
        </td>
        <td>If there is a value in the data table and it differs from the value in Rhakhis should it automatically overwrite the value in Rhakhis, should it ask what to do or should it simply carry on to the next row?</td>
    </tr>
    <tr>
        <th>Always&nbsp;Insert:</th>
        <td>
            <input type="radio" id="insert_yes" name="insert" value="yes"  ><label for="insert_yes">Yes</label>
            <input type="radio" id="insert_no" name="insert" value="no" checked="true" ><label for="insert_no">No</label>
        </td>
        <td>If there is a value in the data table but no value in Rhakhis should it insert the value into Rhakhis without asking even if Overwrite is set to Ask.</td>
    </tr>
    <tr>
        <th>Dry run:</th>
        <td>
            <input type="radio" id="dry_run_yes" name="dry_run" value="yes" checked="true" ><label for="dry_run_yes">Yes</label>
            <input type="radio" id="dry_run_no" name="dry_run" value="no"><label for="dry_run_no">No</label>
        </td>
        <td>Don't write anything to Rhakhis just display what would be done and pause for each page.</td>
    </tr>

    <tr>
        <td style="text-align: right" colspan="5" >Start run through non-skipped rows with WFO ID set: <input type="submit" /></td>
    </tr>
    </table>
<?php
    
}// render form

?>