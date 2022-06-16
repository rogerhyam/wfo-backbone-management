
<?php 
require_once("../config.php");
require_once("../include/User.php");

require_once("../include/WfoDbObject.php");
require_once("../include/Name.php");
require_once("../include/Taxon.php");
require_once("../include/UpdateResponse.php");

require_once("../bulk/include/functions.php");


// only gods are allowed to see anything in the bulk loading place.
// even that it exists!

$user = unserialize( $_SESSION['user']);

if(!$user || $user->getRole() != 'god'){
    header('HTTP/1.0 403 Forbidden');
    echo "<p>You are not permitted here unless you are logged into Rhakhis and have the role of 'god'.</p>";
    echo "<p>Go away and come back later.</p>";
    exit;
}

$action = @$_REQUEST['action']; // use request because we may get posts as well for file uploads

if(!$action){
    echo "No action defined";
    exit;
}

require_once('../bulk/actions/' . $action . '.php');
