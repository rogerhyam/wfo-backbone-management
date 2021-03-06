/*
    This will reset the database during development and prior to reseeding.
    Obviously it will destroy the database once in production!
*/

use promethius_A;

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;
SET innodb_lock_wait_timeout = 600;

truncate matching_hints;

/* We leave the global root taxon in to bootstrap the tree */
delete FROM taxa where id != 1;
delete FROM `names` where id != 1;
delete FROM identifiers where id != 1;
delete FROM taxon_names where id != 1;

ALTER TABLE identifiers AUTO_INCREMENT = 2;
ALTER TABLE matching_hints AUTO_INCREMENT = 2;
ALTER TABLE `names` AUTO_INCREMENT = 2;
ALTER TABLE taxon_names AUTO_INCREMENT = 2;
ALTER TABLE taxa AUTO_INCREMENT = 100;

/* reset the wfo id generator */
update wfo_mint set next_id = start_id;

SET innodb_lock_wait_timeout = 100;
SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;

/* Just remove taxa */
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

delete FROM taxa where id != 1;
delete FROM taxon_names where id != 1;
ALTER TABLE taxon_names AUTO_INCREMENT = 2;
ALTER TABLE taxa AUTO_INCREMENT = 100;

SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;


