<h2>Deduplication</h2>

<p style="max-width: 800px">Here we list the duplicate name entries. Duplicates being names with the same name-parts, rank and author string.
    This is pretty crude. Pages of 1,000 are presented in descending order of number of duplicates and then name. There should be very few if any of these in the database!</p>


<?php

    if(@$_GET['duplicate']){
        render_deduplication_form($_GET['duplicate']);
    }else{
        render_duplicates_list();
    }


function render_deduplication_form($duplicate){

    global $mysqli;

    $duplicate_sql = trim($mysqli->real_escape_string($duplicate));
    $sql = "SELECT id FROM `names` WHERE `deduplication` = '$duplicate_sql';";
    $response = $mysqli->query($sql);

    echo "<h3>Merging Form</h3>";

    echo '<form method="GET" action="index.php">';
    echo '<input type="hidden" name="action" value="deduplicate">';
    echo '<input type="hidden" name="offset" value="'. @$_GET['offset'] .'">';
    echo "<table>";
    echo   "<tr><th>ID</th><th>WFO</th><th>Name</th><th>Status</th><th>Published In</th><th>Target</th><th>Destiny</th></tr>";

    $checked = 'checked';
    while($row = $response->fetch_assoc()){
        

        $name = Name::getName($row['id']);
        
        echo "<tr>";
        echo "<td>". $name->getId() . "</td>";
        echo "<td> <a target=\"rhakhis\" href=\"". get_rhakhis_uri($name->getPrescribedWfoId()) . "\">" . $name->getPrescribedWfoId() . "</a></td>";
        echo "<td>".  $name->getFullNameString() . "</td>";
        echo "<td>".  $name->getStatus() . "</td>";
        echo "<td>".  $name->getCitationMicro() . "</td>";
        echo "<td><input type=\"radio\" name=\"target_wfo\" value=\"". $name->getPrescribedWfoId() ."\"  $checked /></td>";
        echo "<td><button name=\"remove_wfo\" type=\"submit\" value=\"". $name->getPrescribedWfoId() ."\" >Deduplicate</button></td>";
        echo "</tr>";

        // switch the flag as we have targeted the first one
        if($checked) $checked = '';
    }

    echo "<table>";
    echo "</form>";
    echo "<p>Clicking remove on the target will give an error.</p>";

}

function render_duplicates_list(){

    global $mysqli;

      echo "<h3>Duplicates List</h3>";

    echo "<table>";
    echo   "<tr><th>Name Parts</th> <th>Rank</th><th>Authors</th><th># copies</th></tr>";

    $offset = @$_GET['offset'];
    if(!$offset) $offset = 0;

    $sql = "SELECT deduplication, count(*) as n
	    from `names` 
        group by deduplication 
        having count(*) > 1 
	    order by count(*) desc, deduplication
        limit 1000 OFFSET $offset";

    $response = $mysqli->query($sql);

    while($row = $response->fetch_assoc()){

        $parts = explode('~', $row['deduplication']);

        echo "<tr>";
        $dupe = urlencode($row['deduplication']);
        echo "<td><a href=\"?action=view&phase=deduplication&duplicate=$dupe&offset=$offset\">{$parts[0]}</a></td>";
        echo "<td>{$parts[1]}</td>";
        echo "<td>{$parts[2]}</td>";
        echo "<td>{$row['n']}</td>";
        echo "</tr>";
    }

    echo "</table>";

    // next previous links
    echo "<p>";

    if($offset >= 1000){
        $new_offset = $offset - 1000;
        if($new_offset < 0) $new_offset = 0;
        echo "<a href=\"?action=view&phase=deduplication&offset=$new_offset\">&lt; PREVIOUS</a>";
    }else{
        echo "&lt; PREVIOUS";
    }

    echo " | ";

    if($response->num_rows == 1000){
        $new_offset = $offset + 1000;
        echo "<a href=\"?action=view&phase=deduplication&offset=$new_offset\">NEXT &gt;</a>";
    }else{
        echo "NEXT &gt;";
    }

    echo "</p>";

} // render_duplicates_list

?>
