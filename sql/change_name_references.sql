/*
    These are the queries needed to 
    move from a simple flag for placement related
    to a role field. 
    
    This is only run during the update process.

*/

/* new column for the name_references table */
ALTER TABLE `name_references` 
ADD COLUMN `role` ENUM('nomenclatural', 'taxonomic', 'treatment') NOT NULL DEFAULT 'nomenclatural' AFTER `placement_related`;


/* set role based on placement flat */
UPDATE `name_references` SET `role` = 'taxonomic' WHERE placement_related = 1 and name_id > 0;

/* set role based on reference kind */
UPDATE name_references as nr 
JOIN `references` as r  ON nr.reference_id = r.id AND r.kind = 'treatment'
SET nr.`role` = 'treatment';

/*  remove the old field and update index - may take a while */
ALTER TABLE `name_references` 
DROP COLUMN `placement_related`,
DROP INDEX `key` ,
ADD INDEX `key` (`name_id` ASC, `reference_id` ASC, `role` ASC) VISIBLE;


/* Change the kind of treatment references to literature or database depending on URI kind */
UPDATE `references`
SET kind = 'database'
WHERE kind = 'treatment'
AND link_uri LIKE 'https://treatment.plazi.org/%'
AND id > 0;

UPDATE `references`
SET kind = 'literature'
WHERE kind = 'treatment'
AND link_uri LIKE 'https://doi.org/%'
AND id > 0;

UPDATE `references`
SET kind = 'literature'
WHERE kind = 'treatment'
AND link_uri LIKE 'https://data.rbge.org.uk/%'
AND id > 0;

/* remove treatment as a kind from the references table  - may take time to run */
ALTER TABLE `references` 
CHANGE COLUMN `kind` `kind` ENUM('person', 'literature', 'specimen', 'database') NULL DEFAULT NULL ;



