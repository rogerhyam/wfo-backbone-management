<h2>Matching</h2>

<p>Here we align the rows in the selected table with names in Rhakhis.</p>

<?php

    $table = @$_SESSION['selected_table'];
    if(!$table){
        echo "<p>You haven't selected a table. Go to the table tab and select one.</p>";
        exit;
    }


    // are we in the middle of a run or not?

    if(@$_GET['active_run']){
        if($_GET['name_col'] == '~'){
            run_id_matching();
        }else{
            run_name_matching();
        }
        
    }else{
        render_options_form($table);
        render_algorithm_description();
    }


// --------------------------------- //
    function run_id_matching(){
        echo "<p>ID matching hasn't been implemented yet!</p>";
        exit;
    }

    function run_name_matching(){

        global $mysqli;
        global $ranks_table;

        $table = @$_SESSION['selected_table'];

        $page = (int)$_GET['page'];
        $page_size = (int)$_GET['page_size'];
        $offset = $page_size * $page;

        echo "<p><strong>Offset: </strong>$offset | <strong>Page Size: </strong>$page_size</p>";

        $name_col = $_GET['name_col'];
        $authors_col = $_GET['authors_col'];

        $sql = "SELECT * FROM `rhakhis_bulk`.`$table`
            WHERE `rhakhis_wfo` is null
            AND   (`rhakhis_skip` != 1 OR `rhakhis_skip` is null) 
            LIMIT $page_size
            OFFSET $offset";

        //echo $sql; exit;

        $response = $mysqli->query($sql);
        $rows = $response->fetch_all(MYSQLI_ASSOC);

        // if we have more than 0 rows we may need to render the next page
        if(count($rows) > 0){
            
            $params = $_GET;
            $params['page'] = ($page + 1);
            $uri = "index.php?" . http_build_query($params);
            $auto_render_next_page = "<script>window.location = \"$uri\"</script>";
        }else{
            $auto_render_next_page = "<p>Reached end of table</p>";
        }

        // work through the rows and render appropriately
        foreach($rows as $row){

            $name_string = $row[$name_col];
            $authors_string = null;
            if(isset($row[$authors_col])) $authors_string = $row[$authors_col];
            $matches = getMatches($name_string, $authors_string);

            // FIXME ADD OTHER FIELDS IF WE HAVE THEM
            echo "<div><h3>{$row['rhakhis_pk']}:&nbsp;$name_string $authors_string</h3>";
                
            if($matches['unambiguous']){

               // we need all the goodness of a real name object
                $name = Name::getName($matches['unambiguous']['id']);
                echo "<p>";
                echo "<strong style=\"color: green\" >Match:</strong> ";
                echo $name->getFullNameString();
                echo "</p>";

                $wfo = $name->getPrescribedWfoId();
 
                $sql = "UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_wfo` = '$wfo' WHERE `rhakhis_pk` = {$row['rhakhis_pk']};";
                $mysqli->query($sql);

            }else{

                // if we are interactive we need to render a form.
                // if not we will just flag it in passing
                if(@$_GET['interactive_mode']){

                    // render a picker.
                    if(count($matches['candidates']) == 1){

                            // we have a good match but there are homonyms that may cause confusion

                            echo "<p>Good single candidate:</p>";
                            echo '<p style="padding-left: 2em">';
                           // render_name($matches['candidates'][0]);

                            render_name_set_link($matches['candidates'][0]['id'], $row['rhakhis_pk'], 'rhakhis_wfo');

                            echo "</p>";


                            if(count($matches['homonyms']) > 0){
                                echo "<p>Homonyms exist:</p>";
                                foreach ($matches['homonyms'] as $homo) {
                                    echo '<p style="padding-left: 2em">';
                                    render_name_set_link($homo, $table, $row['rowid'], $page);
                                    echo "</p>";
                                }
                            }
                            

                    }elseif(count($matches['candidates']) == 0){

                        if(count($matches['candidates'])){
                            echo "<p>Fuzzy Matches</p>";
                            foreach ($matches['fuzzy'] as $can) {
                                echo '<p style="padding-left: 2em">';
                                render_name_set_link($can['id'], $row['rhakhis_pk'], 'rhakhis_wfo');
                                echo "</p>";
                            }
                        }else{
                            echo "<p>Sorry, no suggestions found.</p>";
                        }
                        
                    }else{
                            // we have multiple candidates to choose between. 
                            echo "<p>Multiples candidates</p>";
                            foreach ($matches['candidates'] as $can) {
                                echo '<p style="padding-left: 2em">';
                                render_name_set_link($can['id'], $row['rhakhis_pk'], 'rhakhis_wfo');
                                echo "</p>";
                            }
                    }

                    // stop processing 
                    $params = $_GET;
                    $params['rhakhis_pk'] = $row['rhakhis_pk'];
                    $params['rhakhis_column'] = 'rhakhis_skip';
                    $params['rhakhis_value'] = 1;
                    $params['calling_action'] = $params['action'];
                    $params['action'] = 'set_rhakhis_value';
                    $query_string = http_build_query($params);

                    $auto_render_next_page = ""; // stop refreshing page

                    echo '<hr/>';
                    echo "<p>Create and link a new name:</p>";

                    echo '<form method="GET" action="index.php">';
                    
                    // copy in all the params so we can come back to here
                    foreach($_GET as $param => $val){

                        if($param == 'calling_action') continue;
                        
                        if($param == 'action'){
                            echo '<input type="hidden" name="action" value="create_name" />';
                            echo '<input type="hidden" name="calling_action" value="'. $val .'" />';
                        }else{
                            echo '<input type="hidden" name="'.$param.'" value="'.$val.'" />';
                        }

                    }

                    echo '<input type="hidden" name="rhakhis_pk" value="'. $row['rhakhis_pk'] .'" />';

                    if(count($matches['name_parts']) > 1) echo 'Genus: <input type="text" name="genus_string" value='.  $matches['name_parts'][1] .'" />';
                    if(count($matches['name_parts']) > 2) echo 'Species: <input type="text" name="species_string" value="'.  $matches['name_parts'][2] .'" />';
                    echo ' Name: <input type="text" name="name_string" value="'.  $matches['name_parts'][0] .'" />';
                    echo ' Authors: <input type="text" size="36" name="authors_string" value="'.  $authors_string .'" />';

                    echo ' Rank: <select name="rank_string">';

                    foreach($ranks_table as $rank_name => $rank){
                        $selected = "";
                        if(count($matches['name_parts']) > 2 && $rank_name == 'subspecies') $selected = 'selected';
                        if(count($matches['name_parts']) > 1 && $rank_name == 'species') $selected = 'selected';
                        if(count($matches['name_parts']) == 1 && $rank_name == 'genus') $selected = 'selected';
                        echo "<option value=\"$rank_name\" $selected>$rank_name</option>";
                    }

                    echo '</select>';

                    echo ' <input type="submit" value="Create name"/>';

                    echo '</form>';
                    echo "<p>(Other details are added in the nomenclature phase.)</p>";

                    echo '<hr/>';

                    echo "<p>Select/create name or <a href=\"/index.php?$query_string\">skip</a>.</p>";

                     echo '<hr/>';
                    
                    echo '<table>';
                    
                    foreach($row as $col => $val){
                        echo "<tr><th style=\"color: gray; text-align: right;\">$col:</th><td>$val</td></tr>";
                    }
                    
                    echo '</table>';


                    echo '<hr/>';

                    break;

                }else{
                    
                    // in unattended mode and there is some doubt.

                    if(@$_GET['homonyms_ok']){
                        
                        // we have flagged we don't mind the existence of homonyms

                        if(count($matches['duplicates']) == 1){

                            // There is only one duplicate (weird I know) so we use it
                            $name = Name::getName($matches['duplicates'][0]['id']);
                            echo "<p>";
                             echo "<strong style=\"color: orange\" >Match with homonyms:</strong>";
                            echo $name->getFullNameString();
                            echo "</p>";

                            $wfo = $name->getPrescribedWfoId();
            
                            $sql = "UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_wfo` = '$wfo' WHERE `rhakhis_pk` = {$row['rhakhis_pk']};";
                            $mysqli->query($sql);

                        }elseif(count($matches['duplicates']) > 1){

                            // there are multiple duplicates (same name and author string)
                            // shouldn't happen really - we will deduplicate them later
                            // we need to pick the one with the best nomenclatural status.

                            // we put the dupes into bins depending on their nomenclaturalStatus
                            $bins = array(
                                array(), // 0 is best = valid
                                array(), // 1 is ok - anything above unknown but not valid
                                array(), // 2 is unknown 
                                array() // 3 is deprecated
                            );
                            foreach ($matches['duplicates'] as $dupe) {
                                switch ($dupe['status']) {
                                    case 'deprecated':
                                        $bins[3][] = $dupe;
                                        break;
                                    case 'unknown':
                                        $bins[2][] = $dupe;
                                        break;
                                    case 'valid':
                                        $bins[0][] = $dupe;
                                        break;
                                    default:
                                        $bins[1][] = $dupe;
                                        break;
                                }
                            }

                            // we work through the bins and pick the first dupe we come
                            // across if it is on its own. If it isn't alone then we can't decide.
                            foreach ($bins as $bin) {
                                if(count($bin) == 0) continue; // nothing in that bin
                                if(count($bin) > 1){
                                    echo "<p>Doubt between duplicates even ...</p>";
                                    break; // we can't choose between them
                                } 

                                // $bin must contain 1
                                $name = Name::getName($bin[0]['id']);
                                echo "<p>";
                                echo "<strong style=\"color: orange\" >Match with homonyms:</strong>";
                                echo $name->getFullNameString();
                                echo "</p>";
                                $wfo = $name->getPrescribedWfoId();                
                                $sql = "UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_wfo` = '$wfo' WHERE `rhakhis_pk` = {$row['rhakhis_pk']};";
                                $mysqli->query($sql);
                                break;

                            }

                        }elseif(count($matches['candidates']) >0){
                            $n = count($matches['candidates']);
                            echo "<p>$n candidates but no way to get an unambiguous match without human intervention.</p>";
                        }else{
                            echo "<p>No duplicates or candidates found. Nothing we can do without human intervention.</p>";
                        }

                    }else{
                        echo "<p>Some doubt ...</p>";
                    }

                }

            }
            echo "</div>";
            flush();
        }

        // we automatically 
        echo $auto_render_next_page;




    }



    function render_options_form($table) {
?>
    <h3>Options</h3>
    <form action="index.php" method="GET">
        <input type="hidden" name="action" value="view" /> 
        <input type="hidden" name="phase" value="matching" /> 
        <input type="hidden" name="active_run" value="true" />
        <input type="hidden" name="page" value="0" />
        <input type="hidden" name="page_size" value="1000" />
    <table>
        <tr>
            <th>Scientific Name Column</th>
                <td>
                <select name="name_col">
                    <option value="~">~ Local ID Only ~</option>
                    <?php  render_column_options($table, null); ?>
                </select>
                </td>
                <td>This must be set.</td>
            </tr>
            <tr>
            <th>Authors Column</th>
                <td>
                <select name="authors_col">
                    <option value="">~ Pick One ~</option>
                    <?php  render_column_options($table, null) ?>
                </select>
                </td>
                <td>This is optional but highly desireable.</td>
            </tr>
            <tr>
            <th>Local ID Column</th>
                <td>
                <select name="name_id_col">
                    <option value="">~ Pick One ~</option>
                    <?php  render_column_options($table, null) ?>
                </select>
                </td>
                <td>This is only used if we know the dataset has been previously matched and the TEN IDs added.</td>
            </tr>
            <tr>
            <th>Local ID Kind</th>
                <td>
                <select name="name_id_type">
                    <option value="">~ Pick One ~</option>
                    <?php  render_id_type_options(null) ?>
                </select>
                </td>
                <td>This must be set if the Local ID Column is set.</td>
            </tr>
            <tr>
            <th>Homonyms OK</th>
                <td style="text-align: center;">
                <input type="checkbox" name="homonyms_ok" value="true" />
                </td>
                <td>Check to signify a lax approach to life.</td>
            </tr>
            <th>Interactive Mode</th>
                <td style="text-align: center;">
                <input type="checkbox" name="interactive_mode" value="true" />
                </td>
                <td>Check to get asked your opinion on all the ambiguous/unresolved names.</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: right;"><input type="submit" value="Start Matching Run"/></td>
            </tr>
        </table>
    </form>
<?php
}

function render_algorithm_description(){

?>

<hr/>
<h3>That Magic Matching Algorithm</h3>
<ol>
    <li>Scientific name is normalized to the canonical form. i.e. any rank or rank abbreviation is removed to leave only one, two or three words separated by single spaces.</li>
    <li>All names matching the canonical form and also the authors string are selected. If there are duplicates any deprecated names are removed - provided they aren't all deprecated. The remains are the "duplicates".</li>
    <li>All names matching the canonical form alone are selected (ignoring the authors string). Deprecated homonyms are removed. These are homonyms.</li>
    <li>If there is only one "duplicate" and no homonyms it is considered an unambiguous match and the WFO ID is written to the table.</li>
    <li>If there are either multiple duplicates and/or homonyms then it depends on the homonym flag setting:
        <ol>
            <li>Homonyms OK:
                <ol>
                    <li>If there is only one duplicate the homonyms (different authors) are ignored and the WFO ID written to the table.</li>
                    <li>If there are multiple duplicates then:
                        <ol>
                            <li>Any homonyms are ignored.</li>
                            <li>If the local identifier variables are set and only one of the names matches these variables then that is the unambiguous name and the WFO ID is written to the table.</li>
                            <li>If local identifiers are not set then one of the duplicates is selected based on ranking the names by nomenclatural status. valid > other > unknown > deprecated. If this still doesn't find an unambiguous match (e.g. there are two valid names) then no match is found. Otherwise WFO ID of pick is written to the table.</li>
                        </ol>
                </ol>
            </li>
            <li>Homonyms not OK: Any ambiguity will prevent WFO ID being written to the table.</li>
        </ol>
    </li>
    <li>In <strong>Unattended Mode</strong> any unresolved ambiguity is ignored and the next name processed.</li>
    <li>In <strong>Interactive Mode:</strong> any unresolved ambiguity leads to the presentation of a choice screen.</li>
    <li>If the scientific name column is set to "~ Local ID Only ~" then only the local ID fields are used to match and only match if they are unambiguous. All other fields are ignored.</li>
    
</ol>
<p>Currently there is no fussy matching of name strings.</p>
<hr/>

<?php

} // end render_algorithm_description

function render_column_options($table, $selected_col){

    global $mysqli; 

    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
    $cols = $response->fetch_all(MYSQLI_ASSOC);

    print_r($cols);
    foreach($cols as $col){
        echo "<option value=\"{$col['Field']}\">{$col['Field']}</option>";
    }

}

function render_id_type_options($selected_type){

    global $mysqli; 

    $result = $mysqli->query("SHOW COLUMNS FROM `identifiers` LIKE 'kind'");
    $row = $result->fetch_assoc();
    $type = $row['Type'];
    echo $type;
    preg_match("/'(.*)'/i", $type, $matches);
    $vals = explode(',', $matches[1]);
    foreach($vals as $val){
        $val = str_replace("'", "", $val);
        echo "<option value=\"$val\">$val</option>";
    }
    $result->close();

}

// similar to method in server side application
function getMatches($nameString, $authorsString){

    global $mysqli;

    $matches = array(
        'unambiguous' => null, // an unambiguous matched name - candidates and homonyms will be empty if this is present
        'duplicates' => array(), // where there are more than one unambiguous name!
        'candidates' => array(), // possible names - may be only one which isn't unambiguous if homonyms are present
        'homonyms' => array(), // possible homonyms - have the same name string but different author strings
        'fuzzy' => array(), // fuzzy matches if all else fails
        'name_parts' => array() // parsed name
    );

    // clean up the name first
    $nameString = trim($nameString);

    // FIXME - remove hybrid symbol
    $nameString = str_replace('×', '', $nameString);

    // the name may include a rank abbreviation
    $nameParts = explode(' ', $nameString);
    $newNameParts = array();
    foreach($nameParts as $part){
        // strip out the rank parts.
        if(!isRankWord($part)){
            $newNameParts[] = $part;
        }
    }
    $nameString = implode(' ', $newNameParts);

    $matches['name_parts'] = $newNameParts;

    // clean the authors - not much to do here
    $authorsString = trim($authorsString);

    // ready to start matching

    // perfect scores are 100% match of name and authors with no homonyms.
    $nameString_sql = $mysqli->real_escape_string($nameString);
    $authorsString_sql = $mysqli->real_escape_string($authorsString);

    $response = $mysqli->query("SELECT * FROM `names` WHERE `name_alpha` = '$nameString_sql' AND `authors` = '$authorsString_sql' LIMIT 100");
    $matches['duplicates'] = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    // do we have any that match the name but not the author string. That would be a downgrade.
    $response = $mysqli->query("SELECT * FROM `names` WHERE `name_alpha` = '$nameString_sql' AND (`authors` != '$authorsString_sql' OR `authors` is null)");
    $matches['homonyms'] = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    // stop here if we have candidate names with same characters - should only be one really.
    if(count($matches['duplicates']) > 0){

        // there is no ambiguity
        if(count($matches['duplicates']) == 1 && count($matches['homonyms']) == 0){
            $matches['unambiguous'] = $matches['duplicates'][0];
        }

        return $matches;
    } 
    
    // OK we don't have any candidates yet. What do we do?

    // if we have homonyms - i.e. name matches but author strings don't we return them as the matches.
    if(count($matches['homonyms']) > 0){
        $matches['candidates'] = $matches['homonyms'];
        $matches['homonyms'] = array();
        return $matches;
    }


    $fuzzParts = array();
    foreach($newNameParts as $p){
        $fuzzParts[] = substr($p, 0, -2) . '%';
    }

    if(count($fuzzParts) == 3){
        $sql = "SELECT * FROM `names` AS n WHERE n.`name` like '$fuzzParts[2]' AND n.`species` like  '$fuzzParts[1]' AND n.`genus` like = '$fuzzParts[0]'";
    }elseif(count($newNameParts) == 2){
        $sql = "SELECT * FROM `names` AS n WHERE n.`name` like '$fuzzParts[1]' AND n.`genus` like = '$fuzzParts[0]'";
    }else{
        $sql = "SELECT * FROM `names` AS n WHERE n.`name` like '$fuzzParts[0]'";
    }

    // lets do a fussy look see.
    $response = $mysqli->query($sql);
    $matches['fuzzy'] = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();


    // well we didn't find anything!
    return $matches;

}

// duplicate function again - same as in NameMatcher
function isRankWord($word){

    global $ranks_table;

    $word = strtolower($word);
    foreach($ranks_table as $rank => $rankInfo){

        // does it match the rank name
        if(strtolower($word) == $rank) return $rank;

        // does it match the official abbreviation
        if($word == strtolower($rankInfo['abbreviation'])) return $rank;

        // does it match one of the known alternatives
        foreach($rankInfo['aka'] as $aka){
            if($word == strtolower($aka)) return $rank;
        }

    }

    // no luck so it isn't a rank word we know of
    return false;

}

?>

