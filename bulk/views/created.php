
<h2>Recently Created Names</h2>

<p>
    When a name is created a record is kept in the current session so you can get a list of what has just happened.
    This is a convenience and will go away when your PHP session expires.
    If the list gets too long you can <a href="index.php?action=clear_session_names">clear it by clicking here</a>.
</p>
<?php

if(isset($_SESSION['created_names'])){
    $created_names = unserialize($_SESSION['created_names']);
}else{
    $created_names = array();
}

$created_names = array_reverse($created_names);
echo "<h3>" . count($created_names) . " new names in session</h3>";

foreach ($created_names as $wfo => $name_string) {
    $uri = get_rhakhis_uri($wfo);
    echo "<p><a target=\"rhakhis\" href=\"$uri\">$wfo</a> $name_string</p>";
}


