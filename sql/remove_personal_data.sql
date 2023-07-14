/*
Remove the personal data (access keys) from the database ready for general release.
*/

SET SQL_SAFE_UPDATES = 0;
update users set wfo_access_token = null, orcid_access_token = null, orcid_refresh_token = null, orcid_expires_in = null, orcid_raw = null;
SET SQL_SAFE_UPDATES = 1;

/* 
Drop botalista as it is in the last versions and never changes and will eventually go
*/
drop table botalista_dump_2;
