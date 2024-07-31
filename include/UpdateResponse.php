<?php

// Something to return from all mutation calls to the GraphQL interface
// Isn't meant to contain data but just information on success and use error messages.

class UpdateResponse{

    public int $status; // an internal status not shared through GraphQL
    public string $name; // the name of the field or of the mutation called
    public bool $success; // did it work?
    public string $message; // an overall message on success or failure of update
    public Array $children = array(); // a list of sub-UpdateResponses (typically one for each field)
    public Array $taxonIds = array();
    public Array $nameIds = array();
    public Array $names = array();
    public Array $taxa = array();

    public function __construct($name, $success, $message){
        $this->name = $name;
        $this->success = $success;
        $this->message = $message;
    }

    /**
     * Run through the descendants and if any
     * have failed then set the top level to 
     * fail.
     */
    public function consolidateSuccess(){
        foreach ($this->children as $kid) {
            $kid->consolidateSuccess();
            if(!$kid->success){
                $this->success = false;
                $this->message = $kid->message; 
                break;
            } 
        }
    }

}