<div style="width: 800px">
    <h2>Name Check</h2>
    <p style="color: orange;">Changes Rhakhis data if you click bulk change at end of scan.</p>
    <p>This utility will run through the matched rows and check that the name string (single word) in the import
        table's scientific name is the same as the name string in Rhakhis.
        It will only look at those with matched WFO IDs and highlight where there are differences.
    </p>

    <?php 

    if(@$_GET['active_run']){
        // run through the table (paged)
        if(!@$_GET['page']){
            // set up the session variable to track the progress
            $_SESSION['nomenclature_name_check'] = array(
                'names_ok' => 0,
                'mismatches' => array()
            );
        }else{

            echo "<p><strong>Names OK: </strong>" . number_format($_SESSION['nomenclature_name_check']['names_ok'], 0) . "</p>";

            if($_SESSION['nomenclature_name_check']['mismatches']){
                echo "<p><strong>Mismatches: </strong>" . number_format(count($_SESSION['nomenclature_name_check']['mismatches']), 0) . "</p>";
            }else{
                echo "<p>There are no mismatches.</p>";
            }
            
        }

        // defined in nomenclature.php
        if(process_page($table)){
            // returns true means we have finished
            if($_SESSION['nomenclature_name_check']['mismatches']){
                echo "<hr/>";
                echo "<h2>Mismatched Names</h2>";
                echo "<form action=\"index.php?action=nomenclature_name_change\" method=\"POST\" />";
                echo "<table>";
                echo "<tr><th>WFO ID</th><th>Full Name</th><th>Rhakhis</th><th>Table</th><th>Bulk</th><tr>";
                foreach($_SESSION['nomenclature_name_check']['mismatches'] as $mismatch){
                    echo "<tr>";
                    echo $mismatch;
                    echo "</li>";
                }
                echo "<tr><td colspan=\"5\" style=\"text-align: right;\">";
                echo "<input type=\"submit\" value=\"Bulk Change\" />";
                echo "</td></tr>";
                echo "</table>";
                echo "</form>";
            }
        }else{
            echo "<p>Working ...</p>";
        }

    }else{
        render_form($table); 
    }


function process_row($row, $table){


    // last page print out results with links to fix individual names

    // parse the name to get the name parts
    $table_full_name = $row[$_GET['name_column']];
    $name_parts = get_name_parts($table_full_name); // defined in actions/view.php
    $table_name_string = end($name_parts);

    // load a rhakhis name for each matched row
    $wfo = $row['rhakhis_wfo'];
    if(!$wfo || !preg_match('/^wfo-[0-9]{10}$/', $wfo)){
        return;
    }

    $name = Name::getName($wfo);

    // compare
    if($table_name_string == $name->getNameString()){
        $_SESSION['nomenclature_name_check']['names_ok'] = $_SESSION['nomenclature_name_check']['names_ok'] + 1;
    }else{

        $uri =  get_rhakhis_uri($name->getPrescribedWfoId());
        $row = "<td style=\"white-space: nowrap;\" ><a target=\"rhakhis\" href=\"$uri\">$wfo</a></td>";
        $row .= "<td>{$name->getFullNameString()}</td>";
        $row .= "<td>{$name->getNameString()}</td>";
        $row .= "<td>$table_name_string";
        $row .= " (<a href=\"\" onclick=\"navigator.clipboard.writeText('$table_name_string');\">copy</a>)</td>";
        $row .= "<td style=\"text-align: center;\">";
        $row .= "<input type=\"checkbox\" name=\"{$name->getPrescribedWfoId()}\" value=\"$table_name_string\"/>";
        $row .= "</td>";

        $_SESSION['nomenclature_name_check']['mismatches'][] = $row;
    
    }
    // if there is an error then write to the session
    
    // if there is not an error then increment the no issues count.

   // print_r($table_name_string);
    

    //exit;
}

function render_form($table){
        
    global $mysqli;
    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
    $cols = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
?>
    <form action="index.php">
        <input type="hidden" name="action" value="view" />
        <input type="hidden" name="phase" value="nomenclature" />
        <input type="hidden" name="task" value="nomenclature_name_check" />
        <input type="hidden" name="active_run" value="true" />
        <input type="hidden" name="page" value="0" />
        <input type="hidden" name="page_size" value="100" />
        <strong>Name column: </strong>
        <select name="name_column">
            <?php
    foreach($cols as $col){
        $selected = @$_GET['name_column'] == $col['Field'] ? 'selected' : '';
        echo "<option $selected value=\"{$col['Field']}\">{$col['Field']}</option>";
    }
?>
        </select>
        &nbsp;

        <input type="submit" value="Run Check" />
    </form>
    <?php
}
?>



</div>