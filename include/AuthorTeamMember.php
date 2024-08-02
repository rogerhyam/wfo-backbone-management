<?php

/**
 * A convenience class representing the 
 * values of an author team member 
 * so we can return then for a GraphQL call
 * 
 */
class AuthorTeamMember{

    public ?string $abbreviation = null;
    public ?string $label = null;
    public ?string $wikiUri = null;
    public ?string $imageUri = null;
    public ?bool $referencePresent = false;

    public function __construct($abbreviation, $label, $wikiUri, $imageUri, $refPresent){
        $this->abbreviation = $abbreviation;
        $this->label = $label;
        $this->wikiUri = $wikiUri;
        $this->imageUri = $imageUri;
        $this->referencePresent = $refPresent;
    }

}