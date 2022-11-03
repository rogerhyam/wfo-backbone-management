<?php

// imports the basionyms

set_time_limit(0);

$table = $_GET['table'];

$offset = @$_GET['offset'];
if(!$offset) $offset = 0;

$page_size = 300;

if($offset == 0){
    $_SESSION['basionyms_processed'] = 0;
    $_SESSION['basionyms_updated'] = 0;
    $_SESSION['basionyms_issues'] = array();
}

$sql = "SELECT `rhakhis_wfo`, `rhakhis_basionym` FROM `rhakhis_bulk`.`wfo_wcvp_fabaceae_2022_2_0_3_export` WHERE length(`rhakhis_basionym`) = 14 AND length(`rhakhis_wfo`) = 14 ORDER BY rhakhis_pk LIMIT $page_size OFFSET $offset;";
//echo $sql;

$response = $mysqli->query($sql);
if($mysqli->error){
    echo $mysqli->error;
    echo $sql;
    exit;
}

echo "<h2>Importing Basionyms</h2>";

echo "<p><strong>Processed: </strong>". number_format($_SESSION['basionyms_processed'], 0) . "</p>";
echo "<p><strong>Issues: </strong>". number_format(count($_SESSION['basionyms_issues']), 0) . "</p>";
echo "<p><strong>Updated: </strong>". number_format($_SESSION['basionyms_updated'], 0) . "</p>";


if($response->num_rows){
    echo "<p>Working ...</p>";

    while($row = $response->fetch_assoc()){

        $name = Name::getName($row['rhakhis_wfo']);
        $new_basionym = Name::getName($row['rhakhis_basionym']);
        $old_basionym = $name->getBasionym();

        if($new_basionym != $old_basionym){


            if($new_basionym->getBasionym()){
                // we can't chain basionyms
                $_SESSION['basionyms_issues'][$name->getPrescribedWfoId()] = "Chaining not allowed! 
                  Trying to set  {$new_basionym->getPrescribedWfoId()} : {$new_basionym->getFullNameString()} as basionym of
                  as basionym of {$name->getPrescribedWfoId()} : {$name->getFullNameString()} 
                  but {$new_basionym->getPrescribedWfoId()} : {$new_basionym->getFullNameString()}
                  has a basionym of its own, namely {$new_basionym->getBasionym()->getPrescribedWfoId()} : {$new_basionym->getBasionym()->getFullNameString()}.";
            }else{
                $name->setBasionym($new_basionym);
                $name->save();
                $_SESSION['basionyms_updated']++;
            }


        }

        $_SESSION['basionyms_processed']++;

    }

    // call for the next page
    $next_offset = $offset + $page_size;
    $uri = "index.php?action=taxonomy_basionyms&table=$table&offset=$next_offset";
    echo "<script>window.location = \"$uri\"</script>";


}else{
    echo "<p>Finished</p>";
    echo "<p><a href=\"index.php?action=view&phase=taxonomy&task=taxonomy_basionyms\">Go to basionyms page.</a></p>";

    foreach($_SESSION['basionyms_issues'] as $wfo => $message){
        echo "<p><strong>$wfo:</strong> $message</p>";
    }
}


