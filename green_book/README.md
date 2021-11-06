# Integrity Rules for the WFO Taxonomic Backbone

(very much under construction)

1. Each name has a single, __prescribed WFO ID__. This is the ID that is used to refer to that name whether it occurs as the accepted name of a taxon, a synonym or remains unplaced.
1. Names may have multiple __deduplication WFO IDs__. When it is determined that two records represent the same real world name they are combined. One ID is chosen as the prescribed ID for the name and the other becomes a deduplication ID. Deduplication IDs should not used other than for the purpose of resolving to the prescribed ID.


## Taxonomic status of Names

A name can only have a single taxonomic status (play one role) in the system.
1. __Accepted Name__. A taxon can only have one of these and they have to agree with the rules regarding placement.
1. __Synonym__ A taxon can have many of these. (Note that whether synonyms are homotypic or heterotypic are tracked separately and discoverable through basionym links)
1. __Unplaced__ A name that isn't associated with a taxon as an accepted name or synonym. This may be because the name hasn't been researched yet (it is __unchecked__) or because the name is __illegitimate__ under the code (_nom. illeg._ or _nom. superfl._) or because there will never be sufficient information to place it anywhere (in which case it will have a comment to this effect).

## Nomeclatural status of Names

Names can have a nomenclatural status. This is separate from their taxonomic status (within the currently accepted taxonomy). Some of these statuses may preclude their use as accepted names of taxa.

1. illegitimate
1. later_homonym
1. superfluous
1. conserved
1. rejected
1. sanctioned
1. valid
1. invalid
1. deprecated

### Deprecated!

The nomenclatural status of __deprecated__ is introduced primarily as an internal device. This is not a nomenclatural status according to the botanical code. It is meant in the modern sense of the word particularly with regard to software:

"_to withdraw official support for or discourage the use of_"

We use it for names that we believe have been created in error and that we can't attribute clear meaning to. __It is recommended that these names are not used in future for any purpose.__ They are maintained in the database for name matching purposes and so they could be resurrected in the future if more information is discovered without creating new WFO IDs.

The status deprecated is introduced to quell the plague of zombie names. These are names that may have occurred in the literature or a database just once and have subsequently been propagated from one list to the next without ever dying a natural death, just soaking up time and resources. Zombie names are particularly problematic in the age of big data. This is where they find peace.

## Status of Taxa

All taxa are of the same status. If they occur in the taxonomy then they are accepted taxa. 

## There are never more than three words in a name in botanical nomenclature but more words maybe used when labelling taxa.

From the point of view of nomenclature subsubspecies and subvarieties are "children" of the species. This is because they could be placed in any subspecies or variety within that species  without changing their name. The rank isn't part of the name. The same rule applies for divisions of the genus.

See Article 53.3 of the code https://www.iapt-taxon.org/nomen/pages/main/art_53.html#Art53.3

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





