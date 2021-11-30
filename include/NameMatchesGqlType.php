<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class NameMatchesGqlType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'description' => "A Name governed by the the code",
            'fields' => function() {
                // this is a very simple wrapper around an
                // object with a few public properties.
                return [
                    'names' => [
                        'type' => Type::listOf(TypeRegister::nameType()),
                        'description' => "The matched Name as an object"
                    ],
                    'query' => [
                        'type' => Type::string(),
                        'description' => "The original query string passed"
                    ],
                    'nameParts' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "The parts of the name that were recognized in the query string in the order they were recognized."
                    ],
                    'rank' => [
                        'type' => Type::string(),
                        'description' => "The rank extracted from the query string, if any"
                    ],
                    'authors' => [
                        'type' => Type::string(),
                        'description' => "The authors extracted from the query string, if any"
                    ],
                    'distances' => [
                        'type' => Type::listOf(Type::int()),
                        'description' => "The Levenshtein Distance (number of character changes) between the query string and the full name string of the name"
                    ]
                ];
            }
        ];
        parent::__construct($config);
    }
}

