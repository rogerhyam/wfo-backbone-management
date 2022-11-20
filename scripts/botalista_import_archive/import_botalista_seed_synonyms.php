<?php


// php -d memory_limit=15G import_botalista_seed_synonyms.php 2>&1

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/Taxon.php');
require_once('../include/UpdateResponse.php');
require_once('../include/User.php');

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));


$result = $mysqli->query("SELECT taxonID, acceptedNameUsageID FROM botalista_dump_2 where length(acceptedNameUsageID) != 0 and taxonomicStatus != 'Accepted' order by taxonID ASC LIMIT 1000000 OFFSET 582372;");
$counter = 0;
while($row = $result->fetch_assoc()){

    $counter++;
    echo "\n$counter\t{$row['taxonID']}\t=>\t{$row['acceptedNameUsageID']}";

    // there is no 'wfo-0000927555'!
    if($row['acceptedNameUsageID'] == 'wfo-0000927555') continue;

    // get the name objects
    $accepted_name = Name::getName($row['acceptedNameUsageID']);
    $synonymous_name = Name::getName($row['taxonID']);

    // get the taxon for the accepted name
    $taxon = Taxon::getTaxonForName($accepted_name);

    // does it exit in the db (there is a chance it is a synonym synonym relationship or some trash)
    if(!$taxon->getId()){
        echo "\n\tNo taxon for {$row['acceptedNameUsageID']} so ignoring;";
        continue;
    }

    $taxon->addSynonym($synonymous_name);
    
    // job done!

}
