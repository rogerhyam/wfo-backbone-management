<?php

    // check that the table has a rhakhis_wfo column and add on if not.
    $sql = "SELECT count(*) FROM pragma_table_info('$table') where `name` = 'rhakhis_wfo'";
    $response = $pdo->query($sql);
    if($response->fetchColumn() < 1){
        $pdo->exec("ALTER TABLE $table ADD COLUMN `rhakhis_wfo` VARCHAR(15)");
        echo "Added rhakhis_wfo column to $table";
    }

    // if the action isn't set then set it to summary
    $action = @$_GET['action'];
    if(!$action) $action = 'summary';

?>
<div>
    <strong>Matching: </strong>
    <a href="index.php?phase=matching&action=summary&table=<?php echo $table ?>">Summary<a>
    |
    <a href="index.php?phase=matching&action=by_name&table=<?php echo $table ?>">By Name<a>
    |
    <a href="index.php?phase=update_cache$by_local_id&table=<?php echo $table ?>">By Local ID<a>
    |
     <a href="actions.php?action=clear_matches&table=<?php echo $table ?>">Clear Matches<a>
</div>
<hr/>

<?php include("include/matching_$action.php");


