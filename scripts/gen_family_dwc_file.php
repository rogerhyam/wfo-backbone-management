<?php

require_once("../config.php");
require_once("../include/WfoDbObject.php");
require_once("../include/Name.php");
require_once("../include/Taxon.php");
require_once("../include/UnplacedFinder.php");

// php -d memory_limit=512M gen_family_dwc_file.php

// get the family and do a crawl down it to load all the name ids
// do we do this by loading objects?

// get all the ones that are genera
// load all the names that have that genus id

// Aquifoliaceae wfo-7000000041
// Ericaceae wfo-7000000218
// Asteraceae wfo-7000000146

$family_name = Name::getName('wfo-7000000218');
$family_taxon = Taxon::getTaxonForName($family_name);

// all the descendants
$descendants = $family_taxon->getDescendants();

// don't forget ourselves
array_unshift($descendants, $family_taxon); 

// get all the linked in synonyms
$synonyms = array();
foreach ($descendants as $descendant) {
    $synonyms = array_merge($synonyms, $descendant->getSynonyms());
}

echo convert(memory_get_usage());
echo "\n";
echo "Total descendants: " . count($descendants);
echo "\n";
echo "Total synonyms: " .count($synonyms);

// now all the unplaced stuff
$unplaced_names = array();
$link_index = array(); // will deduplicate on the way.

echo "\nUnplaced names from taxa";
foreach ($descendants as $taxon) {
    // put it in the link index for later
    $link_index[$taxon->getAcceptedName()->getPrescribedWfoId()] = $taxon;

    // find the associated unplaced names
    $finder = new UnplacedFinder($taxon->getAcceptedName()->getId(), 0, 1000000, true);
    $unplaced_names = array_merge($unplaced_names, $finder->unplacedNames);
}

echo "\nUnplaced names from synonyms";
foreach ($synonyms as $name) {

    // put it in the link index for later
    $link_index[$name->getPrescribedWfoId()] = $name;

    // find associated unplaced names
    $finder = new UnplacedFinder($name->getId(), 0, 1000000, true);
    $unplaced_names = array_merge($unplaced_names, $finder->unplacedNames);
}

// add all the unplaced names to the link_index as well
foreach ($unplaced_names as $name) {
    $link_index[$name->getPrescribedWfoId()] = $name;
}

echo "\n";
echo convert(memory_get_usage());
echo "\n";
echo "Total unplaced: " .count($unplaced_names);
echo "\nTotal links: " .count($link_index);

// now work through all the names and check there are no pointers to things outside our ken
foreach($link_index as $item){
    check_name_links($item);
}

echo "\nTotal links: " .count($link_index);

echo "\n";


// now we can write it out to file :)



// taxonID	scientificNameID	localID	scientificName	taxonRank	parentNameUsageID	scientificNameAuthorship	family	subfamily	tribe	subtribe	genus	subgenus	specificEpithet	infraspecificEpithet	verbatumTaxonRank		nomenclaturalStatus	namePublishedIn	taxonomicStatus	acceptedNameUsageID	originalNameUsageID	taxonRemarks	created	modified	references	excluded

function check_name_links($item){

    global $link_index;

    if(is_a($item, 'Taxon')) $name = $item->getAcceptedName();
    else $name = $item;

    // basionyms first
    $basionym = $name->getBasionym();
    if($basionym){
        // if the basionym isn't in the list add it
        // and check it's links are in the db
        if(!isset($link_index[$basionym->getPrescribedWfoId()])){
            // echo "\n\t" . $name->getFullNameString(false);
            // echo "\n\t\t" . $basionym->getFullNameString(false);
            
            $link_index[$basionym->getPrescribedWfoId()] = $basionym;

            // if the basionym has been place it is in a different family
            $basionym_taxon = Taxon::getTaxonForName($basionym);
            if($basionym_taxon->getId()){

                // add the basionym taxon just incase the basionym is a synonym
                $link_index[$basionym_taxon->getAcceptedName()->getPrescribedWfoId()] = $basionym_taxon;

                // add  all its parents up to family
                $ancestors = $basionym_taxon->getAncestors();
                foreach ($ancestors as $ans) {
                    // echo "\n\t\t\t" . $ans->getAcceptedName()->getFullNameString(false);
                    $link_index[$ans->getAcceptedName()->getPrescribedWfoId()] = $ans;
                    if($ans->getAcceptedName()->getRank() == 'family') break;
                }

            }

        }
    }

}


function convert($size)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

