
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
        echo "<td>" . $parts['basename'] . "</td>";
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
<p><strong>Table names</strong> should be just lowercase letters and underscores.</p>
<p><strong>Files</strong> will be overwritten if there is an existing file with the same name.</p>
<p><strong>Patience is a virtue</strong> with larger files.</p>
<p><strong>Extraction</strong> of zip files depends on their contents. If they contain a meta.xml file they are considered DwCA files and extracted to csv including adding headers if needed. If there is no meta.xml file but a single .csv file then that is extracted as-is. 
<p><strong>post_max_size: </strong><?php echo ini_get('post_max_size') ?></p>
<p><strong>upload_max_filesize: </strong><?php echo ini_get('upload_max_filesize') ?></p>


<?php

    function human_filesize($bytes, $decimals = 2) {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

?>