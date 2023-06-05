<div style="width: 800px">
<h2>Published In</h2>
<p style="color: red;">Changes data in Rhakhis.</p>
<p>
    Use this tool to copy data from the active table into the Published In (micro citation) field of Rhakhis. It works through the rows that have been matched to WFO IDs.
    Progress through the table is tracked by adding a skip to each row that has been compared. A skip is added to the row under the following conditions:</p>
<ul>
    <li>If there is no value in the data table when that row is examined.</li>
    <li>If the value in the table is the same as the value in Rhakhis.</li>
    <li>If Rhakhis is updated for that name.</li>
    <li>If the skip button is selected.</li>
</ul>
<p>You can concatenate up to four columns from the data table to form the string. Separators are only added if they are followed by a value.</p>

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

    // build the update string
    $published_in = $row[$_GET['column_1']];

    if($_GET['column_2'] && $row[$_GET['column_2']]){
        $published_in .= $_GET['separator_1'];
        $published_in .= $row[$_GET['column_2']];
    }

    if($_GET['column_3'] && $row[$_GET['column_3']]){
        $published_in .= $_GET['separator_2'];
        $published_in .= $row[$_GET['column_3']];
    }

    if($_GET['column_4'] && $row[$_GET['column_4']]){
        $published_in .= $_GET['separator_3'];
        $published_in .= $row[$_GET['column_4']];
    }

    $published_in = trim($published_in);

    // get out of here if there is no published_in value
    if(!$published_in){
        $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_skip` = 1 WHERE `rhakhis_pk` = {$row['rhakhis_pk']};");
        return false;
    } 

    // extract the year if we can
    $matches = array();
    $data_year = '';
    if(preg_match_all('/([0-9]{4})/', $published_in, $matches, PREG_SET_ORDER)){
        foreach($matches as $hit){
            $year = (int)$hit[1];
            if($year > 1750 && $year <= date("Y")){
               $data_year= $year;
            }
        }
    }
 
    // load the name
    $name = Name::getName($row['rhakhis_wfo']);

    $rhakhis_published_in = $name->getCitationMicro();
    $rhakhis_year = $name->getYear();

    // logic time..
    
    // if we are dry run then display a dumb table
    if($_GET['dry_run'] == 'yes'){
        echo "<table>";
        echo "<tr><th>". $name->getPrescribedWfoID() ."</th><td colspan=\"2\">".$name->getFullNameString()  ."</td></tr>";
        echo "<tr><th>Data Table:</th><td>$published_in</td><td>$data_year</td></tr>"; 
        echo "<tr><th>Rhakhis:</th><td>$rhakhis_published_in</td><td>$rhakhis_year</td></tr>"; 
        echo "</table>";
    }

    if($published_in == $rhakhis_published_in && $data_year == $rhakhis_year){
        
        // the published in and the year are the same
        if($_GET['dry_run'] == 'yes'){
            echo "<p style=\"color: green;\">Published in values are the same nothing to change.</p>";
        }else{
            // gets flagged as a skip so we don't have to deal with it again
            $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_skip` = 1 WHERE `rhakhis_pk` = {$row['rhakhis_pk']};");
        }
    
    }elseif(($published_in && !$rhakhis_published_in) || ($data_year && !$rhakhis_year)){

        // Either the publication or year are missing in Rhakhis

        if($_GET['insert'] == 'yes' ||  $_GET['overwrite'] == 'auto'){

            // we have a permission to update

            if($_GET['dry_run'] == 'yes'){
                // tell them what we would do if we could
                if($_GET['insert'] == 'yes') echo "<p style=\"color: red;\">Always Insert is ON so would add data to Rhakhis.</p>";
                if($_GET['overwrite'] == 'auto') echo "<p style=\"color: red;\">Overwrite is ON so would add data to Rhakhis.</p>";
            }else{

                // actually do the inserting - field at a time

                if($_GET['overwrite'] == 'auto'){

                    // we don't overwrite with blank values
                    if($published_in) $name->setCitationMicro($published_in);
                    if($data_year) $name->setYear($data_year);

                }else{
                    // we are only inserting missing
                    // no published in so update that but keep year the same
                    if(!$rhakhis_published_in && $published_in) $name->setCitationMicro($published_in);
                    
                    // no year so update that but keep published in the same
                    if(!$rhakhis_year && $data_year) $name->setYear($data_year);    
                }

                $name->save();

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
                render_ask_form($name, $published_in, $data_year, $table, $row['rhakhis_pk']);
                return true;
            }
            
        }

    }elseif(($published_in && ($published_in != $rhakhis_published_in) || $data_year && ($data_year != $rhakhis_year))){

        // we have different data to that in rhakhis

        if($_GET['overwrite'] == 'yes'){

            // we are in auto

            if($_GET['dry_run'] == 'yes'){
                // just say what we would do
                echo "<p style=\"color: red;\">Overwrite is ON so would add data to Rhakhis.</p>";
            }else{
                // actually do the inserting
                $response = $name->updatePublication($published_in, $data_year, null);
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
                render_ask_form($name, $published_in, $data_year, $table, $row['rhakhis_pk']);
                return true;
            }
        }

    }

    return false;

}

function render_ask_form($name, $published_in, $data_year, $table, $row_id){
    $rhakhis_published_in = $name->getCitationMicro();
    $rhakhis_year = $name->getYear();


    // build the skip query string
    $params = $_GET;
    $params['rhakhis_pk'] = $row_id;
    $params['rhakhis_column'] = 'rhakhis_skip';
    $params['rhakhis_value'] = 1;
    $params['calling_action'] = $params['action'];
    $params['action'] = 'set_rhakhis_value';
    $skip_query_string = http_build_query($params);

    $rhakhis_published_in_escaped = htmlentities($rhakhis_published_in);
    $published_in_escaped = htmlentities($published_in);

    echo '<h3>Resolve Issue</h3>';
    echo '<style>th{text-align: right}</style>';
    echo '<form action="index.php" method="GET" />';
    echo '<input type="hidden" name="action" value="rhakhis_set_citation_micro" />';
    echo '<input type="hidden" name="wfo" value="'. $name->getPrescribedWfoId() .'" />';
    echo '<input type="hidden" name="search_query" value="'.  http_build_query($_GET) .'" />';
    echo '<input type="hidden" name="table" value="'.  $table .'" />';
    echo '<input type="hidden" name="rhakhis_pk" value="'.  $row_id .'" />';
    echo "<table>";
    echo "<tr><th><a target=\"rhakhis\" href=\"". get_rhakhis_uri($name->getPrescribedWfoID()) . "\"/>". $name->getPrescribedWfoID() ."</a></th><td colspan=\"2\">".$name->getFullNameString()  ."</td></tr>";

    // Rhakhis values
    echo "<tr>";
    echo "<th>Rhakhis:</th>";
    echo "<td><a href=\"#\" onclick=\"document.getElementById('published_in').value = '$rhakhis_published_in_escaped'\">$rhakhis_published_in</a></td>";
    echo "<td><a href=\"#\" onclick=\"document.getElementById('published_year').value = '$rhakhis_year'\">$rhakhis_year</a></td>";
    echo "</tr>"; 
    echo "<tr>";

    // data table 
    echo "<tr>";
    echo "<th>Data Table:</th>";
    echo "<td><a href=\"#\" onclick=\"document.getElementById('published_in').value = '$published_in_escaped'\">$published_in</a></td>";
    echo "<td><a href=\"#\" onclick=\"document.getElementById('published_year').value = '$data_year'\">$data_year</a></td>";
    echo "</tr>";

    echo "<th>Update Rhakhis to:</th>";
    echo "<td><input type=\"text\" name=\"published_in\" id=\"published_in\" value=\"$published_in_escaped\"  size=\"60\"/></td>";
    echo "<td><input type=\"text\" name=\"published_year\" id=\"published_year\" value=\"$data_year\" size=\"4\" /></td>";
    echo "</tr>"; 
    echo "<tr><td colspan=\"3\" style=\"text-align: right;\"> <input type=\"submit\" value=\"Update Rhakhis & Add Skip\" /></td></tr>"; 
    echo "<tr><td colspan=\"3\" style=\"text-align: right;\"><a href=\"index.php?$skip_query_string\">Skip</a></td></tr>"; 
    echo "</table>";

}

/**
 * 
 *  Render the form
 * 
 */
function render_form($table){

    global $mysqli;

    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
    $cols = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
?>

<form action="index.php" method="GET">
    <input type="hidden" name="action" value="view" />
    <input type="hidden" name="phase" value="nomenclature" />
    <input type="hidden" name="task" value="nomenclature_published_in" />
    <input type="hidden" name="active_run" value="true" />
    <input type="hidden" name="page" value="0" />
    <input type="hidden" name="page_size" value="1000" />
    <input type="hidden" name="names_compared" value="0" />
    <input type="hidden" name="names_modified" value="0" />
<style>
    th{ text-align: right;}
</style>

<table>
    <tr><td colspan="3" style="background-color: gray; color: white;"><strong>Mappings</strong></td></tr>
    <!-- First -->
    <tr>
        <th>First Column:</th>
        <td>

    <select name="column_1">
<?php
    foreach($cols as $col){
        $selected = @$_GET['rank_column'] == $col['Field'] ? 'selected' : '';
        echo "<option $selected value=\"{$col['Field']}\">{$col['Field']}</option>";
    }
?>
   </select>

        </td>
        <td>You must select at least one column.</td>
    <tr>
    <tr>
        <th>Separator:</th>
            <td><input type="text" name="separator_1" size="3" value=" " /></td>
        <td>This defaults to a space.</td>
    </tr>

    <!-- Second -->
    <tr>
        <th>Second Column:</th>
        <td>
    <select name="column_2">
        <option value="">~ select second column ~</option>
<?php
    foreach($cols as $col){
        $selected = @$_GET['rank_column'] == $col['Field'] ? 'selected' : '';
        echo "<option $selected value=\"{$col['Field']}\">{$col['Field']}</option>";
    }
?>
   </select>
        </td>
        <td>Optional second column</td>
    <tr>
        <th>Separator:</th>
        <td><input type="text" name="separator_2" size="3" value=" " /></td>
        <td>This defaults to a space.</td>
    </tr>

    <!-- Third -->
    </tr>
        <tr>
        <th>Third Column:</th>
        <td>
    <select name="column_3">
        <option value="">~ select third column ~</option>
<?php
    foreach($cols as $col){
        $selected = @$_GET['rank_column'] == $col['Field'] ? 'selected' : '';
        echo "<option $selected value=\"{$col['Field']}\">{$col['Field']}</option>";
    }
?>
   </select>
        </td>
        <td>Optional third column</td>
    </tr>
    <tr>
        <th>Separator:</th>
        <td><input type="text" name="separator_3" size="3" value=" " /></td>
        <td>This defaults to a space.</td>
    </tr>
    <!-- Fourth -->
    </tr>
        <tr>
        <th>Fourth Column:</th>
        <td>
    <select name="column_4">
        <option value="">~ select fourth column ~</option>
<?php
    foreach($cols as $col){
        $selected = @$_GET['rank_column'] == $col['Field'] ? 'selected' : '';
        echo "<option $selected value=\"{$col['Field']}\">{$col['Field']}</option>";
    }
?>
   </select>
        </td>
        <td>Optional fourth column</td>
    </tr>


    <!-- Rules -->

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



</form>

<?php
} // end render form
?>