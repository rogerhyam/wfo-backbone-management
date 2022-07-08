
<h2>Files</h2>
<p>This is where you upload and manage CSV and Darwin Core Archive files.</p>
<table>
    <tr>
        <th>File name</th>
        <th>Size</th>
        <th>Actions</th>        
    <tr>
<?php

    $files = glob('../bulk/csv/*.{csv,zip,gz}', GLOB_BRACE);

    foreach($files as $file){

        $parts = pathinfo($file);

        echo "<tr>";
        echo "<td><a href=\"bulk_download.php?file_name={$parts['basename']}\">{$parts['basename']}</a></td>";
        echo "<td>" . human_filesize(filesize($file)) . '</td>';


        if($parts['extension'] == 'csv'){
            $table_name = str_replace('.', '_', strtolower($parts['filename']));
            echo "<form action=\"index.php\" method=\"GET\">";
            echo "<td>";
            echo "<input type=\"hidden\" name=\"action\" value=\"db_create_table\"/>";
            echo "<input type=\"hidden\" name=\"file_in\" value=\"{$parts['basename']}\"/>";
            echo "<input type=\"submit\" value=\"Import to DB table:\" />";
            echo "<input name=\"table_name\" size=\"60\"type=\"text\" value=\"$table_name\">";
            echo " | <a style=\"color: red\" href=\"index.php?action=file_delete&file_name={$parts['basename']}\">Delete</a>";
            echo "</td>";
            echo "</form>";
        }else{
            echo "<form action=\"index.php\" method=\"GET\">";
            echo "<td>";
            echo "<input type=\"hidden\" name=\"action\" value=\"file_extract\"/>";
            echo "<input type=\"hidden\" name=\"file_in\" value=\"{$parts['basename']}\"/>";
            echo "<input type=\"submit\" value=\"Extract to CSV file:\" />";
            echo "</input><input name=\"file_new_name\" size=\"60\"type=\"text\" value=\"{$parts['filename']}.csv\">";
            echo " | <a style=\"color: red\" href=\"index.php?action=file_delete&file_name={$parts['basename']}\">Delete</a>";
            echo "</td>";
            echo "</form>";
        }

        echo "</tr>";
    }

?>
    <tr>
        <td colspan="3" style="text-align: right" >
            <form action="index.php" method="post" enctype="multipart/form-data">
                Select file to upload:
                <input type="hidden" name="action" value="file_upload" />
                <input type="file" name="incoming_file" id="incoming_file" accept=".csv,.zip" >
                <input type="submit" value="Upload File" name="submit">
            </form>
        </td>
    </tr>
</table>

<div style="max-width: 800px">
<h3>Darwin Core Archive Files</h3>
<p>If you upload a DwCA file you will be given the option to extract it. The extractor will read the meta.xml file in the archive, find the taxonomy table and write it out as a CSV file, using commas in the standard way, and insert a header row based on the contents of the meta.xml file.</p>
<p>DwCA files can be hairy! The extractor will do its best but can't convert encodings or handle errors in things like string escaping.</p>

<h3>CSV Files</h3>
<p>You can upload a CSV file provided it uses the standard comma separated convention with string escaping using " and UTF-8 encoded. 
    This is the normal way Excel (and many other applications) save-as option produces CSV. 
    Be cautious that Excel will export in this format but if you double click on a CSV file it may open it in the WRONG encoding and if you then export it as UTF-8 it will mess up accented characters.
    How many Microsoft engineers does it take to change a lightbulb? None. They simply redefine darkness as an industry standard!
</p>
<p>The CSV file <strong>MUST</strong> have a header row with simple column names similar to those used in DwC A.</p>

<h3>Zipped CSV Files</h3>
<p>You should zip up larger CSV files. They can then be unzipped on the server. The extractor can tell they are not DwCA files because they don't contain meta.xml files but only a single CSV file.</p>


<h3>Other Points</h3>

<p>Table names should be just lowercase letters and underscores.</p>
<p>Files and tables will be overwritten without warning by a new file or table with the same name.</p>
<p>Patience is a virtue with larger files. Just let the page keep loading timeouts are longer than with regular web pages.</p>
<p><strong>post_max_size: </strong><?php echo ini_get('post_max_size') ?></p>
<p><strong>upload_max_filesize: </strong><?php echo ini_get('upload_max_filesize') ?></p>

</div>

<?php

    function human_filesize($bytes, $decimals = 2) {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

?>