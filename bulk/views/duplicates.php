<div>
    <strong>Duplicates: </strong>
    <a href="index.php?action=view&phase=duplicates&task=deduplication">Deduplicate</a>
    |
    <a href="index.php?action=view&phase=duplicates&task=reduplication">Reduplicate</a>
</div>
<hr />
<?php
    $task =  @$_GET['task'];
    if(!$task) $task = 'deduplication';
    require_once('../bulk/views/'. $task . ".php");
?>