-- =============================================================================
-- Marketing curriculum seed: modules, topics, file assignments, MCQ assessments
--
-- Matches sidebar/LMS structure:
--   training_modules → training_topics (has_assignment + has_assessment + assessment_id)
--   assignments (one per topic, instructions in `details`)
--   ta_assessments → ta_questions (mcq) → ta_question_options
--
-- Prerequisites (run first, with backups):
--   • database/training_lms_module.sql
--   • database/training_assessment_module.sql
--
-- If your install uses legacy tables only (no ta_*):
--   Replace ta_assessments → assessments, ta_questions → questions,
--   ta_question_options → question_options throughout.
--
-- Re-running inserts duplicate rows — remove prior marketing rows or change titles.
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- Modules
-- -----------------------------------------------------------------------------
INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('Module 1: Introduction to Marketing', 'Module-wise assignments and assessments — marketing fundamentals, funnel, STP, persona, competitors, KPIs.', 'active', 100, NULL, NOW(), NULL);
SET @mk_m1 = LAST_INSERT_ID();

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('Module 2: Digital Advertising', 'Meta Ads, audience targeting, Google Ads, keywords, ad copy.', 'active', 110, NULL, NOW(), NULL);
SET @mk_m2 = LAST_INSERT_ID();

-- =============================================================================
-- Module 1 — Topic: Introduction to Marketing
-- =============================================================================
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Assessment (MCQ): Introduction to Marketing', 'Knowledge check for Introduction to Marketing topic.', 20, 60.00, 0, 0, 2, 1, 1, 'active', 1, NOW(), NULL);
SET @mk_a1 = LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_a1, 'mcq', 'What is the main goal of marketing?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);
SET @mk_q1 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mk_q1, 'Selling products', 0, 0, NOW()),
(@mk_q1, 'Creating awareness', 1, 1, NOW()),
(@mk_q1, 'Manufacturing', 0, 2, NOW()),
(@mk_q1, 'Distribution', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_a1, 'mcq', 'Marketing is different from sales because:', 1.00, NULL, NULL, NULL, 2, NOW(), NULL);
SET @mk_q2 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mk_q2, 'Sales is long-term', 0, 0, NOW()),
(@mk_q2, 'Marketing builds demand', 1, 1, NOW()),
(@mk_q2, 'Sales builds brand', 0, 2, NOW()),
(@mk_q2, 'None', 0, 3, NOW());

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_m1, NULL, 'Introduction to Marketing', 'Core marketing concepts and marketing vs sales.', 'None', 2.00, 1, 1, @mk_a1, 1, NOW(), NULL);
SET @mk_t1 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mk_t1, 'Introduction to Marketing — Assignment', 'Pick a real business (local shop / online brand).\r\n\r\nIdentify:\r\n• What product they sell\r\n• Who is their target audience\r\n• Difference between their marketing & sales approach\r\n\r\nSubmit your write-up as a document upload.', 0, NOW(), NULL);

-- =============================================================================
-- Module 1 — Topic: Marketing Funnel
-- =============================================================================
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Assessment (MCQ): Marketing Funnel', 'Funnel stages and retention.', 20, 60.00, 0, 0, 2, 1, 1, 'active', 1, NOW(), NULL);
SET @mk_a2 = LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_a2, 'mcq', 'First stage of funnel:', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);
SET @mk_q3 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mk_q3, 'Awareness', 1, 0, NOW()),
(@mk_q3, 'Conversion', 0, 1, NOW()),
(@mk_q3, 'Retention', 0, 2, NOW()),
(@mk_q3, 'Sales', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_a2, 'mcq', 'Retention means:', 1.00, NULL, NULL, NULL, 2, NOW(), NULL);
SET @mk_q4 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mk_q4, 'Getting new users', 0, 0, NOW()),
(@mk_q4, 'Keeping existing users', 1, 1, NOW()),
(@mk_q4, 'Selling more ads', 0, 2, NOW()),
(@mk_q4, 'None', 0, 3, NOW());

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_m1, NULL, 'Marketing Funnel', 'Awareness through retention strategies.', 'None', 2.00, 1, 1, @mk_a2, 2, NOW(), NULL);
SET @mk_t2 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mk_t2, 'Marketing Funnel — Assignment', 'Create a funnel for any product (e.g., Mobile, Clothing).\r\n\r\nDefine:\r\n• Awareness strategy\r\n• Consideration strategy\r\n• Conversion strategy\r\n• Retention strategy\r\n\r\nSubmit as a document upload.', 0, NOW(), NULL);

-- =============================================================================
-- Module 1 — Topic: STP Model
-- =============================================================================
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Assessment (MCQ): STP Model', 'Segmentation, targeting, positioning.', 15, 60.00, 0, 0, 2, 1, 1, 'active', 1, NOW(), NULL);
SET @mk_a3 = LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_a3, 'mcq', 'STP stands for:', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);
SET @mk_q5 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mk_q5, 'Segmentation, Targeting, Positioning', 1, 0, NOW()),
(@mk_q5, 'Strategy, Target, Planning', 0, 1, NOW()),
(@mk_q5, 'Sales, Tracking, Promotion', 0, 2, NOW()),
(@mk_q5, 'None', 0, 3, NOW());

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_m1, NULL, 'STP Model', 'Segment, target, and position a product.', 'None', 1.50, 1, 1, @mk_a3, 3, NOW(), NULL);
SET @mk_t3 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mk_t3, 'STP Model — Assignment', 'Choose a product and:\r\n• Define 3 segments\r\n• Select 1 target segment\r\n• Write positioning statement\r\n\r\nSubmit as a document upload.', 0, NOW(), NULL);

-- =============================================================================
-- Module 1 — Topic: Customer Persona
-- =============================================================================
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Assessment (MCQ): Customer Persona', 'What a persona represents.', 15, 60.00, 0, 0, 2, 1, 1, 'active', 1, NOW(), NULL);
SET @mk_a4 = LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_a4, 'mcq', 'Persona represents:', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);
SET @mk_q6 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mk_q6, 'Real user behavior', 1, 0, NOW()),
(@mk_q6, 'Random data', 0, 1, NOW()),
(@mk_q6, 'Sales report', 0, 2, NOW()),
(@mk_q6, 'None', 0, 3, NOW());

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_m1, NULL, 'Customer Persona', 'Build a realistic customer profile.', 'None', 1.50, 1, 1, @mk_a4, 4, NOW(), NULL);
SET @mk_t4 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mk_t4, 'Customer Persona — Assignment', 'Create 1 customer persona including:\r\n• Name\r\n• Age\r\n• Job\r\n• Pain points\r\n• Goals\r\n\r\nSubmit as a document upload.', 0, NOW(), NULL);

-- =============================================================================
-- Module 1 — Topic: Competitor Analysis
-- =============================================================================
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Assessment (MCQ): Competitor Analysis', 'SWOT fundamentals.', 15, 60.00, 0, 0, 2, 1, 1, 'active', 1, NOW(), NULL);
SET @mk_a5 = LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_a5, 'mcq', 'SWOT stands for:', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);
SET @mk_q7 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mk_q7, 'Strength, Weakness, Opportunity, Threat', 1, 0, NOW()),
(@mk_q7, 'Sales, Work, Output, Target', 0, 1, NOW()),
(@mk_q7, 'None', 0, 2, NOW());

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_m1, NULL, 'Competitor Analysis', 'Competitive landscape and SWOT.', 'None', 2.00, 1, 1, @mk_a5, 5, NOW(), NULL);
SET @mk_t5 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mk_t5, 'Competitor Analysis — Assignment', 'Select 2 competitors.\r\nCreate a SWOT analysis for your chosen context.\r\n\r\nSubmit as a document upload.', 0, NOW(), NULL);

-- =============================================================================
-- Module 1 — Topic: KPIs & Metrics
-- =============================================================================
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Assessment (MCQ): KPIs & Metrics', 'CAC and core metrics.', 15, 60.00, 0, 0, 2, 1, 1, 'active', 1, NOW(), NULL);
SET @mk_a6 = LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_a6, 'mcq', 'CAC means:', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);
SET @mk_q8 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mk_q8, 'Customer Acquisition Cost', 1, 0, NOW()),
(@mk_q8, 'Customer Average Cost', 0, 1, NOW()),
(@mk_q8, 'None', 0, 2, NOW());

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_m1, NULL, 'KPIs & Metrics', 'CAC, ROI, LTV and related KPIs.', 'None', 2.00, 1, 1, @mk_a6, 6, NOW(), NULL);
SET @mk_t6 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mk_t6, 'KPIs & Metrics — Assignment', 'Calculate (using sample data provided in class or your brief):\r\n• CAC\r\n• ROI\r\n• LTV\r\n\r\nShow your working. Submit as a document upload.', 0, NOW(), NULL);

-- =============================================================================
-- Module 2 — Topic: Meta Ads Basics
-- =============================================================================
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Assessment (MCQ): Meta Ads Basics', 'Campaign structure on Meta.', 20, 60.00, 0, 0, 2, 1, 1, 'active', 1, NOW(), NULL);
SET @mk_a7 = LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_a7, 'mcq', 'Campaign level defines:', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);
SET @mk_q9 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mk_q9, 'Objective', 1, 0, NOW()),
(@mk_q9, 'Budget', 0, 1, NOW()),
(@mk_q9, 'Audience', 0, 2, NOW());

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_m2, NULL, 'Meta Ads Basics', 'Campaign, ad set, and ad creative overview.', 'None', 2.00, 1, 1, @mk_a7, 1, NOW(), NULL);
SET @mk_t7 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mk_t7, 'Meta Ads Basics — Assignment', 'Create a dummy campaign plan:\r\n• Campaign objective\r\n• Ad set (audience)\r\n• Ad creative (text + image idea)\r\n\r\nSubmit as a document upload.', 0, NOW(), NULL);

-- =============================================================================
-- Module 2 — Topic: Audience Targeting
-- =============================================================================
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Assessment (MCQ): Audience Targeting', 'Custom and lookalike audiences.', 15, 60.00, 0, 0, 2, 1, 1, 'active', 1, NOW(), NULL);
SET @mk_a8 = LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_a8, 'mcq', 'Lookalike audience is:', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);
SET @mk_q10 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mk_q10, 'New users similar to existing users', 1, 0, NOW()),
(@mk_q10, 'Random users', 0, 1, NOW()),
(@mk_q10, 'Competitors', 0, 2, NOW());

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_m2, NULL, 'Audience Targeting', 'Custom and lookalike audiences.', 'None', 1.50, 1, 1, @mk_a8, 2, NOW(), NULL);
SET @mk_t8 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mk_t8, 'Audience Targeting — Assignment', 'Define:\r\n• Custom audience\r\n• Lookalike audience\r\n\r\nSubmit as a document upload.', 0, NOW(), NULL);

-- =============================================================================
-- Module 2 — Topic: Google Ads Basics
-- =============================================================================
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Assessment (MCQ): Google Ads Basics', 'Search ads and keywords.', 15, 60.00, 0, 0, 2, 1, 1, 'active', 1, NOW(), NULL);
SET @mk_a9 = LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_a9, 'mcq', 'Google Ads mainly uses:', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);
SET @mk_q11 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mk_q11, 'Keywords', 1, 0, NOW()),
(@mk_q11, 'Images', 0, 1, NOW()),
(@mk_q11, 'Videos', 0, 2, NOW());

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_m2, NULL, 'Google Ads Basics', 'Keywords, headlines, descriptions.', 'None', 1.50, 1, 1, @mk_a9, 3, NOW(), NULL);
SET @mk_t9 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mk_t9, 'Google Ads Basics — Assignment', 'Create:\r\n• 5 keywords\r\n• 1 ad headline\r\n• 1 description\r\n\r\nSubmit as a document upload.', 0, NOW(), NULL);

-- =============================================================================
-- Module 2 — Topic: Keyword Research
-- =============================================================================
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Assessment (MCQ): Keyword Research', 'Short-tail vs long-tail.', 15, 60.00, 0, 0, 2, 1, 1, 'active', 1, NOW(), NULL);
SET @mk_a10 = LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_a10, 'mcq', 'Long-tail keywords:', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);
SET @mk_q12 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mk_q12, 'More specific', 1, 0, NOW()),
(@mk_q12, 'Generic', 0, 1, NOW()),
(@mk_q12, 'Short', 0, 2, NOW());

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_m2, NULL, 'Keyword Research', 'Short-tail and long-tail keyword lists.', 'None', 1.50, 1, 1, @mk_a10, 4, NOW(), NULL);
SET @mk_t10 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mk_t10, 'Keyword Research — Assignment', 'Find:\r\n• 5 short-tail keywords\r\n• 5 long-tail keywords\r\n\r\nSubmit as a document upload.', 0, NOW(), NULL);

-- =============================================================================
-- Module 2 — Topic: Ad Copywriting
-- =============================================================================
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Assessment (MCQ): Ad Copywriting', 'Headlines, CTA, descriptions.', 15, 60.00, 0, 0, 2, 1, 1, 'active', 1, NOW(), NULL);
SET @mk_a11 = LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_a11, 'mcq', 'CTA means:', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);
SET @mk_q13 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mk_q13, 'Call To Action', 1, 0, NOW()),
(@mk_q13, 'Customer Target Area', 0, 1, NOW()),
(@mk_q13, 'Click Tracking Ads', 0, 2, NOW());

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mk_m2, NULL, 'Ad Copywriting', 'Headline, CTA, and ad description.', 'None', 1.50, 1, 1, @mk_a11, 5, NOW(), NULL);
SET @mk_t11 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mk_t11, 'Ad Copywriting — Assignment', 'Write:\r\n• 1 headline\r\n• 1 CTA\r\n• 1 ad description\r\n\r\nSubmit as a document upload.', 0, NOW(), NULL);

-- =============================================================================
-- End of seed
-- =============================================================================
