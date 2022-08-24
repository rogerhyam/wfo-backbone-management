<?php
    $table = $_SESSION['selected_table'];

    /*

    Validity check.

    Run through each row.
        check the row IDs are unique.
        if it is a synonym check it's accepted name exists and is not, itself, a synonym.
        if it is an accepted name check we can get all the way to the root without coming across a synonym.
            store the root ids in the session.
    */


?>
<div>
<strong>Taxonomy: </strong>
<a href="index.php?action=view&phase=taxonomy&task=taxonomy_summary">Summary</a>
|
<a href="index.php?action=view&phase=taxonomy&task=taxonomy_internal">Internal Mapping</a>
|
<a href="index.php?action=view&phase=taxonomy&task=taxonomy_rhakhis">Rhakhis Consistency</a>
</div>
<hr/>
<?php
    $task =  @$_GET['task'];
    if(!$task) $task = 'taxonomy_summary';
    require_once('../bulk/views/'. $task . ".php");
?>
