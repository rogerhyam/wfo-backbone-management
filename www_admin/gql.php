<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
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
                'description' => "Get a list of names that match the query string.",
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
            ]
        ]// fields
    ]), // query object type
    'mutation' => new ObjectType([
        'name' => "Mutation",
        'description' => "This is how you change stuff",
        'fields' => [
            'createTaxon' => [
                'type' => TypeRegister::taxonType()
            ] // createTaxon
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


