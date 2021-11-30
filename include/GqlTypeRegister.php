<?php

require_once('../include/TaxonGqlType.php');
require_once('../include/NameGqlType.php');
require_once('../include/NameMatchesGqlType.php');

/*

    Register of types because the schema must only have one instance 
    of each type in it.

*/

class TypeRegister {

    private static $taxonType;
    private static $nameType;
    private static $nameMatchesType;

    public static function taxonType(){
        return self::$taxonType ?: (self::$taxonType = new TaxonGqlType());
    }

    public static function nameType(){
        return self::$nameType ?: (self::$nameType = new NameGqlType());
    }
    
    public static function nameMatchesType(){
        return self::$nameMatchesType ?: (self::$nameMatchesType = new NameMatchesGqlType());
    }
}