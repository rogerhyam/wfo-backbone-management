<?php
    $now = new DateTime();
    $today = $now->format('Y-m-d');
    $this_year = $now->format('Y');
    $last_month = $now->modify('-1 month')->format('Y-m-d');
    $file_name = "ipni_export_$today.csv";

?>

<h2>IPNI</h2>
<p>Use the forms below to create export files under the Files tab that can be imported as tables, matched etc.</p>

<hr/>
<h3>Activity in last month</h3>

<table>
    <tr>
        <th style="text-align: right" >Sync log:</th>
<?php
        $response = $mysqli->query("SELECT * FROM kew.ipni_log ORDER BY `created` DESC LIMIT 1");
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        $response->close();
        echo '<td colspan="2">';
        echo $rows[0]['created'];
        echo ' - ';
        echo $rows[0]['message'];
        echo '</td>';
?>
    </tr>
     <tr>
        <th style="text-align: right" >Lastest mod date:</th>
        <td>
<?php
    $response = $mysqli->query("SELECT date_last_modified_date from kew.ipni ORDER BY date_last_modified_date DESC LIMIT 1 ;");
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
    if(count($rows) > 0) echo $rows[0]['date_last_modified_date'];
    else echo "none";
?>
        </td>
        <td>The newest modification date found in the data dump.</td>
    </tr>
    <tr>
        <th style="text-align: right" >New names:</th>
        <td>
<?php
    $two_years_back = $now->modify('-2 years')->format('Y');
    $response = $mysqli->query("SELECT count(*) as n from kew.ipni where publication_year_i > $two_years_back and date_created_date > now() - interval 1 month;");
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
    echo number_format($rows[0]['n'], 0);
?>
        </td>
        <td>New records for names published in this calendar year or last year.</td>
    </tr>
    <tr>
        <th style="text-align: right" >Historic names:</th>
        <td>          
<?php
    $last_year = $now->modify('-1 years')->format('Y');
    $response = $mysqli->query("SELECT count(*) as n from kew.ipni where publication_year_i < $last_year and date_created_date > now() - interval 1 month;");
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
    echo number_format($rows[0]['n'], 0);
?>
        </td>
        <td>New records for names published two or more years ago.</td>
    </tr>
    <tr>
        <th style="text-align: right" >Records modified:</th>
        <td>
<?php
    $response = $mysqli->query("SELECT count(*) as n from kew.ipni where date_last_modified_date > now() - interval 1 month;");
    $rows = $response->fetch_all(MYSQLI_ASSOC);
    $response->close();
    echo number_format($rows[0]['n'], 0);
?>    
        </td>
        <td>Anything changed in the last month.</td>
    </tr>
</table>

<h3>Common Delta Query</h3>

<table>
<form method="GET" action="index.php">
    <input type="hidden" name="action" value="file_ipni_create" />
<tr>
    <th style="text-align: right">Record Created Between:</th>
    <td>
    <input type="text" name="created_after" value="<?php echo $last_month ?>" size="10" />
    <input type="text" name="created_before" value="<?php echo $today ?>" size="10" />
    </td>
    <td>Use dates in the format yyyy-mm-dd e.g. 1965-02-28. Leave blank to not restrict. Dates are inclusive.</td>
</tr>
<tr>
    <th style="text-align: right">Record Modified Between:</th>
    <td>
    <input type="text" name="modified_after" value="" size="10" />
    <input type="text" name="modified_before" value="" size="10" />
    </td>
    <td>Use dates in the format yyyy-mm-dd e.g. 1965-02-28. Leave blank to not restrict. Dates are inclusive.</td>
</tr>
<tr>
    <th style="text-align: right">Family:</th>
    <td>
        <input type="text" name="family" value="" size="30" />
    </td>
    <td>This uses the family_s_lower field and LIKE so you can add % to the end.</td>
</tr>
<tr>
    <th style="text-align: right">Year of Publication:</th>
    <td>
    <input type="text" name="publication_year" value="<?php echo $this_year ?>" size="10" />
    </td>
    <td> Leave blank to not restrict.</td>
</tr>
<tr>
    <th style="text-align: right">Top copy:</th>
    <td>
        <select name="top_copy" >
            <option value="t">True</option>
            <option value="f">False</option>
            <option selected="true" value="">Don't Care</option>
        </select>
    </td>
    <td>Record flagged as top copy.</td>
</tr>
<tr>
    <th style="text-align: right">Suppressed:</th>
    <td>
        <select name="suppressed" >
            <option value="t">True</option>
            <option value="f">False</option>
            <option selected="true" value="">Don't Care</option>
        </select>
    </td>
    <td>Suppressed flag</td>
</tr>
<tr>
    <th style="text-align: right;">File name:</th>
    <td><input type="text" size="50" name="file_name" placeholder="Sensible name, no spaces or punctuation." value="<?php echo $file_name ?>" /></td>
    <td>The name of the file under the files tab. If it already exists it will be overwritten.</td>
</tr>
<tr>
    <td style="text-align: right;" colspan="3"><input type="submit" value="Run Query" /></td>
</tr>
</form>

</table>


<h3>Arbitrary Query</h3>
<p>Enter the where part of the clause. "SELECT * FROM `kew`.`ipni` WHERE ..."</p>
<form method="GET" action="index.php">
    <input type="hidden" name="action" value="file_ipni_create" />
<table>
<tr>
    <td colspan="2">
        <textarea name="sql_select" cols="80" rows="10" placeholder="authors_t = 'Reich%'"></textarea>
    </td>
</tr>
<tr>
    <th style="text-align: right; width: 25%">File name:</th>
    <td><input type="text" size="50" name="file_name" placeholder="Sensible name, no spaces or punctuation." value="<?php echo $file_name ?>"/></td>
</tr>
<tr>
    <td style="text-align: right;" colspan="2"><input type="submit" value="Run Query" /></td>
</tr>
</table>
</form>
<p>
    Caution: There are many columns (90+) and most are of type TEXT and not indexed so arbitrary queries might be slow. If you need queries to run faster let me know and I may be able to add indexes.
</p>
<p>
    There is an arbitrary limit of 100,000 rows returned. This is to stop returning the whole DB. If you get 100k rows in a file you may want to refine your query. There will be no error it will just be chopped.
    This limit can be increased.
</p>

