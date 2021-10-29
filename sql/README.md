# SQL Resources

Resources related to the SQL database including CREATE scripts and models.

## Internal Data Model

The primary purpose of the internal data model is to maintain integrity of the data. It does not attempt to replicated an external data standard such as Darwin Core or ABCD. Data is mapped to and from external models on import and export.

The model takes a Taxon Concept based approach. This is not a version of the potential taxon concept as the intention is to only model the single, global consensus taxonomy. It does, however, strictly separate the notion of a name (as governed by the botanical code) from a taxon (as asserted by a taxonomic expert).

Where convenient data integrity is built into the model but not if it would make the model overly complex. Some integrity rules are maintained in import/export code.

## Tables

- __names__ holds purely nomenclatural data.
- __taxa__ holds the taxonomic tree. Parentage is linked within the table using the parent_id field.
- __taxon_names__ is a join table between taxa and names. It holds all names that are associated with a taxon whether those names are synonyms or the accepted name for the taxon. The id of the taxon_names row that represents the accepted name for a taxon is stored in the taxa.taxon_name_id field as a foreign key. Use of unique indexes enforces:
    1. Names can only occur once in a taxonomy (no matter what their role).
    1. Taxa can only have a single accepted name.
    1. Names can only be the accepted name of one taxon. 
- __identifiers__ stores all the WFO and other external identifiers used to refer to names.
    1. Identifiers can only occur once in the database.
    1. Names can have multiple identifiers.
    1. Identifiers can only belong to a single name.
    1. Names can have a prescribed identifiers linked to by the names.prescribed_id field.
- __users__ stores people and agents who make changes to the data. In addition most changes are also tracked as a 'source' this is the name of the dataset the change comes from but may not be the user making the change.
- __\*\_log__ tables shadow the main data tables. Rows in the data table are copied to the associated log table before the modifications are set by a BEFORE UPDATE trigger. This provided minimal change tracking.
- __matching_hints__ contains strings that might be used as hints when trying to match strings against the names. This is almost always likely to be a family name but could be any string. 


