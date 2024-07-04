<!doctype html>
<?php

    $table = @$_GET['table'];
    $phase = @$_GET['phase'];
    if(!$phase) $phase = 'intro';

    // we use paging in all parts
    $page = @$_GET['page'];
    if(!$page) $page = 0;

?>
<html>

<head>
    <title>Rhakhis Bulk</title>
    <style>
    body {
        font-family: sans-serif;
    }

    table,
    td,
    th {
        text-align: left;
        border: 1px solid black;
        border-collapse: collapse;
        padding: 0.5em;
    }

    tr:hover {
        background-color: lightgray;
    }
    </style>
</head>

<body>
    <?php
    if($system_message){
        echo "<p style=\"background-color: black; color:white; padding: 0.3em; border: solid 1px gray; margin: 0px;\"><strong>&nbsp;⚠️&nbsp;System Message:&nbsp;</strong>$system_message</p>";
        echo "<hr/>";
    }
?>

    <form style="display: 'inline'">
        <div>

            <strong>Rhakhis Bulk: </strong>
            <a href="index.php?action=view&phase=intro">Introduction</a>
            |
            <a href="index.php?action=view&phase=csv">Files</a>
            |
            <a href="index.php?action=view&phase=kew">Kew</a>
            |
            <a href="index.php?action=view&phase=tables">Tables</a>
            |
            <a href="index.php?action=view&phase=matching">Matching</a>
            |
            <a href="index.php?action=view&phase=created">Created</a>
            |
            <a href="index.php?action=view&phase=linking">Linking</a>
            |
            <a href="index.php?action=view&phase=references">References</a>
            |
            <a href="index.php?action=view&phase=nomenclature">Nomenclature</a>
            |
            <a href="index.php?action=view&phase=taxonomy">Taxonomy</a>
            |
            <a href="index.php?action=view&phase=duplicates">Duplicates</a>
            |
            <a href="index.php?action=view&phase=examples">Examples</a>
            |
            <a href="index.php?action=view&phase=switch_user">su</a>
            &nbsp;
            <span style="border: solid 1px black; padding: 0.2em">
                <?php
                echo @$_SESSION['selected_table'] ? "Active Table: " . $_SESSION['selected_table'] : "No Table Selected";
            ?>
            </span>



        </div>
    </form>
    <hr />
    <?php include("../bulk/views/$phase.php") ?>

</body>

</html>