<?php

/**
 * 
 * An object representing purely nomenclatural data.
 * 
 * Basic, non confrontational cleaning is done in the setter methods e.g. trim and capitals.
 * Full integrity checking is done when checkIntegrity is called.
 * 
 */
class Name{


    private ?string $id = null;
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
    private ?string $comment = null;
    private ?string $issue = null;
    private ?int $user_id = null;
    private ?string $source = null; // 45 chars
    private ?string $created = null;
    private ?string $modified = null;

    private Array $all_ids = array();


    // We need to be careful we run 
    // singletons on primary objects so only create
    // Name using factory methods.
    protected static $loaded_db_ids = array();


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
        }
    }

    /**
     * Will load all values from db
     * overwrite anything already in the object.
     * 
     * 
     */
    private function load(){
        
        global $mysqli;

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

        // a list of all the other IDs I have
        $all_ids = array();
        $result = $mysqli->query("SELECT * FROM `identifiers` WHERE `name_id` = {$this->id}");
        while($row = $result->fetch_assoc()){
            $all_ids[$row['kind']][] = array('value' => $row['value'], 'comment' => $row['comment']);
        }
        $this->all_ids = $all_ids;


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
            if(isset(self::$loaded_db_ids[$name_id])){
                return self::$loaded_db_ids[$name_id];
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
                    if(isset(self::$loaded_db_ids[$name_id])){
                        return self::$loaded_db_ids[$name_id];
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

        }elseif($init_value == -1){
            // passing -1 means you want a new name
            return new Name(-1);
        }else{
            // don't know what you asked for but you get nothing!
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
        $this->genus = ucfirst(strtolower(trim($genus)));
    }
    public function getGenusString(){
        return $this->genus;
    }

    public function setSpeciesString($species){
        $this->species = strtolower(trim($species));
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

    public function setUserId($id){
        $this->user_id = $id;
    }

    public function getUserId(){
        return $this->user_id;
    }


    public function setSource($source){
        $this->source = $source;
    }

    public function getSource(){
        return $this->source;
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

        $out = array();
        $out['ok'] = true; 

        // fixme - check my values are cool
        // return informative messages if not

        // the WFO-ID must either not exist or if it does exist have us as its name_id

        return $out;
    }

    public function save(){
        
        global $mysqli;

        // check validity and refuse to proceed if we aren't valid
        $check = $this->checkIntegrity();
        if(!$check['ok']) return false;

        // before we do anything we need to check we have a WFO-ID and the db id of it.

        // if we don't have a wfo_id we create one
        if(!$this->prescribed_wfo_id){
            $this->setPrescribedWfoId($this->generateWfoId());
        }

        // get the wfo_id_id so we can link to it
        $result = $mysqli->query("SELECT * FROM `identifiers` WHERE `kind` = 'wfo' AND `value` = '{$this->prescribed_wfo_id}' ");
        $row = $result->fetch_assoc();
        if($row){
            $wfo_id_db_id = $row['id'];
        }else{
                // it isn't in the table so create it
                if($this->id){
                    // link it to us using our id
                    $result = $mysqli->query("INSERT INTO`identifiers` (`name_id`, `value`, `kind`) VALUES ({$this->id}, '{$this->prescribed_wfo_id}', 'wfo');");
                }else{
                    // we don't have our own id so we can't link it yet
                    $result = $mysqli->query("INSERT INTO`identifiers` (`name_id`, `value`, `kind`) VALUES (NULL, '{$this->prescribed_wfo_id}', 'wfo');");
                }

                $wfo_id_db_id = $mysqli->insert_id;

        }

        if($this->id){
            // we have a real db id so we can do an update
            echo "UPDATE\n";
            // fixme

        }else{
            
            // we don't have a db id so we need to create a row
            $stmt = $mysqli->prepare("INSERT INTO `names`(`prescribed_id`, `rank`, `name`, `genus`, `species`, `authors`, `source`, `user_id`) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("issssssi", 
                $wfo_id_db_id,
                $this->rank,
                $this->name,
                $this->genus,
                $this->species,
                $this->authors,
                $this->source,
                $this->user_id
            );
            if(!$stmt->execute()){
                echo $mysqli->error;
                return false; // let them know we failed
            }

            // get our real id
            $this->id = $mysqli->insert_id;
            
            $stmt->close();

            // we now become a singleton so we don't have to be loaded again
            self::$loaded_db_ids[$this->id] = $this;

            // we have just inserted a new record which means the associated WFO-ID in the identifiers table 
            // has a null name_id. We need to fix this.
            // it should be the only time it happens.
            $mysqli->query("UPDATE `identifiers` SET `name_id` = {$this->id} WHERE id = $wfo_id_db_id");

        }

    } // save()


} // name
