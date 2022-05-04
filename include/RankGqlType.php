<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class RankGqlType extends ObjectType
{
    public function __construct()
    {
        $config = [

            'description' => "A rank recognized in the system",
            'fields' => function() {
                return [
                    'id' => [
                        'type' => Type::string(),
                        'description' => "Object id. Actually just the same as the name.",
                        'resolve' => function($rank, $args, $context, $info){
                            return $rank->name;
                        }
                    ],
                    'name' => [
                        'type' => Type::string(),
                        'description' => "The official name of the rank used as an identifier internally"
                    ],
                    'abbreviation' => [
                        'type' => Type::string(),
                        'description' => "The official abbreviation for this rank"
                    ],
                    'plural' => [
                        'type' => Type::string(),
                        'description' => "A plural version of the name in title case - because it is often used in titles!"
                    ],
                    'aka' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Also Known As. Other strings that might be used by other people for this rank"
                    ],
                    'children' => [
                        'type' => Type::listOf(TypeRegister::rankType()),
                        'description' => "The ranks that child taxa for this taxon can be."
                    ],
                    'isBelowGenus' => [
                        'type' => Type::boolean(),
                        'description' => "Is this rank below the level of genus? e.g. subspecies or subgenus."
                    ]
                ];
            }
        ];
        parent::__construct($config);

    }// constructor

}// class
