<?php

$number_ref_filters = 3;

?>

<h2>Examples</h2>
<p>This utility will help find example names/taxa that have certain combinations of attributes. Useful for developing
    talks and fixing bugs. The first 100 matching names are returned.</p>

<form action="index.php" method="GET">
    <input type="hidden" name="action" value="view" />
    <input type="hidden" name="phase" value="examples" />
    <input type="hidden" name="run" value="true" />


    <table>

        <tr>
            <th style="text-align: right;">Name starts</th>
            <td>
                <input type="text" name="name_alpha_starts" value="<?php echo @$_REQUEST['name_alpha_starts'] ?>" />
            <td>
                The name_alpha field (no rank or authors) starts with.
            </td>
        </tr>

        <tr>
            <th style="text-align: right;">Name matches</th>
            <td>
                <input type="text" name="name_alpha_matches" value="<?php echo @$_REQUEST['name_alpha_matches'] ?>" />
            <td>
                Applies <a target="doc" href="https://dev.mysql.com/doc/refman/8.4/en/regexp.html#regexp-syntax">regular
                    expression</a> to the name_alpha field (no rank or authors). SLOW!
            </td>
        </tr>

        <tr>
            <th style="text-align: right;">Rank</th>
            <td>
                <select name="rank">
                    <option value="">~ any ~</option>
                    <?php
                foreach(array_keys($ranks_table) as $rank){
                    $selected = @$_REQUEST['rank'] == $rank ? 'selected' : '';
                    echo "<option value=\"$rank\" $selected>$rank</optoin>";
                }
                ?>
                </select>
            <td>
                Pick a rank or none.
            </td>
        </tr>

        <tr>
            <th style="text-align: right;">Authors start</th>
            <td>
                <input type="text" name="authors_start" value="<?php echo @$_REQUEST['authors_start'] ?>" />
            <td>
                The authors string starts with.
            </td>
        </tr>

        <tr>
            <th style="text-align: right;">Authors match</th>
            <td>
                <input type="text" name="authors_match" value="<?php echo @$_REQUEST['authors_match'] ?>" />
            <td>
                Applies a <a target="doc"
                    href="https://dev.mysql.com/doc/refman/8.4/en/regexp.html#regexp-syntax">regular expression</a> to
                the authors field. SLOW!
            </td>
        </tr>

        <tr>
            <th style="text-align: right;">Status</th>
            <td>
                <select name="status">
                    <option value="">~ any ~</option>
                    <?php
                foreach(get_status_enumeration() as $status){
                    $selected = @$_REQUEST['status'] == $status ? 'selected' : '';
                    echo "<option value=\"$status\" $selected>$status</optoin>";
                }
                ?>
                </select>
            <td>
                Pick a status or none.
            </td>
        </tr>

        <tr>
            <th style="text-align: right;">Citation matches</th>
            <td>
                <input type="text" name="citation_match" value="<?php echo @$_REQUEST['citation_match'] ?>" />
            <td>
                Applies a <a target="doc"
                    href="https://dev.mysql.com/doc/refman/8.4/en/regexp.html#regexp-syntax">regular expression</a> to
                the micro citation field.
            </td>
        </tr>

        <tr>
            <th style="text-align: right;">Year</th>
            <td>

                <select name="year_operator">
                    <option value="eq" <?php echo @$_REQUEST['year_operator'] == 'eq' ? "selected" : ""; ?>>equals
                    </option>
                    <option value="lt" <?php echo @$_REQUEST['year_operator'] == 'lt' ? "selected" : ""; ?>>less than
                    </option>
                    <option value="gt" <?php echo @$_REQUEST['year_operator'] == 'gt' ? "selected" : ""; ?>>greater than
                    </option>
                </select>
                <input type="int" size="4" name="year" value="<?php echo @$_REQUEST['year'] ?>" />
            <td>
                Year of publication - if set. Won't return names where year is null.
            </td>
        </tr>

        <tr>
            <th style="text-align: right;">Role</th>
            <td>

                <select name="role">
                    <option value="">~ any ~</option>
                    <option value="accepted" <?php echo @$_REQUEST['role'] == 'accepted' ? "selected" : ""; ?>>accepted
                    </option>
                    <option value="synonym" <?php echo @$_REQUEST['role'] == 'synonym' ? "selected" : ""; ?>>synonym
                    </option>
                    <option value="unplaced" <?php echo @$_REQUEST['role'] == 'unplaced' ? "selected" : ""; ?>>unplaced
                    </option>
                </select>
            <td>
                Is it placed in the taxonomy?
            </td>
        </tr>

        <?php
            for($i = 0; $i < $number_ref_filters; $i++){
                render_ref_filter_fields($i);
            }
        ?>


        <tr>
            <td colspan="3" style="text-align: right">
                <input type="button" value="Clear"
                    onclick="window.location.href = 'index.php?action=view&phase=examples';" />
                <input type="submit" value="Get Examples" />
            </td>
        </tr>


    </table>

    <hr />

    <?php
    
    if(@$_REQUEST['run'] == 'true'){

        // let's build some SQL

        $sql = "SELECT distinct(n.id) \n FROM `names` AS n";


        // queries that involve joins

        if(@$_REQUEST['role']){
            // are they in the taxonomy
            if($_REQUEST['role'] == 'unplaced'){
            
                // they must not be in the taxon_names table
                // we left join here and add a filter to the where clause.
                $sql .= "\n LEFT JOIN `taxon_names` as tn ON n.id = tn.name_id";    

            }else{

                // placed names
                // need to join to the taxon names - they must be in this table
                $sql .= "\n JOIN `taxon_names` as tn ON n.id = tn.name_id ";
                

                if($_REQUEST['role'] == 'accepted'){
                    // must be in the taxa table as taxon
                    $sql .= "\n JOIN `taxa` as t ON t.taxon_name_id = tn.id";
                }else{
                    // not in the taxon table as accepted name
                    $sql .= "\n LEFT JOIN `taxa` as t ON t.taxon_name_id = tn.id AND t.id IS NULL";
                }


            }
        }

        // References
        for($i = 0; $i < $number_ref_filters; $i++){

            $kind = @$_REQUEST["ref_filter_kind_$i"];
            $role = @$_REQUEST["ref_filter_role_$i"];

            // they have to have set at least a kind or a role
            if(!$kind && !$role) continue;

            $sql .= "\n JOIN `name_references` as nrefs_$i ON n.id = nrefs_$i.name_id";

            // restrict join to role if there is one set
            if($role) $sql .= " AND nrefs_$i.`role` = '$role'";

            // also restrict by comment content
            if(@$_REQUEST["ref_filter_comment_$i"]){
                $comment_safe = $mysqli->real_escape_string($_REQUEST["ref_filter_comment_$i"]);
                $sql .=  " AND nrefs_$i.`comment` LIKE '%comment_safe%'";
            } 

            // join to the references table so we can add other filters
            $sql .= "\n JOIN `references` as refs_$i ON nrefs_$i.reference_id = refs_$i.id";

            // if we restrict to a kind?
            if($kind) $sql .=  " AND refs_$i.`kind` = '$kind'";

            // if we restrict to an image?
            if(@$_REQUEST["ref_filter_image_$i"]) $sql .=  " AND refs_$i.`thumbnail_uri` IS NOT NULL";

            // restrict label content
            if(@$_REQUEST["ref_filter_label_$i"]){
                $label_safe = $mysqli->real_escape_string($_REQUEST["ref_filter_label_$i"]);
                $sql .=  " AND refs_$i.`display_text` LIKE '%$label_safe%'";
            } 

            // restrict uri content
            if(@$_REQUEST["ref_filter_uri_$i"]){
                $uri_safe = $mysqli->real_escape_string($_REQUEST["ref_filter_uri_$i"]);
                $sql .=  " AND refs_$i.`link_uri` LIKE '%$uri_safe%'";
            } 
            
        }
   
        // filters on main query

        $sql .= "\n WHERE 1 = 1 ";

        // works with join set up above
        if(@$_REQUEST['role'] == 'unplaced'){
            $sql .= "AND tn.id is NULL";
        }
        
        // name_alpha starts
        if(@$_REQUEST['name_alpha_starts']){
            $sql .=  "\n AND name_alpha LIKE '{$_REQUEST['name_alpha_starts']}%'";
        }

        // name_alpha matches
        if(@$_REQUEST['name_alpha_matches']){
            $sql .=  "\n AND name_alpha REGEXP '{$_REQUEST['name_alpha_matches']}'";
        }

        // rank
        if(@$_REQUEST['rank']) $sql .= "\n AND n.rank = '{$_REQUEST['rank']}'";

        // status
        if(@$_REQUEST['status']) $sql .= "\n AND n.status = '{$_REQUEST['status']}'";

        // name_alpha starts
        if(@$_REQUEST['authors_start']){
            $sql .=  "\n AND n.authors LIKE '{$_REQUEST['authors_start']}%'";
        }

        // authors_match
        if(@$_REQUEST['authors_match']){
            $sql .=  "\n AND n.authors REGEXP '{$_REQUEST['authors_match']}'";
        }

        if(@$_REQUEST['citation_match']){
            $sql .=  "\n AND n.citation_micro REGEXP '{$_REQUEST['citation_match']}'";
        }

        if(@$_REQUEST['year']){
            
            switch (@$_REQUEST['year_operator']) {
                case 'eq':
                    $sql .=  "\n AND n.`year` = {$_REQUEST['year']}";
                    break;
                case 'lt':
                    $sql .=  "\n AND n.`year` < {$_REQUEST['year']}";
                    break;
                case 'gt':
                    $sql .=  "\n AND n.`year` > {$_REQUEST['year']}";
                    break;
                default:
                    break;
            }

        }

        $sql .= "\n LIMIT 100";

        $response = $mysqli->query($sql);
        while($row = $response->fetch_assoc()){
            $name = Name::getName($row['id']);

            $uri = get_rhakhis_uri($name->getPrescribedWfoId());

            echo "<p><a target=\"rhakhis\" href=\"$uri\">{$name->getPrescribedWfoId()}</a> - {$name->getFullNameString()} - {$name->getCitationMicro()}</p>";
        }

        echo "<hr/>";
        echo "<h3>SQL Used</h3>";
        echo "<pre>$sql</pre>";
        echo "<hr/>";

    }else{
        echo "<p>Results appear here...</p>";
    }

?>


</form>

<?php

function get_status_enumeration(){

    global $mysqli;

    $result = $mysqli->query("SHOW COLUMNS FROM `names` LIKE 'status'");
    $row = $result->fetch_assoc();
    $type = $row['Type'];
    preg_match("/'(.*)'/i", $type, $matches);
    $vals = explode(',', $matches[1]);
    array_walk($vals, function(&$v){$v = str_replace("'", "", $v);});
    $result->close();

    return $vals;

}

function render_ref_filter_fields($i){
?>
<tr>
    <th style="text-align: right; vertical-align: top;">Ref Filter #<?php echo $i + 1 ?></th>
    <td>
        <table style="border: none;">
            <tr>
                <td colspan="2">
                    <select name="ref_filter_role_<?php echo $i ?>" style="width: 100%;">
                        <option value="">~ pick role ~</option>
                        <option value="nomenclatural"
                            <?php echo @$_REQUEST["ref_filter_role_$i"] == 'nomenclatural' ? 'selected' : ''; ?>>
                            Nomenclatural</option>
                        <option value="taxonomic"
                            <?php echo @$_REQUEST["ref_filter_role_$i"] == 'taxonomic' ? 'selected' : ''; ?>>
                            Taxonomic</option>
                        <option value="treatment"
                            <?php echo @$_REQUEST["ref_filter_role_$i"] == 'treatment' ? 'selected' : ''; ?>>
                            Treatment</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <select name="ref_filter_kind_<?php echo $i ?>" style="width: 100%;">
                        <option value="">~ pick kind ~</option>
                        <option value="literature"
                            <?php echo @$_REQUEST["ref_filter_kind_$i"] == 'literature' ? 'selected' : ''; ?>>Literature
                        </option>
                        <option value="person"
                            <?php echo @$_REQUEST["ref_filter_kind_$i"] == 'person' ? 'selected' : ''; ?>>Person
                        </option>
                        <option value="specimen"
                            <?php echo @$_REQUEST["ref_filter_kind_$i"] == 'specimen' ? 'selected' : ''; ?>>Specimen
                        </option>
                        <option value="database"
                            <?php echo @$_REQUEST["ref_filter_kind_$i"] == 'database' ? 'selected' : ''; ?>>Database
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th style="text-align: right;">
                    With image
                </th>
                <td>
                    <input type="checkbox" name="ref_filter_image_<?php echo $i ?>" value="true"
                        <?php echo @$_REQUEST["ref_filter_image_$i"] ? "checked" : ""; ?> />
                </td>
            </tr>
            <!-- Label -->
            <tr>
                <th style="text-align: right;">
                    Label contains:
                </th>
                <td>
                    <input type="text" name="ref_filter_label_<?php echo $i ?>"
                        value="<?php echo @$_REQUEST["ref_filter_label_$i"] ? $_REQUEST["ref_filter_label_$i"] : ""; ?>" />
                </td>
            </tr>

            <!-- URI -->
            <tr>
                <th style="text-align: right;">
                    URI contains:
                </th>
                <td>
                    <input type="text" name="ref_filter_uri_<?php echo $i ?>" true
                        value="<?php echo @$_REQUEST["ref_filter_uri_$i"] ? $_REQUEST["ref_filter_uri_$i"] : ""; ?>" />

                </td>
            </tr>

            <!-- Comments -->
            <tr>
                <th style="text-align: right;">
                    Comment contains:
                </th>
                <td>
                    <input type="text" name="ref_filter_comment_<?php echo $i ?>"
                        value="<?php echo @$_REQUEST["ref_filter_comment_$i"] ? $_REQUEST["ref_filter_comment_$i"] : ""; ?>" />
                </td>
            </tr>
        </table>
    <td style="text-align: left; vertical-align: top;">
        <p>Filter to only names that have references like this.</p>
        <p>You must select at least a role or a kind for the filter to become active.</p>
    </td>
</tr>

<?php
}

?>