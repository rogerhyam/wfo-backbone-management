/*
    removal of name records that are the basionym
    of another name leaves some hanging links
    This should not occur any more.
*/
with  comb_novs as (SELECT * FROM `names` where basionym_id > 0),
bad_novs as (select comb_novs.id as id from comb_novs left join `names` as b on comb_novs.basionym_id = b.id where b.id is null)
update `names` as n join bad_novs on n.id = bad_novs.id set n.basionym_id = null;