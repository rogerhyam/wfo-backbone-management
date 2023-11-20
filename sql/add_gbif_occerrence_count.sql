
/*
    Adding the occurrence counting table.
*/
CREATE TABLE `gbif_occurrence_count` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name_id` int NOT NULL,
  `count` int DEFAULT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `name_id` (`name_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=826 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
