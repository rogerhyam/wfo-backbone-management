<h3>Impact Report</h3>

<p><img src="images/under_construction.gif" style="height: 80px;"/></p>
<p>Don't play with this yet. It is just here because I wanted to update code elsewhere.</p>

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

   
    <p>This will generate a impact report based on the root taxon <strong><?php echo $name->getPrescribedWfoId() ?> <?php echo $name->getFullNameString() ?> </strong>

    <p>It may take some time to run if it is a big group.</p>

    <form action="index.php" method="GET">
        <input type="hidden" name="action" value="impact_report" />
        <input type="hidden" name="root_taxon_wfo" value="<?php echo $name->getPrescribedWfoId() ?>" />
        <input type="hidden" name="table" value="<?php echo $table ?>" />
        <input type="submit" value="Generate Report" />
    </form>

<?php

    } 
?>


