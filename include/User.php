<?php

class User{

    
    private ?int $id = null;
    private ?string $name = null;
    private ?string $email = null;
    private ?string $wfoAccessToken = null;
    private ?string $orcidId = null;
    private ?string $orcidAccessToken = null;
    private ?string $orcidRefreshToken = null;
    private ?string $orcidExpiresIn = null;
    private ?string $orcidRaw = null;

    /**
     * Initiated with a row from the 
     * user table in the db
     */
    public function __construct($args = false){

        // can be called with db row
        // otherwise creates an empty user
        if($args && is_array($args)){
            $this->id = $args['id'];
            $this->name = $args['name'];
            $this->email = $args['email'];
            $this->wfoAccessToken = $args['wfo_access_token'];
            $this->orcidId = $args['orcid_id'];
            $this->orcidAccessToken = $args['orcid_access_token'];
            $this->orcidRefreshToken = $args['orcid_refresh_token'];
            $this->orcidExpiresIn = $args['orcid_expires_in'];
            $this->orcidRaw = $args['orcid_raw'];
        }
    
    }

    public function save(){

        global $mysqli;

        // only requirement is a name longer than 3 characters!
        if( strlen(trim($this->name)) < 4 ){
            throw new ErrorException("Users must have unique names  4 characters or more long");
            return;
        } 

        // we must always have an access token
        if(!$this->wfoAccessToken){
            $this->wfoAccessToken = bin2hex(openssl_random_pseudo_bytes(24));
        }

        if($this->id){

            // UPDATING
            $stmt = $mysqli->prepare("UPDATE `users` 
                SET 
                `name` = ?,
                `email` = ?,
                `wfo_access_token` = ?,
                `orcid_id` = ?,
                `orcid_access_token` = ?,
                `orcid_refresh_token` = ?,
                `orcid_expires_in` = ?,
                `orcid_raw` = ?
                WHERE 
                `id` = ? "
            );
            if($mysqli->error) error_log($mysqli->error); // should only have prepare errors during dev
            $stmt->bind_param("ssssssssi",
                $this->name,
                $this->email,
                $this->wfoAccessToken,
                $this->orcidId,
                $this->orcidAccessToken,
                $this->orcidRefreshToken,
                $this->orcidExpiresIn,
                $this->orcidRaw,
                $this->id
            );
            if(!$stmt->execute()){
                throw new ErrorException($mysqli->error);
                return false;
            }else{
                return true;
            }

        }else{

            // CREATING
            $stmt = $mysqli->prepare("INSERT 
                INTO `users` (`name`, `email`, `wfo_access_token`, `orcid_id`,`orcid_access_token`,`orcid_refresh_token`, `orcid_expires_in`, `orcid_raw`) 
                VALUES (?,?,?,?,?,?,?,?)");
            if($mysqli->error) error_log($mysqli->error); // should only have prepare errors during dev
    
            $stmt->bind_param("ssssssss",
                $this->name,
                $this->email,
                $this->wfoAccessToken,
                $this->orcidId,
                $this->orcidAccessToken,
                $this->orcidRefreshToken,
                $this->orcidExpiresIn,
                $this->orcidRaw
            );
            if(!$stmt->execute()){
                throw new ErrorException($mysqli->error);
                return false;
            }else{
                // get our db id
                $this->id = $mysqli->insert_id;
                return true;
            }

        }
        
    }

    public static function loadUserForWfoToken($wfo_access_token){

        global $mysqli;

        // malformed access token - prevent injection
        // e.g. e7bc745c1198c7e3867d8cdae62477ef26adf63ed3beb8e7
        // created with bin2hex(openssl_random_pseudo_bytes(24));
        if(!preg_match('/^[0-9A-Fa-f]{48}$/',$wfo_access_token)){
            return null;
        }

        // pull that row from the db
        $response = $mysqli->query("SELECT * FROM `users` WHERE wfo_access_token = '$wfo_access_token'");
        if($response->num_rows != 1) return null;

        return new User($response->fetch_assoc());

    }

    public static function loadUserForOrcidId($orcid_id){

        global $mysqli;

        // regex here to prevent SQL injection
       if(!preg_match('/^[0-9]{4}-[0-9]{4}-[0-9]{4}-([0-9]{3}X|[0-9]{4})$/',$orcid_id)){
            return null;
        }
        
        // pull that row from the db
        $response = $mysqli->query("SELECT * FROM `users` WHERE orcid_id = '$orcid_id'");
        if($response->num_rows != 1) return null;

        return new User($response->fetch_assoc());

    }

    public function isAnonymous(){
        if($this->name == 'web-ui') return true;
        else return false;
    }

    public function getOrcidLogInUri(){
        return ORCID_LOG_IN_URI;
    }

   public function getOrcidLogOutUri(){
        return ORCID_LOG_OUT_URI;
   }

    /**
     * Get the value of name
     */ 
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */ 
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of email
     */ 
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the value of email
     *
     * @return  self
     */ 
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the value of wfoAccessToken
     */ 
    public function getWfoAccessToken()
    {
        return $this->wfoAccessToken;
    }

    /**
     * Get the value of orcidId
     */ 
    public function getOrcidId()
    {
        return $this->orcidId;
    }

    /**
     * Get the value of orcidAccessToken
     */ 
    public function getOrcidAccessToken()
    {
        return $this->orcidAccessToken;
    }

    /**
     * Set the value of orcidAccessToken
     *
     * @return  self
     */ 
    public function setOrcidAccessToken($orcidAccessToken)
    {
        $this->orcidAccessToken = $orcidAccessToken;

        return $this;
    }

    /**
     * Get the value of orcidRefreshToken
     */ 
    public function getOrcidRefreshToken()
    {
        return $this->orcidRefreshToken;
    }

    /**
     * Set the value of orcidRefreshToken
     *
     * @return  self
     */ 
    public function setOrcidRefreshToken($orcidRefreshToken)
    {
        $this->orcidRefreshToken = $orcidRefreshToken;

        return $this;
    }

    /**
     * Get the value of orcidExpiresIn
     */ 
    public function getOrcidExpiresIn()
    {
        return $this->orcidExpiresIn;
    }

    /**
     * Set the value of orcidExpiresIn
     *
     * @return  self
     */ 
    public function setOrcidExpiresIn($orcidExpiresIn)
    {
        $this->orcidExpiresIn = $orcidExpiresIn;

        return $this;
    }

    /**
     * Get the value of orcidRaw
     */ 
    public function getOrcidRaw()
    {
        return $this->orcidRaw;
    }

    /**
     * Set the value of orcidRaw
     *
     * @return  self
     */ 
    public function setOrcidRaw($orcidRaw)
    {
        $this->orcidRaw = $orcidRaw;

        return $this;
    }

    /**
     * Set the value of orcidId
     *
     * @return  self
     */ 
    public function setOrcidId($orcidId)
    {
        $this->orcidId = $orcidId;

        return $this;
    }
}// class