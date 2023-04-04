<?php

/**
 * 
 * A base class for some common stuff between Name and Taxon
 * 
 */
class WfoDbObject{

    protected ?int $id = null;
    protected ?string $comment = null;
    protected ?string $issue = null;
    protected ?int $user_id = null;
    protected ?string $source = null; // 45 chars
    protected ?string $created = null;
    protected ?string $modified = null;

    // for working the singletons
    protected static $loaded = array();

    /**
     * Get the database id (if there is one)
     */
    public function getId(){
        return $this->id;
    }


    public function setComment($comment){
        $this->comment = $comment;
    }

    public function appendToComment($appendix){
        if(!trim($appendix)) return;
        if($this->comment) $this->comment .= "\n";
        $this->comment .= $appendix;
    }

    public function getComment(){
        return $this->comment;
    }

    public function setIssue($issue){
        $this->issue = $issue;
    }

    public function appendToIssue($appendix){
        if(!trim($appendix)) return;
        if($this->issue) $this->issue .= "\n";
        $this->issue .= $issue;
    }

    public function getIssue(){
        return $this->issue;
    }

    public function setSource($source){
        $this->source = $source;
    }

    public function getSource(){
        return $this->source;
    }

    public function setUserId($id){
        $this->user_id = $id;
    }

    public function getUserId(){
        return $this->user_id;
    }

    public function getUser(){
        return User::loadUserForDbId($this->getUserId());
    }

    public function getCreated(){
        return $this->created;
    }

    public function getModified(){
        return $this->modified;
    }

    /**
     * Write this core values of this Object to database safely
    */
    public function save(){

        global $mysqli;

        // we should always be saving as the current user
        $user = unserialize( @$_SESSION['user']);
        $this->setUserId($user->getId()); 

        /* Start transaction */
        $mysqli->begin_transaction();

        try {
            $response = $this->saveDangerously();
            /* If code reaches this point without errors then commit the data in the database */
            $mysqli->commit();
            $response->success = true;
            return $response;
        } catch (mysqli_sql_exception $exception) {
            $mysqli->rollback();
            throw $exception;
            return new UpdateResponse('database', false, "Errors occurred on writing to the database");
        }

    }

    /**
     * Will destroy references to loaded objects
     * Useful when processing big lists as all taxa and names are maintained in memory unless they are actively forgotten
     * Dangerous if you are doing object comparisons because objects with the same id might be different instances.
     */
    public static function resetSingletons(){
        self::$loaded = array();
    }


}