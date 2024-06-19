<?php

/*
    This class handles the parsing of author strings and suggestion of 
    authors the string may contain.
    It maintains the authors lookup table.
*/

class AuthorTeam{

    public $authors = array();
    public $team_string;

    public function __construct($team_abbreviation, $call_wikidata = false){
        $matches = array();
        if(preg_match('/\((.*)\)(.*)/', $team_abbreviation ?? '', $matches)){
            $this->extractIndividualAuthors(trim($matches[1] ?? ''));
            $this->extractIndividualAuthors(trim($matches[2] ?? ''));
        }else{
            $this->extractIndividualAuthors(trim($team_abbreviation ?? ''));
        }

        $this->populateAuthors($call_wikidata);
        $this->team_string = $team_abbreviation;

    }

    private function extractIndividualAuthors($team){
        // replace the &  with a comma so lists are uniform
        $t = str_replace(' & ',',',$team);
        $t = str_replace(' ex ',',',$t);
        $t = str_replace(' in ',',',$t);
        $t = str_replace(';',',',$t);
        $authors = explode(',', $t);
        foreach($authors as $author){
            $this->authors[trim($author)] = null;
        }
    }

    private function populateAuthors($call_wikidata){

        global $mysqli;

        // try and get it from the db

        // if it isn't in the db and we are flagged to call wikidata then
        // try and do that

        foreach ($this->authors as $abbreviation => $author) {

            if(!$author){

                $stmt = $mysqli->prepare("SELECT id, label, uri, image_uri, birth, death from author_lookup WHERE abbreviation = ?");
                echo $mysqli->error;
                $stmt->bind_param("s", $abbreviation);
                $stmt->execute();
                $stmt->bind_result(
                    $author['id'],
                    $author['label'],
                    $author['person'],
                    $author['image'],
                    $author['birth'],
                    $author['death']
                );
                if($stmt->fetch() && $author){

                    echo $mysqli->error;
                    $this->authors[$abbreviation] = $author;
 
                }else{
                    // not got one in the db 
                    if($call_wikidata){
                        $this->populateAuthorFromWikiData($abbreviation);
                    }
                }
                $stmt->close();

            }
        }

    }

    private function populateAuthorFromWikiData($author){


        $endpointUrl = 'https://query.wikidata.org/sparql';
        $sparqlQueryString = 
            "SELECT ?person ?personLabel ?birth ?death ?image
            where{
                ?person wdt:P428 \"$author\" .
                OPTIONAL{?person wdt:P569 ?birth} .
                OPTIONAL{?person wdt:P570 ?death} .
                OPTIONAL{?person wdt:P18 ?image} .
                SERVICE wikibase:label { bd:serviceParam wikibase:language \"en\"}
            }
            LIMIT 10";

        $queryDispatcher = new SPARQLQueryDispatcher($endpointUrl);
        try {
            $queryResult = $queryDispatcher->query($sparqlQueryString);

            if(isset($queryResult['results']['bindings']) && count($queryResult['results']['bindings']) > 0){
                $fields = $queryResult['results']['bindings'][0];

                if(isset($fields['person'])){
                    $this->authors[$author]['person'] = $fields['person']['value'];
                }else{
                    $this->authors[$author]['person'] =  null;
                }

                if(isset($fields['personLabel'])){
                    $this->authors[$author]['label'] = $fields['personLabel']['value'];
                }else{
                    $this->authors[$author]['label'] =  null;
                }

                if(isset($fields['birth'])){
                    $this->authors[$author]['birth'] = $fields['birth']['value'];
                }else{
                    $this->authors[$author]['birth'] =  null;
                }
                
                if(isset($fields['death'])){
                    $this->authors[$author]['death'] = $fields['death']['value'];
                }else{
                    $this->authors[$author]['death'] =  null;
                }

                if(isset($fields['image'])){
                    $this->authors[$author]['image'] = $fields['image']['value'];
                }else{
                    $this->authors[$author]['image'] =  null;
                }

            }else{

                $this->authors[$author]['person'] =  null;
                $this->authors[$author]['label'] =  null;
                $this->authors[$author]['birth'] =  null;
                $this->authors[$author]['death'] =  null;
                $this->authors[$author]['image'] =  null;

            }


        } catch (\Throwable $th) {
           echo "\n$th\n";
        }
        
        // save author if we have content or not
        $this->saveAuthorToDatabase($author);

    }

    private function saveAuthorToDatabase($author_abbrev){

        global $mysqli;

        $author = $this->authors[$author_abbrev];

        $stmt = $mysqli->prepare("INSERT INTO author_lookup (abbreviation, label, uri, image_uri, birth, death) VALUES (?,?,?,?,?,?)");
        echo $mysqli->error;
        $birth_date = substr($author['birth'], 0, 10);
        if(!preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $birth_date)) $birth_date = null;
        $death_date = substr($author['death'], 0, 10);
        if(!preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $death_date)) $death_date = null;
        $stmt->bind_param("ssssss", $author_abbrev, $author['label'], $author['person'], $author['image'], $birth_date, $death_date);
        $stmt->execute();
        echo $mysqli->error;
        $stmt->close(); 

    }


    public function getHtmlAuthors(){

        $out = '<span class="wfo-list-authors">';

        $s = $this->team_string;
        foreach ($this->authors as $abbrev => $author) {
            
            if(!$author) continue;
            if(!$author['person']) continue;

            $title = $author['label'];

            if($author['birth'] || $author['death']){
                $title .= " " . substr($author['birth'] ?? '', 0, 4) . "-" . substr($author['death'] ?? '', 0, 4);
            }
            
            $link = '<a href="' . $author['person'] . '" title="'. $title .'">' . $abbrev . '</a>';
            $s = str_replace($abbrev, $link, $s);

        }

        $out .= $s;

        $out .= "</span>";

        return $out;

    }

    public function getAuthorLabels(){
        $out = array();
        foreach ($this->authors as $abbrev => $author) {
            if(!$author) continue;
            if($author['label']) $out[] = $author['label'];
        }
        return $out;
    }

    public function getAuthorIds(){
        $out = array();
        foreach ($this->authors as $abbrev => $author) {
            if(!$author) continue;
            if($author['person']) $out[] = $author['person'];
        }
        return $out;
    }

}

?>