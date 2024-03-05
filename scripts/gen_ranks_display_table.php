<?php 

// spit out a simple representation of the ranks table for use in documentation

require_once('../config.php');

$level = 0;

foreach($ranks_table as $rank_name => $rank){
    $children = implode(';', $rank['children']);
    $aka = implode(';', $rank['aka']);
    echo "{$level}\t{$rank_name}\t{$children}\t{$rank['abbreviation']}\t{$rank['plural']}\t{$aka}\n";

    $level++;
}

//print_r($ranks_table);