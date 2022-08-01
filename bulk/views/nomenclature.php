<?php
    $table = $_SESSION['selected_table'];

?>
<div>
<strong>Nomenclature: </strong>
<a href="index.php?action=view&phase=nomenclature&task=nomenclature_ranks">Ranks</a>
|
<a href="index.php?action=view&phase=nomenclature&task=nomenclature_statuses">Statuses</a>
|
<a href="index.php?action=view&phase=nomenclature&task=nomenclature_published_in">Published In</a>
</div>
<hr/>
<?php
    $task =  @$_GET['task'];
    if(!$task) $task = 'nomenclature_ranks';
    require_once('../bulk/views/'. $task . ".php");
?>
