<h2>Fix spelling of taxon with children</h2>
<p>The main interface doesn't allow the spelling of a genus or species to be changed if it has children because their names have to agree.</p>
<p>This tool will change the spelling of a genus or species and the name parts of all the children.</p>

<?php
    if(!@$_GET['parent_wfo']){
        render_parent_wfo_form();
    }else{

        $parent_wfo = trim($_GET['parent_wfo']);

        if(!preg_match('/^wfo-[0-9]{10}$/', $parent_wfo)){
            echo '<p style="color:red;">Not a well formed WFO ID</p>';
            render_parent_wfo_form();
        }else{
            // we have a well formed one

            $parent_name = Name::getName($parent_wfo);
            $parent_taxon = Taxon::getTaxonForName($parent_name);

            if(!in_array($parent_name->getRank(), array('genus', 'species')) ){
                echo '<p style="color:red;">Not a genus or species</p>';
                render_parent_wfo_form();
            }elseif(!$parent_taxon->getId()){
                echo '<p style="color:red;">Not placed in taxonomy</p>';
                render_parent_wfo_form();
            }elseif($parent_taxon->getAcceptedName() != $parent_name){
                echo '<p style="color:red;">A synonym not and accepted name</p>';
                render_parent_wfo_form();
            }else{
                // at last we have something we can use

                // if we have been passed a new name the update before display
                if(@$_GET['new_name']) update_name($parent_taxon, $_GET['new_name']);

                echo "<h3>{$parent_name->getFullNameString()}</h3>";

                // form to change the name...
                echo '<form action="index.php" method="GET">';
                echo '<input type="hidden" name="action" value="view" />';
                echo '<input type="hidden" name="phase" value="nomenclature" />';
                echo '<input type="hidden" name="task" value="nomenclature_spelling"  />';
                echo '<input type="hidden" name="parent_wfo" value="'.@$_GET['parent_wfo'].'" placeholder="WFO ID of parent" />';
            
                echo '<input type="text" name="new_name" value="'. $parent_name->getNameString() .'" />';

                if($parent_name->getRank() == 'genus'){
                    // we are changing the genus part
                    echo '<input type="submit" value="Update Genus Name" />';
                }else{
                    // we are changing the species part
                    echo '<input type="submit" value="Update Species Name" />';
                }

                echo '</form>';

                // show what effect this will have
                echo "<h3>These names will be altered</h3>";
                render_children($parent_taxon);


            }





        }


    }


function update_name($parent_taxon, $new_name){

    $parent_taxon->getAcceptedName()->setNameString($new_name);
    $parent_taxon->getAcceptedName()->save();

    if($parent_taxon->getAcceptedName()->getRank() == 'genus'){
        update_children_genus_name($parent_taxon, $new_name);
    }else{
         update_children_species_name($parent_taxon, $new_name);
    }
    
}

function update_children_genus_name($parent_taxon, $new_genus_name){

    $kids = $parent_taxon->getChildren();

    if($kids){
        foreach($kids as $kid){
            $kid->getAcceptedName()->setGenusString($new_genus_name);
            $kid->getAcceptedName()->save();
            update_children_genus_name($kid, $new_genus_name);
        }
    }

}

function update_children_species_name($parent_taxon, $new_species_name){
    
    $kids = $parent_taxon->getChildren();

    if($kids){
        foreach($kids as $kid){
            $kid->getAcceptedName()->setSpeciesString($new_species_name);
            $kid->getAcceptedName()->save();
            update_children_species_name($kid, $new_species_name);
        }
    }

}

function render_children($parent_taxon){

    $kids = $parent_taxon->getChildren();

    if($kids){
        echo "<ul>";
        foreach($kids as $kid){
            echo "<li>";
            echo "<strong>{$kid->getAcceptedName()->getPrescribedWfoId()}: </strong>";
            echo $kid->getAcceptedName()->getFullNameString();
            render_children($kid);
            echo "</li>";
        }
        echo "</ul>";
    }


}

function render_parent_wfo_form(){

    echo "<p>You must provide a parent WFO ID to get started. This must be of an accepted genus or species.</p>";
    echo '<form action="index.php" method="GET">';
    echo '<input type="hidden" name="action" value="view" />';
    echo '<input type="hidden" name="phase" value="nomenclature" />';
    echo '<input type="hidden" name="task" value="nomenclature_spelling"  />';
    echo '<input type="text" name="parent_wfo" value="'.@$_GET['parent_wfo'].'" placeholder="WFO ID of parent" />';
    echo '<input type="submit" value="Fetch Parent" />';
    echo '</form>';

}


?>
