<?php

require_once('../config.php');

error_log("*** Calling logout");

error_log($_SESSION['user']);


session_unset();

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000"); // FIXME THIS SHOULD BE CONFIGURABLE
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, wfo_access_token');
echo JSON_encode(array('loggedOut' => true));

?>