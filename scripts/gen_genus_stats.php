<?php

require_once("../config.php");
require_once("../include/WfoDbObject.php");
require_once("../include/Name.php");
require_once("../include/Taxon.php");
require_once("../include/UnplacedFinder.php");
require_once("../include/Identifier.php");
require_once("../include/User.php");

/*

    keeps the stats_genera table up to date

*/

// we need a list of families placed in the taxonomy so we can try and find
// orders etc from hints in unplaced names
$families = array();
$response = $mysqli->query("select n.id, n.`name` from names as n join taxon_names as tn  on tn.name_id = n.id where n.rank = 'family'");
while($row = $response->fetch_assoc()){
    $families[$row['name']] = $row['id'];
}
$response->close();

// get a list of all the NAMES with the rank genus
// 24k rows
/*
$sql = "select n.id, name_alpha, sg.`created` from
names as n 
left join stats_genera as sg on n.id = sg.name_id
where n.rank = 'genus'
and n.status != 'deprecated' 
and sg.created is null
limit 1000";
*/

$interval = '30 DAY';
if(isset($argv[1])) $interval = $argv[1];

$sql = "select n.id, name_alpha from
names as n 
left join stats_genera as sg on n.id = sg.name_id
where n.rank = 'genus'
and n.status != 'deprecated' 
and (sg.modified is null OR sg.modified < now() - interval $interval)
limit 1000";

$response = $mysqli->query($sql);


while($row = $response->fetch_assoc()){

    $stats = array();
    $stats['name_id'] = (int)$row['id'];
    $stats['wfo'] = '';
    $stats['name'] = '';
    $stats["role"] = 'undefined';
    $stats["phylum"] = 'Unknown';
    $stats["phylum_wfo"] = "";
    $stats["family"] = 'Unknown';
    $stats["family_wfo"] = "";
    $stats["order"] = 'Unknown';
    $stats["order_wfo"] = "";
    $stats["taxa"] = 0;
    $stats["taxa_with_editors"] = 0;
    $stats["species"] = 0;
    $stats["subspecies"] = 0;
    $stats["variety"] = 0;
    $stats["synonyms"] = 0;
    $stats["syn_species"] = 0;
    $stats["syn_subspecies"] = 0;
    $stats["syn_variety"] = 0;
    $stats["unplaced"] = 0;
    $stats["unplaced_species"] = 0;
    $stats["unplaced_subspecies"] = 0;
    $stats["unplaced_variety"] = 0;
    $stats["gbif_gap_species"] = 0;
    $stats["gbif_gap_total_occurrences"] = 0;
    $stats["gbif_gap_mean"] = 0;
    $stats["gbif_gap_stddev"] = 0;


    $name = Name::getName($row['id']);
    $stats['name'] = $name->getNameString();
    $stats['wfo'] = $name->getPrescribedWfoId();

    $taxon = Taxon::getTaxonForName($name);

    $unplaced_names = array();
    $synonyms = array();
    $descendants = array();

    if($taxon->getId() > 0){
        // we are placed in the taxonomy

        if($taxon->getAcceptedName() == $name){

            // we are accepted
            $stats["role"] = 'accepted';

            $descendants = $taxon->getDescendants();
        
            // add ourselves now
            array_unshift($descendants, $taxon);

            $stats["taxa"] = count($descendants);

            // work through the descendants
            foreach($descendants as $kid){
                if($kid->getRank() == 'species') $stats["species"]++;
                if($kid->getRank() == 'subspecies') $stats["subspecies"]++;
                if($kid->getRank() == 'variety') $stats["variety"]++;

                // each kid might have synonyms
                $synonyms = array_merge($synonyms, $kid->getSynonyms());

                // each kid might have unplaced names associated with it
                $finder = new UnplacedFinder($kid->getAcceptedName()->getId(), 0, 1000000, true);
                $unplaced_names = array_merge($unplaced_names, $finder->unplacedNames);

                // how many have editors?
                $editors = $kid->getEditors();
                if(count($editors) > 0) $stats["taxa_with_editors"]++;

            }

            $stats["synonyms"] = count($synonyms);

            // each synonym might have unplaced names associated with it
            foreach($synonyms as $syn){
                $finder = new UnplacedFinder($syn->getId(), 0, 1000000, true);
                $unplaced_names = array_merge($unplaced_names, $finder->unplacedNames);
            }
            
            $stats["unplaced"] = count($unplaced_names);

        }else{

            // we are a synonym name
            $stats["role"] = 'synonym';

            // we just have unplaced names
            $finder = new UnplacedFinder($name->getId(), 0, 1000000, true);
            $unplaced_names = array_merge($unplaced_names, $finder->unplacedNames);
            $stats["unplaced"] = count($unplaced_names);

        }

        // whether a synonym or accepted we work up the way to find the family and order
        $ancestors = $taxon->getAncestors();
        foreach ($ancestors as $anc) {

            if($anc->getRank() == 'family'){
                $stats['family'] = $anc->getAcceptedName()->getNameString();
                $stats['family_wfo'] = $anc->getAcceptedName()->getPrescribedWfoId();
            } 

            if($anc->getRank() == 'order'){
                $stats['order'] = $anc->getAcceptedName()->getNameString();
                $stats['order_wfo'] = $anc->getAcceptedName()->getPrescribedWfoId();
            }

            if($anc->getRank() == 'phylum'){
                $stats['phylum'] = $anc->getAcceptedName()->getNameString();
                $stats['phylum_wfo'] = $anc->getAcceptedName()->getPrescribedWfoId();
            } 
        }

    }else{

        // we are unplaced - only has possible associated unplaced names
        $stats["role"] = 'unplaced';
        $finder = new UnplacedFinder($name->getId(), 0, 1000000, true);
        $unplaced_names = array_merge($unplaced_names, $finder->unplacedNames);
        $stats["unplaced"] = count($unplaced_names);


        // how do we find the family and order for a name not in the taxonomy?
        $hints = $name->getHints();
        foreach ($hints as $hint) {
            if(in_array($hint, array_keys($families))){

                $family_name = Name::getName($families[$hint]);

                // the family is easy
                $stats['family'] = $family_name->getNameString();
                $stats['family_wfo'] = $family_name->getPrescribedWfoId();

                // the order is up the way from the family
                $family_taxon = Taxon::getTaxonForName($family_name);
                $ancestors = $family_taxon->getAncestors();
                foreach ($ancestors as $anc) {

                    if($anc->getRank() == 'order'){
                        $stats['order'] = $anc->getAcceptedName()->getNameString();
                        $stats['order_wfo'] = $anc->getAcceptedName()->getPrescribedWfoId();
                    }

                    if($anc->getRank() == 'phylum'){
                        $stats['phylum'] = $anc->getAcceptedName()->getNameString();
                        $stats['phylum_wfo'] = $anc->getAcceptedName()->getPrescribedWfoId();
                    } 
                
                }

                break;
            }
        }
    }

    // break down the unplaced names by rank
    foreach($unplaced_names as $un){
        if($un->getRank() == 'species') $stats["unplaced_species"]++;
        if($un->getRank() == 'subspecies') $stats["unplaced_subspecies"]++;
        if($un->getRank() == 'variety') $stats["unplaced_variety"]++;
    }

    // break down the synonyms by rank
    foreach($synonyms as $kid){
        if($kid->getRank() == 'species') $stats["syn_species"]++;
        if($kid->getRank() == 'subspecies') $stats["syn_subspecies"]++;
        if($kid->getRank() == 'variety') $stats["syn_variety"]++;
    }

    // finally look at the GBIF gap analysis.
    // we just use the fa
    $gap_response = $mysqli->query("SELECT
        count(*) as species, 
        sum(g.count) as total_occurrences,
        avg(g.count) as mean,
        stddev(g.count) as stddev 
        FROM `gbif_occurrence_count` as g
        JOIN `names` as n ON n.id= g.name_id
        where n.genus = '{$name->getNameString()}'
        AND g.count > 0;");
    if($gap_response->num_rows > 0){
        $gap_row = $gap_response->fetch_assoc();
        $stats["gbif_gap_species"] = $gap_row['species'] ? $gap_row['species'] : 0;
        $stats["gbif_gap_total_occurrences"] = $gap_row['total_occurrences'] ? $gap_row['total_occurrences'] : 0;
        $stats["gbif_gap_mean"] = $gap_row['mean']? $gap_row['mean'] : 0;
        $stats["gbif_gap_stddev"] = $gap_row['stddev']? $gap_row['stddev'] : 0;
    }

    // OK we have the stats what do we do with them!?
    // print_r($stats);

    $sql = "INSERT INTO `stats_genera` (`";
    $sql .= implode('`, `', array_keys($stats));
    $sql .= "`) VALUES (";

    $i = 0;
    $n = count($stats);
    foreach ($stats as $key => $value) {
        if(is_string($value)){
            $sql .= "'$value'";
        }else{
            $sql .= $value;
        }
        if(++$i !== $n) $sql .= ", ";
    }

    $sql .= ") \nON DUPLICATE KEY UPDATE ";

    foreach ($stats as $key => $value) {
        if($key == 'name_id'){
            continue;
        }
        if(is_string($value)){
            $sql .= "\n`$key` = '$value'";
        }else{
            $sql .= "\n`$key` = $value";
        }
        $sql .= ", ";
    }

    $sql .= "\n`modified` = CURRENT_TIMESTAMP;"; // we force the modified date even if they data hasn't changed.

   $mysqli->query($sql);

   echo "\n" . $name->getNameString() . "\t" . convert(memory_get_usage()) ;

}

function convert($size){
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}