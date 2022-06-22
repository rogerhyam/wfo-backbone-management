<div>
<strong>Tables: </strong>
<a href="index.php?action=view&phase=tables&task=tables_summary">Summary</a>
|
<a href="index.php?action=view&phase=tables&task=tables_peek">Peek Active Table</a>
</div>
<hr/>
<?php
    $task =  @$_GET['task'];
    if(!$task) $task = 'tables_summary';
    require_once('../bulk/views/'. $task . ".php");
?>