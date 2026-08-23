-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: hospital_management_system
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `active_sessions`
--

DROP TABLE IF EXISTS `active_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `active_sessions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_at` datetime NOT NULL,
  `last_activity` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `active_department_id` int(11) DEFAULT NULL,
  `status` enum('Active','Terminated','Expired') NOT NULL DEFAULT 'Active',
  `terminated_at` datetime DEFAULT NULL,
  `terminated_by` int(11) DEFAULT NULL,
  `termination_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_active_sessions_session` (`session_id`),
  KEY `idx_sessions_user_status` (`user_id`,`status`),
  KEY `idx_sessions_activity` (`status`,`last_activity`),
  KEY `idx_sessions_expiry` (`status`,`expires_at`),
  KEY `idx_sessions_department` (`active_department_id`),
  KEY `fk_sessions_terminated_by` (`terminated_by`),
  CONSTRAINT `fk_sessions_department` FOREIGN KEY (`active_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sessions_terminated_by` FOREIGN KEY (`terminated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `active_sessions`
--

LOCK TABLES `active_sessions` WRITE;
/*!40000 ALTER TABLE `active_sessions` DISABLE KEYS */;
INSERT INTO `active_sessions` VALUES (1,'so0d745dl2krbnfo7jvarv3sb7',1,'2026-08-05 05:56:45','2026-08-05 05:56:45','2026-08-05 07:26:45','::1','curl/8.21.0',1,'Active',NULL,NULL,NULL,'2026-08-05 04:56:45'),(2,'cc94gd1l1002kmbiu8m21gkvnl',1,'2026-08-05 05:56:53','2026-08-05 05:57:21','2026-08-05 07:27:21','::1','curl/8.21.0',1,'Active',NULL,NULL,NULL,'2026-08-05 04:56:53'),(3,'9h66851plgduchjvs2fo02rnja',1,'2026-08-05 11:07:58','2026-08-05 11:09:53','2026-08-05 12:39:53','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'Terminated','2026-08-05 14:29:50',NULL,'User logout.','2026-08-05 10:07:58'),(4,'rdcj9cd2p9bmg2fgfdpusq1v49',1,'2026-08-05 14:30:10','2026-08-05 14:30:18','2026-08-05 16:00:18','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'Terminated','2026-08-05 21:39:46',NULL,'User logout.','2026-08-05 13:30:10'),(5,'kk7flcolaaalaurbmhlee50cpd',1,'2026-08-05 15:06:23','2026-08-05 15:06:25','2026-08-05 16:36:25','::1','curl/8.21.0',1,'Terminated','2026-08-05 15:06:25',NULL,'User logout.','2026-08-05 14:06:23'),(6,'gfl53ujueeska2p3up8er0ktfn',1,'2026-08-05 16:01:28','2026-08-05 16:01:29','2026-08-05 17:31:29','::1','curl/8.21.0',1,'Terminated','2026-08-05 16:22:35',1,'Terminated through security administration.','2026-08-05 15:01:28'),(7,'o3uuandeqrl1eetghnf0mu53la',1,'2026-08-05 16:18:04','2026-08-05 16:18:07','2026-08-05 17:48:07','::1','curl/8.21.0',1,'Terminated','2026-08-05 16:22:37',1,'Terminated through security administration.','2026-08-05 15:18:04'),(8,'58ohcr15jqs5fkm95g68jajuq4',1,'2026-08-05 16:22:33','2026-08-05 16:22:37','2026-08-05 17:52:37','::1','curl/8.21.0',1,'Terminated','2026-08-05 16:22:38',NULL,'User logout.','2026-08-05 15:22:33'),(9,'ur4al13i23rggi1all2vi3424t',1,'2026-08-05 17:51:53','2026-08-05 17:51:54','2026-08-05 19:21:54','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',1,'Active',NULL,NULL,NULL,'2026-08-05 16:51:53'),(10,'pskj3hjhfm5cju0fnhiuv8cldn',1,'2026-08-05 17:58:23','2026-08-05 17:58:23','2026-08-05 19:28:23','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',1,'Active',NULL,NULL,NULL,'2026-08-05 16:58:23'),(11,'dsfbidj99mio7edifklsdfstgl',1,'2026-08-05 18:07:38','2026-08-05 18:07:38','2026-08-05 19:37:38','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',1,'Active',NULL,NULL,NULL,'2026-08-05 17:07:38'),(12,'httjj6e3iidnsra7mjtt9thahp',1,'2026-08-05 21:39:55','2026-08-05 21:42:34','2026-08-05 23:12:34','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'Terminated','2026-08-06 07:20:26',NULL,'User logout.','2026-08-05 20:39:55'),(13,'to2gpu4vcovo0r02m4fbot7lad',1,'2026-08-06 07:21:23','2026-08-06 07:22:20','2026-08-06 08:52:20','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'Terminated','2026-08-07 03:34:58',NULL,'User logout.','2026-08-06 06:21:23'),(14,'cisd52ko9i90d84hl0n7dfqdsv',1,'2026-08-07 03:35:08','2026-08-07 03:45:28','2026-08-07 05:15:28','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'Terminated','2026-08-07 07:32:39',NULL,'User logout.','2026-08-07 02:35:08'),(15,'0nkv0lgs0rareirp80ov957pnf',1,'2026-08-07 07:32:48','2026-08-07 07:43:41','2026-08-07 09:13:41','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'Terminated','2026-08-07 08:17:07',NULL,'User logout.','2026-08-07 06:32:48'),(16,'cmku4righrntnfo93n796pvkqq',1,'2026-08-07 08:17:14','2026-08-07 08:18:13','2026-08-07 09:48:13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'Terminated','2026-08-07 18:09:46',NULL,'User logout.','2026-08-07 07:17:14'),(17,'s84lred0issqov9pr50cnuu2tq',1,'2026-08-07 18:09:56','2026-08-07 18:12:07','2026-08-07 19:42:07','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'Terminated','2026-08-08 04:54:10',NULL,'User logout.','2026-08-07 17:09:56'),(18,'uq5h3k2aghotpq3pmn0bd13msk',1,'2026-08-08 04:54:21','2026-08-08 04:56:54','2026-08-08 06:26:54','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'Terminated','2026-08-08 10:58:39',NULL,'User logout.','2026-08-08 03:54:21'),(19,'e4ass4s48jftqbk8lhi1q0um48',1,'2026-08-08 10:58:51','2026-08-08 12:30:50','2026-08-08 14:00:50','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-08 17:41:27',NULL,'User logout.','2026-08-08 09:58:51'),(20,'ehbonad9vrkfj32a3eevqdj4c5',5,'2026-08-08 12:15:44','2026-08-08 12:20:23','2026-08-08 13:50:23','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'Active',NULL,NULL,NULL,'2026-08-08 11:15:44'),(21,'rv8inhqj6p2jokcm1obm9u7gtv',4,'2026-08-08 12:22:24','2026-08-08 12:23:39','2026-08-08 13:53:39','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',5,'Active',NULL,NULL,NULL,'2026-08-08 11:22:24'),(22,'rklccibsprq46jrv1735r2g6dp',4,'2026-08-08 12:29:36','2026-08-08 12:31:07','2026-08-08 14:01:07','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',5,'Active',NULL,NULL,NULL,'2026-08-08 11:29:36'),(23,'j7nss7gu4551mqbkjcp7vugt65',1,'2026-08-08 17:41:47','2026-08-08 17:45:17','2026-08-08 19:15:17','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-08 21:01:16',NULL,'User logout.','2026-08-08 16:41:47'),(24,'too9d6cbd19gpfc4vq07fofqat',1,'2026-08-08 21:01:26','2026-08-08 21:11:10','2026-08-08 22:41:10','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-08 21:50:07',NULL,'User logout.','2026-08-08 20:01:26'),(25,'p5f9giaioisjknm5sgqfdnp7mk',1,'2026-08-08 21:50:16','2026-08-08 21:58:58','2026-08-08 23:28:58','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-09 04:18:34',NULL,'User logout.','2026-08-08 20:50:16'),(26,'6emfiq370fg8751jg6qjaqhufl',1,'2026-08-09 04:19:10','2026-08-09 04:34:21','2026-08-09 06:04:21','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-09 05:14:28',NULL,'User logout.','2026-08-09 03:19:10'),(27,'2gs35cf6p8fptg8h6f2csahs4q',1,'2026-08-09 05:14:42','2026-08-09 05:33:47','2026-08-09 07:03:47','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-09 20:32:06',NULL,'User logout.','2026-08-09 04:14:42'),(28,'1nafvqugt109saf69liqh62u24',1,'2026-08-09 20:32:21','2026-08-09 21:13:45','2026-08-09 22:43:45','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-09 22:26:40',NULL,'User logout.','2026-08-09 19:32:21'),(29,'2r8vtd3u5o41lj0ggodl4tmmff',1,'2026-08-09 22:27:00','2026-08-09 22:38:50','2026-08-10 00:08:50','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-09 23:48:21',NULL,'User logout.','2026-08-09 21:27:00'),(30,'vq0814ds5tq4kl7ksgqh7a9rm7',1,'2026-08-09 23:48:50','2026-08-10 00:01:21','2026-08-10 01:31:21','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-10 21:46:09',NULL,'User logout.','2026-08-09 22:48:50'),(31,'365dpe1h62er0pnj7qbo1s6ugk',1,'2026-08-10 21:46:19','2026-08-10 21:53:19','2026-08-10 23:23:19','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-10 23:23:12',NULL,'User logout.','2026-08-10 20:46:19'),(32,'nj8m0loib2vvs1bbhp8suj3t8g',1,'2026-08-10 23:23:23','2026-08-10 23:25:22','2026-08-11 00:55:22','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-11 00:27:45',NULL,'User logout.','2026-08-10 22:23:23'),(33,'hcghi63fdbjq249ubb1i9ugs49',1,'2026-08-11 00:27:58','2026-08-11 00:42:09','2026-08-11 02:12:09','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-11 10:10:19',NULL,'User logout.','2026-08-10 23:27:58'),(34,'tllub7bcr2qieqccfhqg15ips4',1,'2026-08-11 10:10:28','2026-08-11 10:41:40','2026-08-11 12:11:40','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-11 21:35:27',NULL,'User logout.','2026-08-11 09:10:28'),(35,'eiju8jpnn3aqiudgukv9pp8v3f',4,'2026-08-11 10:35:31','2026-08-11 10:39:39','2026-08-11 12:09:39','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',5,'Active',NULL,NULL,NULL,'2026-08-11 09:35:31'),(36,'3gb4di1lic4lb5me8dmpd0k6pk',1,'2026-08-11 21:35:38','2026-08-11 21:36:16','2026-08-11 23:06:16','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-11 22:35:46',NULL,'User logout.','2026-08-11 20:35:38'),(37,'s3r0ktnu4h8v09p0c0l2tk122e',1,'2026-08-11 22:35:56','2026-08-11 22:37:57','2026-08-12 00:07:57','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-11 23:48:49',NULL,'User logout.','2026-08-11 21:35:56'),(38,'q16g3287finlp8npjd8kcc4qth',1,'2026-08-11 23:49:12','2026-08-12 00:08:32','2026-08-12 01:38:32','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-12 12:58:22',NULL,'User logout.','2026-08-11 22:49:12'),(39,'62vco1amkf3ku47hg3rb10acg2',1,'2026-08-12 12:59:46','2026-08-12 13:54:46','2026-08-12 15:24:46','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-12 14:43:43',NULL,'User logout.','2026-08-12 11:59:46'),(40,'osj7qrk86tn3vouoc494hunntc',1,'2026-08-12 14:43:55','2026-08-12 14:45:21','2026-08-12 16:15:21','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-12 15:37:22',NULL,'User logout.','2026-08-12 13:43:55'),(41,'b9sjrtv1a41jpm87u8dsofrjvq',1,'2026-08-12 15:37:30','2026-08-12 17:23:23','2026-08-12 18:53:23','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-13 20:02:37',NULL,'User logout.','2026-08-12 14:37:30'),(42,'b6m6rbo9thqlpseni4vplflan7',1,'2026-08-13 20:03:03','2026-08-13 20:03:38','2026-08-13 21:33:38','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-14 02:20:33',NULL,'User logout.','2026-08-13 19:03:03'),(43,'3s9seh4g7url0kek7onrj3q4j6',1,'2026-08-14 02:21:09','2026-08-14 03:15:10','2026-08-14 04:45:09','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-14 04:10:17',NULL,'User logout.','2026-08-14 01:21:09'),(44,'a2lamuiefnro3suot2oaop5l25',1,'2026-08-14 04:13:16','2026-08-14 04:19:16','2026-08-14 05:49:16','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-14 08:11:47',NULL,'User logout.','2026-08-14 03:13:16'),(45,'c44hpp89i1qp881kd84ulpilqc',1,'2026-08-14 08:11:57','2026-08-14 08:14:19','2026-08-14 09:44:19','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-14 12:04:55',NULL,'User logout.','2026-08-14 07:11:57'),(46,'h4848os6ni4qrhc9tm0ftbpscc',1,'2026-08-14 12:05:03','2026-08-14 12:05:55','2026-08-14 13:35:55','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-14 13:15:47',NULL,'User logout.','2026-08-14 11:05:03'),(47,'v5843229v2aj84oa7majb3deq0',1,'2026-08-14 13:16:07','2026-08-14 13:16:20','2026-08-14 14:46:20','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-17 10:02:23',NULL,'User logout.','2026-08-14 12:16:07'),(48,'alt4ake37jojhme49rjfd343k6',1,'2026-08-17 10:02:39','2026-08-17 10:16:37','2026-08-17 11:46:37','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-19 11:51:01',NULL,'User logout.','2026-08-17 09:02:39'),(49,'905jbc9nr2ie3ogtl231f9gp9l',4,'2026-08-19 11:51:28','2026-08-19 12:05:27','2026-08-19 13:35:27','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',5,'Terminated','2026-08-19 14:09:56',NULL,'User logout.','2026-08-19 10:51:28'),(50,'m4evndh1fihnlhdi28drs46dmf',2,'2026-08-19 14:10:23','2026-08-19 14:11:06','2026-08-19 15:41:06','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',2,'Terminated','2026-08-19 14:46:00',NULL,'User logout.','2026-08-19 13:10:23'),(51,'kqeaq43g82o0fiu7skvd8efa3n',2,'2026-08-19 14:37:53','2026-08-19 14:40:52','2026-08-19 16:10:52','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',2,'Terminated','2026-08-19 14:41:00',NULL,'User logout.','2026-08-19 13:37:53'),(52,'8h500ldoemhras5j66u9dqj6bo',4,'2026-08-19 14:41:24','2026-08-19 14:44:26','2026-08-19 16:14:26','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',5,'Terminated','2026-08-19 14:44:33',NULL,'User logout.','2026-08-19 13:41:24'),(53,'me8u7175ss9smvj479cvpgn9i3',2,'2026-08-19 14:44:54','2026-08-19 14:54:07','2026-08-19 16:24:07','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',2,'Active',NULL,NULL,NULL,'2026-08-19 13:44:54'),(54,'iq8cdrh8s9fum124g55tm8opji',1,'2026-08-19 14:46:17','2026-08-19 14:56:20','2026-08-19 16:26:20','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-21 08:11:06',NULL,'User logout.','2026-08-19 13:46:17'),(55,'ace4fvcsg7483a7bd1pbjeaatq',1,'2026-08-21 08:11:45','2026-08-21 08:21:18','2026-08-21 09:51:18','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-22 07:04:02',NULL,'User logout.','2026-08-21 07:11:45'),(56,'lqpvgfpeq6pphpthmb4fpbj7i0',1,'2026-08-22 07:04:12','2026-08-22 07:57:07','2026-08-22 09:27:07','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-23 08:06:54',NULL,'User logout.','2026-08-22 06:04:12'),(57,'m913b63f9ln7vk8gaevj2ngj3i',4,'2026-08-23 08:07:12','2026-08-23 09:47:02','2026-08-23 11:17:02','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',5,'Terminated','2026-08-23 09:47:34',NULL,'User logout.','2026-08-23 07:07:12'),(58,'oors78a6odcs55fsqvig4dtodb',4,'2026-08-23 09:51:04','2026-08-23 09:51:04','2026-08-23 11:21:04','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',5,'Terminated','2026-08-23 09:51:11',NULL,'User logout.','2026-08-23 08:51:04'),(59,'6re2ngmdnr5hl7a346enanm196',5,'2026-08-23 09:51:23','2026-08-23 09:51:24','2026-08-23 11:21:24','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'Terminated','2026-08-23 09:51:27',NULL,'User logout.','2026-08-23 08:51:23'),(60,'0uesve4rlu6ugrshj5tdo730de',1,'2026-08-23 09:56:47','2026-08-23 10:00:12','2026-08-23 11:30:12','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-23 10:00:34',NULL,'User logout.','2026-08-23 08:56:47'),(61,'hfip024gs7ehdmoujq83qdh4qp',1,'2026-08-23 10:02:16','2026-08-23 10:03:47','2026-08-23 11:33:47','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-23 10:11:48',NULL,'User logout.','2026-08-23 09:02:16'),(62,'9l7lbcmal67bme9p9cutnd7if5',1,'2026-08-23 10:12:47','2026-08-23 10:18:08','2026-08-23 11:48:08','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-23 10:19:40',NULL,'User logout.','2026-08-23 09:12:47'),(63,'ru4tek54hk5rr5pau6rd6vjc37',2,'2026-08-23 10:19:59','2026-08-23 10:24:19','2026-08-23 11:54:19','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',2,'Terminated','2026-08-23 10:25:15',NULL,'User logout.','2026-08-23 09:19:59'),(64,'nsnir7rtgnotm102ce1m97als5',1,'2026-08-23 10:25:42','2026-08-23 10:27:45','2026-08-23 11:57:45','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-23 10:31:18',NULL,'User logout.','2026-08-23 09:25:42'),(65,'b2k2v5106am4a80nr9sjcp8pfn',2,'2026-08-23 10:31:37','2026-08-23 10:32:07','2026-08-23 12:02:07','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',2,'Terminated','2026-08-23 10:34:07',NULL,'User logout.','2026-08-23 09:31:37'),(66,'6qofcrrcqpf748b41v2lhd0qp9',1,'2026-08-23 10:45:05','2026-08-23 10:47:24','2026-08-23 12:17:24','10.183.224.58','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36',1,'Active',NULL,NULL,NULL,'2026-08-23 09:45:05'),(67,'0tosoho45jgmvmabb85emfdk8d',1,'2026-08-23 10:48:50','2026-08-23 10:49:36','2026-08-23 12:19:36','10.183.224.20','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',1,'Active',NULL,NULL,NULL,'2026-08-23 09:48:50'),(68,'f1kf78ea44oiqks91mivhr7cpu',1,'2026-08-23 10:50:47','2026-08-23 10:55:07','2026-08-23 12:25:07','10.183.224.20','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',1,'Active',NULL,NULL,NULL,'2026-08-23 09:50:47'),(69,'arn7tjo4of7c5osg3ajgt4sh4i',3,'2026-08-23 11:05:16','2026-08-23 11:11:02','2026-08-23 12:41:02','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',3,'Terminated','2026-08-23 11:11:52',NULL,'User logout.','2026-08-23 10:05:16'),(70,'2n2tk3snfm9fi3f4o905trvjl1',1,'2026-08-23 11:12:09','2026-08-23 11:14:26','2026-08-23 12:44:26','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'Terminated','2026-08-23 11:15:20',NULL,'User logout.','2026-08-23 10:12:09'),(71,'oinvio8a263q75n7vl65prbj9a',26,'2026-08-23 11:15:35','2026-08-23 11:18:15','2026-08-23 12:48:15','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'Terminated','2026-08-23 11:19:12',NULL,'User logout.','2026-08-23 10:15:35'),(72,'9pj0g0iifc1ln3gc6uanvm6m9t',2,'2026-08-23 11:19:24','2026-08-23 11:20:21','2026-08-23 12:50:21','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',2,'Terminated','2026-08-23 11:20:32',NULL,'User logout.','2026-08-23 10:19:24'),(73,'nioa8ct4ult4etv7a9ave275lo',26,'2026-08-23 11:20:46','2026-08-23 11:34:39','2026-08-23 13:04:39','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'Active',NULL,NULL,NULL,'2026-08-23 10:20:46');
/*!40000 ALTER TABLE `active_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admission_movements`
--

DROP TABLE IF EXISTS `admission_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admission_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `from_ward_id` int(11) DEFAULT NULL,
  `from_bed_id` int(11) DEFAULT NULL,
  `to_ward_id` int(11) DEFAULT NULL,
  `to_bed_id` int(11) DEFAULT NULL,
  `movement_type` enum('Admission','Transfer','Discharge','Cancel') NOT NULL,
  `reason` text DEFAULT NULL,
  `performed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admission_movements_admission` (`admission_id`),
  KEY `idx_admission_movements_visit` (`visit_id`),
  KEY `idx_admission_movements_patient` (`patient_id`),
  KEY `idx_admission_movements_created_at` (`created_at`),
  KEY `fk_admission_movements_from_ward` (`from_ward_id`),
  KEY `fk_admission_movements_from_bed` (`from_bed_id`),
  KEY `fk_admission_movements_to_ward` (`to_ward_id`),
  KEY `fk_admission_movements_to_bed` (`to_bed_id`),
  KEY `fk_admission_movements_performed_by` (`performed_by`),
  CONSTRAINT `fk_admission_movements_admission` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_admission_movements_from_bed` FOREIGN KEY (`from_bed_id`) REFERENCES `ward_beds` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_admission_movements_from_ward` FOREIGN KEY (`from_ward_id`) REFERENCES `wards` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_admission_movements_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_admission_movements_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_admission_movements_to_bed` FOREIGN KEY (`to_bed_id`) REFERENCES `ward_beds` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_admission_movements_to_ward` FOREIGN KEY (`to_ward_id`) REFERENCES `wards` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_admission_movements_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admission_movements`
--

LOCK TABLES `admission_movements` WRITE;
/*!40000 ALTER TABLE `admission_movements` DISABLE KEYS */;
INSERT INTO `admission_movements` VALUES (1,1,10,8,NULL,NULL,1,1,'Admission','checking for signs',4,'2026-08-23 08:17:06');
/*!40000 ALTER TABLE `admission_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admissions`
--

DROP TABLE IF EXISTS `admissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `ward_id` int(11) NOT NULL,
  `bed_id` int(11) NOT NULL,
  `admission_type` enum('Emergency','Elective','Transfer','Observation') NOT NULL DEFAULT 'Emergency',
  `admission_diagnosis` text DEFAULT NULL,
  `admission_notes` text DEFAULT NULL,
  `status` enum('Admitted','Transferred','Discharged','Cancelled') NOT NULL DEFAULT 'Admitted',
  `admitted_by` int(11) NOT NULL,
  `admitted_at` datetime NOT NULL,
  `discharged_by` int(11) DEFAULT NULL,
  `discharged_at` datetime DEFAULT NULL,
  `discharge_destination` varchar(120) DEFAULT NULL,
  `discharge_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admissions_visit` (`visit_id`),
  KEY `idx_admissions_patient` (`patient_id`),
  KEY `idx_admissions_ward` (`ward_id`),
  KEY `idx_admissions_bed` (`bed_id`),
  KEY `idx_admissions_status` (`status`),
  KEY `idx_admissions_admitted_at` (`admitted_at`),
  KEY `idx_admissions_discharged_at` (`discharged_at`),
  KEY `fk_admissions_admitted_by` (`admitted_by`),
  KEY `fk_admissions_discharged_by` (`discharged_by`),
  CONSTRAINT `fk_admissions_admitted_by` FOREIGN KEY (`admitted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_admissions_bed` FOREIGN KEY (`bed_id`) REFERENCES `ward_beds` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_admissions_discharged_by` FOREIGN KEY (`discharged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_admissions_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_admissions_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_admissions_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admissions`
--

LOCK TABLES `admissions` WRITE;
/*!40000 ALTER TABLE `admissions` DISABLE KEYS */;
INSERT INTO `admissions` VALUES (1,10,8,1,1,'Observation','checking','checking for signs','Admitted',4,'2026-08-17 09:16:00',NULL,NULL,NULL,NULL,'2026-08-23 08:17:06',NULL);
/*!40000 ALTER TABLE `admissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `visit_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `module` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `severity` varchar(20) NOT NULL DEFAULT 'INFO',
  `event_type` varchar(100) NOT NULL DEFAULT 'GENERAL',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_visit` (`visit_id`),
  KEY `idx_audit_patient_created` (`patient_id`,`created_at`),
  KEY `idx_audit_module` (`module`),
  KEY `idx_audit_created` (`created_at`),
  KEY `idx_audit_action_created` (`action`,`created_at`),
  KEY `idx_audit_user_created` (`user_id`,`created_at`),
  KEY `idx_audit_ip_created` (`ip_address`,`created_at`),
  KEY `idx_audit_department_created` (`department_id`,`created_at`),
  KEY `idx_audit_module_created` (`module`,`created_at`),
  KEY `idx_audit_event_created` (`event_type`,`created_at`),
  KEY `idx_audit_severity_created` (`severity`,`created_at`),
  KEY `idx_audit_visit_created` (`visit_id`,`created_at`),
  CONSTRAINT `fk_audit_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_audit_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_audit_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=821 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (3,1,1,NULL,'Encounter','CREATE','Encounter created and patient received in Reception.','UNKNOWN',NULL,NULL,'INFO','CREATE','2026-08-05 04:56:02'),(4,1,1,NULL,'Queue','ENQUEUE','Encounter added to the department queue.','UNKNOWN',NULL,NULL,'INFO','ENQUEUE','2026-08-05 04:56:02'),(5,1,1,NULL,'Queue','CALL','Encounter called from the department queue.','UNKNOWN',NULL,NULL,'INFO','CALL','2026-08-05 04:56:02'),(6,1,1,NULL,'Queue','START_SERVICE','Encounter service started.','UNKNOWN',NULL,NULL,'INFO','START_SERVICE','2026-08-05 04:56:02'),(7,1,1,NULL,'Queue','COMPLETE_SERVICE','Encounter service completed. Remarks: Controlled reconstruction verification.','UNKNOWN',NULL,NULL,'INFO','COMPLETE_SERVICE','2026-08-05 04:56:02'),(8,1,1,NULL,'Visits','TRANSFER','Encounter transferred from 2 to Doctor.','UNKNOWN',NULL,NULL,'INFO','TRANSFER','2026-08-05 04:56:02'),(9,1,1,NULL,'Queue','ENQUEUE','Encounter added to the department queue.','UNKNOWN',NULL,NULL,'INFO','ENQUEUE','2026-08-05 04:56:02'),(10,1,1,NULL,'Visits','RECEIVE','Patient received in Doctor department.','UNKNOWN',NULL,NULL,'INFO','RECEIVE','2026-08-05 04:56:02'),(11,1,1,NULL,'Encounter','ASSIGN_DOCTOR','Doctor assigned: Amara Okafor.','UNKNOWN',NULL,NULL,'INFO','ASSIGN_DOCTOR','2026-08-05 04:56:02'),(12,1,1,NULL,'Queue','STATUS_QUEUE_CLOSE','Queue entry closed because the encounter was closed with status Completed.','UNKNOWN',NULL,NULL,'INFO','STATUS_QUEUE_CLOSE','2026-08-05 04:56:02'),(13,1,1,NULL,'Encounter','STATUS_CHANGED','Encounter status changed to Completed.','UNKNOWN',NULL,NULL,'INFO','STATUS_CHANGED','2026-08-05 04:56:02'),(14,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','curl/8.21.0',1,'INFO','SESSION_CREATED','2026-08-05 04:56:45'),(15,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','curl/8.21.0',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 04:56:45'),(16,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','curl/8.21.0',1,'INFO','SESSION_CREATED','2026-08-05 04:56:53'),(17,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','curl/8.21.0',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 04:56:53'),(18,1,NULL,NULL,'Security','PASSWORD_CHANGED','User password changed.','::1','curl/8.21.0',NULL,'INFO','PASSWORD_CHANGED','2026-08-05 04:57:06'),(19,1,NULL,NULL,'Administration','ADMIN_DASHBOARD_VIEWED','Administrator dashboard viewed.','::1','curl/8.21.0',NULL,'INFO','ADMIN_DASHBOARD_VIEWED','2026-08-05 04:57:06'),(20,1,NULL,NULL,'Security','SECURITY_REPORT_VIEWED','Viewed the security dashboard.','::1','curl/8.21.0',1,'INFO','SECURITY_REPORT_VIEWED','2026-08-05 04:57:07'),(21,1,1,NULL,'Security','TRANSFER_ACCESS_DENIED','You do not have permission to transfer this encounter.','::1','curl/8.21.0',NULL,'INFO','TRANSFER_ACCESS_DENIED','2026-08-05 04:57:20'),(22,1,1,NULL,'Security','ASSIGN_DOCTOR_ACCESS_DENIED','You do not have permission to assign a doctor to this encounter.','::1','curl/8.21.0',NULL,'INFO','ASSIGN_DOCTOR_ACCESS_DENIED','2026-08-05 04:57:21'),(23,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'WARNING','LOGIN_FAILED','2026-08-05 06:36:26'),(24,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',NULL,'WARNING','LOGIN_FAILED','2026-08-05 07:27:20'),(25,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'WARNING','LOGIN_FAILED','2026-08-05 10:01:01'),(26,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'WARNING','LOGIN_FAILED','2026-08-05 10:01:21'),(27,2,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'WARNING','LOGIN_FAILED','2026-08-05 10:01:50'),(28,3,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'WARNING','LOGIN_FAILED','2026-08-05 10:02:07'),(29,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #1.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:48'),(30,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #2.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(31,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #3.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(32,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #4.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(33,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #5.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(34,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #6.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(35,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #7.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(36,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #8.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(37,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #9.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:50'),(38,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-05 10:07:58'),(39,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 10:07:58'),(40,1,NULL,NULL,'Security','PASSWORD_CHANGED','User password changed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','PASSWORD_CHANGED','2026-08-05 10:09:20'),(41,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',NULL,'WARNING','LOGIN_FAILED','2026-08-05 11:19:26'),(42,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',NULL,'WARNING','LOGIN_FAILED','2026-08-05 11:19:50'),(43,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-05 13:29:50'),(44,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-05 13:30:10'),(45,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 13:30:10'),(46,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','curl/8.21.0',NULL,'WARNING','LOGIN_FAILED','2026-08-05 13:50:58'),(47,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','curl/8.21.0',1,'INFO','SESSION_CREATED','2026-08-05 14:06:23'),(48,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','curl/8.21.0',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 14:06:23'),(49,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','curl/8.21.0',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-05 14:06:24'),(50,1,NULL,NULL,'Security','INVALID_CSRF','Security validation failed. Please submit the form again.','::1','curl/8.21.0',NULL,'INFO','INVALID_CSRF','2026-08-05 14:06:25'),(51,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','curl/8.21.0',1,'INFO','SESSION_TERMINATED','2026-08-05 14:06:25'),(52,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','curl/8.21.0',1,'INFO','SESSION_CREATED','2026-08-05 15:01:28'),(53,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','curl/8.21.0',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 15:01:28'),(54,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','curl/8.21.0',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-05 15:01:28'),(55,1,NULL,NULL,'Security','INVALID_CSRF','Security validation failed. Please submit the form again.','::1','curl/8.21.0',NULL,'INFO','INVALID_CSRF','2026-08-05 15:01:29'),(56,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','curl/8.21.0',1,'INFO','SESSION_CREATED','2026-08-05 15:18:04'),(57,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','curl/8.21.0',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 15:18:04'),(58,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','curl/8.21.0',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-05 15:18:07'),(59,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','curl/8.21.0',1,'INFO','SESSION_CREATED','2026-08-05 15:22:33'),(60,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','curl/8.21.0',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 15:22:33'),(61,1,NULL,NULL,'Security','SESSION_TERMINATED','Terminated session #6.','::1','curl/8.21.0',1,'INFO','SESSION_TERMINATED','2026-08-05 15:22:35'),(62,1,NULL,NULL,'Security','SESSION_TERMINATED','Terminated session #7.','::1','curl/8.21.0',1,'INFO','SESSION_TERMINATED','2026-08-05 15:22:37'),(63,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','curl/8.21.0',1,'INFO','SESSION_TERMINATED','2026-08-05 15:22:38'),(64,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',1,'INFO','SESSION_CREATED','2026-08-05 16:51:53'),(65,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 16:51:53'),(66,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',1,'INFO','SESSION_CREATED','2026-08-05 16:58:23'),(67,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 16:58:23'),(68,1,NULL,NULL,'Security','INVALID_CSRF','Security validation failed. Please submit the form again.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',NULL,'INFO','INVALID_CSRF','2026-08-05 16:58:23'),(69,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',1,'INFO','SESSION_CREATED','2026-08-05 17:07:38'),(70,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 17:07:38'),(71,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-05 20:39:46'),(72,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-05 20:39:55'),(73,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 20:39:55'),(74,1,2,NULL,'Encounter','CREATE','Encounter created and patient received in Doctor.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CREATE','2026-08-05 20:41:24'),(75,1,2,NULL,'Queue','ENQUEUE','Encounter added to the department queue.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','ENQUEUE','2026-08-05 20:41:24'),(76,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-05 20:41:24'),(77,1,2,NULL,'Encounter','ASSIGN_DOCTOR','Doctor assigned: Amara Okafor.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','ASSIGN_DOCTOR','2026-08-05 20:42:33'),(78,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-05 20:42:34'),(79,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-06 06:20:26'),(80,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'WARNING','LOGIN_FAILED','2026-08-06 06:21:13'),(81,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-06 06:21:23'),(82,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-06 06:21:23'),(83,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-07 02:34:58'),(84,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-07 02:35:08'),(85,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-07 02:35:08'),(86,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 02:38:46'),(87,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 02:40:18'),(88,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 02:41:53'),(89,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 02:42:42'),(90,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 02:43:12'),(91,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 02:43:53'),(92,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 02:44:07'),(93,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 02:44:23'),(94,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 02:44:48'),(95,1,NULL,NULL,'Administration','ADMIN_DASHBOARD_VIEWED','Administrator dashboard viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','ADMIN_DASHBOARD_VIEWED','2026-08-07 02:45:17'),(96,1,NULL,NULL,'Security','SECURITY_REPORT_VIEWED','Viewed the security dashboard.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SECURITY_REPORT_VIEWED','2026-08-07 02:45:25'),(97,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-07 06:32:39'),(98,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-07 06:32:48'),(99,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-07 06:32:48'),(100,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-07 07:17:07'),(101,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-07 07:17:14'),(102,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-07 07:17:14'),(103,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 07:17:46'),(104,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 07:18:13'),(105,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-07 17:09:46'),(106,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-07 17:09:56'),(107,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-07 17:09:56'),(108,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 17:10:23'),(109,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 17:10:47'),(110,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-07 17:11:00'),(111,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-08 03:54:10'),(112,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-08 03:54:21'),(113,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-08 03:54:21'),(114,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 03:55:56'),(115,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 03:56:30'),(116,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 03:56:48'),(117,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 03:56:54'),(118,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-08 09:58:39'),(119,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-08 09:58:39'),(120,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-08 09:58:51'),(121,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-08 09:58:51'),(122,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 09:59:17'),(123,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 09:59:28'),(124,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:04:05'),(125,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:04:10'),(126,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:13:16'),(127,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:13:24'),(128,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:14:46'),(129,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:15:02'),(130,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:15:23'),(131,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:15:36'),(132,NULL,NULL,NULL,'Security','INVALID_CSRF','Authentication request failed CSRF validation.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','INVALID_CSRF','2026-08-08 10:19:17'),(133,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:19:54'),(134,1,2,2,'Consultation','CONSULTATION_CREATED','Created consultation #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','CONSULTATION_CREATED','2026-08-08 10:20:59'),(135,1,2,2,'Consultation','CONSULTATION_UPDATED','Updated consultation #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','CONSULTATION_UPDATED','2026-08-08 10:21:16'),(136,1,2,2,'Consultation','CONSULTATION_COMPLETED','Completed consultation #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','CONSULTATION_COMPLETED','2026-08-08 10:21:20'),(137,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:21:35'),(138,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:22:15'),(139,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:22:24'),(140,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:23:11'),(141,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:23:18'),(142,1,2,2,'Department Notifications','DEPARTMENT_NOTIFICATION_SENT','Sent department notification #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','DEPARTMENT_NOTIFICATION_SENT','2026-08-08 10:24:15'),(143,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:24:15'),(144,1,2,2,'Department Notifications','DEPARTMENT_NOTIFICATION_SENT','Sent department notification #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','DEPARTMENT_NOTIFICATION_SENT','2026-08-08 10:24:53'),(145,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:24:53'),(146,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:25:02'),(147,1,2,2,'Department Notifications','DEPARTMENT_NOTIFICATION_READ','Marked department notification #2 as read.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','DEPARTMENT_NOTIFICATION_READ','2026-08-08 10:25:47'),(148,1,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:26:05'),(149,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:26:28'),(150,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:34:11'),(151,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:35:52'),(152,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:40:48'),(153,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:41:00'),(154,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:41:27'),(155,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:42:00'),(156,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:43:47'),(157,1,2,NULL,'Queue','STATUS_QUEUE_CLOSE','Queue entry closed because the encounter was closed with status Completed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','STATUS_QUEUE_CLOSE','2026-08-08 10:43:52'),(158,1,2,NULL,'Encounter','STATUS_CHANGED','Encounter status changed to Completed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','STATUS_CHANGED','2026-08-08 10:43:52'),(159,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:43:52'),(160,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:43:59'),(161,1,3,NULL,'Encounter','CREATE','Encounter created and patient received in Doctor.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CREATE','2026-08-08 10:44:27'),(162,1,3,NULL,'Queue','ENQUEUE','Encounter added to the department queue.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','ENQUEUE','2026-08-08 10:44:27'),(163,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:44:27'),(164,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:44:40'),(165,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:49:33'),(166,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:49:44'),(167,1,3,NULL,'Encounter','ASSIGN_DOCTOR','Doctor assigned: Amara Okafor.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','ASSIGN_DOCTOR','2026-08-08 10:52:08'),(168,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:52:08'),(169,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:52:15'),(170,1,3,2,'Consultation','CONSULTATION_CREATED','Created consultation #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','CONSULTATION_CREATED','2026-08-08 10:52:51'),(171,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:52:58'),(172,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:53:09'),(173,1,3,2,'Consultation','CONSULTATION_COMPLETED','Completed consultation #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','CONSULTATION_COMPLETED','2026-08-08 10:54:21'),(174,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:54:35'),(175,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 10:54:41'),(176,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 11:03:48'),(177,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 11:04:02'),(178,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 11:04:12'),(179,NULL,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'WARNING','LOGIN_FAILED','2026-08-08 11:11:50'),(180,5,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','SESSION_CREATED','2026-08-08 11:15:44'),(181,5,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-08 11:15:44'),(182,5,NULL,NULL,'Security','PASSWORD_CHANGED','User password changed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','PASSWORD_CHANGED','2026-08-08 11:16:42'),(183,4,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',5,'INFO','SESSION_CREATED','2026-08-08 11:22:24'),(184,4,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-08 11:22:24'),(185,4,NULL,NULL,'Security','PASSWORD_CHANGED','User password changed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','PASSWORD_CHANGED','2026-08-08 11:22:45'),(186,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 11:23:13'),(187,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 11:25:43'),(188,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 11:26:51'),(189,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 11:27:16'),(190,1,3,2,'Department Notifications','DEPARTMENT_NOTIFICATION_SENT','Sent department notification #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','DEPARTMENT_NOTIFICATION_SENT','2026-08-08 11:28:10'),(191,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 11:28:10'),(192,4,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',5,'INFO','SESSION_CREATED','2026-08-08 11:29:36'),(193,4,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-08 11:29:36'),(194,4,3,NULL,'Security','WORKSPACE_ACCESS_DENIED','User attempted to access an encounter workspace outside their department.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','WORKSPACE_ACCESS_DENIED','2026-08-08 11:29:44'),(195,1,3,2,'Department Notifications','DEPARTMENT_NOTIFICATION_SENT','Sent department notification #4.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','DEPARTMENT_NOTIFICATION_SENT','2026-08-08 11:30:50'),(196,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 11:30:51'),(197,4,3,NULL,'Security','WORKSPACE_ACCESS_DENIED','User attempted to access an encounter workspace outside their department.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','WORKSPACE_ACCESS_DENIED','2026-08-08 11:31:07'),(198,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-08 16:41:27'),(199,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-08 16:41:47'),(200,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-08 16:41:47'),(201,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 16:42:18'),(202,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 16:44:14'),(203,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 16:45:17'),(204,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-08 20:01:16'),(205,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-08 20:01:26'),(206,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-08 20:01:26'),(207,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 20:02:11'),(208,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 20:02:18'),(209,1,3,2,'Vital Signs','VITAL_SIGNS_CREATED','Recorded vital signs #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','VITAL_SIGNS_CREATED','2026-08-08 20:03:31'),(210,1,3,2,'Vital Signs','VITAL_SIGNS_UPDATED','Updated vital signs #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','VITAL_SIGNS_UPDATED','2026-08-08 20:04:02'),(211,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 20:04:13'),(212,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 20:04:39'),(213,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 20:04:51'),(214,1,2,2,'Department Notifications','DEPARTMENT_NOTIFICATION_RESOLVED','Resolved department notification #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','DEPARTMENT_NOTIFICATION_RESOLVED','2026-08-08 20:05:18'),(215,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 20:11:10'),(216,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-08 20:50:07'),(217,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-08 20:50:16'),(218,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-08 20:50:16'),(219,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 20:50:37'),(220,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 20:50:46'),(221,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-08 20:58:58'),(222,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-09 03:18:34'),(223,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-09 03:18:34'),(224,NULL,NULL,NULL,'Security','INVALID_CSRF','Authentication request failed CSRF validation.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','INVALID_CSRF','2026-08-09 03:18:58'),(225,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-09 03:19:10'),(226,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-09 03:19:10'),(227,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:19:39'),(228,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:19:51'),(229,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:19:58'),(230,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:20:14'),(231,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:22:51'),(232,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:25:41'),(233,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:25:49'),(234,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:26:02'),(235,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:26:42'),(236,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:31:34'),(237,1,3,2,'Nursing','NURSING_ASSESSMENT_CREATED','Created nursing assessment #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','NURSING_ASSESSMENT_CREATED','2026-08-09 03:33:25'),(238,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:33:25'),(239,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:33:42'),(240,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:34:09'),(241,1,3,2,'Nursing','NURSING_ASSESSMENT_UPDATED','Updated nursing assessment #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','NURSING_ASSESSMENT_UPDATED','2026-08-09 03:34:14'),(242,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:34:14'),(243,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 03:34:21'),(244,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-09 04:14:28'),(245,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-09 04:14:42'),(246,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-09 04:14:42'),(247,1,NULL,NULL,'Administration','ACTIVE_DEPARTMENT_SWITCHED','Switched user #1 to department #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','ACTIVE_DEPARTMENT_SWITCHED','2026-08-09 04:21:43'),(248,1,NULL,NULL,'Administration','ADMIN_DASHBOARD_VIEWED','Administrator dashboard viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','ADMIN_DASHBOARD_VIEWED','2026-08-09 04:28:18'),(249,1,NULL,NULL,'Security','SECURITY_REPORT_VIEWED','Viewed the security dashboard.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SECURITY_REPORT_VIEWED','2026-08-09 04:28:29'),(250,1,NULL,NULL,'Administration','ADMIN_DASHBOARD_VIEWED','Administrator dashboard viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','ADMIN_DASHBOARD_VIEWED','2026-08-09 04:29:24'),(251,1,NULL,NULL,'Administration','ROLE_DEACTIVATED','Deactivated role #11.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','ROLE_DEACTIVATED','2026-08-09 04:29:52'),(252,1,NULL,NULL,'Administration','ROLE_ACTIVATED','Activated role #11.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','ROLE_ACTIVATED','2026-08-09 04:29:54'),(253,1,NULL,NULL,'Administration','ADMIN_DASHBOARD_VIEWED','Administrator dashboard viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','ADMIN_DASHBOARD_VIEWED','2026-08-09 04:31:18'),(254,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-09 19:32:06'),(255,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-09 19:32:21'),(256,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-09 19:32:21'),(257,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 19:33:28'),(258,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 19:33:34'),(259,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 19:35:52'),(260,1,3,NULL,'Encounter','STATUS_CHANGED','Encounter status changed to Laboratory.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','STATUS_CHANGED','2026-08-09 19:36:08'),(261,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 19:36:08'),(262,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 19:38:12'),(263,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 19:42:29'),(264,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 19:42:45'),(265,1,3,2,'Laboratory','LABORATORY_REQUEST_CREATED','Created laboratory request #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','LABORATORY_REQUEST_CREATED','2026-08-09 19:48:27'),(266,1,3,2,'Laboratory','LABORATORY_REQUEST_STARTED','Started laboratory request #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','LABORATORY_REQUEST_STARTED','2026-08-09 19:48:37'),(267,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 19:51:28'),(268,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 19:52:04'),(269,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 19:56:31'),(270,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 19:56:33'),(271,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 19:57:16'),(272,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 20:07:37'),(273,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 20:08:02'),(274,1,3,2,'Laboratory','LABORATORY_REQUEST_CREATED','Created laboratory request #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','LABORATORY_REQUEST_CREATED','2026-08-09 20:11:59'),(275,1,3,2,'Laboratory','LABORATORY_REQUEST_STARTED','Started laboratory request #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','LABORATORY_REQUEST_STARTED','2026-08-09 20:12:11'),(276,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-09 21:26:40'),(277,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-09 21:26:40'),(278,NULL,NULL,NULL,'Security','INVALID_CSRF','Authentication request failed CSRF validation.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','INVALID_CSRF','2026-08-09 21:26:52'),(279,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-09 21:27:00'),(280,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-09 21:27:00'),(281,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 21:27:30'),(282,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 21:27:43'),(283,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 21:28:06'),(284,1,3,2,'Laboratory','LABORATORY_RESULT_CREATED','Created laboratory result for request #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','LABORATORY_RESULT_CREATED','2026-08-09 21:32:25'),(285,1,3,2,'Laboratory','LABORATORY_RESULT_UPDATED','Updated laboratory result for request #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','LABORATORY_RESULT_UPDATED','2026-08-09 21:32:29'),(286,1,3,2,'Laboratory','LABORATORY_RESULT_UPDATED','Updated laboratory result for request #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','LABORATORY_RESULT_UPDATED','2026-08-09 21:32:34'),(287,1,3,2,'Laboratory','LABORATORY_RESULT_UPDATED','Updated laboratory result for request #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','LABORATORY_RESULT_UPDATED','2026-08-09 21:33:03'),(288,1,3,2,'Laboratory','LABORATORY_RESULT_UPDATED','Updated laboratory result for request #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','LABORATORY_RESULT_UPDATED','2026-08-09 21:35:10'),(289,1,3,2,'Laboratory','LABORATORY_RESULT_UPDATED','Updated laboratory result for request #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','LABORATORY_RESULT_UPDATED','2026-08-09 21:35:14'),(290,1,3,2,'Laboratory','LABORATORY_REQUEST_COMPLETED','Completed laboratory request #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','LABORATORY_REQUEST_COMPLETED','2026-08-09 21:35:29'),(291,1,3,2,'Laboratory','LABORATORY_RESULT_CREATED','Created laboratory result for request #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','LABORATORY_RESULT_CREATED','2026-08-09 21:37:13'),(292,1,3,2,'Laboratory','LABORATORY_REQUEST_COMPLETED','Completed laboratory request #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','LABORATORY_REQUEST_COMPLETED','2026-08-09 21:37:20'),(293,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-09 22:48:21'),(294,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-09 22:48:50'),(295,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-09 22:48:50'),(296,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 22:49:29'),(297,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 22:50:46'),(298,1,3,2,'Radiology','RADIOLOGY_REQUEST_CREATED','Created radiology request #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','RADIOLOGY_REQUEST_CREATED','2026-08-09 22:51:29'),(299,1,3,2,'Radiology','RADIOLOGY_REQUEST_STARTED','Started radiology request #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','RADIOLOGY_REQUEST_STARTED','2026-08-09 22:51:32'),(300,1,3,2,'Radiology','RADIOLOGY_REPORT_CREATED','Created radiology report for request #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','RADIOLOGY_REPORT_CREATED','2026-08-09 22:51:44'),(301,1,3,2,'Radiology','RADIOLOGY_REQUEST_COMPLETED','Completed radiology request #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','RADIOLOGY_REQUEST_COMPLETED','2026-08-09 22:51:51'),(302,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-09 23:01:21'),(303,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_TERMINATED','2026-08-10 20:46:09'),(304,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-10 20:46:19'),(305,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-10 20:46:19'),(306,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-10 20:51:19'),(307,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','CLINICAL_SAFETY_VIEWED','2026-08-10 20:51:24'),(308,1,3,2,'Physiotherapy','PHYSIOTHERAPY_CREATED','Created physiotherapy record #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','PHYSIOTHERAPY_CREATED','2026-08-10 20:52:06'),(309,1,3,2,'Physiotherapy','PHYSIOTHERAPY_SESSION_CREATED','Created physiotherapy session #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','PHYSIOTHERAPY_SESSION_CREATED','2026-08-10 20:52:53'),(310,1,3,2,'Physiotherapy','PHYSIOTHERAPY_COMPLETED','Completed physiotherapy record #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',4,'INFO','PHYSIOTHERAPY_COMPLETED','2026-08-10 20:53:05'),(311,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 22:23:12'),(312,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 22:23:23'),(313,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 22:23:23'),(314,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 22:23:45'),(315,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 22:23:58'),(316,1,3,NULL,'Theatre','THEATRE_CREATED','Created theatre record #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 22:24:27'),(317,1,3,NULL,'Theatre','THEATRE_UPDATED','Updated theatre record #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 22:24:35'),(318,1,3,NULL,'Theatre','THEATRE_COMPLETED','Completed theatre record #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 22:24:39'),(319,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 22:24:40'),(320,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 22:25:20'),(321,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 23:27:45'),(322,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 23:27:58'),(323,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 23:27:58'),(324,1,NULL,NULL,'Accounts','BILLABLE_ITEM_CREATED','Created billable item #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-10 23:42:04'),(325,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:10:19'),(326,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:10:28'),(327,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:10:28'),(328,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:11:20'),(329,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:11:28'),(330,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:11:31'),(331,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:11:36'),(332,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:12:07'),(333,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:12:26'),(334,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:33:25'),(335,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:34:11'),(336,4,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:35:31'),(337,4,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:35:31'),(338,1,3,2,'Department Notifications','DEPARTMENT_NOTIFICATION_SENT','Sent department notification #5.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:39:13'),(339,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:39:13'),(340,4,3,NULL,'Security','WORKSPACE_ACCESS_DENIED','User attempted to access an encounter workspace outside their department.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:39:39'),(341,1,NULL,NULL,'Administration','ADMIN_DASHBOARD_VIEWED','Administrator dashboard viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:39:56'),(342,1,NULL,NULL,'Security','SECURITY_REPORT_VIEWED','Viewed the security dashboard.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 09:40:02'),(343,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 20:35:27'),(344,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 20:35:38'),(345,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 20:35:38'),(346,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 21:35:46'),(347,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 21:35:56'),(348,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 21:35:56'),(349,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 21:36:24'),(350,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 21:36:30'),(351,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 21:36:57'),(352,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 21:37:37'),(353,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 21:37:49'),(354,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 22:48:49'),(355,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 22:49:12'),(356,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 22:49:12'),(357,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 22:49:58'),(358,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-11 22:50:25'),(359,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 11:58:22'),(360,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 11:58:22'),(361,NULL,NULL,NULL,'Security','INVALID_CSRF','Authentication request failed CSRF validation.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 11:58:38'),(362,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 11:59:46'),(363,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 11:59:46'),(364,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 12:00:36'),(365,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 12:00:48'),(366,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 12:01:12'),(367,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 12:52:44'),(368,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 12:54:36'),(369,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 12:54:46'),(370,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 13:43:43'),(371,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 13:43:55'),(372,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 13:43:55'),(373,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 13:44:09'),(374,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 13:44:13'),(375,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 13:45:21'),(376,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:37:22'),(377,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:37:30'),(378,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:37:30'),(379,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:37:41'),(380,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:38:02'),(381,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:40:21'),(382,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:42:37'),(383,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:43:11'),(384,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:43:47'),(385,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:44:07'),(386,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:45:28'),(387,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:45:38'),(388,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:45:56'),(389,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:46:40'),(390,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:46:51'),(391,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:47:07'),(392,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:55:54'),(393,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:56:34'),(394,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 14:56:53'),(395,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:08:05'),(396,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:08:12'),(397,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:19:48'),(398,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:20:03'),(399,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:20:38'),(400,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:26:27'),(401,1,3,NULL,'Encounter','STATUS_CHANGED','Encounter status changed to Doctor.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:29:05'),(402,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:29:06'),(403,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:29:26'),(404,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:30:23'),(405,1,4,NULL,'Encounter','CREATE','Encounter created and patient received in Physiotherapy.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:40:48'),(406,1,4,NULL,'Queue','ENQUEUE','Encounter added to the department queue.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:40:48'),(407,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:40:49'),(408,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 15:41:37'),(409,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-12 16:00:26'),(410,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-13 19:02:37'),(411,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-13 19:03:03'),(412,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-13 19:03:03'),(413,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-13 19:03:10'),(414,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-13 19:03:27'),(415,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-13 19:03:31'),(416,1,NULL,NULL,'Reports','FINANCIAL_REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-13 19:03:35'),(417,1,NULL,NULL,'Reports','INVENTORY_REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-13 19:03:38'),(418,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 01:20:33'),(419,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 01:21:09'),(420,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 01:21:09'),(421,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 01:22:35'),(422,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 01:25:18'),(423,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 01:42:03'),(424,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 01:42:14'),(425,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 01:42:25'),(426,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 01:42:38'),(427,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 02:07:34'),(428,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 02:09:14'),(429,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 02:09:49'),(430,1,NULL,NULL,'Security','SESSION_TIMEOUT','User session expired due to inactivity.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:10:17'),(431,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:10:17'),(432,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:13:16'),(433,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:13:16'),(434,1,NULL,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:14:28'),(435,1,NULL,3,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:14:29'),(436,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:15:00'),(437,1,NULL,3,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:15:00'),(438,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:15:16'),(439,1,NULL,3,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:15:16'),(440,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:15:33'),(441,1,NULL,3,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:15:33'),(442,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:15:50'),(443,1,NULL,3,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:15:50'),(444,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:16:06'),(445,1,NULL,3,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:16:06'),(446,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:19:17'),(447,1,NULL,3,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 03:19:17'),(448,1,NULL,NULL,'Security','SESSION_TIMEOUT','User session expired due to inactivity.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 07:11:47'),(449,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 07:11:47'),(450,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 07:11:57'),(451,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 07:11:57'),(452,1,NULL,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 07:12:49'),(453,1,NULL,3,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 07:12:49'),(454,1,NULL,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 07:12:55'),(455,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 07:14:12'),(456,1,NULL,3,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 07:14:12'),(457,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 07:14:16'),(458,1,NULL,3,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 07:14:16'),(459,1,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 07:14:19'),(460,1,NULL,3,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 07:14:19'),(461,1,NULL,NULL,'Security','SESSION_TIMEOUT','User session expired due to inactivity.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 11:04:55'),(462,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 11:04:55'),(463,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 11:05:03'),(464,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 11:05:03'),(465,1,NULL,NULL,'Security','SESSION_TIMEOUT','User session expired due to inactivity.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 12:15:47'),(466,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 12:15:47'),(467,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 12:15:58'),(468,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 12:16:07'),(469,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-14 12:16:07'),(470,1,NULL,NULL,'Security','SESSION_TIMEOUT','User session expired due to inactivity.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-17 09:02:23'),(471,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-17 09:02:23'),(472,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-17 09:02:39'),(473,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-17 09:02:39'),(474,1,NULL,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-17 09:16:30'),(475,1,NULL,3,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-17 09:16:30'),(476,1,NULL,NULL,'Security','SESSION_TIMEOUT','User session expired due to inactivity.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 10:51:01'),(477,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 10:51:01'),(478,4,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 10:51:28'),(479,4,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 10:51:28'),(480,4,NULL,NULL,'Security','SESSION_TIMEOUT','User session expired due to inactivity.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:09:56'),(481,4,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:09:56'),(482,2,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:10:23'),(483,2,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:10:23'),(484,2,NULL,NULL,'Security','PASSWORD_CHANGED','User password changed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:10:46'),(485,2,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:37:53'),(486,2,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:37:53'),(487,2,NULL,8,'Patients','PATIENT_REGISTERED','Registered patient HMS-2026-000008.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:39:17'),(488,2,NULL,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:39:33'),(489,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:39:33'),(490,2,NULL,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:39:43'),(491,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:39:43'),(492,2,NULL,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:39:47'),(493,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:39:47'),(494,2,NULL,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:39:48'),(495,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:39:48'),(496,2,NULL,8,'Security','PROBLEM_LIST_ACCESS_DENIED','Problem List access denied.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:39:49'),(497,2,NULL,8,'Security','NURSING_ACCESS_DENIED','Nursing access denied.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:39:57'),(498,2,NULL,8,'Security','CLINICAL_NOTE_ACCESS_DENIED','Clinical Note list access denied.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:40:00'),(499,2,NULL,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:40:03'),(500,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:40:03'),(501,2,NULL,NULL,'Administration','ACTIVE_DEPARTMENT_SWITCHED','Switched user #2 to department #2.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:40:48'),(502,2,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:41:00'),(503,4,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:41:24'),(504,4,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:41:24'),(505,4,NULL,8,'Security','PATIENT_CHART_ACCESS_DENIED','User attempted to edit patient demographics without permission.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:42:07'),(510,4,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:44:33'),(511,2,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:44:54'),(512,2,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:44:54'),(513,2,NULL,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:45:08'),(514,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:45:08'),(519,2,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:46:00'),(522,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:46:08'),(523,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:46:17'),(524,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:46:17'),(525,1,10,NULL,'Encounter','CREATE','Encounter created and patient received in Doctor.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:46:50'),(526,1,10,NULL,'Queue','ENQUEUE','Encounter added to the department queue.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:46:50'),(527,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:46:51'),(528,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:47:10'),(529,2,NULL,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:47:12'),(530,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:47:12'),(531,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:47:40'),(532,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:49:41'),(533,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:51:00'),(534,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:51:07'),(535,1,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:51:07'),(536,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:51:18'),(537,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:51:19'),(538,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:51:39'),(539,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:51:39'),(540,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:51:43'),(541,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:51:52'),(542,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:51:52'),(543,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:51:56'),(544,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:51:59'),(545,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:51:59'),(546,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:10'),(547,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:10'),(548,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:13'),(549,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:13'),(550,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:14'),(551,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:14'),(552,2,NULL,8,'Security','PROBLEM_LIST_ACCESS_DENIED','Problem List access denied.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:15'),(553,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:22'),(554,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:22'),(555,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:25'),(556,2,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:25'),(557,2,NULL,8,'Security','PROBLEM_LIST_ACCESS_DENIED','Problem List access denied.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:27'),(558,2,NULL,8,'Security','MEDICAL_HISTORY_ACCESS_DENIED','Medical history access denied.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:30'),(559,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:52:56'),(560,1,NULL,NULL,'Administration','ADMIN_DASHBOARD_VIEWED','Administrator dashboard viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:53:00'),(561,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:53:43'),(562,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:54:05'),(563,1,NULL,NULL,'Administration','PERMISSION_ASSIGNED','Assigned permission #2 to role #5.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:54:59'),(564,1,NULL,NULL,'Administration','ROLE_PERMISSION_UPDATED','Updated permissions for role #5.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:54:59'),(565,1,NULL,NULL,'Administration','ROLE_PERMISSION_UPDATED','Updated permissions for role #5.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-19 13:56:17'),(566,1,NULL,NULL,'Security','SESSION_TIMEOUT','User session expired due to inactivity.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:11:05'),(567,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:11:06'),(568,1,NULL,NULL,'Security','SESSION_TIMEOUT','User session expired due to inactivity.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:11:06'),(569,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:11:06'),(570,NULL,NULL,NULL,'Security','INVALID_CSRF','Authentication request failed CSRF validation.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:11:31'),(571,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:11:45'),(572,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:11:45'),(573,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:12:26'),(574,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:14:50'),(575,1,10,NULL,'Encounter','ASSIGN_DOCTOR','Doctor assigned: Amara Okafor.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:16:19'),(576,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:16:19'),(577,1,10,8,'Consultation','CONSULTATION_CREATED','Created consultation #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:16:26'),(578,1,10,8,'Laboratory','LABORATORY_REQUEST_CREATED','Created laboratory request #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:16:58'),(579,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:17:57'),(580,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:17:59'),(581,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:18:03'),(582,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:18:26'),(583,1,10,8,'Laboratory','LABORATORY_REQUEST_STARTED','Started laboratory request #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:18:41'),(584,1,10,8,'Laboratory','LABORATORY_RESULT_CREATED','Created laboratory result for request #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:19:31'),(585,1,10,8,'Laboratory','LABORATORY_RESULT_UPDATED','Updated laboratory result for request #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:19:31'),(586,1,10,8,'Laboratory','LABORATORY_RESULT_UPDATED','Updated laboratory result for request #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:19:38'),(587,1,10,8,'Laboratory','LABORATORY_REQUEST_COMPLETED','Completed laboratory request #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:19:50'),(588,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-21 07:20:03'),(589,1,NULL,NULL,'Security','SESSION_TIMEOUT','User session expired due to inactivity.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:04:02'),(590,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:04:02'),(591,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:04:12'),(592,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:04:12'),(593,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:04:25'),(594,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:04:42'),(595,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:04:45'),(596,1,NULL,NULL,'Accounts','BILLABLE_ITEM_UPDATED','Updated billable item #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:14:29'),(597,1,NULL,NULL,'Accounts','BILLABLE_ITEM_CREATED','Created billable item #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:15:33'),(598,1,NULL,NULL,'Store','INVENTORY_ITEM_CREATED','Created inventory item #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:26:19'),(599,1,NULL,NULL,'Store','INVENTORY_ITEM_UPDATED','Updated inventory item #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:36:40'),(600,1,NULL,NULL,'Store','STOCK_RECEIVED','Received stock movement. Item #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:36:50'),(601,1,NULL,NULL,'Store','STOCK_ISSUED','Issued stock movement. Item #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:37:09'),(602,1,10,8,'Pharmacy','PRESCRIPTION_CREATED','Created prescription #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:39:31'),(603,1,NULL,NULL,'Store','STOCK_ISSUED','Consumed stock from department for dispensing. Item #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:40:00'),(604,1,10,8,'Pharmacy','PRESCRIPTION_DISPENSED','Dispensed prescription #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:40:00'),(605,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:40:56'),(606,1,10,8,'Consultation','CONSULTATION_COMPLETED','Completed consultation #3.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:41:07'),(607,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:47:06'),(608,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:48:04'),(609,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:48:38'),(610,1,10,8,'Billing','PATIENT_CHARGE_CREATED','Created patient charge #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:49:55'),(611,1,10,8,'Billing','PATIENT_CHARGE_CREATED','Created patient charge #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:50:10'),(612,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:50:18'),(613,1,10,8,'Billing','PATIENT_CHARGE_CANCELLED','Cancelled patient charge #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:50:48'),(614,1,10,8,'Billing','PAYMENT_RECORDED','Recorded payment #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-22 06:57:07'),(615,1,NULL,NULL,'Security','SESSION_TIMEOUT','User session expired due to inactivity.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:06:54'),(616,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:06:54'),(617,4,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:07:12'),(618,4,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:07:12'),(619,4,NULL,8,'Security','PATIENT_CHART_ACCESS_DENIED','User attempted to view a patient chart without authorization.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:28:46'),(620,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:42:38'),(621,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:42:51'),(622,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:47:13'),(623,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:47:32'),(624,4,10,8,'Vital Signs','VITAL_SIGNS_CREATED','Recorded vital signs #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:50:54'),(625,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:51:02'),(626,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:52:39'),(627,4,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:52:39'),(628,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:52:55'),(629,4,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:52:55'),(630,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:52:58'),(631,4,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:52:58'),(632,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:53:02'),(633,4,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:53:02'),(634,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:53:05'),(635,4,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:53:05'),(636,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:53:17'),(637,4,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:53:17'),(638,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:53:23'),(639,4,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:53:23'),(640,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:53:55'),(641,4,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:53:55'),(642,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:53:59'),(643,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:54:40'),(644,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:59:47'),(645,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 07:59:57'),(646,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:03:22'),(647,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:09:21'),(648,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:12:17'),(649,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:15:07'),(650,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:15:19'),(651,4,NULL,NULL,'Admissions','WARD_CREATED','Created ward #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:16:06'),(652,4,NULL,NULL,'Admissions','WARD_BED_CREATED','Created bed #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:16:21'),(653,4,10,8,'Admissions','PATIENT_ADMITTED','Admitted patient to ward/bed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:17:06'),(654,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:17:30'),(655,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:18:44'),(656,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:19:11'),(657,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:22:25'),(658,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:23:35'),(659,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:23:39'),(660,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:33:19'),(661,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:34:29'),(662,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:34:52'),(663,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:35:52'),(664,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:36:18'),(665,4,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:36:51'),(666,4,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:36:53'),(667,4,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:36:56'),(668,4,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:37:08'),(669,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:42:14'),(670,4,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:42:22'),(671,4,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:22'),(672,4,NULL,2,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:22'),(673,4,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:28'),(674,4,NULL,2,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:28'),(675,4,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:42'),(676,4,NULL,2,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:42'),(677,4,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:45'),(678,4,NULL,2,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:45'),(679,4,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:51'),(680,4,NULL,2,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:51'),(681,4,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:54'),(682,4,NULL,2,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:54'),(683,4,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:59'),(684,4,NULL,2,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:46:59'),(685,4,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:47:00'),(686,4,NULL,2,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:47:00'),(687,4,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:47:34'),(688,2,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:47:59'),(689,NULL,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:48:21'),(690,NULL,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:48:45'),(691,2,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:49:16'),(692,2,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:49:41'),(693,2,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:49:56'),(694,2,NULL,NULL,'Security','ACCOUNT_LOCKED','Account automatically locked after repeated failed login attempts.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:50:29'),(695,2,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:50:29'),(696,4,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:51:04'),(697,4,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:51:04'),(698,4,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:51:11'),(699,5,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:51:23'),(700,5,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:51:23'),(701,5,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:51:27'),(702,2,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:51:46'),(703,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:55:52'),(704,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:56:47'),(705,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 08:56:47'),(706,1,NULL,NULL,'Administration','ACCOUNT_UNLOCKED','Unlocked user account #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:00:12'),(707,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:00:34'),(708,2,NULL,NULL,'Security','ACCOUNT_LOCKED','Account automatically locked after repeated failed login attempts.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:01:02'),(709,2,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:01:02'),(710,2,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:01:47'),(711,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:02:16'),(712,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:02:16'),(713,1,NULL,NULL,'Administration','ACCOUNT_UNLOCKED','Unlocked user account #2.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:03:47'),(714,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:11:48'),(715,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:11:55'),(716,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:12:47'),(717,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:12:47'),(718,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset and unlocked Reception user account #2.','UNKNOWN',NULL,NULL,'INFO','GENERAL','2026-08-23 09:12:53'),(719,1,NULL,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:14:36'),(720,1,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:14:36'),(721,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:16:11'),(722,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:16:15'),(723,1,NULL,NULL,'Reports','FINANCIAL_REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:16:24'),(724,1,NULL,NULL,'Reports','FINANCIAL_REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:16:32'),(725,1,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:16:36'),(726,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:16:57'),(727,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:17:23'),(728,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:17:35'),(729,1,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:18:00'),(730,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:19:40'),(731,2,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:19:59'),(732,2,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:19:59'),(733,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:20:41'),(734,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:21:30'),(735,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:21:38'),(736,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:21:45'),(737,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:21:52'),(738,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:24:03'),(739,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:24:10'),(740,2,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:25:15'),(741,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:25:42'),(742,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:25:42'),(743,1,NULL,NULL,'Administration','ADMIN_DASHBOARD_VIEWED','Administrator dashboard viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:26:55'),(744,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:31:18'),(745,2,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:31:37'),(746,2,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:31:37'),(747,2,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:31:55'),(748,2,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:32:00'),(749,2,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:34:07'),(750,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','10.183.224.58','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:45:05'),(751,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','10.183.224.58','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:45:05'),(752,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','10.183.224.58','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:47:01'),(753,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','10.183.224.58','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:47:24'),(754,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','10.183.224.20','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:48:50'),(755,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','10.183.224.20','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:48:50'),(756,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','10.183.224.20','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:50:47'),(757,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','10.183.224.20','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:50:47'),(758,1,NULL,NULL,'Administration','ADMIN_DASHBOARD_VIEWED','Administrator dashboard viewed.','10.183.224.20','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:51:44'),(759,1,3,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','10.183.224.20','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:53:24'),(760,1,2,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','10.183.224.20','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 09:54:21'),(761,3,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:05:16'),(762,3,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:05:16'),(763,3,NULL,NULL,'Security','PASSWORD_CHANGED','User password changed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:05:54'),(764,3,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:06:54'),(765,3,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:07:15'),(766,3,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:07:26'),(767,3,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:07:29'),(768,3,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:07:31'),(769,3,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:07:32'),(770,3,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:07:56'),(771,3,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:08:33'),(772,3,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:09:11'),(773,3,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:09:11'),(774,3,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:09:14'),(775,3,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:09:49'),(776,3,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:09:49'),(777,3,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:09:52'),(778,3,NULL,8,'Medical Records','MEDICAL_RECORD_VIEWED','Viewed patient chart #8.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:09:52'),(779,3,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:10:01'),(780,3,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:10:03'),(781,3,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:10:12'),(782,3,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:10:21'),(783,3,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:10:23'),(784,3,NULL,NULL,'Reports','REPORT_VIEWED','Read-only report viewed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:10:30'),(785,3,1,2,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:11:02'),(786,3,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:11:52'),(787,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:12:00'),(788,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:12:09'),(789,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:12:09'),(790,1,NULL,NULL,'Administration','USER_CREATED','Created user account #26.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:14:26'),(791,1,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:15:20'),(792,26,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:15:35'),(793,26,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:15:35'),(794,26,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:16:11'),(795,26,10,NULL,'Encounter','ASSIGN_DOCTOR','Doctor assigned: Olisemeke Ikhile.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:16:47'),(796,26,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:16:47'),(797,26,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:17:29'),(798,26,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:17:31'),(799,26,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:17:32'),(800,26,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:18:16'),(801,26,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:19:12'),(802,2,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:19:24'),(803,2,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:19:24'),(804,2,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:19:41'),(805,2,10,8,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:20:21'),(806,2,NULL,NULL,'Security','SESSION_TERMINATED','User logout.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:20:32'),(807,26,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:20:46'),(808,26,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:20:46'),(809,26,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:21:00'),(810,26,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:21:09'),(811,26,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:21:11'),(812,26,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:25:29'),(813,26,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:25:47'),(814,26,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:27:45'),(815,26,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:30:03'),(816,26,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:32:18'),(817,26,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:32:28'),(818,26,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:32:44'),(819,26,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:32:53'),(820,26,4,3,'Medical Records','CLINICAL_SAFETY_VIEWED','Viewed longitudinal clinical safety information.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',NULL,'INFO','GENERAL','2026-08-23 10:34:17');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `billable_items`
--

DROP TABLE IF EXISTS `billable_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `billable_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_code` varchar(30) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_type` enum('Service','Product') NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_billable_items_code` (`item_code`),
  KEY `idx_billable_items_name` (`item_name`),
  KEY `idx_billable_items_type` (`item_type`),
  KEY `idx_billable_items_department` (`department_id`),
  KEY `idx_billable_items_status` (`is_active`),
  KEY `idx_billable_items_created_at` (`created_at`),
  KEY `idx_billable_items_created_by` (`created_by`),
  KEY `idx_billable_items_updated_by` (`updated_by`),
  CONSTRAINT `fk_billable_items_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_billable_items_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_billable_items_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billable_items`
--

LOCK TABLES `billable_items` WRITE;
/*!40000 ALTER TABLE `billable_items` DISABLE KEYS */;
INSERT INTO `billable_items` VALUES (1,'923','Xray Scan','Service',9,'Xray Scan for Patient',15000.00,'1',1,1,1,'2026-08-10 23:42:04','2026-08-22 06:14:29'),(2,'924','Paracetamol 500mg','Product',7,'Pain Killer',500.00,'1',1,1,NULL,'2026-08-22 06:15:33','2026-08-22 06:15:33');
/*!40000 ALTER TABLE `billable_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinical_note_versions`
--

DROP TABLE IF EXISTS `clinical_note_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinical_note_versions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `note_id` bigint(20) NOT NULL,
  `version_number` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `content_format` enum('Plain Text') NOT NULL DEFAULT 'Plain Text',
  `version_status` enum('Draft','Signed','Amendment Proposal','Amended','Entered-in-error') NOT NULL,
  `author_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `confidentiality_level` enum('Standard','Restricted','Confidential','Highly Confidential') NOT NULL DEFAULT 'Standard',
  `content_checksum` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signed_by` int(11) DEFAULT NULL,
  `signed_at` datetime DEFAULT NULL,
  `amendment_reason` text DEFAULT NULL,
  `supersedes_version_id` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_clinical_note_version` (`note_id`,`version_number`),
  KEY `idx_clinical_note_versions_note_created` (`note_id`,`created_at`),
  KEY `idx_clinical_note_versions_author` (`author_id`,`created_at`),
  KEY `idx_clinical_note_versions_status` (`version_status`,`created_at`),
  KEY `idx_clinical_note_versions_confidentiality` (`confidentiality_level`,`created_at`),
  KEY `idx_clinical_note_versions_checksum` (`content_checksum`),
  KEY `idx_clinical_note_versions_supersedes` (`supersedes_version_id`),
  KEY `fk_clinical_note_versions_department` (`department_id`),
  KEY `fk_clinical_note_versions_signed_by` (`signed_by`),
  CONSTRAINT `fk_clinical_note_versions_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_clinical_note_versions_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_clinical_note_versions_note` FOREIGN KEY (`note_id`) REFERENCES `clinical_notes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_clinical_note_versions_signed_by` FOREIGN KEY (`signed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_clinical_note_versions_supersedes` FOREIGN KEY (`supersedes_version_id`) REFERENCES `clinical_note_versions` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_note_versions`
--

LOCK TABLES `clinical_note_versions` WRITE;
/*!40000 ALTER TABLE `clinical_note_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinical_note_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinical_notes`
--

DROP TABLE IF EXISTS `clinical_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinical_notes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `visit_id` int(11) DEFAULT NULL,
  `note_type` varchar(80) NOT NULL,
  `title` varchar(200) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `confidentiality_level` enum('Standard','Restricted','Confidential','Highly Confidential') NOT NULL DEFAULT 'Standard',
  `note_status` enum('Draft','Signed','Amended','Entered-in-error') NOT NULL DEFAULT 'Draft',
  `current_version` int(11) NOT NULL DEFAULT 1,
  `signed_by` int(11) DEFAULT NULL,
  `signed_at` datetime DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `amended_at` datetime DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_clinical_notes_patient_status` (`patient_id`,`note_status`,`created_at`),
  KEY `idx_clinical_notes_visit_status` (`visit_id`,`note_status`,`created_at`),
  KEY `idx_clinical_notes_author_status` (`author_id`,`note_status`,`updated_at`),
  KEY `idx_clinical_notes_department` (`department_id`,`created_at`),
  KEY `idx_clinical_notes_type` (`note_type`,`note_status`,`created_at`),
  KEY `idx_clinical_notes_confidentiality` (`confidentiality_level`,`note_status`),
  KEY `fk_clinical_notes_signed_by` (`signed_by`),
  CONSTRAINT `fk_clinical_notes_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_clinical_notes_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_clinical_notes_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_clinical_notes_signed_by` FOREIGN KEY (`signed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_clinical_notes_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_notes`
--

LOCK TABLES `clinical_notes` WRITE;
/*!40000 ALTER TABLE `clinical_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinical_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consultations`
--

DROP TABLE IF EXISTS `consultations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consultations` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `presenting_complaint` text NOT NULL,
  `history_of_presenting_complaint` text NOT NULL,
  `examination_findings` text NOT NULL,
  `assessment` text NOT NULL,
  `diagnosis` text NOT NULL,
  `treatment_plan` text NOT NULL,
  `advice` text DEFAULT NULL,
  `follow_up` text DEFAULT NULL,
  `referral_notes` text DEFAULT NULL,
  `status` enum('Draft','Completed') NOT NULL DEFAULT 'Draft',
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_consultations_visit` (`visit_id`),
  KEY `idx_consultations_patient_status` (`patient_id`,`status`,`created_at`),
  KEY `idx_consultations_doctor_status` (`doctor_id`,`status`,`created_at`),
  KEY `idx_consultations_department` (`department_id`,`created_at`),
  KEY `fk_consultations_created_by` (`created_by`),
  KEY `fk_consultations_updated_by` (`updated_by`),
  KEY `fk_consultations_completed_by` (`completed_by`),
  CONSTRAINT `fk_consultations_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultations_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultations_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_consultations_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultations_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultations_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultations_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consultations`
--

LOCK TABLES `consultations` WRITE;
/*!40000 ALTER TABLE `consultations` DISABLE KEYS */;
INSERT INTO `consultations` VALUES (1,2,2,5,4,'walt','akjhaj','ajahva','akjahga','ajavhga','ahjauya','ajah','amnahva','ajhavu','Completed',1,1,1,'2026-08-08 11:20:59','2026-08-08 11:21:20','2026-08-08 11:21:20'),(2,3,2,5,4,'zkzahja','aja0aia','ajhahga','ajhahga','ahaha','ajhagha','ajhaha','ahjah','ahjaha','Completed',1,1,1,'2026-08-08 11:52:51','2026-08-08 11:54:21','2026-08-08 11:54:21'),(3,10,8,5,4,'hjvhf',',nkbvhucg','nbvgfr','hgcfc','xgfxfx','nbvghchgc','vhgcx','bhcgfxf','vghcfyx','Completed',1,1,1,'2026-08-21 08:16:26','2026-08-22 07:41:07','2026-08-22 07:41:07');
/*!40000 ALTER TABLE `consultations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_notifications`
--

DROP TABLE IF EXISTS `department_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_notifications` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `from_department_id` int(11) DEFAULT NULL,
  `to_department_id` int(11) NOT NULL,
  `sent_by` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Unread','Read','Resolved') NOT NULL DEFAULT 'Unread',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_by` int(11) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_department_notifications_to_status` (`to_department_id`,`status`,`created_at`),
  KEY `idx_department_notifications_visit` (`visit_id`,`created_at`),
  KEY `idx_department_notifications_patient` (`patient_id`,`created_at`),
  KEY `idx_department_notifications_sender` (`sent_by`,`created_at`),
  KEY `fk_department_notifications_from_department` (`from_department_id`),
  KEY `fk_department_notifications_read_by` (`read_by`),
  KEY `fk_department_notifications_resolved_by` (`resolved_by`),
  CONSTRAINT `fk_department_notifications_from_department` FOREIGN KEY (`from_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_department_notifications_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_department_notifications_read_by` FOREIGN KEY (`read_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_department_notifications_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_department_notifications_sent_by` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_department_notifications_to_department` FOREIGN KEY (`to_department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_department_notifications_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_notifications`
--

LOCK TABLES `department_notifications` WRITE;
/*!40000 ALTER TABLE `department_notifications` DISABLE KEYS */;
INSERT INTO `department_notifications` VALUES (1,2,2,4,7,1,'need drugs for patient','Unread','2026-08-08 11:24:15',NULL,NULL,NULL,NULL),(2,2,2,4,1,1,'need drugs','Resolved','2026-08-08 11:24:53',1,'2026-08-08 11:25:47',1,'2026-08-08 21:05:18'),(3,3,2,4,5,1,'testing','Unread','2026-08-08 12:28:10',NULL,NULL,NULL,NULL),(4,3,2,4,5,1,'testing2','Unread','2026-08-08 12:30:50',NULL,NULL,NULL,NULL),(5,3,2,4,5,1,'testing','Unread','2026-08-11 10:39:13',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `department_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_stock_balances`
--

DROP TABLE IF EXISTS `department_stock_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_stock_balances` (
  `inventory_item_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`inventory_item_id`,`department_id`),
  KEY `idx_department_stock_balances_department` (`department_id`),
  KEY `idx_department_stock_balances_updated_at` (`updated_at`),
  CONSTRAINT `fk_department_stock_balances_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_department_stock_balances_item` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_stock_balances`
--

LOCK TABLES `department_stock_balances` WRITE;
/*!40000 ALTER TABLE `department_stock_balances` DISABLE KEYS */;
INSERT INTO `department_stock_balances` VALUES (1,7,140.00,'2026-08-22 06:40:00'),(1,12,850.00,'2026-08-22 06:37:09');
/*!40000 ALTER TABLE `department_stock_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_name` varchar(100) NOT NULL,
  `department_code` varchar(30) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `contact_extension` varchar(30) DEFAULT NULL,
  `department_type` enum('Clinical','Administrative','Diagnostic','Support') NOT NULL DEFAULT 'Support',
  `queue_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_departments_name` (`department_name`),
  UNIQUE KEY `uq_departments_code` (`department_code`),
  KEY `idx_departments_active` (`is_active`),
  KEY `idx_departments_type` (`department_type`),
  KEY `idx_departments_queue` (`queue_enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Administrator','DEPT-001','System administration',NULL,NULL,'Administrative',1,1,1,'2026-08-05 04:14:44','2026-08-05 04:48:44'),(2,'Reception','DEPT-002','Patient reception',NULL,NULL,'Clinical',1,1,2,'2026-08-05 04:14:44','2026-08-05 04:48:44'),(3,'Records','DEPT-003','Medical records',NULL,NULL,'Clinical',1,1,3,'2026-08-05 04:14:44','2026-08-05 04:48:44'),(4,'Doctor','DEPT-004','Medical consultation',NULL,NULL,'Clinical',1,1,4,'2026-08-05 04:14:44','2026-08-05 04:48:44'),(5,'Nursing','DEPT-005','Nursing services',NULL,NULL,'Clinical',1,1,5,'2026-08-05 04:14:44','2026-08-05 04:48:44'),(6,'Laboratory','DEPT-006','Laboratory investigations',NULL,NULL,'Diagnostic',1,1,6,'2026-08-05 04:14:44','2026-08-05 04:48:44'),(7,'Pharmacy','DEPT-007','Drug dispensing',NULL,NULL,'Support',1,1,7,'2026-08-05 04:14:44','2026-08-05 04:48:44'),(8,'Physiotherapy','DEPT-008','Physiotherapy services',NULL,NULL,'Support',1,1,8,'2026-08-05 04:14:44','2026-08-05 04:48:44'),(9,'X-Ray','DEPT-009','Radiology and imaging',NULL,NULL,'Diagnostic',1,1,9,'2026-08-05 04:14:44','2026-08-05 04:48:44'),(10,'Theatre','DEPT-010','Surgical theatre',NULL,NULL,'Support',1,1,10,'2026-08-05 04:14:44','2026-08-05 04:48:44'),(11,'Accounts','DEPT-011','Billing and payments',NULL,NULL,'Administrative',1,1,11,'2026-08-05 04:14:44','2026-08-05 04:48:44'),(12,'Store','DEPT-012','Medical store',NULL,NULL,'Support',1,1,12,'2026-08-05 04:14:44','2026-08-05 04:48:44');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `encounter_events`
--

DROP TABLE IF EXISTS `encounter_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `encounter_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `event_title` varchar(150) NOT NULL,
  `event_description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `event_time` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_encounter_events_visit_time` (`visit_id`,`event_time`,`id`),
  KEY `idx_encounter_events_department` (`department_id`),
  KEY `idx_encounter_events_performed_by` (`performed_by`),
  CONSTRAINT `fk_encounter_events_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_encounter_events_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_encounter_events_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_events`
--

LOCK TABLES `encounter_events` WRITE;
/*!40000 ALTER TABLE `encounter_events` DISABLE KEYS */;
INSERT INTO `encounter_events` VALUES (1,1,'ENCOUNTER_CREATED','Encounter Created','Encounter created and patient received in Reception.',2,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(2,1,'QUEUED','Encounter Queued','Encounter added to the department queue.',2,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(3,1,'CALLED','Patient Called','Encounter called from the department queue.',2,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(4,1,'SERVICE_STARTED','Service Started','Encounter service started.',2,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(5,1,'SERVICE_COMPLETED','Service Completed','Encounter service completed. Remarks: Controlled reconstruction verification.',2,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(6,1,'TRANSFERRED','Encounter Transferred','Encounter transferred from 2 to Doctor.',4,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(7,1,'QUEUED','Encounter Queued','Encounter added to the department queue.',4,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(8,1,'PATIENT_RECEIVED','Patient Received','Patient received in Doctor department.',4,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(9,1,'DOCTOR_ASSIGNED','Doctor Assigned','Doctor assigned: Amara Okafor.',4,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(10,1,'QUEUE_CANCELLED','Queue Entry Closed','Queue entry closed because the encounter was closed with status Completed.',4,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(11,1,'STATUS_CHANGED','Encounter Status Changed','Encounter status changed to Completed.',NULL,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(12,2,'ENCOUNTER_CREATED','Encounter Created','Encounter created and patient received in Doctor.',4,1,'2026-08-05 21:41:24','2026-08-05 20:41:24'),(13,2,'QUEUED','Encounter Queued','Encounter added to the department queue.',4,1,'2026-08-05 21:41:24','2026-08-05 20:41:24'),(14,2,'DOCTOR_ASSIGNED','Doctor Assigned','Doctor assigned: Amara Okafor.',4,1,'2026-08-05 21:42:33','2026-08-05 20:42:33'),(15,2,'CONSULTATION_STARTED','Consultation Started','Consultation record opened.',4,1,'2026-08-08 11:20:59','2026-08-08 10:20:59'),(16,2,'CONSULTATION_COMPLETED','Consultation Completed','Consultation completed.',4,1,'2026-08-08 11:21:20','2026-08-08 10:21:20'),(17,2,'DEPARTMENT_NOTIFICATION_SENT','Department Notification Sent','Attention requested from another department.',4,1,'2026-08-08 11:24:15','2026-08-08 10:24:15'),(18,2,'DEPARTMENT_NOTIFICATION_SENT','Department Notification Sent','Attention requested from another department.',4,1,'2026-08-08 11:24:53','2026-08-08 10:24:53'),(19,2,'QUEUE_CANCELLED','Queue Entry Closed','Queue entry closed because the encounter was closed with status Completed.',4,1,'2026-08-08 11:43:52','2026-08-08 10:43:52'),(20,2,'STATUS_CHANGED','Encounter Status Changed','Encounter status changed to Completed.',NULL,1,'2026-08-08 11:43:52','2026-08-08 10:43:52'),(21,3,'ENCOUNTER_CREATED','Encounter Created','Encounter created and patient received in Doctor.',4,1,'2026-08-08 11:44:27','2026-08-08 10:44:27'),(22,3,'QUEUED','Encounter Queued','Encounter added to the department queue.',4,1,'2026-08-08 11:44:27','2026-08-08 10:44:27'),(23,3,'DOCTOR_ASSIGNED','Doctor Assigned','Doctor assigned: Amara Okafor.',4,1,'2026-08-08 11:52:08','2026-08-08 10:52:08'),(24,3,'CONSULTATION_STARTED','Consultation Started','Consultation record opened.',4,1,'2026-08-08 11:52:51','2026-08-08 10:52:51'),(25,3,'CONSULTATION_COMPLETED','Consultation Completed','Consultation completed.',4,1,'2026-08-08 11:54:21','2026-08-08 10:54:21'),(26,3,'DEPARTMENT_NOTIFICATION_SENT','Department Notification Sent','Attention requested from another department.',4,1,'2026-08-08 12:28:10','2026-08-08 11:28:10'),(27,3,'DEPARTMENT_NOTIFICATION_SENT','Department Notification Sent','Attention requested from another department.',4,1,'2026-08-08 12:30:50','2026-08-08 11:30:50'),(28,3,'NURSING_ASSESSMENT_STARTED','Nursing Assessment Started','Nursing assessment created.',4,1,'2026-08-09 04:33:25','2026-08-09 03:33:25'),(29,3,'STATUS_CHANGED','Encounter Status Changed','Encounter status changed to Laboratory.',NULL,1,'2026-08-09 20:36:08','2026-08-09 19:36:08'),(30,3,'LABORATORY_REQUESTED','Laboratory Request Created','Laboratory request created.',4,1,'2026-08-09 20:48:27','2026-08-09 19:48:27'),(31,3,'LABORATORY_REQUESTED','Laboratory Request Created','Laboratory request created.',4,1,'2026-08-09 21:11:59','2026-08-09 20:11:59'),(32,3,'LABORATORY_COMPLETED','Laboratory Request Completed','Laboratory request completed.',4,1,'2026-08-09 22:35:29','2026-08-09 21:35:29'),(33,3,'LABORATORY_COMPLETED','Laboratory Request Completed','Laboratory request completed.',4,1,'2026-08-09 22:37:20','2026-08-09 21:37:20'),(34,3,'RADIOLOGY_REQUESTED','Radiology Request Created','Radiology request created.',4,1,'2026-08-09 23:51:29','2026-08-09 22:51:29'),(35,3,'RADIOLOGY_COMPLETED','Radiology Request Completed','Radiology request completed.',4,1,'2026-08-09 23:51:51','2026-08-09 22:51:51'),(36,3,'PHYSIOTHERAPY_STARTED','Physiotherapy Started','Physiotherapy record created.',4,1,'2026-08-10 21:52:06','2026-08-10 20:52:06'),(37,3,'PHYSIOTHERAPY_COMPLETED','Physiotherapy Completed','Physiotherapy record completed.',4,1,'2026-08-10 21:53:05','2026-08-10 20:53:05'),(38,3,'THEATRE_STARTED','Theatre Started','Theatre record created.',4,1,'2026-08-10 23:24:27','2026-08-10 22:24:27'),(39,3,'THEATRE_COMPLETED','Theatre Completed','Theatre record completed.',4,1,'2026-08-10 23:24:39','2026-08-10 22:24:39'),(40,3,'DEPARTMENT_NOTIFICATION_SENT','Department Notification Sent','Attention requested from another department.',4,1,'2026-08-11 10:39:13','2026-08-11 09:39:13'),(41,3,'STATUS_CHANGED','Encounter Status Changed','Encounter status changed to Doctor.',NULL,1,'2026-08-12 16:29:05','2026-08-12 15:29:05'),(42,4,'ENCOUNTER_CREATED','Encounter Created','Encounter created and patient received in Physiotherapy.',8,1,'2026-08-12 16:40:48','2026-08-12 15:40:48'),(43,4,'QUEUED','Encounter Queued','Encounter added to the department queue.',8,1,'2026-08-12 16:40:48','2026-08-12 15:40:48'),(49,10,'ENCOUNTER_CREATED','Encounter Created','Encounter created and patient received in Doctor.',4,1,'2026-08-19 14:46:50','2026-08-19 13:46:50'),(50,10,'QUEUED','Encounter Queued','Encounter added to the department queue.',4,1,'2026-08-19 14:46:50','2026-08-19 13:46:50'),(51,10,'DOCTOR_ASSIGNED','Doctor Assigned','Doctor assigned: Amara Okafor.',4,1,'2026-08-21 08:16:19','2026-08-21 07:16:19'),(52,10,'CONSULTATION_STARTED','Consultation Started','Consultation record opened.',4,1,'2026-08-21 08:16:26','2026-08-21 07:16:26'),(53,10,'LABORATORY_REQUESTED','Laboratory Request Created','Laboratory request created.',4,1,'2026-08-21 08:16:58','2026-08-21 07:16:58'),(54,10,'LABORATORY_COMPLETED','Laboratory Request Completed','Laboratory request completed.',4,1,'2026-08-21 08:19:50','2026-08-21 07:19:50'),(55,10,'PRESCRIPTION_CREATED','Prescription Created','A prescription was created.',7,1,'2026-08-22 07:39:31','2026-08-22 06:39:31'),(56,10,'PRESCRIPTION_DISPENSED','Prescription Dispensed','A prescription was dispensed.',7,1,'2026-08-22 07:40:00','2026-08-22 06:40:00'),(57,10,'CONSULTATION_COMPLETED','Consultation Completed','Consultation completed.',4,1,'2026-08-22 07:41:07','2026-08-22 06:41:07'),(58,10,'PATIENT_ADMITTED','Patient Admitted','Patient admitted to inpatient ward/bed.',1,4,'2026-08-23 09:17:06','2026-08-23 08:17:06'),(59,10,'DOCTOR_ASSIGNED','Doctor Assigned','Doctor assigned: Olisemeke Ikhile.',4,26,'2026-08-23 11:16:47','2026-08-23 10:16:47');
/*!40000 ALTER TABLE `encounter_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_code` varchar(30) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `billable_item_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventory_items_code` (`item_code`),
  KEY `idx_inventory_items_name` (`item_name`),
  KEY `idx_inventory_items_category` (`category`),
  KEY `idx_inventory_items_billable_item` (`billable_item_id`),
  KEY `idx_inventory_items_status` (`is_active`),
  KEY `idx_inventory_items_created_at` (`created_at`),
  KEY `idx_inventory_items_created_by` (`created_by`),
  KEY `idx_inventory_items_updated_by` (`updated_by`),
  CONSTRAINT `fk_inventory_items_billable_item` FOREIGN KEY (`billable_item_id`) REFERENCES `billable_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_items_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_items_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_items`
--

LOCK TABLES `inventory_items` WRITE;
/*!40000 ALTER TABLE `inventory_items` DISABLE KEYS */;
INSERT INTO `inventory_items` VALUES (1,'924','Paracetamol 500mg','Product','Tablet','pain killer',2,1,1,1,'2026-08-22 06:26:19','2026-08-22 06:36:40');
/*!40000 ALTER TABLE `inventory_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(40) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Unpaid','Partially Paid','Paid','Cancelled') NOT NULL DEFAULT 'Unpaid',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoices_number` (`invoice_number`),
  UNIQUE KEY `uq_invoices_visit` (`visit_id`),
  KEY `idx_invoices_patient` (`patient_id`),
  KEY `idx_invoices_status` (`status`),
  KEY `idx_invoices_created_by` (`created_by`),
  KEY `idx_invoices_created_at` (`created_at`),
  CONSTRAINT `fk_invoices_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_invoices_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_invoices_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'INV-20260822-D86149',10,8,5000.00,5000.00,0.00,'Paid',1,'2026-08-22 07:49:55','2026-08-22 07:57:07');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `laboratory_requests`
--

DROP TABLE IF EXISTS `laboratory_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `laboratory_requests` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `request_source` enum('Clinical','Direct') NOT NULL DEFAULT 'Clinical',
  `tests_requested` text NOT NULL,
  `clinical_information` text DEFAULT NULL,
  `priority` enum('Routine','Urgent') NOT NULL DEFAULT 'Routine',
  `status` enum('Requested','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Requested',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_laboratory_requests_visit_created` (`visit_id`,`created_at`),
  KEY `idx_laboratory_requests_patient_created` (`patient_id`,`created_at`),
  KEY `idx_laboratory_requests_department_created` (`department_id`,`created_at`),
  KEY `idx_laboratory_requests_status_created` (`status`,`created_at`),
  KEY `idx_laboratory_requests_source_created` (`request_source`,`created_at`),
  KEY `idx_laboratory_requests_requested_by_created` (`requested_by`,`created_at`),
  CONSTRAINT `fk_laboratory_requests_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_laboratory_requests_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_laboratory_requests_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_laboratory_requests_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `laboratory_requests`
--

LOCK TABLES `laboratory_requests` WRITE;
/*!40000 ALTER TABLE `laboratory_requests` DISABLE KEYS */;
INSERT INTO `laboratory_requests` VALUES (1,3,2,1,6,'Clinical','pcp test','do well','Routine','Completed','2026-08-09 20:48:27','2026-08-09 22:37:20'),(2,3,2,1,6,'Clinical','blah blah','blah','Routine','Completed','2026-08-09 21:11:59','2026-08-09 22:35:29'),(3,10,8,1,6,'Clinical','bgdrts','n ncfgxfg','Routine','Completed','2026-08-21 08:16:58','2026-08-21 08:19:50');
/*!40000 ALTER TABLE `laboratory_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `laboratory_results`
--

DROP TABLE IF EXISTS `laboratory_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `laboratory_results` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `laboratory_request_id` bigint(20) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `sample_taken` text DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `result` text NOT NULL,
  `interpretation` text DEFAULT NULL,
  `performed_by` int(11) NOT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_laboratory_results_request` (`laboratory_request_id`),
  KEY `idx_laboratory_results_visit_created` (`visit_id`,`created_at`),
  KEY `idx_laboratory_results_patient_created` (`patient_id`,`created_at`),
  KEY `idx_laboratory_results_performed_by_created` (`performed_by`,`created_at`),
  KEY `idx_laboratory_results_completed_at` (`completed_at`),
  KEY `fk_laboratory_results_completed_by` (`completed_by`),
  CONSTRAINT `fk_laboratory_results_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_laboratory_results_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_laboratory_results_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_laboratory_results_request` FOREIGN KEY (`laboratory_request_id`) REFERENCES `laboratory_requests` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_laboratory_results_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `laboratory_results`
--

LOCK TABLES `laboratory_results` WRITE;
/*!40000 ALTER TABLE `laboratory_results` DISABLE KEYS */;
INSERT INTO `laboratory_results` VALUES (1,2,3,2,'nanna','aggag','ahagha','aahah',1,1,'2026-08-09 22:32:25','2026-08-09 22:35:29','2026-08-09 22:35:29'),(2,1,3,2,'bsjjs','sshhs','shjshsh','shshs',1,1,'2026-08-09 22:37:13','2026-08-09 22:37:20','2026-08-09 22:37:20'),(3,3,10,8,'mbkvjhv',',bhvgc','nbugcgyc','xxgfx',1,1,'2026-08-21 08:19:31','2026-08-21 08:19:50','2026-08-21 08:19:50');
/*!40000 ALTER TABLE `laboratory_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medical_document_versions`
--

DROP TABLE IF EXISTS `medical_document_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medical_document_versions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) NOT NULL,
  `version_number` int(11) NOT NULL,
  `storage_provider` varchar(40) NOT NULL DEFAULT 'local',
  `storage_key` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `mime_type` varchar(120) NOT NULL,
  `file_extension` varchar(20) NOT NULL,
  `file_size` bigint(20) unsigned NOT NULL,
  `sha256_checksum` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `upload_status` enum('Pending','Available','Quarantined','Rejected') NOT NULL DEFAULT 'Pending',
  `malware_scan_status` enum('Not Scanned','Clean','Suspicious','Infected','Scan Failed') NOT NULL DEFAULT 'Not Scanned',
  `malware_scan_reference` varchar(191) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `replacement_reason` text DEFAULT NULL,
  `supersedes_version_id` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_medical_document_version` (`document_id`,`version_number`),
  UNIQUE KEY `uq_medical_document_storage_key` (`storage_key`),
  KEY `idx_medical_document_versions_document` (`document_id`,`uploaded_at`),
  KEY `idx_medical_document_versions_status` (`upload_status`,`malware_scan_status`),
  KEY `idx_medical_document_versions_uploader` (`uploaded_by`,`uploaded_at`),
  KEY `idx_medical_document_versions_checksum` (`sha256_checksum`),
  KEY `idx_medical_document_versions_supersedes` (`supersedes_version_id`),
  CONSTRAINT `fk_medical_document_versions_document` FOREIGN KEY (`document_id`) REFERENCES `medical_documents` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_medical_document_versions_supersedes` FOREIGN KEY (`supersedes_version_id`) REFERENCES `medical_document_versions` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_medical_document_versions_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medical_document_versions`
--

LOCK TABLES `medical_document_versions` WRITE;
/*!40000 ALTER TABLE `medical_document_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `medical_document_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medical_documents`
--

DROP TABLE IF EXISTS `medical_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medical_documents` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `visit_id` int(11) DEFAULT NULL,
  `document_type` varchar(80) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `confidentiality_level` enum('Standard','Restricted','Confidential','Highly Confidential') NOT NULL DEFAULT 'Standard',
  `document_status` enum('Active','Archived','Entered-in-error') NOT NULL DEFAULT 'Active',
  `current_version` int(11) NOT NULL DEFAULT 1,
  `uploaded_by` int(11) NOT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_medical_documents_patient_status` (`patient_id`,`document_status`,`created_at`),
  KEY `idx_medical_documents_visit_status` (`visit_id`,`document_status`,`created_at`),
  KEY `idx_medical_documents_type` (`document_type`,`document_status`),
  KEY `idx_medical_documents_confidentiality` (`confidentiality_level`,`document_status`),
  KEY `idx_medical_documents_department` (`department_id`,`created_at`),
  KEY `idx_medical_documents_uploader` (`uploaded_by`,`created_at`),
  KEY `fk_medical_documents_archived_by` (`archived_by`),
  CONSTRAINT `fk_medical_documents_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_medical_documents_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_medical_documents_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_medical_documents_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_medical_documents_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medical_documents`
--

LOCK TABLES `medical_documents` WRITE;
/*!40000 ALTER TABLE `medical_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `medical_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nursing_assessments`
--

DROP TABLE IF EXISTS `nursing_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nursing_assessments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `nurse_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `general_condition` text DEFAULT NULL,
  `nursing_observation` text DEFAULT NULL,
  `pain_assessment` text DEFAULT NULL,
  `mobility` text DEFAULT NULL,
  `nutrition` text DEFAULT NULL,
  `elimination` text DEFAULT NULL,
  `skin_assessment` text DEFAULT NULL,
  `fall_risk` text DEFAULT NULL,
  `nursing_interventions` text DEFAULT NULL,
  `patient_response` text DEFAULT NULL,
  `handover_notes` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `status` enum('Draft','Completed') NOT NULL DEFAULT 'Draft',
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nursing_assessments_visit` (`visit_id`),
  KEY `idx_nursing_assessments_patient_created` (`patient_id`,`created_at`),
  KEY `idx_nursing_assessments_nurse_created` (`nurse_id`,`created_at`),
  KEY `idx_nursing_assessments_department_created` (`department_id`,`created_at`),
  KEY `idx_nursing_assessments_status_created` (`status`,`created_at`),
  KEY `fk_nursing_assessments_created_by` (`created_by`),
  KEY `fk_nursing_assessments_updated_by` (`updated_by`),
  KEY `fk_nursing_assessments_completed_by` (`completed_by`),
  CONSTRAINT `fk_nursing_assessments_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_nursing_assessments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_nursing_assessments_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_nursing_assessments_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_nursing_assessments_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_nursing_assessments_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_nursing_assessments_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_assessments`
--

LOCK TABLES `nursing_assessments` WRITE;
/*!40000 ALTER TABLE `nursing_assessments` DISABLE KEYS */;
INSERT INTO `nursing_assessments` VALUES (1,3,2,NULL,4,'aha','abab','abah','ahah','aah','ahah','ahah','ahah','ahah','ahah','ahahakjhahahahahahhahahahahahhahahahahhahahahahahahhahahahahahhahahahahhahahahahahahahahahhahahahahhahahaha','ahah','Draft',1,1,NULL,'2026-08-09 04:33:25','2026-08-09 04:34:14',NULL);
/*!40000 ALTER TABLE `nursing_assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_history`
--

DROP TABLE IF EXISTS `password_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `change_type` enum('Changed','Reset','Forced') NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_password_history_user_created` (`user_id`,`created_at`),
  KEY `fk_password_history_changed_by` (`changed_by`),
  CONSTRAINT `fk_password_history_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_password_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_history`
--

LOCK TABLES `password_history` WRITE;
/*!40000 ALTER TABLE `password_history` DISABLE KEYS */;
INSERT INTO `password_history` VALUES (1,1,'$2y$10$OVYRg6IDLR3ogSdposvMbeuPgrtB78MfZlnK2AiruD9zXUT6zouwy','Changed',1,'2026-08-05 05:57:06'),(2,1,'$2y$10$WZnb1sIG0J/.pOj9mCGrvOpyN8nLSq8UVj1w9.cq2BSa1N9wmSFkS','Reset',1,'2026-08-05 11:06:48'),(3,2,'$2y$10$0N9.CoF6tbyufpRsTnjljeMxOPrWT1ZvOkXMTd7u4rnYoAgJCRBrG','Reset',1,'2026-08-05 11:06:49'),(4,3,'$2y$10$cvPuYgLrN5MpMSWmK0StROsHuufbW4K.UY5S8RlieIaN2tnuGrI/G','Reset',1,'2026-08-05 11:06:49'),(5,4,'$2y$10$7G43TFFmJCIxCONxCpqO1OdE7xi72F1qAISD9U8bSXCmzQ4Atq/m.','Reset',1,'2026-08-05 11:06:49'),(6,5,'$2y$10$LjToFV7TjF0879NvOBAu.OiG.G9ibl.GDUry5ERFvpM9V81YPEF5K','Reset',1,'2026-08-05 11:06:49'),(7,6,'$2y$10$FLNnaBMCiIJxTwrBVOH3x.oQum9oKpZmJz07B3PwBP8/w/o235dde','Reset',1,'2026-08-05 11:06:49'),(8,7,'$2y$10$.0K2ob5.cHdErVhFQa.zQeu2bK2qb/CsY.MTcoeMX8mMWY.4WxSHK','Reset',1,'2026-08-05 11:06:49'),(9,8,'$2y$10$IlneNxk5MFGH63k1mIM5KekwGmBDrlzAhDhiJClk9lsTtfwfkK7xC','Reset',1,'2026-08-05 11:06:49'),(10,9,'$2y$10$a5huFZvsY2vist.JpN2Q7./uxCFAQxat5f8NRYl7rsRXpb7Da4KJS','Reset',1,'2026-08-05 11:06:50'),(11,1,'$2y$10$FnIJ0nHOUgqzpWyhXO7x1.XvdUf.DJycv3waqFVpfyb41YceVm77O','Changed',1,'2026-08-05 11:09:20'),(12,5,'$2y$10$wt9BsrQGAJkI6yuNPIZ7juORmG0bQaqrzyX0En1JKj/k2EW8arIxG','Changed',5,'2026-08-08 12:16:41'),(13,4,'$2y$10$CxNXvTpmY5sV0KbJEhSr9.b0aAO9cUMReTRg4afaqHDdsrdCChv0y','Changed',4,'2026-08-08 12:22:45'),(14,2,'$2y$10$x2V/9Z.YjraZw8q8UycYWOgSJInxnbfbW3PKX5A2.RAHu2hpUDg7S','Changed',2,'2026-08-19 14:10:46'),(15,2,'$2y$10$BaF.29l2BQOh6FFaQR/OWeYerRjEg6QjVBk0j0Sjt4aqYQvh4VvQC','Reset',1,'2026-08-23 10:12:53'),(16,3,'$2y$10$HMtgFGPxc2126oswHM/9qeCXSFYN16O0VADQqW/xVXkG0hh0zmxXa','Changed',3,'2026-08-23 11:05:54');
/*!40000 ALTER TABLE `password_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_alert_history`
--

DROP TABLE IF EXISTS `patient_alert_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_alert_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `alert_id` bigint(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `version_no` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `previous_snapshot` longtext DEFAULT NULL,
  `new_snapshot` longtext NOT NULL,
  `reason` text NOT NULL,
  `changed_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patient_alert_history_version` (`alert_id`,`version_no`),
  KEY `idx_alert_history_patient` (`patient_id`,`created_at`),
  KEY `idx_alert_history_actor` (`changed_by`,`created_at`),
  CONSTRAINT `fk_alert_history_actor` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_alert_history_alert` FOREIGN KEY (`alert_id`) REFERENCES `patient_alerts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_alert_history_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_alert_history`
--

LOCK TABLES `patient_alert_history` WRITE;
/*!40000 ALTER TABLE `patient_alert_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_alert_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_alerts`
--

DROP TABLE IF EXISTS `patient_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_alerts` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `visit_id` int(11) DEFAULT NULL,
  `alert_type` enum('Clinical Risk','Infection Control','Fall Risk','Communication Need','Safeguarding','Special Handling','Other') NOT NULL,
  `title` varchar(150) NOT NULL,
  `normalized_title` varchar(150) NOT NULL,
  `active_alert_key` varchar(512) DEFAULT NULL,
  `reason` text NOT NULL,
  `priority` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `confidentiality_level` enum('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `closure_reason` text DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patient_alert_active` (`active_alert_key`),
  KEY `idx_patient_alerts_patient_active` (`patient_id`,`is_active`,`priority`),
  KEY `idx_patient_alerts_effective` (`patient_id`,`is_active`,`starts_at`,`expires_at`),
  KEY `idx_patient_alerts_type_title` (`alert_type`,`normalized_title`,`is_active`),
  KEY `idx_patient_alerts_visit` (`visit_id`),
  KEY `idx_patient_alerts_confidentiality` (`confidentiality_level`,`is_active`),
  KEY `fk_patient_alerts_created_by` (`created_by`),
  KEY `fk_patient_alerts_closed_by` (`closed_by`),
  CONSTRAINT `fk_patient_alerts_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_alerts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_alerts_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_alerts_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_alerts`
--

LOCK TABLES `patient_alerts` WRITE;
/*!40000 ALTER TABLE `patient_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_allergies`
--

DROP TABLE IF EXISTS `patient_allergies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_allergies` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `source_visit_id` int(11) DEFAULT NULL,
  `allergy_type` enum('Drug','Food','Environmental','Biological','Other') NOT NULL,
  `substance` varchar(150) NOT NULL,
  `normalized_substance` varchar(150) NOT NULL,
  `active_allergy_key` varchar(512) DEFAULT NULL,
  `reaction` varchar(500) DEFAULT NULL,
  `severity` enum('Mild','Moderate','Severe','Life-threatening','Unknown') NOT NULL DEFAULT 'Unknown',
  `clinical_status` enum('Active','Inactive','Resolved','Entered-in-error') NOT NULL DEFAULT 'Active',
  `verification_status` enum('Unverified','Confirmed','Refuted') NOT NULL DEFAULT 'Unverified',
  `onset_date` date DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patient_allergy_active` (`active_allergy_key`),
  KEY `idx_patient_allergies_patient_status` (`patient_id`,`clinical_status`,`severity`),
  KEY `idx_patient_allergies_substance` (`normalized_substance`,`clinical_status`),
  KEY `idx_patient_allergies_visit` (`source_visit_id`),
  KEY `idx_patient_allergies_verification` (`verification_status`,`verified_at`),
  KEY `fk_patient_allergies_recorded_by` (`recorded_by`),
  KEY `fk_patient_allergies_verified_by` (`verified_by`),
  KEY `fk_patient_allergies_resolved_by` (`resolved_by`),
  CONSTRAINT `fk_patient_allergies_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_allergies_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_allergies_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_allergies_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_allergies_visit` FOREIGN KEY (`source_visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_allergies`
--

LOCK TABLES `patient_allergies` WRITE;
/*!40000 ALTER TABLE `patient_allergies` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_allergies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_allergy_history`
--

DROP TABLE IF EXISTS `patient_allergy_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_allergy_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `allergy_id` bigint(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `version_no` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `previous_snapshot` longtext DEFAULT NULL,
  `new_snapshot` longtext NOT NULL,
  `reason` text NOT NULL,
  `changed_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patient_allergy_history_version` (`allergy_id`,`version_no`),
  KEY `idx_allergy_history_patient` (`patient_id`,`created_at`),
  KEY `idx_allergy_history_actor` (`changed_by`,`created_at`),
  CONSTRAINT `fk_allergy_history_actor` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_allergy_history_allergy` FOREIGN KEY (`allergy_id`) REFERENCES `patient_allergies` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_allergy_history_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_allergy_history`
--

LOCK TABLES `patient_allergy_history` WRITE;
/*!40000 ALTER TABLE `patient_allergy_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_allergy_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_charges`
--

DROP TABLE IF EXISTS `patient_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_charges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `billable_item_id` int(11) NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `source_module` varchar(100) NOT NULL,
  `source_record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Cancelled') NOT NULL DEFAULT 'Active',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patient_charges_source` (`source_module`,`source_record_id`),
  KEY `idx_patient_charges_visit` (`visit_id`),
  KEY `idx_patient_charges_patient` (`patient_id`),
  KEY `idx_patient_charges_billable_item` (`billable_item_id`),
  KEY `idx_patient_charges_status` (`status`),
  KEY `idx_patient_charges_created_by` (`created_by`),
  KEY `idx_patient_charges_created_at` (`created_at`),
  KEY `idx_patient_charges_cancelled_by` (`cancelled_by`),
  CONSTRAINT `fk_patient_charges_billable_item` FOREIGN KEY (`billable_item_id`) REFERENCES `billable_items` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_charges_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_charges_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_charges_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_charges_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_charges`
--

LOCK TABLES `patient_charges` WRITE;
/*!40000 ALTER TABLE `patient_charges` DISABLE KEYS */;
INSERT INTO `patient_charges` VALUES (1,10,8,2,10.00,500.00,5000.00,'Billing',NULL,'Paracetamol 500mg','Active',1,'2026-08-22 07:49:55',NULL,NULL),(2,10,8,2,10.00,500.00,5000.00,'Billing',NULL,'Paracetamol 500mg','Cancelled',1,'2026-08-22 07:50:10',1,'2026-08-22 07:50:48');
/*!40000 ALTER TABLE `patient_charges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_demographic_history`
--

DROP TABLE IF EXISTS `patient_demographic_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_demographic_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `amendment_id` bigint(20) NOT NULL,
  `version_no` int(11) NOT NULL,
  `previous_values` longtext NOT NULL,
  `new_values` longtext NOT NULL,
  `changed_fields` longtext NOT NULL,
  `reason` text NOT NULL,
  `changed_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patient_demographic_history_version` (`patient_id`,`version_no`),
  KEY `idx_patient_demographic_history_created` (`patient_id`,`created_at`),
  KEY `idx_patient_demographic_history_actor` (`changed_by`,`created_at`),
  KEY `idx_patient_demographic_history_amendment` (`amendment_id`),
  CONSTRAINT `fk_patient_demographic_history_actor` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_demographic_history_amendment` FOREIGN KEY (`amendment_id`) REFERENCES `record_amendments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_demographic_history_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_demographic_history`
--

LOCK TABLES `patient_demographic_history` WRITE;
/*!40000 ALTER TABLE `patient_demographic_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_demographic_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_duplicate_candidates`
--

DROP TABLE IF EXISTS `patient_duplicate_candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_duplicate_candidates` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `patient_id_low` int(11) NOT NULL,
  `patient_id_high` int(11) NOT NULL,
  `match_score` decimal(5,2) NOT NULL,
  `classification` enum('Exact Match','Strong Possible Match','Possible Match','Low Confidence') NOT NULL,
  `matched_factors` longtext NOT NULL,
  `status` enum('Pending','Confirmed Duplicate','Not Duplicate','Deferred','Merge Requested') NOT NULL DEFAULT 'Pending',
  `review_decision` varchar(100) DEFAULT NULL,
  `review_reason` text DEFAULT NULL,
  `detected_by` int(11) DEFAULT NULL,
  `detected_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_duplicate_candidate_pair` (`patient_id_low`,`patient_id_high`),
  KEY `idx_duplicate_candidates_status` (`status`,`classification`,`detected_at`),
  KEY `idx_duplicate_candidates_low` (`patient_id_low`,`status`),
  KEY `idx_duplicate_candidates_high` (`patient_id_high`,`status`),
  KEY `fk_duplicate_candidates_detected_by` (`detected_by`),
  KEY `fk_duplicate_candidates_reviewed_by` (`reviewed_by`),
  CONSTRAINT `fk_duplicate_candidates_detected_by` FOREIGN KEY (`detected_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_duplicate_candidates_high` FOREIGN KEY (`patient_id_high`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_duplicate_candidates_low` FOREIGN KEY (`patient_id_low`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_duplicate_candidates_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `CONSTRAINT_1` CHECK (`patient_id_low` < `patient_id_high`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_duplicate_candidates`
--

LOCK TABLES `patient_duplicate_candidates` WRITE;
/*!40000 ALTER TABLE `patient_duplicate_candidates` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_duplicate_candidates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_identifier_history`
--

DROP TABLE IF EXISTS `patient_identifier_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_identifier_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `identifier_id` bigint(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `version_no` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `previous_snapshot` longtext DEFAULT NULL,
  `new_snapshot` longtext NOT NULL,
  `reason` text NOT NULL,
  `changed_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patient_identifier_history_version` (`identifier_id`,`version_no`),
  KEY `idx_identifier_history_patient` (`patient_id`,`created_at`),
  KEY `idx_identifier_history_actor` (`changed_by`,`created_at`),
  CONSTRAINT `fk_identifier_history_actor` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_identifier_history_identifier` FOREIGN KEY (`identifier_id`) REFERENCES `patient_identifiers` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_identifier_history_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_identifier_history`
--

LOCK TABLES `patient_identifier_history` WRITE;
/*!40000 ALTER TABLE `patient_identifier_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_identifier_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_identifiers`
--

DROP TABLE IF EXISTS `patient_identifiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_identifiers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `identifier_type` varchar(80) NOT NULL,
  `identifier_value` varchar(255) NOT NULL,
  `normalized_value` varchar(255) NOT NULL,
  `issuing_authority` varchar(150) DEFAULT NULL,
  `issuing_authority_key` varchar(150) NOT NULL DEFAULT '',
  `uniqueness_scope` enum('Global','Authority','Patient','None') NOT NULL DEFAULT 'Patient',
  `uniqueness_key` varchar(512) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `primary_key_value` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `verification_status` enum('Unverified','Verified','Rejected') NOT NULL DEFAULT 'Unverified',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patient_identifier_uniqueness` (`uniqueness_key`),
  UNIQUE KEY `uq_patient_identifier_primary` (`primary_key_value`),
  KEY `idx_patient_identifiers_patient` (`patient_id`,`is_active`,`identifier_type`),
  KEY `idx_patient_identifiers_lookup` (`identifier_type`,`normalized_value`,`is_active`),
  KEY `idx_patient_identifiers_authority` (`identifier_type`,`issuing_authority_key`,`normalized_value`),
  KEY `idx_patient_identifiers_verification` (`verification_status`,`verified_at`),
  KEY `fk_patient_identifiers_verified_by` (`verified_by`),
  KEY `fk_patient_identifiers_created_by` (`created_by`),
  KEY `fk_patient_identifiers_updated_by` (`updated_by`),
  CONSTRAINT `fk_patient_identifiers_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_identifiers_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_identifiers_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_identifiers_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_identifiers`
--

LOCK TABLES `patient_identifiers` WRITE;
/*!40000 ALTER TABLE `patient_identifiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_identifiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_medical_history`
--

DROP TABLE IF EXISTS `patient_medical_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_medical_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `source_visit_id` int(11) DEFAULT NULL,
  `history_type` enum('Past Medical History','Surgical History','Family History','Social History','Obstetric History','Immunization History','Previous Hospitalization','Previous Procedure','Other') NOT NULL,
  `title` varchar(200) NOT NULL,
  `normalized_title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `event_date` date DEFAULT NULL,
  `date_precision` enum('Exact','Month','Year','Unknown') NOT NULL DEFAULT 'Unknown',
  `status` enum('Active','Historical','Entered-in-error') NOT NULL DEFAULT 'Historical',
  `source` varchar(150) DEFAULT NULL,
  `confidentiality_level` enum('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
  `recorded_by` int(11) NOT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_medical_history_patient_type` (`patient_id`,`history_type`,`status`),
  KEY `idx_medical_history_title` (`normalized_title`,`history_type`),
  KEY `idx_medical_history_event` (`patient_id`,`event_date`),
  KEY `idx_medical_history_visit` (`source_visit_id`),
  KEY `idx_medical_history_confidentiality` (`confidentiality_level`,`status`),
  KEY `fk_medical_history_recorded_by` (`recorded_by`),
  KEY `fk_medical_history_verified_by` (`verified_by`),
  CONSTRAINT `fk_medical_history_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_medical_history_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_medical_history_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_medical_history_visit` FOREIGN KEY (`source_visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_medical_history`
--

LOCK TABLES `patient_medical_history` WRITE;
/*!40000 ALTER TABLE `patient_medical_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_medical_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_medical_history_versions`
--

DROP TABLE IF EXISTS `patient_medical_history_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_medical_history_versions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `history_entry_id` bigint(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `version_no` int(11) NOT NULL,
  `action` varchar(60) NOT NULL,
  `previous_snapshot` longtext DEFAULT NULL,
  `new_snapshot` longtext NOT NULL,
  `reason` text NOT NULL,
  `confidentiality_level` enum('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
  `changed_by` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `visit_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_medical_history_version` (`history_entry_id`,`version_no`),
  KEY `idx_medical_history_versions_patient` (`patient_id`,`created_at`),
  KEY `idx_medical_history_versions_actor` (`changed_by`,`created_at`),
  KEY `idx_medical_history_versions_visit` (`visit_id`,`created_at`),
  KEY `idx_medical_history_versions_confidentiality` (`confidentiality_level`,`created_at`),
  KEY `fk_medical_history_versions_department` (`department_id`),
  CONSTRAINT `fk_medical_history_versions_actor` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_medical_history_versions_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_medical_history_versions_entry` FOREIGN KEY (`history_entry_id`) REFERENCES `patient_medical_history` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_medical_history_versions_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_medical_history_versions_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_medical_history_versions`
--

LOCK TABLES `patient_medical_history_versions` WRITE;
/*!40000 ALTER TABLE `patient_medical_history_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_medical_history_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_problem_history`
--

DROP TABLE IF EXISTS `patient_problem_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_problem_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `problem_id` bigint(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `version_no` int(11) NOT NULL,
  `action` varchar(60) NOT NULL,
  `previous_snapshot` longtext DEFAULT NULL,
  `new_snapshot` longtext NOT NULL,
  `reason` text NOT NULL,
  `confidentiality_level` enum('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
  `changed_by` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `visit_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patient_problem_history_version` (`problem_id`,`version_no`),
  KEY `idx_problem_history_patient` (`patient_id`,`created_at`),
  KEY `idx_problem_history_actor` (`changed_by`,`created_at`),
  KEY `idx_problem_history_visit` (`visit_id`,`created_at`),
  KEY `idx_problem_history_confidentiality` (`confidentiality_level`,`created_at`),
  KEY `fk_problem_history_department` (`department_id`),
  CONSTRAINT `fk_problem_history_actor` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_problem_history_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_problem_history_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_problem_history_problem` FOREIGN KEY (`problem_id`) REFERENCES `patient_problems` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_problem_history_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_problem_history`
--

LOCK TABLES `patient_problem_history` WRITE;
/*!40000 ALTER TABLE `patient_problem_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_problem_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_problems`
--

DROP TABLE IF EXISTS `patient_problems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_problems` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `source_visit_id` int(11) DEFAULT NULL,
  `problem_code_system` varchar(80) DEFAULT NULL,
  `problem_code` varchar(80) DEFAULT NULL,
  `problem_name` varchar(200) NOT NULL,
  `normalized_problem_name` varchar(200) NOT NULL,
  `category` enum('Chronic Condition','Acute Problem','Historical Diagnosis','Surgical Condition','Risk Factor','Other') NOT NULL DEFAULT 'Other',
  `clinical_status` enum('Active','Inactive','Resolved','Entered-in-error') NOT NULL DEFAULT 'Active',
  `verification_status` enum('Unverified','Confirmed','Refuted') NOT NULL DEFAULT 'Unverified',
  `severity` enum('Mild','Moderate','Severe','Unknown') NOT NULL DEFAULT 'Unknown',
  `confidentiality_level` enum('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
  `onset_date` date DEFAULT NULL,
  `recorded_date` date NOT NULL,
  `resolved_date` date DEFAULT NULL,
  `active_problem_key` varchar(512) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patient_problem_active` (`active_problem_key`),
  KEY `idx_patient_problems_status` (`patient_id`,`clinical_status`,`verification_status`),
  KEY `idx_patient_problems_severity` (`patient_id`,`clinical_status`,`severity`),
  KEY `idx_patient_problems_name` (`normalized_problem_name`,`clinical_status`),
  KEY `idx_patient_problems_code` (`problem_code_system`,`problem_code`),
  KEY `idx_patient_problems_visit` (`source_visit_id`),
  KEY `idx_patient_problems_confidentiality` (`confidentiality_level`,`clinical_status`),
  KEY `fk_patient_problems_recorded_by` (`recorded_by`),
  KEY `fk_patient_problems_verified_by` (`verified_by`),
  KEY `fk_patient_problems_resolved_by` (`resolved_by`),
  CONSTRAINT `fk_patient_problems_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_problems_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_problems_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_problems_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_problems_visit` FOREIGN KEY (`source_visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_problems`
--

LOCK TABLES `patient_problems` WRITE;
/*!40000 ALTER TABLE `patient_problems` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_problems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hospital_number` varchar(30) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `normalized_first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `normalized_middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `normalized_last_name` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female','Other','Unknown') NOT NULL,
  `date_of_birth` date NOT NULL,
  `marital_status` varchar(30) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `place_of_work` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `normalized_phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `normalized_email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state_of_origin` varchar(100) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `ethnic_group` varchar(100) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `genotype` varchar(5) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `next_of_kin` varchar(150) DEFAULT NULL,
  `next_of_kin_relationship` varchar(100) DEFAULT NULL,
  `next_of_kin_phone` varchar(20) DEFAULT NULL,
  `next_of_kin_address` text DEFAULT NULL,
  `registered_by` int(11) DEFAULT NULL,
  `demographic_version` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patients_hospital_number` (`hospital_number`),
  KEY `idx_patients_last_name` (`last_name`),
  KEY `idx_patients_first_name` (`first_name`),
  KEY `idx_patients_phone` (`phone`),
  KEY `idx_patients_registered_by` (`registered_by`),
  KEY `idx_patients_demographic_version` (`id`,`demographic_version`),
  KEY `idx_patients_normalized_name` (`normalized_last_name`,`normalized_first_name`,`date_of_birth`),
  KEY `idx_patients_normalized_phone` (`normalized_phone`),
  KEY `idx_patients_normalized_email` (`normalized_email`),
  KEY `idx_patients_dob_normalized_name` (`date_of_birth`,`normalized_last_name`,`normalized_first_name`),
  CONSTRAINT `fk_patients_registered_by` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (2,'DEV-PATIENT-0001','Development','development',NULL,NULL,'PatientOne','patientone','Unknown','1985-01-15',NULL,NULL,NULL,'08000000001','08000000001',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2026-08-05 04:48:53',NULL),(3,'DEV-PATIENT-0002','Development','development',NULL,NULL,'PatientTwo','patienttwo','Unknown','1992-06-30',NULL,NULL,NULL,'08000000002','08000000002',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2026-08-05 04:48:53',NULL),(8,'HMS-2026-000008','Eseosa','eseosa',NULL,'','Eghomwanre','eghomwanre','Male','2026-07-28','Single','Non',NULL,'08138127403','08138127403','eseosaeghomwanre@gmail.com','eseosaeghomwanre@gmail.com','25, Oni-Edigin Street, Off Iguikpe Road, Useh Quarters, Benin City, Edo State.\r\n25, Oni-Edigin Street, Off Iguikpe Road, Useh Quarters, Benin City, Edo State.',NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,2,1,'2026-08-19 13:39:17','2026-08-19 13:39:17');
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('Cash','Card','Transfer','Other') NOT NULL,
  `reference` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `received_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payments_invoice` (`invoice_id`),
  KEY `idx_payments_visit` (`visit_id`),
  KEY `idx_payments_patient` (`patient_id`),
  KEY `idx_payments_received_by` (`received_by`),
  KEY `idx_payments_created_at` (`created_at`),
  CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,10,8,5000.00,'Cash','1234567',NULL,1,'2026-08-22 07:57:07');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) NOT NULL,
  `permission_name` varchar(150) NOT NULL,
  `module` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_key` (`permission_key`),
  KEY `idx_permissions_module` (`module`),
  KEY `idx_permissions_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'view_encounter','View Encounters','Visits','View encounter workspaces.',1,'2026-08-05 04:16:38',NULL),(2,'create_encounter','Create Encounters','Visits','Create new encounters.',1,'2026-08-05 04:16:38',NULL),(3,'transfer_encounter','Transfer Encounters','Visits','Transfer encounters between departments.',1,'2026-08-05 04:16:38',NULL),(4,'receive_encounter','Receive Encounters','Visits','Receive transferred encounters.',1,'2026-08-05 04:16:38',NULL),(5,'assign_doctor','Assign Doctor','Visits','Assign a doctor to an encounter.',1,'2026-08-05 04:16:38',NULL),(6,'change_encounter_status','Change Encounter Status','Visits','Change encounter lifecycle status.',1,'2026-08-05 04:16:38',NULL),(7,'edit_encounter','Edit Encounters','Visits','Edit active encounter data.',1,'2026-08-05 04:16:38',NULL),(8,'manage_users','Manage Users','Administration','Create and administer user accounts.',1,'2026-08-05 04:16:38',NULL),(9,'manage_roles','Manage Roles','Administration','Create and administer roles.',1,'2026-08-05 04:16:38',NULL),(10,'manage_permissions','Manage Permissions','Administration','Assign and administer permissions.',1,'2026-08-05 04:16:38',NULL),(11,'manage_settings','Manage System Settings','Administration','View and administer enterprise system settings.',1,'2026-08-05 04:16:38',NULL),(12,'view_patient_identifiers','View Patient Identifiers','Medical Records','View authorized patient identifiers.',1,'2026-08-05 04:16:44',NULL),(13,'manage_patient_identifiers','Manage Patient Identifiers','Medical Records','Create, amend, deactivate, and select primary patient identifiers.',1,'2026-08-05 04:16:44',NULL),(14,'verify_patient_identifiers','Verify Patient Identifiers','Medical Records','Verify patient identifier evidence.',1,'2026-08-05 04:16:44',NULL),(15,'view_duplicate_candidates','View Duplicate Candidates','Medical Records','View possible duplicate patient cases.',1,'2026-08-05 04:16:44',NULL),(16,'review_duplicate_candidates','Review Duplicate Candidates','Medical Records','Record a controlled duplicate-case review decision.',1,'2026-08-05 04:16:44',NULL),(17,'view_medical_record','View Medical Records','Medical Records','View an authorized patient longitudinal chart.',1,'2026-08-05 04:48:44',NULL),(18,'edit_patient_demographics','Edit Patient Demographics','Medical Records','Correct patient demographics with versioned history.',1,'2026-08-05 04:48:44',NULL),(19,'view_patient_audit_history','View Patient Audit History','Medical Records','View patient-specific audit and demographic history.',1,'2026-08-05 04:48:44',NULL),(28,'view_clinical_safety','View Clinical Safety','Medical Records','View authorized longitudinal allergies and clinical alerts.',1,'2026-08-05 07:26:17',NULL),(29,'record_allergies','Record Allergies','Medical Records','Record structured patient allergy information.',1,'2026-08-05 07:26:17',NULL),(30,'update_allergies','Update Allergies','Medical Records','Correct active structured allergy information.',1,'2026-08-05 07:26:17',NULL),(31,'verify_allergies','Verify Allergies','Medical Records','Clinically verify recorded allergy information.',1,'2026-08-05 07:26:17',NULL),(32,'resolve_allergies','Resolve Allergies','Medical Records','Resolve or mark allergy records entered in error.',1,'2026-08-05 07:26:17',NULL),(33,'manage_clinical_alerts','Manage Clinical Alerts','Medical Records','Create, update, close, and reactivate clinical alerts.',1,'2026-08-05 07:26:17',NULL),(34,'view_confidential_alerts','View Confidential Alerts','Medical Records','View restricted and confidential clinical alert details.',1,'2026-08-05 07:26:17',NULL),(35,'view_clinical_safety_history','View Clinical Safety History','Medical Records','View allergy and clinical alert version history.',1,'2026-08-05 07:26:17',NULL),(36,'view_problem_list','View Problem List','Medical Records','View authorized longitudinal patient problems.',1,'2026-08-05 13:24:56',NULL),(37,'manage_problem_list','Manage Problem List','Medical Records','Create and update longitudinal patient problems.',1,'2026-08-05 13:24:56',NULL),(38,'verify_problem_list','Verify Problem List','Medical Records','Clinically verify or refute longitudinal problems.',1,'2026-08-05 13:24:56',NULL),(39,'resolve_problem_list','Resolve Problem List','Medical Records','Resolve, deactivate, reactivate, or mark problems entered in error.',1,'2026-08-05 13:24:56',NULL),(40,'view_medical_history','View Medical History','Medical Records','View structured longitudinal medical history.',1,'2026-08-05 13:24:56',NULL),(41,'manage_medical_history','Manage Medical History','Medical Records','Create, update, and correct structured medical history.',1,'2026-08-05 13:24:56',NULL),(42,'verify_medical_history','Verify Medical History','Medical Records','Clinically verify structured medical history.',1,'2026-08-05 13:24:56',NULL),(43,'view_confidential_medical_history','View Confidential Medical History','Medical Records','View restricted problems and structured history details.',1,'2026-08-05 13:24:56',NULL),(44,'view_problem_history','View Problem History','Medical Records','View problem and structured medical-history versions.',1,'2026-08-05 13:24:56',NULL),(45,'view_medical_documents','View Medical Documents','Medical Records','View authorized patient and encounter document metadata.',1,'2026-08-05 14:59:38',NULL),(46,'upload_medical_documents','Upload Medical Documents','Medical Records','Upload authorized patient and encounter documents.',1,'2026-08-05 14:59:38',NULL),(47,'replace_medical_documents','Replace Medical Documents','Medical Records','Create replacement versions of authorized documents.',1,'2026-08-05 14:59:38',NULL),(48,'archive_medical_documents','Archive Medical Documents','Medical Records','Archive, restore, or mark authorized documents entered in error.',1,'2026-08-05 14:59:38',NULL),(49,'download_medical_documents','Download Medical Documents','Medical Records','Download available authorized document versions.',1,'2026-08-05 14:59:38',NULL),(50,'view_confidential_documents','View Confidential Documents','Medical Records','View and download restricted or confidential document details.',1,'2026-08-05 14:59:38',NULL),(51,'view_document_history','View Document History','Medical Records','View authorized immutable document versions.',1,'2026-08-05 14:59:38',NULL),(52,'view_clinical_notes','View Clinical Notes','Medical Records','View authorized patient-level and encounter-linked Clinical Notes.',1,'2026-08-05 16:45:40',NULL),(53,'create_patient_notes','Create Patient Notes','Medical Records','Create authorized longitudinal patient Clinical Note drafts.',1,'2026-08-05 16:45:40',NULL),(54,'create_encounter_notes','Create Encounter Notes','Medical Records','Create authorized encounter-linked Clinical Note drafts.',1,'2026-08-05 16:45:40',NULL),(55,'edit_own_note_drafts','Edit Own Note Drafts','Medical Records','Append new draft versions to notes authored by the current user.',1,'2026-08-05 16:45:40',NULL),(56,'edit_any_note_draft','Edit Any Note Draft','Medical Records','Append new draft versions to another author\'s authorized draft.',1,'2026-08-05 16:45:40',NULL),(57,'sign_clinical_notes','Sign Clinical Notes','Medical Records','Sign authorized Clinical Note types and lock their content.',1,'2026-08-05 16:45:40',NULL),(58,'amend_signed_notes','Amend Signed Notes','Medical Records','Request or apply authorized amendments to signed Clinical Notes.',1,'2026-08-05 16:45:40',NULL),(59,'approve_note_amendments','Approve Note Amendments','Medical Records','Approve or reject Clinical Note amendment requests.',1,'2026-08-05 16:45:40',NULL),(60,'mark_note_entered_in_error','Mark Note Entered in Error','Medical Records','Mark an authorized Clinical Note entered in error without deleting history.',1,'2026-08-05 16:45:40',NULL),(61,'view_confidential_notes','View Confidential Notes','Medical Records','View restricted or confidential Clinical Note content.',1,'2026-08-05 16:45:40',NULL),(62,'view_note_history','View Clinical Note History','Medical Records','View authorized immutable Clinical Note versions and amendment history.',1,'2026-08-05 16:45:40',NULL),(63,'view_consultation','View Consultation','Consultation','View encounter consultation records.',1,'2026-08-08 10:16:44',NULL),(64,'create_consultation','Create Consultation','Consultation','Create a consultation for an active encounter.',1,'2026-08-08 10:16:44',NULL),(65,'edit_consultation','Edit Consultation','Consultation','Edit draft consultation records.',1,'2026-08-08 10:16:44',NULL),(66,'complete_consultation','Complete Consultation','Consultation','Complete a draft consultation.',1,'2026-08-08 10:16:44',NULL),(67,'view_vital_signs','View Vital Signs','Vital Signs','View patient and encounter vital signs.',1,'2026-08-08 19:58:35',NULL),(68,'create_vital_signs','Create Vital Signs','Vital Signs','Record vital signs for an active encounter.',1,'2026-08-08 19:58:35',NULL),(69,'edit_vital_signs','Edit Vital Signs','Vital Signs','Edit recorded vital signs.',1,'2026-08-08 19:58:35',NULL),(70,'view_nursing','View Nursing','Nursing','View nursing assessments and summaries.',1,'2026-08-09 03:24:31',NULL),(71,'create_nursing','Create Nursing Assessment','Nursing','Start a nursing assessment for an active encounter.',1,'2026-08-09 03:24:31',NULL),(72,'edit_nursing','Edit Nursing Assessment','Nursing','Edit a draft nursing assessment.',1,'2026-08-09 03:24:31',NULL),(73,'complete_nursing','Complete Nursing Assessment','Nursing','Complete a draft nursing assessment.',1,'2026-08-09 03:24:31',NULL),(74,'view_laboratory','View Laboratory','Laboratory','View laboratory requests and results.',1,'2026-08-09 06:46:51',NULL),(75,'create_laboratory_request','Create Laboratory Request','Laboratory','Create a laboratory request for an encounter.',1,'2026-08-09 06:46:51',NULL),(76,'process_laboratory_request','Process Laboratory Request','Laboratory','Start and process laboratory requests.',1,'2026-08-09 06:46:51',NULL),(77,'enter_laboratory_result','Enter Laboratory Result','Laboratory','Enter a laboratory result.',1,'2026-08-09 06:46:51',NULL),(78,'edit_laboratory_result','Edit Laboratory Result','Laboratory','Edit a laboratory result.',1,'2026-08-09 06:46:51',NULL),(79,'complete_laboratory_request','Complete Laboratory Request','Laboratory','Complete a laboratory request.',1,'2026-08-09 06:46:51',NULL),(80,'view_radiology','View Radiology','Radiology','View radiology requests and reports.',1,'2026-08-09 22:34:05',NULL),(81,'create_radiology_request','Create Radiology Request','Radiology','Create a radiology request for an encounter.',1,'2026-08-09 22:34:05',NULL),(82,'process_radiology_request','Process Radiology Request','Radiology','Start and process radiology requests.',1,'2026-08-09 22:34:05',NULL),(83,'enter_radiology_report','Enter Radiology Report','Radiology','Enter a radiology report.',1,'2026-08-09 22:34:05',NULL),(84,'edit_radiology_report','Edit Radiology Report','Radiology','Edit a radiology report.',1,'2026-08-09 22:34:05',NULL),(85,'complete_radiology_request','Complete Radiology Request','Radiology','Complete a radiology request.',1,'2026-08-09 22:34:05',NULL),(86,'view_physiotherapy','View Physiotherapy','Physiotherapy','View physiotherapy records and sessions.',1,'2026-08-10 20:47:55',NULL),(87,'create_physiotherapy','Create Physiotherapy Record','Physiotherapy','Create a physiotherapy record for an encounter.',1,'2026-08-10 20:47:55',NULL),(88,'edit_physiotherapy','Edit Physiotherapy Record','Physiotherapy','Edit a physiotherapy record.',1,'2026-08-10 20:47:55',NULL),(89,'manage_physiotherapy_sessions','Manage Physiotherapy Sessions','Physiotherapy','Create and edit physiotherapy sessions.',1,'2026-08-10 20:47:55',NULL),(90,'complete_physiotherapy','Complete Physiotherapy Record','Physiotherapy','Complete a physiotherapy record.',1,'2026-08-10 20:47:55',NULL),(91,'view_theatre','View Theatre','Theatre','View theatre records and history.',1,'2026-08-10 22:09:01',NULL),(92,'create_theatre','Create Theatre','Theatre','Create theatre records.',1,'2026-08-10 22:09:01',NULL),(93,'edit_theatre','Edit Theatre','Theatre','Edit draft theatre records.',1,'2026-08-10 22:09:01',NULL),(94,'complete_theatre','Complete Theatre','Theatre','Complete theatre records.',1,'2026-08-10 22:09:01',NULL),(95,'view_billable_items','View Billable Items','Accounts','View the price catalogue.',1,'2026-08-10 23:29:27',NULL),(96,'create_billable_items','Create Billable Items','Accounts','Create price catalogue items.',1,'2026-08-10 23:29:27',NULL),(97,'edit_billable_items','Edit Billable Items','Accounts','Edit price catalogue items.',1,'2026-08-10 23:29:27',NULL),(98,'manage_billable_item_status','Manage Billable Item Status','Accounts','Activate and deactivate price catalogue items.',1,'2026-08-10 23:29:27',NULL),(99,'view_inventory','View Inventory','Store','View inventory items and stock balances.',1,'2026-08-11 00:01:09',NULL),(100,'manage_inventory_items','Manage Inventory Items','Store','Create and edit inventory items.',1,'2026-08-11 00:01:09',NULL),(101,'receive_stock','Receive Stock','Store','Receive stock into store inventory.',1,'2026-08-11 00:01:09',NULL),(102,'issue_stock','Issue Stock','Store','Issue stock to departments.',1,'2026-08-11 00:01:09',NULL),(103,'return_stock','Return Stock','Store','Return stock from departments.',1,'2026-08-11 00:01:09',NULL),(104,'adjust_stock','Adjust Stock','Store','Record stock adjustments.',1,'2026-08-11 00:01:09',NULL),(105,'view_stock_ledger','View Stock Ledger','Store','View stock movement ledger.',1,'2026-08-11 00:01:09',NULL),(106,'view_pharmacy','View Pharmacy','Pharmacy','View prescriptions and dispensing records.',1,'2026-08-11 21:27:40',NULL),(107,'create_prescription','Create Prescription','Pharmacy','Create pharmacy prescriptions.',1,'2026-08-11 21:27:40',NULL),(108,'edit_prescription','Edit Prescription','Pharmacy','Edit pharmacy prescriptions before dispensing.',1,'2026-08-11 21:27:40',NULL),(109,'dispense_prescription','Dispense Prescription','Pharmacy','Dispense pharmacy prescriptions.',1,'2026-08-11 21:27:40',NULL),(110,'view_reports','View Reports','Reports','View the basic reports module.',1,'2026-08-12 08:25:36',NULL),(111,'view_financial_reports','View Financial Reports','Reports','View Billing financial summaries.',1,'2026-08-12 08:25:36',NULL),(112,'view_inventory_reports','View Inventory Reports','Reports','View Store inventory summaries.',1,'2026-08-12 08:25:36',NULL),(113,'view_clinical_reports','View Clinical Reports','Reports','View aggregate clinical activity summaries.',1,'2026-08-12 08:25:36',NULL),(114,'view_admissions','View Admissions','Admissions','View inpatient admissions, ward census, and bed occupancy.',1,'2026-08-19 11:04:29',NULL),(115,'create_admission','Create Admission','Admissions','Admit a patient to a ward and bed.',1,'2026-08-19 11:04:29',NULL),(116,'transfer_admission','Transfer Admission','Admissions','Transfer an admitted patient between wards or beds.',1,'2026-08-19 11:04:29',NULL),(117,'discharge_admission','Discharge Admission','Admissions','Discharge or cancel inpatient admissions.',1,'2026-08-19 11:04:29',NULL),(118,'manage_wards_beds','Manage Wards and Beds','Admissions','Create and maintain wards and beds.',1,'2026-08-19 11:04:29',NULL);
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pharmacy_dispensing`
--

DROP TABLE IF EXISTS `pharmacy_dispensing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pharmacy_dispensing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prescription_id` int(11) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `inventory_item_id` int(11) NOT NULL,
  `quantity_dispensed` decimal(12,2) NOT NULL,
  `dispensing_notes` text DEFAULT NULL,
  `dispensed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pharmacy_dispensing_prescription` (`prescription_id`),
  KEY `idx_pharmacy_dispensing_visit_created` (`visit_id`,`created_at`),
  KEY `idx_pharmacy_dispensing_patient_created` (`patient_id`,`created_at`),
  KEY `idx_pharmacy_dispensing_item_created` (`inventory_item_id`,`created_at`),
  KEY `idx_pharmacy_dispensing_dispensed_by_created` (`dispensed_by`,`created_at`),
  CONSTRAINT `fk_pharmacy_dispensing_dispensed_by` FOREIGN KEY (`dispensed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pharmacy_dispensing_inventory_item` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pharmacy_dispensing_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pharmacy_dispensing_prescription` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pharmacy_dispensing_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_dispensing`
--

LOCK TABLES `pharmacy_dispensing` WRITE;
/*!40000 ALTER TABLE `pharmacy_dispensing` DISABLE KEYS */;
INSERT INTO `pharmacy_dispensing` VALUES (1,1,10,8,1,10.00,NULL,1,'2026-08-22 06:40:00');
/*!40000 ALTER TABLE `pharmacy_dispensing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phase1_patient_gender_repair`
--

DROP TABLE IF EXISTS `phase1_patient_gender_repair`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phase1_patient_gender_repair` (
  `patient_id` int(11) NOT NULL,
  `previous_gender` varchar(30) NOT NULL,
  `repaired_gender` varchar(30) NOT NULL,
  `repaired_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`patient_id`),
  CONSTRAINT `fk_phase1_patient_gender_repair_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phase1_patient_gender_repair`
--

LOCK TABLES `phase1_patient_gender_repair` WRITE;
/*!40000 ALTER TABLE `phase1_patient_gender_repair` DISABLE KEYS */;
/*!40000 ALTER TABLE `phase1_patient_gender_repair` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phase1_visit_status_repair`
--

DROP TABLE IF EXISTS `phase1_visit_status_repair`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phase1_visit_status_repair` (
  `visit_id` int(11) NOT NULL,
  `previous_status` varchar(50) NOT NULL,
  `repaired_status` varchar(50) NOT NULL,
  `repaired_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`visit_id`),
  CONSTRAINT `fk_phase1_status_repair_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phase1_visit_status_repair`
--

LOCK TABLES `phase1_visit_status_repair` WRITE;
/*!40000 ALTER TABLE `phase1_visit_status_repair` DISABLE KEYS */;
/*!40000 ALTER TABLE `phase1_visit_status_repair` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `physiotherapy_records`
--

DROP TABLE IF EXISTS `physiotherapy_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `physiotherapy_records` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `physiotherapist_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `record_source` enum('Clinical','Direct') NOT NULL DEFAULT 'Clinical',
  `referral_reason` text DEFAULT NULL,
  `presenting_problem` text NOT NULL,
  `assessment` text NOT NULL,
  `functional_limitations` text DEFAULT NULL,
  `treatment_plan` text NOT NULL,
  `goals` text DEFAULT NULL,
  `precautions` text DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') NOT NULL DEFAULT 'Active',
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_physiotherapy_records_visit` (`visit_id`),
  KEY `idx_physiotherapy_records_patient_created` (`patient_id`,`created_at`),
  KEY `idx_physiotherapy_records_physio_created` (`physiotherapist_id`,`created_at`),
  KEY `idx_physiotherapy_records_department_created` (`department_id`,`created_at`),
  KEY `idx_physiotherapy_records_source_created` (`record_source`,`created_at`),
  KEY `idx_physiotherapy_records_status_created` (`status`,`created_at`),
  KEY `idx_physiotherapy_records_creator_created` (`created_by`,`created_at`),
  KEY `fk_physiotherapy_records_updated_by` (`updated_by`),
  KEY `fk_physiotherapy_records_completed_by` (`completed_by`),
  CONSTRAINT `fk_physiotherapy_records_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_physiotherapy_records_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_physiotherapy_records_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_physiotherapy_records_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_physiotherapy_records_physio` FOREIGN KEY (`physiotherapist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_physiotherapy_records_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_physiotherapy_records_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physiotherapy_records`
--

LOCK TABLES `physiotherapy_records` WRITE;
/*!40000 ALTER TABLE `physiotherapy_records` DISABLE KEYS */;
INSERT INTO `physiotherapy_records` VALUES (1,3,2,1,8,'Clinical','nvhc','nbcgxgf','kw jbs','nmsns','sbvs','ssjgs','ssgs','Completed',1,1,1,'2026-08-10 21:52:06','2026-08-10 21:53:05','2026-08-10 21:53:05');
/*!40000 ALTER TABLE `physiotherapy_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `physiotherapy_sessions`
--

DROP TABLE IF EXISTS `physiotherapy_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `physiotherapy_sessions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `physiotherapy_record_id` bigint(20) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `session_date` datetime NOT NULL,
  `treatment_given` text NOT NULL,
  `patient_response` text DEFAULT NULL,
  `progress_notes` text DEFAULT NULL,
  `next_plan` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_physiotherapy_sessions_record_date` (`physiotherapy_record_id`,`session_date`),
  KEY `idx_physiotherapy_sessions_visit_date` (`visit_id`,`session_date`),
  KEY `idx_physiotherapy_sessions_patient_date` (`patient_id`,`session_date`),
  KEY `idx_physiotherapy_sessions_recorded_by_date` (`recorded_by`,`session_date`),
  CONSTRAINT `fk_physiotherapy_sessions_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_physiotherapy_sessions_record` FOREIGN KEY (`physiotherapy_record_id`) REFERENCES `physiotherapy_records` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_physiotherapy_sessions_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_physiotherapy_sessions_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physiotherapy_sessions`
--

LOCK TABLES `physiotherapy_sessions` WRITE;
/*!40000 ALTER TABLE `physiotherapy_sessions` DISABLE KEYS */;
INSERT INTO `physiotherapy_sessions` VALUES (1,1,3,2,'2026-08-10 22:52:00','bghvgz','zmnzh','znmbzbnz','zzbzb',1,'2026-08-10 21:52:53','2026-08-10 21:52:53');
/*!40000 ALTER TABLE `physiotherapy_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `prescribed_by` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `prescription_source` enum('Clinical','Direct') NOT NULL,
  `inventory_item_id` int(11) DEFAULT NULL,
  `medication_name` varchar(255) NOT NULL,
  `dosage` text DEFAULT NULL,
  `frequency` text DEFAULT NULL,
  `duration` text DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `instructions` text DEFAULT NULL,
  `status` enum('Prescribed','Dispensed','Cancelled') NOT NULL DEFAULT 'Prescribed',
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `dispensed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_prescriptions_visit_created` (`visit_id`,`created_at`),
  KEY `idx_prescriptions_patient_created` (`patient_id`,`created_at`),
  KEY `idx_prescriptions_status_created` (`status`,`created_at`),
  KEY `idx_prescriptions_source_created` (`prescription_source`,`created_at`),
  KEY `idx_prescriptions_department_created` (`department_id`,`created_at`),
  KEY `idx_prescriptions_item_created` (`inventory_item_id`,`created_at`),
  KEY `idx_prescriptions_prescribed_by_created` (`prescribed_by`,`created_at`),
  KEY `idx_prescriptions_created_by_created` (`created_by`,`created_at`),
  KEY `fk_prescriptions_updated_by` (`updated_by`),
  CONSTRAINT `fk_prescriptions_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prescriptions_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_prescriptions_inventory_item` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_prescriptions_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prescriptions_prescribed_by` FOREIGN KEY (`prescribed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_prescriptions_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_prescriptions_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescriptions`
--

LOCK TABLES `prescriptions` WRITE;
/*!40000 ALTER TABLE `prescriptions` DISABLE KEYS */;
INSERT INTO `prescriptions` VALUES (1,10,8,NULL,7,'Clinical',1,'Paracetamol','2','1-1-1','one week',10.00,'remember to take them','Dispensed',1,1,'2026-08-22 06:39:31','2026-08-22 06:40:00','2026-08-22 06:40:00');
/*!40000 ALTER TABLE `prescriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `radiology_reports`
--

DROP TABLE IF EXISTS `radiology_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `radiology_reports` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `radiology_request_id` bigint(20) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `findings` text DEFAULT NULL,
  `impression` text NOT NULL,
  `recommendation` text DEFAULT NULL,
  `performed_by` int(11) NOT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_radiology_reports_request` (`radiology_request_id`),
  KEY `idx_radiology_reports_visit_created` (`visit_id`,`created_at`),
  KEY `idx_radiology_reports_patient_created` (`patient_id`,`created_at`),
  KEY `idx_radiology_reports_performed_by_created` (`performed_by`,`created_at`),
  KEY `idx_radiology_reports_completed_at` (`completed_at`),
  KEY `fk_radiology_reports_completed_by` (`completed_by`),
  CONSTRAINT `fk_radiology_reports_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_radiology_reports_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_radiology_reports_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_radiology_reports_request` FOREIGN KEY (`radiology_request_id`) REFERENCES `radiology_requests` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_radiology_reports_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_reports`
--

LOCK TABLES `radiology_reports` WRITE;
/*!40000 ALTER TABLE `radiology_reports` DISABLE KEYS */;
INSERT INTO `radiology_reports` VALUES (1,1,3,2,'aanan','ajaaja','ajajaj',1,1,'2026-08-09 23:51:44','2026-08-09 23:51:51','2026-08-09 23:51:51');
/*!40000 ALTER TABLE `radiology_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `radiology_requests`
--

DROP TABLE IF EXISTS `radiology_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `radiology_requests` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `request_source` enum('Clinical','Direct') NOT NULL DEFAULT 'Clinical',
  `study_requested` text NOT NULL,
  `clinical_indication` text DEFAULT NULL,
  `priority` enum('Routine','Urgent') NOT NULL DEFAULT 'Routine',
  `status` enum('Requested','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Requested',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_radiology_requests_visit_created` (`visit_id`,`created_at`),
  KEY `idx_radiology_requests_patient_created` (`patient_id`,`created_at`),
  KEY `idx_radiology_requests_department_created` (`department_id`,`created_at`),
  KEY `idx_radiology_requests_status_created` (`status`,`created_at`),
  KEY `idx_radiology_requests_source_created` (`request_source`,`created_at`),
  KEY `idx_radiology_requests_requested_by_created` (`requested_by`,`created_at`),
  CONSTRAINT `fk_radiology_requests_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_radiology_requests_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_radiology_requests_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_radiology_requests_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_requests`
--

LOCK TABLES `radiology_requests` WRITE;
/*!40000 ALTER TABLE `radiology_requests` DISABLE KEYS */;
INSERT INTO `radiology_requests` VALUES (1,3,2,1,9,'Clinical','hah','ahah','Routine','Completed','2026-08-09 23:51:29','2026-08-09 23:51:51');
/*!40000 ALTER TABLE `radiology_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `record_access_logs`
--

DROP TABLE IF EXISTS `record_access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `record_access_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `visit_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `access_type` varchar(100) NOT NULL,
  `resource_type` varchar(100) NOT NULL,
  `resource_id` bigint(20) DEFAULT NULL,
  `access_reason` varchar(255) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_record_access_patient_created` (`patient_id`,`created_at`),
  KEY `idx_record_access_user_created` (`user_id`,`created_at`),
  KEY `idx_record_access_department_created` (`department_id`,`created_at`),
  KEY `idx_record_access_visit` (`visit_id`),
  KEY `idx_record_access_resource` (`resource_type`,`resource_id`,`created_at`),
  CONSTRAINT `fk_record_access_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_record_access_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_record_access_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_record_access_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `record_access_logs`
--

LOCK TABLES `record_access_logs` WRITE;
/*!40000 ALTER TABLE `record_access_logs` DISABLE KEYS */;
INSERT INTO `record_access_logs` VALUES (1,3,NULL,1,1,'VIEW','PatientChart',3,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 04:14:29'),(2,3,NULL,1,1,'VIEW','PatientChart',3,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 04:15:00'),(3,3,NULL,1,1,'VIEW','PatientChart',3,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 04:15:16'),(4,3,NULL,1,1,'VIEW','PatientChart',3,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 04:15:33'),(5,3,NULL,1,1,'VIEW','PatientChart',3,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 04:15:50'),(6,3,NULL,1,1,'VIEW','PatientChart',3,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 04:16:06'),(7,3,NULL,1,1,'VIEW','PatientChart',3,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 04:19:17'),(8,3,NULL,1,1,'VIEW','PatientChart',3,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 08:12:49'),(9,3,NULL,1,1,'VIEW','PatientChart',3,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 08:14:12'),(10,3,NULL,1,1,'VIEW','PatientChart',3,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 08:14:16'),(11,3,NULL,1,1,'VIEW','PatientChart',3,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 08:14:19'),(12,3,NULL,1,1,'VIEW','PatientChart',3,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-17 10:16:30'),(13,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:39:33'),(14,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:39:43'),(15,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:39:47'),(16,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:39:48'),(17,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:40:03'),(18,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:45:08'),(19,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:47:12'),(20,8,NULL,1,1,'VIEW','PatientChart',8,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:51:07'),(21,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:51:39'),(22,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:51:52'),(23,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:51:59'),(24,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:52:10'),(25,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:52:13'),(26,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:52:14'),(27,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:52:22'),(28,8,NULL,2,2,'VIEW','PatientChart',8,'Longitudinal patient chart access.','192.168.1.239','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 14:52:25'),(29,8,NULL,4,5,'VIEW','PatientChart',8,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 08:52:39'),(30,8,NULL,4,5,'VIEW','PatientChart',8,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 08:52:55'),(31,8,NULL,4,5,'VIEW','PatientChart',8,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 08:52:58'),(32,8,NULL,4,5,'VIEW','PatientChart',8,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 08:53:02'),(33,8,NULL,4,5,'VIEW','PatientChart',8,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 08:53:05'),(34,8,NULL,4,5,'VIEW','PatientChart',8,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 08:53:17'),(35,8,NULL,4,5,'VIEW','PatientChart',8,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 08:53:23'),(36,8,NULL,4,5,'VIEW','PatientChart',8,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 08:53:55'),(37,2,NULL,4,5,'VIEW','PatientChart',2,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 09:46:22'),(38,2,NULL,4,5,'VIEW','PatientChart',2,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 09:46:28'),(39,2,NULL,4,5,'VIEW','PatientChart',2,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 09:46:42'),(40,2,NULL,4,5,'VIEW','PatientChart',2,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 09:46:45'),(41,2,NULL,4,5,'VIEW','PatientChart',2,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 09:46:51'),(42,2,NULL,4,5,'VIEW','PatientChart',2,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 09:46:54'),(43,2,NULL,4,5,'VIEW','PatientChart',2,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 09:46:59'),(44,2,NULL,4,5,'VIEW','PatientChart',2,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 09:47:00'),(45,8,NULL,1,1,'VIEW','PatientChart',8,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 10:14:36'),(46,8,NULL,3,3,'VIEW','PatientChart',8,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 11:09:11'),(47,8,NULL,3,3,'VIEW','PatientChart',8,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 11:09:49'),(48,8,NULL,3,3,'VIEW','PatientChart',8,'Longitudinal patient chart access.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 11:09:52');
/*!40000 ALTER TABLE `record_access_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `record_amendments`
--

DROP TABLE IF EXISTS `record_amendments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `record_amendments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `visit_id` int(11) DEFAULT NULL,
  `record_type` varchar(100) NOT NULL,
  `record_id` bigint(20) DEFAULT NULL,
  `proposed_changes` longtext NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Requested','Approved','Rejected','Applied') NOT NULL DEFAULT 'Requested',
  `requested_by` int(11) NOT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `applied_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_record_amendments_patient_status` (`patient_id`,`status`,`requested_at`),
  KEY `idx_record_amendments_visit` (`visit_id`),
  KEY `idx_record_amendments_record` (`record_type`,`record_id`),
  KEY `idx_record_amendments_requested_by` (`requested_by`,`requested_at`),
  KEY `fk_record_amendments_reviewed_by` (`reviewed_by`),
  CONSTRAINT `fk_record_amendments_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_record_amendments_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_record_amendments_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_record_amendments_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `record_amendments`
--

LOCK TABLES `record_amendments` WRITE;
/*!40000 ALTER TABLE `record_amendments` DISABLE KEYS */;
/*!40000 ALTER TABLE `record_amendments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_permissions` (`role_id`,`permission_id`),
  KEY `idx_role_permissions_role` (`role_id`),
  KEY `idx_role_permissions_permission` (`permission_id`),
  KEY `fk_role_permissions_assigned_by` (`assigned_by`),
  CONSTRAINT `fk_role_permissions_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=583 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,11,6,NULL,'2026-08-05 04:16:38'),(2,11,7,NULL,'2026-08-05 04:16:38'),(3,11,4,NULL,'2026-08-05 04:16:38'),(4,11,3,NULL,'2026-08-05 04:16:38'),(5,11,1,NULL,'2026-08-05 04:16:38'),(6,4,6,NULL,'2026-08-05 04:16:38'),(7,4,7,NULL,'2026-08-05 04:16:38'),(8,4,4,NULL,'2026-08-05 04:16:38'),(9,4,3,NULL,'2026-08-05 04:16:38'),(10,4,1,NULL,'2026-08-05 04:16:38'),(11,6,6,NULL,'2026-08-05 04:16:38'),(12,6,7,NULL,'2026-08-05 04:16:38'),(13,6,4,NULL,'2026-08-05 04:16:38'),(14,6,3,NULL,'2026-08-05 04:16:38'),(15,6,1,NULL,'2026-08-05 04:16:38'),(21,7,6,NULL,'2026-08-05 04:16:38'),(22,7,7,NULL,'2026-08-05 04:16:38'),(23,7,4,NULL,'2026-08-05 04:16:38'),(24,7,3,NULL,'2026-08-05 04:16:38'),(25,7,1,NULL,'2026-08-05 04:16:38'),(26,8,6,NULL,'2026-08-05 04:16:38'),(27,8,7,NULL,'2026-08-05 04:16:38'),(28,8,4,NULL,'2026-08-05 04:16:38'),(29,8,3,NULL,'2026-08-05 04:16:38'),(30,8,1,NULL,'2026-08-05 04:16:38'),(31,9,6,NULL,'2026-08-05 04:16:38'),(32,9,7,NULL,'2026-08-05 04:16:38'),(33,9,4,NULL,'2026-08-05 04:16:38'),(34,9,3,NULL,'2026-08-05 04:16:38'),(35,9,1,NULL,'2026-08-05 04:16:38'),(36,2,6,NULL,'2026-08-05 04:16:38'),(37,2,7,NULL,'2026-08-05 04:16:38'),(38,2,4,NULL,'2026-08-05 04:16:38'),(39,2,3,NULL,'2026-08-05 04:16:38'),(40,2,1,NULL,'2026-08-05 04:16:38'),(41,3,6,NULL,'2026-08-05 04:16:38'),(42,3,7,NULL,'2026-08-05 04:16:38'),(43,3,4,NULL,'2026-08-05 04:16:38'),(44,3,3,NULL,'2026-08-05 04:16:38'),(45,3,1,NULL,'2026-08-05 04:16:38'),(46,12,6,NULL,'2026-08-05 04:16:38'),(47,12,7,NULL,'2026-08-05 04:16:38'),(48,12,4,NULL,'2026-08-05 04:16:38'),(49,12,3,NULL,'2026-08-05 04:16:38'),(50,12,1,NULL,'2026-08-05 04:16:38'),(51,10,6,NULL,'2026-08-05 04:16:38'),(52,10,7,NULL,'2026-08-05 04:16:38'),(53,10,4,NULL,'2026-08-05 04:16:38'),(54,10,3,NULL,'2026-08-05 04:16:38'),(55,10,1,NULL,'2026-08-05 04:16:38'),(64,2,2,NULL,'2026-08-05 04:16:38'),(65,4,5,NULL,'2026-08-05 04:16:38'),(66,3,13,NULL,'2026-08-05 04:16:44'),(67,3,16,NULL,'2026-08-05 04:16:44'),(68,3,14,NULL,'2026-08-05 04:16:44'),(69,3,15,NULL,'2026-08-05 04:16:44'),(70,3,12,NULL,'2026-08-05 04:16:44'),(73,4,12,NULL,'2026-08-05 04:16:44'),(75,2,12,NULL,'2026-08-05 04:16:44'),(76,2,13,NULL,'2026-08-05 04:16:44'),(77,2,15,NULL,'2026-08-05 04:16:44'),(79,3,18,NULL,'2026-08-05 04:48:44'),(80,3,17,NULL,'2026-08-05 04:48:44'),(81,3,19,NULL,'2026-08-05 04:48:44'),(86,4,17,NULL,'2026-08-05 04:48:44'),(88,2,17,NULL,'2026-08-05 04:48:44'),(93,2,18,NULL,'2026-08-05 04:48:44'),(98,4,28,NULL,'2026-08-05 07:26:17'),(99,6,28,NULL,'2026-08-05 07:26:17'),(101,7,28,NULL,'2026-08-05 07:26:17'),(102,8,28,NULL,'2026-08-05 07:26:17'),(103,9,28,NULL,'2026-08-05 07:26:17'),(104,2,28,NULL,'2026-08-05 07:26:17'),(105,3,28,NULL,'2026-08-05 07:26:17'),(106,10,28,NULL,'2026-08-05 07:26:17'),(113,4,33,NULL,'2026-08-05 07:26:17'),(115,4,29,NULL,'2026-08-05 07:26:17'),(117,4,30,NULL,'2026-08-05 07:26:17'),(119,4,31,NULL,'2026-08-05 07:26:17'),(121,4,35,NULL,'2026-08-05 07:26:17'),(128,4,32,NULL,'2026-08-05 07:26:17'),(129,4,34,NULL,'2026-08-05 07:26:17'),(131,3,35,NULL,'2026-08-05 07:26:17'),(132,4,40,NULL,'2026-08-05 13:24:56'),(133,4,36,NULL,'2026-08-05 13:24:56'),(134,6,40,NULL,'2026-08-05 13:24:56'),(135,6,36,NULL,'2026-08-05 13:24:56'),(138,7,40,NULL,'2026-08-05 13:24:56'),(139,7,36,NULL,'2026-08-05 13:24:56'),(140,8,40,NULL,'2026-08-05 13:24:56'),(141,8,36,NULL,'2026-08-05 13:24:56'),(142,9,40,NULL,'2026-08-05 13:24:56'),(143,9,36,NULL,'2026-08-05 13:24:56'),(144,3,40,NULL,'2026-08-05 13:24:56'),(145,3,36,NULL,'2026-08-05 13:24:56'),(146,10,40,NULL,'2026-08-05 13:24:56'),(147,10,36,NULL,'2026-08-05 13:24:56'),(163,4,41,NULL,'2026-08-05 13:24:56'),(164,4,37,NULL,'2026-08-05 13:24:56'),(165,4,39,NULL,'2026-08-05 13:24:56'),(166,4,42,NULL,'2026-08-05 13:24:56'),(167,4,38,NULL,'2026-08-05 13:24:56'),(168,4,43,NULL,'2026-08-05 13:24:56'),(169,4,44,NULL,'2026-08-05 13:24:56'),(173,3,44,NULL,'2026-08-05 13:24:56'),(174,11,49,NULL,'2026-08-05 14:59:38'),(175,11,46,NULL,'2026-08-05 14:59:38'),(176,11,45,NULL,'2026-08-05 14:59:38'),(177,4,49,NULL,'2026-08-05 14:59:38'),(178,4,46,NULL,'2026-08-05 14:59:38'),(179,4,45,NULL,'2026-08-05 14:59:38'),(180,6,49,NULL,'2026-08-05 14:59:38'),(181,6,46,NULL,'2026-08-05 14:59:38'),(182,6,45,NULL,'2026-08-05 14:59:38'),(186,7,49,NULL,'2026-08-05 14:59:38'),(187,7,46,NULL,'2026-08-05 14:59:38'),(188,7,45,NULL,'2026-08-05 14:59:38'),(189,8,49,NULL,'2026-08-05 14:59:38'),(190,8,46,NULL,'2026-08-05 14:59:38'),(191,8,45,NULL,'2026-08-05 14:59:38'),(192,9,49,NULL,'2026-08-05 14:59:38'),(193,9,46,NULL,'2026-08-05 14:59:38'),(194,9,45,NULL,'2026-08-05 14:59:38'),(195,2,49,NULL,'2026-08-05 14:59:38'),(196,2,46,NULL,'2026-08-05 14:59:38'),(197,2,45,NULL,'2026-08-05 14:59:38'),(198,3,49,NULL,'2026-08-05 14:59:38'),(199,3,46,NULL,'2026-08-05 14:59:38'),(200,3,45,NULL,'2026-08-05 14:59:38'),(201,10,49,NULL,'2026-08-05 14:59:38'),(202,10,46,NULL,'2026-08-05 14:59:38'),(203,10,45,NULL,'2026-08-05 14:59:38'),(205,4,47,NULL,'2026-08-05 14:59:38'),(206,4,51,NULL,'2026-08-05 14:59:38'),(207,3,47,NULL,'2026-08-05 14:59:38'),(208,3,51,NULL,'2026-08-05 14:59:38'),(212,3,48,NULL,'2026-08-05 14:59:38'),(213,3,50,NULL,'2026-08-05 14:59:38'),(215,4,50,NULL,'2026-08-05 14:59:38'),(216,4,54,NULL,'2026-08-05 16:45:40'),(217,4,53,NULL,'2026-08-05 16:45:40'),(218,4,55,NULL,'2026-08-05 16:45:40'),(219,4,52,NULL,'2026-08-05 16:45:40'),(220,4,62,NULL,'2026-08-05 16:45:40'),(226,3,54,NULL,'2026-08-05 16:45:40'),(227,3,53,NULL,'2026-08-05 16:45:40'),(228,3,55,NULL,'2026-08-05 16:45:40'),(229,3,52,NULL,'2026-08-05 16:45:40'),(230,3,62,NULL,'2026-08-05 16:45:40'),(231,4,58,NULL,'2026-08-05 16:45:40'),(232,4,60,NULL,'2026-08-05 16:45:40'),(233,4,57,NULL,'2026-08-05 16:45:40'),(234,4,61,NULL,'2026-08-05 16:45:40'),(238,3,58,NULL,'2026-08-05 16:45:40'),(239,3,59,NULL,'2026-08-05 16:45:40'),(240,3,56,NULL,'2026-08-05 16:45:40'),(241,3,60,NULL,'2026-08-05 16:45:40'),(242,3,61,NULL,'2026-08-05 16:45:40'),(245,4,66,NULL,'2026-08-08 10:16:44'),(246,4,64,NULL,'2026-08-08 10:16:44'),(247,4,65,NULL,'2026-08-08 10:16:44'),(248,4,63,NULL,'2026-08-08 10:16:44'),(252,4,68,NULL,'2026-08-08 19:58:35'),(253,4,69,NULL,'2026-08-08 19:58:35'),(254,4,67,NULL,'2026-08-08 19:58:35'),(258,3,67,NULL,'2026-08-08 19:58:35'),(266,4,70,NULL,'2026-08-09 03:24:31'),(267,6,79,NULL,'2026-08-09 06:46:51'),(268,6,75,NULL,'2026-08-09 06:46:51'),(269,6,78,NULL,'2026-08-09 06:46:51'),(270,6,77,NULL,'2026-08-09 06:46:51'),(271,6,76,NULL,'2026-08-09 06:46:51'),(272,6,74,NULL,'2026-08-09 06:46:51'),(274,4,75,NULL,'2026-08-09 06:46:51'),(275,4,74,NULL,'2026-08-09 06:46:51'),(278,3,74,NULL,'2026-08-09 06:46:51'),(279,9,85,NULL,'2026-08-09 22:34:05'),(280,9,81,NULL,'2026-08-09 22:34:05'),(281,9,84,NULL,'2026-08-09 22:34:05'),(282,9,83,NULL,'2026-08-09 22:34:05'),(283,9,82,NULL,'2026-08-09 22:34:05'),(284,9,80,NULL,'2026-08-09 22:34:05'),(286,4,81,NULL,'2026-08-09 22:34:05'),(287,4,80,NULL,'2026-08-09 22:34:05'),(290,3,80,NULL,'2026-08-09 22:34:05'),(291,8,90,NULL,'2026-08-10 20:47:55'),(292,8,87,NULL,'2026-08-10 20:47:55'),(293,8,88,NULL,'2026-08-10 20:47:55'),(294,8,89,NULL,'2026-08-10 20:47:55'),(295,8,86,NULL,'2026-08-10 20:47:55'),(298,4,87,NULL,'2026-08-10 20:47:55'),(299,4,86,NULL,'2026-08-10 20:47:55'),(302,4,94,NULL,'2026-08-10 22:09:01'),(303,4,92,NULL,'2026-08-10 22:09:01'),(304,4,93,NULL,'2026-08-10 22:09:01'),(305,4,91,NULL,'2026-08-10 22:09:01'),(306,10,94,NULL,'2026-08-10 22:09:01'),(307,10,92,NULL,'2026-08-10 22:09:01'),(308,10,93,NULL,'2026-08-10 22:09:01'),(309,10,91,NULL,'2026-08-10 22:09:01'),(318,11,96,NULL,'2026-08-10 23:29:27'),(319,11,97,NULL,'2026-08-10 23:29:27'),(320,11,98,NULL,'2026-08-10 23:29:27'),(321,11,95,NULL,'2026-08-10 23:29:27'),(325,4,95,NULL,'2026-08-10 23:29:27'),(326,6,95,NULL,'2026-08-10 23:29:27'),(328,7,95,NULL,'2026-08-10 23:29:27'),(329,8,95,NULL,'2026-08-10 23:29:27'),(330,9,95,NULL,'2026-08-10 23:29:27'),(331,2,95,NULL,'2026-08-10 23:29:27'),(332,3,95,NULL,'2026-08-10 23:29:27'),(333,12,95,NULL,'2026-08-10 23:29:27'),(334,10,95,NULL,'2026-08-10 23:29:27'),(340,12,104,NULL,'2026-08-11 00:01:09'),(341,12,102,NULL,'2026-08-11 00:01:09'),(342,12,100,NULL,'2026-08-11 00:01:09'),(343,12,101,NULL,'2026-08-11 00:01:09'),(344,12,103,NULL,'2026-08-11 00:01:09'),(345,12,99,NULL,'2026-08-11 00:01:09'),(346,12,105,NULL,'2026-08-11 00:01:09'),(347,11,99,NULL,'2026-08-11 00:01:09'),(348,4,99,NULL,'2026-08-11 00:01:09'),(349,6,99,NULL,'2026-08-11 00:01:09'),(351,7,99,NULL,'2026-08-11 00:01:09'),(352,8,99,NULL,'2026-08-11 00:01:09'),(353,9,99,NULL,'2026-08-11 00:01:09'),(354,2,99,NULL,'2026-08-11 00:01:09'),(355,3,99,NULL,'2026-08-11 00:01:09'),(356,10,99,NULL,'2026-08-11 00:01:09'),(362,11,105,NULL,'2026-08-11 00:01:09'),(363,7,107,NULL,'2026-08-11 21:27:40'),(364,7,109,NULL,'2026-08-11 21:27:40'),(365,7,108,NULL,'2026-08-11 21:27:40'),(366,7,106,NULL,'2026-08-11 21:27:40'),(370,4,107,NULL,'2026-08-11 21:27:40'),(371,4,108,NULL,'2026-08-11 21:27:40'),(372,4,106,NULL,'2026-08-11 21:27:40'),(374,3,106,NULL,'2026-08-11 21:27:40'),(375,11,110,NULL,'2026-08-12 08:25:36'),(376,4,110,NULL,'2026-08-12 08:25:36'),(377,6,110,NULL,'2026-08-12 08:25:36'),(379,7,110,NULL,'2026-08-12 08:25:36'),(380,8,110,NULL,'2026-08-12 08:25:36'),(381,9,110,NULL,'2026-08-12 08:25:36'),(382,3,110,NULL,'2026-08-12 08:25:36'),(383,12,110,NULL,'2026-08-12 08:25:36'),(384,1,110,NULL,'2026-08-12 08:25:36'),(385,10,110,NULL,'2026-08-12 08:25:36'),(390,11,111,NULL,'2026-08-12 08:25:36'),(391,1,111,NULL,'2026-08-12 08:25:36'),(393,12,112,NULL,'2026-08-12 08:25:36'),(394,1,112,NULL,'2026-08-12 08:25:36'),(396,4,113,NULL,'2026-08-12 08:25:36'),(397,6,113,NULL,'2026-08-12 08:25:36'),(399,7,113,NULL,'2026-08-12 08:25:36'),(400,8,113,NULL,'2026-08-12 08:25:36'),(401,9,113,NULL,'2026-08-12 08:25:36'),(402,3,113,NULL,'2026-08-12 08:25:36'),(403,1,113,NULL,'2026-08-12 08:25:36'),(404,10,113,NULL,'2026-08-12 08:25:36'),(410,3,115,NULL,'2026-08-19 11:04:29'),(411,3,117,NULL,'2026-08-19 11:04:29'),(412,3,118,NULL,'2026-08-19 11:04:29'),(413,3,116,NULL,'2026-08-19 11:04:29'),(414,3,114,NULL,'2026-08-19 11:04:29'),(420,4,115,NULL,'2026-08-19 11:04:29'),(421,4,117,NULL,'2026-08-19 11:04:29'),(422,4,114,NULL,'2026-08-19 11:04:29'),(423,2,115,NULL,'2026-08-19 11:04:29'),(424,2,114,NULL,'2026-08-19 11:04:29'),(472,5,95,1,'2026-08-19 13:56:17'),(473,5,115,1,'2026-08-19 13:56:17'),(474,5,117,1,'2026-08-19 13:56:17'),(475,5,118,1,'2026-08-19 13:56:17'),(476,5,116,1,'2026-08-19 13:56:17'),(477,5,114,1,'2026-08-19 13:56:17'),(478,5,74,1,'2026-08-19 13:56:17'),(479,5,54,1,'2026-08-19 13:56:17'),(480,5,53,1,'2026-08-19 13:56:17'),(481,5,49,1,'2026-08-19 13:56:17'),(482,5,55,1,'2026-08-19 13:56:17'),(483,5,33,1,'2026-08-19 13:56:17'),(484,5,41,1,'2026-08-19 13:56:17'),(485,5,29,1,'2026-08-19 13:56:17'),(486,5,30,1,'2026-08-19 13:56:17'),(487,5,46,1,'2026-08-19 13:56:17'),(488,5,31,1,'2026-08-19 13:56:17'),(489,5,62,1,'2026-08-19 13:56:17'),(490,5,52,1,'2026-08-19 13:56:17'),(491,5,28,1,'2026-08-19 13:56:17'),(492,5,35,1,'2026-08-19 13:56:17'),(493,5,45,1,'2026-08-19 13:56:17'),(494,5,40,1,'2026-08-19 13:56:17'),(495,5,17,1,'2026-08-19 13:56:17'),(496,5,12,1,'2026-08-19 13:56:17'),(497,5,44,1,'2026-08-19 13:56:17'),(498,5,36,1,'2026-08-19 13:56:17'),(499,5,73,1,'2026-08-19 13:56:17'),(500,5,71,1,'2026-08-19 13:56:17'),(501,5,72,1,'2026-08-19 13:56:17'),(502,5,70,1,'2026-08-19 13:56:17'),(503,5,106,1,'2026-08-19 13:56:17'),(504,5,86,1,'2026-08-19 13:56:17'),(505,5,80,1,'2026-08-19 13:56:17'),(506,5,113,1,'2026-08-19 13:56:17'),(507,5,110,1,'2026-08-19 13:56:17'),(508,5,99,1,'2026-08-19 13:56:17'),(509,5,91,1,'2026-08-19 13:56:17'),(510,5,6,1,'2026-08-19 13:56:17'),(511,5,2,1,'2026-08-19 13:56:17'),(512,5,7,1,'2026-08-19 13:56:17'),(513,5,4,1,'2026-08-19 13:56:17'),(514,5,3,1,'2026-08-19 13:56:17'),(515,5,1,1,'2026-08-19 13:56:17'),(516,5,68,1,'2026-08-19 13:56:17'),(517,5,69,1,'2026-08-19 13:56:17'),(518,5,67,1,'2026-08-19 13:56:17'),(519,6,63,NULL,'2026-08-23 07:30:57'),(520,5,63,NULL,'2026-08-23 07:30:57'),(521,7,63,NULL,'2026-08-23 07:30:57'),(522,8,63,NULL,'2026-08-23 07:30:57'),(523,9,63,NULL,'2026-08-23 07:30:57'),(524,3,63,NULL,'2026-08-23 07:30:57'),(525,10,63,NULL,'2026-08-23 07:30:57'),(526,7,74,NULL,'2026-08-23 07:30:57'),(527,8,74,NULL,'2026-08-23 07:30:57'),(528,9,74,NULL,'2026-08-23 07:30:57'),(529,10,74,NULL,'2026-08-23 07:30:57'),(530,6,17,NULL,'2026-08-23 07:30:57'),(531,7,17,NULL,'2026-08-23 07:30:57'),(532,8,17,NULL,'2026-08-23 07:30:57'),(533,9,17,NULL,'2026-08-23 07:30:57'),(534,10,17,NULL,'2026-08-23 07:30:57'),(535,6,70,NULL,'2026-08-23 07:30:57'),(536,7,70,NULL,'2026-08-23 07:30:57'),(537,8,70,NULL,'2026-08-23 07:30:57'),(538,9,70,NULL,'2026-08-23 07:30:57'),(539,3,70,NULL,'2026-08-23 07:30:57'),(540,10,70,NULL,'2026-08-23 07:30:57'),(541,6,106,NULL,'2026-08-23 07:30:57'),(542,8,106,NULL,'2026-08-23 07:30:57'),(543,9,106,NULL,'2026-08-23 07:30:57'),(544,10,106,NULL,'2026-08-23 07:30:57'),(545,6,86,NULL,'2026-08-23 07:30:57'),(546,7,86,NULL,'2026-08-23 07:30:57'),(547,9,86,NULL,'2026-08-23 07:30:57'),(548,3,86,NULL,'2026-08-23 07:30:57'),(549,10,86,NULL,'2026-08-23 07:30:57'),(550,6,80,NULL,'2026-08-23 07:30:57'),(551,7,80,NULL,'2026-08-23 07:30:57'),(552,8,80,NULL,'2026-08-23 07:30:57'),(553,10,80,NULL,'2026-08-23 07:30:57'),(554,6,91,NULL,'2026-08-23 07:30:57'),(555,7,91,NULL,'2026-08-23 07:30:57'),(556,8,91,NULL,'2026-08-23 07:30:57'),(557,9,91,NULL,'2026-08-23 07:30:57'),(558,3,91,NULL,'2026-08-23 07:30:57'),(559,6,67,NULL,'2026-08-23 07:30:57'),(560,7,67,NULL,'2026-08-23 07:30:57'),(561,8,67,NULL,'2026-08-23 07:30:57'),(562,9,67,NULL,'2026-08-23 07:30:57'),(563,10,67,NULL,'2026-08-23 07:30:57');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_name` (`role_name`),
  KEY `idx_roles_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'System Administrator','Full system access',1,'2026-08-05 04:14:44',NULL),(2,'Receptionist','Patient registration',1,'2026-08-05 04:14:44',NULL),(3,'Records Officer','Medical records',1,'2026-08-05 04:14:44',NULL),(4,'Doctor','Medical consultation',1,'2026-08-05 04:14:44',NULL),(5,'Nurse','Nursing care',1,'2026-08-05 04:14:44',NULL),(6,'Laboratory Scientist','Laboratory investigations',1,'2026-08-05 04:14:44',NULL),(7,'Pharmacist','Medication dispensing',1,'2026-08-05 04:14:44',NULL),(8,'Physiotherapist','Physiotherapy',1,'2026-08-05 04:14:44',NULL),(9,'Radiographer','Radiology',1,'2026-08-05 04:14:44',NULL),(10,'Theatre Staff','Surgical procedures',1,'2026-08-05 04:14:44',NULL),(11,'Accountant','Billing and payments',1,'2026-08-05 04:14:44','2026-08-09 04:29:54'),(12,'Store Officer','Medical store',1,'2026-08-05 04:14:44',NULL);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schema_migrations` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `migration_name` varchar(255) NOT NULL,
  `checksum` char(64) NOT NULL,
  `batch` int(11) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  `execution_time_ms` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_schema_migrations_name` (`migration_name`),
  KEY `idx_schema_migrations_batch` (`batch`,`applied_at`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES (1,'002_phase0_live_schema_alignment_up.sql','afb299ef87273849f708fd779392b6e2fc4bde05163392d75e556b1b9c10c940',1,'2026-08-05 05:48:44',0),(2,'003_phase0_queue_workflow_up.sql','b3e0bcd385055481bf2358800368b75e511c4d672fc004915436bfb3fdc785a8',1,'2026-08-05 05:48:44',0),(3,'004_phase0_store_status_up.sql','0a89f9334cbed1ace3e22054232b8cab6262f23890aa7ffd7c08716622d5173e',1,'2026-08-05 05:48:44',0),(4,'005_phase1_user_management_up.sql','c423cb5735c133e7929257b48f4496efd34ecbd33ce3240aa48e3a4108b7c4f2',1,'2026-08-05 05:48:44',137),(5,'006_phase1_roles_permissions_up.sql','17eea7657af655180ec50192bbbe8e47960bd15b694470dcaa2f21bfade4d49d',1,'2026-08-05 05:48:44',0),(6,'007_phase1_departments_assignments_up.sql','8e5404e85e7f7087758495876cb532783d34be43dc2b65a1090abb7b786842d9',1,'2026-08-05 05:48:44',250),(7,'008_phase1_security_administration_up.sql','c65b67d515dd07f682d02f17be46e4bf6c58f493396e356d0ac70307779605b9',1,'2026-08-05 05:48:44',0),(8,'009_phase1_system_settings_up.sql','fb478905413737bad5cd9407f8e06d61d8f451fa97ea34c56a6dbe1aac24f859',1,'2026-08-05 05:48:44',0),(9,'010_phase1_production_indexes_up.sql','1dfbf3d4c3873bc39f597fdccfbb306a121ff4b8409dbab1ecdfbfca7c3b17a0',1,'2026-08-05 05:48:44',43),(10,'011_phase1_visit_status_repair_up.sql','7ff2ceeda4762d4b545ad74b3d1fff3338f49547f886398d532499ad3215d222',1,'2026-08-05 05:48:44',16),(11,'012_phase1_patient_gender_remediation_up.sql','cd99efb45b6a16a0ce1e4eda86e6547516bde69de388263f8b39f0a476b955d3',1,'2026-08-05 05:48:44',29),(12,'013_phase2_medical_records_foundation_up.sql','e0fb83803488af9c4fe642208758406ea795e4221cfa052b7f64ed394a28a680',1,'2026-08-05 05:48:44',0),(13,'014_phase2_mpi_identifiers_up.sql','d92e560b01fc3b6b574bda0610a98acb9ae1e4fdae96fb78733f56ba5db635c0',1,'2026-08-05 05:48:44',0),(14,'015_recovery_safety_and_seed_reconciliation_up.sql','ddcfeb365553071b7fd3359cb3ebfde82257183cdeacf52ceba529833c675cd0',2,'2026-08-05 05:48:44',23),(15,'016_phase2_patient_identifiers_mpi_up.sql','ec89afedb6e88421c3af35061aeae459983908a51ce086b72b1b98458f2971cb',1,'2026-08-05 06:43:18',23),(16,'017_phase2_clinical_safety_up.sql','e43377009f238491350268ad5f8449e61a4f6357ae0be2ca42d8be87120cd1c6',1,'2026-08-05 08:26:17',286),(17,'018_phase2_clinical_safety_hardening_up.sql','aa862a60e76b335d9413a5100f89bcbdcbd15480085e508788a6652d593f942a',3,'2026-08-05 12:17:18',30),(18,'019_phase2_problem_list_medical_history_up.sql','f33bed78eb4a0b9b395f5ecb185963375415af5690b5a3abd19e045c535fd3a6',4,'2026-08-05 14:24:57',1044),(19,'020_phase2_medical_documents_up.sql','3df159a81963b59f7474d1c7da4fa6536f5ecd165ae8f6c83a4954a93014a6b7',1,'2026-08-05 15:59:38',161),(20,'021_phase2_clinical_notes_up.sql','bc0be2d863185515be6cc73f52764634ca8b3d4b74b9d1ee4c5d78c8ba894d2e',1,'2026-08-05 17:45:40',172),(21,'022_phase3_consultation_notifications_up.sql','2d2cdb3b1882486482ce333db05e8c19aa41fcf45153ed7296013a7c9bf2e080',1,'2026-08-08 11:16:44',135),(22,'023_phase3_vital_signs_up.sql','97cbaa03c8446cde482ed86486eba8dcf7f183d2d54182b2a73b4030bc95a8d2',23,'2026-08-08 20:58:35',75),(23,'024_phase3_nursing_up.sql','14de63f5581f83130995b3e0a189b215f2493e873ca492962e6019876fd89cae',24,'2026-08-09 04:24:31',130),(24,'025_phase3_laboratory_up.sql','fc259ebc38f37a70003bc306548393599c384bb637a16ef207d3dc74b1343b6d',1,'2026-08-09 07:46:51',174),(25,'026_phase3_laboratory_result_details_up.sql','a1242e05c84a91ccb34329960b72f4ba4f7b6c856d986a9cd8256749ecc2422c',26,'2026-08-09 21:03:20',117),(26,'027_phase3_radiology_up.sql','42a3344accfa1457c4a6ac3b9b30b2122d7c91f8e1ec5bb89ff427db0e8bc48a',1,'2026-08-09 23:34:05',123),(27,'028_phase3_physiotherapy_up.sql','a2efdf62f6531411821ad74284152269b9eea8e238cf08e177996a5f2fcb6656',1,'2026-08-10 21:47:55',226),(28,'029_phase3_theatre_up.sql','54d7bee6f2d67ac1281517493074e88a4cc7108dfa9e0320979452fef29fe4e3',1,'2026-08-10 23:09:01',95),(29,'030_phase4_accounts_price_catalogue_up.sql','957e68f02edeac485787e296125c550cb4045583aff24727d1ec9c2d17c86402',1,'2026-08-11 00:29:27',75),(30,'031_phase4_store_inventory_up.sql','8cf15e3bb23518ee7895eec16115a4849d25c68d1c28473f478dc7d780754511',1,'2026-08-11 01:01:09',227),(31,'032_phase4_pharmacy_up.sql','89fd5f292b0281564ad40f0cd8c1aaee65b5801469aa9135eaf0eab5ade14f88',1,'2026-08-11 22:27:40',164),(32,'033_phase4_billing_up.sql','a5eab6bb6c2bfc28dbd9ab40d6c63ebf783f621db99f0788e6d778970aedaf1b',1,'2026-08-11 23:44:00',1343),(33,'034_phase4_basic_dashboards_reports_up.sql','3e16ab905463b7458b3a423915b33b3933d51a3e161167f9825e3798f855f83d',34,'2026-08-12 09:25:36',90),(34,'035_patient_registration_demographics_up.sql','b17934e12da21ca862f63be9190dd0fa677ed08e0ea0ae8bafab9933a93bd07c',1,'2026-08-12 14:40:34',104),(35,'036_encounter_completion_discharge_up.sql','cf80f30c49b2d832a2bc458885591d26fff9e18f9f184861e3fc700be95cff05',1,'2026-08-12 17:59:47',467),(36,'037_inpatient_admissions_up.sql','af688392294193ab1e1ad84af356365377626c52975854e3ee82b2ef4ce010e6',1,'2026-08-19 12:04:29',301),(37,'038_clinical_cross_view_permissions_up.sql','e1515accf50951d800ec393d28e51d739d6a45acedc959c506c426633a82c0fb',1,'2026-08-23 08:30:57',17),(38,'039_security_lockout_threshold_10_up.sql','d85ea0203f17ec8a7f05ee9fe913860a4dea42bb8e8644c9d9343fb2f35a7f34',1,'2026-08-23 10:06:14',4),(39,'040_receptionist_admission_permission_repair_up.sql','1b15ecd80418243966a00d7d2d1aad86aa654c9b8e78fb793d18a5359a22a4b7',1,'2026-08-23 10:27:55',4),(40,'041_user_notifications_up.sql','9921dd8808c2511fef2efd00498584fc70072450017bb5769c3524eae4a0b188',1,'2026-08-23 11:44:01',71);
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transactions`
--

DROP TABLE IF EXISTS `stock_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `inventory_item_id` int(11) NOT NULL,
  `transaction_type` enum('Receipt','Issue','Return','Adjustment') NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `from_department_id` int(11) DEFAULT NULL,
  `to_department_id` int(11) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `performed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stock_transactions_item` (`inventory_item_id`),
  KEY `idx_stock_transactions_type` (`transaction_type`),
  KEY `idx_stock_transactions_from_department` (`from_department_id`),
  KEY `idx_stock_transactions_to_department` (`to_department_id`),
  KEY `idx_stock_transactions_created_at` (`created_at`),
  KEY `idx_stock_transactions_performed_by` (`performed_by`),
  CONSTRAINT `fk_stock_transactions_from_department` FOREIGN KEY (`from_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_transactions_item` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_transactions_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_transactions_to_department` FOREIGN KEY (`to_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transactions`
--

LOCK TABLES `stock_transactions` WRITE;
/*!40000 ALTER TABLE `stock_transactions` DISABLE KEYS */;
INSERT INTO `stock_transactions` VALUES (1,1,'Receipt',1000.00,NULL,12,NULL,NULL,1,'2026-08-22 06:36:50'),(2,1,'Issue',150.00,12,7,NULL,NULL,1,'2026-08-22 06:37:09'),(3,1,'Issue',10.00,7,NULL,'PRESCRIPTION #1','Dispensed prescription #1.',1,'2026-08-22 06:40:00');
/*!40000 ALTER TABLE `stock_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_setting_history`
--

DROP TABLE IF EXISTS `system_setting_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_setting_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `setting_id` bigint(20) DEFAULT NULL,
  `setting_key` varchar(191) NOT NULL,
  `setting_group` varchar(100) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` longtext DEFAULT NULL,
  `new_value` longtext DEFAULT NULL,
  `is_sensitive` tinyint(1) NOT NULL DEFAULT 0,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_setting_history_setting_created` (`setting_id`,`created_at`),
  KEY `idx_setting_history_key_created` (`setting_key`,`created_at`),
  KEY `idx_setting_history_group_created` (`setting_group`,`created_at`),
  KEY `idx_setting_history_actor_created` (`changed_by`,`created_at`),
  CONSTRAINT `fk_setting_history_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_setting_history_setting` FOREIGN KEY (`setting_id`) REFERENCES `system_settings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_setting_history`
--

LOCK TABLES `system_setting_history` WRITE;
/*!40000 ALTER TABLE `system_setting_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_setting_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(191) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_type` varchar(30) NOT NULL DEFAULT 'string',
  `setting_group` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `default_value` longtext DEFAULT NULL,
  `validation_rules` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_editable` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_sensitive` tinyint(1) NOT NULL DEFAULT 0,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_system_settings_key` (`setting_key`),
  KEY `idx_system_settings_group_order` (`setting_group`,`sort_order`,`setting_key`),
  KEY `idx_system_settings_public` (`is_public`,`setting_group`),
  KEY `idx_system_settings_system` (`is_system`,`is_editable`),
  KEY `fk_system_settings_created_by` (`created_by`),
  KEY `fk_system_settings_updated_by` (`updated_by`),
  CONSTRAINT `fk_system_settings_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_system_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'hospital.name','Hospital Management System','string','Hospital','Official hospital name.','Hospital Management System','{\"required\":true,\"min_length\":2,\"max_length\":150}',1,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(2,'hospital.code','HMS','string','Hospital','Short hospital code.','HMS','{\"required\":true,\"regex\":\"^[A-Za-z0-9_-]{2,20}$\"}',1,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(3,'hospital.logo','','string','Hospital','Relative or absolute hospital logo path.','','{\"max_length\":255}',1,1,1,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(4,'hospital.address','','string','Hospital','Hospital postal address.','','{\"max_length\":500}',1,1,0,0,0,40,NULL,NULL,'2026-08-05 04:16:38',NULL),(5,'hospital.contact_phone','','string','Hospital','Main hospital contact number.','','{\"max_length\":50}',1,1,0,0,0,50,NULL,NULL,'2026-08-05 04:16:38',NULL),(6,'hospital.website','','string','Hospital','Official hospital website.','','{\"max_length\":255}',1,1,0,0,0,60,NULL,NULL,'2026-08-05 04:16:38',NULL),(7,'hospital.email','','string','Hospital','Official hospital email address.','','{\"max_length\":150,\"format\":\"email\"}',1,1,0,0,0,70,NULL,NULL,'2026-08-05 04:16:38',NULL),(8,'general.timezone','Africa/Lagos','string','General','Application timezone.','Africa/Lagos','{\"required\":true,\"format\":\"timezone\"}',1,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(9,'general.date_format','d M Y','string','General','PHP date display format.','d M Y','{\"required\":true,\"max_length\":30}',1,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(10,'general.time_format','H:i','string','General','PHP time display format.','H:i','{\"required\":true,\"max_length\":30}',1,1,1,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(11,'general.currency','NGN','string','General','Default ISO currency code.','NGN','{\"required\":true,\"regex\":\"^[A-Z]{3}$\"}',1,1,1,0,0,40,NULL,NULL,'2026-08-05 04:16:38',NULL),(12,'general.language','en','string','General','Default application language.','en','{\"required\":true,\"allowed\":[\"en\"]}',1,1,1,0,0,50,NULL,NULL,'2026-08-05 04:16:38',NULL),(13,'security.session_timeout_minutes','30','integer','Security','Idle session timeout in minutes.','30','{\"required\":true,\"min\":5,\"max\":1440}',0,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(14,'security.password_min_length','8','integer','Security','Minimum user password length.','8','{\"required\":true,\"min\":8,\"max\":128}',0,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(15,'security.password_complexity','basic','string','Security','Password complexity policy.','basic','{\"required\":true,\"allowed\":[\"basic\",\"standard\",\"strong\"]}',0,1,1,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(16,'security.lockout_threshold','10','integer','Security','Failed login attempts before account lockout.','10','{\"required\":true,\"min\":1,\"max\":20}',0,1,1,0,0,40,NULL,NULL,'2026-08-05 04:16:38','2026-08-23 09:06:14'),(17,'security.password_expiry_days','0','integer','Security','Password expiry interval; zero disables expiry.','0','{\"required\":true,\"min\":0,\"max\":3650}',0,1,1,0,0,50,NULL,NULL,'2026-08-05 04:16:38',NULL),(18,'security.two_factor_enabled','0','boolean','Security','Reserved two-factor authentication switch.','0','{\"required\":true}',0,0,1,0,0,60,NULL,NULL,'2026-08-05 04:16:38',NULL),(19,'encounters.number_format','ENC-{YEAR}-{ID:6}','string','Encounters','Encounter number formatting template.','ENC-{YEAR}-{ID:6}','{\"required\":true,\"max_length\":100}',0,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(20,'encounters.default_department_id','','integer','Encounters','Optional default encounter department ID.','','{\"min\":1}',0,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(21,'encounters.queue_rules','[]','array','Encounters','Encounter queue rule overrides.','[]','{\"required\":true}',0,1,0,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(22,'queue.auto_queue','1','boolean','Queue','Automatically enqueue eligible encounters.','1','{\"required\":true}',0,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(23,'queue.prefix','Q','string','Queue','Default queue number prefix.','Q','{\"required\":true,\"max_length\":20}',1,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(24,'queue.reset_rule','daily','string','Queue','Queue numbering reset frequency.','daily','{\"required\":true,\"allowed\":[\"never\",\"daily\",\"weekly\",\"monthly\"]}',0,1,0,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(25,'notifications.email_enabled','0','boolean','Notifications','Enable email notifications.','0','{\"required\":true}',0,1,0,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(26,'notifications.sms_enabled','0','boolean','Notifications','Enable SMS notifications.','0','{\"required\":true}',0,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(27,'notifications.internal_enabled','1','boolean','Notifications','Enable internal application notifications.','1','{\"required\":true}',0,1,0,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(28,'reporting.default_date_range_days','30','integer','Reporting','Default reporting date range in days.','30','{\"required\":true,\"min\":1,\"max\":366}',0,1,0,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(29,'reporting.export_limit','10000','integer','Reporting','Maximum rows in one report export.','10000','{\"required\":true,\"min\":100,\"max\":1000000}',0,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(30,'backup.frequency','daily','string','Backup','Requested backup frequency.','daily','{\"required\":true,\"allowed\":[\"manual\",\"daily\",\"weekly\",\"monthly\"]}',0,1,0,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(31,'backup.retention_days','30','integer','Backup','Requested backup retention in days.','30','{\"required\":true,\"min\":1,\"max\":3650}',0,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(32,'system.maintenance_mode','0','boolean','System','Application maintenance mode switch.','0','{\"required\":true}',1,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(33,'system.debug_mode','0','boolean','System','Application diagnostic mode switch.','0','{\"required\":true}',0,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(34,'system.version','1.0.0','string','System','Displayed application version.','1.0.0','{\"required\":true,\"regex\":\"^[0-9]+\\.[0-9]+\\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$\"}',1,0,1,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(35,'mpi.enabled_identifier_types','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','array','Medical Records','Enabled alternate patient identifier types.','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','{\"required\":true}',0,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:44',NULL),(36,'mpi.global_unique_types','[\"National Identification Number\",\"Passport Number\"]','array','Medical Records','Identifier types unique across the hospital.','[\"National Identification Number\",\"Passport Number\"]','{}',0,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:44',NULL),(37,'mpi.authority_unique_types','[\"Insurance Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','array','Medical Records','Identifier types unique within an issuing authority.','[\"Insurance Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','{}',0,1,1,0,0,30,NULL,NULL,'2026-08-05 04:16:44',NULL),(38,'mpi.exact_match_threshold','100','integer','Medical Records','Exact duplicate score threshold.','100','{\"min\":90,\"max\":100}',0,1,1,0,0,40,NULL,NULL,'2026-08-05 04:16:44',NULL),(39,'mpi.strong_match_threshold','80','integer','Medical Records','Strong possible duplicate score threshold.','80','{\"min\":60,\"max\":99}',0,1,1,0,0,50,NULL,NULL,'2026-08-05 04:16:44',NULL),(40,'mpi.possible_match_threshold','55','integer','Medical Records','Possible duplicate score threshold.','55','{\"min\":30,\"max\":89}',0,1,1,0,0,60,NULL,NULL,'2026-08-05 04:16:44',NULL),(41,'mpi.search_page_size','25','integer','Medical Records','Default MPI search page size.','25','{\"min\":10,\"max\":100}',0,1,1,0,0,70,NULL,NULL,'2026-08-05 04:16:44',NULL),(42,'mpi.mask_identifier_types','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\"]','array','Medical Records','Identifier types masked in ordinary displays.','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\"]','{}',0,1,1,0,0,80,NULL,NULL,'2026-08-05 04:16:44',NULL),(51,'mpi.identifier_definitions','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','array','Medical Records','Approved alternate patient identifier definitions.','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','{\"required\":true}',0,1,1,0,0,90,NULL,NULL,'2026-08-05 05:43:18',NULL),(52,'mpi.duplicate_threshold','55','integer','Medical Records','Minimum score that creates a duplicate review warning.','55','{\"min\":1,\"max\":100}',0,1,1,0,0,100,NULL,NULL,'2026-08-05 05:43:18',NULL),(53,'mpi.fuzzy_search_threshold','70','integer','Medical Records','Minimum bounded fuzzy-name similarity percentage.','70','{\"min\":50,\"max\":100}',0,1,1,0,0,110,NULL,NULL,'2026-08-05 05:43:18',NULL),(54,'mpi.exact_match_priority','true','boolean','Medical Records','Rank exact identifiers before prefix and bounded fuzzy results.','true','{}',0,1,1,0,0,120,NULL,NULL,'2026-08-05 05:43:18',NULL),(55,'clinical_safety.allergy_types','[\"Drug\",\"Food\",\"Environmental\",\"Biological\",\"Other\"]','array','Medical Records','Allowed structured allergy types.','[\"Drug\",\"Food\",\"Environmental\",\"Biological\",\"Other\"]','{\"required\":true,\"schema_values\":[\"Drug\",\"Food\",\"Environmental\",\"Biological\",\"Other\"]}',0,1,1,0,0,200,NULL,NULL,'2026-08-05 07:26:17','2026-08-05 11:17:18'),(56,'clinical_safety.severity_values','[\"Mild\",\"Moderate\",\"Severe\",\"Life-threatening\",\"Unknown\"]','array','Medical Records','Allowed allergy severity values.','[\"Mild\",\"Moderate\",\"Severe\",\"Life-threatening\",\"Unknown\"]','{\"required\":true,\"schema_values\":[\"Mild\",\"Moderate\",\"Severe\",\"Life-threatening\",\"Unknown\"]}',0,1,1,0,0,210,NULL,NULL,'2026-08-05 07:26:17','2026-08-05 11:17:18'),(57,'clinical_safety.nurse_may_verify_allergies','false','boolean','Medical Records','Whether nurses with permission may confirm allergies.','false','{}',0,1,1,0,0,220,NULL,NULL,'2026-08-05 07:26:17',NULL),(58,'clinical_safety.alert_types','[\"Clinical Risk\",\"Infection Control\",\"Fall Risk\",\"Communication Need\",\"Safeguarding\",\"Special Handling\",\"Other\"]','array','Medical Records','Allowed clinical alert types.','[\"Clinical Risk\",\"Infection Control\",\"Fall Risk\",\"Communication Need\",\"Safeguarding\",\"Special Handling\",\"Other\"]','{\"required\":true,\"schema_values\":[\"Clinical Risk\",\"Infection Control\",\"Fall Risk\",\"Communication Need\",\"Safeguarding\",\"Special Handling\",\"Other\"]}',0,1,1,0,0,230,NULL,NULL,'2026-08-05 07:26:17','2026-08-05 11:17:18'),(59,'clinical_safety.alert_priorities','[\"Low\",\"Medium\",\"High\",\"Critical\"]','array','Medical Records','Allowed clinical alert priorities.','[\"Low\",\"Medium\",\"High\",\"Critical\"]','{\"required\":true,\"schema_values\":[\"Low\",\"Medium\",\"High\",\"Critical\"]}',0,1,1,0,0,240,NULL,NULL,'2026-08-05 07:26:17','2026-08-05 11:17:18'),(60,'clinical_safety.confidentiality_levels','[\"Standard\",\"Restricted\",\"Confidential\"]','array','Medical Records','Allowed alert confidentiality levels.','[\"Standard\",\"Restricted\",\"Confidential\"]','{\"required\":true,\"schema_values\":[\"Standard\",\"Restricted\",\"Confidential\"]}',0,1,1,0,0,250,NULL,NULL,'2026-08-05 07:26:17','2026-08-05 11:17:18'),(61,'clinical_safety.default_alert_expiry_days','0','integer','Medical Records','Default alert lifetime in days; zero means none.','0','{\"min\":0,\"max\":3650}',0,1,1,0,0,260,NULL,NULL,'2026-08-05 07:26:17',NULL),(62,'clinical_safety.legacy_allergy_warning','true','boolean','Medical Records','Display legacy allergy text as an unverified warning.','true','{}',0,1,1,0,0,270,NULL,NULL,'2026-08-05 07:26:17',NULL),(63,'clinical_safety.allow_self_allergy_verification','false','boolean','Medical Records','Whether an allergy author may verify their own unverified allergy. Disabled by default.','false','{}',0,1,1,0,0,225,NULL,NULL,'2026-08-05 11:17:18',NULL),(64,'problem_list.categories','[\"Chronic Condition\",\"Acute Problem\",\"Historical Diagnosis\",\"Surgical Condition\",\"Risk Factor\",\"Other\"]','array','Medical Records','Enabled Problem List categories.','[\"Chronic Condition\",\"Acute Problem\",\"Historical Diagnosis\",\"Surgical Condition\",\"Risk Factor\",\"Other\"]','{\"required\":true,\"schema_values\":[\"Chronic Condition\",\"Acute Problem\",\"Historical Diagnosis\",\"Surgical Condition\",\"Risk Factor\",\"Other\"]}',0,1,1,0,0,300,NULL,NULL,'2026-08-05 13:24:56',NULL),(65,'problem_list.severities','[\"Mild\",\"Moderate\",\"Severe\",\"Unknown\"]','array','Medical Records','Enabled Problem List severity values.','[\"Mild\",\"Moderate\",\"Severe\",\"Unknown\"]','{\"required\":true,\"schema_values\":[\"Mild\",\"Moderate\",\"Severe\",\"Unknown\"]}',0,1,1,0,0,310,NULL,NULL,'2026-08-05 13:24:56',NULL),(66,'problem_list.allow_self_verification','false','boolean','Medical Records','Whether the latest problem author may verify the same problem.','false','{}',0,1,1,0,0,320,NULL,NULL,'2026-08-05 13:24:56',NULL),(67,'problem_list.nurse_may_manage','false','boolean','Medical Records','Whether nurses with permission may manage longitudinal problems.','false','{}',0,1,1,0,0,330,NULL,NULL,'2026-08-05 13:24:56',NULL),(68,'problem_list.show_resolved_in_workspace','false','boolean','Medical Records','Whether resolved problems appear in Encounter Workspace summaries.','false','{}',0,1,1,0,0,340,NULL,NULL,'2026-08-05 13:24:56',NULL),(69,'medical_history.types','[\"Past Medical History\",\"Surgical History\",\"Family History\",\"Social History\",\"Obstetric History\",\"Immunization History\",\"Previous Hospitalization\",\"Previous Procedure\",\"Other\"]','array','Medical Records','Enabled structured medical-history types.','[\"Past Medical History\",\"Surgical History\",\"Family History\",\"Social History\",\"Obstetric History\",\"Immunization History\",\"Previous Hospitalization\",\"Previous Procedure\",\"Other\"]','{\"required\":true,\"schema_values\":[\"Past Medical History\",\"Surgical History\",\"Family History\",\"Social History\",\"Obstetric History\",\"Immunization History\",\"Previous Hospitalization\",\"Previous Procedure\",\"Other\"]}',0,1,1,0,0,350,NULL,NULL,'2026-08-05 13:24:56',NULL),(70,'medical_history.confidentiality_levels','[\"Standard\",\"Restricted\",\"Confidential\"]','array','Medical Records','Enabled confidentiality classifications for problems and medical history.','[\"Standard\",\"Restricted\",\"Confidential\"]','{\"required\":true,\"schema_values\":[\"Standard\",\"Restricted\",\"Confidential\"]}',0,1,1,0,0,360,NULL,NULL,'2026-08-05 13:24:56',NULL),(71,'medical_history.allow_self_verification','false','boolean','Medical Records','Whether the latest history author may verify the same entry.','false','{}',0,1,1,0,0,370,NULL,NULL,'2026-08-05 13:24:56',NULL),(72,'documents.allowed_types','[\"referral_letter\",\"identity_document\",\"insurance_document\",\"consent_form\",\"external_laboratory_result\",\"external_radiology_report\",\"discharge_document\",\"clinical_photograph\",\"medical_certificate\",\"correspondence\",\"other\"]','array','Medical Records','Enabled Medical Document type keys.','[\"referral_letter\",\"identity_document\",\"insurance_document\",\"consent_form\",\"external_laboratory_result\",\"external_radiology_report\",\"discharge_document\",\"clinical_photograph\",\"medical_certificate\",\"correspondence\",\"other\"]','{\"required\":true,\"schema_values\":[\"referral_letter\",\"identity_document\",\"insurance_document\",\"consent_form\",\"external_laboratory_result\",\"external_radiology_report\",\"discharge_document\",\"clinical_photograph\",\"medical_certificate\",\"correspondence\",\"other\"]}',0,1,1,0,0,400,NULL,NULL,'2026-08-05 14:59:38',NULL),(73,'documents.maximum_upload_bytes','10485760','integer','Medical Records','Maximum accepted Medical Document upload size in bytes.','10485760','{\"required\":true,\"min\":1024,\"max\":41943040}',0,1,1,0,0,410,NULL,NULL,'2026-08-05 14:59:38',NULL),(74,'documents.allowed_mime_types','[\"application/pdf\",\"image/jpeg\",\"image/png\",\"text/plain\"]','array','Medical Records','Enabled MIME subset within the mandatory server allowlist.','[\"application/pdf\",\"image/jpeg\",\"image/png\",\"text/plain\"]','{\"required\":true,\"schema_values\":[\"application/pdf\",\"image/jpeg\",\"image/png\",\"text/plain\"]}',0,1,1,0,0,420,NULL,NULL,'2026-08-05 14:59:38',NULL),(75,'documents.allowed_extensions','[\"pdf\",\"jpg\",\"jpeg\",\"png\",\"txt\"]','array','Medical Records','Enabled extension subset within the mandatory server allowlist.','[\"pdf\",\"jpg\",\"jpeg\",\"png\",\"txt\"]','{\"required\":true,\"schema_values\":[\"pdf\",\"jpg\",\"jpeg\",\"png\",\"txt\"]}',0,1,1,0,0,430,NULL,NULL,'2026-08-05 14:59:38',NULL),(76,'documents.confidentiality_levels','[\"Standard\",\"Restricted\",\"Confidential\",\"Highly Confidential\"]','array','Medical Records','Enabled document confidentiality classifications.','[\"Standard\",\"Restricted\",\"Confidential\",\"Highly Confidential\"]','{\"required\":true,\"schema_values\":[\"Standard\",\"Restricted\",\"Confidential\",\"Highly Confidential\"]}',0,1,1,0,0,440,NULL,NULL,'2026-08-05 14:59:38',NULL),(77,'documents.default_confidentiality','Standard','string','Medical Records','Default confidentiality for new Medical Documents.','Standard','{\"required\":true,\"allowed_values\":[\"Standard\",\"Restricted\",\"Confidential\",\"Highly Confidential\"]}',0,1,1,0,0,450,NULL,NULL,'2026-08-05 14:59:38',NULL),(78,'documents.malware_scanning_required','false','boolean','Medical Records','Whether unscanned uploads must remain quarantined.','false','{}',0,1,1,0,0,460,NULL,NULL,'2026-08-05 14:59:38',NULL),(79,'documents.storage_provider','local','string','Medical Records','Permitted active document storage provider.','local','{\"required\":true,\"allowed_values\":[\"local\"]}',0,0,1,0,0,470,NULL,NULL,'2026-08-05 14:59:38',NULL),(80,'documents.download_cache_policy','no-store','string','Medical Records','Cache-Control policy for authorized downloads.','no-store','{\"required\":true,\"allowed_values\":[\"no-store\",\"private, no-cache\"]}',0,1,1,0,0,480,NULL,NULL,'2026-08-05 14:59:38',NULL),(81,'documents.closed_encounter_uploads','false','boolean','Medical Records','Whether closed encounters accept new or replacement attachments.','false','{}',0,1,1,0,0,490,NULL,NULL,'2026-08-05 14:59:38',NULL),(82,'documents.retention_years','10','integer','Medical Records','Minimum configured retention horizon; no automatic purge is implemented.','10','{\"required\":true,\"min\":1,\"max\":100}',0,1,1,0,0,500,NULL,NULL,'2026-08-05 14:59:38',NULL),(83,'clinical_notes.enabled_types','[\"general_clinical_note\",\"medical_records_note\",\"progress_note\",\"care_coordination_note\",\"patient_communication_note\",\"administrative_clinical_note\",\"external_record_summary\",\"other\"]','array','Medical Records','Enabled generic Clinical Note type keys.','[\"general_clinical_note\",\"medical_records_note\",\"progress_note\",\"care_coordination_note\",\"patient_communication_note\",\"administrative_clinical_note\",\"external_record_summary\",\"other\"]','{\"required\":true,\"schema_values\":[\"general_clinical_note\",\"medical_records_note\",\"progress_note\",\"care_coordination_note\",\"patient_communication_note\",\"administrative_clinical_note\",\"external_record_summary\",\"other\"]}',0,1,1,0,0,510,NULL,NULL,'2026-08-05 16:45:40',NULL),(84,'clinical_notes.default_type','general_clinical_note','string','Medical Records','Default Clinical Note type.','general_clinical_note','{\"required\":true,\"allowed_values\":[\"general_clinical_note\",\"medical_records_note\",\"progress_note\",\"care_coordination_note\",\"patient_communication_note\",\"administrative_clinical_note\",\"external_record_summary\",\"other\"]}',0,1,1,0,0,520,NULL,NULL,'2026-08-05 16:45:40',NULL),(85,'clinical_notes.maximum_content_length','50000','integer','Medical Records','Maximum plain-text Clinical Note content length.','50000','{\"required\":true,\"min\":100,\"max\":250000}',0,1,1,0,0,530,NULL,NULL,'2026-08-05 16:45:40',NULL),(86,'clinical_notes.confidentiality_levels','[\"Standard\",\"Restricted\",\"Confidential\",\"Highly Confidential\"]','array','Medical Records','Enabled Clinical Note confidentiality subset.','[\"Standard\",\"Restricted\",\"Confidential\",\"Highly Confidential\"]','{\"required\":true,\"schema_values\":[\"Standard\",\"Restricted\",\"Confidential\",\"Highly Confidential\"]}',0,1,1,0,0,540,NULL,NULL,'2026-08-05 16:45:40',NULL),(87,'clinical_notes.default_confidentiality','Standard','string','Medical Records','Default Clinical Note confidentiality.','Standard','{\"required\":true,\"allowed_values\":[\"Standard\",\"Restricted\",\"Confidential\",\"Highly Confidential\"]}',0,1,1,0,0,550,NULL,NULL,'2026-08-05 16:45:40',NULL),(88,'clinical_notes.allow_self_signing','true','boolean','Medical Records','Allow an authorized clinical author to sign their own draft.','true','{}',0,1,1,0,0,560,NULL,NULL,'2026-08-05 16:45:40',NULL),(89,'clinical_notes.amendment_approval_required','true','boolean','Medical Records','Require approval before an amendment proposal becomes the current signed record.','true','{}',0,1,1,0,0,570,NULL,NULL,'2026-08-05 16:45:40',NULL),(90,'clinical_notes.allow_self_amendment_approval','false','boolean','Medical Records','Allow an amendment requester to approve the same request.','false','{}',0,1,1,0,0,580,NULL,NULL,'2026-08-05 16:45:40',NULL),(91,'clinical_notes.closed_encounter_new_notes','false','boolean','Medical Records','Allow new or edited encounter notes after encounter closure.','false','{}',0,1,1,0,0,590,NULL,NULL,'2026-08-05 16:45:40',NULL),(92,'clinical_notes.draft_visibility','author_and_authorized_editors','string','Medical Records','Visibility policy for unsigned Clinical Note drafts.','author_and_authorized_editors','{\"required\":true,\"allowed_values\":[\"author_only\",\"author_and_authorized_editors\"]}',0,1,1,0,0,600,NULL,NULL,'2026-08-05 16:45:40',NULL),(93,'clinical_notes.auto_lock_on_signing','true','boolean','Medical Records','Mandatory lock policy for newly signed Clinical Notes.','true','{}',0,0,1,0,0,610,NULL,NULL,'2026-08-05 16:45:40',NULL);
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `theatre_records`
--

DROP TABLE IF EXISTS `theatre_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `theatre_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `surgeon_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `procedure_name` varchar(255) NOT NULL,
  `indication` text DEFAULT NULL,
  `preoperative_notes` text DEFAULT NULL,
  `procedure_details` longtext NOT NULL,
  `findings` text DEFAULT NULL,
  `complications` text DEFAULT NULL,
  `postoperative_notes` text DEFAULT NULL,
  `postoperative_plan` text DEFAULT NULL,
  `anaesthesia_notes` text DEFAULT NULL,
  `status` enum('Draft','Completed') NOT NULL DEFAULT 'Draft',
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_theatre_records_visit` (`visit_id`),
  KEY `idx_theatre_records_patient` (`patient_id`),
  KEY `idx_theatre_records_surgeon` (`surgeon_id`),
  KEY `idx_theatre_records_department` (`department_id`),
  KEY `idx_theatre_records_status` (`status`),
  KEY `idx_theatre_records_created_at` (`created_at`),
  KEY `idx_theatre_records_created_by` (`created_by`),
  KEY `idx_theatre_records_completed_by` (`completed_by`),
  KEY `fk_theatre_records_updated_by` (`updated_by`),
  CONSTRAINT `fk_theatre_records_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_theatre_records_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_theatre_records_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_theatre_records_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_theatre_records_surgeon` FOREIGN KEY (`surgeon_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_theatre_records_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_theatre_records_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `theatre_records`
--

LOCK TABLES `theatre_records` WRITE;
/*!40000 ALTER TABLE `theatre_records` DISABLE KEYS */;
INSERT INTO `theatre_records` VALUES (1,3,2,5,10,'ana','ankaaa','aahhha','anahah','aahhaha','anahha','aahhah','ahahah','akhahah','Completed',1,1,1,'2026-08-10 22:24:27','2026-08-10 22:24:39','2026-08-10 23:24:39');
/*!40000 ALTER TABLE `theatre_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_departments`
--

DROP TABLE IF EXISTS `user_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `assigned_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_department` (`user_id`,`department_id`),
  KEY `idx_user_departments_user_active` (`user_id`,`is_active`),
  KEY `idx_user_departments_department_active` (`department_id`,`is_active`),
  KEY `idx_user_departments_primary` (`user_id`,`is_primary`),
  KEY `fk_user_departments_assigned_by` (`assigned_by`),
  CONSTRAINT `fk_user_departments_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_user_departments_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_user_departments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_departments`
--

LOCK TABLES `user_departments` WRITE;
/*!40000 ALTER TABLE `user_departments` DISABLE KEYS */;
INSERT INTO `user_departments` VALUES (1,1,1,1,1,'2026-08-05 05:48:44',NULL),(2,2,2,1,1,'2026-08-05 05:48:53',1),(3,3,3,1,1,'2026-08-05 05:48:53',1),(4,4,5,1,1,'2026-08-05 05:48:53',1),(5,5,4,1,1,'2026-08-05 05:48:53',1),(6,6,6,1,1,'2026-08-05 05:48:53',1),(7,7,9,1,1,'2026-08-05 05:48:53',1),(8,8,7,1,1,'2026-08-05 05:48:53',1),(9,9,11,1,1,'2026-08-05 05:48:53',1),(26,26,4,1,1,'2026-08-23 11:14:26',1);
/*!40000 ALTER TABLE `user_departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_notifications`
--

DROP TABLE IF EXISTS `user_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `sent_by` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` enum('Unread','Read','Resolved') NOT NULL DEFAULT 'Unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_by` int(11) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_notifications_to_status` (`to_user_id`,`status`,`created_at`),
  KEY `idx_user_notifications_visit` (`visit_id`,`created_at`),
  KEY `idx_user_notifications_patient` (`patient_id`,`created_at`),
  KEY `idx_user_notifications_sent_by` (`sent_by`,`created_at`),
  KEY `fk_user_notifications_read_by` (`read_by`),
  KEY `fk_user_notifications_resolved_by` (`resolved_by`),
  CONSTRAINT `fk_user_notifications_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_user_notifications_read_by` FOREIGN KEY (`read_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_user_notifications_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_user_notifications_sent_by` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_user_notifications_to_user` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_user_notifications_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_notifications`
--

LOCK TABLES `user_notifications` WRITE;
/*!40000 ALTER TABLE `user_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(30) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `last_failed_login` datetime DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `locked_by` int(11) DEFAULT NULL,
  `lock_reason` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_employee` (`employee_id`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_department` (`department_id`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_lastname` (`last_name`),
  KEY `idx_users_firstname` (`first_name`),
  KEY `idx_users_locked_at` (`locked_at`),
  KEY `fk_users_locked_by` (`locked_by`),
  CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_users_locked_by` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'EMP000001','System','Administrator','Male',NULL,'admin@hospital.local','admin','$2y$10$FnIJ0nHOUgqzpWyhXO7x1.XvdUf.DJycv3waqFVpfyb41YceVm77O',1,1,'Active',0,NULL,NULL,NULL,NULL,'2026-08-23 11:12:09','2026-08-05 11:09:20',0,'2026-08-05 04:14:45','2026-08-23 10:12:09'),(2,'DEV-REC-001','Development','Receptionist','Female',NULL,'dev_reception@development.invalid','reception','$2y$10$BaF.29l2BQOh6FFaQR/OWeYerRjEg6QjVBk0j0Sjt4aqYQvh4VvQC',2,2,'Active',0,NULL,NULL,NULL,NULL,'2026-08-23 11:19:24','2026-08-23 10:12:53',0,'2026-08-05 04:48:53','2026-08-23 10:19:24'),(3,'DEV-REC-002','Development','Records','Female',NULL,'dev_records@development.invalid','dev_records','$2y$10$HMtgFGPxc2126oswHM/9qeCXSFYN16O0VADQqW/xVXkG0hh0zmxXa',3,3,'Active',0,NULL,NULL,NULL,NULL,'2026-08-23 11:05:16','2026-08-23 11:05:54',0,'2026-08-05 04:48:53','2026-08-23 10:05:54'),(4,'DEV-NUR-001','Development','Nurse','Female',NULL,'dev_nurse@development.invalid','dev_nurse','$2y$10$CxNXvTpmY5sV0KbJEhSr9.b0aAO9cUMReTRg4afaqHDdsrdCChv0y',5,5,'Active',0,NULL,NULL,NULL,NULL,'2026-08-23 09:51:04','2026-08-08 12:22:45',0,'2026-08-05 04:48:53','2026-08-23 08:51:04'),(5,'DEV-DOC-001','Amara','Okafor','Female',NULL,'dev_doctor@development.invalid','dev_doctor','$2y$10$wt9BsrQGAJkI6yuNPIZ7juORmG0bQaqrzyX0En1JKj/k2EW8arIxG',4,4,'Active',0,NULL,NULL,NULL,NULL,'2026-08-23 09:51:23','2026-08-08 12:16:41',0,'2026-08-05 04:48:53','2026-08-23 08:51:23'),(6,'DEV-LAB-001','Development','Laboratory','Female',NULL,'dev_laboratory@development.invalid','dev_laboratory','$2y$10$FLNnaBMCiIJxTwrBVOH3x.oQum9oKpZmJz07B3PwBP8/w/o235dde',6,6,'Active',0,NULL,NULL,NULL,NULL,NULL,'2026-08-05 11:06:49',1,'2026-08-05 04:48:53','2026-08-05 10:06:49'),(7,'DEV-RAD-001','Development','Radiology','Female',NULL,'dev_radiology@development.invalid','dev_radiology','$2y$10$.0K2ob5.cHdErVhFQa.zQeu2bK2qb/CsY.MTcoeMX8mMWY.4WxSHK',9,9,'Active',0,NULL,NULL,NULL,NULL,NULL,'2026-08-05 11:06:49',1,'2026-08-05 04:48:53','2026-08-05 10:06:49'),(8,'DEV-PHA-001','Development','Pharmacy','Female',NULL,'dev_pharmacy@development.invalid','dev_pharmacy','$2y$10$IlneNxk5MFGH63k1mIM5KekwGmBDrlzAhDhiJClk9lsTtfwfkK7xC',7,7,'Active',0,NULL,NULL,NULL,NULL,NULL,'2026-08-05 11:06:49',1,'2026-08-05 04:48:53','2026-08-05 10:06:49'),(9,'DEV-ACC-001','Development','Accounts','Female',NULL,'dev_accounts@development.invalid','dev_accounts','$2y$10$a5huFZvsY2vist.JpN2Q7./uxCFAQxat5f8NRYl7rsRXpb7Da4KJS',11,11,'Active',0,NULL,NULL,NULL,NULL,NULL,'2026-08-05 11:06:50',1,'2026-08-05 04:48:53','2026-08-05 10:06:50'),(26,'DEV_DOCTOR2','Olisemeke','Ikhile','Male','09156461253','waltertalksmoney@gmail.com','DOC2','$2y$10$leWyGucDKMtxRTEe2H0k4OK89AumlXkQsijw0I9HOjNazIug/Xkja',4,4,'Active',0,NULL,NULL,NULL,NULL,'2026-08-23 11:20:46',NULL,0,'2026-08-23 10:14:26','2026-08-23 10:20:46');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visit_queue`
--

DROP TABLE IF EXISTS `visit_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visit_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `assigned_user_id` int(11) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `queue_status` enum('Waiting','Called','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Waiting',
  `queued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `called_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_queue_visit` (`visit_id`),
  KEY `idx_queue_department` (`department_id`),
  KEY `idx_queue_status` (`queue_status`),
  KEY `idx_queue_department_status_position` (`department_id`,`queue_status`,`position`,`queued_at`),
  KEY `idx_queue_visit_status` (`visit_id`,`queue_status`),
  KEY `idx_queue_queued_at` (`queued_at`),
  KEY `idx_queue_position` (`position`),
  KEY `fk_queue_assigned_user` (`assigned_user_id`),
  CONSTRAINT `fk_queue_assigned_user` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_queue_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_queue_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visit_queue`
--

LOCK TABLES `visit_queue` WRITE;
/*!40000 ALTER TABLE `visit_queue` DISABLE KEYS */;
INSERT INTO `visit_queue` VALUES (1,1,2,1,1,NULL,'Completed','2026-08-05 04:56:02','2026-08-05 05:56:02','2026-08-05 05:56:02','2026-08-05 05:56:02',NULL),(2,1,4,NULL,1,'Queue closed because the encounter was closed with status Completed.','Cancelled','2026-08-05 04:56:02',NULL,NULL,NULL,'2026-08-05 05:56:02'),(3,2,4,NULL,1,'Queue closed because the encounter was closed with status Completed.','Cancelled','2026-08-05 20:41:24',NULL,NULL,NULL,'2026-08-08 11:43:52'),(4,3,4,NULL,1,NULL,'Waiting','2026-08-08 10:44:27',NULL,NULL,NULL,NULL),(5,4,8,NULL,1,NULL,'Waiting','2026-08-12 15:40:48',NULL,NULL,NULL,NULL),(6,10,4,NULL,2,NULL,'Waiting','2026-08-19 13:46:50',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `visit_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visit_transfers`
--

DROP TABLE IF EXISTS `visit_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visit_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `from_department_id` int(11) DEFAULT NULL,
  `to_department_id` int(11) NOT NULL,
  `from_status` varchar(50) NOT NULL,
  `to_status` varchar(50) NOT NULL,
  `transfer_type` enum('Forward','Return','Referral','Discharge','Completion','Cancellation') NOT NULL DEFAULT 'Forward',
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `transferred_by` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `transferred_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `received_by` int(11) DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_visit` (`visit_id`),
  KEY `idx_from_department` (`from_department_id`),
  KEY `idx_to_department` (`to_department_id`),
  KEY `idx_transferred_by` (`transferred_by`),
  KEY `idx_transfer_pending` (`visit_id`,`received_at`,`transferred_at`),
  KEY `idx_transfer_destination_pending` (`to_department_id`,`received_at`,`transferred_at`),
  KEY `fk_transfer_received_by` (`received_by`),
  CONSTRAINT `fk_transfer_from_department` FOREIGN KEY (`from_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_transfer_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_transfer_to_department` FOREIGN KEY (`to_department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_transfer_user` FOREIGN KEY (`transferred_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_transfer_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visit_transfers`
--

LOCK TABLES `visit_transfers` WRITE;
/*!40000 ALTER TABLE `visit_transfers` DISABLE KEYS */;
INSERT INTO `visit_transfers` VALUES (1,1,2,4,'Reception','Doctor','Forward','Reception','Doctor',1,NULL,'2026-08-05 04:56:02',1,'2026-08-05 05:56:02');
/*!40000 ALTER TABLE `visit_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visits`
--

DROP TABLE IF EXISTS `visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_number` varchar(30) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `visit_date` datetime NOT NULL,
  `visit_type` enum('Outpatient','Inpatient','Emergency','Referral') NOT NULL DEFAULT 'Outpatient',
  `current_department_id` int(11) DEFAULT NULL,
  `attending_doctor_id` int(11) DEFAULT NULL,
  `queue_number` int(11) DEFAULT NULL,
  `current_department_received_status` enum('Pending','Received') NOT NULL DEFAULT 'Pending',
  `current_department_received_by` int(11) DEFAULT NULL,
  `current_department_received_at` datetime DEFAULT NULL,
  `visit_status` enum('Waiting','Reception','Records','Nursing','Doctor','Laboratory','X-Ray','Pharmacy','Physiotherapy','Theatre','Accounts','Store','Completed','Cancelled') NOT NULL DEFAULT 'Waiting',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `discharge_diagnosis` text DEFAULT NULL,
  `discharge_notes` text DEFAULT NULL,
  `follow_up_instructions` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_visits_number` (`visit_number`),
  KEY `idx_visits_patient` (`patient_id`),
  KEY `idx_visits_department` (`current_department_id`),
  KEY `idx_visits_doctor` (`attending_doctor_id`),
  KEY `idx_visits_department_receive` (`current_department_id`,`current_department_received_status`),
  KEY `idx_visits_creator` (`created_by`),
  KEY `idx_visits_status` (`visit_status`),
  KEY `idx_visits_date` (`visit_date`),
  KEY `fk_visits_received_by` (`current_department_received_by`),
  KEY `idx_visits_completed_at` (`completed_at`),
  KEY `idx_visits_completed_by` (`completed_by`),
  CONSTRAINT `fk_visits_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_visits_department` FOREIGN KEY (`current_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_visits_doctor` FOREIGN KEY (`attending_doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_visits_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_visits_received_by` FOREIGN KEY (`current_department_received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visits`
--

LOCK TABLES `visits` WRITE;
/*!40000 ALTER TABLE `visits` DISABLE KEYS */;
INSERT INTO `visits` VALUES (1,'VIS-2026-000001',2,'2026-08-05 06:56:02','Outpatient',4,5,NULL,'Received',1,'2026-08-05 05:56:02','Completed',1,'2026-08-05 04:56:02','2026-08-05 04:56:02',NULL,NULL,NULL,NULL,NULL),(2,'VIS-2026-000002',2,'2026-08-05 22:41:00','Inpatient',4,5,NULL,'Received',1,'2026-08-05 21:41:24','Completed',1,'2026-08-05 20:41:24','2026-08-08 10:43:52',NULL,NULL,NULL,NULL,NULL),(3,'VIS-2026-000003',2,'2026-08-08 12:44:00','Emergency',4,5,NULL,'Received',1,'2026-08-08 11:44:27','Doctor',1,'2026-08-08 10:44:27','2026-08-12 15:29:05',NULL,NULL,NULL,NULL,NULL),(4,'VIS-2026-000004',3,'2026-08-12 17:40:00','Inpatient',8,NULL,1,'Received',1,'2026-08-12 16:40:48','Physiotherapy',1,'2026-08-12 15:40:48','2026-08-12 15:40:48',NULL,NULL,NULL,NULL,NULL),(10,'VIS-2026-000005',8,'2026-08-19 15:46:00','Emergency',4,26,NULL,'Received',1,'2026-08-19 14:46:50','Doctor',1,'2026-08-19 13:46:50','2026-08-23 10:16:47',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `visits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vital_signs`
--

DROP TABLE IF EXISTS `vital_signs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vital_signs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `pulse` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `systolic_bp` int(11) DEFAULT NULL,
  `diastolic_bp` int(11) DEFAULT NULL,
  `oxygen_saturation` decimal(5,2) DEFAULT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `height` decimal(6,2) DEFAULT NULL,
  `bmi` decimal(6,2) DEFAULT NULL,
  `blood_glucose` decimal(7,2) DEFAULT NULL,
  `pain_score` tinyint(4) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vital_signs_visit_created` (`visit_id`,`created_at`),
  KEY `idx_vital_signs_patient_created` (`patient_id`,`created_at`),
  KEY `idx_vital_signs_recorded_by_created` (`recorded_by`,`created_at`),
  KEY `idx_vital_signs_department_created` (`department_id`,`created_at`),
  CONSTRAINT `fk_vital_signs_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_vital_signs_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_vital_signs_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_vital_signs_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vital_signs`
--

LOCK TABLES `vital_signs` WRITE;
/*!40000 ALTER TABLE `vital_signs` DISABLE KEYS */;
INSERT INTO `vital_signs` VALUES (1,3,2,4,1,37.00,234,34,41,35,20.00,67.00,183.00,20.01,272.00,4,'Looks fine','2026-08-08 21:03:31',NULL),(2,10,8,4,4,30.00,100,30,44,56,30.00,76.00,154.00,32.05,56.00,4,'none','2026-08-23 08:50:54',NULL);
/*!40000 ALTER TABLE `vital_signs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ward_beds`
--

DROP TABLE IF EXISTS `ward_beds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ward_beds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ward_id` int(11) NOT NULL,
  `bed_label` varchar(50) NOT NULL,
  `bed_status` enum('Available','Occupied','Unavailable') NOT NULL DEFAULT 'Available',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ward_beds_label` (`ward_id`,`bed_label`),
  KEY `idx_ward_beds_status` (`bed_status`),
  KEY `idx_ward_beds_active` (`is_active`),
  KEY `fk_ward_beds_created_by` (`created_by`),
  KEY `fk_ward_beds_updated_by` (`updated_by`),
  CONSTRAINT `fk_ward_beds_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ward_beds_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ward_beds_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ward_beds`
--

LOCK TABLES `ward_beds` WRITE;
/*!40000 ALTER TABLE `ward_beds` DISABLE KEYS */;
INSERT INTO `ward_beds` VALUES (1,1,'bed one','Occupied',1,4,4,'2026-08-23 08:16:21','2026-08-23 08:17:06');
/*!40000 ALTER TABLE `ward_beds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wards`
--

DROP TABLE IF EXISTS `wards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ward_name` varchar(120) NOT NULL,
  `ward_code` varchar(30) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wards_code` (`ward_code`),
  UNIQUE KEY `uq_wards_name` (`ward_name`),
  KEY `idx_wards_department` (`department_id`),
  KEY `idx_wards_active` (`is_active`),
  KEY `fk_wards_created_by` (`created_by`),
  KEY `fk_wards_updated_by` (`updated_by`),
  CONSTRAINT `fk_wards_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_wards_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_wards_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wards`
--

LOCK TABLES `wards` WRITE;
/*!40000 ALTER TABLE `wards` DISABLE KEYS */;
INSERT INTO `wards` VALUES (1,'Right Wing','001',5,'Right Side one',1,4,NULL,'2026-08-23 08:16:06',NULL);
/*!40000 ALTER TABLE `wards` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-23 11:44:59
