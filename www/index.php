
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

$user = unserialize( @$_SESSION['user']);

if(!$user || $user->getRole() != 'god'){
    header('HTTP/1.0 403 Forbidden');
    echo "<p>You are not permitted here unless you are logged into Rhakhis and have the role of 'god'.</p>";
    echo "<p>Go away and come back later.</p>";
    exit;
}

$action = @$_REQUEST['action']; // use request because we may get posts as well for file uploads
if(!$action) $action = 'view';

require_once('../bulk/actions/' . $action . '.php');

// duplicate function again - same as in NameMatcher
function isRankWord($word){

    global $ranks_table;

    $word = strtolower($word);
    foreach($ranks_table as $rank => $rankInfo){

        // does it match the rank name
        if(strtolower($word) == $rank) return $rank;

        // does it match the official abbreviation
        if($word == strtolower($rankInfo['abbreviation'])) return $rank;

        // does it match one of the known alternatives
        foreach($rankInfo['aka'] as $aka){
            if($word == strtolower($aka)) return $rank;
        }

    }

    // no luck so it isn't a rank word we know of
    return false;

}


function get_rhakhis_uri($wfo){

    // the name created an link to view it in new tab.
    // this is a bit hacky on which server we are on
    switch ($_SERVER['SERVER_NAME']) {

        // in dev
        case 'localhost':
            $uri = 'http://localhost:3000/#' . $wfo;
            break;

        // on the sandbox
        case 'rhakhis.rbge.info':
            $uri = 'https://rhakhis.rbge.info/#' . $wfo;
            break;
    
        // live
        case 'list.worldfloraonline.org':
            $uri = 'https://list.worldfloraonline.org/rhakhis/ui/#' . $wfo;
            break;
        
        // unknown
        default:
            $uri = null;
            break;
    }

    return $uri;

}
