<?php

require_once("../config.php");
require_once("../include/WfoDbObject.php");
require_once("../include/Name.php");
require_once("../include/Taxon.php");
require_once("../include/UnplacedFinder.php");

// php -d memory_limit=1024M gen_family_dwc_file.php

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

        $family_name = Name::getName($family_wfo);
        $family_taxon = Taxon::getTaxonForName($family_name);

        $creation_date = date(DATE_ATOM);

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
            "created",
            "modified"
        );

        $out = fopen($file_path . ".csv", 'w');

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
            $row[] = $name->getStatus();

            // namePublishedIn = citation
            $row[] = $name->getCitationMicro();

            // originalNameUsageID = basionym WFO ID
            if($name->getBasionym()){
                $row[] = $name->getBasionym()->getPrescribedWfoId();
            }else{
                $row[] = null;
            }
            
            // now fields that only taxa == accepted names
            if($taxon){
                
                // parentNameUsageID = For accepted names of taxa only the parent taxon wfo_ID
                $row[] = $taxon->getParent()->getAcceptedName()->getPrescribedWfoId();

                // taxonomicStatus
                $row[] = 'accepted';

                // acceptedNameUsageID = synonyms only is accepted taxon WFO ID
                $row[] = null;

            }else{

                // parentNameUsageID = For accepted names of taxa only the parent taxon wfo_ID
                $row[] = null;

                // we are a name but are we placed or unplaced.
                $placement = Taxon::getTaxonForName($name);

                if(!$placement->getId()){
                    // no taxon in database for the name so unplaced
                    $row[] = 'unplaced'; // taxonomicStatus
                    $row[] = null; // acceptedNameUsageID 
                }else{
                    $row[] = 'synonym'; // taxonomicStatus
                    $row[] = $placement->getAcceptedName()->getPrescribedWfoId(); // acceptedNameUsageID 
                }

            }

            // taxonRemarks	= comments from name field
            $row[] = str_replace("\n", " ", $name->getComment()); // a hack to assure compatibility
            
            // created = from name ? or earliest from name/taxon
            $row[] = $name->getCreated();

            // modified	= from name ? 
            $row[] = $name->getModified();

            // write it out to the file
            fputcsv($out, $row);

        }

        fclose($out);

        // let's create the zip file with metadata in it

        echo "\nCreating zip\n";
        $zip = new ZipArchive();
        $zip_path = $file_path . ".zip";

        if ($zip->open($zip_path, ZIPARCHIVE::CREATE)!==TRUE) {
            exit("cannot open <$zip_path>\n");
        }

        // create personalize versions of the provenance and meta files for inclusion.

        $meta_path = $file_path . ".meta.xml";

        $prov_path = $file_path . ".prov.xml";
        $prov = file_get_contents('darwin_core_prov.xml');
        $prov = str_replace('{{family}}', $family_name->getNameString(), $prov);
        $prov = str_replace('{{date}}', $creation_date, $prov);
        file_put_contents($prov_path, $prov);

        $meta_path = $file_path . ".meta.xml";
        $meta = file_get_contents('darwin_core_meta.xml');
        $meta = str_replace('{{family}}', $family_name->getNameString(), $meta);
        $meta = str_replace('{{date}}', $creation_date, $meta);
        file_put_contents($meta_path, $meta);


        $zip->addFile($file_path . ".csv", "taxonomy.csv");
        $zip->addFile($prov_path, "prov.xml");
        $zip->addFile($meta_path, "eml.xml");

        if ($zip->close()!==TRUE) {
            exit("cannot close <$zip_path>\n". $zip->getStatusString());
        }

        echo "Removing temp files\n";
        unlink($file_path . ".csv");
        unlink($meta_path);
        unlink($prov_path);
}

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

