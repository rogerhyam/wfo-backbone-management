<?php

require_once('../config.php');

// if this is run as itself then generate a file in the data director
if($argv[0] == "gen_coldp_metadata_json.php"){

    // date of dump must be passed in.
    if(count($argv) < 2 || !preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $argv[1]) ){
        echo "\nYou must provide a publish date in the format 2023-06-21\n";
        exit;
    }

    $pub_date = $argv[1];
    $version = substr($pub_date, 0, 7);

    generate_metadata("../data/coldp_metadata.json", $pub_date, $version);
}

function generate_metadata($file_path, $pub_date, $version){

    global $mysqli;
    global $argv;

    // we load the json object and update it
    $json_string = file_get_contents('coldp.json');

    // The version and other data occur in template strings so we replace that first.
    $json_string = str_replace('*VERSION*',$version, $json_string);
    $json_string = str_replace('*YEAR*', substr($pub_date, 0, 4), $json_string);

    // turn it into an object for the rest
    $json = json_decode($json_string);

    // get the editors in Rhakhis
    $sql = "SELECT u.`name` as user_name, u.orcid_id as orcid, n.name_alpha as taxon_name  
    FROM users_taxa as ut
    join taxa as t on ut.taxon_id = t.id
    join users as u on u.id = ut.user_id
    join taxon_names as tn on tn.id = t.taxon_name_id
    join `names` as n on tn.name_id = n.id
    order by u.orcid_id";
    $response = $mysqli->query($sql);
    $users = array();
    while($row = $response->fetch_assoc()){

        if(isset($users[$row['orcid']])){
            $users[$row['orcid']]['taxa'][] = $row['taxon_name'];
        }else{

            $parts = explode(' ', $row['user_name']);
            $family = mb_ucfirst(mb_strtolower(array_pop($parts)));
            $given =  mb_ucfirst(mb_strtolower(implode(' ', $parts)));

            $users[$row['orcid']] = array(
                'sort_name' => $family . ' ' . $given,
                'orcid' => $row['orcid'],
                'given' => $given,
                'family' => $family,
                'taxa' => array($row['taxon_name'])
            );
        }
    }

    // sort them alphabetically - this destroys the orcids as keys
    usort($users, function ($a, $b) {
    return strcmp(strtolower($a['sort_name']), strtolower($b['sort_name']));
    } );

    // now we have a list of users write them out in 
    $json->editor = array(); // fresh editors list
    foreach($users as $user){

        $editor = $user;
        unset($editor['sort_name']);

        sort($user['taxa']);
        $last = array_pop($user['taxa']);
        if ($user['taxa']) {
            $taxon_list = implode(', ', $user['taxa']) . ' and ' . $last;
        }else{
            $taxon_list = $last;
        }
        $editor['note'] = "Curator of $taxon_list in the Rhakhis editor.";

        unset($editor['taxa']);
        $editor['orcidAsUrl'] = 'https://orcid.org/' . $editor['orcid'];
        $json->editor[] = (object)$editor;

    }

    // get a list of all the TENs to add to the metadata file.
    $sql = "SELECT link_uri as `url`, display_text as `organisation`
    FROM `references` AS r
    JOIN `name_references` AS nr ON nr.reference_id = r.id AND nr.placement_related = 1
    WHERE r.kind = 'person'
    group by link_uri, display_text";

    $response = $mysqli->query($sql);
    $json->contributor = array(); // clean list of contributors

    while($row = $response->fetch_assoc()){
        // organization and name are the same for some reason - not in YAML
        $row['name'] = $row['organisation'];
        $json->contributor[] = (object)$row;
    }

    // when and what
    $json->issued = $pub_date;
    $json->version = $version;
    $json->temporalScope = "Consensus taxonomy as of $pub_date";

    // we can pass a variable to indicate this is not a final release
    if(count($argv) > 2) $json->version .= ' ' . $argv[2];
    

    file_put_contents($file_path, json_encode($json, JSON_PRETTY_PRINT));
}

// needed to handle multibyte chars and upper casing the first
function mb_ucfirst($str) {
    $fc = mb_strtoupper(mb_substr($str, 0, 1));
    return $fc.mb_substr($str, 1);
}