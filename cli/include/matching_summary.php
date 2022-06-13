<h2>Summary</h2>

<?php

    $response = $pdo->query("SELECT count(*) FROM `$table`");
    $total_rows = $response->fetchColumn();

    $response = $pdo->query("SELECT count(*) FROM `$table` WHERE length(`rhakhis_wfo`) = 14");
    $matched_rows = $response->fetchColumn();

    $response = $pdo->query("SELECT count(*) FROM `$table` WHERE `rhakhis_skip` = 1");
    $skipped = $response->fetchColumn();

?>

<table style="text-align: right'">
<tr>
    <th style="text-align: right">Total Rows</th>
    <td style="text-align: right"><?php echo number_format($total_rows, 0) ?></td>
</tr>
<tr>
    <th style="text-align: right">Matched Rows</th>
    <td style="text-align: right"><?php echo number_format($matched_rows, 0) ?></td>
</tr>
<tr>
    <th style="text-align: right">Matched %</th>
    <td style="text-align: right"><?php echo   number_format($matched_rows/$total_rows * 100, 0)?></td>
</tr>
<tr>
    <th style="text-align: right">Skip Flag</th>
    <td style="text-align: right"><?php echo   number_format($skipped, 0)?></td>
</tr>

</table>

<p>During the matching phase we populate a column called 'rhakhis_wfo' with an existing WFO ID from the main Rhakhis database.</p>
<p>We do this by comparing other columns in the table with values in the names cache we have locally.</p>
<p><strong>By Name</strong> will look for the scientificName and the scientificNameAuthors columns and use these to fill in the rhakhis_wfo column.</p>
<p><strong>By WFO</strong> will look at the taxonID column and check that the WFO ID there is in the names cache (or in the main database as a duplicate) and copy it into the rhakhis_wfo</p>
<p><strong>By Local ID</strong> will look at the ??? column and see if that id is bound to a WFO ID in Rhakhis.</p>

<p>Matching will only work on rows that haven't already been matched. To rematch a row go into the table with SQLite Studio and delete out the WFO ID for it.</p>
