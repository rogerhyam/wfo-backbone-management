<h2>Import Basionyms</h2>

<p>This tool will import the basionyms mapped in the rhakhis_basionym field.</p>
<p>It works through the whole table.</p>
<p>The basionyms were mapped under the Taxonomy > Mapping tab</p>

<form action="index.php" method="GET">
    <input type="hidden" name="action" value="taxonomy_basionyms" />
    <input type="hidden" name="table" value="<?php echo $table ?>" />
    <input type="submit"  value="Import Basionyms" onclick="this.disabled = true; this.form.submit(); "/>
</form>

