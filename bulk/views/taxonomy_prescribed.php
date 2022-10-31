<h3>Prescribed WFO ID Check</h3>

<div style="max-width: 60em">

<p style="color: green;">Doesn't change data in Rhakhis</p>

<p>If names have been deduplicated at some point when the mapping of the rhakhis_* fields were being populated for this table it is possible to get in a 
    right fangle. For example, the row might have the prescribed WFO ID in rhakhis_wfo because it was matched on that but a child name might use the deduplicated ID to refer to it.
    This makes it impossible to do joins an integrity checks on the table.
</p>

<p>This tool will run through the table and check that each WFO ID used is the prescribed (i.e. the main one) for the name concerned. <strong>You probably won't need to run it often, if ever, as the data gets cleaner!</strong></p>

<form method="GET" action="index.php">
        <input type="hidden" name="action" value="taxonomy_prescribed" />
        <input type="hidden" name="table" value="<?php echo $table ?>" />
        <input type="hidden" name="offset" value="0" />
        <input type="submit" value="Check WFO IDs" />
</form>

</div>
