<?php

require_once('config.php');
require_once('include/functions.php');

switch ($_GET['action']) {
    case 'set_wfo':
        set_wfo();
        break;
    case 'skip':
        skip();
        break;
    case 'clear_skips':
        clear_skips();
        break;
    case 'clear_matches':
        clear_matches();
        break;
    case 'extract_dwc':
        extract_dwc();
        break;


    default:
        echo "No action set";
        break;
}

function set_wfo(){

    global $pdo;

    $pdo->exec("UPDATE `{$_GET['table']}` SET `rhakhis_wfo` = '{$_GET['wfo']}' WHERE `rowid` = {$_GET['row_id']}" );

    $page = $_GET['page'];
    if($page > 0) $page--; // we go back a page so as not to miss things

    header('Location: ' . "index.php?phase=matching&action=by_name&mode=interactive&table={$_GET['table']}&page=$page");
    exit;

}

function clear_skips(){

    global $pdo;

    $pdo->exec("UPDATE `{$_GET['table']}` SET `rhakhis_skip` = null" );

    header('Location: ' . "index.php?phase=intro&table={$_GET['table']}");
    exit;

}

function clear_matches(){

    global $pdo;

    $pdo->exec("UPDATE `{$_GET['table']}` SET `rhakhis_wfo` = null" );

    header('Location: ' . "index.php?phase=matching&table={$_GET['table']}");
    exit;

}

function skip(){

    global $pdo;

    $pdo->exec("UPDATE `{$_GET['table']}` SET `rhakhis_skip` = 1 WHERE `rowid` =  {$_GET['rowid']}" );

    header('Location: ' . "/index.php?phase=matching&action=by_name&mode=interactive&table={$_GET['table']}");
    exit;

}

/**
 * 
 * Given the name of a dwc archive file it will pull 
 * out the taxonomy table in a desirable way 
 * 
 */
function extract_dwc(){

    $file_name = $_GET['file_name'];
    $zip_path = "sources/$file_name";

    // pull the meta.xml out of it
    $zip = new ZipArchive;
    $zip->open(realpath($zip_path));
    $in = $zip->getStream('meta.xml');
    $xml_string = stream_get_contents($in);
    fclose($in);
    

    $xml = new SimpleXMLElement($xml_string);
    

    if(strtoupper($xml->core->attributes()['encoding']) != 'UTF-8'){
        echo "Encoding is not UTF-8 it is " . $xml->core->attributes()['encoding'] . ". Go back and get something in the right encoding";
        exit();
    }

    if($xml->core->attributes()['rowType'] != 'http://rs.tdwg.org/dwc/terms/Taxon'){
        echo "The core table is not a tdwg:Taxon it is " . $xml->core->attributes()['rowType'] . ". We only import taxon DwC Archive files";
        exit();
    }

    $taxon_file_name = $xml->core->files->location;
    $lineTerminator = (string)$xml->core->attributes()['linesTerminatedBy'];
    $fieldTerminator = (string)$xml->core->attributes()['fieldsTerminatedBy'];
    $fieldTerminator = str_replace("\\t", "\t", $fieldTerminator);
    $fieldEnclosedBy = (string)$xml->core->attributes()['fieldsEnclosedBy'];
    $fieldEnclosedBy = str_replace('"', "\"", $fieldTerminator); // we don't bother using this as PHP seems to do the right thing when it is supplied erratically.
    $hasHeaders = $xml->core->attributes()['ignoreHeaderLines'];


    // open up the taxon file
    $in = $zip->getStream($taxon_file_name);
   

    if($hasHeaders){
        // discard the first row if we have headers
        fgetcsv($in, null, $fieldTerminator);
    }



    $out = fopen($zip_path . '.csv', 'w');

    

    // write the header out
    foreach ($xml->core->field as $field) {
        $info = pathinfo((string)$field->attributes()['term']);
        $header[(int)$field->attributes()['index']] = $info['filename'];
//        print_r($field->attributes());
    }
    fputcsv($out, $header, ",", '"');


    // dump it out
    while($line = fgetcsv($in, null, $fieldTerminator)){

        $out_line = array();

        // we need to build the columns based on the header fields
        // as these may contain default values
        for ($i=0; $i < count($xml->core->field); $i++) { 
            
            $field = $xml->core->field[$i];
            
            if($line[$i]){
                // had val to write in
                $val = $line[$i];
            }else{
                // no val
                // does it have a default?
                if($field->attributes()['default']){
                    $val = $line[$i];
                }else{
                    $val = null;
                }
            }

            $out_line[] = $val;
        }

        fputcsv($out, $out_line, ",", '"');
    }
    fclose($out);

    echo "<pre>";
    print_r($header);
    echo "</pre>";

}