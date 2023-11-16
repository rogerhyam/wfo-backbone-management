<?php

// are they loading a list?
if(@$_POST['wfo_list']){
    
    $list = explode("\n", $_POST['wfo_list']);
    $wfo_list = array();
    foreach ($list as $wfo) {
        $wfo = trim($wfo);
        if(preg_match('/^wfo-[0-9]{10}$/', $wfo)) $wfo_list[] = $wfo;
    }

    // put it in the session for later
    $_SESSION['reduplication_list'] = $wfo_list;

    // if we have something then use the first one.
    if($wfo_list){
        $wfo = $wfo_list[0];
    }else{
        $wfo = null;
    }

}else{
    $wfo = @$_REQUEST['wfo'];
}

if($wfo) $name = Name::getName($wfo);
else $name = null;


// do the work if we are passed something to resurrect
if(@$_POST['lazarus'] && $name){

    $lazarus_wfo = $_POST['lazarus'];
    $lazarus_authors = $_POST['authors'];
    $lazarus_citation = $_POST['citation_micro'];
    
    // OK - we have all the stuff we need. Let's do the business.
    
    // check lazarus hasn't already been saved
    $lazarus = Name::getName($lazarus_wfo);
    if($lazarus->getPrescribedWfoId() == $lazarus_wfo){
        $rhakhis_uri = get_rhakhis_uri($lazarus_wfo);
        echo "<p><strong>Previously saved:</strong> <a target=\"rhakhis\" href=\"{$rhakhis_uri}\">{$lazarus_wfo}</a></p>";
    }else{
        // take the wfo off the old name or we won't be able to create the new one
        $name->removeIdentifier($lazarus_wfo, 'wfo');

        // make a new name
        $lazarus = Name::getName(-1);
        $lazarus->setPrescribedWfoId($lazarus_wfo);
        $lazarus->setRank($name->getRank());
        $lazarus->setNameString($name->getNameString());
        $lazarus->setGenusString($name->getGenusString());
        $lazarus->setSpeciesString($name->getSpeciesString());
        $lazarus->setAuthorsString($lazarus_authors);
        $lazarus->setCitationMicro($lazarus_citation);
        $lazarus->setYear($name->getYear());
        $lazarus->setStatus($name->getStatus());
        $response = $lazarus->save();

        // move the identifiers
        if(@$_POST['identifiers']){
            foreach ($_POST['identifiers'] as $iw) {
                $id = json_decode(base64_decode($iw));
                $name->removeIdentifier($id->value, $id->kind);
                $lazarus->addIdentifier($id->value, $id->kind);
            }
        }

        // move the references
        if(@$_POST['references']){
            foreach ($_POST['references'] as $rw) {
                $ref = json_decode(base64_decode($rw));
                $reference = Reference::getReference($ref->id);
                $name->removeReference($reference);
                $lazarus->addReference($reference, $ref->comment);
            }
        }

        // tell them about it
        if($response->success){
            $rhakhis_uri = get_rhakhis_uri($lazarus_wfo);
            echo "<p><strong>Resurrected:</strong> <a target=\"rhakhis\" href=\"{$rhakhis_uri}\">{$lazarus_wfo}</a></p>";
        }else{
            echo "<pre>";
            print_r($response);
            echo "</pre>";
        }
    }
    
}

?>

<form action="index.php" method="GET" style="float: right;">
    <input type="hidden" name="action" value="view" />
    <input type="hidden" name="phase" value="duplicates" />
    <input type="hidden" name="task" value="reduplication" />
    <input type="text" name="wfo" value="<?php echo $wfo ?>" size="14" placeholder="wfo-0000000000"
        onfocus="this.select()" />
    <input type="submit" value="Fetch Name" />
    <p>
        <?php
            $list = @$_SESSION['reduplication_list'];
            if($list){
                $total = count($list);
                $index = array_search($wfo, $list);
                if($index > 0){
                    echo "<a href=\"index.php?action=view&phase=duplicates&task=reduplication&wfo={$list[$index-1]}\">&lt; Previous</a>";
                }else{
                    echo "&lt; Previous";
                }
                $place = $index + 1;
                echo " | $place of $total | ";
                if($index < $total-1){
                    echo "<a href=\"index.php?action=view&phase=duplicates&task=reduplication&wfo={$list[$index+1]}\">Next &gt;</a>";
                }else{
                    echo "Next &gt;";
                }
            }

?>
    </p>
</form>

<h2>Reduplication</h2>
<p style="max-width: 800px">Here we facilitate the resurrection of name records that have previously been deduplicated.
    Use the box on the right to fetch a name by its WFO ID or by a deduplicated ID.
</p>

<?php 
    if(!$name){

        echo '<h3>No name selected.</h3>';
        echo '<p>You can load a list of WFO IDs (one per line) to work through using the box below.</p>';
        echo '<form action="index.php?action=view&phase=duplicates&task=reduplication" method="POST" style="padding-left: 2em;">';
        echo '<textarea name="wfo_list" rows="30" cols="30" placeholder="Paste a list of WFO IDs here.">';
        $list = @$_SESSION['reduplication_list'];
        if($list){
            foreach($list as $item){
                echo "$item\n";
            }
        }
        echo '</textarea>';
        echo '<br/><input type="submit" value="Load List" />';
        echo '</form>';

    }else{
        
        $wfo = $name->getPrescribedWfoId();
        $identifiers = $name->getIdentifiers();
        $ref_usages = $name->getReferences();

        // get the extra wfos
        $duplication_ids = array(); 
        foreach ($identifiers as $id) {
            if($id->getKind() != 'wfo') continue;
            $duplication_ids = $id->getValues();
            unset($duplication_ids[array_search($wfo, $duplication_ids)]);
        }

        echo "<form action=\"index.php?action=view&phase=duplicates&task=reduplication\" method=\"POST\" >";
        echo "<input type=\"hidden\" name=\"wfo\" value=\"{$wfo}\" />";
        $rhakhis_uri = get_rhakhis_uri($wfo);
        echo "<h3><a href=\"$rhakhis_uri\" target=\"rhakhis\">{$wfo}</a>: {$name->getFullNameString()}</h3>";
        echo "<table>";

        // pick the wfo to resurrect
        echo "<tr>";
        echo "<th style=\"text-align: right;\">Deduplication WFO IDs</th>";
        echo "<td>Pick an id to bring back from the dead.</td>";
        echo "<td>";
        echo "<select name=\"lazarus\">";
        if(count($duplication_ids) == 0){
            echo "<option>~ No IDs to resurrect. ~</option>";
        }else{
            foreach ($duplication_ids as $id) {
                echo "<option value=\"$id\">$id</option>";
            }
        }
        echo "</select>";
        echo "</td>";
        echo "</tr>";

        // new author string
        echo "<tr>";
        echo "<th style=\"text-align: right;\">Author String</th>";
        echo "<td>Author string for resurrected record (should be different).</td>";
        echo "<td>";
        echo "<input type=\"text\" name=\"authors\" size=\"50\"  value=\"{$name->getAuthorsString()}\"/>";
        echo "</td>";
        echo "</tr>";
        
        // new micro citation string
        echo "<tr>";
        echo "<th style=\"text-align: right;\">Micro Citation</th>";
        echo "<td>Citation for resurrected record (should be different).</td>";
        echo "<td>";
        echo "<input type=\"text\" name=\"citation_micro\" size=\"50\" value=\"{$name->getCitationMicro()}\"/>";
        echo "</td>";
        echo "</tr>";

        // Things to move
        echo "<tr>";
        echo "<th colspan=\"3\">Check the things to move to the resurrected name record.</th>";
        echo "</tr>";


        // what IDs to move
        foreach ($identifiers as $id) {
            if($id->getKind() == 'wfo') continue; // no wfo ids obviously
            if(preg_match('/^rhakhis_.+/', $id->getKind())) continue; // no rhakhis ids
            foreach ($id->getValues() as $value) {
                $json = json_encode((object)array('kind' => $id->getKind(), 'value' => $value));
                $json_wrapped = base64_encode($json);
                echo "<tr>";
                echo "<th style=\"text-align: right;\">Identifier</th>";
                echo "<td><strong>{$id->getKind()}:</strong> {$value}</td>";
                echo "<td style=\"text-align: center;\">";
                echo "<input type=\"checkbox\" name=\"identifiers[]\" value=\"$json_wrapped\" />";
                echo "</td>";
                echo "</tr>";
            }
        }

        // what references to move
        foreach ($ref_usages as $ru) {
            $json = json_encode((object)array('id' => $ru->reference->getId(), 'comment' =>$ru->comment));
            $json_wrapped = base64_encode($json);
            echo "<tr>";
            echo "<th style=\"text-align: right;\">Reference</th>";
            echo "<td>
                    <strong>{$ru->reference->getKind()}:</strong> 
                    <a href=\"{$ru->reference->getLinkUri()}\">{$ru->reference->getDisplayText()}</a>
                    <br/>
                    {$ru->comment}
                </td>";
            echo "<td style=\"text-align: center;\">";
            echo "<input type=\"checkbox\" name=\"references[]\" value=\"$json_wrapped\" />";
            echo "</td>";
            echo "</tr>";
        }


        // submit row
        echo "<tr>";
        echo "<td colspan=\"3\" style=\"text-align: right;\">";
        $disabled = $duplication_ids ? '' : 'disabled';
        echo "<input type=\"submit\" value=\"Come back, all is forgiven!\" $disabled />";
        echo "</td>";
        echo "</tr>";
        
        echo "</table>";

        echo "</form>";



    }
?>