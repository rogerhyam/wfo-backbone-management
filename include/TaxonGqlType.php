<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class TaxonGqlType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'description' => "Description of a Taxon",
            'fields' => function() {
                return [
                    'id' => [
                        'type' => Type::int(),
                        'description' => "database id for the taxon",
                        'resolve' => function($taxon){
                            return $taxon->getId();
                        }
                    ],
                    'acceptedName' => [
                        'type' => TypeRegister::nameType(),
                        'description' => "The accepted name for this taxon",
                        'resolve' => function($taxon){
                            return $taxon->getAcceptedName();
                        }
                    ],
                    'synonyms' => [
                        'type' => Type::listOf(TypeRegister::nameType()),
                        'description' => "The names that are considered synonyms of this taxon.",
                        'resolve' => function($taxon){
                            return $taxon->getSynonyms();
                        }
                    ],
                    'children' => [
                        'type' => Type::listOf(TypeRegister::taxonType()),
                        'description' => "The taxa that are part of this taxon.",
                        'resolve' => function($taxon){
                            return $taxon->getChildren();
                        }
                    ],
                    'ancestors' => [
                        'type' => Type::listOf(TypeRegister::taxonType()),
                        'description' => "This taxon all the way back to the root.",
                        'resolve' => function($taxon){
                            return $taxon->getAncestors();
                        }
                    ],
                    'ancestorAtRank' => [
                        'type' => TypeRegister::taxonType(),
                        'description' => "Looks up the ancestor lineage and returns the ancestor with the specified rank or null if one isn't found.",
                        'args' => [
                            'rank' => [
                                'type' => Type::string(),
                                'description' => "The rank of interest.",
                                'required' => true
                            ]
                        ],
                        'resolve' => function($taxon, $args, $context, $info){
                            return $taxon->getAncestorAtRank($args['rank']);
                        }
                    ],
                    'parent' => [
                        'type' => TypeRegister::taxonType(),
                        'description' => "The taxon that contains this taxon.",
                        'resolve' => function($taxon){
                            return $taxon->getParent();
                        }
                    ],
                    'rank' => [
                        'type' => TypeRegister::rankType(),
                        'description' => "The rank of this taxon. This is a wrapper around the rank of the accepted name.",
                        'resolve' => function($taxon){
                            return Rank::getRank($taxon->getRank());
                        }
                    ]
                ];
            }
        ];
        parent::__construct($config);

    }

}