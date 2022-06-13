<?php

/**
 * Renders a name from a row in the names table.
 * 
 */
function render_name($row){

    echo "<span>";
    echo "<strong>";
    echo $row['WFO_ID'];
    echo ":</strong>&nbsp;";
    echo $row['scientificName'];
    echo "&nbsp;";
    echo $row['scientificNameAuthorship'];
    echo "&nbsp;[";
    echo $row['taxonrank'];
    echo ":";
    echo $row['nomenclaturalStatus'];
    echo ":";
    echo $row['taxonomicStatus'];
    echo "]</span>";

}

function render_name_set_wfo_link($name_row, $table, $row_id, $page){

    $uri = "actions.php?action=set_wfo&table=$table&row_id=$row_id&page=$page&wfo=" . $name_row['WFO_ID'];
    echo "<a href=\"$uri\">";
    render_name($name_row);
    echo "</a>";

}