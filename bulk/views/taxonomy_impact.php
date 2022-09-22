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

    <p>The results will be written to a file called <strong><?php echo $table ?>_impact.csv</strong> under the files tab from where you can download/delete it.</p>

    <p>This may take some time to run if it is a big group.</p>


    <form action="index.php" method="GET">
        <input type="hidden" name="action" value="impact_report" />
        <input type="hidden" name="root_taxon_wfo" value="<?php echo $name->getPrescribedWfoId() ?>" />
        <input type="hidden" name="table" value="<?php echo $table ?>" />
        <input type="submit" value="Generate Report" />
    </form>

<?php

    } 
?>

<h3>Meaning of columns in report</h3>

<ul>

    <li><strong>in bounds:</strong> YES/NO - If the name is unplaced or placed below the root taxon then it is available to be moved and in bounds. 
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
            <li><strong>missing: </strong> A name that occurs in Rhakhis (either placed in the taxonomy or detected as unplaced but likely within bounds) does not occur in the data table. It will become unplaced.</li>
            <li><strong>root: </strong> Turning and turning in the widening gyre. The falcon cannot hear the falconer; Things fall apart; the centre cannot hold; Mere anarchy is loosed upon the world. This is the root taxon it can not change! (It's from a very famous Yeats poem).</li>
        </ul>
    </li>
    <li><strong>rank ok:</strong> On import the rank in Rhakhis will be updated to the rank specified in the rhakhis_rank column of the data table if it is specified. If it isn't it use the current rank for the name in Rhakhis. After this process the rank needs to be correct for the new placement of the name. We can't have families as children of species. If the rank will be OK if it isn't then  you get a NO here and it needs fixing before import.</li>
    <li><strong>status ok:</strong> Same as for "rank ok" column but for status. e.g. checks that accepted names are valid, conserved or sanctioned.</li>
    <li><strong>wfo:</strong> The prescribed WFO ID of the name concerned. Binds Rhakhis with the data table.</li>
    <li><strong>name:</strong> A handy string representation of the full name as it is in Rhakhis at the moment.</li>
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


