<?php

require_once("../config.php");
require_once("../include/WfoDbObject.php");
require_once("../include/Name.php");
require_once("../include/Taxon.php");
require_once("../include/UnplacedFinder.php");

// php -d memory_limit=1024M gen_family_html_file.php

// Aquifoliaceae wfo-7000000041
// Ericaceae wfo-7000000218
// Asteraceae wfo-7000000146

$family_wfo = 'wfo-7000000146';

$downloads_dir = '../www/downloads/html/';
if (!file_exists($downloads_dir)) {
    mkdir($downloads_dir, 0777, true);
}

$name_count = 0;
$volume = 1;


// get a list of all the families in the taxonomy
$response = $mysqli->query("SELECT i.`value` as wfo, n.`name` 
FROM `names` as n 
JOIN `identifiers` as i on n.prescribed_id = i.id
JOIN `taxon_names` as tn on n.id = tn.name_id
JOIN `taxa` as t on t.taxon_name_id = tn.id
where n.`rank` = 'family'
order by n.name_alpha
");

// We only process 10 per run to keep the memory low
// this script can then be run frequently and will
// add new families if they are added plus update all of 
// them within a finite time

$counter = 0;

while($row = $response->fetch_assoc()){

    $file_path = $downloads_dir . $row['name'] . "_" . $row['wfo'] . ".zip";
    
    // if the file is less than a day old then skip it
    if(
        file_exists($file_path)
        &&
        filemtime($file_path) > time() - (3 * 24 * 60 * 60)
    ){
        continue;
    }

    // file is more than a day old or doesn't exist so lets create it
    process_family($row['wfo'], $downloads_dir);
    $counter++;

    if($counter > 9) break;
    
}

// process_family($family_wfo, $downloads_dir);

function process_family($family_wfo, $downloads_dir){

        global $name_count;
        global $volume;

        // reset our names
        $name_count = 0;
        $volume = 1;

        $family_name = Name::getName($family_wfo);
        $family_taxon = Taxon::getTaxonForName($family_name);

        // where we going to put te files?
        $temp_dir = $downloads_dir . '/' . $family_wfo . '/';
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0777, true);
        }

        $volume_base_path = $temp_dir . $family_name->getNameString() . "_" . $family_name->getPrescribedWfoId() . "_";

        ob_start();
        render_header();
        render_taxon($family_taxon, $volume_base_path);
        close_volume($volume_base_path, false);
        ob_flush();

        $files = glob($temp_dir . "*.html");
        print_r($files);

        $zip = new ZipArchive();
        $zip_path = $downloads_dir . $family_name->getNameString() . "_" . $family_name->getPrescribedWfoId() . ".zip";

        if ($zip->open($zip_path, ZIPARCHIVE::CREATE)!==TRUE) {
            exit("cannot open <$zip_path>\n");
        }

        foreach ($files as $file) {
            $parts = pathinfo($file);
            $zip->addFile($file, $parts['basename']);
        }

        if ($zip->close()!==TRUE) {
            exit("cannot close <$zip_path>\n". $zip->getStatusString());
        }
        
        // clean up
        foreach ($files as $file) unlink($file);
        rmdir($temp_dir);
        
}

function close_volume($volume_base_path, $and_continue){

    global $name_count;
    global $volume;

    render_footer();
    $html = ob_get_contents();
    file_put_contents($volume_base_path . 'part_' . str_pad($volume, 3, "0", STR_PAD_LEFT) . '.html', $html);
    $name_count = 0;
    $volume++;
    
    // uncomment to render in browser during dev
    //    ob_flush();

    ob_clean();

    if($and_continue){
        render_header();
    }

}

function render_taxon($taxon, $volume_base_path){

    global $name_count;

    // if we have filled one file we close it and start another
    if($name_count > 2000 && $taxon->getRank() == 'genus'){
        close_volume($volume_base_path, true);
    }

    // n.b. word can't handle multiple class names so we wrap with an extra div
    // for spacing
    if($taxon->getRank() == 'genus') echo '<div class="genus">';
    echo '<div class="taxon">';
    
    // partial name 
    echo "<span class=\"accepted-name\">{$taxon->getAcceptedName()->getFullNameString(true, true, true, true)}</span>";
    echo '&nbsp;<span class="citation">' . $taxon->getAcceptedName()->getCitationMicro() . '</span>';
    render_wfo_link($taxon->getAcceptedName());

    if($basionym = $taxon->getAcceptedName()->getBasionym()){
        render_name_div($basionym, 'basionym');
    }

    // are there homotypics?
    foreach ($taxon->getAcceptedName()->getHomotypicNames() as $homo) {
        render_name_div($homo, 'homotypic');
    }

    // synonyms
    foreach ($taxon->getSynonyms() as $syn) {
        echo '<div class="synonym">';

        render_name($syn, 'synonym');

        if($basionym = $syn->getBasionym()){
            render_name_div($basionym, 'basionym');
        }

        // are there homotypics?
        foreach ($syn->getHomotypicNames() as $homo) {
            render_name_div($homo, 'homotypic');
        }

        echo "</div>";
        if($taxon->getRank() == 'genus') echo '</div>';
    }

    // children
    foreach ($taxon->getChildren() as $kid) {
        render_taxon($kid, $volume_base_path);
    }

    // unplaced names
    $finder = new UnplacedFinder($taxon->getAcceptedName()->getId(), 0, 1000000, true);
    if(count($finder->unplacedNames) > 0){
        if($taxon->getRank() == 'genus'){
            echo '<p class="unplaced-heading">'. $taxon->getAcceptedName()->getNameString() .': Unplaced Names</p>';
            
            foreach ($finder->unplacedNames as $unplaced) {
                render_name_div($unplaced, 'unplaced');
            }

        }
    }
 
    echo "</div>";

}

function render_name_div($name, $kind){
    echo '<div class="'. $kind .'">';
    render_name($name, $kind);
    echo "</div>";
}

function render_name($name, $kind){

    global $name_count;
    $name_count++;

    if($kind == 'unplaced'){
        $display_kind = $name->getStatus();
    }else{
        $display_kind = $kind;
    }

    echo '<span class="'. $kind .'-name"><span class="hint">'.$display_kind.'</span>&nbsp;'. $name->getFullNameString() . '</span>';
    echo '&nbsp;<span class="citation">' . $name->getCitationMicro() . '</span>';
    render_wfo_link($name);
}

function render_wfo_link($name){
    echo "&nbsp;<span class=\"wfo-link\">[<a href=\"https://list.worldfloraonline.org/rhakhis/ui/index.html#{$name->getPrescribedWfoId()}\">{$name->getPrescribedWfoId()}</a>]</span>";
}

function render_header(){

     $creation_date = date(DATE_ATOM);
    echo "<!DOCTYPE html>\n";
?>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>WFO Proof</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
        }
        div.taxon,
        div.synonym,
        div.basionym,
        div.homotypic,
        div.unplaced{
            margin-left: 1.5em;
        }

        .hint{
            font-variant-caps: all-small-caps;
            font-size: 70%;
        }

        div.genus{
            margin-top: 1em;
        }
        div.species{
            margin-top: 0.5em;
        }
        .accepted-name .wfo-name{
            font-weight: bold;
        }

        .unplaced-heading{
            font-weight: bold;
            margin-bottom: 0px;
        }

        .wfo-name-authors{
            color: gray;
            font-size: 90%;
        }

        .wfo-link,
        .citation{
            font-size: 70%;
        }


    </style>
  </head>
  <body>
      <p>WFO HTML Proof Created <?php echo $creation_date ?></p>

<?php
}

function render_footer(){
?>
  </body>
</html>
<?php
}
