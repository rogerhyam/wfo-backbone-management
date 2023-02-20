<div>
<strong>Kew Synchronization: </strong>
<a href="index.php?action=view&phase=kew&task=kew_ipni">IPNI</a>
|
<a href="index.php?action=view&phase=kew&task=kew_wcvp">WCVP (slow)</a>
</div>
<hr/>
<?php
    $task =  @$_GET['task'];
    if(!$task) $task = 'kew_ipni';
    require_once('../bulk/views/'. $task . ".php");
?>
