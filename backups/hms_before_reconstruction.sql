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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `active_sessions`
--

LOCK TABLES `active_sessions` WRITE;
/*!40000 ALTER TABLE `active_sessions` DISABLE KEYS */;
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
  CONSTRAINT `fk_audit_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_audit_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_audit_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
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
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_departments_name` (`department_name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Administrator','System administration','2026-08-05 04:14:44',NULL),(2,'Reception','Patient reception','2026-08-05 04:14:44',NULL),(3,'Records','Medical records','2026-08-05 04:14:44',NULL),(4,'Doctor','Medical consultation','2026-08-05 04:14:44',NULL),(5,'Nursing','Nursing services','2026-08-05 04:14:44',NULL),(6,'Laboratory','Laboratory investigations','2026-08-05 04:14:44',NULL),(7,'Pharmacy','Drug dispensing','2026-08-05 04:14:44',NULL),(8,'Physiotherapy','Physiotherapy services','2026-08-05 04:14:44',NULL),(9,'X-Ray','Radiology and imaging','2026-08-05 04:14:44',NULL),(10,'Theatre','Surgical theatre','2026-08-05 04:14:44',NULL),(11,'Accounts','Billing and payments','2026-08-05 04:14:44',NULL),(12,'Store','Medical store','2026-08-05 04:14:44',NULL);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_events`
--

LOCK TABLES `encounter_events` WRITE;
/*!40000 ALTER TABLE `encounter_events` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_history`
--

LOCK TABLES `password_history` WRITE;
/*!40000 ALTER TABLE `password_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_history` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'view_encounter','View Encounters','Visits','View encounter workspaces.',1,'2026-08-05 04:16:38',NULL),(2,'create_encounter','Create Encounters','Visits','Create new encounters.',1,'2026-08-05 04:16:38',NULL),(3,'transfer_encounter','Transfer Encounters','Visits','Transfer encounters between departments.',1,'2026-08-05 04:16:38',NULL),(4,'receive_encounter','Receive Encounters','Visits','Receive transferred encounters.',1,'2026-08-05 04:16:38',NULL),(5,'assign_doctor','Assign Doctor','Visits','Assign a doctor to an encounter.',1,'2026-08-05 04:16:38',NULL),(6,'change_encounter_status','Change Encounter Status','Visits','Change encounter lifecycle status.',1,'2026-08-05 04:16:38',NULL),(7,'edit_encounter','Edit Encounters','Visits','Edit active encounter data.',1,'2026-08-05 04:16:38',NULL),(8,'manage_users','Manage Users','Administration','Create and administer user accounts.',1,'2026-08-05 04:16:38',NULL),(9,'manage_roles','Manage Roles','Administration','Create and administer roles.',1,'2026-08-05 04:16:38',NULL),(10,'manage_permissions','Manage Permissions','Administration','Assign and administer permissions.',1,'2026-08-05 04:16:38',NULL),(11,'manage_settings','Manage System Settings','Administration','View and administer enterprise system settings.',1,'2026-08-05 04:16:38',NULL),(12,'view_patient_identifiers','View Patient Identifiers','Medical Records','View authorized patient identifiers.',1,'2026-08-05 04:16:44',NULL),(13,'manage_patient_identifiers','Manage Patient Identifiers','Medical Records','Create, amend, deactivate, and select primary patient identifiers.',1,'2026-08-05 04:16:44',NULL),(14,'verify_patient_identifiers','Verify Patient Identifiers','Medical Records','Verify patient identifier evidence.',1,'2026-08-05 04:16:44',NULL),(15,'view_duplicate_candidates','View Duplicate Candidates','Medical Records','View possible duplicate patient cases.',1,'2026-08-05 04:16:44',NULL),(16,'review_duplicate_candidates','Review Duplicate Candidates','Medical Records','Record a controlled duplicate-case review decision.',1,'2026-08-05 04:16:44',NULL);
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,11,6,NULL,'2026-08-05 04:16:38'),(2,11,7,NULL,'2026-08-05 04:16:38'),(3,11,4,NULL,'2026-08-05 04:16:38'),(4,11,3,NULL,'2026-08-05 04:16:38'),(5,11,1,NULL,'2026-08-05 04:16:38'),(6,4,6,NULL,'2026-08-05 04:16:38'),(7,4,7,NULL,'2026-08-05 04:16:38'),(8,4,4,NULL,'2026-08-05 04:16:38'),(9,4,3,NULL,'2026-08-05 04:16:38'),(10,4,1,NULL,'2026-08-05 04:16:38'),(11,6,6,NULL,'2026-08-05 04:16:38'),(12,6,7,NULL,'2026-08-05 04:16:38'),(13,6,4,NULL,'2026-08-05 04:16:38'),(14,6,3,NULL,'2026-08-05 04:16:38'),(15,6,1,NULL,'2026-08-05 04:16:38'),(16,5,6,NULL,'2026-08-05 04:16:38'),(17,5,7,NULL,'2026-08-05 04:16:38'),(18,5,4,NULL,'2026-08-05 04:16:38'),(19,5,3,NULL,'2026-08-05 04:16:38'),(20,5,1,NULL,'2026-08-05 04:16:38'),(21,7,6,NULL,'2026-08-05 04:16:38'),(22,7,7,NULL,'2026-08-05 04:16:38'),(23,7,4,NULL,'2026-08-05 04:16:38'),(24,7,3,NULL,'2026-08-05 04:16:38'),(25,7,1,NULL,'2026-08-05 04:16:38'),(26,8,6,NULL,'2026-08-05 04:16:38'),(27,8,7,NULL,'2026-08-05 04:16:38'),(28,8,4,NULL,'2026-08-05 04:16:38'),(29,8,3,NULL,'2026-08-05 04:16:38'),(30,8,1,NULL,'2026-08-05 04:16:38'),(31,9,6,NULL,'2026-08-05 04:16:38'),(32,9,7,NULL,'2026-08-05 04:16:38'),(33,9,4,NULL,'2026-08-05 04:16:38'),(34,9,3,NULL,'2026-08-05 04:16:38'),(35,9,1,NULL,'2026-08-05 04:16:38'),(36,2,6,NULL,'2026-08-05 04:16:38'),(37,2,7,NULL,'2026-08-05 04:16:38'),(38,2,4,NULL,'2026-08-05 04:16:38'),(39,2,3,NULL,'2026-08-05 04:16:38'),(40,2,1,NULL,'2026-08-05 04:16:38'),(41,3,6,NULL,'2026-08-05 04:16:38'),(42,3,7,NULL,'2026-08-05 04:16:38'),(43,3,4,NULL,'2026-08-05 04:16:38'),(44,3,3,NULL,'2026-08-05 04:16:38'),(45,3,1,NULL,'2026-08-05 04:16:38'),(46,12,6,NULL,'2026-08-05 04:16:38'),(47,12,7,NULL,'2026-08-05 04:16:38'),(48,12,4,NULL,'2026-08-05 04:16:38'),(49,12,3,NULL,'2026-08-05 04:16:38'),(50,12,1,NULL,'2026-08-05 04:16:38'),(51,10,6,NULL,'2026-08-05 04:16:38'),(52,10,7,NULL,'2026-08-05 04:16:38'),(53,10,4,NULL,'2026-08-05 04:16:38'),(54,10,3,NULL,'2026-08-05 04:16:38'),(55,10,1,NULL,'2026-08-05 04:16:38'),(64,2,2,NULL,'2026-08-05 04:16:38'),(65,4,5,NULL,'2026-08-05 04:16:38'),(66,3,13,NULL,'2026-08-05 04:16:44'),(67,3,16,NULL,'2026-08-05 04:16:44'),(68,3,14,NULL,'2026-08-05 04:16:44'),(69,3,15,NULL,'2026-08-05 04:16:44'),(70,3,12,NULL,'2026-08-05 04:16:44'),(73,4,12,NULL,'2026-08-05 04:16:44'),(74,5,12,NULL,'2026-08-05 04:16:44'),(75,2,12,NULL,'2026-08-05 04:16:44'),(76,2,13,NULL,'2026-08-05 04:16:44'),(77,2,15,NULL,'2026-08-05 04:16:44');
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
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'hospital.name','Hospital Management System','string','Hospital','Official hospital name.','Hospital Management System','{\"required\":true,\"min_length\":2,\"max_length\":150}',1,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(2,'hospital.code','HMS','string','Hospital','Short hospital code.','HMS','{\"required\":true,\"regex\":\"^[A-Za-z0-9_-]{2,20}$\"}',1,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(3,'hospital.logo','','string','Hospital','Relative or absolute hospital logo path.','','{\"max_length\":255}',1,1,1,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(4,'hospital.address','','string','Hospital','Hospital postal address.','','{\"max_length\":500}',1,1,0,0,0,40,NULL,NULL,'2026-08-05 04:16:38',NULL),(5,'hospital.contact_phone','','string','Hospital','Main hospital contact number.','','{\"max_length\":50}',1,1,0,0,0,50,NULL,NULL,'2026-08-05 04:16:38',NULL),(6,'hospital.website','','string','Hospital','Official hospital website.','','{\"max_length\":255}',1,1,0,0,0,60,NULL,NULL,'2026-08-05 04:16:38',NULL),(7,'hospital.email','','string','Hospital','Official hospital email address.','','{\"max_length\":150,\"format\":\"email\"}',1,1,0,0,0,70,NULL,NULL,'2026-08-05 04:16:38',NULL),(8,'general.timezone','Africa/Lagos','string','General','Application timezone.','Africa/Lagos','{\"required\":true,\"format\":\"timezone\"}',1,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(9,'general.date_format','d M Y','string','General','PHP date display format.','d M Y','{\"required\":true,\"max_length\":30}',1,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(10,'general.time_format','H:i','string','General','PHP time display format.','H:i','{\"required\":true,\"max_length\":30}',1,1,1,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(11,'general.currency','NGN','string','General','Default ISO currency code.','NGN','{\"required\":true,\"regex\":\"^[A-Z]{3}$\"}',1,1,1,0,0,40,NULL,NULL,'2026-08-05 04:16:38',NULL),(12,'general.language','en','string','General','Default application language.','en','{\"required\":true,\"allowed\":[\"en\"]}',1,1,1,0,0,50,NULL,NULL,'2026-08-05 04:16:38',NULL),(13,'security.session_timeout_minutes','30','integer','Security','Idle session timeout in minutes.','30','{\"required\":true,\"min\":5,\"max\":1440}',0,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(14,'security.password_min_length','8','integer','Security','Minimum user password length.','8','{\"required\":true,\"min\":8,\"max\":128}',0,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(15,'security.password_complexity','basic','string','Security','Password complexity policy.','basic','{\"required\":true,\"allowed\":[\"basic\",\"standard\",\"strong\"]}',0,1,1,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(16,'security.lockout_threshold','5','integer','Security','Failed login attempts before account lockout.','5','{\"required\":true,\"min\":1,\"max\":20}',0,1,1,0,0,40,NULL,NULL,'2026-08-05 04:16:38',NULL),(17,'security.password_expiry_days','0','integer','Security','Password expiry interval; zero disables expiry.','0','{\"required\":true,\"min\":0,\"max\":3650}',0,1,1,0,0,50,NULL,NULL,'2026-08-05 04:16:38',NULL),(18,'security.two_factor_enabled','0','boolean','Security','Reserved two-factor authentication switch.','0','{\"required\":true}',0,0,1,0,0,60,NULL,NULL,'2026-08-05 04:16:38',NULL),(19,'encounters.number_format','ENC-{YEAR}-{ID:6}','string','Encounters','Encounter number formatting template.','ENC-{YEAR}-{ID:6}','{\"required\":true,\"max_length\":100}',0,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(20,'encounters.default_department_id','','integer','Encounters','Optional default encounter department ID.','','{\"min\":1}',0,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(21,'encounters.queue_rules','[]','array','Encounters','Encounter queue rule overrides.','[]','{\"required\":true}',0,1,0,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(22,'queue.auto_queue','1','boolean','Queue','Automatically enqueue eligible encounters.','1','{\"required\":true}',0,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(23,'queue.prefix','Q','string','Queue','Default queue number prefix.','Q','{\"required\":true,\"max_length\":20}',1,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(24,'queue.reset_rule','daily','string','Queue','Queue numbering reset frequency.','daily','{\"required\":true,\"allowed\":[\"never\",\"daily\",\"weekly\",\"monthly\"]}',0,1,0,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(25,'notifications.email_enabled','0','boolean','Notifications','Enable email notifications.','0','{\"required\":true}',0,1,0,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(26,'notifications.sms_enabled','0','boolean','Notifications','Enable SMS notifications.','0','{\"required\":true}',0,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(27,'notifications.internal_enabled','1','boolean','Notifications','Enable internal application notifications.','1','{\"required\":true}',0,1,0,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(28,'reporting.default_date_range_days','30','integer','Reporting','Default reporting date range in days.','30','{\"required\":true,\"min\":1,\"max\":366}',0,1,0,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(29,'reporting.export_limit','10000','integer','Reporting','Maximum rows in one report export.','10000','{\"required\":true,\"min\":100,\"max\":1000000}',0,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(30,'backup.frequency','daily','string','Backup','Requested backup frequency.','daily','{\"required\":true,\"allowed\":[\"manual\",\"daily\",\"weekly\",\"monthly\"]}',0,1,0,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(31,'backup.retention_days','30','integer','Backup','Requested backup retention in days.','30','{\"required\":true,\"min\":1,\"max\":3650}',0,1,0,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(32,'system.maintenance_mode','0','boolean','System','Application maintenance mode switch.','0','{\"required\":true}',1,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:38',NULL),(33,'system.debug_mode','0','boolean','System','Application diagnostic mode switch.','0','{\"required\":true}',0,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:38',NULL),(34,'system.version','1.0.0','string','System','Displayed application version.','1.0.0','{\"required\":true,\"regex\":\"^[0-9]+\\.[0-9]+\\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$\"}',1,0,1,0,0,30,NULL,NULL,'2026-08-05 04:16:38',NULL),(35,'mpi.enabled_identifier_types','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','array','Medical Records','Enabled alternate patient identifier types.','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','{\"required\":true}',0,1,1,0,0,10,NULL,NULL,'2026-08-05 04:16:44',NULL),(36,'mpi.global_unique_types','[\"National Identification Number\",\"Passport Number\"]','array','Medical Records','Identifier types unique across the hospital.','[\"National Identification Number\",\"Passport Number\"]','{}',0,1,1,0,0,20,NULL,NULL,'2026-08-05 04:16:44',NULL),(37,'mpi.authority_unique_types','[\"Insurance Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','array','Medical Records','Identifier types unique within an issuing authority.','[\"Insurance Number\",\"External Hospital Number\",\"Legacy Medical Record Number\"]','{}',0,1,1,0,0,30,NULL,NULL,'2026-08-05 04:16:44',NULL),(38,'mpi.exact_match_threshold','100','integer','Medical Records','Exact duplicate score threshold.','100','{\"min\":90,\"max\":100}',0,1,1,0,0,40,NULL,NULL,'2026-08-05 04:16:44',NULL),(39,'mpi.strong_match_threshold','80','integer','Medical Records','Strong possible duplicate score threshold.','80','{\"min\":60,\"max\":99}',0,1,1,0,0,50,NULL,NULL,'2026-08-05 04:16:44',NULL),(40,'mpi.possible_match_threshold','55','integer','Medical Records','Possible duplicate score threshold.','55','{\"min\":30,\"max\":89}',0,1,1,0,0,60,NULL,NULL,'2026-08-05 04:16:44',NULL),(41,'mpi.search_page_size','25','integer','Medical Records','Default MPI search page size.','25','{\"min\":10,\"max\":100}',0,1,1,0,0,70,NULL,NULL,'2026-08-05 04:16:44',NULL),(42,'mpi.mask_identifier_types','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\"]','array','Medical Records','Identifier types masked in ordinary displays.','[\"National Identification Number\",\"Insurance Number\",\"Passport Number\"]','{}',0,1,1,0,0,80,NULL,NULL,'2026-08-05 04:16:44',NULL);
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
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
  CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'EMP000001','System','Administrator','Male',NULL,'admin@hospital.local','admin','$2y$10$dgHg8.V9d8DJ30tzyBdS/.bolc5DivFkTUFjkeqtEI9rM58L6WoHm',1,1,'Active',0,NULL,NULL,NULL,1,'2026-08-05 04:14:45',NULL);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visit_queue`
--

LOCK TABLES `visit_queue` WRITE;
/*!40000 ALTER TABLE `visit_queue` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visit_transfers`
--

LOCK TABLES `visit_transfers` WRITE;
/*!40000 ALTER TABLE `visit_transfers` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visits`
--

LOCK TABLES `visits` WRITE;
/*!40000 ALTER TABLE `visits` DISABLE KEYS */;
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

-- Dump completed on 2026-08-05  5:42:49
