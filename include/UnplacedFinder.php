<?php

/**
 * 
 * Thing with the functionality to suggest 
 * names with a confidence level
 * 
 */
class UnplacedFinder{


    public $name = null;
    public array $unplacedNames = array();
    public int $totalUnplacedNames = 0;
    public int $offset = 0;
    public int $limit = 0;
    public bool $includeDeprecated = false;

    /**
     * Initializing sets it up and does all the work
     * properties are then populated to be called.
     * 
     */
    public function __construct($id, $offset = 0, $limit = 1000, $include_deprecated = false){

        // load the name
        
        $this->name = Name::getName($id);
        if(!$this->name) return; // cop out if we can't load a name


        // set our params
        $this->offset = $offset;
        $this->limit = $limit;
        $this->includeDeprecated = $include_deprecated;

        // only populate if we are genus below or a family
        if($this->name->getRank() == 'genus' || $this->name->getRank() == 'species' ){
            $this->unplacedBelowGenus();
        }else if($this->name->getRank() == 'family' ){
            $this->unplacedFamily();
        }

    }

    private function unplacedBelowGenus(){

        global $mysqli;

        $sql = " FROM `names` AS n LEFT JOIN `taxon_names` AS tn ON n.id = tn.name_id WHERE tn.id IS NULL ";

        // add genus 
        if($this->name->getRank() == 'genus'){
            // we are a genus so list names with our name in their genus part
           $sql .= " AND n.genus = '{$this->name->getNameString()}'";
        }else{
            // we are a species so list names with our name in their species part
            // and our genus part in their genus part
            $sql .= " AND n.genus = '{$this->name->getGenusString()}'";
            $sql .= " AND n.species = '{$this->name->getNameString()}'";
        }
 

        // filter for deprecated
        if(!$this->includeDeprecated){
            $sql .= " AND n.`status` != 'deprecated'";
        }

        // do the count
        $response = $mysqli->query("SELECT count(*) as num " . $sql);
        $row = $response->fetch_assoc();
        $this->totalUnplacedNames = $row['num'];
        $response->close();

        // actually fetch the list - if we have more than 0 in it
        if($this->totalUnplacedNames > 0){
            $sql = "SELECT n.id as id " . $sql . " ORDER BY name_alpha LIMIT " . preg_replace('/[^0-9]/', '', $this->limit) . " OFFSET " . preg_replace('/[^0-9]/', '', $this->offset);
            $response = $mysqli->query($sql);
//            error_log($sql);
            while ($row = $response->fetch_assoc()) {
                $this->unplacedNames[] = Name::getName($row['id']);
            }
        }

    }

    public function unplacedFamily(){
       
        global $mysqli;

        $sql = " FROM `names` AS n LEFT JOIN `taxon_names` AS tn ON n.id = tn.name_id JOIN `matching_hints` AS h ON n.id = h.name_id WHERE tn.id IS NULL AND h.hint = '{$this->name->getNameString()}' ";
         
        // filter for deprecated
        if(!$this->includeDeprecated){
            $sql .= " AND n.`status` != 'deprecated'";
        }

        // do the count
        error_log($sql);
        $response = $mysqli->query("SELECT count(*) as num " . $sql);
        $row = $response->fetch_assoc();
        $this->totalUnplacedNames = $row['num'];
        $response->close();

        // actually fetch the list - if we have more than 0 in it
        if($this->totalUnplacedNames > 0){
            $sql = "SELECT n.id as id " . $sql . " ORDER BY name_alpha LIMIT " . preg_replace('/[^0-9]/', '', $this->limit) . " OFFSET " . preg_replace('/[^0-9]/', '', $this->offset);
            $response = $mysqli->query($sql);
            while ($row = $response->fetch_assoc()) {
                $this->unplacedNames[] = Name::getName($row['id']);
            }
        }

    }


}

