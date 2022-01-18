<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class NameGqlType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'description' => "A Name governed by the the code",
            'fields' => function() {
                return [
                    'id' => [
                        'type' => Type::int(),
                        'description' => "database id for the name",
                        'resolve' => function($name){
                            return $name->getId();
                        }
                    ],
                    'wfo' => [
                        'type' => Type::string(),
                        'description' => "The prescribed WFO ID to use for this name",
                        'resolve' => function($name){
                            return $name->getPrescribedWfoId();
                        }
                    ],
                    'identifiers' => [
                        'type' => Type::listOf(TypeRegister::identifierType()),
                        'description' => "A list of know identifiers (excluding the db on) for this name.",
                        'resolve' => function($name){
                            return $name->getIdentifiers();
                        }
                    ],
                    'rank' => [
                        'type' => TypeRegister::rankType(),
                        'description' => "The rank string for this name",
                        'resolve' => function($name){
                            return Rank::getRank($name->getRank());
                        }
                    ],
                    'nameString' => [
                        'type' => Type::string(),
                        'description' => "The principle name string for this name.",
                        'resolve' => function($name){
                            return $name->getNameString();
                        }
                    ],
                    'genusString' => [
                        'type' => Type::string(),
                        'description' => "The genus name string for names below the rank of genus.",
                        'resolve' => function($name){
                            return $name->getGenusString();
                        }
                    ],
                    'speciesString' => [
                        'type' => Type::string(),
                        'description' => "The species name string for names below the rank of species.",
                        'resolve' => function($name){
                            return $name->getSpeciesString();
                        }
                    ],
                    'authorsString' => [
                        'type' => Type::string(),
                        'description' => "The string representing the author abbreviations for this name.",
                        'resolve' => function($name){
                            return $name->getAuthorsString();
                        }
                    ],
                    'fullNameString' => [
                        'type' => Type::string(),
                        'description' => "The full name for display purposes.",
                        'args' => [
                            'italics' => [
                                'type' => Type::boolean(),
                                'description' => "Whether words in names at and below the genus level should be italicized. Defaults true.",
                                'required' => false,
                                'defaultValue' => true
                            ],
                            'authors' => [
                                'type' => Type::boolean(),
                                'description' => "Whether the authors string (abbreviated authors) should be included. Defaults true.",
                                'required' => false,
                                'defaultValue' => true
                            ],
                            'abbreviateRank' => [
                                'type' => Type::boolean(),
                                'description' => "Whether the rank (always included in names below genus) should be abbreviated. Defaults true.",
                                'required' => false,
                                'defaultValue' => true
                            ],
                            'abbreviateGenus' => [
                                'type' => Type::boolean(),
                                'description' => "Whether the genus word (in names below the rank of genus) should be abbreviated. Defaults false.",
                                'required' => false,
                                'defaultValue' => false
                            ],
                        ],
                        'resolve' => function($name, $args, $context, $info){
                            return $name->getFullNameString( $args['italics'], $args['authors'], $args['abbreviateRank'], $args['abbreviateGenus'] );
                        }
                    ],
                    'status' => [
                        'type' => Type::string(),
                        'description' => "The nomenclatural status of this name.",
                        'resolve' => function($name){
                            return $name->getStatus();
                        }
                    ],
                    'isAutonym' => [
                        'type' => Type::boolean(),
                        'description' => "True if this is an autonym name.",
                        'resolve' => function($name){
                            return $name->isAutonym();
                        }
                    ],
                    'basionym' => [
                        'type' => TypeRegister::nameType(),
                        'description' => "The type bearing name for this name.",
                        'resolve' => function($name){
                            return $name->getBasionym();
                        }
                    ],
                    'homotypicNames' => [
                        'type' => Type::listOf(TypeRegister::nameType()),
                        'description' => "Names that share the same type as this name.",
                        'resolve' => function($name){
                            return $name->getHomotypicNames();
                        }
                    ],
                    'homonyms' => [
                        'type' => Type::listOf(TypeRegister::nameType()),
                        'description' => "Names that are homonyms of this name i.e. have the same name parts.",
                        'resolve' => function($name){
                            return $name->getHomonyms();
                        }
                    ],
                    'citationMicro' => [
                        'type' => Type::string(),
                        'description' => "The standard form short citation for where this name was published.",
                        'resolve' => function($name){
                            return $name->getCitationMicro();
                        }
                    ],
                    'citationId' => [
                        'type' => Type::string(),
                        'description' => "An identifier for the citation of where this name was published.",
                        'resolve' => function($name){
                            return $name->getCitationID();
                        }
                    ],
                    'comment' => [
                        'type' => Type::string(),
                        'description' => "A comment on the name",
                        'resolve' => function($name){
                            return $name->getComment();
                        }
                    ],
                    'year' => [
                        'type' => Type::int(),
                        'description' => "The year of valid publication of this name",
                        'resolve' => function($name){
                            return $name->getYear();
                        }
                    ],

                    // note that strictly Names know nothing of Taxa but because this is an
                    // API interface is seems OK to join them up here - but never in the Name object!!
                    'taxonPlacement' => [
                        'type' => TypeRegister::taxonType(),
                        'resolve' => function($name) {

                            $taxon = Taxon::getTaxonForName($name);

                            // a blank taxon is created if the name is not placed (it is how we create new ones)
                            // and we don't want to return that for a query
                            if($taxon->getId()){
                                return $taxon;
                            }else{
                                return null;
                            }

                        }
                    ]
                    
                ];
            }
        ];
        parent::__construct($config);

    }

}