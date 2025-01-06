<?php

/*

    This will create a table of author/year publication for analysis

    YOU PROBABLY DON'T WANT TO RUN THIS ON LIVE

    The table is just for data analysis and isn't part or the Rhakhis application
    so can safely be dropped when not needed. 

*/

require_once('../config.php');
require_once('../include/AuthorTeam.php');
require_once('../include/AuthorTeamMember.php');
require_once('../include/SPARQLQueryDispatcher.php');

echo "Building list of active authors\n";

// does the table if it exists
$mysqli->query("DROP TABLE IF EXISTS `author_stats_temp`;");
echo "Dropped table\n";

// create the table
$mysqli->query("CREATE TABLE `author_stats_temp` (
  `abbreviation` varchar(45) NOT NULL COMMENT 'The abbreviation may be in the author lookup table (official) or may be not.',
  `year` int DEFAULT NULL,
  `wfo_id` varchar(14) DEFAULT NULL
)");

echo "Created table\n";

// work through all non deprecated names 

$offset =0;

while(true){

    $response = $mysqli->query("SELECT i.`value` as wfo, n.authors, n.`year` from `names` as n
    join identifiers as i on i.id = n.prescribed_id and i.kind = 'wfo'
    where n.status != 'deprecated' ORDER BY n.`id` LIMIT 1000 OFFSET $offset;" );

    if($response->num_rows < 1) break;
    else $offset += 1000;

    while($row = $response->fetch_assoc()){


        echo "{$row['wfo']}\t";
        echo "{$row['year']}\t";
        echo "{$row['authors']}\t\t\t";
    
        if(!$row['authors']) continue;
    
        // we remove the paranthetical authors as they aren't publishers of this name
        // at this time
        $authors = preg_replace('/\(.*\)/', '', $row['authors']);
        $authors = trim($authors);
    
        // remove anything before the ex because that wasn't a valid name
        if(strpos($authors, ' ex ')){
            $authors = substr($authors, strpos($authors, ' ex ') + 3);
            $authors = trim($authors);
        }
    
        echo "{$authors}\t";

        // parse the remaining author string
        //$team = new AuthorTeam($authors, true, null, true); // updating the authors
        $team = new AuthorTeam($authors);

        // add a row for each of the members of the team
        $members = $team->getMembers();

        foreach($members as $member){
            if(strlen($member->abbreviation) > 45){
                echo "TOO LONG";
                break;
            }

            $abbreviation_safe = $mysqli->real_escape_string($member->abbreviation);
            $year = $row['year'] ? $row['year'] : 'NULL';
            $mysqli->query("INSERT INTO `author_stats_temp` (`abbreviation`, `year`, `wfo_id`) VALUES ('$abbreviation_safe', {$year}, '{$row['wfo']}' ) ;");
            if(!$mysqli->error) echo "DONE";
            else echo $mysqli->error;
        }
    
        echo "\n";


    }

}

/*

SQL for common queries

# Unique author names per decade
SELECT `year`, count(distinct(ast.abbreviation)) as authors
FROM author_stats_temp AS ast
JOIN author_lookup as al on al.abbreviation = ast.abbreviation
WHERE `year` is not null
GROUP BY `year`
ORDER BY `year`

# Productivity - number of names/taxonomist/decade 
with decades as (select abbreviation, floor(`year` / 10) * 10 as decade from author_stats_temp where `year` is not null)
select decade, count(distinct(abbreviation)) as authors, count(*) as 'names', round(count(*)/count(distinct(abbreviation))) as per_author from decades group by decade order by decade asc;







*/



