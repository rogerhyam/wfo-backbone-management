# Seed Data Mapping

This describes how the fields in the seed data used to initiate this system was mapped from a flat file into the database structure.

Seed data will be kept as a table in the db and we should be able to map back across to if we find something has been lost - but there will be no mistakes!

The import is done in phases (with appropriate tweaking):

1. Import all records to the names table. Those marked "doNotProcess" are set to status "deprecated" (script: import_botalista_seed.php)
1. Link up basionyms using the import_botalista_seed_basionyms.php script.
1. Create taxa and link them to names by crawling the parentNameUsageID tree down from Angiosperms (import_botalista_seed_taxa.php) - this will like the taxa to names for their accpeted names.
1. Link the synonyms up. (import_botalista_seed_synonyms.php). This uses the acceptednameusageId values to join names to the taxa we created above.
1. Not really an import but a merge of all the "deprecated" names that are definite duplicates.



## taxonID

Copied in to the identifiers table with kind 'wfo' where the name_id is set to the relevant names.id. In the names table the prescribed_id is set to the id in the identifiers table.

## scientificNameID

Copied to the identifiers table with a kind depending on a regex match against the contents. Currently only contains IPNI identifiers?

- IPNI match ```'^[0-9]{1,9}-[0-9]{1,2}$'```

## localID

Copied to the identifiers table with a kind 'ten' (because it is the local identifier at the associated TEN)

## scientificName

Parsed into the fields 'name', 'genus', 'species' in the names table.

## genus, specificEpithet & infraspecificEpithet

Used to double check parsing of scientificName string. Mismatches throw an error.

## family, subfamily, tribe, subtribe, subgenus & majorGroup

Added to the matching_hints table to help in future name matching. majorGroup is expanded to a word (Angiosperm, Bryophyte, Gymnosperm, Pteridophyte).

These fields are redundant because the same data should be stored in the parentage tree specified by the parentNameUsageID hierarchy. There shouldn't be any differences and if 
there were they would likely be very complex and it isn't clear how they would be resolved.The parentNameUsageID is therefore given priority and only if issues arise later will these be returned to.

## taxonRank

Mapped to a restricted enumeration of values ('phylum','class','order','family','genus','subgenus','section','series','species','subspecies','variety','form') and saved in 'rank' field or names table.

Not all the ranks listed in the contributors guide are found in the data and rules need to be established for all those that are included in the new db so they'll be added as they are discovered.

## scientificNameAuthorship

Copied to 'authors' in the names table.

## parentNameUsageID

Used in the taxon table to build the hierarchy of taxa

## verbatimTaxonRank

Ignored

## nomenclaturalStatus

Mapped to a restricted enumeration and stored in the 'status' field of the names table.

- __valid or Valid__ becomes valid in names.status
- __invalidum or Invalid or invalid__ becomes invalid in names.status
- __illegitimum or illegitimate or Illegitimate__ becomes illegitimate in names.status
- __conservandum or Conserved__  becomes conserved in names.status (a comment should be included as to what it is conserved over)
- __orthografia or Orthographic_Variant__ to be discuss (doesn't occur in seed data) 
- __rejiciendum or Rejected__  becomes rejected in names.status (only 3 names in test data) 
- __dubium or Doubtful__ this is the same as an unplaced/unassigned name and is ignored(doesn't occur in test data)


## namePublishedIn

##namePublishedInID

##taxonomicStatus
##acceptedNameUsageID
##originalNameUsageID
##nameAccordingToID

## taxonRemarks

Copied into the names.comments and the taxa.comments fields.


## created & modified

Ignored as they get new start dates on import.

## references & references1.0

References (which are all links) are treated as identifiers of kind 'uri' or 'uri_deprecated' with the choice being based on REGEX

- ```'^http://www\.theplantlist\.org'``` are all 'uri_deprecated'
- others are all 'uri'

## source

Copied into the names.comments field and FIXME in the taxa table.


## tplId

Added to the identifiers table with the kind 'tpl'

## genusHybridMarker && speciesHybridMarker

Hybrid flag in the taxa table.

## tropicosId

Added to the identifiers table with the kind 'tropicos'

## doNotProcess

Used during import to merge records together

## doNotProcess_reason

Added to the comments in identifiers table. i.e. this is why this WFO ID is a deduplication id.

## comments

Copied into the names.comments field.