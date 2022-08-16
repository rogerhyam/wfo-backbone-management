<?php

// this will generate a friendly file showing duplicates to be removed.

require_once("../config.php");


/*
drop table if exists duplicates;
create table duplicates
select 
	i.`value` as wfo, n1.id as name_id, n1.name_alpha, n1.`rank`, n1.authors, n1.citation_micro, n1.`status` 
from `names` as n1 
join `identifiers` as i on n1.prescribed_id = i.id
where name_alpha in (
	select 
	n2.name_alpha
	from names as n2 group by n2.name_alpha, n2.`rank`, n2.authors having count(*) > 1
	order by n2.name_alpha, n2.authors
)
order by n1.name_alpha, n1.`rank`, n1.authors, n1.`status`
;

*/

// set up the output file
$out = fopen('../data/duplicate_names.csv', 'w');

$header = array(
    'comparator_string',
    'wfo',
    'name_id',
    'name_alpha',
    'rank',
    'authors',
    'citation_micro',
    'status',
    'remove',
    'merge_with'
);

fputcsv($out, $header);

// work through the rows in the dupes table and build sets to process
$response = $mysqli->query("SELECT concat_ws('*',`name_alpha`, `rank`, `authors`) as comparator, duplicates.* FROM duplicates ORDER BY `name_alpha`, `rank`, `authors`, `wfo`", MYSQLI_USE_RESULT);

$comparator = false;
$set = array();
while($row = $response->fetch_assoc() ){

    if($row['comparator'] !== $comparator){

        // send the old one off for processing
        put_set($out, $set, $comparator);

        // start a new set
        $set = array();
        $comparator = $row['comparator'];

    }

    $set[] = $row;

}

function put_set($out, $set, $comparator){

    echo "$comparator\t" . count($set) . "\n";

    $not_deprecated = array();
    $deprecated = array();
    foreach ($set as $row) {
        if($row['status'] == 'deprecated') $deprecated[] = $row;
        else $not_deprecated[] = $row;
    }

    if(count($not_deprecated) == 1){
        // we have a single candidate we could merge them with.
        $good_name = $not_deprecated[0];
        $good_name['remove'] = false;
        $good_name['merge_with'] = null;
        fputcsv($out, $good_name);
        foreach($deprecated as $bad_name){
            $bad_name['remove'] = "true";
            $bad_name['merge_with'] = $good_name['wfo'];
            fputcsv($out, $bad_name);
        }
    }elseif(count($not_deprecated) == 0){

        // if there are more than one of them then we can rationalize them down to a single bad name.

        // first one is kept
        $saved_name = $deprecated[0];
        $saved_name['remove'] = "false";
        $saved_name['merge_with'] = null;
        fputcsv($out, $saved_name);
        // subsequent ones are merged with it
        for ($i=1; $i < count($deprecated) ; $i++) { 
            $damned_name = $deprecated[$i];
            $damned_name['remove'] = "true";
            $damned_name['merge_with'] = $saved_name['wfo'];
            fputcsv($out, $damned_name);
        }

    }else{
        // there are more than one non-deprecated name so we need human input
        foreach($deprecated as $bad_name){
            $bad_name['remove'] = "false";
            $bad_name['merge_with'] = 'AMBIGUOUS';
            fputcsv($out, $bad_name);
        }

        foreach($not_deprecated as $bad_name){
            $bad_name['remove'] = "false";
            $bad_name['merge_with'] = 'AMBIGUOUS';
            fputcsv($out, $bad_name);
        }
    }

    // blank line to separate the sets
    fputcsv($out, array());

}


