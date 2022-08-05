<?php
    $table = $_SESSION['selected_table'];

    echo "<h3>$table</h3>";

    if(!@$_GET['search_field'] || !@$_GET['search_value']){

        $offset = @$_GET['offset'];
        if(!$offset) $offset = 0;
        if($offset < 0) $offset = 0;

        $sql = "SELECT * FROM `rhakhis_bulk`.`$table` LIMIT 1 OFFSET $offset";
        
        $response = $mysqli->query($sql);
        $row = $response->fetch_assoc();

        $params = $_GET;
        $params['offset'] = $offset -1;

        $prev_uri = "index.php?" . http_build_query($params);
        $prev_link = "<td><a href=\"$prev_uri\">&lt; Previous</a></td>";

        $params['offset'] = $offset +1;
        $next_uri = "index.php?" . http_build_query($params);
        $next_link = "<td style=\"text-align: right;\"><a href=\"$next_uri\">Next &gt;</a></td>";
    
    }else{

        // we are showing the results of a search
        $field = $_GET['search_field'];
        $value = $_GET['search_value'];
        $offset = 0;
        if(@$_GET['search_offset']) $offset = $_GET['search_offset'];

        $sql = "SELECT count(*) as n FROM `rhakhis_bulk`.`$table` as t WHERE t.`$field` LIKE '$value'";
        $response = $mysqli->query($sql);
        if($mysqli->error){
            echo $mysqli->error;
            echo $sql;
        }
        $row = $response->fetch_assoc();
        echo "<p>Rows found: " . number_format($row['n'], 0) . "</p>";
        $response->close();

        $sql = "SELECT * FROM `rhakhis_bulk`.`$table` as t WHERE t.`$field` LIKE '$value' LIMIT 1 OFFSET $offset;"; 
        $response = $mysqli->query($sql);
        if($mysqli->error){
            echo $mysqli->error;
            echo $sql;
        }
        $row = $response->fetch_assoc();
        $response->close();

        $params = $_GET;
        $params['search_offset'] = ($offset -1);

        $prev_uri = "index.php?" . http_build_query($params);
        $prev_link = "<td><a href=\"$prev_uri\">&lt; Previous</a></td>";

        $params['search_offset'] = ($offset +1);
        $next_uri = "index.php?" . http_build_query($params);
        $next_link = "<td style=\"text-align: right;\"><a href=\"$next_uri\">Next &gt;</a></td>";

    }

    echo "<p><form>";
    echo "<input type=\"hidden\" name=\"action\" value=\"view\" />";
    echo "<input type=\"hidden\" name=\"phase\" value=\"tables\" />";
    echo "<input type=\"hidden\" name=\"task\" value=\"tables_peek\" />";

    echo "Field: <select name=\"search_field\">";
    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
    $cols = $response->fetch_all(MYSQLI_ASSOC);
    foreach($cols as $col){
        $selected = $col['Field'] == @$_GET['search_field'] ? 'selected' : '';
        echo "<option value=\"{$col['Field']}\" $selected>{$col['Field']}</option>";
    }
    echo "<select>";
    $val = @$_GET['search_value'];
    echo " Value: <input type=\"text\" size=\"60\" name=\"search_value\" value=\"$val\" placeholder=\"You can use the % wildcard.\"/>";
    echo " <input type=\"submit\" />";
    echo "</form></p>";

    echo '<table style="width: 800px">';
    echo "<tr>$prev_link $next_link </tr>";
    if($row){
        foreach($row as $col => $val){
            echo "<tr><th style=\"color: gray; text-align: right;\">$col:</th><td>$val</td></tr>";
        }
    }
    echo "<tr>$prev_link $next_link </tr>";
    echo '</table>';




     
