<?php

require_once('../config.php');

// this is run by cron to prevent the genus stats log table getting too big
// it will produce summary rows for each month for each name after six months
/*

ALTER TABLE `promethius`.`stats_genera_log` 
ADD INDEX `name_id` USING BTREE (`name_id`) VISIBLE,
ADD INDEX `modified` USING BTREE (`modified`) VISIBLE;

*/


echo "\nSummarizing genus stats older than 1 month.\n";

// get all the names that have more than one row in a month over 6 months old
$sql = "SELECT name_id, extract(YEAR_MONTH FROM modified) as ym, count(*) as n
FROM stats_genera_log 
where `modified` < now() - INTERVAL 1 MONTH 
group by name_id, extract(YEAR_MONTH FROM modified)
having n > 1
order by n desc";

echo "Fetching candidates ... ";
$response = $mysqli->query($sql);
$candidates = $response->fetch_all(MYSQLI_ASSOC);
$response->close();

echo number_format(count($candidates), 0);
echo " found. \n";

foreach($candidates as $c){

    // fetch all the rows for this one
    $sql = "SELECT * FROM stats_genera_log WHERE name_id = {$c['name_id']} AND extract(YEAR_MONTH FROM modified) = '{$c['ym']}' AND `modified` < now() - INTERVAL 1 MONTH ORDER BY `modified`;";
    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    $n = count($rows);

    echo "{$c['name_id']}\t{$c['ym']}\t$n\n";

    $summary = array();
    $summary['name_id'] = $c['name_id'];

    // initialize the numeric values
    $summary["taxa"] = 0;
    $summary["taxa_with_editors"] = 0;
    $summary["species"] = 0;
    $summary["subspecies"] = 0;
    $summary["variety"] = 0;
    $summary["synonyms"] = 0;
    $summary["syn_species"] = 0;
    $summary["syn_subspecies"] = 0;
    $summary["syn_variety"] = 0;
    $summary["unplaced"] = 0;
    $summary["unplaced_species"] = 0;
    $summary["unplaced_subspecies"] = 0;
    $summary["unplaced_variety"] = 0;
    $summary["gbif_gap_species"] = 0;
    $summary["gbif_gap_total_occurrences"] = 0;
    $summary["gbif_gap_mean"] = 0;
    $summary["gbif_gap_stddev"] = 0;

    foreach($rows as $row){

        // string fields are copied so that we keep the
        // latest version only
        $summary['wfo'] = $row['wfo'];
        $summary['name'] = $row['name'];
        $summary['role'] = $row['role'];
        $summary['phylum'] = $row['phylum'];
        $summary['phylum_wfo'] = $row['phylum_wfo'];
        $summary['family'] = $row['family'];
        $summary['family_wfo'] = $row['family_wfo'];
        $summary['order'] = $row['order'];
        $summary['order_wfo'] = $row['order_wfo'];

        // int fields are added initially - at the end we will average them.
        $summary["taxa"] += $row['taxa'];
        $summary["taxa_with_editors"] += $row['taxa_with_editors'];
        $summary["species"] += $row['species'];
        $summary["subspecies"] += $row['subspecies'];
        $summary["variety"] += $row['variety'];
        $summary["synonyms"] += $row['synonyms'];
        $summary["syn_species"] += $row['syn_species'];
        $summary["syn_subspecies"] += $row['syn_subspecies'];
        $summary["syn_variety"] += $row['syn_variety'];
        $summary["unplaced"] += $row['unplaced'];
        $summary["unplaced_species"] += $row['unplaced_species'];
        $summary["unplaced_subspecies"] += $row['unplaced_subspecies'];
        $summary["unplaced_variety"] += $row['unplaced_variety'];
        $summary["gbif_gap_species"] += $row['gbif_gap_species'];
        $summary["gbif_gap_total_occurrences"] += $row['gbif_gap_total_occurrences'];
        $summary["gbif_gap_mean"] += $row['gbif_gap_mean'];
        $summary["gbif_gap_stddev"] += $row['gbif_gap_stddev'];

    }

    // average the numeric fields


    // integers
    $summary["taxa"] = (int)round($summary["taxa"]/$n);
    $summary["taxa_with_editors"] = (int)round($summary['taxa_with_editors']/$n);
    $summary["species"] = (int)round($summary['species']/$n);
    $summary["subspecies"] = (int)round($summary['subspecies']/$n);
    $summary["variety"] = (int)round($summary['variety']/$n);
    $summary["synonyms"] = (int)round($summary['synonyms']/$n);
    $summary["syn_species"] = (int)round($summary['syn_species']/$n);
    $summary["syn_subspecies"] = (int)round($summary['syn_subspecies']/$n);
    $summary["syn_variety"] = (int)round($summary['syn_variety']/$n);
    $summary["unplaced"] = (int)round($summary['unplaced']/$n);
    $summary["unplaced_species"] = (int)round($summary['unplaced_species']/$n);
    $summary["unplaced_subspecies"] = (int)round($summary['unplaced_subspecies']/$n);
    $summary["unplaced_variety"] = (int)round($summary['unplaced_variety']/$n);
    $summary["gbif_gap_species"] = (int)round($summary['gbif_gap_species']/$n);
    $summary["gbif_gap_total_occurrences"] = (int)round($summary['gbif_gap_total_occurrences']/$n);

    // floats
    $summary["gbif_gap_mean"] = $summary['gbif_gap_mean']/$n;
    $summary["gbif_gap_stddev"] = $summary['gbif_gap_stddev']/$n;

    // make sure the human strings are safe
    $summary['name'] = $mysqli->real_escape_string($row['name']);
    $summary['phylum'] = $mysqli->real_escape_string($row['phylum']);
    $summary['family'] = $mysqli->real_escape_string($row['family']);
    $summary['order'] = $mysqli->real_escape_string($row['order']);

    // delete the existing rows
    $sql = "DELETE FROM stats_genera_log WHERE name_id = {$c['name_id']} AND extract(YEAR_MONTH FROM modified) = '{$c['ym']}' AND `modified` < now() - INTERVAL 1 MONTH ORDER BY `modified`;";
    echo "Rows affected: " . $mysqli->affected_rows . "\n";
    //echo $sql . "\n";
    $mysqli->query($sql);


    // insert the summary row
    $year = substr($c['ym'], 0, 4);
    $month = substr($c['ym'], 4);
    $modified = date("Y-m-t", strtotime("$year-$month-01")); // last day of month 

    $sql = "INSERT INTO stats_genera_log (
        `name_id`, 
        `taxa`, 
        `taxa_with_editors`, 
        `species`, 
        `subspecies`, 
        `variety`, 
        `synonyms`, 
        `syn_species`, 
        `syn_subspecies`, 
        `syn_variety`, 
        `unplaced`, 
        `unplaced_species`, 
        `unplaced_subspecies`, 
        `unplaced_variety`, 
        `gbif_gap_species`, 
        `gbif_gap_total_occurrences`, 
        `gbif_gap_mean`, 
        `gbif_gap_stddev`, 
        `wfo`, 
        `name`, 
        `role`, 
        `phylum`, 
        `phylum_wfo`, 
        `family`, 
        `family_wfo`, 
        `order`, 
        `order_wfo`,
        `modified`
    ) VALUES (
        {$summary['name_id']},
        {$summary['taxa']},
        {$summary['taxa_with_editors']},
        {$summary['species']},
        {$summary['subspecies']},
        {$summary['variety']},
        {$summary['synonyms']},
        {$summary['syn_species']},
        {$summary['syn_subspecies']},
        {$summary['syn_variety']},
        {$summary['unplaced']},
        {$summary['unplaced_species']},
        {$summary['unplaced_subspecies']},
        {$summary['unplaced_variety']},
        {$summary['gbif_gap_species']},
        {$summary['gbif_gap_total_occurrences']},
        {$summary['gbif_gap_mean']},
        {$summary['gbif_gap_stddev']},
        '{$summary['wfo']}',
        '{$summary['name']}',
        '{$summary['role']}',
        '{$summary['phylum']}',
        '{$summary['phylum_wfo']}',
        '{$summary['family']}',
        '{$summary['family_wfo']}',
        '{$summary['order']}',
        '{$summary['order_wfo']}',
        '$modified'
    );";

    $response = $mysqli->query($sql);

}