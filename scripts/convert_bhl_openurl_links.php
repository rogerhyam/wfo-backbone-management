<?php

require_once("../config.php");

/*

This is a run once to clean out the openurl links (333k of them)

php -d memory_limit=2G convert_bhl_openurl_links.php

*/


echo "\nConverting BHL links to page links\n\n";

$sql =  "SELECT * FROM promethius.references where link_uri like 'http://www.biodiversitylibrary.org/openurl%' and
link_uri like '%http://www.biodiversitylibrary.org/bibliography%'";

$response = $mysqli->query($sql);
$rows = $response->fetch_all(MYSQLI_ASSOC);

$counter = 0;
foreach($rows as $row){

	// call for the redirect to the page.
	$counter++;
	echo "$counter\t{$row['id']}\t";

	$link_uri = $row['link_uri'];

	$link_uri = preg_replace('/^http:\/\//', 'https://', $link_uri);

	$headers = get_headers($link_uri, true);
	$location = $headers['Location'];

	if(!is_string($location) || !preg_match('/^https:\/\/www.biodiversitylibrary.org\/page\//', $location)){
		echo "Not a BHL page URI - continuing. $location\n";
		continue;
	}

	echo $location . "\t";

	// before we call it we check if exists
	$uri_safe = $mysqli->real_escape_string($location);
	$response = $mysqli->query("SELECT * FROM `references` WHERE link_uri = '$uri_safe'");
	if($response->num_rows){
		echo "already exists!\n";
		continue;
	}

	$headers = get_headers($location, true);
	echo $headers[0] . "\t";

	if ($headers[0] != "HTTP/1.1 200 OK"){
		echo "Failed to get page so continuing.\n";
		continue;
	}

	// thumbnail uri by calculation
	$thumbnail_uri = preg_replace('/page/', 'pagethumb', $location);
	$headers = get_headers($thumbnail_uri, true);
	echo $headers[0] . "\t";

	if ($headers[0] != "HTTP/1.1 200 OK"){
		$thumbnail_uri = null;
	}

	// got to here so safe to update the reference
	$sql = "UPDATE `references` SET link_uri = '$uri_safe'";
	if($thumbnail_uri){
		$thumb_safe = $mysqli->real_escape_string($thumbnail_uri);
		$sql .= ", thumbnail_uri = '$thumb_safe' ";
	}
	$sql .= " WHERE id = {$row['id']}";
	$mysqli->query($sql);
	if($mysqli->error){
		echo $sql;
		echo $mysqli->error;
		exit;
	}else{
		echo "Saved\n";
	}

}