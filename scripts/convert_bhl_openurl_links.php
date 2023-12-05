<?php

require_once("../config.php");

/*

This is a run once to clean out the openurl links (333k of them)

SELECT 
	REGEXP_SUBSTR(link_uri, 'http://www.biodiversitylibrary.org/(page|bibliography)/[0-9]+' ), link_uri,thumbnail_uri 
# count(*)
FROM 
	promethius.references 
WHERE 
	kind = 'literature' 
and 
	REGEXP_SUBSTR(link_uri, 'http://www.biodiversitylibrary.org/page/[0-9]+' ) is  null
and 
	link_uri like "http://www.biodiversitylibrary.org/openurl%" 



*/


echo "\nConverting BHL links to page links\n\n";

$sql =  