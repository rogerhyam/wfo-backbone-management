<!doctype html>
<?php

    require_once('config.php');
    require_once('include/functions.php');

    $table = @$_GET['table'];
    $phase = @$_GET['phase'];
    if(!$phase) $phase = 'intro';

    // we use paging in all parts
    $page = @$_GET['page'];
    if(!$page) $page = 0;

    $sql = "SELECT count(*) FROM pragma_table_info('$table') where `name` = 'rhakhis_skip'";
    $response = $pdo->query($sql);
    if($response->fetchColumn() < 1){
        $pdo->exec("ALTER TABLE $table ADD COLUMN `rhakhis_skip` INTEGER");
        echo "<p>Added rhakhis_skip column to $table</p>";
    }


?>
<html>
<head>
    <title>Rhakhis Local</title>
    <style>
        body{
            font-family: sans-serif;
        }
    </style>
</head>
<body>

<form style="display: 'inline'">
    <div>

            <strong>Rhakhis Local: </strong>
            <select name="table" onchange="this.form.submit()">
                <?php

                    $response = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
                    while($row = $response->fetch(PDO::FETCH_ASSOC)){

                        // don't render the names table
                        if($row['name'] == 'names') continue;

                        // if we don't have a table set then pick the first one we come across
                        // we must always have a table selected
                        if(!$table) $table = $row['name'];

                        if($table == $row['name']) $selected = 'selected';
                        else $selected = '';

                        // give them a choice
                        echo "<option value=\"{$row['name']}\" $selected >{$row['name']}</option>";

                    }
                ?>
            </select>
            |
            <a href="index.php?phase=intro&table=<?php echo $table ?>">Introduction<a>
            |
            <a href="index.php?phase=matching&table=<?php echo $table ?>">Matching<a>
            |
            <a href="index.php?phase=matching&table=<?php echo $table ?>">Nomenclature<a>
            |
            <a href="index.php?phase=matching&table=<?php echo $table ?>">Taxonomy<a>
            |
            <a href="index.php?phase=update_cache&table=<?php echo $table ?>">Update Name Cache<a>
            |
            <a href="actions.php?action=clear_skips&table=<?php echo $table ?>">Clear Skips<a>


    </div>
</form>
<hr/>
<?php include("include/$phase.php") ?>

</body>
</html>


