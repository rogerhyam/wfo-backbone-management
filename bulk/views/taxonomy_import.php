<h3>Taxonomy Import</h3>

<div style="width: 1000px">

<p style="color: red;">Definitely changes data in Rhakhis!</p>

<p>This tool uses the "wave" approach to synchronizing the taxonomic trees, it will:</p>
<ol>
    <li>Generate a string representation of the path in Rhakhis for each name in the table and store it in rhakhis_r_path. </li>
    <li>Generate a string representation of the path in the table for each name in the table and store it in rhakhis_t_path. </li>
    <li>Select only the rows in the table where the rhakhis_t_path and rhakhis_r_path differ and sort them by longest to shortest.</li>
    <li>Work through the rows that differ.  If a change can be made in Rhakhis it will make it. If there is something blocking the move (e.g. an accepted name of a synonym isn't accepted yet) it will continue to the next path without doing anything.</li>
    <li>When it reaches the end of the list of differences it will stop, print out the number of changes and ask if you want to repeat the process - a second wave.</li>
    <li>This can be repeated until no further changes can be made.</li>
    <li>The number of waves will depend on the complexity of changes that are needed not the size of the data.</li>
    <li>Generating the paths in the first two steps can be time consuming if the data is large. Be patient after initiating a wave.</li>
    <li style="color: red;">Currently no bounding checks are made! The impact report will have said if any taxa are out of bounds. This tool will move taxa regardless. You did run the impact report didn't you!</li>
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
    <input type="hidden" name="action" value="taxonomy_import_wave" />
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
<!--
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
    -->

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
        <td style="text-align: right" colspan="2"><input type="submit" id="import_submit" value="Import Taxonomy" disabled="true" onclick="this.disabled = true; this.form.submit(); "/></td>
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