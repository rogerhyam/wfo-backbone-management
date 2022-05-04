<?php

// a very simple class to represent a rank in the ranks table to the GraphQL API.

class Rank{
    public String $name;
    public String $abbreviation;
    public String $plural;
    public Array $aka;
    public Array $children;
    public bool $isBelowGenus;

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
            // recursion risk
            $kid = Rank::getRank($kidName);
            if(!in_array($kid,$this->children))$this->children[] = $kid;
        }

        // really useful to know if we are below genus level - for autonyms etc
        $my_level = array_search($name, array_keys($ranks_table));
        $genus_level = array_search('genus', array_keys($ranks_table));
        $this->isBelowGenus = $my_level > $genus_level; // higher index is lower rank

        // we now become a singleton so we don't have to be loaded again
        self::$loaded[$name] = $this;


    }

    public static function getRank($name){
        if(!$name) return null;
        if(isset(self::$loaded[$name])) return self::$loaded[$name];
        return new Rank($name);
    }
}
