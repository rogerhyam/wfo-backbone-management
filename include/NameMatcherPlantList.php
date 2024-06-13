<?php

/*

    This is a utility class that allows us to call the 
    PlantList Solr based name matcher from within the Rhakhis code base.
    It returns NameMatches like the regular name matcher does.

*/

class NameMatcherPlantList extends NameMatcher{

    /**
     * Parse a string to find the best matches
     * 
     * @param string $query Presumed to be a reasonably well formed botanical name
     * @param bool $incomplete Flag if the name might be truncated. Used to respond to someone typing a name in.
     */
    function stringMatch($queryString, $incomplete=false){

        // remove any html crap
        $this->queryString = strip_tags($queryString);

        // we use the simple rest service to get a json object
        $query_uri = PLANT_LIST_URI_STAGING . "/matching_rest.php?input_string=" . urlencode($this->queryString);
        $json = file_get_contents($query_uri);
        $response = json_decode($json);
        
        //print_r($response);
        
        // now convert it to NameMatches so it can work like the regular name matcher
        $matches = new NameMatches();
        $matches->queryString = $this->queryString;

        if(isset($response->parsedName->canonical_form)){
            $matches->nameParts = explode(' ', $response->parsedName->canonical_form);
        }else{
            $matches->nameParts = array();
        }

        if(isset($response->parsedName->rank)){
            $matches->rank = $response->parsedName->rank;
        }else{
            $matches->rank = null;
        }

        if(isset($response->parsedName->author_string)){
            $matches->authors = $response->parsedName->author_string;
        }else{
            $matches->authors = null;
        }

        
        $matches->names = array(); // to be filled in below
        $matches->distances = array(); // to be filled in below

        // if we have a single match then 
        if($response->match && count($response->candidates) == 0){
            $matches->names[] = Name::getName($response->match->wfo_id);
            $matches->distances[] = 0;
        }else{

            // problem - single candidate will look like a match!
            if(count($response->candidates) > 1){
                $dist = 0;
                foreach($response->candidates as $candidate){
                    $matches->names[] = Name::getName($candidate->wfo_id);
                    $matches->distances[] = $dist;
                    $dist++;
                }
            }
     
        }
        
        return $matches;
       // print_r($matches);
      
    }


}