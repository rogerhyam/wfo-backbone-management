<h3>Taxonomy Import</h3>

<div style="width: 1000px">

<p style="color: red;">Definitely changes data in Rhakhis!</p>

<p>This tool has the following effects:</p>
<ol>
    <li>It will remove everything below the root taxon from the taxonomy in Rhakhis. This includes all the descendants and their synonyms and but not the synonyms of the root taxon.</li>
    <li>It will work through the taxonomy in the data table, <strong><?php echo $table ?></strong>, from the root taxon and add back in the taxa and synonyms as specified in the table.</li>
    <li>Before each taxon is added the nomenclatural status and rank will be set in the associated name (if specified in <strong><?php echo $table ?></strong>).
        <ul>
            <li>If either the rank or the status are not compatible with the placement of the name in the taxonomy errors may be thrown and import stop.</li>
            <li>There should be no surprises as any clashes in rank/status will have been given in the Impact Report.</li>
        </ul>
    </li>
    <li>Basionym links are also established here even though they are strictly a nomenclatural construct.
        <ul>
            <li>If a name row in the data table has a rhakhis_basionym set then that name will be added as the basionym to the current name.</li>
            <li>This process is additive. If no basionym is specified in the data table but one is present in Rhakhis then the one in Rhakhis will not be removed. This must be done manually.</li>
        </ul>
    </li>
</ol>

<?php
    // if we haven't been passed a root taxon then we render a message.
    if(!@$_GET['root_taxon_wfo']){
?>
    <p><strong>You need to provide a root taxon WFO ID. Do that by clicking on a link under the browse tab.</strong></p>
    <p>You will then be given a checklist here to proceed with the import.</p>
<?php
    }else{
        $name = Name::getName($_GET['root_taxon_wfo']);
?>

<form action="index.php" method="GET">
    <input type="hidden" name="action" value="taxonomy_import_incremental" />
    <input type="hidden" name="table" value="<?php echo $table ?>" />
    <input type="hidden" name="root_taxon_wfo" value="<?php echo $_GET['root_taxon_wfo'] ?>" />

<table>
    <tr>
        <th style="text-align: center; color: white; background-color: black" colspan="2">Checklist</th>
    </tr>
    <tr>
        <th style="text-align: right">Root taxon is: <?php echo $name->getFullNameString(); ?></th>
        <td style="text-align: center"><input type="checkbox" id="root_taxon" onchange="enable_submit()"/></td>
    </tr>
    <tr>
        <th style="text-align: right" >Boundary taxon: </th>
        <td>
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

    </td>

    </tr>

    <tr>
        <th style="text-align: right">Impact report has been generated:</th>
        <td style="text-align: center"><input type="checkbox" id="impact_report" onchange="enable_submit()"/></td>
    </tr>
    <tr>
        <th style="text-align: right">No serious errors in impact report:</th>
        <td style="text-align: center"><input type="checkbox" id="impact_errors" onchange="enable_submit()" /></td>
    </tr>
    <tr>
        <th style="text-align: right">Table has not changed since impact report:</th>
        <td style="text-align: center"><input type="checkbox" id="no_changes" onchange="enable_submit()" /></td>
    </tr>
    <tr>
        <th style="text-align: right">I really want to do this:</th>
        <td style="text-align: center"><input type="checkbox" id="do_it"  onchange="enable_submit()" /></td>
    </tr>
    <tr>
        <td style="text-align: right" colspan="2"><input type="submit" id="import_submit" value="Import Taxonomy" disabled="true" /></td>
    </tr>
</table>


</form>

<?php
    } // have root_taxon_wfo
?>

</div>

<script>

function enable_submit(){

    if(
        document.getElementById("root_taxon").checked
        &&
        document.getElementById("impact_report").checked
        &&
        document.getElementById("impact_errors").checked
        &&
        document.getElementById("no_changes").checked
        &&
        document.getElementById("do_it").checked
    ){
        document.getElementById("import_submit").disabled = false;
    }else{
        document.getElementById("import_submit").disabled = true;
    }

}

</script>