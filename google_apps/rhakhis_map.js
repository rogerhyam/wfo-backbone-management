// Globals

const graphQlUri = "https://list.worldfloraonline.org/rhakhis/api/gql.php";
let defaultApiAccessToken = "2e1695ae6d4c0e36c67088d1aaba39a9cba6b9703b5f9220";

const graphQuery = `
  query(
  	$scientificName: String!
	  $scientificNameAuthorship: String
  	$taxonrank: String
  	$family: String
	){
      getNamesByDarwinCoreMatch(
        scientificName: $scientificName 
        scientificNameAuthorship: $scientificNameAuthorship
        taxonrank: $taxonrank
        family: $family){
          names{
            wfo,
            fullNameString,
            authorsString,
            basionym{
              wfo
            },
            taxonPlacement{
              acceptedName{
                wfo
              }
              parent{
                acceptedName{
                  wfo
                }
              }
            }
          },
          distances
          nameParts,
          authors
      }
  } 
  `;

// NB this repeats fields above
const getNameQuery = `
  query(
  	$wfo: String!
	){
      getNameForWfoId(id: $wfo){
          wfo,
          fullNameString,
          authorsString,
          basionym{
            wfo
          },
          taxonPlacement{
            acceptedName{
              wfo
            }
            parent{
              acceptedName{
                wfo
              }
            }
          }
      }
  } 
  `;


function onOpen() {

  // add the menus 
  var ui = SpreadsheetApp.getUi();
  ui.createMenu('WFO')
    .addItem('How this works', 'howGeneral')
    .addItem("Set API access token", 'getApiAccessToken')
    .addItem("Check API connection", "checkConnection")
    .addItem('Map rows (matching)', 'mapRows')
    .addItem('Update rows (no matching)', 'updateRows')
    .addItem('Pick name (ambiguous)', 'pickName')
    .addToUi();

  // set the UI default access token if we don't have one set already.
  if (!PropertiesService.getUserProperties().getProperty('apiAccessToken')) {
    PropertiesService.getUserProperties().setProperty('apiAccessToken', defaultApiAccessToken);
  }
}

function getApiAccessToken() {
  var ui = SpreadsheetApp.getUi();
  var result = ui.prompt("Please enter you API access token.");
  if (result && result.getResponseText()) {
    PropertiesService.getUserProperties().setProperty('apiAccessToken', result.getResponseText().trim());
  }

}

/**
 * 
 * Utility to check that there is an API connection
 * by getting the user
 * 
 */
function checkConnection() {

  const ui = SpreadsheetApp.getUi();

  const data = runGraphQuery(
    `query{
	      getUser{
        id,
        name,
        orcid
      }
    }`,
    {}
  );

  ui.alert("You are logged in as: '" + data.getUser.name + "'");

}

function runGraphQuery(query, variables) {

  const payload = {
    'query': query,
    'variables': variables
  }

  var options = {
    'method': 'post',
    'contentType': 'application/json',
    'headers': {
      'wfo_access_token': PropertiesService.getUserProperties().getProperty('apiAccessToken')
    },
    'payload': JSON.stringify(payload)
  };

  const response = UrlFetchApp.fetch(graphQlUri, options);
  const reply = JSON.parse(response.getContentText());
  return reply.data;

}

function pickName() {

  const ui = SpreadsheetApp.getUi();
  const sheet = SpreadsheetApp.getActiveSheet();
  const colIndexes = getColumnIndexes();

  const rowIndex = sheet.getActiveRange().getRowIndex();

  const rhakhisIdCell = sheet.getRange(rowIndex, colIndexes.scientificName + 1);

  // as a minimum we add the scientificName
  const variables = {
    scientificName: rhakhisIdCell.getValue()
  };

  // if any of the other cols are present we add them
  if (colIndexes.scientificNameAuthorship !== undefined) variables.scientificNameAuthorship = sheet.getRange(rowIndex, colIndexes.scientificNameAuthorship + 1).getValue();
  if (colIndexes.taxonrank !== undefined) variables.taxonrank = sheet.getRange(rowIndex, colIndexes.taxonrank + 1).getValue();
  if (colIndexes.family !== undefined) variables.family = sheet.getRange(rowIndex, colIndexes.family + 1).getValue();

  const data = runGraphQuery(graphQuery, variables);

  // don't do anything if we have no names.
  if (data.getNamesByDarwinCoreMatch.names.length < 2) {
    ui.alert("No names to choose between for this row.");
    return;
  }

  let pickList = `
  <h2>Pick a name</h2><ul>
  `;
  data.getNamesByDarwinCoreMatch.names.map(name => {
    pickList += "<li>";
    pickList += "<a href=\"#\" onclick=\"google.script.run.namePicked('" + name.wfo + "'," + rowIndex + "); google.script.host.close();\" >" + name.fullNameString + '</a>';
    pickList += " [" + name.wfo + "] ";
    pickList += "</li>";

  });
  pickList += "</ul>";

  let widget = HtmlService.createHtmlOutput(pickList);
  widget.setTitle("Rhakhis name picker");
  ui.showSidebar(widget);

  Logger.log(data.getNamesByDarwinCoreMatch);

  //ui.alert(variables.scientificName + " Implement me! I will take the current row and give you a list of potential names to pick.");

}

function namePicked(wfo, rowIndex) {

  const ui = SpreadsheetApp.getUi();
  const sheet = SpreadsheetApp.getActiveSheet();
  const colIndexes = getColumnIndexes();

  const data = runGraphQuery(getNameQuery, { wfo: wfo });
  const name = data.getNameForWfoId;

  // set the wfo in the first element
  sheet.getRange(rowIndex, 1).setValue(wfo);

  // update all the other columns
  updateRow(name, rowIndex - 1, colIndexes)

}


/**
 * Run through rows and update the 
 * other fields based on the wfo 
 * in the first column
 * 
 */
function updateRows() {

  const sheet = SpreadsheetApp.getActiveSheet();
  const rows = sheet.getDataRange().getValues();
  const colIndexes = getColumnIndexes();

  rows.map((row, index) => {
    // only interested in rows that have wfo id
    const regex = /^wfo-[0-9]{10}$/;
    if (row[0].match(regex)) {

      // get the data for the name from rhakhis
      const data = runGraphQuery(getNameQuery, { wfo: row[0] });

      // only interested if we get a name back
      if (data.getNameForWfoId && data.getNameForWfoId.wfo) {
        updateRow(data.getNameForWfoId, index, colIndexes);
      }

    }

  });

}


/**
 * run the name matching function
 * will fetch a WFO ID for each of the
 * rows with names in that don't already
 * have one
 * 
 */
function mapRows() {

  const ui = SpreadsheetApp.getUi();
  const sheet = SpreadsheetApp.getActiveSheet();

  // Add the columns in if they aren't already there
  configureRhakhisColumns();

  // get the indexes of all the cols we are interested in
  const colIndexes = getColumnIndexes();

  // do nothing if we don't have a scientificName column
  if (colIndexes.scientificName === undefined) {
    ui.alert("No 'scientificName' column is present. Please check and try again.");
    return;
  }

  // let's do the lookup!
  const rows = sheet.getDataRange().getValues();
  rows.map((row, index) => {

    // we skip rows that already have a wfo-id in the first column
    const regex = /^wfo-[0-9]{10}$/;
    if (!row[0].match(regex)) {
      mapRow(row, index, colIndexes);
    }

  });

}

function mapRow(row, index, colIndexes) {

  const sheet = SpreadsheetApp.getActiveSheet();

  // skip the titles row 
  if (index === 0) return;

  // as a minimum we add the scientificName
  const variables = {
    scientificName: row[colIndexes.scientificName],
  };

  // if any of the other cols are present we add them
  if (colIndexes.scientificNameAuthorship !== undefined) variables.scientificNameAuthorship = row[colIndexes.scientificNameAuthorship];
  if (colIndexes.taxonrank !== undefined) variables.taxonrank = row[colIndexes.taxonrank];
  if (colIndexes.family !== undefined) variables.family = row[colIndexes.family];

  const data = runGraphQuery(graphQuery, variables);

  const rhakhisIdCell = sheet.getRange(index + 1, 1);

  // get out of here if we have found no matches
  if (data.getNamesByDarwinCoreMatch.names.length === 0) {
    rhakhisIdCell.setValue('No Match');
    rhakhisIdCell.setNote(data.getNamesByDarwinCoreMatch.authors);
  } else if (data.getNamesByDarwinCoreMatch.names.length === 1) {
    rhakhisIdCell.setValue(data.getNamesByDarwinCoreMatch.names[0].wfo);
    rhakhisIdCell.setNote(data.getNamesByDarwinCoreMatch.names[0].fullNameString.replace(/(<([^>]+)>)/gi, ""));
    if (data.getNamesByDarwinCoreMatch.distances[0] === 0) {
      rhakhisIdCell.setFontColor("green");
    } else {
      rhakhisIdCell.setFontColor("orange");
    }
    updateRow(data.getNamesByDarwinCoreMatch.names[0], index, colIndexes);
  } else {
    rhakhisIdCell.setValue('Ambiguous: ' + data.getNamesByDarwinCoreMatch.names.length);
    rhakhisIdCell.setFontColor("orange");

    let note = "";
    data.getNamesByDarwinCoreMatch.names.map((name, index) => {
      note += name.wfo + " ";
      note += name.fullNameString.replace(/(<([^>]+)>)/gi, "");
      note += "(" + data.getNamesByDarwinCoreMatch.distances[index] + ")";
      note += "\n";
    });
    rhakhisIdCell.setNote(note);

  }

  SpreadsheetApp.flush();

}

/**
 * given a name and a row index 
 * we update the other fields 
 * in the row
 * 
 */
function updateRow(name, index, colIndexes) {

  const ui = SpreadsheetApp.getUi();
  const sheet = SpreadsheetApp.getActiveSheet();

  // check the id is correct first
  if (name.wfo !== sheet.getRange(index + 1, 1).getValue()) {
    Logger.log(name.wfo + " does not match " + sheet.getRange(index + 1, 1).getValue());
    return;
  }

  // the taxonID should be the same as the name.wfo if present
  if (colIndexes.taxonID) {
    const taxonIdCell = sheet.getRange(index + 1, colIndexes.taxonID + 1);
    if (taxonIdCell.getValue() && taxonIdCell.getValue() === name.wfo) {
      taxonIdCell.setFontColor('green');
    } else {
      taxonIdCell.setFontColor('orange');
    }
  }

  // enter the other values if we have them.

  // is it placed
  if (name.taxonPlacement && name.taxonPlacement.acceptedName.wfo) {

    // if the accepted taxon placement if different then we are a synonym
    if (name.wfo !== name.taxonPlacement.acceptedName.wfo) {

      // synonym
      sheet.getRange(index + 1, 2).setValue(null); // parent
      sheet.getRange(index + 1, 3).setValue(name.taxonPlacement.acceptedName.wfo); // accepted

      // if we have an accepted name column we should highlight it 
      if (colIndexes.acceptedNameUsageID) {
        const acceptedIdCell = sheet.getRange(index + 1, colIndexes.acceptedNameUsageID + 1);
        if (acceptedIdCell.getValue() && acceptedIdCell.getValue() === name.taxonPlacement.acceptedName.wfo) {
          acceptedIdCell.setFontColor('green');
          acceptedIdCell.setNote(null);
        } else {
          acceptedIdCell.setFontColor('orange');
          acceptedIdCell.setNote("This does not match the accepted ID in Rhakhis");
        }
      }

      // if we have a parent column is should be empty
      if (colIndexes.parentNameUsageID) {
        const parentIdCell = sheet.getRange(index + 1, colIndexes.parentNameUsageID + 1);
        if (parentIdCell.getValue().toString().length > 0) {
          parentIdCell.setFontColor('orange');
          parentIdCell.setNote("This is a synonym and should not have a parent ID");
        } else {
          parentIdCell.setFontColor(null);
          parentIdCell.setNote(null);
        }
      }

    } else {
      // accepted name
      sheet.getRange(index + 1, 2).setValue(name.taxonPlacement.parent.acceptedName.wfo); // parent
      sheet.getRange(index + 1, 3).setValue(null); // accepted

      // if we have an parent name column we should highlight it 
      if (colIndexes.parentNameUsageID) {
        const parentIdCell = sheet.getRange(index + 1, colIndexes.parentNameUsageID + 1);
        if (parentIdCell.getValue() && parentIdCell.getValue() === name.taxonPlacement.parent.acceptedName.wfo) {
          parentIdCell.setFontColor('green');
          parentIdCell.setNote(null);
        } else {
          parentIdCell.setFontColor('orange');
          parentIdCell.setNote("This does not match the parent ID in Rhakhis");
        }
      }

      // if we have an accepted column is should be empty
      if (colIndexes.acceptedNameUsageID) {
        const acceptedIdCell = sheet.getRange(index + 1, colIndexes.acceptedNameUsageID + 1);
        if (acceptedIdCell.getValue().toString().length > 0) {
          acceptedIdCell.setFontColor('orange');
          acceptedIdCell.setNote("This is an accepted name and should not have an accepted name ID");
        } else {
          acceptedIdCell.setFontColor(null);
          acceptedIdCell.setNote(null);
        }
      }

    }

  } else {
    // not placement so no parent or accepted
    sheet.getRange(index + 1, 2).setValue(null); // parent
    sheet.getRange(index + 1, 3).setValue(null); // accepted
  }

  // basionym
  if (name.basionym && name.basionym.wfo) {
    sheet.getRange(index + 1, 4).setValue(name.basionym.wfo);
    if (colIndexes.originalNameUsageID) {
      const basionymIdCell = sheet.getRange(index + 1, colIndexes.originalNameUsageID + 1);
      if (basionymIdCell.getValue() && basionymIdCell.getValue() === name.basionym.wfo) {
        basionymIdCell.setFontColor('green');
        basionymIdCell.setNote(null);
      } else {
        basionymIdCell.setFontColor('orange');
        basionymIdCell.setNote("This does not match the basionym ID in Rhakhis");
      }
    }
  } else {
    sheet.getRange(index + 1, 4).setValue(null);
  }

  // authorship
  if (colIndexes.scientificNameAuthorship) {
    const authorsCell = sheet.getRange(index + 1, colIndexes.scientificNameAuthorship + 1);
    if (authorsCell.getValue() && authorsCell.getValue() === name.authorsString) {
      authorsCell.setFontColor('green');
      authorsCell.setNote(null);
    } else {
      authorsCell.setFontColor('orange');
      authorsCell.setNote("This does not match the authors string in Rhakhis");
    }
  }

}

function getColumnIndexes() {

  const ui = SpreadsheetApp.getUi();
  const sheet = SpreadsheetApp.getActiveSheet();

  const columnIndexes = {};

  const columnNames = [
    "scientificName",
    "scientificNameAuthorship",
    "taxonrank",
    "family",
    "WFO_ID",
    "taxonID",
    "LocalID",
    "originalNameUsageID",
    "parentNameUsageID",
    "acceptedNameUsageID"
  ];

  // get the first fifty cells along the top
  const range = sheet.getRange(1, 1, 1, 50);
  const values = range.getValues();

  // work through them
  values[0].map((v, i) => {
    columnNames.map(cn => {
      if (cn === v.trim()) {
        columnIndexes[cn] = i;
      }
    });

  });

  return columnIndexes;

}

function configureRhakhisColumns() {

  const sheet = SpreadsheetApp.getActiveSheet();

  // wfo id column
  var range = sheet.getRange(1, 1);
  var values = range.getValues();
  if (values[0][0] !== 'rhakhis_id') {
    sheet.insertColumnBefore(1);
    range = sheet.getRange(1, 1);
    range.setValue("rhakhis_id");
  }

  // wfo parent_id
  range = sheet.getRange(1, 2);
  values = range.getValues();
  if (values[0][0] !== 'rhakhis_parent') {
    sheet.insertColumnAfter(1);
    range = sheet.getRange(1, 2);
    range.setValue("rhakhis_parent");
  }

  // wfo accepted
  range = sheet.getRange(1, 3);
  values = range.getValues();
  if (values[0][0] !== 'rhakhis_accepted') {
    sheet.insertColumnAfter(2);
    range = sheet.getRange(1, 3);
    range.setValue("rhakhis_accepted");
  }

  // wfo basionym
  range = sheet.getRange(1, 4);
  values = range.getValues();
  if (values[0][0] !== 'rhakhis_basionym') {
    sheet.insertColumnAfter(3);
    range = sheet.getRange(1, 4);
    range.setValue("rhakhis_basionym");
  }

}


/**
 *  Display the general help message
 * 
 */
function howGeneral() {

  const html = `

<p>
  This Add-on maps the nomenclatural and taxonomic data in a spreadsheet with data in the WFO taxonomic backbone as managed in the Rhakhis system using the Rhakhis API.
</p>
<p>
  To use the tool you must have a valid API Access Token. Each registered user of Rhakhis has their own token that can be retrieved through the Rhakhis user interface.
  Never share your token! Set the token using the "Set API access token" menu item then check the connection works using the "Check API connection".
  If this returns your name then you are ready to use the mapping functions.  
</p>
<p>
  The "Map rows" menu item will run a script that does the following:
</p>
<ol>

  <li>Adds four columns to the start of the current sheet, if they don't already exist. These columns will be populated by the script with data from the API.
    <ul>
      <li><strong>rhakhis_id:</strong> This will contain the WFO ID for the name in this row of the spreadsheet based on the data in the other fields (see below).</li>
      <li><strong>rhakhis_basionym:</strong> The WFO ID for the basionym of the name, as it occurs in the Rhakhis system.</li>
      <li><strong>rhakhis_parent:</strong> The WFO ID for the parent taxon if this row represents the name of an accepted taxon according to current data. This field is mutually exclusive with rhakhis_accepted. They can't both have data.</li>
      <li><strong>rhakhis_accepted:</strong> The WFO ID for the accepted taxon if this ro represents the name of synonym. Empty if rhakhis_parent is present. You can't be an accepted taxon name and a synonym.</li>
    </ul>
  </li>

  <li>Looks up the values for the four columns using the Rhakhis API. This is done on the basis of the content of the following columns in the sheet.
        <ul>
          <li><strong>scientificName:</strong> REQUIRED This should contain the scientific name without the authors string. If this isn't present the script won't run.</li>

          <li><strong>scientificNameAuthorship:</strong> OPTIONAL This should contain the authors string in the standard abbreviated form.</li>

          <li><strong>taxonrank:</strong> OPTIONAL This should contain the rank of the name, ideally the full name in lower case.</li>

          <li><strong>family:</strong> OPTIONAL This should contain the family the name is associated with. It is treated as a hint because the name matching process is purely nomenclatural.</li>
        </ul>
  </li>

  <li>Compares the values in the four rhakhis_* fields with fields in the rest of the sheet if they are present and highlights any differences. These field names are the ones found in Darwin Core.

      <ul>
          <li><strong>taxonID:</strong> If you already have a WFO ID for the name you can include it here. It isn't used in the matching but will be highlighted if it differs from the one found through matching.</li>

          <li><strong>LocalID:</strong> The ID in the source database. If this is present it can be used to express the parent, accepted and basionym relationships in the other fields in place of the WFO ID.</li>

          <li><strong>originalNameUsageID:</strong> A correct match is containing the same WFO ID as in rhakhis_basionym or it could contain the LocalID of another row that contains the correct WFO ID in the rhakhis_id column.</li>

          <li><strong>parentNameUsageID:</strong> A correct match is containing the same WFO ID as in the rhakhis_parent column or a LocalID that maps to that WFO ID.</li>

          <li><strong>acceptedNameUsageID:</strong> A correct match is containing the same WFO ID as in the rhakhis_accepted column or a LocalID that maps to that WFO ID.</li>
      </ul>
  </li>
  
</ol>

<p>
  The "Map rows" menu item will only process rows that don't already have a value in the rhakhis_id column. The "Force map" does exactly the same as "Map rows" but will reprocess every row.
</p>

<p>
  The sheet can have any number of columns containing any data that is useful but the columns named above must be within the first fifty columns and the rhakhis_* columns must be the first four. They will be recreated if they are not.
</p>


`;

  var ui = SpreadsheetApp.getUi();
  var htmlOutput = HtmlService
    .createHtmlOutput(html)
    .setWidth(800)
    .setHeight(600);
  ui.showModalDialog(htmlOutput, 'How the WFO Rhakhis sheets tool works');

}

