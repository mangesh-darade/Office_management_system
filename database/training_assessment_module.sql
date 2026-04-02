-- =============================================================================
-- Training & Assessment module — schema with prefixed tables (ta_*), FKs, permissions
-- CodeIgniter 3 / MySQL 5.7+ / utf8mb4
-- New installs: run this file once. BACK UP first.
-- Existing installs with legacy names (assessments, questions, …): keep them;
--   the app uses legacy tables when `assessments` exists, otherwise ta_*.
--   Optional rename: database/training_assessment_legacy_to_prefixed.sql (advanced).
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `ta_results`;
DROP TABLE IF EXISTS `ta_user_answers`;
DROP TABLE IF EXISTS `ta_question_options`;
DROP TABLE IF EXISTS `ta_questions`;
DROP TABLE IF EXISTS `ta_assessment_users`;
DROP TABLE IF EXISTS `ta_assessments`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `ta_assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `time_limit_minutes` int(11) NOT NULL DEFAULT 30,
  `passing_marks` decimal(5,2) NOT NULL DEFAULT 60.00 COMMENT 'Percentage 0–100',
  `randomize_questions` tinyint(1) NOT NULL DEFAULT 0,
  `shuffle_options` tinyint(1) NOT NULL DEFAULT 0,
  `max_attempts` int(11) NOT NULL DEFAULT 1 COMMENT '0 = unlimited',
  `allow_retake` tinyint(1) NOT NULL DEFAULT 0,
  `show_correct_after_submit` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Learner sees correct answers on result',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ta_assessments_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ta_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(11) NOT NULL,
  `question_type` enum('mcq','text','coding') NOT NULL DEFAULT 'mcq',
  `question_text` text NOT NULL,
  `points` decimal(6,2) NOT NULL DEFAULT 1.00,
  `coding_language` varchar(20) DEFAULT NULL COMMENT 'php or js',
  `model_answer` text COMMENT 'Text rubric / expected keywords; optional auto-compare',
  `coding_expected_output` text COMMENT 'Compared to trimmed execution output for coding',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ta_q_assessment` (`assessment_id`),
  CONSTRAINT `fk_ta_questions_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `ta_assessments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ta_question_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `option_text` varchar(500) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ta_qo_question` (`question_id`),
  CONSTRAINT `fk_ta_qo_question` FOREIGN KEY (`question_id`) REFERENCES `ta_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ta_assessment_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'users.id when assignee is an employee',
  `candidate_name` varchar(190) DEFAULT NULL,
  `candidate_email` varchar(190) DEFAULT NULL,
  `access_token` varchar(64) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` datetime NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `server_ends_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `attempts_used` int(11) NOT NULL DEFAULT 0,
  `question_order` text COMMENT 'CSV of question ids for this attempt',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ta_access_token` (`access_token`),
  KEY `idx_ta_au_assessment` (`assessment_id`),
  KEY `idx_ta_au_user` (`user_id`),
  CONSTRAINT `fk_ta_au_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `ta_assessments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ta_user_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option_id` int(11) DEFAULT NULL,
  `answer_text` text,
  `code_submitted` text,
  `execution_output` text,
  `is_graded_correct` tinyint(1) DEFAULT NULL,
  `points_earned` decimal(6,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ta_au_question` (`assessment_user_id`,`question_id`),
  KEY `idx_ta_ua_question` (`question_id`),
  CONSTRAINT `fk_ta_ua_au` FOREIGN KEY (`assessment_user_id`) REFERENCES `ta_assessment_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ta_ua_question` FOREIGN KEY (`question_id`) REFERENCES `ta_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ta_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_user_id` int(11) NOT NULL,
  `score_percent` decimal(6,2) NOT NULL DEFAULT 0.00,
  `total_points` decimal(8,2) NOT NULL DEFAULT 0.00,
  `earned_points` decimal(8,2) NOT NULL DEFAULT 0.00,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `duration_seconds` int(11) DEFAULT NULL,
  `submitted_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ta_result_au` (`assessment_user_id`),
  CONSTRAINT `fk_ta_res_au` FOREIGN KEY (`assessment_user_id`) REFERENCES `ta_assessment_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Permissions: grant module key `training_assessment` to roles that should manage it.
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'training_assessment', 1),
(1, 'training_assessment_manage', 1),
(1, 'training_assessment_take', 1),
(2, 'training_assessment', 1),
(2, 'training_assessment_manage', 1),
(2, 'training_assessment_take', 1),
(3, 'training_assessment_take', 1),
(4, 'training_assessment_take', 1);

-- -----------------------------------------------------------------------------
-- Sample assessment (optional)
-- -----------------------------------------------------------------------------
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('PHP & Architecture fundamentals', 'Starter assessment covering PHP basics, MVC, and simple coding.', 45, 60.00, 1, 1, 2, 1, 0, 'active', 1, NOW(), NOW());
SET @aid := LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`) VALUES
(@aid, 'mcq', 'What is PHP?', 1.00, NULL, NULL, NULL, 1, NOW());
SET @q_mcq := LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`) VALUES
(@aid, 'text', 'Explain MVC architecture in your own words.', 2.00, NULL, 'model view controller separation', NULL, 2, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`) VALUES
(@aid, 'coding', 'Write a PHP one-liner that reverses $s using strrev and returns it.', 3.00, 'php', NULL, 'olleh', 3, NOW());

INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@q_mcq, 'A programming language', 1, 1, NOW()),
(@q_mcq, 'A database engine', 0, 2, NOW()),
(@q_mcq, 'A web server', 0, 3, NOW()),
(@q_mcq, 'None of the above', 0, 4, NOW());

INSERT INTO `ta_assessment_users` (`assessment_id`, `user_id`, `candidate_name`, `candidate_email`, `access_token`, `assigned_by`, `assigned_at`, `created_at`) VALUES
(@aid, NULL, 'Demo Candidate', 'candidate@example.com', 'demoassessmenttoken000000000012', 1, NOW(), NOW());
