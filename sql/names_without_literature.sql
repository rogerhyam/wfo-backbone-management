SELECT 
trim(REGEXP_SUBSTR(n.citation_micro, '^[^0-9\(\\[]+')) as abr, 
#n.citation_micro,
#n.name_alpha
count(*) as n
FROM promethius_A.names as n
WHERE n.citation_micro is not null
AND n.id not in (
	SELECT n.id FROM `names` AS n
	JOIN name_references as nr on n.id = nr.name_id and nr.placement_related = 0
    JOIN `references` as r on nr.reference_id = r.id AND r.kind = 'literature'
)
#and n.citation_micro like 'Phytologia%'
group by abr
order by n desc


/* Count names with literature references */
SELECT `status`, count(*) num FROM `names` AS n
JOIN name_references as nr on n.id = nr.name_id and nr.placement_related = 0
JOIN `references` as r on nr.reference_id = r.id AND r.kind = 'literature'
group by `status`
with rollup
order by num desc



