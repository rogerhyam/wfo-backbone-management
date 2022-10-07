<h3>Hybrid Flagging</h3>

<p style="color: red;">Changes data in Rhakhis</p>

<?php


// written into the name comment if the name is unplaced or a synonym
$comment_text = "Name has been used for hybrid taxon.";

if(@$_GET['hybrid_wfo']){
    set_hybrids($_GET['hybrid_wfo'], $comment_text);
}else{
    if(@$_GET['name_column']){
        display_hybrids_table($table, $comment_text);
    }else{
        display_column_picker($table);
    }
}

function display_column_picker($table){
    global $mysqli;
?>
<p>Pick the column in the data table that contains the name with a hybrid symbol in it.</p>
<form method="GET" action="index.php">
    <input type="hidden" name="phase" value="taxonomy" />
    <input type="hidden" name="task" value="taxonomy_hybrids" />
    <select name="name_column">
        <option value="">~ select name column ~</option>
<?php
    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
    $cols = $response->fetch_all(MYSQLI_ASSOC);
    foreach($cols as $col){
        $selected = @$_GET['name_column'] == $col['Field'] ? 'selected' : '';
        echo "<option $selected value=\"{$col['Field']}\">{$col['Field']}</option>";
    }
?>
   </select>
   <input type="submit" value="Show list of potential hybrids."/>
</form>
<p>n.b. This is designed to work with "a few hundred" hybrid names and <strong>may</strong> fail if there are "a few thousand" hybrids in one table.</p>
<?php

}

function display_hybrids_table($table, $comment_text){
    global $mysqli;

    // U+00D7 = multiplication sign
    // U+2715 ✕ MULTIPLICATION X
    // U+2A09 ⨉ N-ARY TIMES OPERATOR

    // do a trick to get the different characters that may be used as hybrid symbols
    $json = '["\u00D7","\u2715","\u2A09"]';
    $hybrid_symbols = json_decode($json);

    $hybrid_rows = array();
    $response = $mysqli->query("SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_wfo` IS NOT NULL and (`rhakhis_skip` != 1 or `rhakhis_skip` is null)");
    while($row = $response->fetch_assoc()){
        $name = $row[$_GET['name_column']];

        // unicode assci symbols
        foreach ($hybrid_symbols as $symbol) {
            if(strpos($name, $symbol) !== false) $hybrid_rows[] = $row;
        }

        // use of the letter x
        if(preg_match('/ x /i', $name))$hybrid_rows[] = $row;
        if(preg_match('/^x /i', $name))$hybrid_rows[] = $row;

    }
    $response->close();

    if($hybrid_rows){
        echo "<p>Scroll down the list and check they should all either have hybrid flags (if they are taxon names) or have a note (if they are unplaced or synonyms).</p>";
         echo "<p><strong>This is additive!</strong> Unchecking a box will not remove a flag if it is already in Rhakhis. That must be done manually.</p>";
        echo "<form action=\"index.php\" method=\"GET\" >";
        echo "<input type=\"hidden\" name=\"action\" value=\"view\" />";
        echo "<input type=\"hidden\" name=\"phase\" value=\"taxonomy_hybrids\" />";
        echo "<table>";
        echo "<tr><th colspan=\"2\">Data File</th><th colspan=\"3\">Current Rhakhis</th><th>&nbsp;</th></tr>";
        echo "<tr><th>WFO</th><th>Name String</th><th>Role</th><th>Taxon Flag</th><th>Name Note</th><th>Should be Hybrid</th></tr>";
        foreach($hybrid_rows as $row){

            $name_string = $row[$_GET['name_column']];
            $wfo = $row['rhakhis_wfo'];
            $name_uri = get_rhakhis_uri($wfo);

            $name = Name::getName($wfo);
            if(!$name->getId()) continue; // missing names for dev
            $taxon = Taxon::getTaxonForName($name);

            $role = "Unplaced";
            if($taxon->getId()){
                if($taxon->getAcceptedName() == $name){
                    $role = "Accepted";
                    $hybrid_flag = $taxon->getHybridStatus() ? "Yes": "No";
                }else{
                    $role = "Synonym";
                    $hybrid_flag = "No";
                }
            }

            $hybrid_note = strpos($name->getComment(), $comment_text) !== false ? "Yes":"No"; 
            
            echo "<tr>";
            echo "<td><a href=\"$name_uri\" target=\"rhakhis\">$wfo</a></td>";
            echo "<td>$name_string</td>";
            echo "<td>$role</td>";
            echo "<td style=\"text-align: center;\">$hybrid_flag</td>";
            echo "<td style=\"text-align: center;\" >$hybrid_note</td>";
            echo "<td style=\"text-align: center;\"><input type=\"checkbox\" name=\"hybrid_wfo[]\" value=\"$wfo\" checked=\"true\"  /></td>";
            echo "</tr>";
        }

        echo "<tr><td colspan=\"6\" style=\"text-align: right;\"><input type=\"submit\" value=\"Update Rhakhis\" /></td></tr>";
        echo "</table>";
        

    }else{
        echo "<p>No potential hybrids found.</p>";
    }

}

function set_hybrids($hybrid_wfos, $comment_text){

    $updated_names = 0;
    $updated_taxa = 0;

    foreach($hybrid_wfos as $wfo){

        $name = Name::getName($wfo);
        if(!$name->getId()) continue; // missing names for dev
        $taxon = Taxon::getTaxonForName($name);

        $accepted_name = false;
        if($taxon->getId() && $taxon->getAcceptedName() == $name){
            $accepted_name = true;
        }
        
        if($accepted_name){
            if(!$taxon->getHybridStatus()){
                $taxon->setHybridStatus(true);
                $taxon->save();
                $updated_taxa++;
            }
        }else{
            // not an accepted name so it must have a note.
            if(strpos($name->getComment(), $comment_text) === false){
                $name->setComment($comment_text . "\n" .  $name->getComment());
                $name->save();
                $updated_names++;
            }
        }

    }

    echo "<p>Run complete. Updated $updated_names names and $updated_taxa taxa.</p>";

}



?>
