/*
    This will reset the database during development and prior to reseeding.
    Obviously it will destroy the database once in production!
*/

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

truncate taxon_names;
truncate identifiers;
truncate matching_hints;
truncate `names`;

/* We leave the global root taxon in to bootstrap the tree */
delete FROM taxa where id != 39;

ALTER TABLE taxon_names AUTO_INCREMENT = 1;
ALTER TABLE identifiers AUTO_INCREMENT = 1;
ALTER TABLE matching_hints AUTO_INCREMENT = 1;
ALTER TABLE `names` AUTO_INCREMENT = 1;
ALTER TABLE taxon_names AUTO_INCREMENT = 1;
ALTER TABLE taxa AUTO_INCREMENT = 100;

SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;

/* reset the wfo id generator */
update promethius.wfo_mint set next_id = start_id;
