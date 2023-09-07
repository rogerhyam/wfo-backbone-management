drop table if exists kew.wcvp_comparison;
create table kew.wcvp_comparison 
SELECT 
    wfo.wfo, 
    wfo.phylum, 
    wfo.`name` as genus_name, 
    wfo.family as wfo_family,
    kew.family as kew_family, 
    kew.taxon_status as kew_status, 
    kew.genus_hybrid as hybrid,
    kew.ipni_id, 
    wfo.species as wfo_species,
    0 as  kew_species
FROM promethius.stats_genera AS wfo
LEFT JOIN 
	kew.wcvp AS kew 
    ON wfo.`name` = kew.`genus`
    AND kew.taxon_rank like 'Genus'
	AND kew.taxon_status in ("Accepted", 'Artificial Hybrid')
WHERE wfo.`role` = 'accepted'
AND wfo.`phylum` in ('Angiosperms', 'Pteridophytes', 'Gymnosperms')
UNION ALL 
/* add the ones that aren't in wfo */
SELECT
    wfo.wfo,
    wfo.phylum,
    kew.`genus` as genus_name,
    wfo.family as wfo_family,
    kew.family as kew_family,
    kew.taxon_status as kew_status,
    kew.genus_hybrid as hybrid,
    kew.ipni_id,
    wfo.species as wfo_species,
    0 as  kew_species
FROM kew.wcvp AS kew
LEFT JOIN 
	promethius.stats_genera AS wfo
    ON wfo.`name` = kew.`genus`
    AND wfo.`role` = 'accepted'
    AND wfo.`phylum` in ('Angiosperms', 'Pteridophytes', 'Gymnosperms')
WHERE kew.taxon_rank like 'Genus'
AND kew.taxon_status in ("Accepted", 'Artificial Hybrid')
AND wfo.`name` is null;

/* add in the counts */
with sp_count as 
(
	SELECT genus as genus_name, count(*) as n 
	FROM kew.wcvp 
	WHERE taxon_rank = 'Species'
	AND taxon_status in ("Accepted", 'Artificial Hybrid')
	group by genus
)
update kew.wcvp_comparison as c join sp_count as sp on c.genus_name = sp.genus_name
set c.kew_species = sp.n;

/* Summary stats */
SELECT count(distinct(genus_name)) into @total_distinct_genera FROM kew.wcvp_comparison;
SELECT count(distinct(genus_name)) into @common_distinct_genera FROM kew.wcvp_comparison WHERE wfo is not null && kew_status is not null;
SELECT count(distinct(genus_name)) into @wfo_distinct_genera FROM kew.wcvp_comparison where wfo is not null;
SELECT count(distinct(genus_name)) into @wfo_missing_genera FROM kew.wcvp_comparison where wfo is null && kew_status is not null;
SELECT count(distinct(genus_name)) into @kew_distinct_genera FROM kew.wcvp_comparison where kew_status is not null;
SELECT count(distinct(genus_name)) into @kew_missing_genera FROM kew.wcvp_comparison where wfo is not null && kew_status is null;
SELECT sum(if(wfo_species = kew_species, 1, 0)) into @sp_count_same FROM kew.wcvp_comparison WHERE wfo is not null && kew_status is not null;
SELECT sum(if(if(wfo_species = kew_species,1, if(wfo_species > kew_species,(wfo_species - kew_species)/wfo_species,(kew_species - wfo_species)/kew_species)) > 0.1,1,0)) INTO @sp_count_within_10_percent FROM kew.wcvp_comparison;

SELECT 
	@total_distinct_genera as 'Total distinct genus names',
    @wfo_distinct_genera as 'WFO genus count', 
    @wfo_missing_genera as 'WFO missing genera', 
    @kew_distinct_genera as 'Kew genus count', 
    @kew_missing_genera as 'Kew missing genera',
    @common_distinct_genera as 'Shared genera',
    @sp_count_same as 'Genera with the same species count',
    @sp_count_within_10_percent as 'Genera within 10% of same species count',
    100 - (((@kew_missing_genera + @wfo_missing_genera) / @total_distinct_genera) * 100) as '% of accepted genera in common',
    (@sp_count_within_10_percent/@common_distinct_genera) * 100 as '% of common genera within 10% species count',
    (@sp_count_within_10_percent/@total_distinct_genera) * 100 as '% total genera within 10% species count';

/*
    Discrepancies by family
*/
SELECT  concat_ws('-', wfo_family, kew_family) as 'family', count(*) as n 
FROM kew.wcvp_comparison 
where (kew_status is null or wfo is null)
group by concat_ws('-', wfo_family, kew_family)
order by n desc;

