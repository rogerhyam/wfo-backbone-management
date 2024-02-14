<?php
/*

    This is a run once script to import the initial treatment back stuff
    - may be exended 

    The way this works is that we use a SAX parser on there RSS feed
    to pull the title (which contains the name) and link.

    Initially we will do this against a downloaded version of the complete RSS feed.
    Subsequently we will do it against the URL and just stop when we get to a treatment
    we have seen before.

    - alter table
    ALTER TABLE `references` 
    CHANGE COLUMN `kind` `kind` ENUM('person', 'literature', 'specimen', 'database', 'treatment') NULL DEFAULT NULL ;

    --  initial import download file
    cd ../data/treatment_bank/
    curl -O https://tb.plazi.org/GgServer/xml.rss.xml

    -- get a list of the treatments
    SELECT i.`value`, r.* FROM promethius.references as r
JOIN name_references as nr ON r.id = nr.reference_id
JOIN identifiers as i on nr.name_id = i.name_id and i.kind = 'wfo'
 where r.kind = 'treatment';


*/

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/User.php');
require_once('../include/UpdateResponse.php');
require_once('../include/Reference.php');
require_once('../include/ReferenceUsage.php');

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

$plants_count = 0;
$total_count = 0;

$file = "../data/treatment_bank/xml.rss.xml";
process_xml_file($file);

// for development
/*
process_item(
    array(
        'TITLE' => 'Rhododendron atropurpureum',
        'LINK' => 'https://tb.plazi.org/GgServer/xml/3F9A66A0-676A-49E8-B96A-D87E2A0BEEDD',
        'DESCRIPTION' => 'Stub treatment',
        'PUBDATE' => 'test'
    )
);
*/

function process_xml_file($file){

    $item = null;

    $xml_parser = xml_parser_create();
    xml_set_element_handler($xml_parser, "start_element", "end_element");
    xml_set_character_data_handler($xml_parser, "character_data");

    // get the file to read - will be URL in future
    if (!($fp = fopen($file, "r"))) {
        die("could not open XML input");
    }

    // pipe the XML
    while ($data = fread($fp, 4096)) {
        if (!xml_parse($xml_parser, $data, feof($fp))) {
            die(sprintf("XML error: %s at line %d",
                        xml_error_string(xml_get_error_code($xml_parser)),
                        xml_get_current_line_number($xml_parser)));
        }
    }

    // clear it
    xml_parser_free($xml_parser);

}

// this is where the work is done
function process_item($item, $tries = 0){

    global $out;
    global $mysqli;
    global $total_count;
    global $plants_count;
    global $argv;

    // we have a skip mechanism to jump on to where we failed before
    if(count($argv) > 1 && $argv[1] > $total_count){
        $total_count++;
        return;
    }

    $name_string = trim($item['TITLE']);
    $link = trim($item['LINK']);
    $link_taxonx = preg_replace('/\/xml\//', '/taxonx/', $link);
    $link_html = preg_replace('/\/xml\//', '/id/', $link);
  
    $description = trim($item['DESCRIPTION']);
    $pub_date = trim($item['PUBDATE']);

    echo "{$plants_count}/{$total_count}\t{$name_string}\n";
    //echo "\t{$link}\n";
    //echo "\t{$link_taxonx}\n";
    //echo "\t{$link_html}\n";

    // is a plant at least mentioned in the treatement?
    try{
        $taxonx = @file_get_contents($link_taxonx);
        if($taxonx === false) throw new ErrorException("nothing returned");
    }catch (Exception $e) {
        if($tries < 4){
            echo $e->getMessage();
            echo "\nTrying again in 10 seconds\n";
            sleep(10); // hang on 10 seconds see if the connection comes back up
            $tries++;
            process_item($item, $tries);
        }
        return;
    }
    
    $total_count++;
    
    $xml = simplexml_load_string($taxonx);

    $xml->registerXPathNamespace('tax', 'http://www.taxonx.org/schema/v1');
    $xml->registerXPathNamespace('dwc', 'http://digir.net/schema/conceptual/darwin/2003/1.0');

    $nodes = $xml->xpath("//tax:taxonx/tax:taxonxBody/tax:treatment/tax:nomenclature/tax:name/tax:xmldata/child::*");

    $classification = array();
    foreach($nodes as $dwc_node){
        $classification[$dwc_node->getName()] = (string)$dwc_node;
    }

//    print_r($classification);

    // if it is a not Plantae then skip it
    if(!isset($classification['Kingdom']) || $classification['Kingdom'] != 'Plantae'){
        echo "\tNot a plant\n";
        return;
    }

    $plants_count++;
     echo "\t{$link_html}\n";

    // we need to have a rank
    if(!isset($classification['taxonRank'])){
        echo "\tNo rank\n";
        return;
    }

    $where = "WHERE `status` != 'deprecated' ";
    // do the switcheroo on the name parts to create a query string
    switch ($classification['taxonRank']) {
        
        case 'genus':
            $where .= "AND `name` = '{$classification['Genus']}' ";
            $where .= "AND `rank` = 'genus' ";
            break;        

        case 'species':
            $where .= "AND `name` = '{$classification['Species']}' ";
            $where .= "AND `genus` = '{$classification['Genus']}' ";
            $where .= "AND `rank` = 'species' ";
            break;  
            
        case 'subspecies':
            $where .= "AND `name` = '{$classification['Subspecies']}' ";
            $where .= "AND `species` = '{$classification['Species']}' ";
            $where .= "AND `genus` = '{$classification['Genus']}' ";
            $where .= "AND `rank` = 'subspecies' ";
            break;

        // FIXME - could support more ranks here

        default:
            echo "\tUnsupported rank '{$classification['taxonRank']}'\n";
            return;
    }

    $sql = "SELECT id FROM `names` $where";

    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    if(count($rows) < 1){
        echo "\tNo simple match found\n";
        return;
    }

    $name = null;
    if(count($rows) == 1){
        $name = Name::getName($rows[0]['id']);
    }elseif(isset($classification['ScientificNameAuthor'])){

        $safe_authors = $mysqi->real_escape_string(trim($classification['ScientificNameAuthor']));

        $sql = "SELECT id FROM `names` $where . AND authors = '$safe_authors'";
        $response = $mysqli->query($sql);
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        $response->close();

        if(count($rows) == 1){
            $name = Name::getName($rows[0]['id']);
        }

    }else{
        echo "\tNo match found\n";
        return;
    }

    // we have a name matched
    echo "\tFound: ";
    echo strip_tags($name->getFullNameString());
    echo " (" . $name->getPrescribedWfoId() . ")";
    echo "\n";

    // do we have a reference for this uri?
    $ref = Reference::getReferenceByUri($link_html);
    if(!$ref){
        // no ref so create it
        $ref = Reference::getReference(null);
        $ref->setKind('treatment');
        $ref->setLinkUri($link_html);
        $ref->setDisplayText($description);  
        $ref->setUserId(1);
        $ref->save();
        echo "\tCreated:\t" . $ref->getId() . "\n";
    }else{
        echo "\tExists:\t" . $ref->getId() . "\n";
    }

    // is it already attached?
    $already_there = false; 
    foreach($name->getReferences() as $usage){
        if($usage->reference->getId() == $ref->getId()){
            $already_there = true;
            break;
        }
    }

    // do we need to attach it to the name?
    if(!$already_there){
        $name->addReference($ref, "From Plazi TreatmentBank", false);
        echo "\tAdded\n";
    }else{
        echo "\tAlready present\n";
    }


    echo "\n";


}

function start_element($parser, $name, $attrs){

    global $item;

    // if we are starting a new item
    if($name == 'ITEM'){
        $item = array('tag' => 'ITEM');
    }

    if($item && $name != 'ITEM'){
        $item['tag'] = $name;
    }

}

function end_element($parser, $name){

    global $item;

    // closing off an item so process it
    if($name == 'ITEM'){
        process_item($item);
        $item = null;
    }

}

function character_data($parser, $data){

    global $item;

    if($item){
        if(isset($item[$item['tag']])){
            $item[$item['tag']] .= $data;
        }else{
            $item[$item['tag']] = $data;
        }
    }

}