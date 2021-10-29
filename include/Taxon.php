<?php

class Taxon{


    // We need to be careful we run 
    // singletons on primary objects so only create
    // Taxa using factory methods.
    public string $id;
    protected static $loaded = array();

    /**
     * Create an instance of a Taxon
     * Don't call this directly use the getById or getByDwC 
     * factory methods only.
     * 
     * @param mixed $init_val If it is a int then presumed to be a database id (not WFO id).
     * If an array then set of DwC values.
     */
    public function __construct($init_val){

        // FIXME: test for int or array and load appropriate values.

        $this->id = uniqid('wfo-');
        self::$loaded[$this->id] = $this;
    }

    /**
     * Fetch a taxon by its database id
     * 
     * @param int $taxon_id The primary key of the taxon in the taxa database table.
     * @return Taxon
     */
    public static function getById($taxon_id){
        
        if(isset(self::$loaded[$taxon_id])){
            return self::$loaded[$taxon_id];
        }

        return new Taxon($taxon_id);
        
    }
        

} // Taxon