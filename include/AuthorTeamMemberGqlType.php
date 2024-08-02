<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class AuthorTeamMemberGqlType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'description' => "Representation of a member of a plant name author team with an official abbreviation.",
            'fields' => function() {
                return [
                    'id' => [
                        'type' => Type::string(),
                        'description' => "Unique ID for this person - also their abbreviation.",
                        'resolve' => function($member){
                            return $member->abbreviation; // we return the abbrev as the ID for graphql best practice - should be unique.
                        }
                    ],
                    'abbreviation' => [
                        'type' => Type::string(),
                        'description' => "The official abbreviation for this person as stored in wikidata P428 - https://www.wikidata.org/wiki/Property:P428"
                    ],
                    'label' => [
                        'type' => Type::string(),
                        'description' => "A display string generated from data in Wikidata for this person."
                    ],
                    'wikiUri' => [
                        'type' => Type::string(),
                        'description' => "The URI for this persons Wikidata entry."
                    ],
                    'imageUri' => [
                        'type' => Type::string(),
                        'description' => "An image of this person from Wikidata - if available."
                    ],
                    'referencePresent' => [
                        'type' => Type::boolean(),
                        'description' => "Whether or not there is reference present in the associated name (if a wfo id was provided)"
                    ],
                ];
            }
        ];
        parent::__construct($config);

    }// constructor

}// class