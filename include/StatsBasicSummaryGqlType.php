<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class StatsBasicSummaryGqlType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'description' => "A row in the basic summary statistics",
            'fields' => function() {
                return [
                    'id' => [
                        'type' => Type::string(),
                        'description' => "Unique ID for the row."
                    ],
                    'phylum' => [
                        'type' => Type::string(),
                        'description' => "The Phylum name"
                    ],
                    'phylumWfo' => [
                        'type' => Type::string(),
                        'description' => "The Phylum WFO ID"
                    ],
                    'order' => [
                        'type' => Type::string(),
                        'description' => "The Order"
                    ],
                    'orderWfo' => [
                        'type' => Type::string(),
                        'description' => "The Order WFO ID"
                    ],
                    'family' => [
                        'type' => Type::string(),
                        'description' => "The Family"
                    ],
                    'familyWfo' => [
                        'type' => Type::string(),
                        'description' => "The Family WFO ID"
                    ],
                    'taxa' => [
                        'type' => Type::int(),
                        'description' => "The number of accepted taxa."
                    ],
                    'withEditors' => [
                        'type' => Type::int(),
                        'description' => "The number of accepted taxa that have editors who are not administrators."
                    ],
                    'synonyms' => [
                        'type' => Type::int(),
                        'description' => "The number of synonyms attached to accepted taxa."
                    ],
                    'unplaced' => [
                        'type' => Type::int(),
                        'description' => "The number names that have not been placed in the taxonomy but might occur here. This includes names that have a genus part that is the same as the name of an accepted or synonymous genus or names that have a flag on import indicating possible family placement."
                    ],
                    'genera' => [
                        'type' => Type::int(),
                        'description' => "The number of accepted genera."
                    ],
                    'species' => [
                        'type' => Type::int(),
                        'description' => "The number of accepted species."
                    ],
                    'subspecies' => [
                        'type' => Type::int(),
                        'description' => "The number of accepted subspecies."
                    ],
                    'varieties' => [
                        'type' => Type::int(),
                        'description' => "The number of accepted varieties."
                    ],
                    'gbifGapSpecies' => [
                        'type' => Type::int(),
                        'description' => "Unplaced species names with occurrences in GBIF."
                    ],
                    'gbifGapOccurrences' => [
                        'type' => Type::int(),
                        'description' => "Total occurrence records in GBIF tagged with unplaced species names."
                    ]
                ];
            }
        ];
        parent::__construct($config);
    
    } // constructor

}// class