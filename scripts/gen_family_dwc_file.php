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

    if($counter > 9) break;
    
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

        $header = array(
            "taxonID",
            "scientificName",
            "taxonRank",
            "scientificNameAuthorship",
            "genus",
            "specificEpithet",
            "infraspecificEpithet",
            "nomenclaturalStatus",
            "namePublishedIn",
            "originalNameUsageID",
            "parentNameUsageID",
            "taxonomicStatus",
            "acceptedNameUsageID",
            "taxonRemarks",
            "references",
            "tplID",
            "source",
            "created",
            "modified",
            "majorGroup"
        );

        // we need a list of higher ranks
        // those above genus and below family
        $extra_ranks = array();

        $upper_level = array_search("order", array_keys($ranks_table));
        $genus_level = array_search("species", array_keys($ranks_table));
        for ($i=0; $i < count($ranks_table) ; $i++) { 
            if($i < $upper_level) continue; // not got there yet
            if($i >= $genus_level) continue; // gone past it
            if(array_keys($ranks_table)[$i] == 'genus') continue; // the genus is part of the name so not added here
            $header[] =  array_keys($ranks_table)[$i]; // add it to the header
            $extra_ranks[] =  array_keys($ranks_table)[$i]; // add it to the header
        }

        $out = fopen($file_path . ".csv", 'w');

        // add in the extra fields william asked for
        $header[] = "verbatimTaxonRank";
        $header[] = "scientificNameId";
        $header[] = "localId";
        $header[] = "doNotProcess";
        $header[] = "doNotProcess_reason";
        $header[] = "deprecated";
        $header[] = "nameAccordingToID";

        fputcsv($out, $header);

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
            $row[] = $name->getPrescribedWfoId();

            // scientificName = 
            $row[] = trim(strip_tags($name->getFullNameString(false,false)));

            // rank
            $row[] = $name->getRank();

            // scientificNameAuthorship = authorship field
            $row[] = $name->getAuthorsString();

            // genus = the genus name part or the name if this is of rank genus
            if($name->getRank() == 'genus'){
                $row[] = $name->getNameString();
            }else{
                $row[] = $name->getGenusString(); // will be empty above genus level
            }
            
            // specificEpithet = species name part if set or the species name if this is of rank species
            if($name->getRank() == 'species'){
                // we are an actual species
                $row[] = $name->getNameString();
                $row[] = ''; // nothing in the infraspecificEpithet
            }else{
                if($name->getSpeciesString()){
                    // we are below species level so will have an infraspecific epithet
                    $row[] = $name->getSpeciesString(); // specificEpithet
                    $row[] = $name->getNameString(); // infraspecificEpithet
                }else{
                    // we are species or above so these are empty
                    $row[] = ""; // specificEpithet
                    $row[] = ""; // infraspecificEpithet
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

            $row[] = $nomStatus;

            // namePublishedIn = citation
            $row[] = $name->getCitationMicro();

            // originalNameUsageID = basionym WFO ID
            if($name->getBasionym()){

                // double check the basionym is in the list.
                $basionym_wfo = $name->getBasionym()->getPrescribedWfoId();
                if( isset($link_index[$basionym_wfo]) ){
                    $row[] = $basionym_wfo;
                }else{
                    echo "\n BROKEN BASIONYM LINK FOUND \n";
                    print_r($name);
                    echo "\n-- basionym --\n";
                    print_r($name->getBasionym());
                    exit;
                }

            }else{
                $row[] = null;
            }
            
            // now fields that only taxa == accepted names
            if($taxon){
                
                // parentNameUsageID = For accepted names of taxa only the parent taxon wfo_ID
                $row[] = $taxon->getParent()->getAcceptedName()->getPrescribedWfoId();

                // taxonomicStatus
                $row[] = 'Accepted';

                // acceptedNameUsageID = synonyms only is accepted taxon WFO ID
                $row[] = null;

            }else{

                // parentNameUsageID = For accepted names of taxa only the parent taxon wfo_ID
                $row[] = null;

                // we are a name but are we placed or unplaced.
                $placement = Taxon::getTaxonForName($name);

                if(!$placement->getId()){
                    // no taxon in database for the name so unplaced
                    $row[] = 'Unchecked'; // taxonomicStatus
                    $row[] = null; // acceptedNameUsageID 
                }else{
                    $row[] = 'Synonym'; // taxonomicStatus
                    $row[] = $placement->getAcceptedName()->getPrescribedWfoId(); // acceptedNameUsageID 
                }

            }

            // taxonRemarks	= comments from name field
            $row[] = str_replace("\n", " ", substr($name->getComment(), 0, 254)); // a hack to assure compatibility

            // now any identifiers we can think of
            $identifiers = $name->getIdentifiers();

            // references is a deep link into the TEN that supplied the data
            $references = "";
            foreach ($identifiers as $identifier) {
                if($identifier->getKind() == 'uri'){
                    $references = $identifier->getValues()[0]; // just take the first
                }
            }
            $row[] = $references;

            // the plant list ID if there is one
            $tpl_id = "";
            foreach ($identifiers as $identifier) {
                if($identifier->getKind() == 'tpl'){
                    $tpl_id = $identifier->getValues()[0]; // just take the first
                }
            }
            $row[] = $tpl_id;

            // source is a string giving the name of where it came from
            // fixme - this is returning the source as it was passed in the botalista dump
            // in the future we need to work it out from the contributors
            $row[] = $name->getSource();
            
            // created = from name ? or earliest from name/taxon
            $row[] =  date("Y-m-d", strtotime($name->getCreated()));
            // modified	= from name ? 
            $row[] = date("Y-m-d", strtotime($name->getModified()));

            // now we have the extra rows columns
            // if the name is a synonym we use the
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
            $row[] = $major_group;

            // then all the other ranks
            foreach($extra_ranks as $er){
                $an_name = "";

                // special case. If there are no ancestors (the name is unplaced)
                // we include the family we are processing because it is built by family
                if(!$ancestors && $er == 'family' ) $an_name = $family_name->getNameString();

                // if this is the family entry then it won't have a family in its ancestry so we force it
                if($name->getRank() == 'family' && $er == 'family') $an_name = $name->getNameString();

                foreach($ancestors as $an){
                    if($an->getRank() == $er){
                        $an_name = $an->getAcceptedName()->getNameString();
                    }
                }
                $row[] = $an_name;
            }

            // now add some stuff from botalista dump.
            /*

                from william 12/05/2022

                1. ScientificNameId/IpniId - It contains the IPNI/Tropicos ID
                2. localId: - contains the Providers database taxon ID.
                3. DoNotProcess - indicates if the taxon is excluded.
                4. DoNotProcessReason - text with reason for exclusion.
                5. VerbatimTaxonRank/InfraspecificRank - Contains the infraspecific rank.
                6. deprecated - gives the information about the taxon excluded/deleted. It is similar to donotProcess but deprecated field indicates that taxon is deleted and that the WFO ID is not useful (dummy).
                7. NameAccordingToId: Contains taxon citation, a reference ID.

            */

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
            $row[] = $rank_abbreviation;

            $botalista_data = getBotalistaRow($name->getPrescribedWfoId());
            if($botalista_data){
                $row[] = $botalista_data["scientificNameID"];
                $row[] = $botalista_data["localID"];
                $row[] = $botalista_data["doNotProcess"];
                $row[] = $botalista_data["doNotProcess_reason"];
                $row[] = $botalista_data["deprecated"];
                $row[] = $botalista_data["nameAccordingToID"];
            }else{
                $row[] = "";
                $row[] = "";
                $row[] = "";
                $row[] = "";
                $row[] = "";
                $row[] = "";
            }

            // write it out to the file
            fputcsv($out, $row);

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
        unlink($file_path . ".csv");
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

