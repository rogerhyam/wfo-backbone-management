<div style="width: 800px">
<h2>Status Import</h2>
<p style="color: red;">Changes data in Rhakhis</p>
<p>
    This takes the values in the rhakhis_status column of the data table (created during status mapping) and updates Rhakhis with these values.
    It works through all the mapped rows, i.e. those with a value in rhakhis_wfo and skips those marked to be skipped.
    Values are overwritten using the following rules:
</p>
<ol>
    <li>New value must be compatible with role of name in Rhakhis at the moment. i.e. The accepted name of a taxon can't be changed to 'illegitimate' or 'unknown'.</li>
    <li>We can't move towards ignorance. i.e. If the status in Rhakhis is NOT 'unknown' it can't be changed to 'unknown'. That move has to be done manually.</li>
</ol>
<p>Little feedback is given. Only the number of names updated or not updated are reported. If updates are rejected because of taxonomy then that is sorted out in the taxonomy phase.</p>

<?php   

    if(@$_GET['active_run']){

        // if this is the first page we reset the session variable
        // that tracks the progress
        if($_GET['page'] == 0) $_SESSION['nomenclatural_status_import'] = array("same_value" => 0, "updated" => 0);
        process_page($table); // defined in nomenclature.php
    }else{
        render_form($table);
    }

function process_row($row, $table){

    global $mysqli;

    // if the row doesn't have a status we don't bother
    if(!$row['rhakhis_status']) return false; // return true because we just keep going

    // get the name for the row
    // the updateStatus will control for the controlled vocabulary
    // plus we are in a situation where the value should be correct.
    // but names don't know about taxonomy so we need to check for the taxon
    $name = Name::getName($row['rhakhis_wfo']);
    $taxon = Taxon::getTaxonForName($name);

    $new_status = $row['rhakhis_status'];
    $old_status = $name->getStatus();
    
    // nothing to do.
    if($old_status == $new_status){
        $_SESSION['nomenclatural_status_import']["same_value"]++;
//        echo "<p>{$name->getPrescribedWfoId()} : {$name->getFullNameString()} : {$name->getStatus()} :  $new_status </p>";
        return false;
    };

    // can't move towards ignorance
    if($new_status == 'unknown'){
        echo "<p>{$name->getPrescribedWfoId()} : {$name->getFullNameString()} : {$name->getStatus()} : Not updating to:  $new_status </p>";
        return false;
    };

    // ENUM('unknown', 'invalid', 'valid', 'illegitimate', 'superfluous', 'conserved', 'rejected', 'sanctioned', 'deprecated')

    if($taxon->getId()){
        // this name is placed in the taxonomy
        if($taxon->getAcceptedName() == $name){
            // this name is the accepted name of a taxon - it must be valid
            if($new_status == 'valid' || $new_status == 'conserved' || $new_status == 'sanctioned'){
                $name->updateStatus($new_status);
                $_SESSION['nomenclatural_status_import']["updated"]++;
                return false;
            }
        }else{
            // this name is placed as a synonym - it can be anything but deprecated
            if($new_status != 'deprecated'){
                $name->updateStatus($new_status);
                $_SESSION['nomenclatural_status_import']["updated"]++;
                return false;
            }
        }
    }else{
        // this name is unplaced it can have any status
        $name->updateStatus($new_status);
        $_SESSION['nomenclatural_status_import']["updated"]++;
        return false;
    }

    echo "<p>{$name->getPrescribedWfoId()} : {$name->getFullNameString()} : {$name->getStatus()} : Not updating to:  $new_status </p>";
    return false;

}


function render_form($table){

    global $mysqli;

    // get overall row counts
    $sql = "SELECT count(*) AS 'n' FROM `rhakhis_bulk`.`$table` WHERE`rhakhis_wfo` is not null and `rhakhis_status` is not null AND `rhakhis_skip` IS NULL OR `rhakhis_skip` < 1";
    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
    $total = number_format($rows[0]['n'], 0);
?>

<form action="index.php" method="GET">
    <input type="hidden" name="action" value="view" />
    <input type="hidden" name="phase" value="nomenclature" />
    <input type="hidden" name="task" value="nomenclature_status_import" />
    <input type="hidden" name="table" value="<?php echo $table ?>" />
    <input type="hidden" name="page_size" value="1000" />
    <input type="hidden" name="page" value="0" />
    <input type="hidden" name="active_run" value="true" />
<table>
    <tr>
        <th style="text-align: right;">Rows with WFO and Status Mapped:</th>
        <td><?php echo $total  ?></td>
    </tr>
<?php
    // we are rendering at the end of an active run
    if(@$_GET['active_run']){
?>
    <tr>
        <th style="text-align: right;">Names with same value:</th>
        <td><?php echo $_SESSION['nomenclatural_status_import']["same_value"] ?></td>
    </tr>
    <tr>
        <th style="text-align: right;">Names updated:</th>
        <td><?php echo $_SESSION['nomenclatural_status_import']["updated"] ?></td>
    </tr>
    <tr>
        <th style="text-align: right;">Updates rejected:</th>
        <td><?php echo $rows[0]['n'] - ( $_SESSION['nomenclatural_status_import']["updated"] + $_SESSION['nomenclatural_status_import']["same_value"] )?></td>
    </tr>
<?php
    }// active run
?>  
    <tr>
        <td style="text-align: right;" colspan="2"><input type="submit" value="Import Statuses" /></td>
    </tr>




</table>
</form>
<?php

} // render form

?>

