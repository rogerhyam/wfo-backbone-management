<?php

require_once('../include/TaxonGqlType.php');
require_once('../include/NameGqlType.php');
require_once('../include/NameMatchesGqlType.php');
require_once('../include/RankGqlType.php');
require_once('../include/UpdateResponseGqlType.php');

/*

    Register of types because the schema must only have one instance 
    of each type in it.

*/

class TypeRegister {

    private static $taxonType;
    private static $nameType;
    private static $nameMatchesType;
    private static $rankType;
    private static $updateResponseType;

    public static function taxonType(){
        return self::$taxonType ?: (self::$taxonType = new TaxonGqlType());
    }

    public static function nameType(){
        return self::$nameType ?: (self::$nameType = new NameGqlType());
    }
    
    public static function nameMatchesType(){
        return self::$nameMatchesType ?: (self::$nameMatchesType = new NameMatchesGqlType());
    }

    public static function rankType(){
        return self::$rankType ?: (self::$rankType = new RankGqlType());
    }

    public static function updateResponseType(){
        return self::$updateResponseType ?: (self::$updateResponseType = new UpdateResponseGqlType());
    }

}