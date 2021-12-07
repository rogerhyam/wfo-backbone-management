<?php

// Something to return from all mutation calls to the GraphQL interface

class UpdateResponse{

    public int $status; // an internal status not shared through GraphQL
    public string $name; // the name of the field or of the mutation called
    public bool $success; // did it work?
    public string $message; // an overall message on success or failure of update
    public Array $children = array(); // a list of sub-UpdateResponses (typically one for each field)
    public Array $taxa = array();
    public Array $names = array();

    public function __construct($name, $success, $message){
        $this->name = $name;
        $this->success = $success;
        $this->message = $message;
    }

}
