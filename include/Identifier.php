<?php

// because GraphQL doesn't do a kind value pairs class.
// this is a little different because it is kind valueS.

class Identifier{

    private string $kind;
    private array $values;
    private ?string $preferredValue = null;
    private string $displayName;

    private array $displayNameMap = array(
            'ipni' => "IPNI",
            'tpl' => "The Plant List",
            'wfo' => "WFO ID",
            'ten' => "TEN internal",
            'tropicos' => "Tropicos",
            'uri' => 'Web Link',
            'uri_deprecated' => "Web Link (Deprecated)",
            'rhakhis_name_id' => "Rhakhis Internal Name ID",
            'rhakhis_taxon_id' => "Rhakhis Internal Taxon ID",
            'gbif' => "GBIF Taxon ID"
    );

    public function __construct($kind, $values, $preferred_value = null){
        $this->kind = $kind;
        $this->values = $values;
        $this->preferredValue = $preferred_value;
    }

    public function getKind(){
        return $this->kind;
    }

    public function getValues(){
        return $this->values;
    }

    public function getPreferredValue(){
        return $this->preferredValue;
    }

    public function isPreferredValue($candidate){
        return $candidate == $this->preferredValue ? true : false;
    }

    public function getDisplayName(){
        if(array_key_exists($this->kind, $this->displayNameMap)){
            return $this->displayNameMap[$this->kind];
        }else{
            return $this->kind;
        }
    }


}