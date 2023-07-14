drop table if exists kew.wcvp_comparison;
create table kew.wcvp_comparison 
SELECT 
wfo.wfo, wfo.phylum, wfo.`name`, wfo.family as wfo_family, kew.family as kew_family, kew.taxon_status as kew_status, kew.genus_hybrid as hybrid, kew.ipni_id
FROM promethius_A.stats_genera AS wfo
LEFT JOIN 
	kew.wcvp AS kew 
    ON wfo.`name` = kew.`genus`
    AND kew.taxon_rank like 'Genus'
	AND kew.taxon_status in ("Accepted", 'Artificial Hybrid')
WHERE wfo.`role` = 'accepted'
AND wfo.`phylum` in ('Angiosperms', 'Pteridophytes', 'Gymnosperms')
UNION ALL 
SELECT
 wfo.wfo, wfo.phylum, kew.`genus` as 'name', wfo.family as wfo_family, kew.family as kew_family, kew.taxon_status as kew_status, kew.genus_hybrid as hybrid, kew.ipni_id
FROM kew.wcvp AS kew
LEFT JOIN 
	promethius_A.stats_genera AS wfo
    ON wfo.`name` = kew.`genus`
    AND wfo.`role` = 'accepted'
    AND wfo.`phylum` in ('Angiosperms', 'Pteridophytes', 'Gymnosperms')
WHERE kew.taxon_rank like 'Genus'
AND kew.taxon_status in ("Accepted", 'Artificial Hybrid')
AND wfo.`name` is null

/* Summary stats */
SELECT 
	count(*) as total_genera,
    count(wfo) as wfo_genera, 
    count(*) - count(wfo) as not_in_wfo, 
    count(kew_status) as kew_genera, 
    count(*) - count(kew_status) as not_in_kew, 
	(count(*) - count(kew_status)) + (count(*) - count(wfo)) as discrepency,
    1 - ((count(*) - count(kew_status)) + (count(*) - count(wfo))) / count(*)  as agreement
FROM kew.wcvp_comparison;

/*
    Discrepancies
*/
SELECT  concat_ws('-', wfo_family, kew_family) as 'family', count(*) as n 
FROM kew.wcvp_comparison 
where (kew_status is null or wfo is null)
group by concat_ws('-', wfo_family, kew_family)
order by n desc;

