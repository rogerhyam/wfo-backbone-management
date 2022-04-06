<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;


class UserGqlType extends ObjectType
{
    public function __construct()
    {
        $config = [ 
            
            'description' => "The current user i.e. you!",
            'fields' => function() {
                return [
                    'id' => [
                        'type' => Type::int(),
                        'description' => "The database id of the user.",
                        'resolve' => function($user){
                            return $user->getID();
                        }
                    ],
                    'isAnonymous' => [
                        'type' => Type::boolean(),
                        'description' => "The API will always return a user but they may be an anonymous user with no powers. This is needed for applications like enabling non logged in browsing. ",
                        'resolve' => function($user){
                            return $user->isAnonymous();
                        }
                    ],
                    'isEditor' => [
                        'type' => Type::boolean(),
                        'description' => "This is a real user with permission to edit at least one taxon somewhere in the system",
                        'resolve' => function($user){
                            return $user->isEditor();
                        }
                    ],
                    'isGod' => [
                        'type' => Type::boolean(),
                        'description' => "A user with godlike powers (might be an admin in lesser systems)",
                        'resolve' => function($user){
                            return $user->isGod();
                        }
                    ],
                    'name' => [
                        'type' => Type::string(),
                        'description' => "The name of the field or the mutation called.",
                        'resolve' => function($user){
                            return $user->getName();
                        }
                    ],
                    'uri' => [
                        'type' => Type::string(),
                        'description' => "The URI to the official web page of this user (typically a TEN).",
                        'resolve' => function($user){
                            return $user->getUri();
                        }
                    ],
                    'orcid' => [
                        'type' => Type::string(),
                        'description' => "The user's ORCID ID - useful for building links to their profile.",
                        'resolve' => function($user){
                            return $user->getOrcidID();
                        }
                    ],
                    'orcidLogInUri' => [
                        'type' => Type::string(),
                        'description' => "The URI required to launch a ORCID login window.",
                        'resolve' => function($user){
                            return $user->getOrcidLogInUri();
                        }
                    ],
                    'orcidLogOutUri' => [
                        'type' => Type::string(),
                        'description' => "The URI required to launch a ORCID login window.",
                        'resolve' => function($user){
                            return $user->getOrcidLogOutUri();
                        }
                    ],
                    'taxaCurated' => [
                        'type' => Type::listOf(TypeRegister::taxonType()),
                        'description' => "A list of taxa for which this this user is a curator - they can edit everything downstream of this.",
                        'resolve' => function($user){
                            return $user->getTaxaCurated();
                        }
                    ]
                    
                ];
            }
        ];
        parent::__construct($config);

    }// constructor

}// class
