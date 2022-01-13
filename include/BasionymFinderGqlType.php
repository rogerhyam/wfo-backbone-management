<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class BasionymFinderGqlType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'description' => "A response with a list of the possible basionyms for a name",
            'fields' => function() {
                return [
                    'name' => [
                        'type' => TypeRegister::nameType(),
                        'description' => "The name in question."
                    ],
                    'possibleBasionyms' => [
                        'type' => Type::listOf(TypeRegister::nameType()),
                        'description' => "The list of possible basionym names."
                    ],
                    'limit' => [
                        'type' => Type::int(),
                        'description' => "The maximum number of unplaced names requested. Defaults to 30."
                    ],
                    'filter' => [
                        'type' => Type::string(),
                        'description' => "The first few letters of a name used as a filter down the list if it is longer than 30."
                    ]
                ];
            }
        ];
        parent::__construct($config);

    }// constructor

}// class
