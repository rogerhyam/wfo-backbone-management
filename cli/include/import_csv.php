<?php

$file_path = @$_GET['file'];

if($file_path){

    $parts = pathinfo($file_path);
    $table_name = $parts['filename'];

    // lets create the sql file we are going to pass to sqlite on the command line
    $out = fopen('input_csv/import.sql', 'w');
    fwrite($out, "drop table if exists `$table_name`;");
    fwrite($out, "\n.mode csv");
    fwrite($out, "\n.import \"$file_path\" \"$table_name\" ");
    fclose($out);

    echo exec('sqlite3 data/sqlite.db ".read input_csv/import.sql"');

    echo "<p>$file_path</p>";
}else{
    // no path supplied so list those in the folder.
    echo "<h2>Import CSV File</h2>";
    echo "<p>These are the files in the input_csv directory. Put the file to be imported in that directory and name it what you want the table to be called in the database (plus a .csv ending). Keep it simple with no special characters or spaces.";
    echo '<p style="color:red">Warning: This will overwrite a table with the same name as the file.</p>';

    $files = glob('input_csv/*.csv');
    echo '<table>';
    foreach($files as $f){
        echo "<tr><td>$f</td><td><a href=\"import_csv.php?file=$f\">import</a></td></tr>";
    }
    echo "</table>";
}