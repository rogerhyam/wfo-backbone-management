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
                        'type' => Type::string(),
                        'description' => "database id for the taxon",
                        'resolve' => function($taxon){
                            return $taxon->getId();
                        }
                    ]
                ];
            }
        ];
        parent::__construct($config);

    }

}