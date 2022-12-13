/*
	species names don't match
*/
SELECT 
	i.`value` as subsp_wfo,
	ssn.name_alpha as subsp,
	ssn.`status` as subsp_status,
	sn.name_alpha as species_name
FROM taxa as st 
JOIN taxon_names as stn on st.taxon_name_id = stn.id # join to accepted name
join `names` as sn on sn.id = stn.name_id
join taxa as sst on sst.parent_id = st.id
join taxon_names as sstn on sst.taxon_name_id = sstn.id
join `names` as ssn on ssn.id = sstn.name_id
join `identifiers` as i on ssn.prescribed_id = i.id
where sn.`rank` = 'species'
and ssn.species != sn.`name`

