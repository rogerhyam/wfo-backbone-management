<?php

require_once("../config.php");

// get a random row from the BHL mapping table
$response = $mysqli->query("SELECT * FROM `rhakhis_bulk`.`bhl_mapping` where bhl_page_id is not null AND top_copy_b = 't' ORDER BY RAND() LIMIT 1;");
$row = $response->fetch_assoc();
$response->close();

$bhl_page_uri = "https://www.biodiversitylibrary.org/page/" . $row['bhl_page_id'];
$bhl_thumbnail = "https://www.biodiversitylibrary.org/pagethumb/" . $row['bhl_page_id'];

?>
<!doctype html>
<html>
    <head>
        <title>BHL Reference Checking</title>
    </head>
<body>
    <h1><?php echo $row['name_full'] ?></h1>
    <p><?php echo $row['micro_citation'] ?></p>
<a href="<?php echo $bhl_page_uri?>" target="bhl_page" >
    <img src="<?php echo $bhl_thumbnail ?>" />
</a>
<form action="bhl_check.php" method="GET">
    <input type="hidden" name="row_id" value="<?php echo $row['id'] ?>" />
    <input type="text" name="user" value="<?php @$_GET['user'] ?>" placeholder="Your name here." />
    <input type="submit" name="judgement" value="Good" />
    <input type="submit" name="judgement" value="Bad" />



</form>

</body>
</html>
