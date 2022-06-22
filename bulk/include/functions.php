<?php

/**
 * Renders a name from a row in the names table.
 * 
 */
function render_name($row){

    echo "<span>";
    echo "<strong>";
    echo $row['id'];
    echo ":</strong>&nbsp;";
    echo $row['name'];
    echo "&nbsp;";
    echo $row['authors'];
    echo "&nbsp;[";
    echo $row['rank'];
    echo ":";
    echo $row['status'];
    echo "]</span>";

}

function render_name_set_link($name_id, $rhakhis_pk, $column){

    $name = Name::getName($name_id);

    $params = $_GET;
    $params['calling_action'] = $params['action'];
    $params['action'] = 'set_rhakhis_value';
    $params['rhakhis_pk'] = $rhakhis_pk;
    $params['rhakhis_column'] = $column;

    // the value will be extracted from the name object
    switch ($column) {
        case 'rhakhis_wfo':
            $params['rhakhis_value'] = $name->getPrescribedWfoId();
            break;
        default:
            $params['rhakhis_value'] = "FIXME in functions! - $column";
            break;
    }    

    echo $name->getPrescribedWfoId();
    echo ": ";

    $uri = "index.php?" . http_build_query($params);
    echo "<a href=\"$uri\">";
    echo $name->getFullNameString();
    echo "</a>";

    $uri = "https://list.worldfloraonline.org/rhakhis/ui/index.html#" . $name->getPrescribedWfoId();
    echo " Research: <a target=\"rhakhis\" href=\"$uri\">Rhakhis</a>";

    $uri = "http://www.worldfloraonline.org/taxon/" . $name->getPrescribedWfoId();
    echo " | <a target=\"wfo_main\" href=\"$uri\">Main Site</a>";

}

function render_column_options($table, $selected_col){

    global $mysqli; 

    $response = $mysqli->query("DESCRIBE `rhakhis_bulk`.`$table`");
    $cols = $response->fetch_all(MYSQLI_ASSOC);

    print_r($cols);
    foreach($cols as $col){
        echo "<option value=\"{$col['Field']}\">{$col['Field']}</option>";
    }

}