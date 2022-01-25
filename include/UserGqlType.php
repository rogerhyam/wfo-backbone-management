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
                    'name' => [
                        'type' => Type::string(),
                        'description' => "The name of the field or the mutation called.",
                        'resolve' => function($user){
                            return $user->getName();
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
                    ]
                ];
            }
        ];
        parent::__construct($config);

    }// constructor

}// class
