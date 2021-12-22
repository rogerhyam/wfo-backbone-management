<?php

/**
 * 
 * The logic for where we can and can't place names
 * Plus the ability to actually move them.
 * 
 */
class NamePlacer{

    /*

### Placement Actions

There are five actions that can be taken to change a names placement

1. __Raise to accepted name__ A name that is a synonym or not yet placed in the taxonomy and has a nomenclatural status or Valid, Conserved or Sanctioned can become the accepted name of a taxon.
1. __Sink into synonym__ A name that is the accepted name of a taxon (which doesn't have children or synonyms) or has not yet been placed in the taxonomy can become a synonym in an accepted taxon.
1. __Change parent taxon__ A name that is the accepted name of a taxon can be moved to another part of the taxonomy.
1. __Change accepted taxon__ A name that is a synonym of one taxon can be moved to become the synonym of another taxon.
1. __Remove from taxonomy__ A name that forms part of the taxon as the accepted name of a taxon (which doesn't have children or synonyms) or is a synonym can be removed from the taxonomy.

### Placement Destinations

There are three rules that govern where a name can be placed in the taxonomy

1. __Nomenclatural Status__ Deprecated names can't be placed in the taxonomy at all. Names of all other statuses can be synonyms. Valid, Conserved and Sanctioned names can be accepted names of taxa.  
1. __Congruent Ranks__ The rank of a child taxon must be one of the accepted ranks of the parent according to the ranks table. e.g. a subspecies can't be a direct child of a genus or family.
1. __Congruent Name Parts__ The name parts of the parent taxon must agree with the genus string and species string part of the name. e.g. a species can only be in a genus which has a name-string that matches its genus-string and a subspecies can only be in a species that has the name-string and genus-string that agrees with its own species-string and genus-string.
 
*/

    public Name $name;
    public ?Taxon $taxon;

    // available actions
    public bool $canBeRaised = false;
    public bool $canBeSunk = false;
    public bool $canChangeParent = false;
    public bool $canChangeAccepted = false;
    public bool $canBeRemoved = false;

    public string $action = 'none';
    public string $filter = '';

    public bool $filterNeeded = false;
    public array $narrative = array();
    public array $possibleTaxa = array();

    public static array $ACTION_TYPES = array("none", "raise", "sink", "change_parent", "change_accepted", "remove");


    /**
     * Create an instance of the NamePlacer for a particular
     * name identified either but a WFO ID or by an internal database ID (interger)
     * 
     * @param int $init_value either a primary key in the database or a wfo-id for a name.
     */
    public function __construct($name_id, $action, $filter){

        $this->name = Name::getName($name_id);
        $taxon = Taxon::getTaxonForName($this->name);

        // we pass the action back out again
        $this->action = $action;
        $this->filter = $filter;

        // a blank taxon is created if the name is not placed (it is how we create new ones)
        // and we don't want that here
        if($taxon->getId()){
            $this->taxon = $taxon;
        }else{
            $this->taxon = null;
        }

        // We have to break out the logic into functions
        // or it just gets too confusing to manage
        $this->setCanBeRaised();
        $this->setCanBeSunk();
        $this->setCanChangeParent();
        $this->setCanChangeAccepted();
        $this->setCanBeRemoved();

        // then where we are headed
        switch ($this->action) {
     
            // if we are going to be a accepted by raising or 
            // moving then possibilities are the same
            case "raise":
                if($this->canBeRaised) $this->setPossibleAcceptedLocations();
                else throw new ErrorException("Calling for raise on non name that can't be raised.");
                break;

            case "change_parent":
                if($this->canChangeParent) $this->setPossibleAcceptedLocations();
                else throw new ErrorException("Calling to change parent on name that can't be moved.");
                break;

            case "sink":
                if($this->canBeSunk) $this->setPossibleSynonymLocations();
                else throw new ErrorException("Calling to sink name that can't be sunk.");
                break;

            case "change_accepted":
                if($this->canChangeAccepted) $this->setPossibleSynonymLocations();
                else throw new ErrorException("Calling to move a synonym of an name that can't be moved.");
                break;
            
            case "remove":
                if(!$this->canBeRemoved) throw new ErrorException("Calling remove on a name that can't be removed.");
                break;
            
            // default includes actions none 
            // no need for taxa
            default:
                $this->possibleTaxa = array();
                break;
        }        

    }

    /**
     * A name that is a synonym or not yet placed in the taxonomy 
     * and has a nomenclatural status or Valid, Conserved or Sanctioned
     * can become the accepted name of a taxon.
     */
    private function setCanBeRaised(){
        
        // correct status
        $acceptable_statuses = array('valid', 'conserved', 'sanctioned');
        if(!in_array($this->name->getStatus(), $acceptable_statuses)){
            $this->canBeRaised = false;
            return;
        }

        // if we are unplaced
        if(!$this->taxon){
            $this->canBeRaised = true;
            return;
        }

        // if we are placed but a synonym
        if($this->taxon->getAcceptedName() == $this->name){
            // we are an accepted name so can't be raised to be one
            $this->canBeRaised = false;
        }else{
            // we are a synonym with the right status so can be raised
            $this->canBeRaised = true;
        }

    }

    /**
     * A name that is the accepted name of a taxon
     * (which doesn't have children or synonyms) 
     * or has not yet been placed in the taxonomy 
     * can become a synonym in an accepted taxon.
     */
    private function setCanBeSunk(){

        // can't sink a deprecated name into taxonomy
        if($this->name->getStatus() == 'deprecated'){
            $this->canBeSunk = false;
            return;
        }

        // are we dealing with an already placed name?
        if($this->taxon){

            // if it placed as a synonym it can't be sunk
            if($this->taxon->getAcceptedName() != $this->name){
                // we are an accepted name so can't be raised to be one
                $this->canBeSunk = false;
                return;
            }

            // we are an accepted taxon so do we have kids or synonyms
            if($this->taxon->getChildren() || $this->taxon->getSynonyms()){
                $this->canBeSunk = false;
                return;
            }

        }

        // everything else can be sunk into taxonomy as a synonym
        $this->canBeSunk = true;

    }

    /**
     * A name that is the accepted name of a taxon
     * can be moved to another part of the taxonomy.
     */
    private function setCanChangeParent(){

        // if it isn't in the taxonomy it can't be moved
        if(!$this->taxon){
            $this->canChangeParent = false;
            return;
        }

        // if it is a synonym it can't be moved
         if($this->taxon->getAcceptedName() != $this->name){
            $this->canChangeParent = false;
            return;
         }

         // we are an accepted name so we can be moved
         // whether there is anywhere to go is another matter!
         $this->canChangeParent = true;


    }

    /**
     * A name that is a synonym of one taxon can be moved to become the synonym of another taxon.
     */
    private function setCanChangeAccepted(){

       // if it isn't in the taxonomy it can't be moved
        if(!$this->taxon){
            $this->canChangeAccepted = false;
            return;
        }

        // If we are an accepted name we can't be moved as a synonym
         if($this->taxon->getAcceptedName() == $this->name){
            $this->canChangeAccepted = false;
            return;
         }

         // we are a synonym so we can be moved just about anywhere
         $this->canChangeAccepted = true;

    }

    private function setCanBeRemoved(){

        // if it is not in the taxonomy placed it can't be removed.
        if(!$this->taxon){
            $this->canBeRemoved = false;
            return;
        }

        // are we an accepted name
        if($this->taxon->getAcceptedName() == $this->name){

             // if it has synonyms or children it can't be removed
            if( $this->taxon->getChildren()){
                $this->canBeRemoved = false;
            }elseif($this->taxon->getSynonyms()){
                $this->canBeRemoved = false;
            }else{
                $this->canBeRemoved = true;
            }

        }else{
            // not accepted - must be synonym
            $this->canBeRemoved = true;
        }

    }

    /**
     * 
     * Where could this name be placed
     * as the name of an accepted taxon
     * 
     */
    private function setPossibleAcceptedLocations(){

        global $mysqli;

        // for starters options are restricted to taxa that
        // are of the correct rank
        $sql = "SELECT n.id AS name_id, t.id AS taxon_id FROM `names` AS n
                 JOIN taxon_names AS tn ON tn.name_id = n.id
                 JOIN taxa AS t ON t.taxon_name_id = tn.id
                 WHERE n.rank IN ({$this->getPossibleParentRanks(true)}) "; 

        // further restrictions are added if the name has genus or species strings
        if( $this->name->getGenusString() ){
            
            if( $this->name->getSpeciesString() ){
                // add the genus and species filters
                $sql .= " AND `genus` = '{$this->name->getGenusString()}' AND `name` = '{$this->name->getSpeciesString()}' ";
            }else{
                // just add the genus filter
                $sql .= " AND `name` = '{$this->name->getGenusString()}' ";
            }

        }

        // then we filter it if we have a filter
        if($this->filter){
            $sql .= " AND `name_alpha` LIKE '{$this->filter}%' ";
        }

        // order and limit
        $sql .= " ORDER BY n.name_alpha LIMIT 30";

        // run the query
        $response = $mysqli->query($sql);

        // if we have a full 30 rows then presume their may be more and keep the need for a filter
        if($response->num_rows == 30) $this->filterNeeded = true;
        else $this->filterNeeded = false;

        // load these into the possible destination
        while($row = $response->fetch_assoc()){
            $this->possibleTaxa[] = Taxon::getById($row['taxon_id']);
        }
        
    }

    /**
     * Where can this be placed as a synonym
     * = just about any accepted taxon!
     */
    private function setPossibleSynonymLocations(){

         global $mysqli;

        // for starters options are restricted to taxa that
        // are of the correct rank
        $sql = "SELECT n.id AS name_id, t.id AS taxon_id FROM `names` AS n
                 JOIN taxon_names AS tn ON tn.name_id = n.id
                 JOIN taxa AS t ON t.taxon_name_id = tn.id
                 AND `name_alpha` LIKE '{$this->filter}%' 
                 ORDER BY n.name_alpha LIMIT 30";
        
        // run the query
        $response = $mysqli->query($sql);

        // if we have a full 30 rows then presume their may be more and keep the need for a filter
        if($response->num_rows == 30) $this->filterNeeded = true;
        else $this->filterNeeded = false;

        // load these into the possible destination
        while($row = $response->fetch_assoc()){
            $this->possibleTaxa[] = Taxon::getById($row['taxon_id']);
        }

    }

    private function getPossibleParentRanks($as_string = false){

        global $ranks_table;

        $possibles = array();

        foreach ($ranks_table as $rank_name => $rank_details) {
            if( in_array($this->name->getRank(), $rank_details['children']) ){
                $possibles[] = $rank_name;
            }
        }

        if($as_string){
            if($possibles){
                return "'" . implode("','", $possibles) . "'";
            }else{
                return "";
            }
        }else{
            return $possibles;
        }
        
    }

    public function getPossiblePlaces($filter = false){

    }

}