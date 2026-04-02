-- =============================================================================
-- Training LMS + Topic Assignments (CodeIgniter 3, MySQL 5.7+)
-- Tables: training_modules, training_topics, assignments, assignment_submissions
-- Links training_topics.assessment_id to ta_assessments.id or assessments.id (app resolves).
-- Run once after backup. Adjust role_id in permissions for your install.
-- Demo INSERTs at bottom: remove or edit user_id 1 / submission filenames for production.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `assignment_submissions`;
DROP TABLE IF EXISTS `assignments`;
DROP TABLE IF EXISTS `training_topic_completions`;
DROP TABLE IF EXISTS `training_enrollments`;
DROP TABLE IF EXISTS `training_topics`;
DROP TABLE IF EXISTS `training_modules`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `training_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tm_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `training_topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `prerequisite_topic_id` int(11) DEFAULT NULL COMMENT 'Must complete this topic first (same module recommended)',
  `name` varchar(255) NOT NULL,
  `description` text,
  `prerequisites` text,
  `duration_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `has_assignment` tinyint(1) NOT NULL DEFAULT 0,
  `has_assessment` tinyint(1) NOT NULL DEFAULT 0,
  `assessment_id` int(11) DEFAULT NULL COMMENT 'Links to ta_assessments.id or assessments.id',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tt_module` (`module_id`),
  KEY `idx_tt_prereq` (`prerequisite_topic_id`),
  KEY `idx_tt_assessment` (`assessment_id`),
  CONSTRAINT `fk_tt_module` FOREIGN KEY (`module_id`) REFERENCES `training_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `details` text COMMENT 'Instructions for learner',
  `max_submissions` int(11) NOT NULL DEFAULT 0 COMMENT '0 = unlimited file uploads per user',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assign_topic` (`topic_id`),
  CONSTRAINT `fk_assign_topic` FOREIGN KEY (`topic_id`) REFERENCES `training_topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `assignment_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_ext` varchar(20) NOT NULL DEFAULT '',
  `file_size` int(11) NOT NULL DEFAULT 0,
  `mime_type` varchar(120) DEFAULT NULL,
  `status` enum('pending','submitted','assessed') NOT NULL DEFAULT 'submitted',
  `score` decimal(6,2) DEFAULT NULL,
  `feedback` text,
  `submitted_at` datetime NOT NULL,
  `assessed_at` datetime DEFAULT NULL,
  `assessed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sub_assign` (`assignment_id`),
  KEY `idx_sub_user` (`user_id`),
  CONSTRAINT `fk_sub_assign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `training_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_te_user_module` (`user_id`,`module_id`),
  KEY `idx_te_module` (`module_id`),
  CONSTRAINT `fk_te_module` FOREIGN KEY (`module_id`) REFERENCES `training_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `training_topic_completions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `completed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ttc_user_topic` (`user_id`,`topic_id`),
  KEY `idx_ttc_topic` (`topic_id`),
  CONSTRAINT `fk_ttc_topic` FOREIGN KEY (`topic_id`) REFERENCES `training_topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional FK to assessments (uncomment if table exists)
-- ALTER TABLE `training_topics`
--   ADD CONSTRAINT `fk_tt_assessment_ref` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE SET NULL;

INSERT IGNORE INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'training_lms', 1),
(1, 'training_lms_manage', 1),
(2, 'training_lms', 1),
(2, 'training_lms_manage', 1);

-- Optional: learner-only roles (change role_id if your “employee” role differs)
INSERT IGNORE INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(3, 'training_lms', 1),
(4, 'training_lms', 1);

-- =============================================================================
-- DEMO DATA — remove this section for production, or edit user_id / filenames
-- =============================================================================

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`)
VALUES ('Onboarding', 'Introduction for new staff (demo module).', 'active', 1, NULL, NOW(), NULL);
SET @lms_demo_m1 = LAST_INSERT_ID();

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`)
VALUES ('Compliance', 'Policies and procedures (demo module).', 'active', 2, NULL, NOW(), NULL);
SET @lms_demo_m2 = LAST_INSERT_ID();

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`)
VALUES (@lms_demo_m1, NULL, 'Welcome & orientation', 'Company values, org chart, first-week checklist.', 'None', 1.50, 0, 1, NULL, 1, NOW(), NULL);
SET @lms_demo_t1 = LAST_INSERT_ID();

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`)
VALUES (@lms_demo_m1, @lms_demo_t1, 'Safety basics', 'Workplace safety and incident reporting.', 'Complete Welcome & orientation', 2.00, 1, 1, NULL, 2, NOW(), NULL);
SET @lms_demo_t2 = LAST_INSERT_ID();

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`)
VALUES (@lms_demo_m2, NULL, 'Data protection', 'Handling personal and confidential data.', 'None', 3.00, 1, 0, NULL, 1, NOW(), NULL);
SET @lms_demo_t3 = LAST_INSERT_ID();

INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`)
VALUES (@lms_demo_t2, 'Safety acknowledgment', 'Upload a signed PDF of the safety checklist (or a clear photo of the signature page).', 0, NOW(), NULL);
SET @lms_demo_a1 = LAST_INSERT_ID();

INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`)
VALUES (@lms_demo_t3, 'Data policy acknowledgment', 'Submit your signed acknowledgment (PDF, DOC/DOCX, or image).', 0, NOW(), NULL);
SET @lms_demo_a2 = LAST_INSERT_ID();

-- Submissions: set user_id to an existing users.id (1 is common for first admin). Files are DB-only demo; upload real files via the app if you need downloads.
INSERT INTO `assignment_submissions` (`assignment_id`, `user_id`, `stored_filename`, `original_filename`, `file_ext`, `file_size`, `mime_type`, `status`, `score`, `feedback`, `submitted_at`, `assessed_at`, `assessed_by`, `created_at`, `updated_at`) VALUES
(@lms_demo_a1, 1, 'demo_lms_safety_v1.pdf', 'signed_checklist.pdf', 'pdf', 245760, 'application/pdf', 'assessed', 95.00, 'Demo feedback: approved.', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 1, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(@lms_demo_a1, 1, 'demo_lms_safety_v2.pdf', 'signed_checklist_revised.pdf', 'pdf', 251904, 'application/pdf', 'submitted', NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
(@lms_demo_a2, 1, 'demo_lms_data_ack.docx', 'data_policy_ack.docx', 'docx', 18944, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'pending', NULL, NULL, NOW(), NULL, NULL, NOW(), NULL);
