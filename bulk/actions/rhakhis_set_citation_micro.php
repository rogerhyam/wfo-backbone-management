<p>Processing ... </p>

<?php

    $name = Name::getName($_GET['wfo']);
    $response = $name->updatePublication($_GET['published_in'], $_GET['published_year'], null);

    if($response->status){

        // we add this to the skip list or we will stop here again

        $table = $_GET['table'];
        $rhakhis_pk = $_GET['rhakhis_pk'];
        $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_skip` = 1 WHERE `rhakhis_pk` = $rhakhis_pk;");

        $uri = 'index.php?' . $_GET['search_query'];
        header("Location: $uri");
    }else{
        echo "<h2>Show this to Roger</h2>";
        echo "<pre>";
        print_r($response);
        print_r($_GET);
        print_r($name);
        echo "</pre>";
    }
    

?>
