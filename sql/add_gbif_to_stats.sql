ALTER TABLE `stats_genera` 
ADD COLUMN `gbif_gap_species` INT NULL AFTER `unplaced_variety`,
ADD COLUMN `gbif_gap_total_occurrences` INT NULL AFTER `gbif_gap_species`,
ADD COLUMN `gbif_gap_mean` FLOAT NULL AFTER `gbif_gap_total_occurrences`,
ADD COLUMN `gbif_gap_stddev` FLOAT NULL AFTER `gbif_gap_mean`;

ALTER TABLE `stats_genera_log` 
ADD COLUMN `gbif_gap_species` INT NULL AFTER `unplaced_variety`,
ADD COLUMN `gbif_gap_total_occurrences` INT NULL AFTER `gbif_gap_species`,
ADD COLUMN `gbif_gap_mean` FLOAT NULL AFTER `gbif_gap_total_occurrences`,
ADD COLUMN `gbif_gap_stddev` FLOAT NULL AFTER `gbif_gap_mean`;

