<?php
/*

Keeping the gbif usageKey identifer in sync with gbif.

*/

require_once('../config.php');
require_once('../include/Variables.php');
require_once('../include/WfoDbObject.php');
require_once('../include/Name.php');
require_once('../include/User.php');

// we need to have a mock session  
$_SESSION['user'] = serialize(User::loadUserForDbId(1));

// we keep track of how far through the names table we are
// we work backwards so processing recent additions before
// trying to find those old ones
$start_id = Variables::get('gbif_progress_name_id', 10000000);

// and the last one in the list
$rows = $mysqli->query("SELECT max(id) as 'max' from `names`;")->fetch_all(MYSQLI_ASSOC);
$last_id = $rows[0]['max'];

// set up the context we will call under
$opts = [
    "https" => [
        "method" => "GET",
        "header" => "User-Agent: WFO-Rhakhis/1.0 (GBIF usageKey mapping script rhyam@rbge.org.uk) \r\n"
    ]
];
$context = stream_context_create($opts);

// work through the names a page at a time
$offset = 0;
while(true){

  $sql = "SELECT n.id, i.`value` FROM `names` as n
          LEFT JOIN identifiers as i on i.name_id = n.id and i.`kind` = 'gbif'
          WHERE i.`value` is null 
          AND n.id < $start_id
          ORDER BY n.id DESC
          LIMIT 1000 OFFSET $offset";

  $response = $mysqli->query($sql);
  $rows = $response->fetch_all(MYSQLI_ASSOC);

  if(count($rows) == 0){
    // we have reached the end of the run
    // next time we are run we will start from
    // the beginning again
    Variables::set('gbif_progress_name_id', 10000000);
    break;
  }

  $last_processed_name_id = 0;
  foreach ($rows as $row){
      
     $name = Name::getName($row['id']);
     $name_string = strip_tags($name->getFullNameString());
     $out = "{$row['id']}\t$name_string\t";

     // call GBIF!
    $query_params = array(
      'verbose' => false,
      'kingdom' => 'Plantae',
      'name' => $name_string
    );

    $uri = GBIF_WEB_SERVICE_URI . 'species/match?' . http_build_query($query_params);

    $gbif_data = json_decode(file_get_contents($uri, false, $context));
    $out .= "{$gbif_data->confidence}\t{$gbif_data->usageKey}\t";

    if($gbif_data->confidence == 100 && strtolower($gbif_data->rank) == $name->getRank()){
      $name->addIdentifier($gbif_data->usageKey, 'gbif');
      $out .= "MATCH\n";
    }else{
      $out .= "miss\n";
    }

    $last_processed_name_id = $row['id'];

    if(isset($argv[1]) && $argv[1] == 'verbose') echo $out;

    sleep(0.5); // don't do a denial of service attack!

  }

  // end of page
  $offset += 1000;

  echo "\nOffset: " . number_format($offset, 0) . "\n";

  // don't fall backwards more than one page
  // don't do this for every name as it would add 1,000s of updates
  Variables::set('gbif_progress_name_id', $last_processed_name_id);

}

