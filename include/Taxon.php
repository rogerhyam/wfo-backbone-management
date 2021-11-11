<?php

class Taxon extends WfoDbObject{


    // We need to be careful we run 
    // singletons on primary objects so only create
    // Taxa using factory methods.
    public ?Name $name = null;
    public ?Taxon $parent = null;
    public ?Array $children = null;
    public ?Array $synonyms = null;

    protected static $loaded = array();

    /**
     * Create an instance of a Taxon
     * Don't call this directly use the getTaxonForName or getById or getRootTaxon
     * factory methods only.
     * 
     * @param int $db_id The primary key of the taxon in the database
     * If an array then set of DwC values.
     */
    public function __construct($db_id){
        if($db_id != -1){
            $this->id = $db_id;
            $this->load();
            self::$loaded[$this->id] = $this;
        }
    }

    /**
     * Will load the taxon from the db if it has a db id
     * Will throw error if the taxon doesn't have a db id yet
     * 
     */
    public function load(){

        global $mysqli;
        if(!$this->id) throw new ErrorException("You can't call load on a Taxon that doesn't have an db id yet");

        $sql = "SELECT * FROM taxa as t left join taxon_names as tn on t.id = tn.taxon_id WHERE t.id = {$this->id}";
        $result = $mysqli->query($sql);
        if($mysqli->error) echo $mysqli->error; // should only have prepare errors during dev
        // echo $sql; exit;
        $row = $result->fetch_assoc();

        // get the name via the taxonNames table
        if($row['name_id']) $this->name = Name::getName((int)$row['name_id']);

        // This is a good one. Be careful getting the parent because if you are at the root you are the parent. Infinite loop find you here after hours of searching.
        if($row['parent_id'] == $this->id){
            $this->parent = $this;
        }else{
            $this->parent = Taxon::getById($row['parent_id']);
        }
        
        $this->comment = $row['comment'];
        $this->issue = $row['issue'];
        $this->user_id = $row['user_id'];
        $this->created = $row['created'];
        $this->modified = $row['modified'];

        $result->close();

        // fixme - load synonyms?
    
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

    /**
     * Returns the taxon associated with a name object
     * the name may be the accepted name or a synonym in this taxon
     *
     * @return Taxon or null if one isn't associated with the name.
     * 
     */
    public static function getTaxonForName($name){

        global $mysqli;

        if(!$name->getId()) throw new ErrorException("You can't call load on a Taxon with a Name that doesn't have an id i.e. has not been saved to the db");

        // is it in the names yet (we could possible check if it is in self::loaded before going to the db if this proves slow)
        $result = $mysqli->query("SELECT `taxon_id` FROM `taxon_names` WHERE `name_id` = {$name->getId()}");
        
        if($result->num_rows > 1) throw new ErrorException("Something terrible happened. There are multiple taxa with the same name id {$name->getId()}");
        
        // found a taxon for the name so return it
        if($result->num_rows == 1){
            $row = $result->fetch_assoc();
            return Taxon::getById($row['taxon_id']);
        }

        // can't get a taxon for this name so create an empty one and set this name in it
        // if they do checkIntegrity() or save() it will control for this name not being available
        $taxon = new Taxon(-1);
        $taxon->setAcceptedName($name);
        return $taxon;

    }

    /**
     * 
     * Returns the root taxon of all taxa
     * 
     */
    public static function getRootTaxon(){
        
        global $mysqli;
        
        // root taxon is the one which is its own parent 
        $result = $mysqli->query("SELECT `id` FROM `taxa` WHERE `id` = `parent_id` ");
        if($result->num_rows > 1) throw new ErrorException("Something terrible happened! There are multiple root taxa in the database.");
        if($result->num_rows < 1) throw new ErrorException("Something terrible happened! There is no root taxon in the database");

        $row = $result->fetch_assoc();

        return Taxon::getById($row['id']);

    
    }

    /*

        G E T T E R  &  S E T T E R  M E T H O D S

    */

    /**
     * Sets the name of this taxon.
     * 
     * @param Name A name object
     */

    public function setAcceptedName($name){
        $this->name = $name;
    }

    /**
     * Gets the accepted name of this taxon
     * 
     * @return Name the name object
     */
    public function getAcceptedName(){
        return $this->name;
    }

    public function setParent($parent){
        $this->parent = $parent;
    }
    
    public function getParent(){
        return $this->parent;
    }

    public function checkIntegrity(){

        global $mysqli;

        $out = array();
        $out['ok'] = true; 

        // Call integrity check on the accepted name?

        // is the name in use as the accepted name of another taxon?

        // I should be a correct lower rank to my parent

        // I should be same rank as my siblings
        
        // I should have a name or I should be unspecified

        // There should only be one unspecified taxon among siblings

        // Unspecified taxa should disappear if they don't have children but not if they are autonyms


        return $out;
    }

    public function save(){

        global $mysqli;

        // check validity and refuse to proceed if we aren't valid
        $check = $this->checkIntegrity();
        if(!$check['ok']) return false;

        // Integrity checks out so it is OK to proceed

        // note setting of accepted name is done separately at the end
        // of the process to be sure we have a db id and that we can make
        // any other changes necessary in taxon_names

        if($this->id){

            // UPDATING
            // we have a db id so we are updating
            
            $stmt = $mysqli->prepare("UPDATE `taxa` 
                SET 
                `parent_id` = ?,
                `user_id` = ?,
                `comment` = ?, 
                `issue` = ?,
                `source` = ? 
                WHERE 
                `id` = ?"
            );
            if($mysqli->error) echo $mysqli->error; // should only have prepare errors during dev
            $parent_id = $this->parent->getId();
             $stmt->bind_param("iisssi",
                $parent_id,
                $this->user_id,
                $this->comment,
                $this->issue,
                $this->source,
                $this->id
            );
            if(!$stmt->execute()){
                throw new ErrorException($mysqli->error);
                return false; // let them know we failed
            }

        }else{

            // CREATING
            // we don't have a db id so we are creating

             $stmt = $mysqli->prepare("INSERT 
                INTO `taxa` (`parent_id`, `user_id`, `comment`,`issue`,`source`) 
                VALUES (?,?,?,?,?)");
            if($mysqli->error) echo $mysqli->error; // should only have prepare errors during dev
            $parent_id = $this->parent->getId();

            $stmt->bind_param("iisss",
                $parent_id,
                $this->user_id,
                $this->comment,
                $this->issue,
                $this->source
            );
            if(!$stmt->execute()){
                throw new ErrorException($mysqli->error);
                return false; // let them know we failed
            }else{
                // get our db id
                $this->id = $mysqli->insert_id;
            }

        }

        // close the statement we opened in one of the legs above
        $stmt->close();

        // assign the accepted name whether we have created or updated
        $this->assignAcceptedName($this->name);


    }


    /**
     * Not to be confused associated public functions
     * 
     * This makes changes to the taxa table to map
     * a taxon_names row to the taxa.taxon_name_id field
     * 
     * This will call assignName first to make sure name is in table
     * 
     * 
     */
    private function assignAcceptedName($name){

        global $mysqli;

        $taxon_names_id = $this->assignName($name);

        if($taxon_names_id){
            $result = $mysqli->query("UPDATE taxa SET taxon_name_id = {$taxon_names_id} WHERE id = {$this->id}");
            if($mysqli->affected_rows != 1){
                throw new ErrorException("Failed to update taxa with taxon_name_id {$taxon_names_id} for taxon id {$this->id}. Rows affected: {$mysqli->affected_rows}");
                return false;
            }else{
                return true;
            }
        }else{
            // exceptions will have been thrown by assignName
            return false;
        }

    }

    /**
     * Not to be confused with public method setAcceptedName
     * This actually does the work of joining the names
     * up.
     * 
     * This can be used to assign accepted names and synonyms 
     * as it just makes necessary changes to the taxon_names table
     * 
     * @return int the id of the row in the taxon_names table or false on failure
     * 
     */
    private function assignName($name){
        
        global $mysqli;
        
        // is the name already in use in the taxon_names table?
        $result = $mysqli->query("SELECT * FROM taxon_names WHERE name_id = {$name->id}");
        if($result->num_rows > 1) throw new ErrorException("Something terrible happened! There are multiple entries in taxon_names for name_id {$name->id}.");
        if($result->num_rows == 0){
            // the name is not assigned to any taxon we can go ahead and create the row
            $result = $mysqli->query("INSERT INTO taxon_names (taxon_id, name_id) VALUES ({$this->id}, {$name->id})");
            if($mysqli->affected_rows == 1){
                return $mysqli->insert_id;
            }else{
                throw new ErrorException("Failed to create taxon_names row for taxon_id {$this->id} and name_id {$name->id}");
                return false;
            }

        }else{

            // the name is assigned to something.
            $row = $mysqli->fetch_assoc();

            // is it us? If so nothing to do
            if($row['taxon_id'] == $this->id) return $row['id'];

            // it is not so we need to highjack it - but first we double check it isn't in use as an accepted name of another taxon
            $result = $mysqli->query("SELECT * FROM taxa WHERE taxon_name_id = {$row['id']} AND id != {$this->id}");
            if($result->num_rows > 0) throw new ErrorException("Trying to assign taxon_name {$row['id']} to {$this->id} when it is already in use as an accepted taxon.");

            // now the highjack
            $mysqli->query("UPDATE taxon_names SET taxon_id = {$this->id} WHERE id = {$row['id']}");
             if($mysqli->affected_rows == 1){
                return $row['id'];
            }else{
                throw new ErrorException("Failed to update taxon_names row {$row['id']} for taxon_id {$this->id} and name_id {$name->id}");
                return false;
            }
            

        }


    }

    

    // ------------ R E L A T I O N S ----------------

    /**
     * We could do this in a more efficient manner but get it working
     * first. Just load all the kids
     * 
     */
    public function getChildren(){

        global $mysqli;

        if($this->children === null){
            $this->children = array();
            $result = $mysqli->query("SELECT id FROM taxa WHERE parent_id = {$this->id} AND id != {$this->id}"); // don't get the root
            while($row = $result->fetch_assoc()){
                $this->children[] = Taxon::getById($row['id']);
            }
        }

        return $this->children;

    }

    public function isRoot(){
        return $this->parent == $this;
    }


} // Taxon