<?php

/*

    This is a run once (well five times actually) script to 
    convert legacy DwC dump files into Plant List JSON format.

    2022-06
    2021-12
    2019-05
    2019-03
    2018-07

*/

require_once("../config.php");

$version = "2018-07";

$dwc_file_path = "../data/versions/dwc_{$version}.txt.csv";
$json_file_path = "../data/versions/plant_list_{$version}.json";

// we don't want to accidentally create fields other than these
$pl_fields = array(
    "id",
    "wfo_id_s",
    "full_name_string_html_s",
    "full_name_string_plain_s",
    "full_name_string_no_authors_plain_s",
    "name_string_s",
    "genus_string_s",
    "species_string_s",
    "nomenclatural_status_s",
    "rank_s",
    "citation_micro_s",
    "citation_micro_t",
    "identifiers_other_kind_ss",
    "identifiers_other_value_ss",
    "wfo_id_deduplicated_ss",
    "authors_string_s",
    "authors_string_html_s",
    "authors_string_labels_ss",
    "authors_string_ids_ss",
    "reference_kinds_ss",
    "reference_uris_ss",
    "reference_thumbnail_uris_ss",
    "reference_labels_ss",
    "reference_comments_ss",
    "reference_contexts_ss",
    "role_s",
    "classification_id_s",
    "classification_year_i",
    "classification_month_i",
    "full_name_string_alpha_s",
    "full_name_string_alpha_t_sort",
    "solr_import_dt",
    "parent_id_s",
    "placed_in_genus_s",
    "placed_in_family_s",
    "placed_in_order_s",
    "placed_in_phylum_s",
    "placed_in_code_s",
    "name_descendent_path",
    "name_ancestor_path",
    "name_path_s",
    "editors_name_ss",
    "editors_orcid_ss",
    "ten_name_s",
    "ten_uri_s",
    "ten_comment_s",
    "placed_in_species_s",
    "accepted_id_s",
    "accepted_full_name_string_html_s",
    "accepted_full_name_string_plain_s",
    "accepted_child_count_is",
    "placed_in_subspecies_s",
    "placed_in_subfamily_s",
    "placed_in_variety_s",
    "placed_in_superorder_s",
    "placed_in_subclass_s",
    "placed_in_class_s",
    "placed_in_form_s",
    "placed_in_section_s",
    "placed_in_subgenus_s",
    "placed_in_tribe_s",
    "placed_in_series_s",
    "placed_in_prole_s",
    "placed_in_subsection_s",
    "placed_in_subseries_s",
    "placed_in_subvariety_s"
);

$rank_map = array(
    "phylum" => "phylum",
    "class" => "class",
    "subclass" => "subclass",
    "superorder" => "superorder",
    "order" => "order",
    "family" => "family",
    "subfamily" => "subfamily",
    "supertribe" => "supertribe",
    "tribe" => "tribe",
    "subtribe" => "subtribe",
    "genus" => "genus",
    "section" => "section",
    "subsection" => "subsection",
    "subgenus" => "subgenus",
    'series' => 'series',
    'subseries' => 'subseries',
    "species" => "species",
    "nothospecies"=> "species",
    "nothosubsp."=> "subspecies",
    "nothovar."=> "variety",
    "subspecies" => "subspecies",
    "proles" => "prole",
    "variety" => "variety",
    "convar." => "variety",
    "convariety" => "variety",
    "provar." => "variety",
    "subvariety" => "subvariety",
    "form" => "form",
    "forma" => "form",
    "subform" => "subform",
    "unranked" => "unranked"
);

$in = fopen($dwc_file_path, 'r');
$out = fopen($json_file_path, 'w');
fwrite($out, "[\n"); // wrap it in an array

// read the header
$header = fgetcsv($in, 0, "\t");

$counter = 0;
while($row = fgetcsv($in, 0, "\t")){

    if(count($header) != count($row)){
        echo "\nColumn count mismatch\n";
        continue;
    } 

    // make the row into an object as it is much easier to deal with
    $dwc = array();
    for ($i=0; $i < count($header); $i++) $dwc[$header[$i]] = isset($row[$i]) ? $row[$i] : "";
    $dwc = (object)$dwc;

    // a destination object
    $pl = (object)[];

    // let the mapping begin!
    
    // id
    $pl->id = $dwc->taxonID . "-" . $version;

    // wfo_id_s
    $pl->wfo_id_s= $dwc->taxonID;

    // classification_id_s
    $pl->classification_id_s= $version;
    $parts = explode('-', $version);
    // classification_year_i
    $pl->classification_year_i= $parts[0];
    // classification_month_i
    $pl->classification_month_i= $parts[1];

    // solr_import_dt
    $pl->solr_import_dt  = date('Y-m-d\Th:i:s\Z');

    // parse that name out
    
    // rank_s - do this first and the name depends on it
    // start by normalising the rank as much as we can
    // rank map - make sure we only accept certain ranks
    $rank = mb_strtolower(trim($dwc->taxonRank));
    if(array_key_exists($rank, $rank_map)) {
        $rank = $rank_map[$rank];
    }else{
        echo "\nUnrecognised rank: {$dwc->taxonRank}\n";
        exit;
    }
    $pl->rank_s = $rank;

    // authors_string_s
    $pl->authors_string_s = trim($dwc->scientificNameAuthorship);
    // authors_string_html_s
    $pl->authors_string_html_s = "<span class=\"wfo-list-authors\">{$pl->authors_string_s}</span>";

    // authors_string_labels_ss - only going to support this in 2022-12 forward
    // authors_string_ids_ss - only going to support this in 2022-12 forward

     // the name depends on the rank
    $scientificName = trim($dwc->scientificName);
    $scientificName = str_replace('×', '', $scientificName); // remove hybrid symbols
    $scientificName = str_replace('ä', 'ae', $scientificName);
    $scientificName = str_replace('ö', 'oe', $scientificName);
    $scientificName = str_replace('ü', 'ue', $scientificName);
    $scientificName = str_replace('é', 'e', $scientificName);
    $scientificName = str_replace('è', 'e', $scientificName);
    $scientificName = str_replace('ê', 'e', $scientificName);
    $scientificName = str_replace('ñ', 'n', $scientificName);
    $scientificName = str_replace('ø', 'oe', $scientificName);
    $scientificName = str_replace('å', 'ao', $scientificName);
    $scientificName = str_replace("'", '', $scientificName); // can you believe an o'donolli 

    switch ($rank) {

        // binomials
        case 'species':
        case "section":
        case "subsection":
        case "subgenus":
        case 'series':
        case 'subseries':

            $parts = explode(' ', $scientificName);
            
            // genus_string_s
            $pl->genus_string_s = $parts[0];

            // name_string_s
            // subgenera and sections may include the rank or may not - so take the last word
            $pl->name_string_s = array_pop($parts);
            
            // species_string_s
            $pl->species_string_s = null;

            // full_name_string_plain_s
            $pl->full_name_string_plain_s = "{$pl->genus_string_s} {$pl->name_string_s} {$pl->authors_string_s}";

            // full_name_string_no_authors_plain_s
            $pl->full_name_string_plain_s = "{$pl->genus_string_s} {$pl->name_string_s}";

            // full_name_string_alpha_s
            $pl->full_name_string_alpha_s = "{$pl->genus_string_s} {$pl->name_string_s}";

            // full_name_string_alpha_t_sort
            $pl->full_name_string_alpha_t_sort = "{$pl->genus_string_s} {$pl->name_string_s}";

            // full_name_string_html_s
            $pl->full_name_string_html_s = "<span class=\"wfo-name-full\" ><span class=\"wfo-name\"><i>{$pl->genus_string_s}</i> <i>{$pl->name_string_s}</i></span> {$pl->authors_string_html_s}</span>";

            break;
        
        // trinomials
        case 'subspecies':
        case 'variety':
        case 'subvariety':
        case 'form':
        case 'subform':
        case 'prole':

            $parts = explode(' ', $scientificName);

            // genus_string_s
            $pl->genus_string_s = $parts[0];

            // name_string_s
            $pl->name_string_s = array_pop($parts); // last element avoiding ssp. or var. etc
            
            // species_string_s
            $pl->species_string_s = isset($parts[1]) ? $parts[1] : null;

            // get an official abbreviation
            $rank_abbreviation = $ranks_table[$rank]['abbreviation'];

            // full_name_string_plain_s
            $pl->full_name_string_plain_s = "{$pl->genus_string_s} {$pl->species_string_s} $rank_abbreviation {$pl->name_string_s} {$pl->authors_string_s}";

            // full_name_string_no_authors_plain_s
            $pl->full_name_string_plain_s = "{$pl->genus_string_s} {$pl->species_string_s} $rank_abbreviation {$pl->name_string_s}";

            // full_name_string_alpha_s
            $pl->full_name_string_alpha_s = "{$pl->genus_string_s} {$pl->species_string_s} {$pl->name_string_s}";

            // full_name_string_alpha_t_sort
            $pl->full_name_string_alpha_t_sort = "{$pl->genus_string_s} {$pl->species_string_s} {$pl->name_string_s}";

            // full_name_string_html_s
            $pl->full_name_string_html_s = "<span class=\"wfo-name-full\" ><span class=\"wfo-name\"><i>{$pl->genus_string_s}</i> <i>{$pl->species_string_s}</i> $rank_abbreviation <i>{$pl->name_string_s}</i></span> {$pl->authors_string_html_s}</span>";

            break;
 
        // genus
        case 'genus':

            // name_string_s
            $pl->name_string_s = $scientificName;
            
            // genus_string_s
            $pl->genus_string_s = null;

            // species_string_s
            $pl->species_string_s = null;

            // full_name_string_plain_s
            $pl->full_name_string_plain_s = "{$pl->name_string_s} {$pl->authors_string_s}";

            // full_name_string_no_authors_plain_s
            $pl->full_name_string_plain_s = "{$pl->name_string_s}";

            // full_name_string_alpha_s
            $pl->full_name_string_alpha_s = "{$pl->name_string_s}";

            // full_name_string_alpha_t_sort
            $pl->full_name_string_alpha_t_sort = "{$pl->name_string_s}";

            // full_name_string_html_s
            $pl->full_name_string_html_s = "<span class=\"wfo-name-full\" ><span class=\"wfo-name\"><i>{$pl->name_string_s}</i></span> {$pl->authors_string_html_s}</span>";
    
            break;
        
        // other mononomials
        default:

            // name_string_s
            $pl->name_string_s = $scientificName;
            
            // genus_string_s
            $pl->genus_string_s = null;

            // species_string_s
            $pl->species_string_s = null;

            // full_name_string_plain_s
            $pl->full_name_string_plain_s = "{$pl->name_string_s} {$pl->authors_string_s}";

            // full_name_string_no_authors_plain_s
            $pl->full_name_string_plain_s = "{$pl->name_string_s}";

            // full_name_string_alpha_s
            $pl->full_name_string_alpha_s = "{$pl->name_string_s}";

            // full_name_string_alpha_t_sort
            $pl->full_name_string_alpha_t_sort = "{$pl->name_string_s}";

            // full_name_string_html_s
            $pl->full_name_string_html_s = "<span class=\"wfo-name-full\" ><span class=\"wfo-name\">{$pl->name_string_s}</span> {$pl->authors_string_html_s}</span>";
            break;

    }
    
    // nomenclatural_status_s
    if(isset($dwc->doNotProcess) && $dwc->doNotProcess){
        $nomenclaturalStatus = 'deprecated';
    }else{
        $nomenclaturalStatus = mb_strtolower(trim($dwc->nomenclaturalStatus));
    }

    switch ($nomenclaturalStatus) {
        
        case 'valid':
            $pl->nomenclatural_status_s = 'valid';
            break;

        case 'invalid':
        case 'invalidum':
            $pl->nomenclatural_status_s = 'invalid';
            break;

        case 'illegitimum':
        case 'illegitimate':
            $pl->nomenclatural_status_s = 'illegitimate';
            break;

        case 'conservandum':
        case 'conserved':
            $pl->nomenclatural_status_s = 'conserved';
            break;

        case 'rejiciendum':
        case 'rejected':
            $pl->nomenclatural_status_s = 'rejected';
            break;

        case 'sanctioned':
            $pl->nomenclatural_status_s = 'sanctioned';
            break;

        case 'deprecated':
            $pl->nomenclatural_status_s = 'deprecated';
            break;

        default:
            break;
    }
    

    // citation_micro_s
    $pl->citation_micro_s = trim($dwc->namePublishedIn);

    // citation_micro_t
    $pl->citation_micro_t = trim($dwc->namePublishedIn);
    
    // identifiers_other_kind_ss
    // identifiers_other_value_ss

    $pl->identifiers_other_kind_ss = array();
    $pl->identifiers_other_value_ss = array();

    // do we have an ipni id?
    if(preg_match('/^[0-9]{1,9}-[0-9]{1,2}$/', trim($dwc->scientificNameID))){
        $pl->identifiers_other_kind_ss[] = 'ipni';
        $pl->identifiers_other_value_ss[] = 'urn:lsid:ipni.org:names:' . trim($dwc->scientificNameID);
    } 

    if(preg_match('/^urn:lsid:ipni.org:names:/', trim($dwc->scientificNameID))){
        $pl->identifiers_other_kind_ss[] = 'ipni';
        $pl->identifiers_other_value_ss[] = trim($dwc->scientificNameID);
    } 

    // localID
    if(isset($dwc->localID)){
        $pl->identifiers_other_kind_ss[] = 'ten';
        $pl->identifiers_other_value_ss[] = trim($dwc->localID);
    }

    // tplId
    if(isset($dwc->tplId)){
        $pl->identifiers_other_kind_ss[] = 'tpl';
        $pl->identifiers_other_value_ss[] = trim($dwc->tplId);
    }

    // basionym time
    if(isset($dwc->originalNameUsageID)){
        $pl->basionym_id_s = trim($dwc->originalNameUsageID);
    }
    
    // reference_kinds_ss - no references handled before 2022-12
    // reference_uris_ss - no references handled before 2022-12
    // reference_thumbnail_uris_ss - no references handled before 2022-12
    // reference_labels_ss - no references handled before 2022-12
    // reference_comments_ss - no references handled before 2022-12
    // reference_contexts_ss - no references handled before 2022-12

    // taxonomy time!

    if(isset($dwc->parentNameUsageID) && $dwc->parentNameUsageID){
        // role_s
        $pl->role_s = 'accepted';

        // parent_id_s
        $pl->parent_id_s = trim($dwc->parentNameUsageID) . '-' . $version;


    }elseif(isset($dwc->acceptedNameUsageID) && $dwc->acceptedNameUsageID){
        
        // role_s
        $pl->role_s = 'synonym';

        // accepted_id_s
        $pl->accepted_id_s = trim($dwc->acceptedNameUsageID) . '-' . $version;

    }else{
        // role_s
        $pl->role_s = 'unplaced';

    }

    // taxonomy up the way
    
    // placed_in_phylum_s
    if(isset($dwc->majorGroup)){
        switch ($dwc->majorGroup) {
            case 'A':
                $pl->placed_in_phylum_s = 'Angiosperms';
                break;
            case 'B':
                $pl->placed_in_phylum_s = 'Bryophytes';
                break;
            case 'G':
                $pl->placed_in_phylum_s = 'Gymnosperms';
                break;
            case 'P':
                $pl->placed_in_phylum_s = 'Pteridophytes';
                break;        
            default:
                $pl->placed_in_phylum_s = null;
                break;
        }
    }

    
    // placed_in_family_s
    if(isset($dwc->family)) $pl->placed_in_family_s = trim($dwc->family);

    // placed_in_subfamily_s
    if(isset($dwc->subfamily)) $pl->placed_in_subfamily_s = trim($dwc->subfamily);
    
    // placed_in_tribe_s
    if(isset($dwc->tribe)) $pl->placed_in_tribe_s = trim($dwc->tribe);

    // placed_in_subtribe_s
    if(isset($dwc->subtribe)) $pl->placed_in_subtribe_s = trim($dwc->subtribe);

    // placed_in_subgenus_s
    if(isset($dwc->subgenus)) $pl->placed_in_subgenus_s = trim($dwc->subgenus);

    // placed_in_genus_s
    if($pl->rank_s == 'genus'){
        $pl->placed_in_genus_s = $pl->name_string_s;
    }elseif($pl->genus_string_s){
        $pl->placed_in_genus_s = $pl->genus_string_s;
    }else{
        $pl->placed_in_genus_s = null;
    }

    // placed_in_species_s
    if($pl->rank_s == 'species'){
        $pl->placed_in_species_s = $pl->name_string_s;
    }elseif($pl->species_string_s){
        $pl->placed_in_species_s = $pl->species_string_s;
    }else{
        $pl->species_in_genus_s = null;
    }
 
    if(trim($dwc->family)) $pl->placed_in_family_s = trim($dwc->family);
    
    if(isset($dwc->taxonRemarks)) $pl->comment_t = trim($dwc->taxonRemarks);

    // FOLLOWING NOT SUPPORTED PRIOR TO 2022-12

    // name_descendent_path
    // name_ancestor_path
    // name_path_s
    // editors_name_ss
    // editors_orcid_ss
    // ten_name_s - not supported prior to 2022-12
    // ten_uri_s - not supported prior to 2022-12
    // ten_comment_s - not supported prior to 2022-12
    // accepted_full_name_string_html_s
    // accepted_full_name_string_plain_s
    // accepted_child_count_is
    // placed_in_species_s
    // placed_in_genus_s
    // placed_in_order_s
    // placed_in_code_s
    // placed_in_subspecies_s
    // placed_in_subfamily_s
    // placed_in_variety_s
    // placed_in_superorder_s
    // placed_in_subclass_s
    // placed_in_class_s
    // placed_in_form_s
    // placed_in_section_s
    // placed_in_series_s
    // placed_in_prole_s
    // placed_in_subsection_s
    // placed_in_subseries_s
    // placed_in_subvariety_s

    // write the json to the file
    if($counter > 0) fwrite($out, ",\n");
    fwrite($out, json_encode($pl));
     
    $counter++;
    
    //if($counter > 10000) break;

    if($counter % 10000 == 0) echo "\n" . number_format($counter, 0);

}

fclose($in);
fwrite($out, "\n]\n"); // wrap it in an array
fclose($out);

