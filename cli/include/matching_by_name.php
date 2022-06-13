
<div>
    <strong>By Name: </strong>
    <a href="index.php?phase=matching&action=by_name&mode=unattended&table=<?php echo $table ?>">Unattended<a>
    |
    <a href="index.php?phase=matching&action=by_name&mode=unattended&homonyms=ok&table=<?php echo $table ?>">Unattended (homonyms OK)<a>
    |
    <a href="index.php?phase=matching&action=by_name&mode=interactive&table=<?php echo $table ?>">Interactive<a>
</div>
<hr/>
<?php 

    // check that the columns are present in the table
    $sql = "SELECT count(*) FROM pragma_table_info('$table') where `name` = 'scientificName'";
    $response = $pdo->query($sql);
    if($response->fetchColumn() < 1){
       echo '<p style="color: red">Error: There is no column called scientificName so name matching is impossible.</p>';
    }

    $sql = "SELECT count(*) FROM pragma_table_info('$table') where `name` = 'scientificNameAuthorship'";
    $response = $pdo->query($sql);
    if($response->fetchColumn() < 1){
       echo '<p style="color: orange">Warning: There is no column called scientificNameAuthorship so name matching will be less accurate.</p>';
    }

    $sql = "SELECT count(*) FROM pragma_table_info('$table') where `name` = 'taxonRank'";
    $response = $pdo->query($sql);
    if($response->fetchColumn() < 1){
       echo '<p style="color: orange">Warning: Including a taxonRank column can improve accuracy.</p>';
    }

    $mode = @$_GET['mode'];
    if(!$mode){
        echo "<p><strong>Unattended</strong> will run through unmatched rows automatically adding unambiguous matches and skipping ambiguous matches. You probably want to run this first.</p>";
        echo "<p><strong>Interactive</strong> will run through unmatched rows automatically adding unambiguous matches and stop at ambiguous matches for you to make a choice.</p>";
    }else{

        $offset = $page_size * $page;
        echo "<p><strong>Offset: </strong>$offset | <strong>Page Size: </strong>$page_size</p>";

        $sql = "SELECT rowid, * FROM `$table`
            WHERE `rhakhis_wfo` is null
            AND   (`rhakhis_skip` != 1 OR `rhakhis_skip` is null) 
            LIMIT $page_size
            OFFSET $offset";

        //echo $sql; exit;

        $response = $pdo->query($sql);
        $rows = $response->fetchAll(PDO::FETCH_ASSOC);

        // if we have more than 0 rows we may need to render the next page
        if(count($rows) > 0){
            $homonyms = @$_GET['homonyms'];
            $uri ="index.php?phase=matching&action=by_name&mode=$mode&homonyms=$homonyms&table=$table&page=" . ($page + 1);
            $auto_render_next_page = "<script>window.location = \"$uri\"</script>";
        }else{
            $auto_render_next_page = "<p>Reached end of table</p>";
        }
        
        // work through the rows and render appropriately
        foreach($rows as $row){

            $name_string = $row['scientificName'];
            $authors_string = null;
            if(isset($row['scientificNameAuthorship'])) $authors_string = $row['scientificNameAuthorship'];
            $rank_string = null;
            if(isset($row['taxonRank'])) $rank_string = $row['taxonRank'];
            $matches = getMatches($name_string, $authors_string , $rank_string);
            $c = count($matches['candidates']);

            // FIXME ADD OTHER FIELDS IF WE HAVE THEM
            echo "<div><h3>{$row['rowid']}:&nbsp;$name_string $authors_string [$rank_string]</h3>";
                
            if($matches['unambiguous']){

                // just rendering a flag to say we are accepting this name
                echo "<p>";
                echo "<strong style=\"color: green\" >Match:</strong>";
                echo "</p>";
                render_name($matches['unambiguous']);
                $sql = "UPDATE `$table` SET `rhakhis_wfo` = '{$matches['unambiguous']['WFO_ID']}' WHERE `rowid` = {$row['rowid']} ";
                $pdo->exec($sql);

            }else{

                // if we are interactive we need to render a form.
                // if not we will just flag it in passing
                if($mode == 'interactive'){

                    // render a picker.
                    if(count($matches['candidates']) == 1){

                            // we have a good match but there are homonyms that may cause confusion

                            echo "<p>Good single candidate:</p>";
                            echo '<p style="padding-left: 2em">';
                           // render_name($matches['candidates'][0]);
                            render_name_set_wfo_link($matches['candidates'][0], $table, $row['rowid'], $page);
                            echo "</p>";


                            if(count($matches['homonyms']) > 0){
                                echo "<p>Homonyms exist:</p>";
                                foreach ($matches['homonyms'] as $homo) {
                                    echo '<p style="padding-left: 2em">';
                                    render_name_set_wfo_link($homo, $table, $row['rowid'], $page);
                                    echo "</p>";
                                }
                            }
                            

                    }elseif(count($matches['candidates']) == 0){

                        echo "<p>Sorry, no matches found.</p>";

                    }else{
                            // we have multiple candidates to choose between. 
                            echo "<p>Multiples candidates</p>";
                            foreach ($matches['candidates'] as $can) {
                                echo '<p style="padding-left: 2em">';
                                render_name_set_wfo_link($can, $table, $row['rowid'], $page);
                                echo "</p>";
                            }
                    }

                    // stop processing 
                    $auto_render_next_page = "<p>Select name or <a href=\"/actions.php?action=skip&table=$table&rowid={$row['rowid']}\">skip</a>.</p>";
                    break;

                }else{
                    
                    // in unattended mode and there is some doubt.
                    if($_GET['homonyms'] && $_GET['homonyms'] == 'ok'){
                        
                        // we have flagged we don't mind the existence of homonyms

                        if(count($matches['duplicates']) == 1){
                            // There is only one duplicate (weird I know) so we use it
                            echo "<p>";
                            echo "<strong style=\"color: orange\" >Match with homonyms:</strong>";
                            echo "</p>";
                            render_name($matches['duplicates'][0]);
                            $sql = "UPDATE `$table` SET `rhakhis_wfo` = '{$matches['duplicates'][0]['WFO_ID']}' WHERE `rowid` = {$row['rowid']} ";
                            $pdo->exec($sql);
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
                                switch ($dupe['nomenclaturalStatus']) {
                                    case 'deprecated':
                                        $bins[3][] = $dupe;
                                        break;
                                    case 'unknown':
                                        $bins[3][] = $dupe;
                                        break;
                                    case 'valid':
                                        $bins[3][] = $dupe;
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
                                echo "<p>";
                                echo "<strong style=\"color: orange\" >Match with homonyms:</strong>";
                                echo "</p>";
                                render_name($bin[0]);
                                $sql = "UPDATE `$table` SET `rhakhis_wfo` = '{$bin[0]['WFO_ID']}' WHERE `rowid` = {$row['rowid']} ";
                                $pdo->exec($sql);
                                break;
                            }

                        }elseif(count($matches['candidates']) >0){

                            // FIXME: look at the ex authors?
                            // valid author comes second..

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

// similar to method in server side application
function getMatches($nameString, $authorsString, $rankString){

    global $pdo;

    $matches = array(
        'unambiguous' => null, // an unambiguous matched name - candidates and homonyms will be empty if this is present
        'duplicates' => array(), // where there are more than one unambiguous name!
        'candidates' => array(), // possible names - may be only one which isn't unambiguous if homonyms are present
        'homonyms' => array() // possible homonyms - have the same name string but different author strings
    );

    // clean up the name first
    $nameString = trim($nameString);

    // FIXME - remove hybrid symbol

    // the name may include a rank abbreviation
    $nameParts = explode(' ', $nameString);
    $newNameParts = array();
    foreach($nameParts as $part){
        if(isRankWord($part)){
            // if we find a part that looks like a rank word
            // we don't add it to the new version of the name
            // but if we don't have a rank designation we use it for that.
            if(!$rankString) $rankString = $part;
        }else{
            $newNameParts[] = $part;
        }
    }
    $nameString = implode(' ', $newNameParts);

    // clean the authors - not much to do here
    $authorsString = trim($authorsString);

    // normalize the rank if we can.
    $rankString = isRankWord($part);

    // ready to start matching

    // perfect scores are 100% match of name and authors with no homonyms.
    $nameString_sql = $pdo->quote($nameString);
    $authorsString_sql = $pdo->quote($authorsString);

    $response = $pdo->query("SELECT * FROM `names` WHERE `scientificName` = $nameString_sql AND `scientificNameAuthorship` = $authorsString_sql LIMIT 100");
    $matches['duplicates'] = $response->fetchAll(PDO::FETCH_ASSOC);
    $response->closeCursor();

    // do we have any that match the name but not the author string. That would be a downgrade.
    $response = $pdo->query("SELECT * FROM `names` WHERE `scientificName` = $nameString_sql AND (`scientificNameAuthorship` != $authorsString_sql OR `scientificNameAuthorship` is null)");
    $matches['homonyms'] = $response->fetchAll(PDO::FETCH_ASSOC);
    $response->closeCursor();

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


/*
    // no name matches at all so fuzz up the names and try that.
    $fuzzed = '';
    foreach ($newNameParts as $part) {
        $fuzzed .= $this->fuzzWord($part);
        $fuzzed .= ' ';
    }
    $fuzzed = trim($fuzzed);

    $sql = "SELECT `id` FROM `names` WHERE `name_alpha` LIKE '$fuzzed' ORDER BY `name_alpha` LIMIT 100";
    $response = $mysqli->query($sql);
    error_log($sql);

    if($response->num_rows > 0){
        while($row = $response->fetch_assoc()){
            $matches->names[] = Name::getName($row['id']);
            $matches->distances[] = 3;
        }
        $response->close();
        return $matches;
    }
*/
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
