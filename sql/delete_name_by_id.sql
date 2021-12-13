/*
during dev delete name and its identifiers.
*/

SET @name_id = '1351026';
SET FOREIGN_KEY_CHECKS = 0;
delete from identifiers where name_id = @name_id;
delete from `names` where id = @name_id;
delete from `taxon_names` where name_id = @name_id;
SET FOREIGN_KEY_CHECKS = 1;