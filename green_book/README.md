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

## Status of Taxa

All taxa are of the same status. If they occur in the taxonomy then they are accepted taxa. 

