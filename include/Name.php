<?php

/**
 * 
 * An object representing purely nomenclatural data.
 * 
 * Basic, non confrontational cleaning is done in the setter methods e.g. trim and capitals.
 * Full integrity checking is done when checkIntegrity is called.
 * 
 */
class Name extends WfoDbObject{

    private ?string $prescribed_wfo_id = null;
    private ?string $rank = null; // enumeration
    private ?string $name = null; // 100 char max
    private ?string $genus = null; // 100 char max
    private ?string $species = null; // 100 char max
    private ?string $authors = null; // 250 char max
    private ?int $year = null;
    private ?string $status = null; // enumeration
    private ?string $citation_micro = null; // 800 char
    private ?string $citation_full = null; // 1000 char
    private ?string $citation_id = null; // 45
    private ?string $publication_id = null; // 45
    private ?int $basionym_id = null;

    private Array $all_ids = array();
    private Array $hints = array();

    private Array $status_enumeration = array(); // list of possible status values
    private Array $rank_enumeration = array(); // list of all possible ranks


    /**
     * Create an instance of a Name
     * Don't call this directly use the getName() factory method
     * only.
     * 
     * @param int $init_value either a primary key in the database or -1
     */
    public function __construct($init_value){
        // unless we have been passed a positive int we load it all 
        // from the database.
        if($init_value != -1){
            $this->id = $init_value;
            $this->load();
            self::$loaded[$init_value] = $this;
        }
    }

    /**
     * Will load all values from db
     * overwrite anything already in the object.
     * 
     * 
     */
    public function load(){
        
        global $mysqli;

        if(!$this->id) throw new ErrorException("You can't call load on a Name that doesn't have an db id yet");

        $result = $mysqli->query("SELECT n.*, i.value as 'prescribed_wfo_id' FROM `names` as n JOIN `identifiers` as i on i.`name_id` = n.`id` WHERE n.`id` = {$this->id} ");
        echo $mysqli->error;
        $row = $result->fetch_assoc();

        // set all the fields individually - more data knitting
        $this->prescribed_wfo_id = $row['prescribed_wfo_id'];
        $this->rank = $row['rank'];
        $this->name = $row['name'];
        $this->genus = $row['genus'];
        $this->species = $row['species'];
        $this->authors = $row['authors'];
        $this->year = $row['year'];
        $this->status = $row['status'];
        $this->citation_micro = $row['citation_micro'];
        $this->citation_full = $row['citation_full'];
        $this->citation_id = $row['citation_id'];
        $this->publication_id = $row['publication_id'];
        $this->basionym_id = $row['basionym_id'];
        $this->comment = $row['comment'];
        $this->issue = $row['issue'];
        $this->user_id = $row['user_id'];
        $this->source = $row['source'];
        $this->created = $row['created'];
        $this->modified = $row['modified'];
        $this->created = $row['created'];

        $result->close();

        $this->loadHints();
        $this->loadIdentifiers();

    }


    /**
     * Always use this function to get an instance of a taxon
     * 
     * @param mixed An int if it is the DB id of the name or a string if it is the WFO ID
     */
    public static function getName($init_value){

        global $mysqli;

        // easy case is that this is the db primary key
        if(filter_var($init_value, FILTER_VALIDATE_INT)){

            // we are singleton so if it is already loaded return that.
            if(isset(self::$loaded[$init_value])){
                return self::$loaded[$init_value];
            }else{
                return new Name($init_value);
            }

        }elseif(is_string($init_value) && preg_match('/wfo-[0-9]{10}/', $init_value)){

                // we've been passed a wfo id - we don't care if this is the prescribed wfo-id or one of the duplicates at this stage
                $sql = "SELECT  * FROM `identifiers` as i WHERE i.`kind` = 'wfo' and i.`value` = '$init_value'";
                $result = $mysqli->query($sql);

                // we have to have one row or we give up
                if($result->num_rows == 1){
                    
                    // load the name id
                    $row = $result->fetch_assoc();
                    $name_id = $row['name_id'];

                    if($name_id == null){
                        error_log("No name_id for WFO-ID $init_value this is bad.");
                        return null;
                    }

                    // return the cached one if we have one or call for 
                    // a new one
                    if(isset(self::$loaded[$name_id])){
                        return self::$loaded[$name_id];
                    }else{
                        return new Name($name_id);
                    }
                    
                }else{

                    // we've been passed a well formed wfo-id but it isn't in the database
                    // return an empty object except for the wfo-id.
                    $name = new Name(-1);
                    $name->setPrescribedWfoId($init_value);
                    return $name;
                }
                $result->close();

        }elseif($init_value == -1){
            // passing -1 means you want a new name
            return new Name(-1);
        }else{
            // don't know what you asked for but you get nothing!
            error_log("No name for init value: " . $init_value);
            return null;
        }

    }

    /*

        G E T T E R  &  S E T T E R  M E T H O D S

    */

    public function setRank($rank){
        $this->rank = strtolower(trim($rank));
    }
    public function getRank(){
        return $this->rank;
    }

    public function setNameString($name){
        $this->name = trim($name);
    }
    public function getNameString(){
        return $this->name;
    }

    public function setGenusString($genus){
        $this->genus = ucfirst(mb_strtolower(trim($genus)));
    }
    public function getGenusString(){
        return $this->genus;
    }

    public function setSpeciesString($species){
        $this->species = mb_strtolower(trim($species));
    }
    public function getSpeciesString(){
        return $this->species;
    }

    public function setAuthorsString($authors){
        $this->authors = trim($authors);
    }
    public function getAuthorsString(){
        return $this->authors;
    }

    public function setStatus($status){
        $this->status = mb_strtolower(trim($status));
    }
    public function getStatus(){
        return $this->status;
    }


    public function isAutonym(){

        global $ranks_table;
        
        // get the ordinal position of the ranks (they count up towards subvar)
        $genus_index = array_search('genus', array_keys($ranks_table));
        $species_index = array_search('species', array_keys($ranks_table));
        $rank_index = array_search($this->getRank(), array_keys($ranks_table));

        // autonyms only apply to ranks below genus that aren't the species
        if($rank_index <= $genus_index || $rank_index == $species_index) return false;

        // autonyms have no author string
        if($this->getAuthorsString()) return false;

        // autonyms at ranks below species (subsp, var and form) have the same name as the species name
        if($rank_index > $species_index &&  $this->name == $this->species) return true;

        // autonyms at other ranks (those left over!) have the same name as the genus
        if($this->name == $this->genus) return true;

        // we may be at autonym rank level but we don't have matching names so we aren't
        return false;

    }

    /**
     * 
     * Set the basionym for this Name
     * Note that this will allow the setting on any name as the basionym of the 
     * name for any other name but that integrity checks will be carried out before saving.
     * 
     * @param $basionym A name object that is to be the new basionym or NULL
     * 
     */
    public function setBasionym($basionym){

        // ok to set null
        if($basionym === NULL){
            $this->basionym_id = NULL;
        }

        // we are given a value so check it is an OK one
        if(!is_object($basionym)) throw new ErrorException("Trying to setBasionym with non-object value");
        if(!$basionym instanceof Name) throw new ErrorException("Trying to setBasionym with object not of type Name");
        if(!$basionym->getId()) throw new ErrorException("Trying to setBasionym uninitialized Name");

        // all good
        $this->basionym_id = $basionym->getId();

    }

    /**
     * Returns the currently set basionym for this Name
     * Note that this could be potentially be bad value unless the integrity of the Name has
     * been checked by calling checkIntegrity() or save() or load().
     * 
     */
    public function getBasionym(){
        if($this->basionym_id === NULL) return NULL;
        return Name::getName($this->basionym_id);
    }

    public function setCitationMicro($cite){
        $this->citation_micro = $cite;
    }

    public function getCitationMicro(){
        return $this->citation_micro;
    }

    public function setCitationId($id){
        $this->citation_id = $id;
    }

    public function getCitationId(){
        return $this->citation_id;
    }

    public function setYear($year){
        $this->year = (int)$year;
    }

    public function getYear(){
        return $this->year;
    }

    public function getFullNameString($italics = true, $authors = true, $abbreviate_rank = true, $abbreviate_genus = false){

        global $ranks_table;

        $out = "";

        $my_level = array_search($this->rank, array_keys($ranks_table));
        $genus_level = array_search('genus', array_keys($ranks_table));
        $species_level = array_search('species', array_keys($ranks_table));

        if($my_level > $genus_level){            
            
            // we are below genus level

            // always include the genus
            if($abbreviate_genus){
                $genus = substr($this->genus, 0, 1) . ".";
            }else{
                $genus = $this->genus;
            }

            if($italics){
                $out .= "<i>$genus</i>";
            }else{
                $out .= $genus;
            }

            // if we are below the species we include the species epithet
            if($my_level > $species_level){
                if($italics){
                    $out .= " <i>{$this->species}</i>";
                }else{
                    $out .= " {$this->species}";
                }
            }

            // next the rank for non species
            if($my_level != $species_level){
                if($abbreviate_rank){
                    $rank = $ranks_table[$this->rank]['abbreviation'];
                }else{
                    $rank = ucfirst($this->rank);
                }
                $out .= " $rank";
            }

            // now the actual name
             if($italics){
                $out .= " <i>{$this->name}</i>";
            }else{
                $out .= " {$this->name}";
            }

        }elseif($my_level == $genus_level){
            // we are a genus
            if($italics){
                $out .= "<i>{$this->name}</i>";
            }else{
                $out .= $this->name;
            }
        }else{
            // we are above genus level - easy!
            $out .= $this->name;
        }
    

        if($authors){
            $out .= " {$this->authors}";
        }

        return $out;

    }

    /**
     * 
     * Sets a hint word to use when doing name matching
     * typically this will be a family name
     * n.b. This can only be called once the name has been saved once 
     * and has a db id otherwise it will throw an Exception
     * 
     * @param string $hint The word to be added to the matching_hints table
     * @return boolean True if hint is added. False if already present. (Exception thrown otherwise)
     * 
     */
    public function addHint($hint){

        global $mysqli;

        if(!$this->id) throw new ErrorException("Attempt to add hint to Name which doesn't have a db id.");

        // could maybe improve this next bit with an insert if does not exist - but indexes and keys might be tricky

        // does it exist?
        $result = $mysqli->query("SELECT * FROM `matching_hints` WHERE `name_id` = {$this->id} AND `hint` = '$hint' ");
        if($result->num_rows > 0) return false; // don't need to do anything
        $result->close();

        // add it in
        $mysqli->query("INSERT INTO `matching_hints`  (`name_id`, `hint`) VALUES ({$this->id}, '$hint')");
        if($mysqli->affected_rows == 1 ) return true; 
        else throw new ErrorException("Failed to add hint " . $mysqli->error);
  

    }

    /**
     * Removes this hint word for this name from the matching_hints table 
     * n.b. This can only be called once the name has been saved once 
     * and has a db id otherwise it will throw an Exception
     * 
     * @param string $hint The word to be removed to the matching_hints table
     * @return boolean True if hint is removed. False if it was never there. (Exception thrown otherwise)
     */
    public function removeHint($hint){

        global $mysqli;

        if(!$this->id) throw new ErrorException("Attempt to remove hint from Name which doesn't have a db id.");

        // we do this by primary key so we can keep safety lock on and don't accidentally delete more than we intend
        $result = $mysqli->query("SELECT * FROM `matching_hints` WHERE `name_id` = {$this->id} AND `hint` = '$hint' ");
        if($result->num_rows == 0){
            return false; // don't need to do anything
        }elseif($result->num_rows == 1){
            $row = $result->fetch_assoc();
            $hint_id = $row['id'];
        }else{
            throw new ErrorException("Attempt to remove hint ($hint for {$this->id}) matches multiple hints. This should not be possible.");
        }
        $result->close();

        $result = $mysqli->query("DELETE FROM `matching_hints` WHERE `id` = $hint_id ");
        if($mysqli->affected_rows == 1 ) return true; 
        else throw new ErrorException("Failed to remove hint " . $mysqli->error);
        $result->close();

    }

    public function getHints(){
        return $this->hints;
    }

    public function loadHints(){

        global $mysqli;

        if(!$this->id) throw new ErrorException("Attempt to load hints for Name which doesn't have a db id.");

        $this->hints = array();
        $result = $mysqli->query("SELECT * FROM `matching_hints` WHERE `name_id` = {$this->id} ");
        while($row = $result->fetch_assoc()){
            $this->hints[] = $row['hint'];
        }
        $result->close();

    }


    /**
     * Add Identifier 
     * n.b. This can only be called once the name has been saved once 
     * and has a db id otherwise it will throw an Exception
    
     * @param string $identifier The word to be added to the matching_hints table
     * @param string $kind The kind of identifier (needs to be in the enumeration in the db)
     * 
     * @return boolean True if identifier is added. False if already present. (Exception thrown otherwise)
     */
    public function addIdentifier($identifier, $kind){

        global $mysqli;

        if(!$this->id) throw new ErrorException("Attempt to add identifier to Name which doesn't have a db id.");

         // could maybe improve this next bit with an insert if does not exist - but indexes and keys might be tricky
        
        // identifiers may not be sql friendly strings
        $identifier = $mysqli->real_escape_string($identifier);

        // does it exist?
        $sql = "SELECT * FROM `identifiers` WHERE `name_id` = {$this->id} AND `value` = '$identifier' AND `kind` = '$kind' ";
        $result = $mysqli->query($sql);
        if($result->num_rows > 0) return false; // don't need to do anything

        // add it in
        $mysqli->query("INSERT INTO `identifiers`  (`name_id`, `value`, `kind`) VALUES ({$this->id}, '$identifier', '$kind')");
        if($mysqli->affected_rows == 1 ) return true; 
        else throw new ErrorException("Failed to add identifier. Is the kind in the db enumeration?" . $mysqli->error);

    }

    /**
     * Removes this identifier for this kind for this name from the identifiers table 
     * n.b. This can only be called once the name has been saved once 
     * and has a db id otherwise it will throw an Exception
     * 
     * @param string $identifier The identifier to be removed to the identifiers table
     * @param string $kind The kind of identifier (will be in the enumeration in the db)
     * @return boolean True if identifier is removed. False if it was never there. (Exception thrown otherwise)
     */
    public function removeIdentifier($identifier, $kind){

        global $mysqli;

        if(!$this->id) throw new ErrorException("Attempt to remove identifier from Name which doesn't have a db id.");

        // we do this by primary key so we can keep safety lock on and don't accidentally delete more than we intend
        $result = $mysqli->query("SELECT * FROM `identifiers` WHERE `name_id` = {$this->id} AND `value` = '$identifier' AND `kind` = '$kind' ");
        if($result->num_rows == 0){
            return false; // don't need to do anything as it doesn't exist
        }elseif($result->num_rows == 1){
            $row = $result->fetch_assoc();
            $identifier_id = $row['id'];
        }else{
            throw new ErrorException("Attempt to remove identifier ($identifier with kind $kind for {$this->id}) matches multiple rows. This should not be possible.");
        }
        $result->close();

        $result = $mysqli->query("DELETE FROM `identifiers` WHERE `id` = $identifier_id ");
        if($mysqli->affected_rows == 1 ) return true; 
        else throw new ErrorException("Failed to remove identifier " . $mysqli->error);
        $result->close();

    }

    public function getIdentifiers(){
        return $this->all_ids;
    }

    public function loadIdentifiers(){

        global $mysqli;

        if(!$this->id) throw new ErrorException("Attempt to load identifiers for Name which doesn't have a db id.");

        $this->all_ids = array();
        $result = $mysqli->query("SELECT * FROM `identifiers` WHERE `name_id` = {$this->id} ");
        while($row = $result->fetch_assoc()){
            $this->all_ids[] = array("identifier" => $row['value'], "kind" =>$row['kind']);
        }

    }


    /*
        W F O - I D  S T U F F 
    */

    /**
     * 
     * Sets the wfo_id for this name. This is the prescribed one that should be used.
     * 
     */
    public function setPrescribedWfoId($wfo_id){
        $id = trim($wfo_id);
        $this->prescribed_wfo_id = $id; 
    }

    public function getPrescribedWfoId(){
        return $this->prescribed_wfo_id; 
    } 

    private function generateWfoId(){
        return uniqid('tmp-'); // FIXME: This shouldn't happen yet
    }

    /**
     * WFO-ID have to be associated with something so there is no ability to remove a WFO-ID
     * they can be moved between names
     * Fails silently if id has already been added.
     * If id already belongs to another name it is moved.
     * Unless it is the prescribed id in which case an error is thrown.
     * 
     */
    public function addDeduplicationWfoId($wfo_id){
        // fixme
    }

    /**
     * Most important function we have
     * 
     * @return array A description trying to validate the data
     */
    public function checkIntegrity(){

        global $mysqli;

        $out = array();
        $out['status'] = WFO_INTEGRITY_OK; 

        // is the rank valid?
        if(!$this->rank){
            $out['ok'] = WFO_INTEGRITY_FAIL;
            $out['status'][] = "No rank is set.";
        }
        $ranks = $this->getRankEnumeration();

        if(!in_array($this->rank, $ranks)){
            $out['ok'] = WFO_INTEGRITY_FAIL;
            $possibles = implode(',', $ranks);
            $out['status'][] = "Unrecognised rank '{$this->rank}'. Possible values are: $possibles";
        }

        // is the status valid?
        $statuses = $this->getStatusEnumeration();
        if($this->status && !in_array($this->status, $statuses)){
            $out['ok'] = WFO_INTEGRITY_FAIL;
            $possibles = implode(',', $statuses);
            $out['status'][] = "Unrecognised nomenclatural status '{$this->status}'. Possible values are: $possibles";
        }

        //  Does the basionym have a basionym
        $basionym = $this->getBasionym();
        if($basionym && $basionym->getBasionym()){
            $out['ok'] = WFO_INTEGRITY_FAIL;
            $out['status'] = "The basionym is set to {$basionym->getPrescribedWfoId()} but that also has a basionym of {$basionym->getBasionym()->getPrescribedWfoId()}. You can't chain basionyms.";
        }

        // fixme - check my values are cool
        // return informative messages if not

        /*

 60.7.  Diacritical signs are not used in scientific names. When names
(either new or old) are drawn from words in which such signs appear, the
signs are to be suppressed with the necessary transcription of the letters so
modified; for example ä, ö, ü become, respectively, ae, oe, ue (not æ or œ,
see below); é, è, ê become e; ñ becomes n; ø becomes oe (not œ); å becomes
ao.

    // these should happen in setName I think not here because they are auto corrections
    $scientificName = str_replace('ä', 'ae', $scientificName);
    $scientificName = str_replace('ö', 'oe', $scientificName);
    $scientificName = str_replace('ü', 'ue', $scientificName);
    $scientificName = str_replace('é', 'e', $scientificName);
    $scientificName = str_replace('è', 'e', $scientificName);
    $scientificName = str_replace('ê', 'e', $scientificName);
    $scientificName = str_replace('ñ', 'n', $scientificName);
    $scientificName = str_replace('ø', 'oe', $scientificName);
    $scientificName = str_replace('å', 'ao', $scientificName);
    $scientificName = str_replace("", '', $scientificName); // can you believe an o'donolli 

    // check here whether there are any non alpha chars or - 

    */

        // the WFO-ID must either not exist or if it does exist have us as its name_id

        return $out;
    }


    public function getStatusEnumeration(){

        global $mysqli;

        if(!$this->status_enumeration){
            $result = $mysqli->query("SHOW COLUMNS FROM `names` LIKE 'status'");
            $row = $result->fetch_assoc();
            $type = $row['Type'];
            preg_match("/'(.*)'/i", $type, $matches);
            $vals = explode(',', $matches[1]);
            array_walk($vals, function(&$v){$v = str_replace("'", "", $v);});
            $this->status_enumeration = $vals;
            $result->close();
        }
        return $this->status_enumeration;
    }

    public function getRankEnumeration(){

        global $mysqli;

        if(!$this->rank_enumeration){
            $result = $mysqli->query("SHOW COLUMNS FROM `names` LIKE 'rank'");
            $row = $result->fetch_assoc();
            $type = $row['Type'];
            preg_match("/'(.*)'/i", $type, $matches);
            $vals = explode(',', $matches[1]);
            array_walk($vals, function(&$v){$v = str_replace("'", "", $v);});
            $this->rank_enumeration = $vals;
            $result->close();
        }
        return $this->rank_enumeration;
    }

    /**
     * This is always called via inherited
     * save() method in WfoDbObject which 
     * wraps all these calls in a transaction
     * 
     */
    protected function saveDangerously(){

        // FIXME: add in issue 
        
        global $mysqli;

        // check validity and refuse to proceed if we aren't valid
        $check = $this->checkIntegrity();
        if($check['status'] == WFO_INTEGRITY_FAIL) return false;

        // before we do anything we need to check we have a WFO-ID and the db id of it.

        // if we don't have a wfo_id we create one
        if(!$this->prescribed_wfo_id){
            $this->setPrescribedWfoId($this->generateWfoId());
        }

        // get the wfo_id_id so we can link to it
        $result = $mysqli->query("SELECT * FROM `identifiers` WHERE `kind` = 'wfo' AND `value` = '{$this->prescribed_wfo_id}' ");
        $row = $result->fetch_assoc();
        $result->close();
        if($row){
            $wfo_id_db_id = $row['id'];
        }else{
                // it isn't in the table so create it
                if($this->id){
                    // link it to us using our id
                    $mysqli->query("INSERT INTO`identifiers` (`name_id`, `value`, `kind`) VALUES ({$this->id}, '{$this->prescribed_wfo_id}', 'wfo');");
                }else{
                    // we don't have our own id so we can't link it yet
                    $mysqli->query("INSERT INTO`identifiers` (`name_id`, `value`, `kind`) VALUES (NULL, '{$this->prescribed_wfo_id}', 'wfo');");
                }

                $wfo_id_db_id = $mysqli->insert_id;

        }
        

        if($this->id){
            // we have a real db id so we can do an update
            $stmt = $mysqli->prepare("UPDATE `names`
            SET 
                
                `prescribed_id` = ? ,
                `rank` = ? ,
                `name` = ? ,
                `genus` = ? ,
                `species` = ? ,
                `authors` = ? ,
                `status` = ? ,
                `source` = ? ,
                `citation_micro` = ?,
                `citation_id` = ?,
                `comment` = ?,
                `basionym_id` = ?,
                `year` = ?, 
                `user_id` = ? 

            WHERE `id` = ?");
            if($mysqli->error) echo $mysqli->error; // should only have prepare errors during dev

            $stmt->bind_param("issssssssssiiii", 
                $wfo_id_db_id,
                $this->rank,
                $this->name,
                $this->genus,
                $this->species,
                $this->authors,
                $this->status,
                $this->source,
                $this->citation_micro,
                $this->citation_id,
                $this->comment,
                $this->basionym_id,
                $this->year,
                $this->user_id,
                $this->id
            );
            if(!$stmt->execute()){
                echo $mysqli->error;
                return false; // let them know we failed
            }
            $stmt->close();

        }else{
            
            // we don't have a db id so we need to create a row
            $stmt = $mysqli->prepare("INSERT 
                INTO `names`(`prescribed_id`, `rank`, `name`, `genus`, `species`, `authors`, `status`, `source`, `citation_micro`,`citation_id`,`comment`, `basionym_id`, `year`, `user_id`) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            if($mysqli->error) echo $mysqli->error; // should only have prepare errors during dev
            $stmt->bind_param("issssssssssiii", 
                $wfo_id_db_id,
                $this->rank,
                $this->name,
                $this->genus,
                $this->species,
                $this->authors,
                $this->status,
                $this->source,
                $this->citation_micro,
                $this->citation_id,
                $this->comment,
                $this->basionym_id,
                $this->year,
                $this->user_id
            );
            if(!$stmt->execute()){
                echo $mysqli->error;

                // if we failed but have created an orphaned row in the identifiers table 
                // we must delete it.
                $mysqli->query("DELETE identifiers WHERE `id` = $wfo_id_db_id AND `name_id` is NULL");

                return false; // let them know we failed
            }

            // get our real id
            $this->id = $mysqli->insert_id;
            
            $stmt->close();

            // we now become a singleton so we don't have to be loaded again
            self::$loaded[$this->id] = $this;

            // we have just inserted a new record which means the associated WFO-ID in the identifiers table 
            // has a null name_id. We need to fix this.
            // it should be the only time it happens.
            $mysqli->query("UPDATE `identifiers` SET `name_id` = {$this->id} WHERE id = $wfo_id_db_id");

        }

    } // save()


} // name
