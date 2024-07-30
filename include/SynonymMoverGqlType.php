<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\EnumType;


class SynonymMoverGqlType extends ObjectType
{

    public function __construct()
    {
        $config = [
            'description' => "A mechanism for moving all the synonyms of a name.",
            'fields' => function() {
                // this is a very simple wrapper around an
                // object with a few public properties.
                return [
                    'filter' => [
                        'type' => Type::string(),
                        'description' => "The text used to filter the possibleTaxa returned. Initially the first few letters of the name_alpha"
                    ],
                    'possibleTaxa' => [
                        'type' => Type::listOf(TypeRegister::taxonType()),
                        'description' => "A list of taxa that the synonym could be moved to, restricted to 100 and by filter applied.",
                        'resolve' => function($mover){
                            return $mover->getPossibleTaxa();
                        }
                    ],
                    'taxon' => [
                        'type' => TypeRegister::taxonType(),
                        'description' => "The taxon that currently owns the synonyms to be moved."
                    ]
                ];
            }
        ];
        parent::__construct($config);
    }
}