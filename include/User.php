<?php

class User{

    
    private ?int $id = null;
    private ?string $name = null;
    private ?string $email = null;
    private ?string $wfoAccessToken = null;
    private ?string $orcidId = null;
    private ?string $ocidAccessToken = null;
    private ?string $ocidRefreshToken = null;
    private ?string $ocidExpires_in = null;
    private ?string $ocidRaw = null;

    /**
     * Initiated with a row from the 
     * user table in the db
     */
    private function __construct($args){

        $this->id = $args['id'];
        $this->name = $args['name'];
        $this->email = $args['email'];
        $this->wfoAccessToken = $args['wfo_access_token'];
        $this->orcidId = $args['orcid_id'];
        $this->ocidAccessToken = $args['ocid_access_token'];
        $this->ocidRefreshToken = $args['ocid_refresh_token'];
        $this->ocidExpiresIn = $args['ocid_expires_in'];
        $this->ocidRaw = $args['ocid_raw'];

    }

    public function save(){

        if($this->id){

            // UPDATING
            $stmt = $mysqli->prepare("UPDATE `users` 
                SET 
                `name` = ?,
                `email` = ?,
                `wfo_access_token` = ?,
                `orcid_id` = ?,
                `ocid_access_token` = ?,
                `ocid_refresh_token` = ?,
                `ocid_expires_in` = ?
                `ocid_raw` = ?
                WHERE 
                `id` = ?"
            );
            if($mysqli->error) error_log($mysqli->error); // should only have prepare errors during dev
            $stmt->bind_param("ssssssssi",
                $this->name,
                $this->email,
                $this->wfoAccessToken,
                $this->orcidId,
                $this->ocidAccessToken,
                $this->ocidRefreshToken,
                $this->ocidExpiresIn,
                $this->ocidRaw,
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
                INTO `users` (`name`, `email`, `wfo_access_token`, `orcid_id`,`ocid_access_token`,`ocid_refresh_token`, `ocid_expires_in`, `ocid_raw`) 
                VALUES (?,?,?,?,?,?,?,?)");
            if($mysqli->error) error_log($mysqli->error); // should only have prepare errors during dev
    
            $stmt->bind_param("ssssssss",
                $this->name,
                $this->email,
                $this->wfoAccessToken,
                $this->orcidId,
                $this->ocidAccessToken,
                $this->ocidRefreshToken,
                $this->ocidExpiresIn,
                $this->ocidRaw
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
}// class