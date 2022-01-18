<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class IdentifierGqlType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'description' => "A Name governed by the the code",
            'fields' => function() {
                return [
                    'kind' => [
                        'type' => Type::string(),
                        'description' => "The machine name for the identifier type",
                        'resolve' => function($identifier){
                            return $identifier->getKind();
                        }
                    ],
                    'displayName' => [
                        'type' => Type::string(),
                        'description' => "A human readable name for the identifier",
                        'resolve' => function($identifier){
                            return $identifier->getDisplayName();
                        }
                    ],
                    'values' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "All the values of this identifier for the name.",
                        'resolve' => function($identifier){
                            return $identifier->getValues();
                        }
                    ]
                ];
            }
        ];
        parent::__construct($config);
    
    } // constructor

}// class