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
        
        global $mysqli;

        $table = @$_SESSION['selected_table'];

        $page = (int)$_GET['page'];
        $page_size = (int)$_GET['page_size'];
        $offset = $page_size * $page;

        echo "<p><strong>Offset: </strong>$offset | <strong>Page Size: </strong>$page_size</p>";

        $name_id_col = $_GET['name_id_col'];
        $name_id_type = $_GET['name_id_type'];
        $name_id_prefix = $_GET['name_id_prefix'];

        $sql = "SELECT * FROM `rhakhis_bulk`.`$table`
            LIMIT $page_size
            OFFSET $offset";

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

        foreach($rows as $row){

            $name_id_value = $mysqli->real_escape_string($name_id_prefix . $row[$name_id_col]);
            $sql = "SELECT * FROM `identifiers` WHERE `kind` = '$name_id_type' AND `value` = '$name_id_value'";
            $response = $mysqli->query($sql);
            $rows = $response->fetch_all(MYSQLI_ASSOC);
            $response->close();
            if(count($rows) > 0){
                // found it so bind it
                $name = Name::getName($rows[0]['name_id']);
                $wfo = $name->getPrescribedWfoId();
                $sql = "UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_wfo` = '$wfo' WHERE `rhakhis_pk` = {$row['rhakhis_pk']};";
                $mysqli->query($sql);
                echo "<p><strong>$name_id_value</strong> Found! $wfo for " . $name->getFullNameString() . " added to table.</p>"; 
            }else{
                echo "<p><strong>$name_id_value</strong> Not found!</p>"; 
            }

            flush();
        
        }

        // we automatically 
        echo $auto_render_next_page;



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
            LIMIT $page_size
            OFFSET $offset";

        $response = $mysqli->query($sql);
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        $response->close();

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

            if($row['rhakhis_skip']) continue;
            if($row['rhakhis_wfo']) continue;

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

                    switch (count($matches['name_parts'])) {
                        case 2:
                            echo 'Genus: <input type="text" name="genus_string" value="'.  $matches['name_parts'][0] .'" />';
                            echo ' Species: <input type="text" name="name_string" value="'.  $matches['name_parts'][1] .'" />';
                            break;
                        case 3:
                            echo 'Genus: <input type="text" name="genus_string" value="'.  $matches['name_parts'][0] .'" />';
                            echo ' Species: <input type="text" name="species_string" value="'.  $matches['name_parts'][1] .'" />';
                            echo ' Subspecific epithet: <input type="text" name="name_string" value="'.  $matches['name_parts'][2] .'" />';
                            break;
                        default:
                            echo 'Name: <input type="text" name="name_string" value="'.  $matches['name_parts'][0] .'" />';;
                            break;
                    }
                    
                    echo ' Authors: <input type="text" size="36" name="authors_string" value="'.  $authors_string .'" />';

                    echo ' <span style="color: red">Rank</span>: <select name="rank_string">';

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

                    echo "<p>Select/create name or <a href=\"index.php?$query_string\">skip</a>.</p>";

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
                           echo "<p>No possible matches found</p>";
                        }

                    }else{

                        // homonyms not OK

                        // report on what we have

                        $duplicates_count = count($matches['duplicates']);
                        echo "<p>Duplicates count: $duplicates_count</p>";

                        $homonyms_count = count($matches['homonyms']);
                        echo "<p>Homonym count: $homonyms_count</p>";

                        $candidates_count = count($matches['candidates']);
                        echo "<p>Candidate count: $candidates_count</p>";

                        if(@$_GET['auto_create'] && $duplicates_count == 0 && $homonyms_count == 0 && $candidates_count == 0){

                            echo "<p>No duplicates or homonyms and auto create is on so will try and auto create name.</p>";
                                
                            // we need to have a good rank to create the name at
                            $proposed_rank = $row[$_GET['rank_col']];
                            $good_rank = isRankWord($proposed_rank);

                            if($good_rank){

                                    // we can't find anything and we have auto_create on so we should just create a new name and be done with it.
                                    $canonical_name = implode(' ', $matches['name_parts']);
                                    $update = Name::createName($canonical_name, true, true);

                                    // if the  update has failed stop and display error results
                                    if(!$update->success || !isset($update->names[0])){
                                        
                                        echo "<pre>";
                                        print_r($update);
                                        echo "</pre>";

                                    }else{

                                        // ok the creation went OK. Let's get the name
                                        $name = $update->names[0];

                                        // and set some values
                                        $name->setAuthorsString($authors_string);
                                        $name->setRank($good_rank);
                                        $name->save();

                                        // other values will be set during the update phase.

                                        // now write the new WFO to the table.
                                        $wfo = $name->getPrescribedWfoId();
                                        $sql = "UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_wfo` = '$wfo' WHERE `rhakhis_pk` = {$row['rhakhis_pk']};";
                                        $mysqli->query($sql);

                                        // keep track of recently created names in session
                                        if(!isset($_SESSION['created_names'])) $_SESSION['created_names'] = serialize(array());
                                        $created_names = unserialize($_SESSION['created_names']);
                                        $created_names[$wfo] = $name->getFullNameString();
                                        $_SESSION['created_names'] = serialize($created_names);

                                        echo "<p><strong>Created Name: $wfo </strong> {$name->getFullNameString()}</p>";

                                    } // end name created OK

                            }else{
                                    echo "<p>Can't create name because of unrecognized rank value: \"$proposed_rank\"</p>";
                            }

                        }else{

                            echo "<p>Unattended mode so nothing to do.</p>";

                        }
                        
                    } // end homonyms not OK

                } // end unattended mode

            }
            echo "</div>";
            flush();
        }

        // we automatically 
        echo $auto_render_next_page;




    }



    function render_options_form($table) {
        global $mysqli;
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
            <th style="text-align: right;">Scientific Name Column:</th>
                <td>
                <select name="name_col">
                    <option value="~">~ Local ID Only ~</option>
                    <?php  render_column_options($table, null); ?>
                </select>
                </td>
                <td>This must be set.</td>
            </tr>
            <tr>
            <th style="text-align: right;">Authors Column:</th>
                <td>
                <select name="authors_col">
                    <option value="">~ Pick One ~</option>
                    <?php  render_column_options($table, null) ?>
                </select>
                </td>
                <td>This is optional but highly desireable.</td>
            </tr>
            <tr>
            <th style="text-align: right;">Local ID Column:</th>
                <td>
                <select name="name_id_col">
                    <option value="">~ Pick One ~</option>
                    <?php  render_column_options($table, null) ?>
                </select>
                </td>
                <td>This is only used if we know the dataset has been previously matched and the TEN IDs added.</td>
            </tr>
            <tr>
            <th style="text-align: right;">Local ID Kind:</th>
                <td>
                <select name="name_id_type">
                    <option value="">~ Pick One ~</option>
                    <?php  render_id_type_options(null) ?>
                </select>
                </td>
                <td>This must be set if the Local ID Column is set.</td>
            </tr>
            <tr>
            <tr>
            <th style="text-align: right;">Local ID Prefix:</th>
                <td>
                <input name="name_id_prefix" value="" />
                </td>
                <td>This is added to the front of the value in the local ID column to get the id that is stored in Rhakhis (see linking for explanation).</td>
            </tr>
            <tr>
            <th style="text-align: right;">Homonyms OK:</th>
                <td style="text-align: center;">
                <input type="checkbox" name="homonyms_ok" value="true" />
                </td>
                <td>Check to signify a lax approach to life.</td>
            </tr>
            <th style="text-align: right;" >Interactive Mode:</th>
                <td style="text-align: center;">
                <input type="checkbox" name="interactive_mode" value="true" />
                </td>
                <td>Check to get asked your opinion on all the ambiguous/unresolved names.</td>
            </tr>
            <th style="text-align: right;" >Auto Create Names:</th>
                <td style="text-align: center;">
                <input type="checkbox" name="auto_create" value="true" />
                <select name="rank_col">
                    <option value ="">~ Rank Column ~</option>
<?php
                    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
                    $cols = $response->fetch_all(MYSQLI_ASSOC);
                    foreach($cols as $col){
                        echo "<option value=\"{$col['Field']}\">{$col['Field']}</option>";
                    }
?>
                </select>
                </td>
                <td>
                    This will create new names for names that don't have duplicates or homonyms.
                    <br/>
                    It only works if you are not in interactive mode and homonyms OK is off - they are not OK when creating new names.
                    <br/>
                    You need to specify which column contains the rank for name creation. The name that is created will be minimal with name parts, authors and rank.
                    <br/>
                    If the column contains a value that isn't a recognized rank the name will not be created.
                    <br/>
                    You could check the rank values are OK before running this using the facility under the Nomenclature tab then use the rhakhis_rank column as the source for the rank name here.
                </td>
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
                            <li>One of the duplicates is selected based on ranking the names by nomenclatural status. valid > other > unknown > deprecated. If this still doesn't find an unambiguous match (e.g. there are two valid names) then no match is found. Otherwise WFO ID of pick is written to the table.</li>
                        </ol>
                </ol>
            </li>
            <li>Homonyms not OK: Any ambiguity will prevent WFO ID being written to the table.</li>
        </ol>
    </li>
    <li>In <strong>Unattended Mode</strong> any unresolved ambiguity is ignored and the next name processed.</li>
    <li>If no ambiguity is discovered in Unattended Mode and <strong>Auto Create Mode</strong> is selected then new names will be created - provided there are no duplicates or homonyms.</li>
    <li>If there are duplicates or homonyms then new names must be created through the Rhakhis UI not the bulk loader.</li>
    <li>In <strong>Interactive Mode:</strong> any unresolved ambiguity leads to the presentation of a choice screen.</li>
    <li>In interactive mode a list of suggestions may be supplied if no matches are found. This is based on a simple word stemming approach.</li>
    <li>If the scientific name column is set to "~ Local ID Only ~" then only the local ID fields are used to match and only match if they are unambiguous. All other fields are ignored.</li>
    
</ol>
<hr/>

<?php

} // end render_algorithm_description

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

    $name_parts = get_name_parts($nameString);

    $nameString = implode(' ', $name_parts);

    $matches['name_parts'] = $name_parts;

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
    $sql = "SELECT * FROM `names` WHERE `name_alpha` = '$nameString_sql' AND (`authors` != '$authorsString_sql' OR `authors` is null)";
    $response = $mysqli->query($sql);
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
    foreach($name_parts as $p){
        $fuzzParts[] = $mysqli->real_escape_string(substr($p, 0, -2)) . '%';
    }

    if(count($fuzzParts) == 3){
        $sql = "SELECT * FROM `names` AS n WHERE n.`name` like '$fuzzParts[2]' AND n.`species` like  '$fuzzParts[1]' AND n.`genus` like '$fuzzParts[0]'";
    }elseif(count($name_parts) == 2){
        $sql = "SELECT * FROM `names` AS n WHERE n.`name` like '$fuzzParts[1]' AND n.`genus` like '$fuzzParts[0]'";
    }else{
        $sql = "SELECT * FROM `names` AS n WHERE n.`name` like '$fuzzParts[0]'";
    }

    $sql .= " LIMIT 100";

    // lets do a fussy look see.
    $response = $mysqli->query($sql);
    if($mysqli->error){
        echo $sql;
        echo $mysqli->error;
        exit;
    }
    
    $matches['fuzzy'] = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();


    // well we didn't find anything!
    return $matches;

}

function get_name_parts($nameString){
    
    // clean up the name first
    $nameString = trim($nameString);

    // hybrid symbol gone
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

    return $newNameParts;
}

?>

