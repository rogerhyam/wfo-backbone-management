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

$typeReg = new TypeRegister();

$schema = new Schema([
    'query' => new ObjectType([
        'name' => 'Query',
        'description' => 
            "This is the WFO Taxonomic Backbone management API",
        'fields' => [
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


