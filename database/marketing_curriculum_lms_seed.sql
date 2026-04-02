-- =============================================================================
-- Marketing curriculum — LMS modules, topics, assignments + knowledge checks
-- Based on provided syllabus (modules 1–6, final project, per-topic assignments).
--
-- Prerequisites:
--   • training_lms_module.sql applied (training_modules, training_topics, assignments, …)
--   • training_assessment_module.sql applied (ta_assessments, ta_questions, ta_question_options)
--
-- If your DB uses legacy names only (assessments, questions, question_options):
--   Replace `ta_assessments` → `assessments`, `ta_questions` → `questions`,
--   `ta_question_options` → `question_options` throughout this file.
--
-- Does NOT insert assignment_submissions or ta_assessment_users (trainee-specific).
-- Safe to run once; duplicate runs will create duplicate modules/topics — edit or
-- delete existing marketing rows first if re-running.
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- Modules
-- -----------------------------------------------------------------------------
INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('Module 1: Marketing foundations', 'Basics of marketing, funnel, STP, persona, competitors, KPIs.', 'active', 10, NULL, NOW(), NULL);
SET @mkt_m1 = LAST_INSERT_ID();

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('Module 2: Paid advertising', 'Meta Ads, Google Ads, copy, budgeting & ROI.', 'active', 20, NULL, NOW(), NULL);
SET @mkt_m2 = LAST_INSERT_ID();

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('Module 3: SEO & content', 'SEO, keywords, blogging, on-page, content calendar.', 'active', 30, NULL, NOW(), NULL);
SET @mkt_m3 = LAST_INSERT_ID();

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('Module 4: Analytics & reporting', 'GA4, Excel, dashboards.', 'active', 40, NULL, NOW(), NULL);
SET @mkt_m4 = LAST_INSERT_ID();

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('Module 5: CRM & email', 'CRM, email campaigns, automation, segmentation.', 'active', 50, NULL, NOW(), NULL);
SET @mkt_m5 = LAST_INSERT_ID();

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('Module 6: CRO', 'Conversion optimization, landing pages, A/B testing.', 'active', 60, NULL, NOW(), NULL);
SET @mkt_m6 = LAST_INSERT_ID();

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('Final project: Marketing strategy', 'Capstone — full strategy, execution plan, analytics & reporting.', 'active', 70, NULL, NOW(), NULL);
SET @mkt_m7 = LAST_INSERT_ID();

-- =============================================================================
-- Helper: each topic = 1 assessment + 1 question (+ options if MCQ) + topic + assignment
-- =============================================================================

-- ----- Module 1 -----
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Introduction to Marketing', 'Marketing vs sales and core concepts.', 25, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a1 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a1, 'text', 'Explain the difference between marketing and sales. Support your answer with at least one example of each.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m1, NULL, 'Introduction to Marketing', 'Basics of marketing, marketing vs sales.', 'None', 2.00, 1, 1, @mkt_a1, 1, NOW(), NULL);
SET @mkt_t_intro = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_intro, 'Write difference with examples', 'Submit a short document comparing marketing and sales with concrete examples.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Marketing Funnel', 'Stages of the marketing funnel.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a2 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a2, 'mcq', 'Which funnel stage best describes turning interested prospects into paying customers?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);
SET @mkt_q_funnel = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mkt_q_funnel, 'Awareness', 0, 0, NOW()),
(@mkt_q_funnel, 'Consideration', 0, 1, NOW()),
(@mkt_q_funnel, 'Conversion', 1, 2, NOW()),
(@mkt_q_funnel, 'Retention (post-purchase loyalty)', 0, 3, NOW());

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m1, NULL, 'Marketing Funnel', 'Awareness, consideration, conversion, retention.', 'None', 3.00, 1, 1, @mkt_a2, 2, NOW(), NULL);
SET @mkt_t_funnel = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_funnel, 'Create funnel for a product', 'Build a simple funnel diagram or description for one product of your choice.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: STP Model', 'Segmentation, targeting, positioning.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a3 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a3, 'mcq', 'What does the “STP” model stand for in marketing?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);
SET @mkt_q_stp = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@mkt_q_stp, 'Sales, Traffic, Promotion', 0, 0, NOW()),
(@mkt_q_stp, 'Segmentation, Targeting, Positioning', 1, 1, NOW()),
(@mkt_q_stp, 'Strategy, Tactics, Performance', 0, 2, NOW()),
(@mkt_q_stp, 'Survey, Test, Publish', 0, 3, NOW());

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m1, NULL, 'STP Model', 'Segmentation, targeting, positioning.', 'None', 3.00, 1, 1, @mkt_a3, 3, NOW(), NULL);
SET @mkt_t_stp = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_stp, 'Define STP for a brand', 'Choose a brand and describe its segmentation, target segment, and positioning.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Customer Persona', 'Demographics, behavior, pain points.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a4 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a4, 'text', 'List three elements every strong customer persona should include and briefly explain why each matters.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m1, NULL, 'Customer Persona', 'User demographics, behavior, pain points.', 'None', 3.00, 1, 1, @mkt_a4, 4, NOW(), NULL);
SET @mkt_t_persona = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_persona, 'Create 1 detailed persona', 'Submit one persona document including demographics, goals, pain points, and channels.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Competitor Analysis', 'Direct/indirect competitors, SWOT.', 25, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a5 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a5, 'text', 'What is the difference between a direct and an indirect competitor? Give one example of each.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m1, NULL, 'Competitor Analysis', 'Direct/indirect competitors, SWOT.', 'None', 3.00, 1, 1, @mkt_a5, 5, NOW(), NULL);
SET @mkt_t_comp = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_comp, 'Analyze 3 competitors', 'Submit an analysis covering three competitors (direct and/or indirect) with brief SWOT notes.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: KPIs & Metrics', 'CAC, LTV, ROI basics.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a6 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a6, 'text', 'Define CAC and LTV in your own words. Why do marketers compare them?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m1, NULL, 'KPIs & Metrics', 'CAC, LTV, ROI basics.', 'Basic math', 2.00, 1, 1, @mkt_a6, 6, NOW(), NULL);
SET @mkt_t_kpis = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_kpis, 'List KPIs for a campaign', 'List at least five KPIs you would track for a digital campaign and what each measures.', 0, NOW(), NULL);

-- ----- Module 2 (prereq chains: Meta after M1 end; Google after M1 end; internal chains) -----
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Meta Ads Basics', 'Campaign structure, ad sets, creatives.', 25, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a7 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a7, 'text', 'Name the three main levels of a Meta (Facebook/Instagram) campaign structure from top to bottom.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m2, @mkt_t_kpis, 'Meta Ads Basics', 'Campaign structure, ad sets, creatives.', 'Module 1', 4.00, 1, 1, @mkt_a7, 1, NOW(), NULL);
SET @mkt_t_meta = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_meta, 'Create dummy campaign', 'Outline a mock Meta campaign: objective, ad set targeting, and one creative concept.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Audience Targeting', 'Custom and lookalike audiences.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a8 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a8, 'text', 'Explain what a lookalike audience is and when you would use it.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m2, @mkt_t_meta, 'Audience Targeting', 'Custom audience, lookalike audience.', 'Meta Ads basics', 3.00, 1, 1, @mkt_a8, 2, NOW(), NULL);
SET @mkt_t_aud = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_aud, 'Define target audience', 'Describe a custom audience and a lookalike you would test for a sample product.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Google Ads Basics', 'Search ads, keywords.', 25, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a9 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a9, 'text', 'What is the relationship between a Google Ads campaign, ad group, and keywords in a Search campaign?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m2, @mkt_t_kpis, 'Google Ads Basics', 'Search ads, keywords.', 'Module 1', 4.00, 1, 1, @mkt_a9, 3, NOW(), NULL);
SET @mkt_t_google = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_google, 'Create keyword list', 'Submit a starter keyword list (themes + example keywords) for one product or service.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Keyword Research', 'Keyword types, tools.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a10 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a10, 'text', 'What is the difference between a head (short-tail) and a long-tail keyword? Give one example of each.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m2, @mkt_t_google, 'Keyword Research', 'Keyword types, tools.', 'Google Ads basics', 3.00, 1, 1, @mkt_a10, 4, NOW(), NULL);
SET @mkt_t_kw = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_kw, '20 keyword sheet', 'Submit a spreadsheet or table with at least 20 keywords grouped by intent or theme.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Ad Copywriting', 'Hooks, CTA, messaging.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a11 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a11, 'text', 'What are two characteristics of a strong call-to-action (CTA) in paid social copy?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m2, NULL, 'Ad Copywriting', 'Hooks, CTA, messaging.', 'None', 3.00, 1, 1, @mkt_a11, 5, NOW(), NULL);
SET @mkt_t_copy = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_copy, 'Write 3 ad copies', 'Submit three variations of ad copy (hook + body + CTA) for the same offer.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Budgeting & ROI', 'CPC, CTR, ROAS.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a12 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a12, 'text', 'Define CPC and CTR. How would a drop in CTR affect your campaign evaluation?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m2, NULL, 'Budgeting & ROI', 'CPC, CTR, ROAS.', 'Basic Excel', 3.00, 1, 1, @mkt_a12, 6, NOW(), NULL);
SET @mkt_t_budget = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_budget, 'Calculate ROI example', 'Show a simple ROI or ROAS calculation with assumed spend and revenue numbers.', 0, NOW(), NULL);

-- ----- Module 3 -----
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: SEO Basics', 'On-page, off-page, technical.', 25, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a13 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a13, 'text', 'Name the three SEO pillars (on-page, off-page, technical) and give one example tactic for each.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m3, @mkt_t_kpis, 'SEO Basics', 'On-page, off-page, technical SEO.', 'Module 1', 4.00, 1, 1, @mkt_a13, 1, NOW(), NULL);
SET @mkt_t_seo = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_seo, 'Audit website SEO', 'Submit a short checklist-style audit (real or example site) covering on-page, off-page, and technical signals.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Keyword Strategy', 'Short-tail vs long-tail.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a14 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a14, 'text', 'When would you prioritize long-tail keywords over short-tail keywords in an SEO plan?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m3, @mkt_t_seo, 'Keyword Strategy', 'Short-tail vs long-tail.', 'SEO basics', 3.00, 1, 1, @mkt_a14, 2, NOW(), NULL);
SET @mkt_t_kwstrat = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_kwstrat, 'Keyword plan', 'Submit a small keyword plan mapping topics to target keywords (short and long-tail).', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Blog Writing', 'Structure, readability, CTA.', 25, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a15 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a15, 'text', 'What is the purpose of an H1 vs H2 heading in a blog post for both readers and SEO?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m3, NULL, 'Blog Writing', 'Structure, readability, CTA.', 'None', 4.00, 1, 1, @mkt_a15, 3, NOW(), NULL);
SET @mkt_t_blog = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_blog, 'Write 1 blog', 'Submit one blog article (outline + draft) on a marketing-related topic.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: On-Page SEO', 'Meta tags, headings.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a16 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a16, 'text', 'List three on-page elements you would optimize on a landing page and what you would change.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m3, @mkt_t_seo, 'On-Page SEO', 'Meta tags, headings.', 'SEO basics', 3.00, 1, 1, @mkt_a16, 4, NOW(), NULL);
SET @mkt_t_onpage = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_onpage, 'Optimize blog', 'Apply on-page SEO improvements to your blog draft (title tag, meta description, headings).', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Content Calendar', 'Planning content.', 15, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a17 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a17, 'text', 'Why is a content calendar useful for teams? Name two benefits.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m3, NULL, 'Content Calendar', 'Planning content.', 'None', 2.00, 1, 1, @mkt_a17, 5, NOW(), NULL);
SET @mkt_t_cal = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_cal, 'Create 2-week calendar', 'Submit a two-week content calendar with themes, channels, and publish dates.', 0, NOW(), NULL);

-- ----- Module 4 -----
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: GA4 Basics', 'Dashboard, sessions, users.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a18 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a18, 'text', 'In GA4, what is the difference between a session and a user at a high level?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m4, NULL, 'GA4 Basics', 'Dashboard, sessions, users.', 'None', 3.00, 1, 1, @mkt_a18, 1, NOW(), NULL);
SET @mkt_t_ga4 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_ga4, 'Analyze report', 'Submit short notes interpreting one GA4 report screenshot or export (traffic trend, users, or sessions).', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Events & Conversions', 'Tracking actions.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a19 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a19, 'text', 'What is an event in GA4? Give two examples of events you would track on a lead-gen site.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m4, @mkt_t_ga4, 'Events & Conversions', 'Tracking actions.', 'GA4 basics', 3.00, 1, 1, @mkt_a19, 2, NOW(), NULL);
SET @mkt_t_events = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_events, 'Define events', 'List at least five events you would define for a marketing site and the business question each answers.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Traffic Sources', 'Organic, paid, direct.', 15, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a20 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a20, 'text', 'How would you explain “direct” traffic vs “organic search” traffic to a stakeholder?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m4, @mkt_t_ga4, 'Traffic Sources', 'Organic, paid, direct.', 'GA4 basics', 2.00, 1, 1, @mkt_a20, 3, NOW(), NULL);
SET @mkt_t_traffic = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_traffic, 'Identify sources', 'From a sample or hypothetical report, classify traffic into major channels and note one insight per channel.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Excel Basics', 'Pivot tables, formulas.', 25, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a21 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a21, 'text', 'What problem does a pivot table solve for marketers analyzing campaign export data?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m4, NULL, 'Excel Basics', 'Pivot tables, formulas.', 'Basic Excel', 3.00, 1, 1, @mkt_a21, 4, NOW(), NULL);
SET @mkt_t_excel = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_excel, 'Create pivot', 'Submit a screenshot or description of a pivot table summarizing sample campaign data.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Dashboard Creation', 'Visual reporting.', 25, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a22 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a22, 'text', 'Name three KPIs you would show on a weekly marketing dashboard and who would consume each.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m4, @mkt_t_excel, 'Dashboard Creation', 'Visual reporting.', 'Excel basics', 4.00, 1, 1, @mkt_a22, 5, NOW(), NULL);
SET @mkt_t_dash = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_dash, 'Create dashboard', 'Submit a mock dashboard (image or wireframe) with charts/tables and data sources labeled.', 0, NOW(), NULL);

-- ----- Module 5 -----
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: CRM Basics', 'Lead management.', 15, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a23 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a23, 'text', 'What is a sales funnel stage in a CRM and why is lead status important?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m5, NULL, 'CRM Basics', 'Lead management.', 'None', 2.00, 1, 1, @mkt_a23, 1, NOW(), NULL);
SET @mkt_t_crm = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_crm, 'Define CRM flow', 'Document a simple lead lifecycle (stages + owner handoffs) for a B2B example.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Email Marketing', 'Campaign basics.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a24 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a24, 'text', 'What is the purpose of a subject line preheader and how does it affect open rate?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m5, @mkt_t_crm, 'Email Marketing', 'Campaign basics.', 'CRM basics', 3.00, 1, 1, @mkt_a24, 2, NOW(), NULL);
SET @mkt_t_email = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_email, 'Create email', 'Submit copy for one promotional or nurture email (subject, preheader, body, CTA).', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Automation', 'Drip campaigns.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a25 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a25, 'text', 'What is a drip campaign and one trigger you might use to enter contacts into it?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m5, @mkt_t_email, 'Automation', 'Drip campaigns.', 'Email basics', 3.00, 1, 1, @mkt_a25, 3, NOW(), NULL);
SET @mkt_t_auto = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_auto, 'Design workflow', 'Draw or describe an automation workflow (3+ steps) for onboarding new leads.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Segmentation', 'Audience grouping.', 15, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a26 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a26, 'text', 'Give two criteria you could use to segment an email list and why each is actionable.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m5, @mkt_t_crm, 'Segmentation', 'Audience grouping.', 'CRM basics', 2.00, 1, 1, @mkt_a26, 4, NOW(), NULL);
SET @mkt_t_seg = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_seg, 'Segment users', 'Define three segments for a sample product and what message differs per segment.', 0, NOW(), NULL);

-- ----- Module 6 -----
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: CRO Basics', 'Conversion optimization.', 15, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a27 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a27, 'text', 'What is conversion rate optimization (CRO) and how does it differ from getting more traffic?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m6, @mkt_t_seo, 'CRO Basics', 'Conversion optimization.', 'SEO basics', 2.00, 1, 1, @mkt_a27, 1, NOW(), NULL);
SET @mkt_t_cro = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_cro, 'Define improvements', 'List five CRO hypotheses (problem + suggested fix) for a sample landing page.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Landing Pages', 'Structure, CTA.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a28 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a28, 'text', 'What are three elements of a high-converting landing page above the fold?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m6, @mkt_t_cro, 'Landing Pages', 'Structure, CTA.', 'CRO basics', 3.00, 1, 1, @mkt_a28, 2, NOW(), NULL);
SET @mkt_t_land = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_land, 'Audit page', 'Submit a short audit of one landing page (headline, proof, CTA, form friction).', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: A/B Testing', 'Testing variations.', 20, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a29 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a29, 'text', 'What is a null hypothesis in an A/B test and why do you need a sample size plan?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m6, @mkt_t_cro, 'A/B Testing', 'Testing variations.', 'CRO basics', 3.00, 1, 1, @mkt_a29, 3, NOW(), NULL);
SET @mkt_t_ab = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_ab, 'Suggest tests', 'Propose three A/B test ideas with metric, variant, and success criterion.', 0, NOW(), NULL);

-- ----- Final project -----
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Marketing Strategy', 'Full funnel planning.', 45, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a30 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a30, 'text', 'Summarize the four components of a one-page marketing strategy (goals, audience, channels, measurement).', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m7, @mkt_t_ab, 'Marketing Strategy', 'Full funnel planning.', 'All modules', 8.00, 1, 1, @mkt_a30, 1, NOW(), NULL);
SET @mkt_t_strat = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_strat, 'Create strategy doc', 'Submit a marketing strategy document: objectives, ICP, positioning, channels, budget outline.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Execution Plan', 'Ads + SEO + content.', 45, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a31 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a31, 'text', 'How would you sequence paid, SEO, and content initiatives in a 90-day plan for a new product launch?', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m7, @mkt_t_strat, 'Execution Plan', 'Ads + SEO + content.', 'All modules', 8.00, 1, 1, @mkt_a31, 2, NOW(), NULL);
SET @mkt_t_exec = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_exec, 'Create execution plan', 'Submit a 90-day execution plan with milestones, owners, and channel mix.', 0, NOW(), NULL);

INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('Knowledge check: Analytics & Reporting', 'Metrics + dashboard.', 40, 60.00, 0, 0, 1, 1, 1, 'active', NULL, NOW(), NULL);
SET @mkt_a32 = LAST_INSERT_ID();
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_a32, 'text', 'Describe how you would report “full funnel” performance from awareness to revenue in one slide outline.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL);

INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@mkt_m7, @mkt_t_exec, 'Analytics & Reporting', 'Metrics + dashboard.', 'All modules', 6.00, 1, 1, @mkt_a32, 3, NOW(), NULL);
SET @mkt_t_analytics = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@mkt_t_analytics, 'Build dashboard', 'Submit a reporting dashboard spec: metrics, dimensions, data sources, refresh cadence.', 0, NOW(), NULL);

-- End of marketing curriculum seed
