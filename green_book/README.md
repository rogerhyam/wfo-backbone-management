# Integrity Rules for the WFO Taxonomic Backbone

(very much under construction)

## Separation of Names and Taxa



## WFO IDs and Names
1. Each name has a single, __prescribed WFO ID__. This is the ID that is used to refer to that name whether it occurs as the accepted name of a taxon, a synonym or remains unplaced.
1. Names may have multiple __deduplication WFO IDs__. When it is determined that two records represent the same real world name they are combined. One ID is chosen as the prescribed ID for the name and the other becomes a deduplication ID. Deduplication IDs should not used other than for the purpose of resolving to the prescribed ID.

## WFO IDs and Taxa


## Name Parts

There are never more than three words in a name in botanical nomenclature. More words maybe used when labelling taxa to indicate the taxonomic placement of the name. Internally the backbone system refers to the three words that make up any name as the Name Parts.

All names have a "Name String" part. This is the word that is minted when the name is published. For a Family it is the family name. For a genus it is the genus name. For a species it is the specific epithet. For a subspecies it is the subspecific epithet.

Names below the rank of genus have a "Genus Part" or "Genus String" to their names. This indicates the genus they were placed in when published or the combination made. (FIXME: Mention homotypic genera?)

Names below the rank of species have a "Species Part" or "Species String" to their name indicating the combination they were published in.

From the point of view of nomenclature subsubspecies and subvarieties are direct "children" of the species. There are no polynomials in botany. This is because they could be placed in any subspecies or variety within that species  without changing their name. The rank isn't part of the name. The same rule applies for divisions of the genus. See Article 53.3 of the code https://www.iapt-taxon.org/nomen/pages/main/art_53.html#Art53.3 

Where it doesn't cause confusion the three parts may be referred to simply as Name, Genus and Species but care must be taken. Better to say the Name String of a name than the Name of a name!

When the name parts are combined, possibly with rank and authors string, then the result is referred to as the "Full Name".

This structure can be mapped onto any arbitrary use of names found in different exchange standards and publications. It can also be extended with names from other ranks in the hierarchy on export. For example it may be required to include a subspecies in the expanded full name of a variety but this reflects the taxonomy not the nomenclature.

## Nomeclatural status of Names

Names can have a nomenclatural status. This is separate from their taxonomic status (within the currently accepted taxonomy). Some of these statuses may preclude their use as accepted names of taxa in the taxonomy.

1. illegitimate - can't be used as accepted name in taxonomy as accepted name.
1. later_homonym - can't be used as accepted name in taxonomy as accepted name.
1. superfluous - can't be used as accepted name in taxonomy as accepted name.
1. conserved - a later homonym that has been explicitly flagged as available for use under the code.
1. rejected - can't be used as accepted name in taxonomy as accepted name.
1. sanctioned - fungi specific version of conserved
1. valid - a correctly published name.
1. invalid - can't be used as accepted name in taxonomy as accepted name.
1. deprecated - can't be used as accepted name in taxonomy at all.
1. unknown - not recommended for placement in taxonomy


### More on Deprecation

The nomenclatural status of __deprecated__ is introduced primarily as an internal device. This is not a nomenclatural status according to the botanical code. It is meant in the modern sense of the word particularly with regard to software:

"_to withdraw official support for or discourage the use of_"

We use it for names that we believe have been created in error and that we can't attribute clear meaning to. __It is recommended that these names are not used in future for any purpose.__ They are maintained in the database for name matching purposes and so they can be resurrected in the future without creating new WFO IDs if more information is discovered.

The status deprecated is introduced to quell the plague of zombie names. These are names that may have occurred in the literature or a database just once and have subsequently been propagated from one list to the next without ever dying a natural death, just soaking up time and resources. Zombie names are particularly problematic in the age of big data. This is where they find peace.

## Taxonomic status of Names

A name can only have a single __taxonomic__ status (that is play one role) in the system.
1. __Accepted Name__. A taxon can only have one of these and they have to agree with the rules regarding placement. The nomenclatural status of the name must be valid, conserved or sanctioned.
1. __Synonym__ A taxon can have many of these. They can be of any nomenclatural status apart from deprecated. (Note that whether synonyms are homotypic or heterotypic are tracked separately and discoverable through basionym links)
1. __Unplaced__ A name that isn't associated with any taxon as an accepted name or synonym. This may be because the name hasn't been researched yet (it is __unknown__) or because the name is __illegitimate__ under the code in some way (_nom. illeg._ or _nom. superfl._) or because there will probably never be sufficient information to place it anywhere, nomenclatural status deprecated. All unplaced names (apart from deprecated names) are available for placement in the taxonomy as synonyms. 

## Placement of Names

There are rules governing how and when names can be placed in the taxonomy. There are five possible actions  In addition there are some rules that prevent names being moved.


### Placement Actions

There are five actions that can be taken to change a names placement

1. __Raise to accepted name__ A name that is a synonym or not yet placed in the taxonomy and has a nomenclatural status or Valid, Conserved or Sanctioned can become the accepted name of a taxon.
1. __Sink into synonym__ A name that is the accepted name of a taxon (which doesn't have children or synonyms) or has not yet been placed in the taxonomy can become a synonym in an accepted taxon.
1. __Change parent taxon__ A name that is the accepted name of a taxon can be moved to another part of the taxonomy.
1. __Change accepted taxon__ A name that is a synonym of one taxon can be moved to become the synonym of another taxon.
1. __Remove from taxonomy__ A name that forms part of the taxon as the accepted name of a taxon (which doesn't have children or synonyms) or is a synonym can be removed from the taxonomy.

### Placement Destinations

There are three rules that govern where a name can be placed in the taxonomy

1. __Nomenclatural Status__ Deprecated names can't be placed in the taxonomy at all. Names of all other statuses can be synonyms. Valid, Conserved and Sanctioned names can be accepted names of taxa.  
1. __Congruent Ranks__ The rank of a child taxon must be one of the accepted ranks of the parent according to the ranks table. e.g. a subspecies can't be a direct child of a genus or family.
1. __Congruent Name Parts__ The name parts of the parent taxon must agree with the genus string and species string part of the name. e.g. a species can only be in a genus which has a name-string that matches its genus-string and a subspecies can only be in a species that has the name-string and genus-string that agrees with its own species-string and genus-string.
 


## Taxon Status

All taxa are of the same status. If they occur in the taxonomy then they are accepted taxa. Taxa can't be synonyms. Synonyms are names (from the Greek "with name"). Synonymy indicates the placement of Type specimens only. 


## Names are only alphabetical characters without diacritics 

60.1. The original spelling of a name or epithet is to be retained, except for the correction of typographical or orthographical errors and the standardizations imposed by Art. 60.4 (letters and ligatures foreign to classical Latin), 60.5 and 60.6 (interchange between u/v, i/j, or eu/ev), 60.7 (diacritical signs and ligatures), 60.8 (terminations; see also Art. 32.2), 60.9 (intentional latinizations), 60.10 (compounding forms), 60.11 and 60.12 (hyphens), 60.13 (apostrophes and full stops), 60.14 (abbreviations), and F.9.1 (epithets of fungal names) (see also Art. 14.8, 14.11, and F.3.2).


60.7.  Diacritical signs are not used in scientific names. When names
(either new or old) are drawn from words in which such signs appear, the
signs are to be suppressed with the necessary transcription of the letters so
modified; for example ä, ö, ü become, respectively, ae, oe, ue (not æ or œ,
see below); é, è, ê become e; ñ becomes n; ø becomes oe (not œ); å becomes
ao.
    $scientificName = str_replace('ä', 'ae', $scientificName);
    $scientificName = str_replace('ö', 'oe', $scientificName);
    $scientificName = str_replace('ü', 'ue', $scientificName);
    $scientificName = str_replace('é', 'e', $scientificName);
    $scientificName = str_replace('è', 'e', $scientificName);
    $scientificName = str_replace('ê', 'e', $scientificName);
    $scientificName = str_replace('ñ', 'n', $scientificName);
    $scientificName = str_replace('ø', 'oe', $scientificName);
    $scientificName = str_replace('å', 'ao', $scientificName);
    $scientificName = str_replace("", '', $scientificName); // can you believe an o'donolli 



Lycium chilense var. o'donellii F.A.Barkley


## Integrity Checks on Saving a Name


## Integrity Checks on Saving a Taxon

1. An accepted name must be set.
1. A parent taxon must be set (apart from the root of all taxa which has itself as its parent).
1. The accepted name for the taxon must pass the integrity checks necessary for it to be saved as a name (see above).
1. The accepted name must have the nomenclatural status 'valid'.
1. The accepted name must not be the accepted name of another taxon.
1. The basionym of the accepted name (or any other homotypic name) must not be the accepted name of another taxon.
1. If the accepted name is a synonym of another taxon then that synonym link will be broken - the name is moved.
1. If the basionym of the accepted name (or any other homotypic name) is a synonym of another taxon then that synonym link will be broken and the name moved to be a synonym of this taxon.
1. The rank of the accepted name must be an appropriate lower rank to that of the name of the parent (see ranks table below).
1. The siblings of the taxon within this parent must be at the same rank. If a rank in the hierarchy is skipped then the structure of the tree will be adjusted to use autonyms below the genus level placeholder taxa as described below.
1. For taxa at species rank and below the genus part of the name must agree with the name part of the genus above them in the hierarchy.
1. For taxa below species rank the species part of their names must agree with the name part of the species above them in the hierarchy.
1. For taxa at species rank and below the year in the name can't be great than the year in name of the genus taxon (if these are set).


## Autonyms

According to the nomeclatural code the creation of a subdivision within a genus or a species automatically creates a null taxon called the autonym which holds everything that isn't specified as being part of the named subdivisions. Autonyms include the type specimen for the species and genus (Art. 22.3 and 26.3 of code). These taxa simply repeat the name of the genus or species as their own name and have no author strings. In line with these rules the WFO backbone system automatically creates and destroys autonym taxa as infrageneric and infraspecific taxa are added and removed from the taxonomic hierarchy.

Above the level of genus the code has no notion of autonyms. The creation of a new subfamily does not result in the creation of an associated autonym subfamily to hold the type and all other material that wasn't considered by the author of the subfamily. In a large scale, collaborative project like the WFO backbone this can result in a counter intuitive classification. A taxon of the rank class may __directly__ contain several subclasses, some orders and a family or two. This is something that wouldn't occur in a single written publication but is the logical result of combining multiple publications. It simply reflects that the work hasn't been done to specify where all the subtaxa fall at every rank. Such a classification probably wouldn't be accepted by a journal editor! The user interface will therefore always highlight where this occurs to flag it as bad practice.

## Ranks

Recognised ranks are listed in the table below in hierarchical order along with the ranks that are permitted to belong to taxa at that rank. The lowercase English version of the rank name is used internally. Mappings to other versions and abbreviations are
carried out during import.


## Overloading Basionym

