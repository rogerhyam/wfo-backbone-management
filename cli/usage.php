<?php

echo <<<'EOD'

*********
* Usage *
*********

The Rhakhis CLI (Command Line Interface) is designed to make it easy to work
through lists of names and match them to those already in the WFO Taxonomy.

When names can't be matched it will help create new name entries.

Once names have been matched it will help reconcile a local taxonomy with the
WFO Taxonomy.

Rhakhis CLI works with CSV (Comma Separated Values) files.

If 'php rhakhis.phar' is run with no arguments (and no working.csv file is 
present) it will display this message.

If it is run with the name of an input file then it will parse that input file,
create a working.csv file and begin to parse it.

The input CSV file MUST be comma separated with " as the text escape character.
This is the default "save as csv" format in MS Excel and other spreadsheets.

The input file MUST be UTF-8 encoded.

The first line of the input CSV MUST be the column headings.

Any number of columns with any arbitrary content can be present and will be 
copied to the working.csv file - apart from the two special columns added by
Rhakhis CLI. If they are present they MUST be the first two and in the correct
order.

The working.csv file is a copy of the input file but with an extra column added
to the beginning, rhakhis_ID.

Each time Rhakhis CLI is run it works through the working.csv file from the
beginning and uses the scientificName, scientificNameAuthorship and taxonrank 
columns in the spreadsheet to match names against the API.

If there is a syntactically correct WFO ID in rhakhis_ID it skips that row.
If not then it calls the Rhakhis API to see if it can find the name.

If it finds an unambiguous match if fills in the rhakhis_ID column appropriately.

If if finds an ambiguous or imperfect match it gives the user an opportunity to
confirm the match, create a new name or skip the row.

Thus "php rhakhis.phar" can be run repeatedly until all the rows are matched
to names in the WFO database.

Phase II - reconciling the taxonomy.

**********************************
EOD;