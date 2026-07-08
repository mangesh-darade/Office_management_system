-- =============================================================================
-- TRUNCATE training / assessment–related tables (MySQL / MariaDB)
--
-- WARNING: Deletes ALL rows. Back up first. Resets AUTO_INCREMENT.
-- If a table does not exist, comment out that TRUNCATE line and re-run.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `ta_user_answers`;
TRUNCATE TABLE `ta_results`;
TRUNCATE TABLE `ta_question_options`;
TRUNCATE TABLE `ta_questions`;
TRUNCATE TABLE `ta_assessment_users`;
TRUNCATE TABLE `assignment_submissions`;
TRUNCATE TABLE `assignments`;
TRUNCATE TABLE `training_topic_completions`;
TRUNCATE TABLE `training_enrollments`;
TRUNCATE TABLE `training_topics`;
TRUNCATE TABLE `training_modules`;
TRUNCATE TABLE `ta_assessments`;
TRUNCATE TABLE `sma_external_trainings`;
TRUNCATE TABLE `ta_activity_logs`;
TRUNCATE TABLE `assessment_attempts`;
TRUNCATE TABLE `assessment_logs`;

SET FOREIGN_KEY_CHECKS = 1;
