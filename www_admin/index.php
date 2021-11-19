
<h1>WFO Backbone</h2>
<?php 
require_once("../config.php");
require_once("../include/WfoDbObject.php");
require_once("../include/Name.php");
require_once("../include/Taxon.php");

$taxon_id = &$_GET['taxon_id'];

// get the taxon_id if it isn't set.
if(!$taxon_id){
    $taxon = Taxon::getRootTaxon();
}else{
    $taxon = Taxon::getById($taxon_id);
}


if($taxon->isRoot()){
    echo "<h2>I am ROOT</h2>";
}else{
    
    $ancestors = array();
    $parent = $taxon->getParent();
    while(!$parent->isRoot()){
        $pname = $parent->getAcceptedName();
        $ancestors[] = "<a href=\"=?taxon_id={$parent->getId()}\">". $pname->getNameString() . "</a>";
        $parent = $parent->getParent();
    }
    $ancestors[] = "<a href=\".\">ROOT</a>";
    $ancestors = array_reverse($ancestors);
    foreach($ancestors as $ancestor){
        echo $ancestor . " &gt; ";
    }

    $name = $taxon->getAcceptedName();
    echo "<h2>{$name->getRank()}: {$name->getNameString()}</h2>";
    echo "<pre>";
    //print_r($name);
    echo "</pre>";

}

echo "<h2>Synonyms</h2>";
echo "<ul>";
$syns = $taxon->getSynonyms();
foreach($syns as $syn){
    echo "<li>{$syn->getGenusString()} {$syn->getSpeciesString()} {$syn->getNameString()} - {$syn->getAuthorsString()} </li>";
}
echo "</ul>";

echo "<h2>Child taxa</h2>";
echo "<ul>";
$kids = $taxon->getChildren();
foreach($kids as $kid){
    $name = $kid->getAcceptedName()->getNameString();
    $id = $kid->getId();
    echo "<li><a href=\"?taxon_id=$id\">$name</a> ({$kid->getAcceptedName()->getId()}) & isAutonym = {$kid->isAutonym()}</li>";
}
echo "</ul>";
