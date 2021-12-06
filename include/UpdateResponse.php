<?php

// Something to return from all mutation calls to the GraphQL interface

class UpdateResponse{

    public string $name; // the name of the field or of the mutation called
    public bool $success; // did it work?
    public string $message; // an overall message on success or failure of update
    public Array $children = array(); // a list of sub-UpdateResponses (typically one for each field)

}
