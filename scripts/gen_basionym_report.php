<?php

/*
    generate CSV file of basionym issues for an accepted taxon and all 
    its decendants


    names with parentheses 394,382
    names with parentheses but no basionym 143,245


    Are all the types in the same taxon?

*/

require_once("../config.php");
require_once("../include/WfoDbObject.php");
require_once("../include/Name.php");
require_once("../include/Taxon.php");

echo "\nBasionym Issue Finder\n";

if(count($argv) < 2){
    echo "\nYou need to specify a WFO ID for scanning";
    exit;
}

$wfo_id = $argv[1];

if(!preg_match('/^wfo-[0-9]{10}$/', $wfo_id)){
    echo "\nThat doesn't look like a well formed WFO ID: '$wfo_id'";
    exit;
}

// we got a good id. Let's see if it is an accepted name
$name = Name::getName($wfo_id);
$name_display = strip_tags($name->getFullNameString());
echo "\n$wfo_id\t$name_display";

$taxon = Taxon::getTaxonForName($name);

if(!$taxon->getId()){
    echo "\nThis is an unplaced name. You need to specify a placed one.";
    exit;
}

if($taxon->getAcceptedName()->getId() != $name->getId()){
    $accepted_display = strip_tags($taxon->getAcceptedName()->getFullNameString());
    echo "\nThis is a synonym of accepted name '$accepted_display'. You need to specify an accepted name.";
    exit;
}

// OK - we have a good taxon name. Let's start work.
$taxa = $taxon->getDescendants();
array_unshift($taxa, $taxon);

foreach($taxon as $t){

    $n = $t->getAcceptedName();

    // get all the homontypic names
    // they should all be synonyms of this taxon.
    $homotypics = $n->getHomotypicNames();
    foreach($homontypics as $h){
        $syns = $t->getSynonyms();
        foreach($syns as $s){
            if($s->getId() == $h->getId()) continue 2;
        }

        // this is an ERROR
        // we haven't found it amongst the synonyms so where is it?
        // FIXME

    }

    // if it has parentheses but no basionym then it is an error.
    // can we find a basionym for it?




}


echo "\n{$taxon->getId()}";
// is it an existing name?


