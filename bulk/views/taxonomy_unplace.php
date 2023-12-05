<?php
    require_once('../include/NamePlacer.php');
?>
<div style="width: 1000px">
    <h3>Unplace a List of Names</h3>
    <p>
        Use this tool to remove names from the classification based on their WFO IDs.
        It will refuse to remove taxa that have children or synonyms but keep them in the list for
        dealing with later.
    </p>
    <?php

    if(@$_POST['unplace_list']){
 
        $list = explode("\n", $_POST['unplace_list']);
        $list = array_filter(array_map('trim', $list));   

        foreach($list as $wfo){

            if(!preg_match('/^wfo-[0-9]{10}$/', $wfo)){
                echo "<p><strong style=\"color: red;\" >$wfo</strong> is badly formed, ignoring.</p>";
                unset($list[array_search($wfo, $list)]);
                continue;
            }

            try{
                $placer = new NamePlacer($wfo, 'remove');
                $placer->updatePlacement(null);
                echo "<p><strong>$wfo</strong>: Unplaced</p>";
                unset($list[array_search($wfo, $list)]);
            }catch(Exception $e){
                echo "<p><strong>$wfo</strong>: ". $e->getMessage() . "</p>";
                $name = Name::getName($wfo);
                $taxon = Taxon::getTaxonForName($name);
                echo "<p style=\"margin-left: 2em;\">" . $name->getFullNameString() . "</p>";
                if($taxon->getId()){
                    echo "<p style=\"margin-left: 2em;\">has ". count($taxon->getChildren())  ." children.</p>";
                    echo "<p style=\"margin-left: 2em;\">has ". count($taxon->getSynonyms())  ." synonyms.</p>";
                }else{
                    echo "<p style=\"margin-left: 2em;\">Already unplaced.</p>";
                    unset($list[array_search($wfo, $list)]);
                }
            }

        }

        // put it back in the session so they can see the partially done list
        $_SESSION['taxon_unplace'] = $list;

    }// if post


?>

    <p>Cut and paste a list of IDs into the box below, one per line.</p>
    <p>
    <form method="POST" action="index.php?action=view&phase=taxonomy&task=taxonomy_unplace">
        <textarea name="unplace_list"
            rows="10"><?php if(@$_SESSION['taxon_unplace']){ echo implode("\n", $_SESSION['taxon_unplace']); } ?></textarea>
        <br />
        <input name="confirm" value="" type="text" placeholder="Type UNPLACE to continue." size="25"
            onkeyup="if(this.value == 'UNPLACE') document.getElementById('submit_unplace').disabled = false; else document.getElementById('submit_unplace').disabled = true;" />
        <br />
        <input id="submit_unplace" type="submit" value="Unplace Names" disabled />
    </form>

    </p>
</div>