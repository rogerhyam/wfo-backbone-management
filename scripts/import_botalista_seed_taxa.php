<?php

// php -d memory_limit=10G import_botalista_seed_taxa.php 2>&1


// this script will probably only be used at the initiation of the database
// using the data from Missouri originally destined for Botalista

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/Taxon.php');

// everything must be joined to the root 
// orphaned trees is an exercise left to the reader.

create_taxon('wfo-9499999999', Taxon::getRootTaxon());

function create_taxon($wfo_id, $parent_taxon){

    global $mysqli;

    echo "\n$wfo_id";

    $name = Name::getName($wfo_id);
    $taxon = Taxon::getTaxonForName($name);
    $taxon->setParent($parent_taxon);
    $taxon->setUserId(1);
    $taxon->setSource('seed/Botalista1');
    $taxon->save();
    $taxon->load(); // make sure we are up to date with our parentage.

    echo "\t {$taxon->getId()}";

    //do we have any children to add
    $result = $mysqli->query("SELECT taxonID FROM botalista_dump_1 as t WHERE t.taxonomicStatus = 'Accepted' AND t.parentNameUsageID = '$wfo_id'");
    while($row = $result->fetch_assoc()){
        create_taxon($row['taxonID'], $taxon);
    }

}


