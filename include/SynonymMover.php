<?php
/*
    The logic required to safely move or unplace
    all the synonyms of a taxon
    See also NamePlacer that can do one at a time.

    Better to have this separate that make the NamePlacer logic even more complex
*/
class SynonymMover{

    public Name $name;
    public ?Taxon $taxon;
    public string $filter = '';
    public array $possibleTaxa = array();


    /**
     * Will return a list of taxa that the synonyms could be moved to!
     * 
     * @param $name_id is name of the existing name so we don't return that
     * @param $filter is the starting letters of the name
     */
    public function __construct($name_id, $filter = ''){

        global $mysqli;

        // get a handle on the taxon the names are a synonym of
        $this->name = Name::getName($name_id);
        $taxon = Taxon::getTaxonForName($this->name);
        if($taxon->getId()) $this->taxon = $taxon;
        else $this->taxon = null;

        $this->filter = $mysqli->real_escape_string($filter);


    }

    /**
     * A list of the possible taxa that the synonyms
     * could be moved to. This includes taxa the
     * user might not have permission to move things to.
     * 
     */
    public function getPossibleTaxa(){

        global $mysqli;

        $sql = "SELECT n.id AS name_id, t.id AS taxon_id FROM `names` AS n
            JOIN taxon_names AS tn ON tn.name_id = n.id
            JOIN taxa AS t ON t.taxon_name_id = tn.id
            WHERE `name_alpha` LIKE '{$this->filter}%' 
            ORDER BY `name_alpha`
            LIMIT 100;";

        $response = $mysqli->query($sql);
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        $response->close();

        $out = array();
        foreach ($rows as $row) {
            $out[] = Taxon::getById($row['taxon_id']);
        }
        return $out;

    }

    public function moveAllSynonymsTo($destination_wfo_id){

        

        // firstly check we have a source taxon and can edit it



        // if we don't have a destination set then we unplace them all
        if(!$destination_wfo_id){

            // just run through the synonyms and unplace them


        }else{

            // we are in the land of moving the synonyms
            // Check the destination is OK and we can edit it.

        }




    }


}