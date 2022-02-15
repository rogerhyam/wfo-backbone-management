<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\EnumType;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Error\DebugFlag;

require_once('../config.php');
require_once('../include/GqlTypeRegister.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Taxon.php');
require_once('../include/Name.php');
require_once('../include/NameMatcher.php');
require_once('../include/NameMatches.php');
require_once('../include/Rank.php');
require_once('../include/UpdateResponse.php');
require_once('../include/NamePlacer.php');
require_once('../include/UnplacedFinder.php');
require_once('../include/BasionymFinder.php');
require_once('../include/Identifier.php');
require_once('../include/User.php');
require_once('../include/DownloadFile.php');

$typeReg = new TypeRegister();

$schema = new Schema([
    'query' => new ObjectType([
        'name' => 'Query',
        'description' => 
            "This is the WFO Taxonomic Backbone management API",
        'fields' => [
            'getUser' => [
                'type' => TypeRegister::userType(),
                'resolve' => function() {
                    return unserialize($_SESSION['user']);
                }
            ],
            'getNameForWfoId' => [
                'type' => TypeRegister::nameType(),
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => "A WFO ID associated with this name. Does not have to be the prescribed WFO ID but could be a deduplicated one.",
                        'required' => true
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    // this method not used for creation or db retrieval so force wfo id
                    if(preg_match('/wfo-[0-9]{10}/', $args['id'])){
                        return Name::getName($args['id']);
                    }else{
                        return null;
                    }
                }
            ],
            'getTaxonById' => [
                'type' => TypeRegister::taxonType(),
                'args' => [
                    'id' => [
                        'type' => Type::int(),
                        'description' => "The database id of the taxon",
                        'required' => true
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return Taxon::getById($args['id']);
                }
            ],
            'getNamesByStringMatch' => [
                'type' => TypeRegister::nameMatchesType(),
                'description' => "Get a list of names that match the query string using some fuzzy best we can reckoning.",
                'args' => [
                    'queryString' => [
                        'type' => Type::string(),
                        'description' => "A string that resembles a correctly cited botanical name",
                        'required' => true
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info){
                    $matcher = new NameMatcher();
                    return $matcher->stringMatch($args['queryString']);
                }
            ],
            'getNamesByAlphaMatch' => [
                'type' => TypeRegister::nameMatchesType(),
                'description' => "Get a list of names that match the query string assuming simple alphabetical matching first part (excluding rank).",
                'args' => [
                    'queryString' => [
                        'type' => Type::string(),
                        'description' => "The start of the name string excluding any rank and authors",
                        'required' => true
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info){
                    $matcher = new NameMatcher();
                    return $matcher->alphaMatch($args['queryString']);
                }
            ],
            'getAllRanks' => [
                'type' => Type::listOf(TypeRegister::rankType()),
                'description' => "A list of all recognized ranks from highest to lowest",
                'resolve' => function(){
                    global $ranks_table;
                    $ranks = array();
                    foreach(array_keys($ranks_table) as $rankName){
                        $ranks[] = Rank::getRank($rankName);
                    }
                    return $ranks;
                }
            ],
            'getNamePlacer' => [
                'type' => TypeRegister::namePlacerType(),
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID or database ID of the name in question.",
                        'required' => true
                    ],
                    'action' => [
                        'type' => TypeRegister::getPlacementActionEnum(),
                        'description' => "The name of the intended action ",
                        'defaultValue' => 'none'
                    ],
                    'filter' => [
                        'type' => Type::string(),
                        'description' => "Characters to use as a filter on the suggested placements",
                        'defaultValue' => ''
                    ],
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return new NamePlacer($args['id'], $args['action'], $args['filter']);
                }
            ],
            'getUnplacedNames' => [
                'type' => TypeRegister::unplacedFinderType(),
                'description' => 'Return list of unplaced names associated with a name.',
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID or database ID of the name in question.",
                        'required' => true
                    ],
                    'offset' => [
                        'type' => Type::int(),
                        'description' => "Where to start from in the list.",
                        'defaultValue' => 0
                    ],
                    'limit' => [
                        'type' => Type::int(),
                        'description' => "Maximum results to return.",
                        'defaultValue' => 100
                    ],
                    'includeDeprecated' => [
                        'type' => Type::boolean(),
                        'description' => "Whether to include names with nomenclatural status 'deprecated",
                        'defaultValue' => false
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return new UnplacedFinder($args['id'], $args['offset'], $args['limit'], $args['includeDeprecated']);
                }
            ],// unplaced names
            'getPossibleBasionyms' => [
                'type' => TypeRegister::basionymFinderType(),
                'description' => 'Return list of possible basionyms for a name.',
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID or database ID of the name in question.",
                        'required' => true
                    ],
                    'filter' => [
                        'type' => Type::string(),
                        'description' => "Narrow down the search by including the first few letters of a name.",
                        'defaultValue' => ''
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return new BasionymFinder($args['id'], $args['filter']);
                }
            ],
            'getPossibleEditors' => [
                'type' => Type::listOf(TypeRegister::userType()),
                'description' => 'Return list of possible editors (excludes those with role anonymous).',
                'resolve' => function($rootValue, $args, $context, $info) {
                    return User::getPossibleEditors();
                }
            ],
            'getDownloads' => [
                'type' => Type::listOf(TypeRegister::downloadFileType()),
                'description' => "Return a list of data files for download.",
                'args' => [
                    'directoryName' => [
                        'type' => Type::string(),
                        'description' => "The name of the download directory to provide a file list for. These are considered well known. 'dwc' for Darwin Core Archives by family. 'lookup' for general matching files.",
                        'required' => true
                    ],
                    'fileEnding' => [
                        'type' => Type::string(),
                        'description' => "The the ending the files must have. These are considered well known. 'zip' for DwC archive files. 'gz' for other compressed files.",
                        'required' => true
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {

                    $files = array();
                    // just check they don't try and insert anything naughty into the path.
                    //if(!preg_match('/^[A-Za-z0-9]$/', $args['directoryName'])) return $files;
                    //if(!preg_match('/^[A-Za-z0-9]$/', $args['fileEnding'])) return $files;

                    $paths = glob("downloads/{$args['directoryName']}/*.{$args['fileEnding']}");
                    foreach ($paths as $path) {
                        $files[] = new DownloadFile($path);
                    }

                    return $files;
                }
            ]
        ]// fields
    ]), // query object type


    // -- M U T A T I O N S --

    'mutation' => new ObjectType([
        'name' => "Mutation",
        'description' => "Update and create taxa and names.",
        'fields' => [
            'updateNameParts' => [
                'type' => TypeRegister::updateResponseType(),
                'description' => "Update the name parts.",
                'args' => [
                    'wfo' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID of the name to be changed. This could be the prescribed WFO ID or one from a deduplication exercise",
                        'required' => true
                    ],
                    'genusString' => [
                        'type' => Type::string(),
                        'description' => "The string to be used in the genus part of the name.",
                        'required' => true
                    ],
                    'speciesString' => [
                        'type' => Type::string(),
                        'description' => "A string to be used in the species part of the name.",
                        'required' => true
                    ],
                    'nameString' => [
                        'type' => Type::string(),
                        'description' => "The actual name string (single word) for this name",
                        'required' => true
                    ],
                    'rankString' => [
                        'type' => Type::string(),
                        'description' => "The name of the rank (from the ranks table) for this name.",
                        'required' => true
                    ],
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    $response = new UpdateResponse('UpdateNameParts', true, "Updating the name parts");
                    $name = Name::getName($args['wfo']);
                    if(!$name || !$name->getId()){
                        $response->success = false;
                        $response->message = "Couldn't find name for WFO ID '{$args['wfo']}'"; 
                    }else{
                        $name->updateNameParts($args,$response);
                    }
                    return $response;
                }
            ], // updateNameParts
            'updateNameStatus' => [
                'type' => TypeRegister::updateResponseType(),
                'description' => "Update the names nomenclatural status.",
                'args' => [
                    'wfo' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID of the name to be changed. This could be the prescribed WFO ID or one from a deduplication exercise",
                        'required' => true
                    ],
                    'status' => [
                        'type' => Type::string(),
                        'description' => "The new status for the name.",
                        'required' => true
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    $response = new UpdateResponse('UpdateNameParts', true, "Updating the name parts");
                    $name = Name::getName($args['wfo']);
                    if(!$name || !$name->getId()){
                        $response->success = false;
                        $response->message = "Couldn't find name for WFO ID '{$args['wfo']}'"; 
                    }else{
                        $name->updateStatus($args['status'],$response);
                    }
                    return $response;
                }
            ], // updateNameStatus
            'updateAuthorsString' => [
                'type' => TypeRegister::updateResponseType(),
                'description' => "Update the author string of a name.",
                'args' => [
                    'wfo' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID of the name to be changed. This could be the prescribed WFO ID or one from a deduplication exercise",
                        'required' => true
                    ],
                    'authorsString' => [
                        'type' => Type::string(),
                        'description' => "The new authorsString for the name.",
                        'required' => true
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    $response = new UpdateResponse('UpdateNameParts', true, "Updating the name parts");
                    $name = Name::getName($args['wfo']);
                    if(!$name || !$name->getId()){
                        $response->success = false;
                        $response->message = "Couldn't find name for WFO ID '{$args['wfo']}'"; 
                    }else{
                        $name->updateAuthorsString($args['authorsString'],$response);
                    }
                    return $response;
                }
            ], // updateAuthorsString
            'updatePublication' => [
                'type' => TypeRegister::updateResponseType(),
                'description' => "Update the publication details of a name.",
                'args' => [
                    'wfo' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID of the name to be changed. This could be the prescribed WFO ID or one from a deduplication exercise",
                        'required' => true
                    ],
                    'citationMicro' => [
                        'type' => Type::string(),
                        'description' => "The abbreviated publication string",
                        'required' => true
                    ],
                    'year' => [
                        'type' => Type::int(),
                        'description' => "The year of publication as an integer",
                        'required' => false,
                        'defaultValue' => null
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    $response = new UpdateResponse('UpdatePublication', true, "Updating the name publication details");
                    $name = Name::getName($args['wfo']);
                    if(!$name || !$name->getId()){
                        $response->success = false;
                        $response->message = "Couldn't find name for WFO ID '{$args['wfo']}'"; 
                    }else{
                        $name->updatePublication($args['citationMicro'],$args['year'],$response);
                    }
                    return $response;
                }
            ], // updatePublication

            'updateComment' => [
                'type' => TypeRegister::updateResponseType(),
                'description' => "Update the comment on a name.",
                'args' => [
                    'wfo' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID of the name to be changed. This could be the prescribed WFO ID or one from a deduplication exercise",
                        'required' => true
                    ],
                    'comment' => [
                        'type' => Type::string(),
                        'description' => "The new comment for the name.",
                        'required' => true
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    $response = new UpdateResponse('UpdateComment', true, "Updating the comment.");
                    $name = Name::getName($args['wfo']);
                    if(!$name || !$name->getId()){
                        $response->success = false;
                        $response->message = "Couldn't find name for WFO ID '{$args['wfo']}'"; 
                    }else{
                        $name->updateComment($args['comment'],$response);
                    }
                    return $response;
                }
            ], // updateComment
            'updatePlacement' => [
                'type' => TypeRegister::updateResponseType(),
                'description' => "Update the placement of a name within the taxonomy (or remove it).",
                'args' => [
                    'wfo' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID of the name to be changed. This could be the prescribed WFO ID or one from a deduplication exercise",
                        'required' => true
                    ],
                    'action' => [
                        'type' => TypeRegister::getPlacementActionEnum(),
                        'description' => "The action to perform.",
                        'required' => true
                    ],
                    'destinationWfo' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID of the destination taxon (could be null if we are removing).",
                        'required' => false,
                        'defaultValue' => null
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    $placer = new NamePlacer($args['wfo'], $args['action']);
                    return $placer->updatePlacement($args['destinationWfo']);
                }
            ], // updatePlacement
            'updateBasionym' => [
                'type' => TypeRegister::updateResponseType(),
                'description' => "Update the basionym of this name.",
                'args' => [
                    'wfo' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID of the name to be changed. This could be the prescribed WFO ID or one from a deduplication exercise",
                        'required' => true
                    ],
                    'basionymWfo' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID of the name to be set as the basionym. This could be the prescribed WFO ID or one from a deduplication exercise.",
                        'required' => false,
                        'defaultValue' => null
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    $name = Name::getName($args['wfo']);
                    if($args['basionymWfo']){
                        $basionym = Name::getName($args['basionymWfo']);
                    }else{
                        $basionym = null;
                    }
                    return $name->updateBasionym($basionym, new UpdateResponse('UpdateBasionym', true, "Updating the basionym.") );
                }
            ],// updateBasionym

            'updateHybridStatus' => [
                'type' => TypeRegister::updateResponseType(),
                'description' => "Update the hybrid status of a taxon.",
                'args' => [
                    'id' => [
                        'type' => Type::int(),
                        'description' => "The database ID of the taxon record.",
                        'required' => true
                    ],
                    'isHybrid' => [
                        'type' => Type::boolean(),
                        'description' => "Whether this taxon is a hybrid or not.",
                        'required' => true
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    $response = new UpdateResponse('UpdatingHybridSatus', true, "Updating the hybrid status.");
                    $taxon = Taxon::getById($args['id']);
                    if(!$taxon || !$taxon->getId()){
                        $response->success = false;
                        $response->message = "Couldn't find taxon for  ID '{$args['id']}'"; 
                    }else{
                        // we don't have much validity checking for taxa so can set directly.
                        $taxon->setHybridStatus($args['isHybrid']);
                        $taxon->save();
                    }
                    return $response;
                }
            ], // updateComment

            'createName' => [
                'type' => TypeRegister::updateResponseType(),
                'description' => "Create a new name - or get feedback on creating a new name. It takes a monomial, binomial or trinomial and creates a basic name record with a default rank set. This record can then be edited.",
                'args' => [
                    'proposedName' => [
                        'type' => Type::string(),
                        'description' => "The proposed new name. This will be a string containing 1,2 or 3 words. Do not include ranks, hybrid markers or author string.",
                        'required' => true
                    ],
                    'create' => [
                        'type' => Type::boolean(),
                        'description' => "Whether to actually create the name. If false then only a validity test will be run.",
                        'required' => false,
                        'defaultValue' => false
                    ],
                    'forceHomonym' => [
                        'type' => Type::boolean(),
                        'description' => "Whether to create a name when there are know to be homonyms for this name. The WFO IDs of the known homonyms must be included in the knownHomonyms property. This prevents the temptation to just set this to true and be damned!",
                        'required' => false,
                        'defaultValue' => false
                    ],
                    'knownHomonyms' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "When creating a new name that it is known will be a homonym you must provide a list of the WFO IDs of the existing names to show you know what you are doing. You must also set the forceHomonym to true.",
                        'required' => false,
                        'defaultValue' => array()
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return Name::createName($args['proposedName'], $args['create'], $args['forceHomonym'], $args['knownHomonyms']);
                }
            ], // createName

            'addCurator' => [
                'type' => TypeRegister::updateResponseType(),
                'description' => "Add a curator to a taxon.",
                'args' => [
                    'wfo' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID of the name of the taxon to be changed. This could be the prescribed WFO ID or one from a deduplication exercise",
                        'required' => true
                    ],
                    'userId' => [
                        'type' => Type::int(),
                        'description' => "The database ID of the user to be added to the taxon as a curator. You might have got this through a call to the 'curators' property of the taxon.",
                        'required' => true
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    $name = Name::getName($args['wfo']);
                    $taxon = Taxon::getTaxonForName($name);
                    $curator = User::loadUserForDbId($args['userId']);
                    return $taxon->addCurator($curator);
                }
            ], // addCurator

            'removeCurator' => [
                'type' => TypeRegister::updateResponseType(),
                'description' => "Remove a curator from a taxon.",
                'args' => [
                    'wfo' => [
                        'type' => Type::string(),
                        'description' => "The WFO ID of the name of the taxon to be changed. This could be the prescribed WFO ID or one from a deduplication exercise",
                        'required' => true
                    ],
                    'userId' => [
                        'type' => Type::int(),
                        'description' => "The database ID of the user to be removed as a curator. You might have got this through a call to getPossibleEditors.",
                        'required' => true
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    $name = Name::getName($args['wfo']);
                    $taxon = Taxon::getTaxonForName($name);
                    $curator = User::loadUserForDbId($args['userId']);
                    return $taxon->removeCurator($curator);
                }
            ], // addCurator

        ]// fields
    ])// mutations
             
]); // schema


// these may need removing in production
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, wfo_access_token');


$rawInput = file_get_contents('php://input');

if(!trim($rawInput)){
    echo "<h1>WFO Taxonomic Backbone Management Interface</h1>";
    echo "<p>You don't seem to have given us a query to work with. Please use a GraphQL client to pass query info.</p>";
    exit;
}

/*

They are posting some data so we need to engage authentication.

------------------------
How authentication works
------------------------

Narrative 
- There must be a user object in the PHP session for processing to continue.
- This user object is either already there because they have a PHP session and have visited before
  or we are being called from a stateless script or from the UI for the first time and the user will need to be added.
- If there is no user in the session we create one using the wfo_access_token provided in the header.
- Every user in the users table has a wfo_access_token
- Regular users keep their tokens secret and only use them if they are running a stateless script.
- There is one special user called "web-ui" who has a well known access token embedded in the web ui code. This user has few powers.
- When the web client connects to the API it passes the well known token and the web-ui user is added to the PHP session.
- The human user can then use the web client to login with ORCID credentials and the user in their session is swapped from the web-ui user to the actual user from the db table.
- If the human user wants to run a command line script as themselves they can access their own wfo_access_token and use that in the script.
- All communications are kept https so tokens shouldn't leak but if they do they can be changed.
- Any public DB dumps will need to omit wfo_access_token (and probably other users table fields) for security and anonymity.

*/


//unset($_SESSION['user']);

// check the session doesn't have a broken user object in it
// mainly 


// no user in session and we need one
if(!isset($_SESSION['user'])){

    // pull out the access token.
    $wfo_access_token = null;
    $headers = apache_request_headers();
    foreach ($headers as $header => $value) {
        if($header == 'wfo_access_token') $wfo_access_token = $value;
    }

    // no access token no go
    // key generated like this  echo bin2hex(openssl_random_pseudo_bytes(24));
    if(!$wfo_access_token ){
        http_response_code(403);
        die('Forbidden: No wfo_access_token provided.');
    }

    // we have a token so lets load the user
    $user = User::loadUserForWfoToken($wfo_access_token);
    error_log("got user from db via token: " .  $user->getId());
    if(!$user){
        http_response_code(403);
        die('Forbidden: Failed to load user for access token: ' . $wfo_access_token);
    }else{ 
         error_log('Adding user to session');
        $_SESSION['user'] = serialize($user);
    }
    
}else{
    error_log('user is in session');
}


$input = json_decode($rawInput, true);
$query = $input['query'];
$variableValues = isset($input['variables']) ? $input['variables'] : null;

$debug = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;

try {
    $result = GraphQL::executeQuery($schema, $query, null, null, $variableValues);
    $output = $result->toArray($debug);
} catch (\Exception $e) {
    $output = [
        'errors' => [
            [
                'message' => $e->getMessage()
            ]
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode($output);


