<?php
$_SESSION['selected_table'] = $_GET['table_name'];
// redirect back to tables page
header('Location: index.php?action=view&phase=tables');
