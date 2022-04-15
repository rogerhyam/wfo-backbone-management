<?php

// php -d memory_limit=10G import_botalista_seed_taxa.php 2>&1


// this script will probably only be used at the initiation of the database
// using the data from Missouri originally destined for Botalista

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/UpdateResponse.php');
require_once('../include/Taxon.php');
require_once('../include/User.php');

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));


// everything must be joined to the root 

// orphaned trees is an exercise left to the reader.

// FIXME - INVESTIGATE HOW MANY ROOT TAXA WE HAVE IN THE SOURCE DATA
// root taxon "Code" is wfo-9971000003 in the live data.

// wfo-9949999999 = Angiosperms


create_taxon('wfo-9949999999', Taxon::getRootTaxon()); // angiosperm
create_taxon('wfo-9949999998', Taxon::getRootTaxon()); // Gymnosperms
create_taxon('wfo-9949999997', Taxon::getRootTaxon()); // Pteridophytes
create_taxon('wfo-9949999996', Taxon::getRootTaxon()); // Bryophytes

function create_taxon($wfo_id, $parent_taxon){

    global $mysqli;

    echo "\n$wfo_id";

    $name = Name::getName($wfo_id);

    // don't create taxa if they are deprecated
    if($name->getStatus() == 'deprecated') return;

    $taxon = Taxon::getTaxonForName($name);
    $taxon->setParent($parent_taxon);
    $taxon->setUserId(1);
    $taxon->setSource($name->getSource());
    echo "\tsaving";
    $response = $taxon->save();
    $response->consolidateSuccess();
    if(!$response->success){
        echo "\n" . $response->message;
        foreach($response->children as $kid){
            echo "\n\t\t" . $kid->message;
        }
    }
    $taxon->load(); // make sure we are up to date with our parentage.

    // all accepted names must be valid or conserved names
    if($name->getStatus() != 'valid' && $name->getStatus() != 'conserved'){
        $name->updateStatus('valid', null); 
        echo "\tupdating";
    }else{
        echo "\tnot-updating";
    }

    echo "\t {$taxon->getId()}";

    //do we have any children to add
    $result = $mysqli->query("SELECT taxonID FROM botalista_dump_2 as t WHERE t.taxonomicStatus = 'Accepted' AND t.parentNameUsageID = '$wfo_id'");
    while($row = $result->fetch_assoc()){
        create_taxon($row['taxonID'], $taxon);
    }

}


