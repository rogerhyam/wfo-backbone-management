<?php
    require_once('../include/NamePlacer.php');
?>
<div style="width: 1000px">
    <h3>Remove Basionyms</h3>
    <p>
        This tool will remove the basionym link from the list of WFO IDs provided.
    </p>
    <?php

    if(@$_POST['remove_list']){
 
        $list = explode("\n", $_POST['remove_list']);
        $list = array_filter(array_map('trim', $list));   

        echo "<ul>";
        foreach($list as $wfo){
            echo "<li>";
            $name = Name::getName($wfo);
            echo "<strong>{$name->getPrescribedWfoId()}: </strong> {$name->getFullNameString()} ";
            $basionym = $name->getBasionym();
            if($basionym){
                echo "<br/>has basionym: <strong>{$basionym->getPrescribedWfoId()}: </strong>  {$basionym->getFullNameString()}";
                $name->setBasionym(null);
                $name->save();
                echo " =&gt; <strong>Removed</strong>";
            }else{
                echo " no basionym present!";
            }
            echo "</li>";
        }
        echo "</ul>";


    }// if post


?>

    <p>Cut and paste a list of IDs into the box below, one per line.</p>
    <p>
    <form method="POST" action="index.php?action=view&phase=taxonomy&task=taxonomy_remove_basionyms">
        <textarea name="remove_list"
            rows="10"></textarea>
        <br />
        <input name="confirm" value="" type="text" placeholder="Type REMOVE to continue." size="25"
            onkeyup="if(this.value == 'REMOVE') document.getElementById('submit_remove_basionyms').disabled = false; else document.getElementById('submit_remove_basionyms').disabled = true;" />
        <br />
        <input id="submit_remove_basionyms" type="submit" value="Remove Basionyms" disabled />
    </form>

    </p>
</div>