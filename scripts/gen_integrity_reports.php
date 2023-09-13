<?php

/*

    The UI (both Rhakhis and the backend) try to enforce some data integrity rules.
    The DB Schema also tries to prevent bad things happening.
    That doesn't mean bad things don't happen or haven't been imported before.

    This is a selection of scripts that check for integrity failures in the data
    and create CSV files reporting on them in the downloads folder.

    FIXME: you shouldn't be able to have a type specimen and also a basionym.

*/

require_once('../config.php');
require_once('../include/DownloadFile.php');

echo "\nIntegrity Checks\n";

// check the output folder we all use exists
$downloads_dir = '../www/downloads/integrity_reports/';
if (!file_exists($downloads_dir)) {
    mkdir($downloads_dir, 0777, true);
}

// either call a named check or call all of them
if(count($argv) > 1){
    $argv[1]($downloads_dir);
}else{
    echo "Calling all checks.\n";
    check_no_deprecated_in_classification($downloads_dir);
    check_names_of_taxa_correct_status($downloads_dir);
    check_basionyms_not_chained($downloads_dir);
    check_full_name_string_unique($downloads_dir);
    check_homotypic_names_in_same_taxon($downloads_dir);
    check_genus_name_part_matches_parent($downloads_dir);
}


/*
    We should not have names that have the nomenclatural status 'deprecated' in the
    classification.
*/
function check_no_deprecated_in_classification($downloads_dir){

    $sql = "SELECT 
        n.id as name_id, i.`value` as wfo_id, n.`name_alpha` as 'name', n.`authors`, n.`rank`, n.`status`
        FROM `names` as n
        JOIN taxon_names as tn on tn.name_id = n.id
        JOIN taxa AS t ON t.id = tn.taxon_id
        JOIN identifiers as i on n.prescribed_id = i.id
        where n.`status` = 'deprecated';";

    run_sql_check(
        "check_no_deprecated_in_classification", // $name,
        "Deprecated names in classification.", //$title,
        "No deprecated names were found in the classification", // $success,
        "Names with status 'deprecated' should not appear in the classification as either accepted names of taxa or synonyms. ## found.", // $failure,
        $sql,
        $downloads_dir);
}

/*

    Names of taxa should be valid, conserved or sanctioned

*/
function check_names_of_taxa_correct_status($downloads_dir){

    $sql = "SELECT n.id as name_id, i.`value` as wfo_id, n.name_alpha as 'name', n.`status`, n.`year`
            FROM `names` as n
            JOIN taxon_names as tn on tn.name_id = n.id
            JOIN taxa AS t ON t.taxon_name_id = tn.id
            JOIN identifiers as i ON n.prescribed_id = i.id
            where n.`status` not in ('valid', 'conserved', 'sanctioned')
            order by n.`status`";

    run_sql_check(
        "check_names_of_taxa_correct_status", // $name,
        "Names of taxa are correct status.", //$title,
        "All taxon names are valid, conserved or sanctioned.", // $success,
        "Names of taxa should have a nomenclatural status of valid, conserved or sanctioned. Found ## that are not one of these statuses.", // $failure,
        $sql,
        $downloads_dir);
}

/*

    Basionyms shouldn't have basionyms.

*/
function check_basionyms_not_chained($downloads_dir){

    $sql = "SELECT
        com_novs.id as name_id, 
        cni.`value` as 'com_nov_id',
        com_novs.name_alpha as com_novs_name, com_novs.authors as com_novs_authors,
        bi.`value` as basionym_id,
        basionyms.name_alpha as basionym_name, 
        basionyms.authors as basionym_authors,
        cbi.`value` as 'chained_basionym_id',
        chained_basionyms.name_alpha as 'chained_basionym_name',
        chained_basionyms.authors as 'chained_basionym_authors'
        FROM `names` as com_novs 
        JOIN `names` as basionyms on com_novs.basionym_id = basionyms.id
        JOIN `names` AS chained_basionyms on basionyms.basionym_id = chained_basionyms.id
        JOIN identifiers as bi ON basionyms.prescribed_id = bi.id
        JOIN identifiers as cni ON com_novs.prescribed_id = cni.id
        JOIN identifiers as cbi ON chained_basionyms.prescribed_id = cbi.id
        where basionyms.basionym_id is not null";

    run_sql_check(
        "check_basionyms_not_chained", // $name,
        "Basionyms should not have basionyms.", //$title,
        "No basionyms were found to have basionyms.", // $success,
        "Names that are basionyms should not have basionyms themselves. Found ## chained basionyms.", // $failure,
        $sql,
        $downloads_dir);
}

/*

    Homotypic names should be in the same taxon.

*/

function check_homotypic_names_in_same_taxon($downloads_dir){

    $sql = "SELECT 
        com_nov.id as name_id,
        com_nov_i.`value` as com_nov_id,
        com_nov.name_alpha as com_nov_name,
        basionym_i.`value` as basionym_id,
        basionym.name_alpha as basionym_name
        FROM `names` AS basionym
        JOIN `names` AS com_nov on com_nov.basionym_id = basionym.id
        JOIN taxon_names as basionym_tn on basionym_tn.name_id = basionym.id
        JOIN taxon_names as com_nov_tn ON com_nov_tn.name_id = com_nov.id
        JOIN identifiers as basionym_i on basionym.prescribed_id = basionym_i.id
        JOIN identifiers as com_nov_i on com_nov.prescribed_id = com_nov_i.id
        WHERE basionym_tn.taxon_id != com_nov_tn.taxon_id";

    run_sql_check(
        "check_homotypic_names_in_same_taxon", // $name,
        "Homotypic names should be in the same taxon.", //$title,
        "No homotypic name pairs were split across taxa were found.", // $success,
        "Names that share a type should be in the same taxon as their placement is based on the type. Found ## conflicting homotypic placements.", // $failure,
        $sql,
        $downloads_dir);
}

/*

    full name strings should be unique

*/
function check_full_name_string_unique($downloads_dir){

    $sql = "SELECT replace(deduplication, '~', ' ') as duplication_string, count(*) as 'number_of_instances'
        from `names` 
        group by deduplication
        having count(*) > 1 
        order by count(*) desc, deduplication";


    run_sql_check(
        "check_full_name_string_unique", // $name,
        "Full name strings should be unique.", //$title,
        "No repeating full name strings were found.", // $success,
        "Full name strings (including authors and rank) should be unique or made unique by tweaking authors string. Found ## that repeat.", // $failure,
        $sql,
        $downloads_dir);

}

function check_genus_name_part_matches_parent($downloads_dir){

    $sql = "SELECT 
        n.id as name_id, 
        i.`value` as wfo_id,
        n.name_alpha, n.genus,
        parent_n.name_alpha as parent_name, parent_n.genus as parent_genus_part
        FROM `names` AS n 
        JOIN taxon_names as tn on tn.name_id = n.id
        JOIN taxa as t on t.taxon_name_id = tn.id
        JOIN taxa as parent_t on parent_t.id = t.parent_id
        JOIN taxon_names as parent_tn on parent_tn.id = parent_t.taxon_name_id
        JOIN `names` as parent_n on parent_n.id = parent_tn.name_id
        JOIN identifiers as i on i.id = n.prescribed_id 
        WHERE length(n.genus) > 0
        AND parent_n.`name` != n.genus AND (parent_n.genus is null OR parent_n.genus != n.genus);";

    run_sql_check(
        "check_genus_name_part_matches_parent", // $name,
        "Genus name part should match parent name.", //$title,
        "No names were found it taxonomy that have the wrong genus part for their placement.", // $success,
        "The genus part of a species (or other lower rank) should be appropriate for the taxon in which it is placed e.g. the genus. Found ## that don't match.", // $failure,
        $sql,
        $downloads_dir);

}

function check_species_name_part_matches_parent($downloads_dir){

    $sql = "SELECT 
        n.id as name_id, 
        i.`value` as wfo,
        n.name_alpha, 
        n.species,
        parent_n.name_alpha as parent_name, parent_n.species as parent_species_part
        FROM `names` AS n 
        JOIN taxon_names as tn on tn.name_id = n.id
        JOIN taxa as t on t.taxon_name_id = tn.id
        JOIN taxa as parent_t on parent_t.id = t.parent_id
        JOIN taxon_names as parent_tn on parent_tn.id = parent_t.taxon_name_id
        JOIN `names` as parent_n on parent_n.id = parent_tn.name_id
        JOIN identifiers as i on i.id = n.prescribed_id 
        WHERE length(n.species) > 0
        AND parent_n.`name` != n.species AND (parent_n.species is null OR parent_n.species != n.species);";

    run_sql_check(
        "check_species_name_part_matches_parent", // $name,
        "Species name part should match parent name.", //$title,
        "No names were found it taxonomy that have the wrong species part for their placement.", // $success,
        "The species part of a subspecies (or other lower rank) should be appropriate for the species in which it is placed. Found ## that don't match.", // $failure,
        $sql,
        $downloads_dir);

}

/*
    Run a check that expects an empty result set on success.
    It looks for a 'name_id' field in the results and if there is 
    one it replaces it with phylum and family columns.
*/
function run_sql_check($name, $title, $success, $failure, $sql, $downloads_dir){

    global $mysqli;

    echo "Calling: $name\n";

    $response = $mysqli->query($sql);

    // header for the csv
    $header = array();
    foreach ($response->fetch_fields() as $field) $header[] = $field->name;

    // if we have a name_id column replace it with two new ones
    $first_is_name_id = false;
    if($header[0] == 'name_id'){
        $first_is_name_id = true;
        array_shift($header);
        array_unshift($header, 'family');
        array_unshift($header, 'phylum');
    }

    // rows in the csv
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    
    // we write the csv even if it only has the header in it
    $out_file_path = $downloads_dir . $name . '.csv';
    $out = fopen($out_file_path, 'w');
    fputcsv($out, $header);
    foreach ($rows as $row){

        if($first_is_name_id){
            $name_id = array_shift($row);
            $higher_taxa = get_higher_taxa_for_name_id($name_id);
            array_unshift($row, $higher_taxa['family']);
            array_unshift($row, $higher_taxa['phylum']);
        }

        fputcsv($out, $row);
    } 
    fclose($out);

    // output the json describing the test
    $meta = array();
    $meta['filename'] = $out_file_path;
    $now = new DateTime();
    $meta['created'] = $now->format(DateTime::ATOM);
    $meta['title'] = $title;
    if(count($rows) > 0){
        $failure = str_replace('##', number_format(count($rows), 0), $failure);
        $meta['description'] = $failure;
    }else{
        $meta['description'] = $success;
    }
    $meta['size_bytes'] = filesize($out_file_path);
    $meta['size_human'] = DownloadFile::humanFileSize($meta['size_bytes']);
    file_put_contents($out_file_path . '.json', json_encode($meta, JSON_PRETTY_PRINT));


}

function get_higher_taxa_for_name_id($name_id){
    
    global $mysqli;

    $out = array('family' => 'unplaced', 'phylum' => 'unplaced'); // set them to null incase we don't find them.

    $sql = "WITH RECURSIVE parentage AS(
		SELECT n.name_alpha, n.`rank`, t.parent_id as parent_id
		FROM `names` as n 
		JOIN taxon_names as tn on tn.name_id = n.id
		JOIN taxa as t on t.taxon_name_id = tn.id
        WHERE n.id = $name_id
    UNION ALL
		SELECT n.name_alpha, n.`rank`, t.parent_id as parent_id
        FROM `names` as n 
		JOIN taxon_names as tn on tn.name_id = n.id
		JOIN taxa as t on t.taxon_name_id = tn.id
        JOIN parentage as p on p.parent_id = t.id
        WHERE t.parent_id is not null AND t.parent_id != t.id
        )
        SELECT * FROM parentage WHERE `rank` in ('family', 'phylum');";

    $response = $mysqli->query($sql);
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        $out[$row['rank']] = $row['name_alpha'];
    }

    return $out;

}