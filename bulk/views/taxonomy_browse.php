<h2>Taxonomy Browser</h2>
<p style="color: green;">Doesn't change data.</p>
<p> 
    This shows the taxonomy in the data table (as defined in the rhakhis_* fields).
    Click the WFO IDs to explore the classification. The names link to the names currently in Rhakhis.
</p>

<?php

    // how many rows all together
    $response = $mysqli->query("SELECT count(*) as n FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_wfo` IS NOT NULL");
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $matched_rows_count = $rows[0]['n'];
    $response->close();

    
    // get a list of root taxa - we always display this.
    $response = $mysqli->query("SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_parent` IS NULL AND `rhakhis_accepted` IS NULL AND `rhakhis_wfo` IS NOT NULL");
    $roots = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    echo "<strong>Root Taxa:</strong> ";

    if(count($roots)/$matched_rows_count > 0.8){
        echo "<p>Greater than 80% of matched names are potential root taxa! Have you done the internal mapping stage? Stopping here.</p>";
        exit;
    }

    $first = true;
    foreach ($roots as $root) {
        echo $first ? '':" | ";
        if($first) $first = false;
       render_label($root);
    }

    echo "<hr/>";

    // do we have a taxon to render

    if(@$_GET['taxon_wfo']){

        $response = $mysqli->query("SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_wfo` = '{$_GET['taxon_wfo']}'");
        $taxon_rows = $response->fetch_all(MYSQLI_ASSOC);
        $taxon_row = $taxon_rows[0];
        $response->close();

        echo "<strong>Path:</strong> ";
        $ancestors = array();
        get_ancestors($taxon_row, $ancestors, $table);
        $ancestors = array_reverse($ancestors);
        $ancestors[] = $taxon_row; // add self to end

        $indent_count = 0;
        for ($i=0; $i < count($ancestors); $i++) { 
            echo "<ul>";
            echo "<li>";
            render_label($ancestors[$i]);
            echo "</li>";

            // if we are on the last one 
            if($i == count($ancestors) -1){

                // get the basionyms
                $sql = "SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_basionym` = '{$taxon_row['rhakhis_wfo']}' OR `rhakhis_wfo` = '{$taxon_row['rhakhis_basionym']}'";
                $response = $mysqli->query($sql);
                $homotypics = $response->fetch_all(MYSQLI_ASSOC);
                $response->close();

                if(count($homotypics)){
                    echo "<h4>Homotypics</h4>";
                    echo "<ul>";
                    foreach($homotypics as $homo){
                        echo "<li>";
                        render_label($homo);
                        echo "</li>";
                    }
                    echo "</ul>";
                }

                // get the children
                $sql = "SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_parent` = '{$taxon_row['rhakhis_wfo']}'";
                $response = $mysqli->query($sql);
                $children = $response->fetch_all(MYSQLI_ASSOC);
                $response->close();

                
                if(count($children)){
                    echo "<h4>Subtaxa</h4>";
                    echo "<ul>";
                    foreach($children as $kid){
                        echo "<li>";
                        render_label($kid);
                        echo "</li>";
                    }
                    echo "</ul>";
                }

                // get the synonyms
                $sql = "SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_accepted` = '{$taxon_row['rhakhis_wfo']}'";
                $response = $mysqli->query($sql);
                $syns = $response->fetch_all(MYSQLI_ASSOC);
                $response->close();

               
                if(count($syns)){
                    echo "<h4>Synonyms</h4>";
                    echo "<ul>";
                    foreach($syns as $syn){
                        echo "<li>";
                        render_label($syn, false);
                        echo "</li>";
                    }
                    echo "</ul>";
                }

                // close off the lists
                echo str_repeat('</ul>', count($ancestors));

            } // last one

        }

    }


    function render_label($taxon_row, $as_link = true){
          
          if($as_link) echo "<a href=\"index.php?action=view&phase=taxonomy&task=taxonomy_browse&taxon_wfo={$taxon_row['rhakhis_wfo']}\">";
          else echo "<strong>";

          echo $taxon_row['rhakhis_wfo'];
          
          if($as_link) echo "</a>: ";
          else echo "</strong> ";
          
          $name = Name::getName($taxon_row['rhakhis_wfo']);

          $link_url = get_rhakhis_uri($taxon_row['rhakhis_wfo']);
          echo  "<a target=\"rhakhis\" href=\"$link_url\">{$name->getFullNameString()}</a>";
          
          echo " <strong>" . $name->getStatus() . "</strong> ";

          $rhakhis_taxon = Taxon::getTaxonForName($name);
          if($rhakhis_taxon->getId() > 0){

            if($taxon_row['rhakhis_wfo'] == $rhakhis_taxon->getAcceptedName()->getPrescribedWfoId()){
                // we are the accepted name of the taxon
                echo "accepted name in ";
                $rhakhis_parent = $rhakhis_taxon->getParent();
                if($rhakhis_parent){
                    $link_url = get_rhakhis_uri($rhakhis_parent->getAcceptedName()->getPrescribedWfoId());
                    echo  "<a target=\"rhakhis\" href=\"$link_url\">{$rhakhis_parent->getFullNameString()}</a>";
                }
            }else{
                // we are a synonym
                echo "synonym of ";
                $link_url = get_rhakhis_uri($rhakhis_taxon->getAcceptedName()->getPrescribedWfoId());
                echo  "<a target=\"rhakhis\" href=\"$link_url\">{$rhakhis_taxon->getFullNameString()}</a>";

            }
            
        
 
          }

          
    }

    function get_ancestors($taxon_row, &$ancestors, $table){
        
        global $mysqli;

        if($taxon_row['rhakhis_parent']){
            $sql = "SELECT * FROM `rhakhis_bulk`.`$table` WHERE `rhakhis_wfo` = '{$taxon_row['rhakhis_parent']}'";
            $response = $mysqli->query($sql);
            $parents = $response->fetch_all(MYSQLI_ASSOC);
            $response->close();
            $ancestors[] = $parents[0];
            get_ancestors($parents[0], $ancestors, $table);
        }


    }
?>

