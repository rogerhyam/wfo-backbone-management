<!doctype html>
<?php

    $table = @$_GET['table'];
    $phase = @$_GET['phase'];
    if(!$phase) $phase = 'intro';

    // we use paging in all parts
    $page = @$_GET['page'];
    if(!$page) $page = 0;

    /*

    $sql = "SELECT count(*) FROM pragma_table_info('$table') where `name` = 'rhakhis_skip'";
    $response = $pdo->query($sql);
    if($response->fetchColumn() < 1){
        $pdo->exec("ALTER TABLE $table ADD COLUMN `rhakhis_skip` INTEGER");
        echo "<p>Added rhakhis_skip column to $table</p>";
    }
    
    */

?>
<html>
<head>
    <title>Rhakhis Bulk</title>
    <style>
        body{
            font-family: sans-serif;
        }
        table, td, th{
            text-align:left;
            border: 1px solid black;
            border-collapse: collapse;
            padding: 0.5em;
        }
    </style>
</head>
<body>

<?php
    // display sandbox warning text
    if( @$_SERVER['SERVER_NAME'] == 'rhakhis.rbge.info'){
?>
<p style="background-color: yellow; padding: 0.3em; border: solid 1px orange; margin: 0px;">🏖️ <strong style="color: red;">This is the sandbox server.</strong> Data will be overwritten nightly.</p>
<hr/>
<?php
    } // end sandbox test
?>

<form style="display: 'inline'">
    <div>

            <strong>Rhakhis Bulk: </strong>
            <a href="index.php?action=view&phase=intro">Introduction<a>
            |
            <a href="index.php?action=view&phase=csv">Files<a>
            |
            <a href="index.php?action=view&phase=kew">Kew<a>
            |
            <a href="index.php?action=view&phase=tables">Tables<a>
            |
            <a href="index.php?action=view&phase=matching">Matching<a>
            |
            <a href="index.php?action=view&phase=created">Created<a>
            |
            <a href="index.php?action=view&phase=linking">Linking<a>
            |
            <a href="index.php?action=view&phase=nomenclature">Nomenclature<a>
            |
            <a href="index.php?action=view&phase=taxonomy">Taxonomy<a>
            
            <span style="border: solid 1px black; padding: 0.2em">
            <?php
                echo @$_SESSION['selected_table'] ? "Active Table: " . $_SESSION['selected_table'] : "No Table Selected";
            ?>
            </span>
            


    </div>
</form>
<hr/>
<?php include("../bulk/views/$phase.php") ?>

</body>
</html>


