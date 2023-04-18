<?php
$id = intval($_GET['new_user_id']);
$new_user = User::loadUserForDbId($id);
$_SESSION['user'] = serialize($new_user);
header('Location: index.php');
?>