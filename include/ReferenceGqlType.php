<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class ReferenceGqlType extends ObjectType
{

    public function __construct()
    {
        $config = [
            'description' => "A referenced to an external resource",
            'fields' => function() {
                return [
                    'id' => [
                        'type' => Type::int(),
                        'description' => "Object id.",
                        'resolve' => function($ref, $args, $context, $info){
                            return $ref->getId();
                        }
                    ],
                    'kind' => [
                        'type' => Type::string(),
                        'description' => "Kind of reference this is: person, specimen, literature.",
                        'resolve' => function($ref, $args, $context, $info){
                            return $ref->getKind();
                        }
                    ],
                    'displayText' => [
                        'type' => Type::string(),
                        'description' => "The text to be displayed - typically the link text or full literature citation.",
                        'resolve' => function($ref, $args, $context, $info){
                            return $ref->getDisplayText();
                        }
                    ],
                    'linkUri' => [
                        'type' => Type::string(),
                        'description' => "A URI linking to the external resource",
                        'resolve' => function($ref, $args, $context, $info){
                            return $ref->getLinkUri();
                        }
                    ],
                    'thumbnailUri' => [
                        'type' => Type::string(),
                        'description' => "A URI linking to a thumbnail image of the resource e.g. first page or low res of specimen.",
                        'resolve' => function($ref, $args, $context, $info){
                            return $ref->getThumbnailUri();
                        }
                    ]
                ];
            }
        ];
        parent::__construct($config);

    }// constructor

}