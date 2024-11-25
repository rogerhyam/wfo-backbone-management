<?php

/*
    This will generate a report on the conflicts between
    Rhakhis and IPNI data for set fields.
*/

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/Taxon.php');
require_once('../include/Identifier.php');
require_once('../include/User.php');
require_once('../include/DownloadFile.php');

// names mondified in the last x days can be passed in
if(count($argv) > 1) $days = (int)$argv[1];
else $days = 7; // default to a week

$file_out_path = '../www/downloads/integrity_reports/ipni_notifier.html';
$out = fopen($file_out_path, 'w');
fwrite($out, "<dif style=\"font-family: monospace, monospace;\">\n");

// get a list of the changed names
$offset = 0;
while(true){

    // get a page of ids
    $response = $mysqli->query("SELECT id, now() as 'now', now() - INTERVAL $days DAY as 'then' FROM `names` AS n WHERE n.modified > now() - INTERVAL $days DAY ORDER BY id LIMIT 100 OFFSET $offset");
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
    
    if($offset == 0){
        fwrite($out, "<p>This is a list of the names that have been edited in Rhakhis between {$rows[0]['then']} and {$rows[0]['now']} that differ in 
            name string, author string or microcitation from the current IPNI dataset.
        </p>\n");
    }
    
    if(count($rows) == 0) break;
    else $offset += 100;

    // fresh cache each time
    Name::resetSingletons();

    foreach ($rows as $row) {
        
        // get the name from rhakhis
        $name = Name::getName($row['id']);

        $identifiers = $name->getIdentifiers();

        // extract the ipni identifiers for the name
        $ipni_ids = array();
        foreach ($identifiers as $identifier) {
            if($identifier->getKind() == 'ipni'){
                $ipni_ids = $identifier->getValues();
                break;
            }
        }

        // work through the ipni rows - only those not suppressed
        foreach($ipni_ids as $ipni_id){

            $response = $mysqli->query("SELECT * FROM kew.ipni WHERE suppressed_b != 't' AND top_copy_b = 't' AND id = '$ipni_id';");
            $ipni_rows = $response->fetch_all(MYSQLI_ASSOC);
            $response->close();
            if(count($ipni_rows) == 0) continue;

            foreach($ipni_rows as $ipni_row){
                // at last we have all we need to create an output entry - if they differ!
                echo strip_tags($name->getFullNameString());

                // what we check from IPNI
                $ipni_name = $ipni_row['taxon_scientific_name_s_lower'];
                $ipni_name_parts = get_name_parts($ipni_name);
                $ipni_authors = $ipni_row['authors_t'];
                $ipni_micro_citation = $ipni_row['reference_t'];

                if(
                    end($ipni_name_parts) != $name->getNameString() // the name string for the name (no genus part check)
                    ||
                    $ipni_authors != $name->getAuthorsString()
                    ||
                    $ipni_micro_citation != $name->getCitationMicro()
                ){
                    // header for the section.
                    fwrite($out, "<h3>" . $name->getFullNameString() . "</h3>\n");
                    fwrite($out, "<ul>");

                    // name strings
                    if(end($ipni_name_parts) != $name->getNameString()){
                        fwrite($out, "<li>Names strings do not match");
                        fwrite($out, "<ul>");
                        $ns = end($ipni_name_parts);
                        fwrite($out, "<li><strong>IPNI:</strong> $ns</li>");
                        fwrite($out, "<li><strong>&nbsp;WFO:</strong> {$name->getNameString()}</li>");
                        fwrite($out, "</ul>");
                        fwrite($out, "</li>");
                    }

                    // author strings
                    if($ipni_authors != $name->getAuthorsString()){
                        fwrite($out, "<li>Author strings do not match");
                        fwrite($out, "<ul>");
                        fwrite($out, "<li><strong>IPNI:</strong> $ipni_authors</li>");
                        fwrite($out, "<li><strong>&nbsp;WFO:</strong> {$name->getAuthorsString()}</li>");
                        fwrite($out, "</ul>");
                        fwrite($out, "</li>");
                    }

                    // citation strings
                    if($ipni_micro_citation != $name->getCitationMicro()){
                        fwrite($out, "<li>Citation strings do not match");
                        fwrite($out, "<ul>");
                        fwrite($out, "<li><strong>IPNI:</strong> $ipni_micro_citation</li>");
                        fwrite($out, "<li><strong>&nbsp;WFO:</strong> {$name->getCitationMicro()}</li>");
                        fwrite($out, "</ul>");
                        fwrite($out, "</li>");
                    }
                    
                    // placement
                    if($ipni_micro_citation != $name->getCitationMicro()){
                        fwrite($out, "<li>Family placement");
                        fwrite($out, "<ul>");
                        fwrite($out, "<li><strong>IPNI:</strong> " . $ipni_row['family_s_lower'] . "</li>");
                        
                        $taxon = Taxon::getTaxonForName($name);
                        if($taxon->getId()){
                            $fam = $taxon->getAncestorAtRank('family');
                            if($fam) $fam = $fam->getFullNameString();
                            else $fam = "above family level";
                        }else{
                            $fam = "Unplaced";
                        }

                        fwrite($out, "<li><strong>&nbsp;WFO:</strong> {$fam}</li>");
                        fwrite($out, "</ul>");
                        fwrite($out, "</li>");
                    }

                    // links
                    fwrite($out, "<li><a href=\"https://list.worldfloraonline.org/rhakhis/ui/index.html#{$name->getPrescribedWfoId()}\" target=\"rhakhis\">{$name->getPrescribedWfoId()}</a></li>");
                    fwrite($out, "<li><a href=\"https://ipni.org/n/{$ipni_id}\" target=\"ipni\">{$ipni_id}</a></li>");

                    // last edit
                    $last_user = $name->getUser();
                    fwrite($out, "<li>Last edit by: {$last_user->getName()} (<a href=\"https://ipni.org/n/{$ipni_id}\" target=\"orcid\">{$last_user->getOrcidId()}</a>) on {$name->getModified()}</li>");

                    fwrite($out, "</ul>");
                } 
                
            }

        }





        
        // get row from ipni - must be matched
//        $response = $mysqli->query("SELECT * FROM `kew`.`ipni` as ipni WHERE ipni.wfo_id = ");




        echo "\n";
    }


    // get the 


}

fwrite($out, "</div>");
fclose($out);

$meta = array();
$meta['filename'] = $file_out_path;
$now = new DateTime();
$meta['created'] = $now->format(DateTime::ATOM);
$meta['title'] = 'IPNI Weekly Notification';
$meta['description'] = "This is a list of the names that have been edited in Rhakhis in the last week that differ in 
            name string, author string or microcitation from the current IPNI dataset.";
$meta['size_bytes'] = filesize($file_out_path);
$meta['size_human'] = DownloadFile::humanFileSize($meta['size_bytes']);
file_put_contents($file_out_path . '.json', json_encode($meta, JSON_PRETTY_PRINT));


function get_name_parts($nameString){
    
    // clean up the name first
    $nameString = trim($nameString);

    // U+00D7 = multiplication sign
    // U+2715 ✕ MULTIPLICATION X
    // U+2A09 ⨉ N-ARY TIMES OPERATOR

    // hybrid symbol be gone
    $json = '["\u00D7","\u2715","\u2A09"]';
    $hybrid_symbols = json_decode($json);
    foreach ($hybrid_symbols as $symbol) {
        $nameString = str_replace($symbol, '', $nameString);
    }

    // the name may include a rank abbreviation
    $nameParts = explode(' ', $nameString);
    $newNameParts = array();
    foreach($nameParts as $part){
        // strip out the rank parts.
        if(!Name::isRankWord($part)){
            $newNameParts[] = $part;
        }
    }

    return $newNameParts;
}