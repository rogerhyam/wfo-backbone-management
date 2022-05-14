<?php

/*
    run through the existing names and add author references based on the content of the author strings.

*/

/*
   import the IPNI records from the wfo dump as references
   This is probably a run once script.

   php -d memory_limit=10G import_author_references.php

*/
require_once('../config.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/User.php');
require_once('../include/UpdateResponse.php');
require_once('../include/Reference.php');
require_once('../include/ReferenceUsage.php');
require_once('../include/AuthorTeam.php');
require_once('../include/SPARQLQueryDispatcher.php');

echo "\nAuthor References Importer\n";

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

$offset = 0;

$sql = "SELECT n.id, n.authors
FROM `names` AS n
WHERE length(n.authors) >0
AND n.`status` != 'deprecated'";

$result = $mysqli->query($sql);

$result = $mysqli->query($sql);

$counter = $offset; // start where we left off.
while($row = $result->fetch_assoc()){

    $team = new AuthorTeam($row['authors']);

    foreach($team->authors as $abbreviation => $author){

        // if we have no link for the author we skip it
        if(!$author || !$author['person']){
            // echo "\nSkipping $abbreviation";
            continue;
        }

        $name = Name::getName($row['id']);

        // does this name already have this reference?
        foreach($name->getReferences() as $usage){
            if($usage->reference->getLinkUri() == $author['person']) continue 2;
        }

        // does the reference already exist?
        $ref = Reference::getReferenceByUri($author['person']);
        if(!$ref){
            $ref = Reference::getReference(null);
            $ref->setLinkUri($author['person']);
            // display text is made from fields
            $birth_date = strtotime($author['birth']);
            $death_date = strtotime($author['death']);
            $null_date = strtotime('1970-01-01 00:00:00');

            if($birth_date == $null_date){
                // no birthday
                if($death_date == $null_date){
                    // no birthday or death day
                    $life = "";
                }else{
                    // just a death day
                    $life = (" (d." . date("Y", $death_date) . ")");
                }
            }else{
                if($death_date == $null_date){
                    //birthday but no death day
                    $life = (" (b." . date("Y", $birth_date) . ")");
                }else{
                    // birthday and death day
                    $life = (" (" . date("Y", $birth_date) . "-" . date("Y", $death_date) . ")");
                }
            }

            $display_text = $author['label'] . $life;
            $ref->setDisplayText($display_text);

            if($author['image']){
                $ref->setThumbnailUri($author['image']);
            }

            $ref->setKind('person');
            $ref->setUserId(1);
            $ref->save();
        }

        // add the reference to the name
        $name->addReference($ref, "Based on occurrence of standard abbreviation '$abbreviation' in the authors string.", false);

        $c = number_format($counter);

        if($counter % 1000 == 0 ){
            echo "\n$c\t{$name->getPrescribedWfoId()}\t$abbreviation\t{$ref->getDisplayText()}";
        } 
        

    }

    $counter++;

}