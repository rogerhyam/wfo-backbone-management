<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class NameIpniDifferencesGqlType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'description' => "A wrapper around a name that fetches differences with the live IPNI record for the name. May perform slowly.",
            'fields' => function() {
                return [
                    'id' => [
                        'type' => Type::string(),
                        'description' => "LSID of the IPNI record used as ID for this object."
                    ],
                    'retrieved' => [
                        'type' => Type::boolean(),
                        'description' => "Whether data has be retrieved from IPNI for the name."
                    ],
                    'differenceCount' => [
                        'type' => Type::int(),
                        'description' => "The number of differences between Name fields and IPNI fields."
                    ],
                    'nameString' => [
                        'type' => Type::string(),
                        'description' => "The name string name part in IPNI if it is different from that in the Name object."
                    ],
                    'genusString' => [
                        'type' => Type::string(),
                        'description' => "The genus string name part in IPNI if it is different from that in the Name object."
                    ],
                    'speciesString' => [
                        'type' => Type::string(),
                        'description' => "The species string name part in IPNI if it is different from that in the Name object."
                    ],
                    'authorsString' => [
                        'type' => Type::string(),
                        'description' => "The authors string in IPNI if it is different from that in the Name object."
                    ],
                    'citationMicro' => [
                        'type' => Type::string(),
                        'description' => "The protologue citation string in IPNI if it is different from that in the Name object."
                    ]
                ];
            }
        ];
        parent::__construct($config);

    }

}