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


$typeReg = new TypeRegister();

$schema = new Schema([
    'query' => new ObjectType([
        'name' => 'Query',
        'description' => 
            "This is the WFO Taxonomic Backbone management API",
        'fields' => [
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
        ]// fields
    ]), // query object type
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
                    //print_r($context);
                    error_log(print_r($context, true));
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
                    //print_r($context);
                    error_log(print_r($context, true));
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
            ],


        ]// fields
    ])// mutations
             
]); // schema


// these may need removing in production
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');


$rawInput = file_get_contents('php://input');

if(!trim($rawInput)){
    echo "<h1>WFO Taxonomic Backbone Management Interface</h1>";
    echo "<p>You don't seem to have given us a query to work with. Please use a GraphQL client to pass query info.</p>";
    exit;
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


