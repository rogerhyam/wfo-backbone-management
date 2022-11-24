<?php

/**
 * 
 * A class to represent a reference to an external source.
 * 
 */
class Reference{


    // for working the singletons to keep the memory down and allow equality comparisons
    protected static $loaded = array();
    protected static $kindEnumeration = null;
    
    private ?int $id = null;
    private ?string $kind = null;
    private ?string $displayText = null;
    private ?string $linkUri = null;
    private ?string $thumbnailUri = null;
    private ?int $userId = null;
    private ?string $created = null;
    private ?string $modified = null;

    /**
     * Don't call this directly. Use the getRank factory method
     * 
     */
    public function __construct($init_value){

        $this->id = $init_value;
        
        if($this->id){
            $this->load();
            // we now become a singleton so we don't have to be loaded again
            self::$loaded[$init_value] = $this;
        }


    }

    public function load(){

        global $mysqli;

        if(!$this->id) throw new ErrorException("You can't call load on a Reference that doesn't have an db id yet");

        $result = $mysqli->query("SELECT * FROM `references` WHERE `id` = {$this->id} ");
        if($mysqli->error) echo $mysqli->error;
        $row = $result->fetch_assoc();

        $this->kind = $row['kind'];
        $this->displayText = $row['display_text'];
        $this->linkUri = $row['link_uri'];
        $this->thumbnailUri = $row['thumbnail_uri'];
        $this->userId = $row['user_id'];
        $this->created = $row['created'];
        $this->modified = $row['modified'];

        //$result->close();

    }


    public static function getReference($id){
        if(isset(self::$loaded[$id])) return self::$loaded[$id];
        return new Reference($id);
    }

    public function save(){

        global $mysqli;

        //error_log("we are saving a reference");

        if(!$this->linkUri) throw new ErrorException("You can't save a reference with no link uri.");
        if(strlen($this->linkUri) < 1) throw new ErrorException("You must set a display string for a reference.");

        $updateResponse = new UpdateResponse('ReferenceSave', true, "Saving reference");

        // we absolutely must not create duplicate link_uri - in fact there is a unique index on that col to stop it
        // so we will overwrite an existing record if it exists.
        //  - we don't want the uri as the primary key because we make joins to this table and they become really inefficient.
        $dupe = Reference::getReferenceByUri($this->linkUri);
        if($dupe){
            // we will now be saved over the top of the existing record even if we are a new record
            // of course this could be ourselves in which case no harm as the id will be the same!
            $this->id = $dupe->getId();
        }
        
        
        if($this->id){

            //error_log("\t updating");
            //error_log($this->userId);

            // we have an id so exist in the db and are being updated
            // we have a real db id so we can do an update
            $stmt = $mysqli->prepare("UPDATE `references`
            SET 
                `link_uri` = ? ,
                `thumbnail_uri` = ? ,
                `display_text` = ? ,
                `kind` = ? ,
                `user_id` = ?
            WHERE `id` = ?");
            if($mysqli->error) echo $mysqli->error; // should only have prepare errors during dev
            $stmt->bind_param("ssssii", 
                $this->linkUri,
                $this->thumbnailUri,
                $this->displayText,
                $this->kind,
                $this->userId,
                $this->id
            );
            if(!$stmt->execute()){
                echo $mysqli->error;
                $updateResponse->message = $mysqli->error;
                $updateResponse->success = false;
                return $updateResponse; // let them know we failed
            }

        }else{

            //error_log("\t creating");
            //error_log($this->userId);
            
            // we have no id so this is an insert call
            $stmt = $mysqli->prepare("INSERT 
                INTO `references`(`link_uri`, `thumbnail_uri`, `display_text`, `kind`, `user_id`) 
                VALUES (?,?,?,?,?)");
            if($mysqli->error) echo $mysqli->error; // should only have prepare errors during dev
            $stmt->bind_param("ssssi", 
                $this->linkUri,
                $this->thumbnailUri,
                $this->displayText,
                $this->kind,
                $this->userId
            );
            if(!$stmt->execute()){
                $updateResponse->message = $mysqli->error;
                $updateResponse->success = false;
                return $updateResponse; // let them know we failed
            }

            // get our real id
            $this->id = $mysqli->insert_id;

            // we now become a singleton so we don't have to be loaded again
            self::$loaded[$this->id] = $this;

        }

    }

    /**
     * Get the value of modified
     */ 
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Get the value of created
     */ 
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get the value of userId
     */ 
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set the value of userId
     */ 
    public function setUserId($user_id){
        $this->userId = $user_id;
    }

    /**
     * Get the value of thumbnailUri
     */ 
    public function getThumbnailUri()
    {
        return $this->thumbnailUri;
    }

    /**
     * Set the value of thumbnailUri
     *
     */ 
    public function setThumbnailUri($thumbnailUri){
        $this->thumbnailUri = $thumbnailUri;
    }

    /**
     * Get the value of linkUri
     */ 
    public function getLinkUri()
    {
        return $this->linkUri;
    }

    /**
     * Set the value of linkUri
     *
     * @return  bool true if it is a valid uri and therefore set
     */ 
    public function setLinkUri($linkUri)
    {
        $linkUri = trim($linkUri);
       
        // there is an issue with non-ascii characters in the urls 
        // these won't pass the PHP validator 
        // we therefore do a crude parsing and check the schema
        $parts = parse_url($linkUri);

        if($parts && ($parts['scheme'] == 'http' || $parts['scheme'] == 'https') ){
            $this->linkUri = $linkUri;
            return true;
        }else{
            error_log("Invalid URI format: " . $linkUri);
            return false;
        }

        /*

        if (filter_var($linkUri, FILTER_VALIDATE_URL)) {

        } else {
            echo "\nInvalid URI format";
            echo "\n$linkUri";
            return false;
        }

        */



    }

    /**
     * Get the value of displayText
     */ 
    public function getDisplayText()
    {
        return $this->displayText;
    }

    /**
     * Set the value of displayText
     *
     * @return  self
     */ 
    public function setDisplayText($displayText)
    {
        $this->displayText = $displayText;
    }

    /**
     * Get the value of kind
     */ 
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * Set the value of kind
     *
     * @return  self
     */ 
    public function setKind($kind)
    {

        $kinds = Reference::getReferenceKindEnumeration();
        if(in_array($kind, $kinds)){
            $this->kind = $kind;
            return true;
        }else{
            throw new ErrorException("Trying to set illegal Reference kind value: $kind.");
            return false;
        }
        
    }


    public static function getReferenceKindEnumeration(){

        global $mysqli;

        if(!self::$kindEnumeration){
            $result = $mysqli->query("SHOW COLUMNS FROM `references` LIKE 'kind'");
            $row = $result->fetch_assoc();
            $type = $row['Type'];
            preg_match("/'(.*)'/i", $type, $matches);
            $vals = explode(',', $matches[1]);
            array_walk($vals, function(&$v){$v = str_replace("'", "", $v);});
            self::$kindEnumeration = $vals;
            // $result->close();
        }
        return self::$kindEnumeration;

    }

    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    public static function getReferenceByUri($uri){

        global $mysqli;

        $safe_uri = $mysqli->real_escape_string($uri);

        // we always look for both versions of the URI
        if(preg_match('/^http:\/\//', $safe_uri)){
            $https_version = str_replace('http://', 'https://', $safe_uri);
            $http_version = $safe_uri;
        }else{
            $https_version = $safe_uri;
            $http_version = str_replace('https://', 'http://', $safe_uri);
        }

        $result = $mysqli->query("SELECT id FROM `references` WHERE link_uri = '$https_version' OR link_uri = '$http_version'");
        if($result->num_rows > 0){
            
            $row = $result->fetch_assoc();
            $ref = Reference::getReference($row['id']);

            // if we were asked for the https version but the one received was the http version
            // then we set the link to the https version so that any subsequent save (e.g. updating the display string) will update it.
            if($safe_uri == $https_version && $ref->getLinkUri() == $http_version){
                $ref->setLinkUri($https_version);
            }

            return $ref;

        }else{
            return null;
        }

    }


    public static function resetSingletons(){
        self::$loaded = array();
    }

}