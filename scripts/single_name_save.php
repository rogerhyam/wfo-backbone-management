<?php

require_once('../config.php');
require_once('../include/NameMatcher.php');
require_once('../include/NameMatch.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/Taxon.php');


$name = Name::getName('1229446');

$taxon = Taxon::getTaxonForName($name);

print_r($taxon->getAcceptedName()->getFullNameString());

$taxon->setComment(date("Y-m-d H:i:s"));

$taxon->save();

$taxon->load();

echo "\n";
echo $taxon->getComment();
echo "\n";

/*

$name->setComment('test1');

$name->save();

$name->load();

print_r($name);
*/
