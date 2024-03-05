<?php

require_once('../config.php');
require_once('../include/NameMatcher.php');
require_once('../include/NameMatcherPlantList.php');
require_once('../include/NameMatches.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');

echo "\nName Match Tester\n";

$tester = new NameMatcherPlantList();

//$out = $tester->stringMatch("Rhododendron aganniphum Balf.f. & Kingdon-Ward");
//$matches = $tester->stringMatch("Rhododendron aganniphum var schizopeplum (Balf.f. & Forrest) T.L.Ming");

$matches = $tester->stringMatch("Cicerbita"); // space at end
//$matches = $tester->stringMatch("Aotus franklandii Chappill & C.F.Wilkins"); // third word is author and also name

//print_r($matches->names);
/*
for ($i=0; $i < count($matches->names); $i++) { 
  echo "\n{$matches->distances[$i]}\t{$matches->names[$i]->getFullNameString()}";
}
*/
//print_r($out);