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

        $sql = "SELECT n.*, i.value as 'prescribed_wfo_id' FROM `names` as n JOIN `identifiers` as i on i.`name_id` = n.`id` AND n.prescribed_id = i.id WHERE n.`id` = {$this->id} ";
        $result = $mysqli->query($sql);
        if($mysqli->error) echo $mysqli->error;
        $row = $result->fetch_assoc();

        if(!$row){
            error_log($sql);
        }else{

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
            $this->basionym_id = $row['basionym_id'];
            $this->comment = $row['comment'];
            $this->issue = $row['issue'];
            $this->change_log = $row['change_log'];
            $this->user_id = $row['user_id'];
            $this->source = $row['source'];
            $this->modified = $row['modified'];
            $this->created = $row['created'];

        }



        $result->close();

        $this->loadHints();

    }


    /**
     * Always use this function to get an instance of a name
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

        }elseif(is_string($init_value) && preg_match('/^wfo-[0-9]{10}$/', $init_value)){

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
            throw new ErrorException("No name for init value: " . $init_value);
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
        if(is_null($basionym)){
            $this->basionym_id = NULL;
            return;
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

    public function setYear($year){
        $this->year = (int)$year;
    }

    public function getYear(){
        return $this->year;
    }

    public function getFullNameString($italics = true, $authors = true, $abbreviate_rank = true, $abbreviate_genus = false){

        global $ranks_table;


        $out = '<span class="wfo-name-full" ><span class="wfo-name">';

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
                $out .= " <span class=\"wfo-name-rank\">$rank</span>";
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
    
        $out .= "</span>";

        if($authors){
            $out .= " <span class=\"wfo-name-authors\" >{$this->authors}</span>";
        }

        $out .= "</span>";

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

        // we do nothing if the user doesn't have rights to change this name
        // They should never get here because interface should stop them
        if(!$this->canEdit()){
            throw new ErrorException("User does not have permission to save changes to Name");
            return;
        }

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

        // we do nothing if the user doesn't have rights to change this name
        // They should never get here because interface should stop them
        if(!$this->canEdit()){
            throw new ErrorException("User does not have permission to save changes to Name");
            return;
        }

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

        // we can't remove our own prescribed WFO ID as there is a foreign key constraint
        if ($kind == 'wfo' && $identifier == $this->getPrescribedWfoID())
            throw new ErrorException("Can't remove prescribed WFO ID from name.");

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

        global $mysqli;

        if(!$this->id) throw new ErrorException("Attempt to load identifiers for Name which doesn't have a db id.");

        // build a consolidated list
        $all_ids = array();
        $result = $mysqli->query("SELECT * FROM `identifiers` WHERE `name_id` = {$this->id} ");
        while($row = $result->fetch_assoc()){
            $all_ids[$row['kind']][] = $row['value'];
        }

        // turn that into objects
        $out = array();
        foreach ($all_ids as $kind => $values) {
           $out[] = new Identifier($kind, $values);
        }

        // add in our internal identifiers - they are useful to see!
        $out[] = new Identifier("rhakhis_name_id", array($this->id));

        $taxon = Taxon::getTaxonForName($this);
        if($taxon->getId()){
            $out[] = new Identifier("rhakhis_taxon_id", array($taxon->getId()));
        }

        return $out;

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

        global $mysqli;

        $mysqli->query("UPDATE wfo_mint SET next_id = (@new_id := next_id) + 1 WHERE `rank` = '{$this->rank}';");
        $response = $mysqli->query("select @new_id as wfo_id");
        $row = $response->fetch_assoc();
        return 'wfo-' . $row['wfo_id'];

        // FIXME - this could do with limits checks on it but can run for many millions before we run out of space.

    }

    /**
     * Most important function we have
     * 
     * @return array A description trying to validate the data
     */
    public function checkIntegrity(){

        global $mysqli;

        $out = new UpdateResponse('name', true, "Name integrity check");
        $out->status = WFO_INTEGRITY_OK;

        // the name has to be at least two characters long!
        if(strlen($this->getNameString()) < 2){
            $out->children[] = new UpdateResponse('name', false, "Name string is less than 2 characters long");
        }

        // is the rank valid?
        if(!$this->rank){
            $out->status = WFO_INTEGRITY_FAIL;
            $out->success = false;
            $out->children[] = new UpdateResponse('rank', false, 'No rank is set');
        }
        $ranks = $this->getRankEnumeration();

        if(!in_array($this->rank, $ranks)){
            $out->status = WFO_INTEGRITY_FAIL;
            $out->success = false;
            $possibles = implode(',', $ranks);
            $out->children[] = new UpdateResponse('rank', false, "Unrecognised rank '{$this->rank}'. Possible values are: $possibles");
        }

        // is the status valid?
        $statuses = $this->getStatusEnumeration();
        if($this->status && !in_array($this->status, $statuses)){
            $out->status = WFO_INTEGRITY_FAIL;
            $out->success = false;
            $possibles = implode(',', $statuses);
            $out->children[] = new UpdateResponse('status', false, "Unrecognised nomenclatural status '{$this->status}'. Possible values are: $possibles");
        }

        //  Does the basionym have a basionym
        $basionym = $this->getBasionym();
        if($basionym && $basionym->getBasionym()){
            $out->status = WFO_INTEGRITY_FAIL;
            $out->success = false;
            $out->children[] = new UpdateResponse('basionym', false, "The basionym is set to {$basionym->getPrescribedWfoId()} but that also has a basionym of {$basionym->getBasionym()->getPrescribedWfoId()}. You can't chain basionyms.");
        }

        // fixme - check my values are cool
        // return informative messages if not
        // the WFO-ID must either not exist or if it does exist have us as its name_id

        $out->consolidateSuccess();

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
        
        global $mysqli;
        global $ranks_table;

        // we do nothing if the user doesn't have rights to change this name
        // They should never get here because interface should stop them
        if(!$this->canEdit()){
            throw new ErrorException("User does not have permission to save changes to Name");
            return;
        }

        // check validity and refuse to proceed if we aren't valid
        $updateResponse = $this->checkIntegrity();
        if($updateResponse->status == WFO_INTEGRITY_FAIL){
            $updateResponse->success = false;
            return $updateResponse;
        } 

        // before we do anything we need to check we have a WFO-ID and the db id of it.

        // if we don't have a wfo_id we create one
        if(!$this->prescribed_wfo_id){
            $this->setPrescribedWfoId( $this->generateWfoId() );
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

        // check our name parts are of the right number for our rank - no more 
        // we do this here not at integrity check as it is changing the data
        $my_level = array_search($this->rank, array_keys($ranks_table));
        $genus_level = array_search('genus', array_keys($ranks_table));
        $species_level = array_search('species', array_keys($ranks_table));

        // if it is at or above species then it can't have a species value
        if($my_level <= $species_level) $this->species = null;

        // if it at or above genus level then it can't have a genus
        if($my_level <= $genus_level) $this->genus = null;


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
                `comment` = ?,
                `change_log` = ?,
                `basionym_id` = ?,
                `year` = ?, 
                `user_id` = ? 

            WHERE `id` = ?");
            if($mysqli->error) {
                error_log('Updating name - prepare');
                error_log($mysqli->error);
            }; // should only have prepare errors during dev

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
                $this->comment,
                $this->change_log,
                $this->basionym_id,
                $this->year,
                $this->user_id,
                $this->id
            );
            if(!$stmt->execute()){
                echo $mysqli->error;
                $updateResponse->message = $mysqli->error;
                $updateResponse->success = false;
                error_log('Updating name - execute');
                error_log($mysqli->error);
                return $updateResponse; // let them know we failed
            }
            $stmt->close();

        }else{
            
            // we don't have a db id so we need to create a row
            $stmt = $mysqli->prepare("INSERT 
                INTO `names`(`prescribed_id`, `rank`, `name`, `genus`, `species`, `authors`, `status`, `source`, `citation_micro`,`comment`, `change_log`, `basionym_id`, `year`, `user_id`) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            if($mysqli->error) {
                error_log('Inserting name - prepare');
                error_log($mysqli->error);
            };  // should only have prepare errors during dev
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
                $this->comment,
                $this->change_log,
                $this->basionym_id,
                $this->year,
                $this->user_id
            );
            if(!$stmt->execute()){
               if($mysqli->error) {
                    error_log('Inserting name - execute');
                    error_log($mysqli->error);
                }; 

                // if we failed but have created an orphaned row in the identifiers table 
                // we must delete it.
                $mysqli->query("DELETE FROM identifiers WHERE `id` = $wfo_id_db_id AND `name_id` is NULL");

                $updateResponse->message = $mysqli->error;
                $updateResponse->success = false;
                return $updateResponse; // let them know we failed
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

        return $updateResponse;

    } // save()

    /**
     * 
     * Homotypic Names synonyms are fetched on request.
     */

     public function getHomotypicNames(){

        global $mysqli;

        $out = array();

        // do we have a basionym set (i.e. are we a comb nov )
        if($this->basionym_id){
            $type_id = $this->basionym_id;
        }else{
            $type_id = $this->getId();
        }

        // find all the things that have this set as the basionym (excluding ourselves )
        $result = $mysqli->query("SELECT id FROM `names` WHERE basionym_id = $type_id AND id != {$this->getId()} order by `name_alpha`");
        while($row = $result->fetch_assoc()){
            $out[] = Name::getName($row['id']);
        }

        return $out;

     }

     public function getHomonyms(){

        global $mysqli;

        $homonyms = array();

        // lets get right to it and look to see if we have matching names
        $sql = "SELECT id FROM `names` WHERE `name` = '{$this->getNameString()}' ";

        if($this->getId()){
            $sql .=  " AND `id` != '{$this->getId()}'";
        }

        if($this->getGenusString()){
            $sql .=  " AND `genus` = '{$this->getGenusString()}'";
        }else{
            $sql .=  " AND (length(`genus`) = 0 || `genus` IS NULL)";
        }

        if($this->getSpeciesString()){
            $sql .=  " AND `species` = '{$this->getSpeciesString()}'";
        }else{
            $sql .=  " AND (length(`species`) = 0 || `species` IS NULL)";
        }

        $result = $mysqli->query($sql);
        while($row = $result->fetch_assoc()){
            $homonyms[] = Name::getName($row['id']);
        }
        $result->free();

        return $homonyms;

     }


    /**
     * An update function for the name parts
     * that follows the update -> UpdateResponse pattern for GraphQL (or other) API
     * 
     */
    public function updateNameParts($args,$response = null){

        $this->setNameString($args['nameString']);
        $this->setGenusString($args['genusString']);
        $this->setSpeciesString($args['speciesString']);
        $this->setRank($args['rankString']);
        return $this->save();

    }

    public function updateStatus($new_status, $response = null){
        $this->setStatus($new_status);
        return $this->save();
    }

    public function updateAuthorsString($new_authors, $response = null){
        $this->setAuthorsString($new_authors);
        return $this->save();
    }

    public function updatePublication($new_citation_micro, $year, $response = null){
        $this->setCitationMicro($new_citation_micro);
        $this->setYear($year);
        return $this->save();
    }

    public function updateComment($new_comment, $response = null){
        $this->setComment($new_comment);
        return $this->save();
    }

    public function updateBasionym($new_basionym, $response = null){
        $this->setBasionym($new_basionym);
        return $this->save();
    }


    /**
     * Change log is just a message about 
     * placement changes or additions
     * so they can be registered when things outside the name table change.
     * 
     */
    public function updateChangeLog($change){
        $this->setChangeLog($change);
        return $this->save();
    }

    public function setChangeLog($change){
        $this->change_log = $change;
    }

    public function getChangeLog(){
        return $this->change_log;
    }

    /**
     * Static function to create a new name
     * Has controls on creating homonyms.
     * If the name is a homonym you just include a list of homonyms WFO IDs to show
     * that you know they exist and are doing it anyway.
     * 
     */
    public static function createName($proposed_name, $create, $force_homonym, $known_homonyms = array()){

        global $mysqli;

        $updateResponse = new UpdateResponse("NewName", true, "Starting check");

        $updateResponse->children[] = new UpdateResponse("ProposedName", true, $proposed_name);
        $updateResponse->children[] = new UpdateResponse("ForceHomonym", true, $force_homonym);
        
        // at the same time we create an empty name object - we can throw it away later if needed.
        $name = Name::getName(-1);
        $name->setStatus('unknown');

        $user = unserialize( @$_SESSION['user']);
        $name->setUserId($user->getId()); 

        // there may be double spaces in name
        $proposed_name = preg_replace('/\s+/', ' ', $proposed_name);

        // no silly characters - letters alone
        $proposed_name = preg_replace('/[^\sa-zA-Z]/', '', $proposed_name);

        // let's start by parsing out the name into parts.
        $parts = explode(' ', trim($proposed_name));
        switch (count($parts)) {

            case 1:
                $name->setNameString($parts[0]);
                $name->setRank('genus');
                break;
            
            case 2:
                $name->setNameString($parts[1]);
                $name->setGenusString($parts[0]);
                $name->setRank('species');
                break;

            case 3:
                $name->setNameString($parts[2]);
                $name->setSpeciesString($parts[1]);
                $name->setGenusString($parts[0]);
                $name->setRank('subspecies');
                break;
            
            default:
                $n = count($parts);
                $updateResponse->children[] = new UpdateResponse("NamePartsCount", false, "The name string should contain 1, 2 or 3 words, $n found.");
                return $updateResponse; // we can do no more if we don't have name parts.
                break;
        
        }

        $homonymList = $name->getHomonyms();

        // report that list
        $homoResponse = new UpdateResponse("HomonymsFound", true, "These are the homonyms found for this name.");
        $homoResponse->names = $homonymList;
        $updateResponse->children[] = $homoResponse;

        // do we have homonyms for this name?
        if($homonymList){

            // but wait! we have a list of known homonyms

            foreach ($homonymList as $homonym) {
                if(!in_array($homonym->getPrescribedWfoId(), $known_homonyms)){
                    // found one not in the list provided
                    $updateResponse->children[] = new UpdateResponse("HomonymCheck", false, "{$homonym->getPrescribedWfoId()} is a homonym NOT provided in the list");
                }else{
                    $updateResponse->children[] = new UpdateResponse("HomonymCheck", true, "{$homonym->getPrescribedWfoId()} is a homonym and is provided in the list");
                }
            }

        }

        // total up any errors
        $updateResponse->consolidateSuccess();

        // should we actually create the name?
        // if we are asking to create and we either don't have homonyms or 
        // or we 
        if(
            $create 
            &&
            (
                !$homonymList // no homonyms
                ||
                ($updateResponse->success && $force_homonym) // homonyms but they are kosher 
            )){
                $saveResponse = $name->save();
                $updateResponse->children[] = $saveResponse; // add the validation response to the response tree.
                if($saveResponse->success){
                    $updateResponse->names[] = $name; // store the new name in the top level response
                }
        }else{
                $updateResponse->children[] = $name->checkIntegrity(); // just add the validity check to the response tree
        }

        $updateResponse->consolidateSuccess(); // wrap up all the responses so the top level success reflects truth

        return $updateResponse;

    }

    /**
     * The only place where we break
     * down the fact that names don't know about taxa.
     */
    public function canEdit(){

        $user = unserialize($_SESSION['user']);

        if($user->isGod()) return true; // gods can do anything.
        
        if(!$this->getId()) return true; // if we haven't been saved yet you can edit us

        // otherwise we get a taxon to judge
        $taxon = Taxon::getTaxonForName($this);

        // if the taxon doesn't have an id then it doesn't exist yet - i.e. this name hasn't been placed
        if(!$taxon->getId()){
            // anyone with the role 'editor' or 'god' can edit unplaced names
            // this is gained by being a curator somewhere in the tree.
            if($user->isEditor()) return true;
            else return false;
        }
        
        return $taxon->canEdit($user);
    }

      /**
     * Add a reference to a taxon or name object. 
     * No attempt is made to prevent duplicates.
     * But adding ones with identical URI will fail
     */
    public function addReference(Reference $ref, $comment, $placement_related = false){

        global $mysqli;

        $placement = $placement_related ? 1:0;

        // we do things as the current user no the user of this object
        $user = unserialize($_SESSION['user']);

        if(!$this->canEdit()){
            throw new ErrorException("User: {$user->getName()} ({$user->getId()}) does not have permission to edit this item ({$this->getId()})");
        }

        $stmt = $mysqli->prepare("INSERT INTO `name_references`(`name_id`, `reference_id`, `comment`, `placement_related`, `user_id`) VALUES (?,?,?,?,?)");
        
        if($mysqli->error) echo $mysqli->error; // should only have prepare errors during dev

        $user_id = $user->getId();
        $ref_id = $ref->getId();

        $stmt->bind_param("iisii", 
            $this->id,
            $ref_id,
            $comment,
            $placement,
            $user_id
        );
        if(!$stmt->execute()){
            throw new ErrorException("Failed to add reference ({$ref->getId()}) to name - {$mysqli->error}");
            return false;
        }
        if($mysqli->error) echo $mysqli->error;

    }

    public function updateReference(Reference $ref, $comment, $placement_related = false){
       
        global $mysqli;

        $placement = $placement_related ? 1:0;

        // we do things as the current user no the user of this object
        $user = unserialize($_SESSION['user']);

        if(!$this->canEdit()){
            throw new ErrorException("User: {$user->getName()} ({$user->getId()}) does not have permission to edit this item ({$this->getId()})");
        }

        // simple query to update the comment.
        $c = $mysqli->real_escape_string($comment);
        $sql = "UPDATE `name_references` SET `comment` = '$c' WHERE reference_id = {$ref->getId()} AND `name_id` = {$this->getId()} AND placement_related = $placement";

        error_log($sql);
        $result = $mysqli->query($sql);
        if($mysqli->error) error_log($mysqli->error);

    }

    public function removeReference(Reference $ref, $placement_related = false){

        global $mysqli;

        $placement = $placement_related ? 1:0;

        if(!$this->canEdit()){
            throw new ErrorException("User: {$user->getName()} ({$user->getId()}) does not have permission to edit this item ({$this->getId()})");
        }

        // simple query. Including taxon/name id should help secure 
        $result = $mysqli->query("DELETE FROM `name_references` WHERE reference_id = {$ref->getId()} AND `name_id` = {$this->getId()} AND placement_related = $placement");
        if($mysqli->error) echo $mysqli->error;
        
    }

    /**
     * Get a list of the references associated with this item
     * 
     */

    public function getReferences($kind = false){

        global $mysqli;

        // no attempt made to cache list but references are singletons so should only be created once
        // even if we call this several times.

        $sql = "SELECT `reference_id`, `comment`, `placement_related` 
                FROM `name_references` as tr
                JOIN `references` as r on tr.reference_id = r.id
                WHERE  `name_id` = {$this->getId()}";

        if($kind) $sql .= " AND `kind` = '$kind'";

        $result = $mysqli->query($sql);

        $out = array();
        while($row = $result->fetch_assoc()){

            $placement = $row['placement_related'] == 0 ? 'name':'taxon';

            $ref = Reference::getReference($row['reference_id']);
            $id = $ref->getId() . '-' . $this->getId() . '-' . $placement;
            $out[] = new ReferenceUsage($id,$ref,$row['comment'], $placement);
        }
        return $out;

    }

    public function getGbifOccurrenceCount(){


        global $mysqli;

        $response = $mysqli->query("SELECT * FROM gbif_occurrence_count WHERE `name_id` = {$this->getId()}");
        if($response->num_rows > 0){
            $row = $response->fetch_assoc();
            return $row['count'];
        }else{
            return null;
        }
        
    }

    /**
     * Remove and swap any dodgy characters
     * 
     */
    public static function sanitizeNameString($dirty){

            /*

                60.7.  Diacritical signs are not used in scientific names. When names
                (either new or old) are drawn from words in which such signs appear, the
                signs are to be suppressed with the necessary transcription of the letters so
                modified; for example ä, ö, ü become, respectively, ae, oe, ue (not æ or œ,
                see below); é, è, ê become e; ñ becomes n; ø becomes oe (not œ); å becomes
                ao.

            */

            $cleaner = str_replace('ä', 'ae', $dirty);
            $cleaner = str_replace('ö', 'oe', $cleaner);
            $cleaner = str_replace('ü', 'ue', $cleaner);
            $cleaner = str_replace('é', 'e', $cleaner);
            $cleaner = str_replace('è', 'e', $cleaner);
            $cleaner = str_replace('ê', 'e', $cleaner);
            $cleaner = str_replace('ë', 'e', $cleaner);
            $cleaner = str_replace('ñ', 'n', $cleaner);
            $cleaner = str_replace('ø', 'oe', $cleaner);
            $cleaner = str_replace('å', 'ao', $cleaner);
            $cleaner = str_replace("", '', $cleaner); // can you believe an o'donolli 

            // we don't do hybrid symbols or other weirdness
            $cleaner = str_replace(' X ', '', $cleaner); // may use big X for hybrid  - we ignore
            $cleaner = str_replace(' x ', '', $cleaner); // may use big X for hybrid - we ignore
            $cleaner = preg_replace('/[^A-Za-z\-. ]/', '', $cleaner); // any non-alpha character, hyphen or full stop (OK in abbreviated ranks) 
            $cleaner = preg_replace('/\s\s+/', ' ', $cleaner); // double spaces

            return $cleaner;

    }

    /**
     * Destroy this name and move any existing
     * identifiers are references to
     * the target name supplied
     * returns true on success
     * Intention is to only call this at cmd line so no response handing 
     * 
     */
    public function deduplicate_into(Name $target_name){

        global $mysqli;

        // fail if we are in use in the taxonomy
        $taxon = Taxon::getTaxonForName($this);
        if($taxon->getId()){
            echo "\nOccurs in taxonomy so failing\n";
            return false;
        }

        // fail if we haven't been supplied with a good name
        $target_name_id = $target_name->getId();
        if(!$target_name_id){
            echo "\nTarget name is rubbish - failing\n";
            return false;
        }
        // we have to be severed 

        $identifiers = $this->getIdentifiers();
        foreach ($identifiers as $identifier) {

            foreach ($identifier->getValues() as $value) {
                // we can't delete a prescribed WFO ID - it will go when the name is deleted
                if($value !== $this->getPrescribedWfoId()){
                    $this->removeIdentifier($value, $identifier->getKind());
                }
                // but we do add in if they are in the db enumeration
                if(in_array($identifier->getKind(), array('ipni', 'tpl', 'wfo', 'if', 'ten', 'tropicos', 'uri', 'uri_deprecated'))){
                    $target_name->addIdentifier($value, $identifier->getKind());
                }
                
            }
        }

        $references = $this->getReferences();
        foreach($references as $ref){
            $this->removeReference($ref->reference);

            // we don't want to double up the references 
            $already_there = false;
            $existing_refs = $target_name->getReferences();
            foreach ($existing_refs as $existing_ref) {
                if($ref->reference->getLinkUri() == $existing_ref->reference->getLinkUri()){
                    $already_there = true;
                    break;
                }
            }

            // not already in target so add it.
            if(!$already_there){
                $target_name->addReference($ref->reference, "Originally associated with " . $this->getPrescribedWfoId());
            }
            
        }

        $hints = $this->getHints();
        foreach($hints as $hint){
            $this->removeHint($hint);
            $target_name->addHint($hint);
        }

        $target_name->save();

        // actually delete the name record
        $old_name_id = $this->getId();

        try{
            $mysqli->query("SET foreign_key_checks = 0");
            $mysqli->query("DELETE FROM `identifiers` WHERE `name_id` = $old_name_id");
            $mysqli->query("DELETE FROM `names` WHERE `id` = $old_name_id");
            $mysqli->query("SET foreign_key_checks = 1");
            $mysqli->commit();
        } catch (mysqli_sql_exception $exception) {
            $mysqli->rollback();
            throw $exception;
            echo $mysqli->error;
            echo "\n$old_name_id\n";
            echo $this->getPrescribedWfoId() . "\n";
            exit;
        }

        return true;

    }

} // name
