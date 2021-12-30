<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class UnplacedFinderGqlType extends ObjectType
{
    public function __construct()
    {
        $config = [

            'description' => "A response with a list of the unplaced names associated with a name",
            'fields' => function() {
                return [
                    'name' => [
                        'type' => TypeRegister::nameType(),
                        'description' => "The name with the associated unplaced names."
                    ],
                    'unplacedNames' => [
                        'type' => Type::listOf(TypeRegister::nameType()),
                        'description' => "The list of unplaced names constrained by offset and limit."
                    ],
                    'offset' => [
                        'type' => Type::int(),
                        'description' => "The offset into the alphabetical list of unplaced names requested."
                    ],
                    'limit' => [
                        'type' => Type::int(),
                        'description' => "The total number of unplaced names requested."
                    ],
                    'totalUnplacedNames' => [
                        'type' => Type::int(),
                        'description' => "The total number of unplaced names associated with this name."
                    ],
                    'includeDeprecated' => [
                        'type' => Type::boolean(),
                        'description' => "Whether it was requested to include deprecated names in this list."
                    ]
                ];
            }
        ];
        parent::__construct($config);

    }// constructor

}// class
