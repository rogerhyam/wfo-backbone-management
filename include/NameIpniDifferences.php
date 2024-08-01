<?php

/**
 * A wrapper class that will
 * fetch data associated with a 
 * name at IPNI based on its preferred IPNI ID
 * if any
 * 
 */
class NameIpniDifferences{

    public string $id = '';
    public bool $retrieved = false;
    public int $differenceCount = 0;
    public ?string $nameString = null;
    public ?string $genusString = null;
    public ?string $speciesString = null;
    public ?string $authorsString = null;
    public ?string $citationMicro = null;

    public function __construct(Name $name){

        global $ranks_table;

        if($name->getPreferredIpniId()){

            $this->id = $name->getPreferredIpniId();
            
            // we have a preferred IPNI Id so let's call IPNI and get the RDF
            $id = substr($name->getPreferredIpniId(), strrpos($name->getPreferredIpniId(), ':') + 1);
            $rdf = @file_get_contents("https://www.ipni.org/n/{$id}/rdf");

            if($rdf !== false){
                $xml = simplexml_load_string($rdf);
                
                $this->retrieved = true;

                error_log($xml->asXML());

                // authorship is straight forward
                $authorship = $xml->xpath("//rdf:RDF/tn:TaxonName/tn:authorship");
                if($authorship && $authorship[0] != $name->getAuthorsString()){
                    $this->differenceCount++;
                    $this->authorsString = $authorship[0];
                }


                // citation is a combination field
                /*
                <tcom:publishedIn>Fl. Serres Jard. Eur.</tcom:publishedIn>
                <tcom:microReference>ix. (1853-54) 80; et in Rev. Hort. Ser. IV. iii. (1854) 64</tcom:microReference>
                <tn:year>1956</tn:year>
                */

                $citation = $xml->xpath("//rdf:RDF/tn:TaxonName/tcom:publishedIn");
                if($citation) $citation = $citation[0];
                $micro = $xml->xpath("//rdf:RDF/tn:TaxonName/tcom:microReference");
                if($micro) $citation .= ' ' . $micro[0];
                $year = $xml->xpath("//rdf:RDF/tn:TaxonName/tn:year");
                if($year) $citation .= ' (' . $year[0] . ')';

                if($citation && $citation != $name->getCitationMicro()){
                    $this->differenceCount++;
                    $this->citationMicro = $citation;
                }


                // They weirdly put the full name in the uninomial - should just be the name part
                // <tn:uninomial>Apium nodiflorum subsp. mairei</tn:uninomial>
                // we can look at the last word no matter the rank
                $name_string = $xml->xpath("//rdf:RDF/tn:TaxonName/tn:uninomial");
                if($name_string){
                    $parts = explode(' ', $name_string[0]);
                    $name_string = end($parts);
                    if($name_string !== $name->getNameString()){
                        $this->differenceCount++;
                        $this->nameString = $name_string;
                    }
                }

                // Other parts are dependent on rank
                $our_level = array_search($name->getRank(), array_keys($ranks_table));
                $species_level = array_search('species', array_keys($ranks_table));
                $genus_level = array_search('genus', array_keys($ranks_table));

                // binomial + are below genus down to species
                if($our_level > $genus_level){
                    // <tn:genusPart>Apium</tn:genusPart>
                    $genus_string = $xml->xpath("//rdf:RDF/tn:TaxonName/tn:genusPart");
                    if($genus_string && $genus_string[0] != $name->getGenusString()){
                        $this->differenceCount++;
                        $this->genusString = $genus_string[0];
                    }

                    // missing a genus string at this level is also an issue
                    if(!$genus_string || !$genus_string[0]){
                        $this->differenceCount++;
                        $this->genusString = 'MISSING';
                    }

                }

                // trinomial names are below species
                if($our_level > $species_level){
                    /*
                    <tn:specificEpithet>nodiflorum</tn:specificEpithet>
                    */
                    $species_string = $xml->xpath("//rdf:RDF/tn:TaxonName/tn:specificEpithet");
                    if($species_string && $species_string[0] != $name->getSpeciesString()){
                        $this->differenceCount++;
                        $this->speciesString = $species_string[0];
                    }

                    // missing a genus string at this level is also an issue
                    if(!$species_string || !$species_string[0]){
                        $this->differenceCount++;
                        $this->speciesString = 'MISSING';
                    }

                }

               // print_r($citation_elements);
                // we have some RDF represening 


            }


        }

    }

}