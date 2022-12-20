<?php

/*

Generates the metadata for upload to Zenodo

*/

require_once("../config.php");
require_once("../include/WfoDbObject.php");
require_once("../include/Name.php");
require_once("../include/Taxon.php");
require_once("../include/User.php");
require_once("../include/UpdateResponse.php");

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

$metadata = json_decode(file_get_contents("zenodo_metadata.json"));

$metadata->description = file_get_contents("zenodo_description.html");

echo "\nZenodo Metadata Generation\n";

$contributors = array();

/*
* name: Name of creator in the format Family name, Given names
* type: Contributor type. Controlled vocabulary (ContactPerson, DataCollector, DataCurator, DataManager,Distributor, Editor, HostingInstitution, Producer, ProjectLeader, ProjectManager, ProjectMember, RegistrationAgency, RegistrationAuthority, RelatedPerson, Researcher, ResearchGroup, RightsHolder,Supervisor, Sponsor, WorkPackageLeader, Other)
* affiliation: Affiliation of creator (optional).
* orcid: ORCID identifier of creator (optional).
* gnd: GND identifier of creator (optional).
*/


// get all the users 
$users = User::getPossibleEditors();
foreach($users as $user){
    if($user->isEditor() && $user->getOrcidId() && $user->getTaxaCurated()){
        $meta = array();
        $meta['type'] = "DataCurator";
        $meta['orcid'] = $user->getOrcidId();

        $parts = explode(' ', $user->getName());
        $family = array_pop($parts);
        $given = implode(' ', $parts);
        $meta['name'] = "$family, $given";

        $metadata->contributors[] = $meta;
    }
}

// now for the TENs
$response = $mysqli->query("
SELECT distinct r.id, r.display_text
FROM `references` AS r
JOIN `name_references` AS nr ON nr.reference_id = r.id
WHERE nr.placement_related = 1
AND r.kind = 'person'
");

while($row = $response->fetch_assoc()){

        $meta = array();
        $meta['type'] = "DataCurator";
        $meta['name'] = str_replace('TEN', 'Taxonomic Expert Network', $row['display_text']);
        $metadata->contributors[] = $meta;
}


// write it out
file_put_contents("../data/versions/zenodo_metadata.json", json_encode($metadata, JSON_PRETTY_PRINT));
