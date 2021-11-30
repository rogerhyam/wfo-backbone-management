<?php

/**
 * 
 * Simple representation of a matched name.
 * 
 */
class NameMatches{

    public Array $names = array();
    public String $queryString;
    public Array $nameParts = array();
    public ?String $rank = null;
    public ?String $authors = null;
    public Array $distances = array();

}