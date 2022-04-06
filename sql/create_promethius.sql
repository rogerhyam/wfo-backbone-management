-- MySQL dump 10.13  Distrib 8.0.20, for macos10.15 (x86_64)
--
-- Host: localhost    Database: promethius_B
-- ------------------------------------------------------
-- Server version	8.0.20

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `identifiers`
--

DROP TABLE IF EXISTS `identifiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `identifiers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name_id` int DEFAULT NULL COMMENT 'The name this is associated with. This can be null but only because we get into a loop if we can’t create a the name before the id or id before the name.',
  `value` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kind` enum('ipni','tpl','wfo','if','ten','tropicos','uri','uri_deprecated') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `name_id_idx` (`name_id`),
  KEY `value` (`value`) USING BTREE,
  KEY `kind` (`kind`) USING BTREE,
  CONSTRAINT `name_id` FOREIGN KEY (`name_id`) REFERENCES `names` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7525432 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `matching_hints`
--

DROP TABLE IF EXISTS `matching_hints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matching_hints` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name_id` int DEFAULT NULL,
  `hint` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `name_id_idx` (`name_id`),
  KEY `hint` (`hint`) USING BTREE,
  CONSTRAINT `hints_name_id` FOREIGN KEY (`name_id`) REFERENCES `names` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2629741 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `names`
--

DROP TABLE IF EXISTS `names`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `names` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Primary key for internal use.',
  `prescribed_id` int NOT NULL COMMENT 'The preferred WFO ID for this name. The name may have other IDs (WFO and otherwise) but these are stored in the other_ids table. ',
  `rank` enum('code','kingdom','phylum','class','order','family','genus','subgenus','section','series','species','subspecies','variety','subvariety','form','subform') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `genus` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `species` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name_alpha` varchar(100) COLLATE utf8mb4_general_ci GENERATED ALWAYS AS (trim(concat_ws(_utf8mb4' ',`genus`,`species`,`name`))) STORED,
  `authors` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year` int DEFAULT NULL COMMENT 'Year of publication\n',
  `status` enum('unknown','invalid','valid','illegitimate','superfluous','conserved','rejected','sanctioned','deprecated') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `citation_micro` varchar(800) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `citation_full` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `citation_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publication_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `basionym_id` int DEFAULT NULL,
  `comment` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `issue` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `change_log` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` int NOT NULL,
  `source` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this record was created',
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When this record was last modified\\\\\\\\n',
  PRIMARY KEY (`id`),
  UNIQUE KEY `identifier_id_UNIQUE` (`prescribed_id`) USING BTREE,
  KEY `editor_idx` (`user_id`),
  KEY `basionym_link_idx` (`basionym_id`),
  KEY `name` (`name`) USING BTREE,
  KEY `genus` (`genus`) USING BTREE,
  KEY `species` (`species`) USING BTREE,
  KEY `name_alpha` (`name_alpha`) USING BTREE,
  KEY `rank` (`rank`),
  CONSTRAINT `basionym_link` FOREIGN KEY (`basionym_id`) REFERENCES `names` (`id`),
  CONSTRAINT `names_editor` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `prescribed_id` FOREIGN KEY (`prescribed_id`) REFERENCES `identifiers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1351871 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `names_log`
--

DROP TABLE IF EXISTS `names_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `names_log` (
  `id` int NOT NULL COMMENT 'Primary key for internal use.',
  `prescribed_id` int NOT NULL COMMENT 'The preferred WFO ID for this name. The name may have other IDs (WFO and otherwise) but these are stored in the other_ids table. ',
  `rank` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `genus` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `species` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name_alpha` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `authors` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year` int DEFAULT NULL COMMENT 'Year of publication\n',
  `status` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `citation_micro` varchar(800) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `citation_full` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `citation_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publication_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `basionym_id` int DEFAULT NULL,
  `comment` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `issue` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `change_log` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` int NOT NULL,
  `source` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL COMMENT 'When this record was created',
  `modified` timestamp NULL DEFAULT NULL COMMENT 'When this record was last modified',
  KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stats_genera`
--

DROP TABLE IF EXISTS `stats_genera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stats_genera` (
  `name_id` int NOT NULL,
  `wfo` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phylum` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phylum_wfo` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `family` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `family_wfo` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order_wfo` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `taxa` int DEFAULT NULL,
  `taxa_with_editors` int DEFAULT NULL,
  `species` int DEFAULT NULL,
  `subspecies` int DEFAULT NULL,
  `variety` int DEFAULT NULL,
  `synonyms` int DEFAULT NULL,
  `syn_species` int DEFAULT NULL,
  `syn_subspecies` int DEFAULT NULL,
  `syn_variety` int DEFAULT NULL,
  `unplaced` int DEFAULT NULL,
  `unplaced_species` int DEFAULT NULL,
  `unplaced_subspecies` int DEFAULT NULL,
  `unplaced_variety` int DEFAULT NULL,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name_id`),
  KEY `name_id` (`name_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stats_genera_log`
--

DROP TABLE IF EXISTS `stats_genera_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stats_genera_log` (
  `name_id` int NOT NULL,
  `wfo` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phylum` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phylum_wfo` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `family` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `family_wfo` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order_wfo` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `taxa` int DEFAULT NULL,
  `taxa_with_editors` int DEFAULT NULL,
  `species` int DEFAULT NULL,
  `subspecies` int DEFAULT NULL,
  `variety` int DEFAULT NULL,
  `synonyms` int DEFAULT NULL,
  `syn_species` int DEFAULT NULL,
  `syn_subspecies` int DEFAULT NULL,
  `syn_variety` int DEFAULT NULL,
  `unplaced` int DEFAULT NULL,
  `unplaced_species` int DEFAULT NULL,
  `unplaced_subspecies` int DEFAULT NULL,
  `unplaced_variety` int DEFAULT NULL,
  `modified` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxa`
--

DROP TABLE IF EXISTS `taxa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `taxa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int DEFAULT NULL,
  `taxon_name_id` int DEFAULT NULL COMMENT 'The id of the name of this taxon. Note it has a unique index. Only one taxon per name.',
  `is_hybrid` tinyint(1) DEFAULT '0',
  `comment` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `issue` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `user_id` int NOT NULL,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When this record was last modified\\n',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this record was created',
  `source` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_id_UNIQUE` (`taxon_name_id`),
  KEY `editor_idx` (`user_id`),
  KEY `parentage_idx` (`parent_id`),
  CONSTRAINT `editor` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=347913 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxon_names`
--

DROP TABLE IF EXISTS `taxon_names`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `taxon_names` (
  `id` int NOT NULL AUTO_INCREMENT,
  `taxon_id` int NOT NULL,
  `name_id` int NOT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_id_UNIQUE` (`name_id`),
  KEY `taxon_id_idx` (`taxon_id`),
  CONSTRAINT `taxon_id` FOREIGN KEY (`taxon_id`) REFERENCES `taxa` (`id`),
  CONSTRAINT `taxon_name_id` FOREIGN KEY (`name_id`) REFERENCES `names` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=946593 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `uri` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('anonymous','nobody','editor','god') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `wfo_access_token` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orcid_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orcid_access_token` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orcid_refresh_token` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orcid_expires_in` int DEFAULT NULL,
  `orcid_raw` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_unique` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_taxa`
--

DROP TABLE IF EXISTS `users_taxa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_taxa` (
  `taxon_id` int NOT NULL,
  `user_id` int NOT NULL,
  KEY `pk` (`taxon_id`,`user_id`) USING BTREE,
  KEY `user_fk_idx` (`user_id`),
  CONSTRAINT `taxon_fk` FOREIGN KEY (`taxon_id`) REFERENCES `taxa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wfo_mint`
--

DROP TABLE IF EXISTS `wfo_mint`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wfo_mint` (
  `rank` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `next_id` bigint DEFAULT NULL,
  `max_id` bigint DEFAULT NULL,
  `start_id` bigint DEFAULT NULL,
  PRIMARY KEY (`rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-06 11:00:36
