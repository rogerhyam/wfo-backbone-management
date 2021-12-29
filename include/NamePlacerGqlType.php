<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\EnumType;



class NamePlacerGqlType extends ObjectType
{

    public function __construct()
    {
        $config = [
            'description' => "A mechanism for placing a name.",
            'fields' => function() {
                // this is a very simple wrapper around an
                // object with a few public properties.
                return [
                    'canBeRaised' => [
                        'type' => Type::boolean(),
                        'description' => "A name that is a synonym or not yet placed in the taxonomy and has a nomenclatural status or Valid, Conserved or Sanctioned can become the accepted name of a taxon."
                    ],
                    'canBeSunk' => [
                        'type' => Type::boolean(),
                        'description' => "A name that is the accepted name of a taxon (which doesn't have children or synonyms) or has not yet been placed in the taxonomy can become a synonym in an accepted taxon."
                    ],
                    'canChangeParent' => [
                        'type' => Type::boolean(),
                        'description' => "A name that is the accepted name of a taxon can be moved to another part of the taxonomy."
                    ],
                    'canChangeAccepted' => [
                        'type' => Type::boolean(),
                        'description' => "A name that is a synonym of one taxon can be moved to become the synonym of another taxon."
                    ],
                    'canBeRemoved' => [
                        'type' => Type::boolean(),
                        'description' => "A name that forms part of the taxon as the accepted name of a taxon (which doesn't have children or synonyms) or is a synonym can be removed from the taxonomy."
                    ],
                    'action' => [
                        'type' => Type::string(),
                        'description' => "A name that forms part of the taxon as the accepted name of a taxon (which doesn't have children or synonyms) or is a synonym can be removed from the taxonomy."
                    ],
                    'filter' => [
                        'type' => Type::string(),
                        'description' => "The text used to filter the possibleTaxa returned. Initially the first few letters of the name_alpha"
                    ],
                    'filterNeeded' => [
                        'type' => Type::boolean(),
                        'description' => "Whether a filter string is needed to display more taxon placements"
                    ],
                    'possibleTaxa' => [
                        'type' => Type::listOf(TypeRegister::taxonType()),
                        'description' => "List of taxa that could be used."
                    ],
                    'name' => [
                        'type' => TypeRegister::nameType(),
                        'description' => "The name we are looking to place."
                    ]
                    // FIXME - possible places ..
                ];
            }
        ];
        parent::__construct($config);
    }
}

