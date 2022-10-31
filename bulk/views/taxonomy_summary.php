
<div style="width: 1000px">
<p>This phase always follows name matching and import of taxonomic data (apart from ranks which are reported on here and statuses which may be updated).</p>

<h3>Mapping:</h3> 
<p>
    Here we fill in the rhakhis_parent, rhakhis_accepted and rhakhis_basionym columns in the table. 
    Note these are expressing the taxonomy in the table in terms of WFO IDs. 
    They are nothing to do with the classification in Rhakhis at this point but the map to the NAMES in Rhakhis.
</p>


<table>
    <tr>
        <th colspan="2" style="text-align: center;"><?php echo $table ?></th>
    </tr>

<?php

    // basic stats
    $response = $mysqli->query("SELECT count(*) as 'Total Rows', count(rhakhis_wfo) as 'Mapped', count(rhakhis_parent) as 'Have Parent', count(rhakhis_accepted) as 'Have Accepted', count(rhakhis_basionym) as 'Have Basionym'  FROM `rhakhis_bulk`.`$table`");
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $stats = $rows[0];
    $response->close();

    foreach($stats as $stat => $val){
        $val = number_format($val, 0);
        echo "<tr><th style=\"text-align: right;\">$stat</th><td>$val</td></tr>";
    }

    // double wfo ids!
    $response = $mysqli->query("SELECT rhakhis_wfo, count(*) as n FROM `rhakhis_bulk`.`$table` group by rhakhis_wfo having count(*) > 1 order by n desc;");
    $repeats = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();

    if(count($repeats)){
        echo "<tr><th style=\"text-align: center;\" colspan=\"2\">Repeating rhakhis_wfo values</th></tr>";
        foreach($repeats as $repeat){
            $val = $repeat['rhakhis_wfo'] === null ? 'null' : $repeat['rhakhis_wfo'];
            echo "<tr><th style=\"text-align: right;\">$val</th><td>{$repeat['n']}</td></tr>";
        }
    }else{
        echo "<tr><th style=\"text-align: right;\">Repeat&nbsp;rhakhis_wfo&nbsp;values</th><td>0</td></tr>";
    }

?>

</table>



<h3>Browse</h3>
<p>
    Once we have populated the tree structure that is in the table in a uniform way we can browse it using this tool.
    The taxonomy in this browser comes from the data table but the name information comes from Rhakhis.
    The nomenclatural data in the table and in Rhakhis should be equivalents by now anyhow.
</p>
<p>
    There are links in the browser to launch an Impact report generator tab and to the Importer itself.
</p>

<h3>Impact Report</h3>
<p>
    This takes a WFO ID for a taxon that is both in Rhakhis and in the data table.
    It then runs through the taxonomy in Rhakhis and the data table and creates a report of 
    what the effect of replacing the Rhakhis taxonomy with that in the data table would be.
    More details about the report on the Impact tab.
</p>

<strong>Import</strong>
<p>
    If there are no major issues in the impact report then you can click the link in the browser
    to import the taxonomy from the table into Rhakhis. How this works is described on the import page.
</p>

</div>