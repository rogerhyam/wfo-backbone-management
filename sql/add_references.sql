CREATE TABLE `references` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kind` enum('person','literature','specimen','database') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `display_text` varchar(1000) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `link_uri` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `thumbnail_uri` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` int NOT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uri_unique` (`link_uri`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `name_references` (
  `name_id` int NOT NULL,
  `reference_id` int NOT NULL,
  `placement_related` tinyint NOT NULL DEFAULT '0',
  `comment` varchar(1000) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` int NOT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `key` (`name_id`,`reference_id`,`placement_related`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `names` 
DROP COLUMN `citation_id`,
DROP COLUMN `citation_full`;

ALTER TABLE `names_log` 
DROP COLUMN `citation_id`,
DROP COLUMN `citation_full`;
