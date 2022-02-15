<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class DownloadFileGqlType extends ObjectType
{
    public function __construct()
    {
        $config = [

            'description' => "A data file that can be downloaded",
            'fields' => function() {
                return [
                    'id' => [
                        'type' => Type::string(),
                        'description' => "An identifier for this file object."
                    ],
                    'fileName' => [
                        'type' => Type::string(),
                        'description' => "The name of the file."
                    ],
                    'title' => [
                        'type' => Type::string(),
                        'description' => "The display name of the file."
                    ],
                    'description' => [
                        'type' => Type::string(),
                        'description' => "A description of what is in the file. May be empty if none is available."
                    ],
                    'sizeBytes' => [
                        'type' => Type::int(),
                        'description' => "A machine readable size of the file in bytes."
                    ],
                    'sizeHuman' => [
                        'type' => Type::string(),
                        'description' => "A human friendly version of the file size."
                    ],
                    'uri' => [
                        'type' => Type::string(),
                        'description' => "The download link for the file."
                    ],
                    'created' => [
                        'type' => Type::string(),
                        'description' => "The creation time of the file as an ISO date string."
                    ]

                   
                ];
            }
        ];
        parent::__construct($config);

    }// constructor

}// class
