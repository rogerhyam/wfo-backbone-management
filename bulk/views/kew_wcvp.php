<h3>WCVP - Summary Page</h3>
<p>Pick a family to work with.</p>
<h3>WCVP Data</h3>
<table>
    <tr>
        <th style="text-align: right" >Sync log:</th>
<?php
        $response = $mysqli->query("SELECT * FROM kew.wcvp_log ORDER BY `created` DESC LIMIT 1");
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        $response->close();
        echo '<td colspan="3">';
        echo $rows[0]['created'];
        echo ' - ';
        echo $rows[0]['message'];
        echo '</td>';
?>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <th style="text-align: center" >Current Copy</th>
    <th style="text-align: center" >Last Copy</th>
    </tr>
<?php
        // get the latest counts
        $response = $mysqli->query("
            SELECT 
            sum(if(wfo_id like 'wfo-%', 1, 0)) AS matched,
            count(*) as total
            FROM kew.wcvp ;
        ");
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        $current_stats = $rows[0];
        $response->close();

        // get the counts for the last dump
        $response = $mysqli->query("
            SELECT 
            sum(if(wfo_id like 'wfo-%', 1, 0)) AS matched,
            count(*) as total
            FROM kew.wcvp_last;
        ");
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        $last_stats = $rows[0];
        $response->close();

?>
    <tr>
        <th style="text-align: right" >Total Rows</th>
        <td style="text-align: right"><?php echo number_format($current_stats['total'], 0) ?></td>
        <td style="text-align: right"><?php echo number_format($last_stats['total'], 0) ?></td>
    </tr>

    <tr>
        <th style="text-align: right" >Names Matched</th>
        <td style="text-align: right"><?php echo number_format($current_stats['matched'], 0);?></td>
        <td style="text-align: right"><?php echo number_format($last_stats['matched'], 0) ?></td>
    </tr>
    
    <tr>
        <th style="text-align: right" >% Matched</th>
        <td style="text-align: right"><?php echo number_format( $current_stats['matched']/$current_stats['total'] * 100, 2) ?>%</td>
        <td style="text-align: right"><?php echo number_format( $last_stats['matched']/$last_stats['total'] * 100, 2) ?>%</td>
    </tr>

<?php
        // get the latest counts
        $response = $mysqli->query("SELECT count(distinct family) as n FROM kew.wcvp;");
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        $current_family_count = $rows[0]['n'];
        $response->close();

        $response = $mysqli->query("SELECT count(distinct family) as n FROM kew.wcvp_last;");
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        $last_family_count = $rows[0]['n'];
        $response->close();

?>
    <tr>
        <th style="text-align: right" >Families</th>
        <td style="text-align: right"><?php echo number_format( $current_family_count, 0) ?></td>
        <td style="text-align: right"><?php echo number_format( $last_family_count, 0) ?></td>
    </tr>

<?php
        // get the latest counts
        $response = $mysqli->query("SELECT count(distinct genus) as n FROM kew.wcvp;");
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        $current_genus_count = $rows[0]['n'];
        $response->close();

        $response = $mysqli->query("SELECT count(distinct genus) as n FROM kew.wcvp_last;");
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        $last_genus_count = $rows[0]['n'];
        $response->close();

?>
    <tr>
        <th style="text-align: right" >Genus Names</th>
        <td style="text-align: right"><?php echo number_format( $current_genus_count, 0) ?></td>
        <td style="text-align: right"><?php echo number_format( $last_genus_count, 0) ?></td>
    </tr>

</table>

<h3>Families</h3>

<table>
    <tr>
    <th>Family</th>
    <th>Names</th>
    <th>Matched</th>
    <th>Has IPNI</th>
    <th>Accepted</th>
    <th>Synonyms</th>
    <th style="text-align:center">Changed</th>
    </tr>

<?php

    $response = $mysqli->query("SELECT 
        n.family, 
        count(*) as total, 
        sum(if(n.wfo_id like 'wfo-%', 1, 0)) AS 'matched', 
        sum(if(length(n.ipni_id) > 0, 1, 0)) AS ipni,
        sum(if(n.taxon_status = 'Accepted' > 0, 1, 0)) AS accepted,
        sum(if(n.taxon_status = 'Synonym' > 0, 1, 0)) AS 'synonym',
        sum(if(n.`hash` != l.`hash` > 0, 1, 0)) AS 'changed'
        FROM kew.wcvp AS n
        LEFT JOIN kew.wcvp_last AS l ON n.plant_name_id = l.plant_name_id
        GROUP BY family ORDER BY family;");

        $rows = $response->fetch_all(MYSQLI_ASSOC);

        foreach ($rows as $row) {

            $params = $_GET;
            $params['family'] = $row['family'];
            $params['action'] = 'file_wcvp_create';
            $family_uri = 'index.php?' . http_build_query($params);
            $percentage = number_format(($row['changed']/$row['total'])*100, 2);
            $changed = number_format($row['changed'], 0);
            $color = 'black';
            if($percentage > 33) $color = 'orange';
            if($percentage > 50) $color = 'red';

            echo "<tr>";
            echo "<td><a href=\"$family_uri\">{$row['family']}</a></td>";
            echo '<td  style="text-align: right" >'. number_format($row['total'], 0) . "</td>";
            echo '<td  style="text-align: right" >'. number_format($row['matched'], 0) . "</td>";
            echo '<td  style="text-align: right" >'. number_format($row['ipni'], 0) . "</td>";
            echo '<td  style="text-align: right" >'. number_format($row['accepted'], 0) . "</td>";
            echo '<td  style="text-align: right" >'. number_format($row['synonym'], 0) . "</td>";
            echo "<td  style=\"text-align: right; color: $color; \" >$changed ($percentage%)</td>";
            echo "</tr>";
        }


?>



