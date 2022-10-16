# Rhakhis Manual

This is the top level documentation for the World Flora Online taxonomy management system. It is an overview of how the data is modelled rather than a step by step guide to using the graphical interface, API or data downloads.  

## Separation of Names and Taxa

The core principle that informed the design of Rhakhis was the separation of nomenclature from taxonomy. This is a  consequence of applying the rules of the __International Code of Nomenclature for algae, fungi, and plants__. Names are created via nomenclatural events (being validly published either for the first time or in a new combination) and bound to a type specimen (possibly via lectotypification). Taxa are created by authors using combinations of descriptions (characters) and example specimens. Names are bound to taxa via the rule of priority, the first published name who's type specimen falls within the taxon is the accepted name of that taxon (barring special instances of conservation).

**Analogy #1:** Each name is written on an index card. The card contains all the information of when and where that name was published and what the type specimen is. There are around 1.5 million of these index cards stored alphabetically. To build a taxonomy the name cards are taken and placed into a hierarchical set of folders, one folder for each taxon. Each folder has a key card which is the accepted name for that taxon. The other cards in the folder are the synonyms. Placing the cards in the folders does not affect what is written on them but what is written on them may govern which folders they can be placed in. e.g. If the genus part of the name does not match the genus folder in which it is placed as an accepted name.

**Analogy #2:** Each name is a Christmas tree decoration in a big box waiting to be hung on the tree. The taxonomy is the Christmas tree. We are all collaborating in decorating the tree!




### Side box: Taxon Concept Model
If you are familiar with the Taxon Concept models that were proposed at the turn of the century and the subsequent Taxon Concept Schema TDWG standard this approach will be familiar to you. From one perspective Rhakhis is NOT a taxon concept based approach as only a single classification is currently modelled and no attempt is made to delimit the taxa, either by listing specimens or with descriptions. From another perspective it IS a taxon concept based approach because their is an implied delimitation of the taxa based on the types of the names placed in them as well as those descriptions indexed as WFO content and the periodic, versioned data dumps provide identifiers for taxa in those dumps and relate them to each other. The concept based approach can either be embraced or ignored and it makes no difference to building a shared taxonomic backbone. 

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

> to withdraw official support for or discourage the use of

We use it for names that we believe have been created in error and that we can't attribute clear meaning to. __It is recommended that these names are not used in future for any purpose.__ They are maintained in the database for name matching purposes and so they can be resurrected in the future without creating new WFO IDs if more information is discovered.

The status deprecated is introduced to quell the plague of zombie names. These are names that may have occurred in the literature or a database just once and have subsequently been propagated from one list to the next without ever dying a natural death, just soaking up time and resources. Zombie names are particularly problematic in the age of big data. This is where they find peace.

### More on Names

The code's definition of a name includes the word "name" includes "name". 

> 6.3. In this Code, unless otherwise indicated, the word “name” means a name that has been validly published, whether it is legitimate or illegitimate (see Art. 12; but see Art. 14.9 and 14.14).

Unfortunately we don't know whether a name has been validly published or not before we have to start tracking it within Rhakhis. Even if we discover a name is not validly published but is in common usage (people reasonably assume it has been validly published or it is causing some confusion) then we need to take account of it. For clarity here are some of the terms we use when discussing names:

1.	__Name String:__  A string of characters that looks like it is an effective/validly published botanical name. Something in a database or publication.
1.	__Name parts:__ One, two or three words only. See above. Edge case is that hyphens are allowed in some of the words but two hyphenated words count as a word for our purposes.
1.	__Rank:__ A rank word from our controlled vocabulary of ranks. The controlled vocabulary keeps track of abbreviations and acts as a gate keeper for arbitrary ranks.
1.	__Authors String:__ The string of characters representing the author(s) of the name. This may follow standard author abbreviations but for our purposes here it is just a correctable string.
1.	__Publication String:__ The string of character representing the place of publication. May follow standards but is just a correctable string here. 
1.	__Name Record:__ A row in the database representing a name. Each row has a single prescribed WFO-ID. (It may also have deduplicated WFO-IDs, issued in error in the past, that resolve to it but only one that should be used going forward.)
1.	__WFO-ID:__ An identifier used to represent the Name Record in the wider world.
1.	__Homonyms:__ Two or more names that have the same Name Parts – ignoring rank, authors string and publication string for our purposes. For our purposes Isonyms are a subset of Homonyms although this is not the strict definition in the code. See not below.
1.	__Duplicates:__ Two or more Rank Records that have the same Name Parts, Authors String and Rank.


#### Homonyms and Isonyms

The code has the following note under point 6.3

> Note 2. When the same name, based on the same type, has been published independently at different times, perhaps by different authors, then only the earliest of these “isonyms” has nomenclatural status. The name is always to be cited from its original place of valid publication, and later isonyms may be disregarded (but see Art. 14.14).

In the glossary Isonym is defined as: 

> __isonym.__ The same name based on the same type, published independently at different times perhaps by different authors. Note: only the earliest isonym has nomenclatural status (Art. 6 Note 2; but see Art. 14.14).

The glossary defines homonym as:

> __homonym.__ A name spelled exactly like another name published for a taxon at the same rank based on a different type (Art. 53.1). Note: names of subdivisions of the same genus or of infraspecific taxa within the same species that are based on different types and have the same final epithet are homonyms, even if they differ in rank (Art. 53.3), because the rank-denoting term is not part of the name (Art. 21 Note 1 and Art. 24 Note 2) (see also confusingly similar names).

To distinguish between homonyms and isonyms we need to know the types of both names but won't know this until both names have been researched and we need to track the names in Rhakhis to facilitate that research. Until that point names are potential homonyms or isonyms and fit this description:

> A name spelled exactly like another name published for a taxon at the same rank, unless the name is a subdivision of genus or a species in which case the rank isn't taken into account.

 Unfortunately we don't have a word in the code for this class of names. Homonyms are far more common than isonyms (although putting a figure on that is hard without finding them all). Many isonyms are created by the author publishing the name again, perhaps in a paper and in a flora or catalogue, and so have the same Authors String and are unlikely to warrant a Name Record in Rhakhis or indeed cause any taxonomic confusion beyond correctly quoting the original place of publication. Indeed isonyms are the "same name" according to the code which implies they should only have one Name Record in Rhakhis. We therefore use the term homonym in this looser sense within Rhakhis to apply to names that have the same spelling, are probably homonyms _sensu stricto_ but might be isonyms. If they turn out to be isonyms their records will probably be merged unless there is a compelling reason to keep them.

 ## Aspiration: Unique Full Name String

> Rhakhis should not have records that have identical name-parts, rank and author string. If such records exist it is an error. If two records exist in this state we should merge one into the other or differentiate them by correcting one of them. We should prevent such records from being created.

 FIXME: Aspiration for each WFO to equate to a unique full name string of Does not make WFO-ID redundant as a WFO-ID only applies to one record and therefore one normative name string. A name string could be matched to multiple WFO-IDs based on approximation.

 #### FIXME: Notes for expansion

 Bring on the crazy edge cases!
 
1.	Spelling variants (correctable) – There are corrections specified in the code (e.g. 'ä' becomes 'ae'). These should be automatically corrected on the way into Rhakhis and on searching so are effectively the same string. Nothing to see here.
2.	Spelling variants (common) – It may be desirable to include common miss spellings of names as invalid or nom nud and synonymised to the correct spelling. But this is a judgement call and the subject of loads of work Mark has to do talking to people. It doesn’t affect the rule because, by definition, they have different name parts. (We could add a “spelling error” nomenclatural status but I’m reluctant to do that – a separate discussion).
3.	Aus bus Koch ex Koch implies we could also have Aus bus Koch as the root name but this doesn’t affect the rule. We could have two Name Records one with Koch (nom nud?) and one with Koch ex Koch and synonymise them. If someone searches for Aus bus Koch they would get it as a synonym of Aus bus Koch ex Koch if they didn’t just go straight to the original because Aus bus Koch is an accepted name. I see no issues.
4.	Author publishes same name with same type in multiple places - typically in a paper and in a flora account – maybe in same year.
a.	The flora account will typically have different authors and therefore a different authors string so it can have a different record and be synonymised.
b.	If the paper and flora account have exactly the same author and it is imperative to have two records then the superfluous record can be bastardised with and “in Flora Bulgaria” to differentiate it. (I struggle a bit with why we would want to include two records here. If the second publication of a name isn’t a nomenclatural act but merely a use of the name that people mistakenly believe was the initial publication then we aren’t talking about two names at all but a correction of the place of publication of one name. We wouldn’t track all errors in places of publication with new records! If it is a nomenclatural act then we would have an “ex” in the author string and so the rule applies and the earlier name would not be valid. See Koch ex Kock.)
 

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

### Unranked Names

We force the adoption of a rank. You can't have unranked names beyond a comment.

35.3. A new name or combination published before 1 January 1953 without a clear indication of its rank is validly published provided that all other requirements for valid publication are fulfilled; it is, however, inoperative in questions of priority except for homonymy (see Art. 53.4). If it is a new name, it may serve as a basionym for subsequent combinations or a replaced synonym for nomina nova in definite ranks.



## Overloading Basionym


## Authentication

There are two ways to interact with the Rhakhis system, either through the web based user interface or via the API. Both methods require the user to be authenticated. Authentication is not required to download data snapshots.

When accessing the data through the web UI users must log in with a valid ORCID ID. Authentication is handled in partnership with with ORCID.org so only a user name and ORCID ID are stored locally in Rhakhis. Anyone with a valid ORCID ID can log into Rhakhis and browse the data but they will not have authorization to change anything until it is granted by another user. 

The Rhakhis user account is created the first time user logs in with their ORCID ID. It is therefore not possible to grant a user editing rights until they have logged in at least once.

When a Rhakhis user account is created an API access token is also minted for that user. This access token can be used by scripts that interact with the API on behalf of the user. Any changes to the data made using this access token will appear to have been made by the user just as if they were changing the data through the web UI. The access tokens should therefore be kept secret and never shared!


## Authorization 

A user can be assigned as the curator of a taxon and this gives them rights to edit that taxon, any descendant taxa and any synonyms of those taxa.

If a user has the rights to edit a taxon they can also assign another user to be the curator of that taxon. If a user is made the curator of a family (so they can edit that family and everything it contains) they can assign a colleague to work on a genus within the family by making them the curator of that genus. Taxa have multiple editors so when a genus is assigned to a colleague the original user doesn't lose control of it. Both users can work on the genus together. Taxa can also have multiple curators so a team of user could work on a whole family if that were desired.

Unplaced names are not controlled by this authorization mechanism. An unplaced name can be edited by any user who is an editor of a taxon (any taxon) but once that name is placed within the taxonomic hierarchy it is controlled by the editors of the associated taxa and becomes "locked" into the consensus taxonomy.

## References

The scope of Rhakhis is nomenclature and taxonomy. We are working to make it as complete and authoritative as possible. Progress would be slower if we also tried build a system for managing ancillary data and, for example, incorporated a full citation manager or specimen catalogue. Those functions are better performed by other systems elsewhere on the internet. Certain classes of data are therefore handled by _References_ to other systems. These can be presented as decorated links in user interfaces and documents or can be explored by software agents.

A Reference in Rhakhis consists of the following fields:

1. __URI__ - A unique HTTP(s) web reference to another system on the internet. This includes DOIs in there HTTP form.
1. __Label__ - The display text to present to the user. For a book this might be a human readable version of the citation.
1. __Kind__ - The type of reference the URI points to. This can be one of: Person, Literature, Specimen or Database.
1. __Image URI (optional)__ - A link to a thumbnail image that may be useful to decorate the link. e.g. A low resolution image of the specimen, portrait of the person or the title page of a PDF.

References are normalized, there can be only one instance of a reference with its unique URI within Rhakhis. Multiple names can link to each reference.

Names are associated with References through name_references. These allow the relationship to have two properties:

1. __Comment__ - An explanation of how the reference applies specifically to this name. It could be something like "Holotype specimen" or "Only known mention of name" or "Author based on abbreviation in the authors string."
1. __Placement Related__ - A flag so indicate this reference is concerned with the taxonomic placement of the name rather than the nomenclatural status of the name. In the current interface reference links with this flag are presented in a yellow box called "Taxonomic Sources". Reference links without this flag appear in the gray box entitled "Nomenclatural References".

### What if I don't have a URI for my reference?

#### Database

If the database isn't available online then we can't link to it. If you have a heritage database that is unlikely to be made available online but can be stored in an archival way (e.g. CSV files not Microsoft Access) then you could consider submitting it to [Zenodo](https://zenodo.org/) for safe keeping. This will create a DOI for the dataset that you can then use as the link.

#### Literature

It is common to have a list of literature references with no URIs. Typically this is because they don't have DOIs because they are too old to have been given one. There are different approaches you could take:

1. If the publication is in the [Biodiversity Heritage Library](https://www.biodiversitylibrary.org/) you can link to it there. This can either be done using the BHL link or a DOI they have minted for their publications. There is a [BHL working group](https://blog.biodiversitylibrary.org/2021/05/persistent-identifier-working-group.html) on this.
1. Check if the treatment is in [Plazi TreatmentBank](https://plazi.org/treatmentbank/) and engage with them in getting literature submitted if possible. 
1. Link to an entry in [WikiData](https://www.wikidata.org/) for the article or book. If one doesn't exist you can create it. Put the page information in the name_reference comment if that is appropriate, e.g. the page in a book so you don't have to create a WikiData entry for every page.
1. If you can't or don't want to create article level entries in [WikiData](https://www.wikidata.org/) you can link to the publication (e.g. The book or journal) entry and include the details of the volume, page and article title in the name_reference comment.

We will work to come up with more detailed guidance on creating links to literature in the future.

#### Person

If the person is alive then you should use their [ORCID](https://orcid.org/) as the link. If they don't have one you could ask them to register. If they don't want to register then you can't link to them. As a human they have a right not to be involved.

If the person is historical (a.k.a. dead) then you can link to them in [WikiData](https://www.wikidata.org/), creating an entry if need be. Don't try and solve the problem of no ORCID by moving someone from the alive to historical. That wouldn't be ethical.

#### Specimens

We only provide the ability to cite specimens if they are available online as an entry in a catalogue or image. If you have an image of the specimen and it is unlikely to be put online by the holding institution then you might consider uploading it to [Zenodo](https://zenodo.org/) where it will be given a DOI that you can cite. If you only have text you can add it to the comments on the name.

### Taxonomic Sources

Each branch of the taxonomy within Rhakhis is supported by some external source. We therefore aim to have a Reference in the Taxonomic Source section for each accepted name that links to the authority we use to assert that taxon exists and has those synonyms. Ideally this will be a single database reference and/or a single literature reference although this isn't currently enforced by the system. An analogy is the linking to external sources for statements of fact in Wikipedia. Sometimes this Reference may be at a higher level within the taxonomy than the current taxon e.g. a single Taxonomic Source for a whole genus or family.

### Nomenclatural References

All names should have nomenclatural references. Eventually they will all have links to the original place of publication but it is appropriate to include any reference here that would be useful for someone researching the nomenclatural aspects of this name. These might include links to the authors and type specimens or nomenclatural databases (e.g. [IPNI](https://www.ipni.org/)) that contain such information. It would be inappropriate to have links here to simple occurrences of the name such as in a flora or occurrence database like [GBIF](https://www.gbif.org/) unless these were the only known source of the name and would be useful to figure out the place of publication etc. 








