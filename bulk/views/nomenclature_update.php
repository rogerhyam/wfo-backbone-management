<h2>Nomenclature</h2>
<p>This is where we selectively import nomenclatural fields from the table to Rhakhis.</p>

<!--

<form action="" method="" >

<table>
<tr>
    <td colspan="2">&nbsp;</td>
    <th colspan="3" style="text-align: center;">What happens to value in Rhakhis</th>

</tr>
<tr>
    <td>&nbsp;</td>
    <th style="text-align: center;" >Data Column</th>
    <th style="text-align: center;" >Add</th>
    <th style="text-align: center;" >Overwrite</th>
    <th style="text-align: center;" >Delete</th>
</tr>
<tr>
    <th style="text-align: right;">Rank:</th>
    <td><?php column_select('rank') ?></td>
    <td>n.a.</td>
    <td><?php options_radio('rank', 'overwrite') ?></td>
    <td>n.a.</td>
</tr>
<tr>
    <th style="text-align: right;">Authors String:</th>
    <td><?php column_select('authors') ?></td>
    <td><?php options_radio('authors', 'add') ?></td>
    <td><?php options_radio('authors', 'overwrite') ?></td>
    <td><?php options_radio('authors', 'delete') ?></td>
</tr>
<tr>
    <th style="text-align: right;">Publication String:</th>
    <td><?php column_select('publication') ?></td>
    <td><?php options_radio('publication', 'add') ?></td>
    <td><?php options_radio('publication', 'overwrite') ?></td>
    <td><?php options_radio('publication', 'delete') ?></td>
</tr>

<tr>
    <th style="text-align: right;">Year:</th>
    <td><?php column_select('year') ?></td>
    <td><?php options_radio('year', 'add') ?></td>
    <td><?php options_radio('year', 'overwrite') ?></td>
    <td><?php options_radio('year', 'delete') ?></td>
</tr>


<tr>
    <th style="text-align: right;">Status:</th>
    <td><?php column_select('status') ?></td>
    <td><?php options_radio('status', 'add') ?></td>
    <td><?php options_radio('status', 'overwrite') ?></td>
    <td><?php options_radio('status', 'delete') ?></td>
</tr>


<tr>
    <th style="text-align: right;">Comment:</th>
    <td><?php column_select('comment') ?></td>
    <td><?php options_radio('comment', 'add') ?></td>
    <td><?php options_radio('comment', 'overwrite') ?></td>
    <td><?php options_radio('comment', 'delete') ?></td>
</tr>

</table>


</form>

<hr/>

<p>Understanding the options for updating Rhakhis.</p>

<table>
<tr>
    <th>Data Value</th>
    <th>Rhakhis Value</th>
    <th>What might happen</th>
</tr>

<tr>
    <td>empty</td>
    <td>empty</td>
    <td>Nothing to do</td>
</tr>

<tr>
    <td>empty</td>
    <td>something</td>
    <td>Could <strong style="color: blue;">delete</strong> or keep Rhakhis value.</td>
</tr>

<tr>
    <td>something</td>
    <td>empty</td>
    <td>Could <strong style="color: blue;">add</strong> value to Rhakhis</td>
</tr>

<tr>
    <td>something</td>
    <td>same thing</td>
    <td>Nothing to do</td>
</tr>

<tr>
    <td>something</td>
    <td>different thing</td>
    <td>Could <strong style="color: blue;">overwrite</strong> Rhakhis</td>
</tr>

</table>
-->

<?php

function column_select($rhakhis_field){

    echo "<select>";
    echo "<option>~ Ignore ~</option>";
    echo "</select>";

}


function options_radio($rhakhis_field, $action){

    echo "<select>";
    echo "<option>Do nothing</option>";
    echo "<option>Automatically</option>";
    echo "<option>Ask first</option>";
    echo "</select>";



}


?>