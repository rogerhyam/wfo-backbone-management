<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class ReferenceUsageGqlType extends ObjectType
{

    public function __construct()
    {
        $config = [
            'description' => "The usage of the reference with associated comment",
            'fields' => function() {
                return [
                    'id' => [
                        'type' => Type::string(),
                        'description' => "Object id."
                    ],
                    'reference' => [
                        'type' => TypeRegister::referenceType(),
                        'description' => "The reference object"
                    ],
                    'comment' => [
                        'type' => Type::string(),
                        'description' => "The comment about this usage of the reference."
                    ],
                    'subjectType' => [
                        'type' => Type::string(),
                        'description' => "Whether the reference is about a taxon or name."
                    ]
                ];
            }
        ];
        parent::__construct($config);

    }// constructor

}