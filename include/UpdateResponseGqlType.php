<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class UpdateResponseGqlType extends ObjectType
{
    public function __construct()
    {
        $config = [

            'description' => "A response to a request to update data",
            'fields' => function() {
                return [
                    'name' => [
                        'type' => Type::string(),
                        'description' => "The name of the field or the mutation called."
                    ],
                    'success' => [
                        'type' => Type::boolean(),
                        'description' => "Whether the update was successful or not."
                    ],
                    'message' => [
                        'type' => Type::string(),
                        'description' => "Human readable explanation of success or failure."
                    ],
                    'children' => [
                        'type' => Type::listOf(TypeRegister::updateResponseType()),
                        'description' => "The update response for any sub parts of the update e.g. the fields in the form."
                    ],
                    'taxonIds' => [
                        'type' => Type::listOf(Type::int()),
                        'description' => "A list of affected taxon db ids. This is useful for clearing cached data"
                    ],
                    'nameIds' => [
                        'type' => Type::listOf(Type::int()),
                        'description' => "A list of affected name db ids. This is useful for clearing cached data"
                    ]
                ];
            }
        ];
        parent::__construct($config);

    }// constructor

}// class
