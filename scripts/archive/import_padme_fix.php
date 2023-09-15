<?php


/*
    One off script to create taxonomy references 
    for the URLs in the 'references' column of botalista

*/

require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/Taxon.php');
require_once('../include/User.php');
require_once('../include/UpdateResponse.php');
require_once('../include/Reference.php');
require_once('../include/ReferenceUsage.php');

$_SESSION['user'] = serialize(User::loadUserForDbId(1));

$sql = "SELECT 
`taxonID` as wfo,
`references` as 'uri'
FROM botalista_dump_2 
WHERE `references` LIKE 'https://padme.rbge.org.uk/wfo/%';";

$result = $mysqli->query($sql);

$counter = 0;

while($row = $result->fetch_assoc()){


    // get the name

    // is it in one of the expected families?
    // Irvingiaceae and Zingiberaceae

    $name = Name::getName($row['wfo']);
    if(!$name){
        echo "\n No name {$row['wfo']} skipping.";
        $counter++;
        continue;
    }

    $in_scope = false;

    $taxon = Taxon::getTaxonForName($name);
    $ancestor = $taxon;

    if($ancestor->getRank() == 'family' && ($ancestor->getAcceptedName()->getNameString() == 'Irvingiaceae' || $ancestor->getAcceptedName()->getNameString() == 'Zingiberaceae')  ){
        $in_scope = true;
    }

    while($ancestor = $ancestor->getParent()){
        if($ancestor->getRank() == 'family' && ($ancestor->getAcceptedName()->getNameString() == 'Irvingiaceae' || $ancestor->getAcceptedName()->getNameString() == 'Zingiberaceae')  ){
            $in_scope = true;
        }
    }

    // if we aren't in scope

    if(!$in_scope){
        $counter++;
        echo "\n$counter\t{$row['wfo']}\t OUT OF SCOPE";
        
        // get the references for the name
        $refs = $name->getReferences();

        // find the one that is the padme one
        foreach($refs as $usage){
            if( strpos($usage->reference->getLinkUri(), 'padme') && $usage->subjectType == 'taxon'){
                echo "\tremoving\t{$usage->reference->getLinkUri()}";
                $name->removeReference($usage->reference, true);
            }
        }

        // remove it


    }
   
}

 echo "\n$counter";

