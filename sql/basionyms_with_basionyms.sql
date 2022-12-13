/* 
	chained basionyms
*/
SELECT
	cni.`value` as "com_nov_id",
    com_novs.name_alpha as com_novs_name, com_novs.authors as com_novs_authors,
    bi.`value` as basionym_id,
    basionyms.name_alpha as basionym_name, 
    basionyms.authors as basionym_authors,
    cbi.`value` as "chained_basionym_id",
    chained_basionyms.name_alpha as "chained_basionym_name"
FROM `names` as com_novs 
JOIN `names` as basionyms on com_novs.basionym_id = basionyms.id
JOIN `names` AS chained_basionyms on basionyms.basionym_id = chained_basionyms.id
JOIN identifiers as bi ON basionyms.prescribed_id = bi.id
JOIN identifiers as cni ON com_novs.prescribed_id = cni.id
JOIN identifiers as cbi ON chained_basionyms.prescribed_id = cbi.id
where basionyms.basionym_id is not null