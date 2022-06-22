<div style="max-width: 800px">
<h2>Introduction</h2>

<p>
    This tool is used to reconcile data in a flat table with the data in Rhakhis. The process works as follows:
</p>

<ul>
    <li>All work is carried out on a table that has been loaded into the system. There are two ways to get data into the system as a table. You can upload a file or run a query against a Kew 'shadow' dataset.</li>
    <li><strong>Uploaded Files</strong> can either be DarwinCore Archive format or CSV or zipped up CSV files.</li>
    <li><strong>Kew Datasets</strong> are maintained as SQL databases so queries are run to generate tables to work on.</li>
    <li><strong>Tables</strong> have a series of columns added to them of the form rhakhis_* these are used during the matching and taxonomy phases.</li>
    <li>Tables can be <strong>extracted</strong> and downloaded to return to a TEN as a CSV file including the additional rhakhis_* columns.</li>
    <li>There can be multiple tables in the system but one is selected as the <strong>active table</strong> and all actions (matching etc) are carried out on that table.</li>
    <li>Once the data is in a table there are four main phases of importing it into Rhakhis:
        <ol>
            <li><strong>Matching</strong> involves aligning rows in the table with names in Rhakhis by adding the WFO ID of a name to the 
            rhakhis_wfo column in the table. It is done either by matching on the name and author strings or on IDs that the TEN keeps constant. All subsequent phases rely on matching having been done.</li>
            <li><strong>Linking</strong> takes the local ID used by the data supplier (TEN) and adds it to the name record in Rhakhis. This means that we can unambiguously match on an ID in the future instead of having to disambiguate name strings every time.</li>
            <li><strong>Nomenclature</strong> involves copying data from the table into Rhakhis for matched names. There are a series of decisions that need to be taken as to what is overwritten and what is not.</li>
            <li><strong>Taxonomy</strong> involves validating the hierarchy in the provided data and comparing it to that already in Rhakhis before replacing that found in Rhakhis with that found in the table - if it is an improvement.</li> 
        </ol>
    </li>
        <li><strong>Skips</strong> are a flag added to a row in the table that says it should not be processed. They enable rows to be returned to later if they need more work.</li>
</ul>


</div>