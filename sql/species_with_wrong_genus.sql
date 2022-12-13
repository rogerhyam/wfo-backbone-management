/*
	genus names don't match
*/
SELECT gn.`name` as genus_name, sn.name_alpha as species_name, sn.`status`, i.`value` as wfo
FROM `names` as gn 
JOIN taxon_names AS gtn ON gn.id = gtn.name_id
JOIN taxa AS g ON g.taxon_name_id = gtn.id
JOIN taxa as s on s.parent_id = g.id # species join to their parents
JOIN taxon_names AS stn ON stn.id = s.taxon_name_id
JOIN `names` AS sn ON sn.id = stn.name_id
JOIN `identifiers` AS i ON sn.prescribed_id = i.id
WHERE gn.`rank` = 'genus'
AND sn.genus != gn.`name`
