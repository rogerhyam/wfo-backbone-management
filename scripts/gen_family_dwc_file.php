<?php

require_once("../config.php");
require_once("../include/WfoDbObject.php");
require_once("../include/Name.php");
require_once("../include/Taxon.php");
require_once("../include/UnplacedFinder.php");
require_once("../include/Identifier.php");
require_once("../include/User.php");

// php -d memory_limit=10G gen_family_dwc_file.php

// is the memory thing because we are doing multiples. Should we call it in batches?

// get the family and do a crawl down it to load all the name ids
// do we do this by loading objects?

// get all the ones that are genera
// load all the names that have that genus id

// Aquifoliaceae wfo-7000000041
// Ericaceae wfo-7000000218
// Asteraceae wfo-7000000146
// $family_wfo = 'wfo-7000000218';

$downloads_dir = '../www/downloads/dwc/';
if (!file_exists($downloads_dir)) {
    mkdir($downloads_dir, 0777, true);
}

// get a list of all the families in the taxonomy
$response = $mysqli->query("SELECT i.`value` as wfo, n.`name` 
FROM `names` as n 
JOIN `identifiers` as i on n.prescribed_id = i.id
JOIN `taxon_names` as tn on n.id = tn.name_id
JOIN `taxa` as t on t.taxon_name_id = tn.id
where n.`rank` = 'family'
order by n.name_alpha
");

// We only process 20 per run to keep the memory low
// this script can then be run frequently and will
// add new families if they are added plus update all of 
// them within a finite time

$counter = 0;

while($row = $response->fetch_assoc()){

    // before we start a family we clear the links to old taxa 
    // so we don't run out of memory 
    Taxon::resetSingletons();
    Name::resetSingletons();

    $file_path = $downloads_dir . $row['name'] . '_'. $row['wfo'];

    // if the file is less than a day old then skip it
    if(
        file_exists($file_path . ".zip")
        &&
        filemtime($file_path . ".zip") > time() - (3 * 24 * 60 * 60)
    ){
        continue;
    }

    // file is more than a day old or doesn't exist so lets create it

    echo "\n*** ". $row['name'] ." ***\n";
    process_family($row['wfo'], $file_path);
    $counter++;

    if($counter > 0) break;
    
}

//process_family($family_wfo);

function process_family($family_wfo, $file_path){

        global $ranks_table;

        $family_name = Name::getName($family_wfo);
        $family_taxon = Taxon::getTaxonForName($family_name);

        $creation_datestamp = date('Y-m-d\Th:i:s');
        $creation_date = date('Y-m-d');

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
        $original_link_index = $link_index; // we work on a copy so we aren't changing the array we iterate over.
        foreach($original_link_index as $item){
            check_name_links($item, $link_index);
        }
        echo "\nTotal links: " .count($link_index);

        echo "\n";

        // now we can write it out to file :)
        $out = fopen($file_path . '.csv', 'w');

        $fields = array(
            "taxonID",
            "scientificNameID",
            "localID",
            "scientificName",
            "taxonRank",
            "parentNameUsageID",
            "scientificNameAuthorship",
            "family",
            "subfamily",
            "tribe",
            "subtribe",
            "genus",
            "subgenus",
            "specificEpithet",
            "infraspecificEpithet",
            "verbatimTaxonRank",
            "nomenclaturalStatus",
            "namePublishedIn",
            "taxonomicStatus",
            "acceptedNameUsageID",
            "originalNameUsageID",
            "nameAccordingToID",
            "taxonRemarks",
            "created",
            "modified",
            "references",
            "source",
            "majorGroup",
            "tplID"
        );

        fputcsv($out, $fields);

        foreach ($link_index as $wfo => $item) {
            
            $row = array();

            // we could have been passed either
            if(is_a($item, 'Taxon')){
                $name = $item->getAcceptedName();
                $taxon = $item;
            }else{
                $name = $item;
                $taxon = null;
            }
            
            // we do the name specific stuff first
            // there is always a name.

            // taxonID = prescribed wfo ID
            $row["taxonID"] = $name->getPrescribedWfoId();

            // scientificName = 
            $row["scientificName"] = trim(strip_tags($name->getFullNameString(false,false)));

            // localID

 //           ENUM('ipni', 'tpl', 'wfo', 'if', 'ten', 'tropicos', 'uri', 'uri_deprecated')

            // rank
            $row["taxonRank"] = $name->getRank();

            // scientificNameAuthorship = authorship field
            $row["scientificNameAuthorship"] = $name->getAuthorsString();

            // genus = the genus name part or the name if this is of rank genus
            if($name->getRank() == 'genus'){
                $row["genus"] = $name->getNameString();
            }else{
                $row["genus"] = $name->getGenusString(); // will be empty above genus level
            }
            
            // specificEpithet = species name part if set or the species name if this is of rank species
            if($name->getRank() == 'species'){
                // we are an actual species
                $row["specificEpithet"] = $name->getNameString();
                $row["infraspecificEpithet"] = ''; // nothing in the infraspecificEpithet
            }else{
                if($name->getSpeciesString()){
                    // we are below species level so will have an infraspecific epithet
                    $row["specificEpithet"] = $name->getSpeciesString(); // specificEpithet
                    $row["infraspecificEpithet"] = $name->getNameString(); // infraspecificEpithet
                }else{
                    // we are species or above so these are empty
                    $row["specificEpithet"] = ""; // specificEpithet
                    $row["infraspecificEpithet"] = ""; // infraspecificEpithet
                }
            }
            
            // nomenclaturalStatus = name status field
            switch ($name->getStatus()) {
                case 'invalid':
                    $nomStatus = 'Invalid';
                    break;
                case 'valid':
                    $nomStatus = 'Valid';
                    break;
                case 'illegitimate':
                    $nomStatus = 'Illegitimate';
                    break;
                case 'superfluous':
                    $nomStatus = 'Superfluous';
                    break;
                case 'conserved':
                    $nomStatus = 'Conserved';
                    break;
                case 'rejected': 
                    $nomStatus = 'Rejected';
                    break;
                default:
                    $nomStatus = '';
                    break;
            }

            $row["nomenclaturalStatus"] = $nomStatus;

            // namePublishedIn = citation
            $row["namePublishedIn"] = $name->getCitationMicro();

            // originalNameUsageID = basionym WFO ID
            if($name->getBasionym()){

                // double check the basionym is in the list.
                $basionym_wfo = $name->getBasionym()->getPrescribedWfoId();
                if( isset($link_index[$basionym_wfo]) ){
                    $row["originalNameUsageID"] = $basionym_wfo;
                }else{
                    echo "\n BROKEN BASIONYM LINK FOUND \n";
                    print_r($name);
                    echo "\n-- basionym --\n";
                    print_r($name->getBasionym());
                    exit;
                }

            }else{
                $row["originalNameUsageID"] = null;
            }
            
            // now fields that only taxa == accepted names
            if($taxon){
                
                // parentNameUsageID = For accepted names of taxa only the parent taxon wfo_ID
                $row["parentNameUsageID"] = $taxon->getParent()->getAcceptedName()->getPrescribedWfoId();

                // taxonomicStatus
                $row["taxonomicStatus"] = 'Accepted';

                // acceptedNameUsageID = synonyms only is accepted taxon WFO ID
                $row["acceptedNameUsageID"] = null;

            }else{

                // parentNameUsageID = For accepted names of taxa only the parent taxon wfo_ID
                $row["parentNameUsageID"] = null;

                // we are a name but are we placed or unplaced.
                $placement = Taxon::getTaxonForName($name);

                if(!$placement->getId()){
                    // no taxon in database for the name so unplaced
                    $row["taxonomicStatus"] = 'Unchecked'; // taxonomicStatus
                    $row["acceptedNameUsageID"] = null; // acceptedNameUsageID 
                }else{
                    $row["taxonomicStatus"] = 'Synonym'; // taxonomicStatus
                    $row["acceptedNameUsageID"] = $placement->getAcceptedName()->getPrescribedWfoId(); // acceptedNameUsageID 
                }

            }

            // taxonRemarks	= comments from name field
            $row["taxonRemarks"] = str_replace("\n", " ", substr($name->getComment(), 0, 254)); // a hack to assure compatibility

            // now any identifiers we can think of
            $identifiers = $name->getIdentifiers();

            foreach($identifiers as $identifier) {

                switch ($identifier->getKind()) {
                    case 'ipni':
                        $row['scientificNameID'] = $identifier->getValues()[0];
                        break;
                    case 'tropicos':
                        $row['scientificNameID'] = $identifier->getValues()[0];
                        break;
                    case 'ten':
                        $row['localID'] = $identifier->getValues()[0];
                        break;
                    case 'tpl':
                        $row['tplID'] = $identifier->getValues()[0];
                        break;
                }

            }


            /*

            // references is a deep link into the TEN that supplied the data
            $references = "";
            foreach ($identifiers as $identifier) {
                if($identifier->getKind() == 'uri'){
                    $references = $identifier->getValues()[0]; // just take the first
                }
            }
            $row["references"] = $references;

            // the plant list ID if there is one
            $tpl_id = "";
            foreach ($identifiers as $identifier) {
                if($identifier->getKind() == 'tpl'){
                    $tpl_id = $identifier->getValues()[0]; // just take the first
                }
            }
            $row[] = $tpl_id;

            */

            // source is a string giving the name of where it came from
            // fixme - this is returning the source as it was passed in the botalista dump
            // in the future we need to work it out from the contributors
            $row["source"] = $name->getSource();
            
            // created = from name ? or earliest from name/taxon
            $row["created"] =  date("Y-m-d", strtotime($name->getCreated()));
            // modified	= from name ? 
            $row["modified"] = date("Y-m-d", strtotime($name->getModified()));

            // now we have the extra rows columns
            // if the name is a synonym we use the accepted name.
            $ancestors = null;
            if($taxon){
                $ancestors = $taxon->getAncestors();
            }else{
                $t = Taxon::getTaxonForName($name);
                $ancestors = $t->getAncestors(); // maybe nothing if taxon is empty because this is unplaced name.
            }

            // add the major group first
            // we can't just use the major group of the family because this may be
            // a homonym or something from another major group
            $major_group = "?";
            if($ancestors){
                // we look up the tree if we are attached to the tree
                $major_group_ancestors = $ancestors;
            }else{
                // we look up the tree of the family that is being exported
                // if we are unplaced.
                $major_group_ancestors = $family_taxon->getAncestors();
            }
            foreach($major_group_ancestors as $an){
                // FIXME: These names have to match names in data for this to work
                if($an->getRank() == 'phylum'){
                    switch ($an->getAcceptedName()->getNameString()) {
                        case 'Angiosperms':
                            $major_group = "A";
                            break;
                        case 'Bryophytes':
                            $major_group = "B";
                            break;
                        case 'Gymnosperms':
                            $major_group = "G";
                            break;
                        case 'Pteridophytes':
                            $major_group = "P";
                            break;
                        default:
                            $major_group = $an->getAcceptedName()->getNameString();
                            break;
                    }
                    break;
                }
            }
            $row["majorGroup"] = $major_group;

            // family and other ranks

            // special case. If there are no ancestors (the name is unplaced)
            // we include the family we are processing because it is built by family
            if(!$ancestors || $name->getRank() == 'family'){
                $row['family'] = $family_name->getNameString();
            }
            foreach($ancestors as $an){
                switch ($an->getAcceptedName()->getRank()) {
                    case "family":
                        $row["family"] = $an->getAcceptedName()->getNameString();
                        break;
                    case "subfamily":
                        $row["subfamily"] = $an->getAcceptedName()->getNameString();
                        break;
                    case "tribe":
                        $row["tribe"] = $an->getAcceptedName()->getNameString();
                        break;
                    case "subtribe":
                        $row["subtribe"] = $an->getAcceptedName()->getNameString();
                        break;
                    case "subgenus":
                        $row["subgenus"] = $an->getAcceptedName()->getNameString();
                        break;
                }
            }

            // verbatimTaxonRank
            $above_species = true;
            $rank_abbreviation = "";
            foreach($ranks_table as $rank_name => $rank){

                // we aren't interested in things above species
                if($above_species){
                    if($rank_name == 'species') $above_species = false;
                    continue;
                }

                // we are below species
                if($rank_name == $name->getRank()){
                    $rank_abbreviation = $rank['abbreviation'];
                }
            
            }
            $row["verbatimTaxonRank"] = $rank_abbreviation;

            // write it out to the file
            $csv_row = array();
            foreach($fields as $field){

                if(isset($row[$field])){
                    $csv_row[] = $row[$field];
                }else{
                    $csv_row[] = null;
                }
            }
            fputcsv($out, $csv_row);

        }

        fclose($out);

        // we need to make a personalized attribution string for the eml file.
        // there is default string (applies in most cases)
        // But if there is a curator for the family we use the URI in their profile.
        // In future we could navigate up to find the heirarchy of curators and editors.
        $attribution_text = "This taxonomic group is not currently curated by a WFO TEN (Taxonomic Expert Network).";

        $curators = $family_taxon->getCurators();
        if(count($curators) >0){
            $attribution_text = "This taxonomic group is assigned to the WFO TEN (Taxonomic Expert Network): " . $family_name->getNameString();
            if($curators[0]->getUri()){
                $attribution_text .= " ({$curators[0]->getUri()}).";
            }else{
                ".";
            }
        }
 
        // let's create the zip file with metadata in it

        echo "\nCreating zip\n";
        $zip = new ZipArchive();
        $zip_path = $file_path . ".zip";

        if ($zip->open($zip_path, ZIPARCHIVE::CREATE)!==TRUE) {
            exit("cannot open <$zip_path>\n");
        }

        // create personalize versions of the provenance and meta files for inclusion.

        /*
        $prov_path = $file_path . ".prov.xml";
        $prov = file_get_contents('darwin_core_prov.xml');
        $prov = str_replace('{{family}}', $family_name->getNameString(), $prov);
        $prov = str_replace('{{date}}', $creation_date, $prov);
        file_put_contents($prov_path, $prov);
        */

        $meta_path = $file_path . ".meta.xml";
        $meta = file_get_contents('darwin_core_meta.xml');
        $meta = str_replace('{{family}}', $family_name->getNameString(), $meta);
        $meta = str_replace('{{date}}', $creation_date, $meta);
        file_put_contents($meta_path, $meta);

        $eml_path = $file_path . ".eml.xml";
        $eml = file_get_contents('darwin_core_eml.xml');
        $eml = str_replace('{{family}}', $family_name->getNameString(), $eml);
        $eml = str_replace('{{date}}', $creation_date, $eml);
        $eml = str_replace('{{datestamp}}', $creation_datestamp, $eml);
        $eml = str_replace('{{attribution}}', $attribution_text, $eml);

        file_put_contents($eml_path, $eml);

        $zip->addFile($file_path . ".csv", "taxonomy.csv");
        //$zip->addFile($prov_path, "prov.xml");
        $zip->addFile($eml_path, "eml.xml");
        $zip->addFile($meta_path, "meta.xml");

        if ($zip->close()!==TRUE) {
            exit("cannot close <$zip_path>\n". $zip->getStatusString());
        }

        echo "Removing temp files\n";
       // unlink($file_path . ".csv");
        unlink($meta_path);
        //unlink($prov_path);
        unlink($eml_path);
}

function getBotalistaRow($taxon_id){

    global $mysqli;

    $result = $mysqli->query("SELECT * from botalista_dump_2 where taxonID = '$taxon_id'");
    if($result->num_rows) return $result->fetch_assoc();
    else return array();

}

function check_name_links($item, &$link_index){

    if(is_a($item, 'Taxon')) $name = $item->getAcceptedName();
    else $name = $item;

    if($name->getPrescribedWfoId() == "wfo-0001044126"){
        echo "\n checking name links \n";
    }

    // basionyms first
    $basionym = $name->getBasionym();
    if($basionym){
        // if the basionym isn't in the list add it
        // and check it's links are in the db
        if(!isset($link_index[$basionym->getPrescribedWfoId()])){
            //echo "\n\t" . $name->getFullNameString(false) . " " . $name->getPrescribedWfoId();
            //echo "\n\t\t" . $basionym->getFullNameString(false);
            
            $link_index[$basionym->getPrescribedWfoId()] = $basionym;

            // if the basionym has been place it is in a different family
            $basionym_taxon = Taxon::getTaxonForName($basionym);
            if($basionym_taxon->getId()){

                // add the basionym taxon just incase the basionym is a synonym
                $link_index[$basionym_taxon->getAcceptedName()->getPrescribedWfoId()] = $basionym_taxon;

                // the accepted name might have a basionym
                if($basionym_taxon->getAcceptedName()->getBasionym()){
                    $link_index[$basionym_taxon->getAcceptedName()->getBasionym()->getPrescribedWfoId()] = $basionym_taxon->getAcceptedName()->getBasionym(); 
                    //echo "\n\t\t\t" . $basionym_taxon->getAcceptedName()->getBasionym()->getFullNameString(false);
                }

                // add  all its parents up to family
                $ancestors = $basionym_taxon->getAncestors();
                foreach ($ancestors as $ans) {
                    //echo "\n\t\t\t" . $ans->getAcceptedName()->getFullNameString(false);
                    $link_index[$ans->getAcceptedName()->getPrescribedWfoId()] = $ans;
                    check_name_links($ans, $link_index);
                    if($ans->getAcceptedName()->getRank() == 'family') break;
                }

            }

            check_name_links($basionym, $link_index);

        }
    }

}


function convert($size)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

