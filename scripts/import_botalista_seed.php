<?php


// php -d memory_limit=10G import_botalista_seed.php 2>&1


// this script will probably only be used at the initiation of the database
// using the data from Missouri originally destined for Botalista

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');


// work through all the rows - may take a while
$sql = "SELECT * FROM botalista_dump_1";

$response = $mysqli->query($sql);

if($mysqli->error) echo $mysqli->error;

echo "Starting run \n";

$start = time();
$counter = 0;
$total = $response->num_rows;

while($row = $response->fetch_assoc()){

    // load a name based on the WFO-ID
    $name = Name::getName($row['taxonID']);
    if(!$name) continue;

    echo number_format($counter++, 0) . "\t"; 
    echo $name->getPrescribedWfoId();

    $elapsed_hrs = (time() - $start)/ (60*60);
    if($counter > 0 && $elapsed_hrs > 0){

        echo "\t" . number_format($elapsed_hrs, 3) . " hrs elapsed "; 

        $rate = $counter/$elapsed_hrs;
        echo "\t" . number_format($rate) . " rows/hour";

        $remaining_rows = $total - $counter;
        $remaining_time = $remaining_rows/$rate;
        echo "\t" . number_format($remaining_time, 3) . "hrs remaining";

    }
    

    echo "\n";

    $name->setUserId(1);
    $name->setSource('Seed/botalista_1');

    // start by normalising the rank as much as we can
    // rank map - make sure we only accept certain ranks
    $rank_map = array(
        "phylum" => "phylum",
        "class" => "class",
        "order" => "order",
        "family" => "family",
        "genus" => "genus",
        "section" => "section",
        "subgenus" => "subgenus",
        'series' => 'series',
        "species" => "species",
        "nothospecies"=> "species",
        "nothosubsp."=> "subspecies",
        "nothovar."=> "variety",
        "subspecies" => "subspecies",
        "variety" => "variety",
        "subvariety" => "subvariety",
        "form" => "form",
        "forma" => "form",
        "subform" => "subform"
    );

    $rank = mb_strtolower(trim($row['taxonRank']));
    if(array_key_exists($rank, $rank_map)) {
        $rank = $rank_map[$rank];
    }else{
        echo "\nUnrecognised rank: {$row['taxonRank']}\n";
        exit;
    }
    $name->setRank($rank);

    // the name depends on the rank
    $scientificName = trim($row['scientificName']);
    $scientificName = str_replace('×', '', $scientificName); // remove hybrid symbols
    $scientificName = str_replace('ä', 'ae', $scientificName);
    $scientificName = str_replace('ö', 'oe', $scientificName);
    $scientificName = str_replace('ü', 'ue', $scientificName);
    $scientificName = str_replace('é', 'e', $scientificName);
    $scientificName = str_replace('è', 'e', $scientificName);
    $scientificName = str_replace('ê', 'e', $scientificName);
    $scientificName = str_replace('ñ', 'n', $scientificName);
    $scientificName = str_replace('ø', 'oe', $scientificName);
    $scientificName = str_replace('å', 'ao', $scientificName);
    $scientificName = str_replace("'", '', $scientificName); // can you believe an o'donolli 

    //Carduus esdraëlonicus
    switch ($rank) {

        // binomials
        case 'species':
            $parts = explode(' ', $scientificName);
            $name->setGenusString($parts[0]);
            $name->setNameString($parts[1]);
            $name->setSpeciesString(null);
            break;
        
        // trinomials
        case 'subspecies':
        case 'variety':
        case 'subvariety':
        case 'form':
        case 'subform':
            $parts = explode(' ', $scientificName);
            $name->setGenusString($parts[0]);
            $name->setSpeciesString($parts[1]);
            $name->setNameString(array_pop($parts)); // last element avoiding ssp. or var. etc
            break;
        
        // genus
        case 'genus':
            $name->setNameString($scientificName);
            $name->setGenusString(null);
            $name->setSpeciesString(null);
            break;
        
        // other mononomials
        default:
            $name->setNameString($scientificName);
            $name->setGenusString(null);
            $name->setSpeciesString(null);
            break;
    }

    $name->setAuthorsString($row['scientificNameAuthorship']);

    // nomenclatural status
    if($row['doNotProcess']){
        $processComment = "\nNomenclatural status in seed: " . $row['nomenclaturalStatus'];
        $nomenclaturalStatus = 'deprecated';
    }else{
        $nomenclaturalStatus = mb_strtolower(trim($row['nomenclaturalStatus']));
        $processComment = "";
    }

    switch ($nomenclaturalStatus) {
        
        case 'valid':
            $name->setStatus('valid');
            break;

        case 'invalid':
        case 'invalidum':
            $name->setStatus('invalid');
            break;

        case 'illegitimum':
        case 'illegitimate':
            $name->setStatus('illegitimate');
            break;

        case 'conservandum':
        case 'conserved':
            $name->setStatus('conserved');
            break;

        case 'rejiciendum':
        case 'rejected':
            $name->setStatus('rejected');
            break;

        case 'sanctioned':
            $name->setStatus('sanctioned');
            break;

        case 'deprecated':
            $name->setStatus('deprecated');
            break;

        default:
            break;
    }
    
    // source
    $name->setComment("Source in seed data: " . $row['source'] . $processComment);

    // comment
    $name->appendToComment($row['comments']);
    $name->appendToComment($row['taxonRemarks']);

    // namePublishedIn
    $name->setCitationMicro(trim($row['namePublishedIn']));

    // year  - we may as well extract it here if we can
    $matches = array();
    if(preg_match_all('/([0-9]{4})/', $row['namePublishedIn'], $matches, PREG_SET_ORDER)){
        foreach($matches as $hit){
            $year = (int)$hit[1];
            if($year > 1750 && $year < 2022){
                $name->setYear($year);
            }
        }
    }

    // publication ID - FIXME: at the moment this is a local id but should be a DOI or Q number
    $name->setCitationId(trim($row['namePublishedInID']));


//    [namePublishedIn] => Revis. Gen. Pl. 1: 328 1891
    
    // this will generate an db id for it if it hasn't already got on
    // making it safe to add hints and other ids
    // which act like they aren't part of the name 
    $name->save();


    /*
        - - - - - H I N T S - - - - 
    */
    $family = trim($row['family']);
    if($family) $name->addHint($family);

    $subfamily = trim($row['subfamily']);
    if($subfamily) $name->addHint($subfamily);

    $tribe = trim($row['tribe']);
    if($tribe) $name->addHint($tribe);

    $subtribe = trim($row['subtribe']);
    if($subtribe) $name->addHint($subtribe);

    // majorGroup needs expanding..
    switch ($row['majorGroup']) {
        case 'A':
            $name->addHint("Angiosperm");
            break;
        case 'B':
            $name->addHint("Bryophyte");
            break;
        case 'P':
            $name->addHint("Pteridophyte");
            break;
        case 'G':
            $name->addHint("Gymnosperm");
            break;
        default:
            break;
    }

    /*
        - - - - I D E N T I F I E R S - - - - - 
    */

    // scientificNameID
    if(preg_match('/^[0-9]{1,9}-[0-9]{1,2}$/', trim($row['scientificNameID']) )) $name->addIdentifier(trim($row['scientificNameID']), 'ipni');

    // localID
    if(trim($row['localID'])) $name->addIdentifier(trim($row['localID']), 'ten');

    // tplId
    if(trim($row['tplId'])) $name->addIdentifier(trim($row['tplId']), 'tpl');

    // tropicosID
    if(trim($row['tropicosId'])) $name->addIdentifier(trim($row['tropicosId']), 'tropicos');

    // references are treated as identifiers
    if(trim($row['references'])){
        $kind = preg_match('/^http:\/\/www\.theplantlist\.org/', trim($row['references'])) ? 'uri_deprecated': 'uri';
        $name->addIdentifier(trim($row['references']), $kind);
    }

    if(trim($row['references1.0'])){
        $kind = preg_match('/^http:\/\/www\.theplantlist\.org/', trim($row['references1.0'])) ? 'uri_deprecated': 'uri';
        $name->addIdentifier(trim($row['references1.0']), $kind);
    }

/*

    // ah - basionym! Second pass
    [originalNameUsageID] => 
    
    // is hybrid
    [genusHybridMarker] => 
    [speciesHybridMarker] => 
    
    // do not process pass
    [doNotProcess] => 0
    [doNotProcess_reason] =>  // has to be copied to 

    // taxonomy pass
    [parentNameUsageID] => 
    [taxonomicStatus] => Synonym
    [acceptedNameUsageID] => wfo-0000000796
    [nameAccordingToID] => 
    [taxonRemarks] => More details could be found in <a href=http://www.theplantlist.org/tpl1.1/record/gcc-100005 >The Plant List v.1.1.</a> Originally in <a href=http://www.theplantlist.org/tpl/record/gcc-100005 >The Plant List v.1.0</a>
   [source] => gcc // perhaps add to the comments?
*/


    //$name->load();
    //print_r($name->getIdentifiers());
    //print_r($row);



}




