<?php

// a very simple class to represent a rank in the ranks table to the GraphQL API.

class Rank{
    public String $name;
    public String $abbreviation;
    public String $plural;
    public Array $aka;
    public Array $children;

    // we are singletons so equality comparisons will work and save a bit of memory.
    protected static $loaded = array();

    /**
     * Don't call this directly. Use the getRank factory method
     * 
     */
    public function __construct($name){

        global $ranks_table;

        $this->name = $name;
        $this->abbreviation = $ranks_table[$name]['abbreviation'];
        $this->plural = $ranks_table[$name]['plural'];
        $this->aka = $ranks_table[$name]['aka'];

        $this->children = array();
        foreach($ranks_table[$name]['children'] as $kidName){
            $this->children[] = Rank::getRank($kidName);
        }

    }

    public static function getRank($name){
        if(!$name) return null;
        if(isset(self::$loaded[$name])) return self::loaded[$name];
        return new Rank($name);
    }
}
