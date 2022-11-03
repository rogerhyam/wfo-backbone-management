<h3>Impact Report</h3>

<div style="width: 1000px">

<p style="color: green;">Doesn't change data.</p>

<?php
    // if we haven't been passed a root taxon then we render a message.
    if(!@$_GET['root_taxon_wfo']){
?>
    <p><strong>You need to provide a root taxon WFO ID. Do that by clicking on a link under the browse tab.</strong></p>
    <p>This will run through the taxonomic tree in Rhakhis from a specified taxon down and report on the impact of replacing that taxon with the taxonomy present in the file.</p>
<?php
    }else{
        $name = Name::getName($_GET['root_taxon_wfo']);      
?>
    <p>This will generate an impact report based on the root taxon <strong><?php echo $name->getPrescribedWfoId() ?> <?php echo $name->getFullNameString() ?> </strong>
    It will run across the taxonomic tree in Rhakhis and include unplaced names <strong>below the genus level only</strong>. 
    
    It may take some time to run if it is a big group.</p>

    <p>The results will be written to a file called <strong><?php echo "{$name->getNameString()}_{$_GET['root_taxon_wfo']}_impact.csv" ?></strong> under the files tab from where you can download/delete it.</p>

    <p>The boundary taxon specifies where names can be moved from. Typically this will be the root but it could be above the root if, for example, a genus is being imported and it is OK to move taxa 
        from anywhere within the family</p>

    <p>This may take some time to run if it is a big group.</p>


    <form action="index.php" method="GET">
        <input type="hidden" name="action" value="taxonomy_impact_report" />
        <input type="hidden" name="root_taxon_wfo" value="<?php echo $name->getPrescribedWfoId() ?>" />
        <strong>Boundary Taxon: </strong>
        <select name="boundary_taxon_wfo">
<?php

        $taxon = Taxon::getTaxonForName($name); // must be accepted name from how we got here
        $ancestors = $taxon->getAncestors();
        array_unshift($ancestors, $taxon);
        foreach($ancestors as $anc){
            $fns = strip_tags($anc->getAcceptedName()->getFullNameString());
            $anc_wfo = $anc->getAcceptedName()->getPrescribedWfoId();
            echo "<option value=\"$anc_wfo\">$anc_wfo: $fns</option>";
        }

?>

        </select>
        <input type="hidden" name="table" value="<?php echo $table ?>" />
        <input type="submit" value="Generate Report" />
    </form>

<?php

    } 
?>

<h3>Meaning of columns in report</h3>

<ul>
    <li><strong>wfo:</strong> The prescribed WFO ID of the name concerned. Binds Rhakhis with the data table.</li>
    <li><strong>name:</strong> A handy string representation of the full name as it is in Rhakhis at the moment.</li>
    <li><strong>in bounds:</strong> YES/NO - If the name is unplaced or placed below the boundary taxon then it is available to be moved and in bounds. 
        <span style="color: red;">A NO means the name is placed outside the root taxon (in the bounds of another TEN) and needs to be un-placed in Rhakhis or removed from the table before the import is run.</span>
        Import will stop if an out of bounds name is encountered.
    </li>
    <li><strong>consequence:</strong> What will happen to the name as a result of the import:
        <ul>
            <li><strong>no change:</strong> The name maintains its current placement.</li>
            <li><strong>move: </strong> The name is placed somewhere else in the taxonomy - different parent or accepted name.</li>
            <li><strong>sunk: </strong> An accepted name becomes a synonym.</li>
            <li><strong>raised: </strong> A synonym becomes the name of an accepted taxon.</li>
            <li><strong>removed: </strong> A name that was placed as a synonym or accepted name becomes unplaced.</li>
            <li><strong>placed: </strong> A name that was unplaced in the Rhakhis taxonomy is placed within the new taxonomy.</li>
            <li><strong>missing: </strong> A name that occurs in Rhakhis (either placed in the taxonomy or detected as unplaced but likely within bounds) does not occur in the data table. It will not be changed during import.</li>
            <li><strong>root: </strong> Turning and turning in the widening gyre. The falcon cannot hear the falconer; Things fall apart; the centre cannot hold; Mere anarchy is loosed upon the world. This is the root taxon it can not change! (It's from a very famous Yeats poem).</li>
        </ul>
    </li>
    <li><strong>structure ok:</strong> The links between wfo, parent and accepted names have to be correct. The following checks are made:
        <ul>
            <li>row must only exist once</li>
            <li>can't have a accepted name and parent name</li>
            <li>accepted name can't be self</li>
            <li>parent name can't be self</li>
            <li>parent must exist once in table</li>
            <li>accepted must exist once in table</li>
        </ul>
        <span style="color: red;">Bad structure will break the import.</span>
    </li>
    <li><strong>rank ok:</strong> On import the rank in Rhakhis will be updated to the rank specified in the rhakhis_rank column of the data table if it is specified. If it isn't it use the current rank for the name in Rhakhis. After this process the rank needs to be correct for the new placement of the name. We can't have families as children of species. If the rank will be OK if it isn't then  you get a NO here and it needs fixing before import.</li>
    <li><strong>status ok:</strong> Same as for "rank ok" column but for status. e.g. checks that accepted names are valid, conserved or sanctioned.</li>
    <li><strong>parent name:</strong> OK if the proposed parent has the right genus or species name for the name parts of the name in this row. "FAIL: reason if it doesn't".</li>
    <li><strong>current rank:</strong> The rank in Rhakhis BEFORE import.</li>
    <li><strong>current status:</strong> The status in Rhakhis BEFORE import.</li>
    <li><strong>current placement:</strong>  The placement in Rhakhis BEFORE import. Value should be interpreted with the following two columns: "accepted within", "synonym of", "unplaced"</li>
    <li><strong>current placement wfo:</strong> The WFO of the current parent or accepted name in Rhakhis. Blank if name is currently unplaced in Rhakhis.</li>
    <li><strong>current placement name:</strong> The name of the current parent or accepted name in Rhakhis. Blank if name is currently unplaced in Rhakhis.</li>
    <li><strong>new placement:</strong> The placement in Rhakhis AFTER import. Value should be interpreted with the following two columns: "accepted within", "synonym of", "unplaced".</li>
    <li><strong>new placement wfo:</strong> The WFO of the new parent or accepted name in Rhakhis. Blank if name is currently unplaced in Rhakhis.</li>
    <li><strong>new placement name:</strong> The name of new current parent or accepted name in Rhakhis. Blank if name is currently unplaced in Rhakhis.</li>
    <li><strong>new rank:</strong> The rank in Rhakhis AFTER import.</li>
    <li><strong>new status:</strong> The status in Rhakhis AFTER import.</li>

</ul>

</div>


