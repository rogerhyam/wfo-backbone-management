<?php

// configuration variables used across the scripts and applications
require_once('../vendor/autoload.php');
include_once("../../backbone_secrets.php");

session_name($session_name); // set in secrets to differentiate sandbox from live site.
session_start();

// create and initialise the database connection
$mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);  

// connect to the database
if ($mysqli->connect_error) {
  echo $mysqli->connect_error;
}

if (!$mysqli->set_charset("utf8")) {
  echo printf("Error loading character set utf8: %s\n", $mysqli->error);
}

// ORCID Connection details
// client id and secret are loaded in the secret file
define('ORCID_CLIENT_ID', $orcid_client_id);
define('ORCID_CLIENT_SECRET', $orcid_client_secret);
define('ORCID_TOKEN_URI', 'https://orcid.org/oauth/token');

// the redirect uri depends on live or not
// FIXME for live more smoothly
if(getenv('WFO_EDIT_DEV')){
  define('ORCID_REDIRECT_URI', 'http://localhost:1756/orcid_redirect.html');
  define('ORCID_LOG_OUT_URI', 'http://localhost:1756/orcid_log_out.php');
}else{
  define('ORCID_REDIRECT_URI', 'https://list.worldfloraonline.org/edit/orcid_redirect.html');
  define('ORCID_LOG_OUT_URI', 'http://localhost:1756/orcid_log_out.php');
}

// the login uri is constructed from variables above
define('ORCID_LOG_IN_URI', 'https://orcid.org/oauth/authorize?client_id='. ORCID_CLIENT_ID .'&response_type=code&scope=/authenticate&redirect_uri=' . ORCID_REDIRECT_URI);

// status flags
define("WFO_INTEGRITY_OK", 1); // when the integrity of a name or taxon checks out
define("WFO_INTEGRITY_FAIL", 2); // when the integrity of a name or taxon does not check out
define("WFO_INTEGRITY_WARN", 3); // when the integrity is OK but there are information messages
define("WFO_AUTONYM", 4); // returned when this is the autonym
define("WFO_AUTONYM_EXISTS", 5); // returned when this isn't the autonym - but one exists
define("WFO_AUTONYM_REQUIRED", 6); // returned when this isn't the autonym and no autonym exists at this rank
define("WFO_AUTONYM_NA", 7); // returned when this isn't the autonym and no autonym exists at this rank
define("WFO_RANK_REBALANCE", 8); // the ranks at this point are out of balance and need fixing.


// the rank table - having this in code saves a lot of sql queries and joins because
// it is used everywhere.
// -- READ -- THIS --
// this should be kept in syn with the enumeration values in the db
// and also the wfo_mint table to generate
$ranks_table = array(

  "kingdom" => array(
    "children" => array("phylum"), // permissible ranks for child taxa
    "abbreviation" => "K.", // official abbreviation
    "plural" => "Kingdoms",
    "aka" => array() // alternative representations for import
  ),

  "phylum" => array(
    "children" => array("class", "order", "family"), // permissible ranks for child taxa
    "abbreviation" => "P.", // official abbreviation
    "plural" => "Phyla",
    "aka" => array() // alternative representations for import
  ),

  "class" => array(
    "children" => array("subclass", "order", "family"), // permissible ranks for child taxa
    "abbreviation" => "C.", // official abbreviation
    "plural" => "Classes",
    "aka" => array() // alternative representations for import
  ),

  "subclass" => array(
    "children" => array("order", "family"), // permissible ranks for child taxa
    "abbreviation" => "subC.", // official abbreviation
    "plural" => "Subclasses",
    "aka" => array() // alternative representations for import
  ),


  "order" => array(
    "children" => array("suborder", "family"), // permissible ranks for child taxa
    "abbreviation" => "O.", // official abbreviation
    "plural" => "Orders",
    "aka" => array() // alternative representations for import
  ),

  "suborder" => array(
    "children" => array("family"), // permissible ranks for child taxa
    "abbreviation" => "subO.", // official abbreviation
    "plural" => "Suborders",
    "aka" => array() // alternative representations for import
  ),

  "family" => array(
    "children" => array("subfamily", "tribe", "genus"), // permissible ranks for child taxa
    "abbreviation" => "Fam.", // official abbreviation
    "plural" => "Families",
    "aka" => array() // alternative representations for import
  ),

  "subfamily" => array(
    "children" => array("tribe", "genus"), // permissible ranks for child taxa
    "abbreviation" => "subFam.", // official abbreviation
    "plural" => "Subfamilies",
    "aka" => array() // alternative representations for import
  ),

  "tribe" => array(
    "children" => array("subtribe", "genus"), // permissible ranks for child taxa
    "abbreviation" => "T.", // official abbreviation
    "plural" => "Tribes",
    "aka" => array() // alternative representations for import
  ),

  "subtribe" => array(
    "children" => array("genus"), // permissible ranks for child taxa
    "abbreviation" => "subT.", // official abbreviation
    "plural" => "Subtribes",
    "aka" => array() // alternative representations for import
  ),

  "genus" => array(
    "children" => array("subgenus", "section", "series", "species"), // permissible ranks for child taxa
    "abbreviation" => "Gen.", // official abbreviation
    "plural" => "Genera",
    "aka" => array() // alternative representations for import
  ),

  "subgenus" => array(
    "children" => array("section", "series", "species"), // permissible ranks for child taxa
    "abbreviation" => "subGen.", // official abbreviation
    "plural" => "Subgenera",
    "aka" => array() // alternative representations for import
  ),

  "section" => array(
    "children" => array("subsection", "series", "species"), // permissible ranks for child taxa
    "abbreviation" => "Sect.", // official abbreviation
    "plural" => "Sections",
    "aka" => array() // alternative representations for import
  ),
  
  "subsection" => array(
    "children" => array("series", "species"), // permissible ranks for child taxa
    "abbreviation" => "subSect.", // official abbreviation
    "plural" => "Subsections",
    "aka" => array() // alternative representations for import
  ),

  "series" => array(
    "children" => array("subseries", "species"), // permissible ranks for child taxa
    "abbreviation" => "Ser.", // official abbreviation
    "plural" => "Series",
    "aka" => array() // alternative representations for import
  ),

  "subseries" => array(
    "children" => array("species"), // permissible ranks for child taxa
    "abbreviation" => "subSer.", // official abbreviation
    "plural" => "Subseries",
    "aka" => array() // alternative representations for import
  ),

  "species" => array(
    "children" => array("subspecies", "variety", "form"), // permissible ranks for child taxa
    "abbreviation" => "sp.", // official abbreviation
    "plural" => "Species",
    "aka" => array("nothospecies") // alternative representations for import
  ),

  "subspecies" => array(
    "children" => array("variety", "form"), // permissible ranks for child taxa
    "abbreviation" => "subsp.", // official abbreviation
    "plural" => "Subspecies",
    "aka" => array("nothosubspecies", "subsp.", "subsp") // alternative representations for import
  ),

  "variety" => array(
    "children" => array("subvariety", "form"), // permissible ranks for child taxa
    "abbreviation" => "var.", // official abbreviation
    "plural" => "Varieties",
    "aka" => array("nothovar.", "var.", "var") // alternative representations for import
  ),

  "subvariety" => array(
    "children" => array("form"), // permissible ranks for child taxa
    "abbreviation" => "subvar.", // official abbreviation
    "plural" => "Subvarieties",
    "aka" => array("subvar") // alternative representations for import
  ),

  "form" => array(
    "children" => array("subform"), // permissible ranks for child taxa
    "abbreviation" => "f.", // official abbreviation
    "plural" => "Forms",
    "aka" => array("forma", "f") // alternative representations for import
  ),

  "subform" => array(
    "children" => array(), // permissible ranks for child taxa
    "abbreviation" => "subf.", // official abbreviation
    "plural" => "Subforms",
    "aka" => array("subforma") // alternative representations for import
  )

);


