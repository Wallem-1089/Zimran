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
-- Current Database: `hospital_management_system`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `hospital_management_system` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `hospital_management_system`;

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `active_sessions`
--

LOCK TABLES `active_sessions` WRITE;
/*!40000 ALTER TABLE `active_sessions` DISABLE KEYS */;
INSERT INTO `active_sessions` VALUES (1,'so0d745dl2krbnfo7jvarv3sb7',1,'2026-08-05 05:56:45','2026-08-05 05:56:45','2026-08-05 07:26:45','::1','curl/8.21.0',1,'Active',NULL,NULL,NULL,'2026-08-05 04:56:45'),(2,'cc94gd1l1002kmbiu8m21gkvnl',1,'2026-08-05 05:56:53','2026-08-05 05:57:21','2026-08-05 07:27:21','::1','curl/8.21.0',1,'Active',NULL,NULL,NULL,'2026-08-05 04:56:53'),(3,'9h66851plgduchjvs2fo02rnja',1,'2026-08-05 11:07:58','2026-08-05 11:09:53','2026-08-05 12:39:53','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'Active',NULL,NULL,NULL,'2026-08-05 10:07:58');
/*!40000 ALTER TABLE `active_sessions` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (3,1,1,NULL,'Encounter','CREATE','Encounter created and patient received in Reception.','UNKNOWN',NULL,NULL,'INFO','CREATE','2026-08-05 04:56:02'),(4,1,1,NULL,'Queue','ENQUEUE','Encounter added to the department queue.','UNKNOWN',NULL,NULL,'INFO','ENQUEUE','2026-08-05 04:56:02'),(5,1,1,NULL,'Queue','CALL','Encounter called from the department queue.','UNKNOWN',NULL,NULL,'INFO','CALL','2026-08-05 04:56:02'),(6,1,1,NULL,'Queue','START_SERVICE','Encounter service started.','UNKNOWN',NULL,NULL,'INFO','START_SERVICE','2026-08-05 04:56:02'),(7,1,1,NULL,'Queue','COMPLETE_SERVICE','Encounter service completed. Remarks: Controlled reconstruction verification.','UNKNOWN',NULL,NULL,'INFO','COMPLETE_SERVICE','2026-08-05 04:56:02'),(8,1,1,NULL,'Visits','TRANSFER','Encounter transferred from 2 to Doctor.','UNKNOWN',NULL,NULL,'INFO','TRANSFER','2026-08-05 04:56:02'),(9,1,1,NULL,'Queue','ENQUEUE','Encounter added to the department queue.','UNKNOWN',NULL,NULL,'INFO','ENQUEUE','2026-08-05 04:56:02'),(10,1,1,NULL,'Visits','RECEIVE','Patient received in Doctor department.','UNKNOWN',NULL,NULL,'INFO','RECEIVE','2026-08-05 04:56:02'),(11,1,1,NULL,'Encounter','ASSIGN_DOCTOR','Doctor assigned: Amara Okafor.','UNKNOWN',NULL,NULL,'INFO','ASSIGN_DOCTOR','2026-08-05 04:56:02'),(12,1,1,NULL,'Queue','STATUS_QUEUE_CLOSE','Queue entry closed because the encounter was closed with status Completed.','UNKNOWN',NULL,NULL,'INFO','STATUS_QUEUE_CLOSE','2026-08-05 04:56:02'),(13,1,1,NULL,'Encounter','STATUS_CHANGED','Encounter status changed to Completed.','UNKNOWN',NULL,NULL,'INFO','STATUS_CHANGED','2026-08-05 04:56:02'),(14,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','curl/8.21.0',1,'INFO','SESSION_CREATED','2026-08-05 04:56:45'),(15,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','curl/8.21.0',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 04:56:45'),(16,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','curl/8.21.0',1,'INFO','SESSION_CREATED','2026-08-05 04:56:53'),(17,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','curl/8.21.0',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 04:56:53'),(18,1,NULL,NULL,'Security','PASSWORD_CHANGED','User password changed.','::1','curl/8.21.0',NULL,'INFO','PASSWORD_CHANGED','2026-08-05 04:57:06'),(19,1,NULL,NULL,'Administration','ADMIN_DASHBOARD_VIEWED','Administrator dashboard viewed.','::1','curl/8.21.0',NULL,'INFO','ADMIN_DASHBOARD_VIEWED','2026-08-05 04:57:06'),(20,1,NULL,NULL,'Security','SECURITY_REPORT_VIEWED','Viewed the security dashboard.','::1','curl/8.21.0',1,'INFO','SECURITY_REPORT_VIEWED','2026-08-05 04:57:07'),(21,1,1,NULL,'Security','TRANSFER_ACCESS_DENIED','You do not have permission to transfer this encounter.','::1','curl/8.21.0',NULL,'INFO','TRANSFER_ACCESS_DENIED','2026-08-05 04:57:20'),(22,1,1,NULL,'Security','ASSIGN_DOCTOR_ACCESS_DENIED','You do not have permission to assign a doctor to this encounter.','::1','curl/8.21.0',NULL,'INFO','ASSIGN_DOCTOR_ACCESS_DENIED','2026-08-05 04:57:21'),(23,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'WARNING','LOGIN_FAILED','2026-08-05 06:36:26'),(24,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',NULL,'WARNING','LOGIN_FAILED','2026-08-05 07:27:20'),(25,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'WARNING','LOGIN_FAILED','2026-08-05 10:01:01'),(26,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'WARNING','LOGIN_FAILED','2026-08-05 10:01:21'),(27,2,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'WARNING','LOGIN_FAILED','2026-08-05 10:01:50'),(28,3,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'WARNING','LOGIN_FAILED','2026-08-05 10:02:07'),(29,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #1.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:48'),(30,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #2.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(31,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #3.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(32,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #4.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(33,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #5.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(34,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #6.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(35,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #7.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(36,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #8.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:49'),(37,1,NULL,NULL,'Administration','PASSWORD_RESET','Reset password for user account #9.','UNKNOWN',NULL,NULL,'WARNING','PASSWORD_RESET','2026-08-05 10:06:50'),(38,1,NULL,NULL,'Security','SESSION_CREATED','Created authenticated session.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',1,'INFO','SESSION_CREATED','2026-08-05 10:07:58'),(39,1,NULL,NULL,'Authentication','LOGIN_SUCCESS','User logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','LOGIN_SUCCESS','2026-08-05 10:07:58'),(40,1,NULL,NULL,'Security','PASSWORD_CHANGED','User password changed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',NULL,'INFO','PASSWORD_CHANGED','2026-08-05 10:09:20'),(41,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',NULL,'WARNING','LOGIN_FAILED','2026-08-05 11:19:26'),(42,1,NULL,NULL,'Authentication','LOGIN_FAILED','Failed login attempt.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8875',NULL,'WARNING','LOGIN_FAILED','2026-08-05 11:19:50');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_events`
--

LOCK TABLES `encounter_events` WRITE;
/*!40000 ALTER TABLE `encounter_events` DISABLE KEYS */;
INSERT INTO `encounter_events` VALUES (1,1,'ENCOUNTER_CREATED','Encounter Created','Encounter created and patient received in Reception.',2,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(2,1,'QUEUED','Encounter Queued','Encounter added to the department queue.',2,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(3,1,'CALLED','Patient Called','Encounter called from the department queue.',2,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(4,1,'SERVICE_STARTED','Service Started','Encounter service started.',2,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(5,1,'SERVICE_COMPLETED','Service Completed','Encounter service completed. Remarks: Controlled reconstruction verification.',2,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(6,1,'TRANSFERRED','Encounter Transferred','Encounter transferred from 2 to Doctor.',4,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(7,1,'QUEUED','Encounter Queued','Encounter added to the department queue.',4,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(8,1,'PATIENT_RECEIVED','Patient Received','Patient received in Doctor department.',4,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(9,1,'DOCTOR_ASSIGNED','Doctor Assigned','Doctor assigned: Amara Okafor.',4,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(10,1,'QUEUE_CANCELLED','Queue Entry Closed','Queue entry closed because the encounter was closed with status Completed.',4,1,'2026-08-05 05:56:02','2026-08-05 04:56:02'),(11,1,'STATUS_CHANGED','Encounter Status Changed','Encounter status changed to Completed.',NULL,1,'2026-08-05 05:56:02','2026-08-05 04:56:02');
/*!40000 ALTER TABLE `encounter_events` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_history`
--

LOCK TABLES `password_history` WRITE;
/*!40000 ALTER TABLE `password_history` DISABLE KEYS */;
INSERT INTO `password_history` VALUES (1,1,'$2y$10$OVYRg6IDLR3ogSdposvMbeuPgrtB78MfZlnK2AiruD9zXUT6zouwy','Changed',1,'2026-08-05 05:57:06'),(2,1,'$2y$10$WZnb1sIG0J/.pOj9mCGrvOpyN8nLSq8UVj1w9.cq2BSa1N9wmSFkS','Reset',1,'2026-08-05 11:06:48'),(3,2,'$2y$10$0N9.CoF6tbyufpRsTnjljeMxOPrWT1ZvOkXMTd7u4rnYoAgJCRBrG','Reset',1,'2026-08-05 11:06:49'),(4,3,'$2y$10$cvPuYgLrN5MpMSWmK0StROsHuufbW4K.UY5S8RlieIaN2tnuGrI/G','Reset',1,'2026-08-05 11:06:49'),(5,4,'$2y$10$7G43TFFmJCIxCONxCpqO1OdE7xi72F1qAISD9U8bSXCmzQ4Atq/m.','Reset',1,'2026-08-05 11:06:49'),(6,5,'$2y$10$LjToFV7TjF0879NvOBAu.OiG.G9ibl.GDUry5ERFvpM9V81YPEF5K','Reset',1,'2026-08-05 11:06:49'),(7,6,'$2y$10$FLNnaBMCiIJxTwrBVOH3x.oQum9oKpZmJz07B3PwBP8/w/o235dde','Reset',1,'2026-08-05 11:06:49'),(8,7,'$2y$10$.0K2ob5.cHdErVhFQa.zQeu2bK2qb/CsY.MTcoeMX8mMWY.4WxSHK','Reset',1,'2026-08-05 11:06:49'),(9,8,'$2y$10$IlneNxk5MFGH63k1mIM5KekwGmBDrlzAhDhiJClk9lsTtfwfkK7xC','Reset',1,'2026-08-05 11:06:49'),(10,9,'$2y$10$a5huFZvsY2vist.JpN2Q7./uxCFAQxat5f8NRYl7rsRXpb7Da4KJS','Reset',1,'2026-08-05 11:06:50'),(11,1,'$2y$10$FnIJ0nHOUgqzpWyhXO7x1.XvdUf.DJycv3waqFVpfyb41YceVm77O','Changed',1,'2026-08-05 11:09:20');
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
  `phone` varchar(20) DEFAULT NULL,
  `normalized_phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `normalized_email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state_of_origin` varchar(100) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `genotype` varchar(5) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `next_of_kin` varchar(150) DEFAULT NULL,
  `next_of_kin_relationship` varchar(100) DEFAULT NULL,
  `next_of_kin_phone` varchar(20) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (2,'DEV-PATIENT-0001','Development','development',NULL,NULL,'PatientOne','patientone','Unknown','1985-01-15',NULL,NULL,'08000000001','08000000001',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2026-08-05 04:48:53',NULL),(3,'DEV-PATIENT-0002','Development','development',NULL,NULL,'PatientTwo','patienttwo','Unknown','1992-06-30',NULL,NULL,'08000000002','08000000002',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2026-08-05 04:48:53',NULL);
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'view_encounter','View Encounters','Visits','View encounter workspaces.',1,'2026-08-05 04:16:38',NULL),(2,'create_encounter','Create Encounters','Visits','Create new encounters.',1,'2026-08-05 04:16:38',NULL),(3,'transfer_encounter','Transfer Encounters','Visits','Transfer encounters between departments.',1,'2026-08-05 04:16:38',NULL),(4,'receive_encounter','Receive Encounters','Visits','Receive transferred encounters.',1,'2026-08-05 04:16:38',NULL),(5,'assign_doctor','Assign Doctor','Visits','Assign a doctor to an encounter.',1,'2026-08-05 04:16:38',NULL),(6,'change_encounter_status','Change Encounter Status','Visits','Change encounter lifecycle status.',1,'2026-08-05 04:16:38',NULL),(7,'edit_encounter','Edit Encounters','Visits','Edit active encounter data.',1,'2026-08-05 04:16:38',NULL),(8,'manage_users','Manage Users','Administration','Create and administer user accounts.',1,'2026-08-05 04:16:38',NULL),(9,'manage_roles','Manage Roles','Administration','Create and administer roles.',1,'2026-08-05 04:16:38',NULL),(10,'manage_permissions','Manage Permissions','Administration','Assign and administer permissions.',1,'2026-08-05 04:16:38',NULL),(11,'manage_settings','Manage System Settings','Administration','View and administer enterprise system settings.',1,'2026-08-05 04:16:38',NULL),(12,'view_patient_identifiers','View Patient Identifiers','Medical Records','View authorized patient identifiers.',1,'2026-08-05 04:16:44',NULL),(13,'manage_patient_identifiers','Manage Patient Identifiers','Medical Records','Create, amend, deactivate, and select primary patient identifiers.',1,'2026-08-05 04:16:44',NULL),(14,'verify_patient_identifiers','Verify Patient Identifiers','Medical Records','Verify patient identifier evidence.',1,'2026-08-05 04:16:44',NULL),(15,'view_duplicate_candidates','View Duplicate Candidates','Medical Records','View possible duplicate patient cases.',1,'2026-08-05 04:16:44',NULL),(16,'review_duplicate_candidates','Review Duplicate Candidates','Medical Records','Record a controlled duplicate-case review decision.',1,'2026-08-05 04:16:44',NULL),(17,'view_medical_record','View Medical Records','Medical Records','View an authorized patient longitudinal chart.',1,'2026-08-05 04:48:44',NULL),(18,'edit_patient_demographics','Edit Patient Demographics','Medical Records','Correct patient demographics with versioned history.',1,'2026-08-05 04:48:44',NULL),(19,'view_patient_audit_history','View Patient Audit History','Medical Records','View patient-specific audit and demographic history.',1,'2026-08-05 04:48:44',NULL),(28,'view_clinical_safety','View Clinical Safety','Medical Records','View authorized longitudinal allergies and clinical alerts.',1,'2026-08-05 07:26:17',NULL),(29,'record_allergies','Record Allergies','Medical Records','Record structured patient allergy information.',1,'2026-08-05 07:26:17',NULL),(30,'update_allergies','Update Allergies','Medical Records','Correct active structured allergy information.',1,'2026-08-05 07:26:17',NULL),(31,'verify_allergies','Verify Allergies','Medical Records','Clinically verify recorded allergy information.',1,'2026-08-05 07:26:17',NULL),(32,'resolve_allergies','Resolve Allergies','Medical Records','Resolve or mark allergy records entered in error.',1,'2026-08-05 07:26:17',NULL),(33,'manage_clinical_alerts','Manage Clinical Alerts','Medical Records','Create, update, close, and reactivate clinical alerts.',1,'2026-08-05 07:26:17',NULL),(34,'view_confidential_alerts','View Confidential Alerts','Medical Records','View restricted and confidential clinical alert details.',1,'2026-08-05 07:26:17',NULL),(35,'view_clinical_safety_history','View Clinical Safety History','Medical Records','View allergy and clinical alert version history.',1,'2026-08-05 07:26:17',NULL);
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `record_access_logs`
--

LOCK TABLES `record_access_logs` WRITE;
/*!40000 ALTER TABLE `record_access_logs` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,11,6,NULL,'2026-08-05 04:16:38'),(2,11,7,NULL,'2026-08-05 04:16:38'),(3,11,4,NULL,'2026-08-05 04:16:38'),(4,11,3,NULL,'2026-08-05 04:16:38'),(5,11,1,NULL,'2026-08-05 04:16:38'),(6,4,6,NULL,'2026-08-05 04:16:38'),(7,4,7,NULL,'2026-08-05 04:16:38'),(8,4,4,NULL,'2026-08-05 04:16:38'),(9,4,3,NULL,'2026-08-05 04:16:38'),(10,4,1,NULL,'2026-08-05 04:16:38'),(11,6,6,NULL,'2026-08-05 04:16:38'),(12,6,7,NULL,'2026-08-05 04:16:38'),(13,6,4,NULL,'2026-08-05 04:16:38'),(14,6,3,NULL,'2026-08-05 04:16:38'),(15,6,1,NULL,'2026-08-05 04:16:38'),(16,5,6,NULL,'2026-08-05 04:16:38'),(17,5,7,NULL,'2026-08-05 04:16:38'),(18,5,4,NULL,'2026-08-05 04:16:38'),(19,5,3,NULL,'2026-08-05 04:16:38'),(20,5,1,NULL,'2026-08-05 04:16:38'),(21,7,6,NULL,'2026-08-05 04:16:38'),(22,7,7,NULL,'2026-08-05 04:16:38'),(23,7,4,NULL,'2026-08-05 04:16:38'),(24,7,3,NULL,'2026-08-05 04:16:38'),(25,7,1,NULL,'2026-08-05 04:16:38'),(26,8,6,NULL,'2026-08-05 04:16:38'),(27,8,7,NULL,'2026-08-05 04:16:38'),(28,8,4,NULL,'2026-08-05 04:16:38'),(29,8,3,NULL,'2026-08-05 04:16:38'),(30,8,1,NULL,'2026-08-05 04:16:38'),(31,9,6,NULL,'2026-08-05 04:16:38'),(32,9,7,NULL,'2026-08-05 04:16:38'),(33,9,4,NULL,'2026-08-05 04:16:38'),(34,9,3,NULL,'2026-08-05 04:16:38'),(35,9,1,NULL,'2026-08-05 04:16:38'),(36,2,6,NULL,'2026-08-05 04:16:38'),(37,2,7,NULL,'2026-08-05 04:16:38'),(38,2,4,NULL,'2026-08-05 04:16:38'),(39,2,3,NULL,'2026-08-05 04:16:38'),(40,2,1,NULL,'2026-08-05 04:16:38'),(41,3,6,NULL,'2026-08-05 04:16:38'),(42,3,7,NULL,'2026-08-05 04:16:38'),(43,3,4,NULL,'2026-08-05 04:16:38'),(44,3,3,NULL,'2026-08-05 04:16:38'),(45,3,1,NULL,'2026-08-05 04:16:38'),(46,12,6,NULL,'2026-08-05 04:16:38'),(47,12,7,NULL,'2026-08-05 04:16:38'),(48,12,4,NULL,'2026-08-05 04:16:38'),(49,12,3,NULL,'2026-08-05 04:16:38'),(50,12,1,NULL,'2026-08-05 04:16:38'),(51,10,6,NULL,'2026-08-05 04:16:38'),(52,10,7,NULL,'2026-08-05 04:16:38'),(53,10,4,NULL,'2026-08-05 04:16:38'),(54,10,3,NULL,'2026-08-05 04:16:38'),(55,10,1,NULL,'2026-08-05 04:16:38'),(64,2,2,NULL,'2026-08-05 04:16:38'),(65,4,5,NULL,'2026-08-05 04:16:38'),(66,3,13,NULL,'2026-08-05 04:16:44'),(67,3,16,NULL,'2026-08-05 04:16:44'),(68,3,14,NULL,'2026-08-05 04:16:44'),(69,3,15,NULL,'2026-08-05 04:16:44'),(70,3,12,NULL,'2026-08-05 04:16:44'),(73,4,12,NULL,'2026-08-05 04:16:44'),(74,5,12,NULL,'2026-08-05 04:16:44'),(75,2,12,NULL,'2026-08-05 04:16:44'),(76,2,13,NULL,'2026-08-05 04:16:44'),(77,2,15,NULL,'2026-08-05 04:16:44'),(79,3,18,NULL,'2026-08-05 04:48:44'),(80,3,17,NULL,'2026-08-05 04:48:44'),(81,3,19,NULL,'2026-08-05 04:48:44'),(86,4,17,NULL,'2026-08-05 04:48:44'),(87,5,17,NULL,'2026-08-05 04:48:44'),(88,2,17,NULL,'2026-08-05 04:48:44'),(93,2,18,NULL,'2026-08-05 04:48:44'),(98,4,28,NULL,'2026-08-05 07:26:17'),(99,6,28,NULL,'2026-08-05 07:26:17'),(100,5,28,NULL,'2026-08-05 07:26:17'),(101,7,28,NULL,'2026-08-05 07:26:17'),(102,8,28,NULL,'2026-08-05 07:26:17'),(103,9,28,NULL,'2026-08-05 07:26:17'),(104,2,28,NULL,'2026-08-05 07:26:17'),(105,3,28,NULL,'2026-08-05 07:26:17'),(106,10,28,NULL,'2026-08-05 07:26:17'),(113,4,33,NULL,'2026-08-05 07:26:17'),(114,5,33,NULL,'2026-08-05 07:26:17'),(115,4,29,NULL,'2026-08-05 07:26:17'),(116,5,29,NULL,'2026-08-05 07:26:17'),(117,4,30,NULL,'2026-08-05 07:26:17'),(118,5,30,NULL,'2026-08-05 07:26:17'),(119,4,31,NULL,'2026-08-05 07:26:17'),(120,5,31,NULL,'2026-08-05 07:26:17'),(121,4,35,NULL,'2026-08-05 07:26:17'),(122,5,35,NULL,'2026-08-05 07:26:17'),(128,4,32,NULL,'2026-08-05 07:26:17'),(129,4,34,NULL,'2026-08-05 07:26:17'),(131,3,35,NULL,'2026-08-05 07:26:17');
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
INSERT INTO `roles` VALUES (1,'System Administrator','Full system access',1,'2026-08-05 04:14:44',NULL),(2,'Receptionist','Patient registration',1,'2026-08-05 04:14:44',NULL),(3,'Records Officer','Medical records',1,'2026-08-05 04:14:44',NULL),(4,'Doctor','Medical consultation',1,'2026-08-05 04:14:44',NULL),(5,'Nurse','Nursing care',1,'2026-08-05 04:14:44',NULL),(6,'Laboratory Scientist','Laboratory investigations',1,'2026-08-05 04:14:44',NULL),(7,'Pharmacist','Medication dispensing',1,'2026-08-05 04:14:44',NULL),(8,'Physiotherapist','Physiotherapy',1,'2026-08-05 04:14:44',NULL),(9,'Radiographer','Radiology',1,'2026-08-05 04:14:44',NULL),(10,'Theatre Staff','Surgical procedures',1,'2026-08-05 04:14:44',NULL),(11,'Accountant','Billing and payments',1,'2026-08-05 04:14:44',NULL),(12,'Store Officer','Medical store',1,'2026-08-05 04:14:44',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES (1,'002_phase0_live_schema_alignment_up.sql','afb299ef87273849f708fd779392b6e2fc4bde05163392d75e556b1b9c10c940',1,'2026-08-05 05:48:44',0),(2,'003_phase0_queue_workflow_up.sql','b3e0bcd385055481bf2358800368b75e511c4d672fc004915436bfb3fdc785a8',1,'2026-08-05 05:48:44',0),(3,'004_phase0_store_status_up.sql','0a89f9334cbed1ace3e22054232b8cab6262f23890aa7ffd7c08716622d5173e',1,'2026-08-05 05:48:44',0),(4,'005_phase1_user_management_up.sql','c423cb5735c133e7929257b48f4496efd34ecbd33ce3240aa48e3a4108b7c4f2',1,'2026-08-05 05:48:44',137),(5,'006_phase1_roles_permissions_up.sql','17eea7657af655180ec50192bbbe8e47960bd15b694470dcaa2f21bfade4d49d',1,'2026-08-05 05:48:44',0),(6,'007_phase1_departments_assignments_up.sql','8e5404e85e7f7087758495876cb532783d34be43dc2b65a1090abb7b786842d9',1,'2026-08-05 05:48:44',250),(7,'008_phase1_security_administration_up.sql','c65b67d515dd07f682d02f17be46e4bf6c58f493396e356d0ac70307779605b9',1,'2026-08-05 05:48:44',0),(8,'009_phase1_system_settings_up.sql','fb478905413737bad5cd9407f8e06d61d8f451fa97ea34c56a6dbe1aac24f859',1,'2026-08-05 05:48:44',0),(9,'010_phase1_production_indexes_up.sql','1dfbf3d4c3873bc39f597fdccfbb306a121ff4b8409dbab1ecdfbfca7c3b17a0',1,'2026-08-05 05:48:44',43),(10,'011_phase1_visit_status_repair_up.sql','7ff2ceeda4762d4b545ad74b3d1fff3338f49547f886398d532499ad3215d222',1,'2026-08-05 05:48:44',16),(11,'012_phase1_patient_gender_remediation_up.sql','cd99efb45b6a16a0ce1e4eda86e6547516bde69de388263f8b39f0a476b955d3',1,'2026-08-05 05:48:44',29),(12,'013_phase2_medical_records_foundation_up.sql','e0fb83803488af9c4fe642208758406ea795e4221cfa052b7f64ed394a28a680',1,'2026-08-05 05:48:44',0),(13,'014_phase2_mpi_identifiers_up.sql','d92e560b01fc3b6b574bda0610a98acb9ae1e4fdae96fb78733f56ba5db635c0',1,'2026-08-05 05:48:44',0),(14,'015_recovery_safety_and_seed_reconciliation_up.sql','ddcfeb365553071b7fd3359cb3ebfde82257183cdeacf52ceba529833c675cd0',2,'2026-08-05 05:48:44',23),(15,'016_phase2_patient_identifiers_mpi_up.sql','ec89afedb6e88421c3af35061aeae459983908a51ce086b72b1b98458f2971cb',1,'2026-08-05 06:43:18',23),(16,'017_phase2_clinical_safety_up.sql','e43377009f238491350268ad5f8449e61a4f6357ae0be2ca42d8be87120cd1c6',1,'2026-08-05 08:26:17',286),(17,'018_phase2_clinical_safety_hardening_up.sql','aa862a60e76b335d9413a5100f89bcbdcbd15480085e508788a6652d593f942a',3,'2026-08-05 12:17:18',30);
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'hospital.name','Hospital Management System','string','Hospital','Official hospital name.','Hospital Management System','{\"required\":true,\"min_length\":2,\"max_length\":150}',1,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(2,'hospital.code','HMS','string','Hospital','Short hospital code.','HMS','{\"required\":true,\"regex\":\"^[A-Za-z0-9_-]{2,20}$\"}',1,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(3,'hospital.logo','','string','Hospital','Relative or absolute hospital logo path.','','{\"max_length\":255}',1,1,1,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(4,'hospital.address','','string','Hospital','Hospital postal address.','','{\"max_length\":500}',1,1,0,0,0,40,NULL,NULL,'2026-08-05 04:16:38',NULL),(5,'hospital.contact_phone','','string','Hospital','Main hospital contact number.','','{\"max_length\":50}',1,1,0,0,0,50,NULL,NULL,'2026-08-05 04:16:38',NULL),(6,'hospital.website','','string','Hospital','Official hospital website.','','{\"max_length\":255}',1,1,0,0,0,60,NULL,NULL,'2026-08-05 04:16:38',NULL),(7,'hospital.email','','string','Hospital','Official hospital email address.','','{\"max_length\":150,\"format\":\"email\"}',1,1,0,0,0,70,NULL,NULL,'2026-08-05 04:16:38',NULL),(8,'general.timezone','Africa/Lagos','string','General','Application timezone.','Africa/Lagos','{\"required\":true,\"format\":\"timezone\"}',1,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(9,'general.date_format','d M Y','string','General','PHP date display format.','d M Y','{\"required\":true,\"max_length\":30}',1,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(10,'general.time_format','H:i','string','General','PHP time display format.','H:i','{\"required\":true,\"max_length\":30}',1,1,1,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(11,'general.currency','NGN','string','General','Default ISO currency code.','NGN','{\"required\":true,\"regex\":\"^[A-Z]{3}$\"}',1,1,1,0,0,40,NULL,NULL,'2026-08-05 04:16:38',NULL),(12,'general.language','en','string','General','Default application language.','en','{\"required\":true,\"allowed\":[\"en\"]}',1,1,1,0,0,50,NULL,NULL,'2026-08-05 04:16:38',NULL),(13,'security.session_timeout_minutes','30','integer','Security','Idle session timeout in minutes.','30','{\"required\":true,\"min\":5,\"max\":1440}',0,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(14,'security.password_min_length','8','integer','Security','Minimum user password length.','8','{\"required\":true,\"min\":8,\"max\":128}',0,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(15,'security.password_complexity','basic','string','Security','Password complexity policy.','basic','{\"required\":true,\"allowed\":[\"basic\",\"standard\",\"strong\"]}',0,1,1,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(16,'security.lockout_threshold','5','integer','Security','Failed login attempts before account lockout.','5','{\"required\":true,\"min\":1,\"max\":20}',0,1,1,0,0,40,NULL,NULL,'2026-08-05 04:16:38',NULL),(17,'security.password_expiry_days','0','integer','Security','Password expiry interval; zero disables expiry.','0','{\"required\":true,\"min\":0,\"max\":3650}',0,1,1,0,0,50,NULL,NULL,'2026-08-05 04:16:38',NULL),(18,'security.two_factor_enabled','0','boolean','Security','Reserved two-factor authentication switch.','0','{\"required\":true}',0,0,1,0,0,60,NULL,NULL,'2026-08-05 04:16:38',NULL),(19,'encounters.number_format','ENC-{YEAR}-{ID:6}','string','Encounters','Encounter number formatting template.','ENC-{YEAR}-{ID:6}','{\"required\":true,\"max_length\":100}',0,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(20,'encounters.default_department_id','','integer','Encounters','Optional default encounter department ID.','','{\"min\":1}',0,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(21,'encounters.queue_rules','[]','array','Encounters','Encounter queue rule overrides.','[]','{\"required\":true}',0,1,0,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(22,'queue.auto_queue','1','boolean','Queue','Automatically enqueue eligible encounters.','1','{\"required\":true}',0,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(23,'queue.prefix','Q','string','Queue','Default queue number prefix.','Q','{\"required\":true,\"max_length\":20}',1,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(24,'queue.reset_rule','daily','string','Queue','Queue numbering reset frequency.','daily','{\"required\":true,\"allowed\":[\"never\",\"daily\",\"weekly\",\"monthly\"]}',0,1,0,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(25,'notifications.email_enabled','0','boolean','Notifications','Enable email notifications.','0','{\"required\":true}',0,1,0,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(26,'notifications.sms_enabled','0','boolean','Notifications','Enable SMS notifications.','0','{\"required\":true}',0,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(27,'notifications.internal_enabled','1','boolean','Notifications','Enable internal application notifications.','1','{\"required\":true}',0,1,0,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(28,'reporting.default_date_range_days','30','integer','Reporting','Default reporting date range in days.','30','{\"required\":true,\"min\":1,\"max\":366}',0,1,0,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(29,'reporting.export_limit','10000','integer','Reporting','Maximum rows in one report export.','10000','{\"required\":true,\"min\":100,\"max\":1000000}',0,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(30,'backup.frequency','daily','string','Backup','Requested backup frequency.','daily','{\"required\":true,\"allowed\":[\"manual\",\"daily\",\"weekly\",\"monthly\"]}',0,1,0,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(31,'backup.retention_days','30','integer','Backup','Requested backup retention in days.','30','{\"required\":true,\"min\":1,\"max\":3650}',0,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(32,'system.maintenance_mode','0','boolean','System','Application maintenance mode switch.','0','{\"required\":true}',1,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(33,'system.debug_mode','0','boolean','System','Application diagnostic mode switch.','0','{\"required\":true}',0,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(34,'system.version','1.0.0','string','System','Displayed application version.','1.0.0','{\"required\":true,\"regex\":\"^[0-9]+\\.[0-9]+\\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$\"}',1,0,1,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(35,'mpi.enabled_identifier_types','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','array','Medical Records','Enabled alternate patient identifier types.','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','{\"required\":true}',0,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:44',NULL),(36,'mpi.global_unique_types','[\"National Identification Number\",\"Passport Number\"]','array','Medical Records','Identifier types unique across the hospital.','[\"National Identification Number\",\"Passport Number\"]','{}',0,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:44',NULL),(37,'mpi.authority_unique_types','[\"Insurance Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','array','Medical Records','Identifier types unique within an issuing authority.','[\"Insurance Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','{}',0,1,1,0,0,30,NULL,NULL,'2026-08-05 04:16:44',NULL),(38,'mpi.exact_match_threshold','100','integer','Medical Records','Exact duplicate score threshold.','100','{\"min\":90,\"max\":100}',0,1,1,0,0,40,NULL,NULL,'2026-08-05 04:16:44',NULL),(39,'mpi.strong_match_threshold','80','integer','Medical Records','Strong possible duplicate score threshold.','80','{\"min\":60,\"max\":99}',0,1,1,0,0,50,NULL,NULL,'2026-08-05 04:16:44',NULL),(40,'mpi.possible_match_threshold','55','integer','Medical Records','Possible duplicate score threshold.','55','{\"min\":30,\"max\":89}',0,1,1,0,0,60,NULL,NULL,'2026-08-05 04:16:44',NULL),(41,'mpi.search_page_size','25','integer','Medical Records','Default MPI search page size.','25','{\"min\":10,\"max\":100}',0,1,1,0,0,70,NULL,NULL,'2026-08-05 04:16:44',NULL),(42,'mpi.mask_identifier_types','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\"]','array','Medical Records','Identifier types masked in ordinary displays.','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\"]','{}',0,1,1,0,0,80,NULL,NULL,'2026-08-05 04:16:44',NULL),(51,'mpi.identifier_definitions','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','array','Medical Records','Approved alternate patient identifier definitions.','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','{\"required\":true}',0,1,1,0,0,90,NULL,NULL,'2026-08-05 05:43:18',NULL),(52,'mpi.duplicate_threshold','55','integer','Medical Records','Minimum score that creates a duplicate review warning.','55','{\"min\":1,\"max\":100}',0,1,1,0,0,100,NULL,NULL,'2026-08-05 05:43:18',NULL),(53,'mpi.fuzzy_search_threshold','70','integer','Medical Records','Minimum bounded fuzzy-name similarity percentage.','70','{\"min\":50,\"max\":100}',0,1,1,0,0,110,NULL,NULL,'2026-08-05 05:43:18',NULL),(54,'mpi.exact_match_priority','true','boolean','Medical Records','Rank exact identifiers before prefix and bounded fuzzy results.','true','{}',0,1,1,0,0,120,NULL,NULL,'2026-08-05 05:43:18',NULL),(55,'clinical_safety.allergy_types','[\"Drug\",\"Food\",\"Environmental\",\"Biological\",\"Other\"]','array','Medical Records','Allowed structured allergy types.','[\"Drug\",\"Food\",\"Environmental\",\"Biological\",\"Other\"]','{\"required\":true,\"schema_values\":[\"Drug\",\"Food\",\"Environmental\",\"Biological\",\"Other\"]}',0,1,1,0,0,200,NULL,NULL,'2026-08-05 07:26:17','2026-08-05 11:17:18'),(56,'clinical_safety.severity_values','[\"Mild\",\"Moderate\",\"Severe\",\"Life-threatening\",\"Unknown\"]','array','Medical Records','Allowed allergy severity values.','[\"Mild\",\"Moderate\",\"Severe\",\"Life-threatening\",\"Unknown\"]','{\"required\":true,\"schema_values\":[\"Mild\",\"Moderate\",\"Severe\",\"Life-threatening\",\"Unknown\"]}',0,1,1,0,0,210,NULL,NULL,'2026-08-05 07:26:17','2026-08-05 11:17:18'),(57,'clinical_safety.nurse_may_verify_allergies','false','boolean','Medical Records','Whether nurses with permission may confirm allergies.','false','{}',0,1,1,0,0,220,NULL,NULL,'2026-08-05 07:26:17',NULL),(58,'clinical_safety.alert_types','[\"Clinical Risk\",\"Infection Control\",\"Fall Risk\",\"Communication Need\",\"Safeguarding\",\"Special Handling\",\"Other\"]','array','Medical Records','Allowed clinical alert types.','[\"Clinical Risk\",\"Infection Control\",\"Fall Risk\",\"Communication Need\",\"Safeguarding\",\"Special Handling\",\"Other\"]','{\"required\":true,\"schema_values\":[\"Clinical Risk\",\"Infection Control\",\"Fall Risk\",\"Communication Need\",\"Safeguarding\",\"Special Handling\",\"Other\"]}',0,1,1,0,0,230,NULL,NULL,'2026-08-05 07:26:17','2026-08-05 11:17:18'),(59,'clinical_safety.alert_priorities','[\"Low\",\"Medium\",\"High\",\"Critical\"]','array','Medical Records','Allowed clinical alert priorities.','[\"Low\",\"Medium\",\"High\",\"Critical\"]','{\"required\":true,\"schema_values\":[\"Low\",\"Medium\",\"High\",\"Critical\"]}',0,1,1,0,0,240,NULL,NULL,'2026-08-05 07:26:17','2026-08-05 11:17:18'),(60,'clinical_safety.confidentiality_levels','[\"Standard\",\"Restricted\",\"Confidential\"]','array','Medical Records','Allowed alert confidentiality levels.','[\"Standard\",\"Restricted\",\"Confidential\"]','{\"required\":true,\"schema_values\":[\"Standard\",\"Restricted\",\"Confidential\"]}',0,1,1,0,0,250,NULL,NULL,'2026-08-05 07:26:17','2026-08-05 11:17:18'),(61,'clinical_safety.default_alert_expiry_days','0','integer','Medical Records','Default alert lifetime in days; zero means none.','0','{\"min\":0,\"max\":3650}',0,1,1,0,0,260,NULL,NULL,'2026-08-05 07:26:17',NULL),(62,'clinical_safety.legacy_allergy_warning','true','boolean','Medical Records','Display legacy allergy text as an unverified warning.','true','{}',0,1,1,0,0,270,NULL,NULL,'2026-08-05 07:26:17',NULL),(63,'clinical_safety.allow_self_allergy_verification','false','boolean','Medical Records','Whether an allergy author may verify their own unverified allergy. Disabled by default.','false','{}',0,1,1,0,0,225,NULL,NULL,'2026-08-05 11:17:18',NULL);
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_departments`
--

LOCK TABLES `user_departments` WRITE;
/*!40000 ALTER TABLE `user_departments` DISABLE KEYS */;
INSERT INTO `user_departments` VALUES (1,1,1,1,1,'2026-08-05 05:48:44',NULL),(2,2,2,1,1,'2026-08-05 05:48:53',1),(3,3,3,1,1,'2026-08-05 05:48:53',1),(4,4,5,1,1,'2026-08-05 05:48:53',1),(5,5,4,1,1,'2026-08-05 05:48:53',1),(6,6,6,1,1,'2026-08-05 05:48:53',1),(7,7,9,1,1,'2026-08-05 05:48:53',1),(8,8,7,1,1,'2026-08-05 05:48:53',1),(9,9,11,1,1,'2026-08-05 05:48:53',1);
/*!40000 ALTER TABLE `user_departments` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'EMP000001','System','Administrator','Male',NULL,'admin@hospital.local','admin','$2y$10$FnIJ0nHOUgqzpWyhXO7x1.XvdUf.DJycv3waqFVpfyb41YceVm77O',1,1,'Active',2,'2026-08-05 12:19:50',NULL,NULL,NULL,'2026-08-05 11:07:58','2026-08-05 11:09:20',0,'2026-08-05 04:14:45','2026-08-05 11:19:50'),(2,'DEV-REC-001','Development','Receptionist','Female',NULL,'dev_reception@development.invalid','dev_reception','$2y$10$0N9.CoF6tbyufpRsTnjljeMxOPrWT1ZvOkXMTd7u4rnYoAgJCRBrG',2,2,'Active',1,'2026-08-05 11:01:50',NULL,NULL,NULL,'2026-08-05 06:20:39','2026-08-05 11:06:49',1,'2026-08-05 04:48:53','2026-08-05 10:06:49'),(3,'DEV-REC-002','Development','Records','Female',NULL,'dev_records@development.invalid','dev_records','$2y$10$cvPuYgLrN5MpMSWmK0StROsHuufbW4K.UY5S8RlieIaN2tnuGrI/G',3,3,'Active',1,'2026-08-05 11:02:07',NULL,NULL,NULL,NULL,'2026-08-05 11:06:49',1,'2026-08-05 04:48:53','2026-08-05 10:06:49'),(4,'DEV-NUR-001','Development','Nurse','Female',NULL,'dev_nurse@development.invalid','dev_nurse','$2y$10$7G43TFFmJCIxCONxCpqO1OdE7xi72F1qAISD9U8bSXCmzQ4Atq/m.',5,5,'Active',0,NULL,NULL,NULL,NULL,NULL,'2026-08-05 11:06:49',1,'2026-08-05 04:48:53','2026-08-05 10:06:49'),(5,'DEV-DOC-001','Amara','Okafor','Female',NULL,'dev_doctor@development.invalid','dev_doctor','$2y$10$LjToFV7TjF0879NvOBAu.OiG.G9ibl.GDUry5ERFvpM9V81YPEF5K',4,4,'Active',0,NULL,NULL,NULL,NULL,'2026-08-05 06:20:39','2026-08-05 11:06:49',1,'2026-08-05 04:48:53','2026-08-05 10:06:49'),(6,'DEV-LAB-001','Development','Laboratory','Female',NULL,'dev_laboratory@development.invalid','dev_laboratory','$2y$10$FLNnaBMCiIJxTwrBVOH3x.oQum9oKpZmJz07B3PwBP8/w/o235dde',6,6,'Active',0,NULL,NULL,NULL,NULL,NULL,'2026-08-05 11:06:49',1,'2026-08-05 04:48:53','2026-08-05 10:06:49'),(7,'DEV-RAD-001','Development','Radiology','Female',NULL,'dev_radiology@development.invalid','dev_radiology','$2y$10$.0K2ob5.cHdErVhFQa.zQeu2bK2qb/CsY.MTcoeMX8mMWY.4WxSHK',9,9,'Active',0,NULL,NULL,NULL,NULL,NULL,'2026-08-05 11:06:49',1,'2026-08-05 04:48:53','2026-08-05 10:06:49'),(8,'DEV-PHA-001','Development','Pharmacy','Female',NULL,'dev_pharmacy@development.invalid','dev_pharmacy','$2y$10$IlneNxk5MFGH63k1mIM5KekwGmBDrlzAhDhiJClk9lsTtfwfkK7xC',7,7,'Active',0,NULL,NULL,NULL,NULL,NULL,'2026-08-05 11:06:49',1,'2026-08-05 04:48:53','2026-08-05 10:06:49'),(9,'DEV-ACC-001','Development','Accounts','Female',NULL,'dev_accounts@development.invalid','dev_accounts','$2y$10$a5huFZvsY2vist.JpN2Q7./uxCFAQxat5f8NRYl7rsRXpb7Da4KJS',11,11,'Active',0,NULL,NULL,NULL,NULL,NULL,'2026-08-05 11:06:50',1,'2026-08-05 04:48:53','2026-08-05 10:06:50');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visit_queue`
--

LOCK TABLES `visit_queue` WRITE;
/*!40000 ALTER TABLE `visit_queue` DISABLE KEYS */;
INSERT INTO `visit_queue` VALUES (1,1,2,1,1,NULL,'Completed','2026-08-05 04:56:02','2026-08-05 05:56:02','2026-08-05 05:56:02','2026-08-05 05:56:02',NULL),(2,1,4,NULL,1,'Queue closed because the encounter was closed with status Completed.','Cancelled','2026-08-05 04:56:02',NULL,NULL,NULL,'2026-08-05 05:56:02');
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
  CONSTRAINT `fk_visits_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_visits_department` FOREIGN KEY (`current_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_visits_doctor` FOREIGN KEY (`attending_doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_visits_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_visits_received_by` FOREIGN KEY (`current_department_received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visits`
--

LOCK TABLES `visits` WRITE;
/*!40000 ALTER TABLE `visits` DISABLE KEYS */;
INSERT INTO `visits` VALUES (1,'VIS-2026-000001',2,'2026-08-05 06:56:02','Outpatient',4,5,NULL,'Received',1,'2026-08-05 05:56:02','Completed',1,'2026-08-05 04:56:02','2026-08-05 04:56:02');
/*!40000 ALTER TABLE `visits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'hospital_management_system'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-05 14:10:06
