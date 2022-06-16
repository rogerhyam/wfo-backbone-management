<div>
<h2>Introduction</h2>

<p>
    This tool is used to reconcile data in a flat table (typically Darwin Core Archive format) with the data in Rhakhis.
    It does not provide all the functionality you might need but only that required to do the matching of names and merging of nomenclatural and taxonomic data. 
    You will still need to manipulated data to get it into a table in SQLite with the correct column headings.
</p>

<p><strong>Matching:</strong> This is the process of adding and populating a column on the table called 'rhakhis_wfo' that contains the WFO ID of the name record in Rhakhis main.</p>

<p><strong>Nomenclature:</strong> This involves comparing the values in the table to those in the name records in Rhakhis based on the WFO ID in the rhakhis_wfo column. You can choose to update Rhakhis with the values in the table.</p>

<p><strong>Taxonomy:</strong> This validates the taxonomy in the table and compares it to that in Rhakhis. It then offers the ability to replace the Rhakhis taxonomy with the one found in the file.

<p><strong>Name Cache:</strong> It is really inefficient to do an API call for every comparison during name matching and so a minimal copy of the names data is downloaded and used locally. The is generated on the server each night and  updated here by clicking the link above. </p>

<p><strong>Skips:</strong> If a row is skipped during matching then a flag is set on that row and it isn't then revisited. You can use the "clear skips" to clear all these flags in the table. If you want to clear individual ones you have to do that with SQLite Studio. </p>

<h3>Application Structure</h3>
<p>You need to interact with 3 files within the application folder to make things work.</p>
<ul>
    <li><strong style="color: green" >config.php</strong> - You need to edit this to add your API key. You may need to re-edit it after the code is updated so keep a note of your key.</li>
    <li><strong>data/</strong>
        <ul>
            <li><strong style="color: green">sqlite.db</strong> - This is the SQLite data. Open this using SQLite Studio to import/export tables.</li>
            <li><strong>020_name_matching.csv</strong> - This is the last name cache download. Don't touch it.</li>
            <li><strong>import.sql</strong> - This is used to import the names table. Don't touch it.</li>
        </ul>
    </li>
    <li><strong style="color: green" >README.md</strong> - You may have already read this as it is how you get the application working in the first place.</li>
    <li><strong>actions.php</strong> - This is application code. Don't touch it.</li>
    <li><strong>index.php</strong> - This is application code. Don't touch it.</li>
</ul>

<h3>Using SQLite Studio</h3>

<p ><strong style="color: red">Beware! </strong> SQLite is a single user database and so there is a danger of overwriting your work if you edit the data with this application and SQLite Studio at the same time.
    This is mainly the case with changing the table structure.  
</p>
<ul>
    <li>Do not manipulate data in Studio whilst this app is also doing a task.</li>
    <li>Do close the table structure edit tab in Studio when you have finished and before running functions in the app. The tab loads the structure once so if this tool changes the structure after the tab is loaded the tab will overwrite the changes on next commit.</li>
</ul>

<h4>Importing CSV Data</h4>
<p></p>

<ol>
    <li>Your CSV import file needs to have column headings in it. If it comes form unzipping a DwCA file it may not have them and you may need to add them manually. (If need be I can create a tool to do this).</li>
    <li>SQLiteStudio > Tools >  Import</li>
    <li>Type a new table name</li>
    <li>Continue</li>
    <li>Get the tab or comma delimitation etc correct</li>
    <li>FIXME: Add some indexes if the table is very big - ids especially.</li>

</ol>

</div>