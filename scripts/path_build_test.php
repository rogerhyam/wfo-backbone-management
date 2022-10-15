<?php



// test to see how fast we can build a set of paths
// and how much memory they use.

require_once('../config.php');

$root_wfo = 'wfo-7000000323';

$paths = array();

add_name($root_wfo, $root_wfo, $paths);
echo convert(memory_get_usage(true));
print_r($paths);


function add_name($wfo, $current_path, &$paths){

    global $mysqli;
    $sql = "SELECT i.`value` as 'wfo', tn.name_id, atn.name_id as accepted_name_id,  atn.name_id = tn.name_id as 'accepted'  FROM 
            taxon_names as tn join identifiers as i on i.name_id = tn.name_id
        join taxa as t on tn.taxon_id = t.id
        join taxon_names as atn on t.taxon_name_id = atn.id
        WHERE i.kind = 'wfo'
        AND t.parent_id in 
        (
            SELECT t.id FROM 
            taxon_names as tn join identifiers as i on i.name_id = tn.name_id
            join taxa as t on tn.taxon_id = t.id
            WHERE i.kind = 'wfo'
            AND i.`value` = '$wfo'
        )";
            
    // if I don't have children then I write myself out if 

    //$paths[$wfo] = array();

    $response = $mysqli->query($sql);
    if($response->num_rows > 0){

        while($row = $response->fetch_assoc()){

            if($row['accepted']){
                // we have children so aren't the end of a path
                add_name($row['wfo'], $current_path . ">" . $wfo, $paths);
            }else{
                // it is a synonym so has to be the end of the path
                $paths[$row['wfo']]['rhakhis'] = $current_path . ":" . $row['wfo'];
            }
        }

    }else{
        // no children so this is the end of the line
        // can't be a synonym because we aren't called for those
        $paths[$wfo]['rhakhis'] = $current_path . ">" . $wfo;
    }

}



function convert($size)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}
