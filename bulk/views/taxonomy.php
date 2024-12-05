<?php
    /*

    Validity check.

    Run through each row.
        check the row IDs are unique.
        if it is a synonym check it's accepted name exists and is not, itself, a synonym.
        if it is an accepted name check we can get all the way to the root without coming across a synonym.
            store the root ids in the session.
    */

    echo '<div>';
    echo '<strong>Taxonomy: </strong>';

    $table = @$_SESSION['selected_table'];
    if($table){
?>

<a href="index.php?action=view&phase=taxonomy&task=taxonomy_summary">Summary</a>
|
<a href="index.php?action=view&phase=taxonomy&task=taxonomy_internal">Mapping</a>
|
<a href="index.php?action=view&phase=taxonomy&task=taxonomy_browse">Browse</a>
|
<a href="index.php?action=view&phase=taxonomy&task=taxonomy_impact">Impact</a>
|
<a href="index.php?action=view&phase=taxonomy&task=taxonomy_import">Import</a>
|
<a href="index.php?action=view&phase=taxonomy&task=taxonomy_hybrids">Hybrids</a>
|
<a href="index.php?action=view&phase=taxonomy&task=taxonomy_basionyms">Basionyms</a>
|
<a href="index.php?action=view&phase=taxonomy&task=taxonomy_prescribed">Prescribed IDs</a>
|
<?php
    } // if table
?>
<a href="index.php?action=view&phase=taxonomy&task=taxonomy_unplace">Unplace</a>
|
<a href="index.php?action=view&phase=taxonomy&task=taxonomy_remove_basionyms">Remove Basionyms</a>
</div>
<hr />
<?php
    $table = @$_SESSION['selected_table'];
    $task =  @$_GET['task'];
    if(!$table && $task != 'taxonomy_unplace'){
        echo '<p style="color: red;">You need to select a table before you can do much more here.</p>';
    }else{
        $task =  @$_GET['task'];
        if(!$task) $task = 'taxonomy_summary';
        require_once('../bulk/views/'. $task . ".php");
    }


?>