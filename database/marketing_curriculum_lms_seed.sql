-- =============================================================================
-- Marketing Curriculum Seed Data
-- Populates training_modules, training_topics, assignments, ta_assessments, ta_questions, ta_question_options
-- =============================================================================

SET NAMES utf8mb4;

-- ==========================================
-- MODULE 1: Introduction to Marketing
-- ==========================================
INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_at`) 
VALUES ('Introduction to Marketing', 'Fundamentals, Funnels, STP, Personas, Competitors, and KPIs.', 'active', 1, NOW());
SET @mod1 = LAST_INSERT_ID();

-- ---------------------------------------------------------
-- TOPIC 1: Introduction to Marketing
-- ---------------------------------------------------------
INSERT INTO `ta_assessments` (`title`, `time_limit_minutes`, `passing_marks`, `status`, `created_at`) 
VALUES ('Introduction to Marketing Quiz', 15, 50.00, 'active', NOW());
SET @aid = LAST_INSERT_ID();

-- Q1
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `sort_order`, `created_at`) 
VALUES (@aid, 'mcq', 'What is the main goal of marketing?', 1, NOW());
SET @qid = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES 
(@qid, 'Selling products', 0, 1, NOW()), (@qid, 'Creating awareness', 1, 2, NOW()), (@qid, 'Manufacturing', 0, 3, NOW()), (@qid, 'Distribution', 0, 4, NOW());

-- Q2
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `sort_order`, `created_at`) 
VALUES (@aid, 'mcq', 'Marketing is different from sales because:', 2, NOW());
SET @qid = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES 
(@qid, 'Sales is long-term', 0, 1, NOW()), (@qid, 'Marketing builds demand', 1, 2, NOW()), (@qid, 'Sales builds brand', 0, 3, NOW()), (@qid, 'None', 0, 4, NOW());

INSERT INTO `training_topics` (`module_id`, `name`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`) 
VALUES (@mod1, 'Introduction to Marketing', 1.00, 1, 1, @aid, 1, NOW());
SET @tid = LAST_INSERT_ID();

INSERT INTO `assignments` (`topic_id`, `name`, `details`, `created_at`) 
VALUES (@tid, 'Business Marketing & Sales Analysis', 'Pick a real business (local shop / online brand)\nIdentify:\n- What product they sell\n- Who is their target audience\n- Difference between their marketing & sales approach', NOW());


-- ---------------------------------------------------------
-- TOPIC 2: Marketing Funnel
-- ---------------------------------------------------------
INSERT INTO `ta_assessments` (`title`, `time_limit_minutes`, `passing_marks`, `status`, `created_at`) 
VALUES ('Marketing Funnel Quiz', 15, 50.00, 'active', NOW());
SET @aid = LAST_INSERT_ID();

-- Q1
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `sort_order`, `created_at`) 
VALUES (@aid, 'mcq', 'First stage of funnel:', 1, NOW());
SET @qid = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES 
(@qid, 'Awareness', 1, 1, NOW()), (@qid, 'Conversion', 0, 2, NOW()), (@qid, 'Retention', 0, 3, NOW()), (@qid, 'Sales', 0, 4, NOW());

-- Q2
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `sort_order`, `created_at`) 
VALUES (@aid, 'mcq', 'Retention means:', 2, NOW());
SET @qid = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES 
(@qid, 'Getting new users', 0, 1, NOW()), (@qid, 'Keeping existing users', 1, 2, NOW()), (@qid, 'Selling more ads', 0, 3, NOW()), (@qid, 'None', 0, 4, NOW());

INSERT INTO `training_topics` (`module_id`, `name`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`) 
VALUES (@mod1, 'Marketing Funnel', 1.00, 1, 1, @aid, 2, NOW());
SET @tid = LAST_INSERT_ID();

INSERT INTO `assignments` (`topic_id`, `name`, `details`, `created_at`) 
VALUES (@tid, 'Marketing Funnel Creation', 'Create a funnel for:\nAny product (e.g., Mobile, Clothing)\nDefine:\n- Awareness strategy\n- Consideration strategy\n- Conversion strategy\n- Retention strategy', NOW());


-- ---------------------------------------------------------
-- TOPIC 3: STP Model
-- ---------------------------------------------------------
INSERT INTO `ta_assessments` (`title`, `time_limit_minutes`, `passing_marks`, `status`, `created_at`) 
VALUES ('STP Model Quiz', 10, 100.00, 'active', NOW());
SET @aid = LAST_INSERT_ID();

-- Q1
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `sort_order`, `created_at`) 
VALUES (@aid, 'mcq', 'STP stands for:', 1, NOW());
SET @qid = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES 
(@qid, 'Segmentation, Targeting, Positioning', 1, 1, NOW()), (@qid, 'Strategy, Target, Planning', 0, 2, NOW()), (@qid, 'Sales, Tracking, Promotion', 0, 3, NOW()), (@qid, 'None', 0, 4, NOW());

INSERT INTO `training_topics` (`module_id`, `name`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`) 
VALUES (@mod1, 'STP Model', 1.00, 1, 1, @aid, 3, NOW());
SET @tid = LAST_INSERT_ID();

INSERT INTO `assignments` (`topic_id`, `name`, `details`, `created_at`) 
VALUES (@tid, 'STP Model Implementation', 'Choose a product and:\n- Define 3 segments\n- Select 1 target segment\n- Write positioning statement', NOW());


-- ---------------------------------------------------------
-- TOPIC 4: Customer Persona
-- ---------------------------------------------------------
INSERT INTO `ta_assessments` (`title`, `time_limit_minutes`, `passing_marks`, `status`, `created_at`) 
VALUES ('Customer Persona Quiz', 10, 100.00, 'active', NOW());
SET @aid = LAST_INSERT_ID();

-- Q1
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `sort_order`, `created_at`) 
VALUES (@aid, 'mcq', 'Persona represents:', 1, NOW());
SET @qid = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES 
(@qid, 'Real user behavior', 1, 1, NOW()), (@qid, 'Random data', 0, 2, NOW()), (@qid, 'Sales report', 0, 3, NOW()), (@qid, 'None', 0, 4, NOW());

INSERT INTO `training_topics` (`module_id`, `name`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`) 
VALUES (@mod1, 'Customer Persona', 1.00, 1, 1, @aid, 4, NOW());
SET @tid = LAST_INSERT_ID();

INSERT INTO `assignments` (`topic_id`, `name`, `details`, `created_at`) 
VALUES (@tid, 'Customer Persona Creation', 'Create 1 customer persona including:\n- Name\n- Age\n- Job\n- Pain points\n- Goals', NOW());


-- ---------------------------------------------------------
-- TOPIC 5: Competitor Analysis
-- ---------------------------------------------------------
INSERT INTO `ta_assessments` (`title`, `time_limit_minutes`, `passing_marks`, `status`, `created_at`) 
VALUES ('Competitor Analysis Quiz', 10, 100.00, 'active', NOW());
SET @aid = LAST_INSERT_ID();

-- Q1
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `sort_order`, `created_at`) 
VALUES (@aid, 'mcq', 'SWOT stands for:', 1, NOW());
SET @qid = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES 
(@qid, 'Strength, Weakness, Opportunity, Threat', 1, 1, NOW()), (@qid, 'Sales, Work, Output, Target', 0, 2, NOW()), (@qid, 'None', 0, 3, NOW());

INSERT INTO `training_topics` (`module_id`, `name`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`) 
VALUES (@mod1, 'Competitor Analysis', 1.00, 1, 1, @aid, 5, NOW());
SET @tid = LAST_INSERT_ID();

INSERT INTO `assignments` (`topic_id`, `name`, `details`, `created_at`) 
VALUES (@tid, 'SWOT Analysis Creation', 'Select 2 competitors\nCreate SWOT analysis', NOW());


-- ---------------------------------------------------------
-- TOPIC 6: KPIs & Metrics
-- ---------------------------------------------------------
INSERT INTO `ta_assessments` (`title`, `time_limit_minutes`, `passing_marks`, `status`, `created_at`) 
VALUES ('KPIs & Metrics Quiz', 10, 100.00, 'active', NOW());
SET @aid = LAST_INSERT_ID();

-- Q1
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `sort_order`, `created_at`) 
VALUES (@aid, 'mcq', 'CAC means:', 1, NOW());
SET @qid = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES 
(@qid, 'Customer Acquisition Cost', 1, 1, NOW()), (@qid, 'Customer Average Cost', 0, 2, NOW()), (@qid, 'None', 0, 3, NOW());

INSERT INTO `training_topics` (`module_id`, `name`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`) 
VALUES (@mod1, 'KPIs & Metrics', 1.00, 1, 1, @aid, 6, NOW());
SET @tid = LAST_INSERT_ID();

INSERT INTO `assignments` (`topic_id`, `name`, `details`, `created_at`) 
VALUES (@tid, 'Calculate Marketing Metrics', 'Calculate:\n- CAC (given sample data)\n- ROI\n- LTV', NOW());


-- ==========================================
-- MODULE 2: Digital Advertising
-- ==========================================
INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_at`) 
VALUES ('Digital Advertising', 'Meta Ads, Google Ads, Keyword Research, Copywriting.', 'active', 2, NOW());
SET @mod2 = LAST_INSERT_ID();


-- ---------------------------------------------------------
-- TOPIC 1: Meta Ads Basics
-- ---------------------------------------------------------
INSERT INTO `ta_assessments` (`title`, `time_limit_minutes`, `passing_marks`, `status`, `created_at`) 
VALUES ('Meta Ads Basics Quiz', 10, 100.00, 'active', NOW());
SET @aid = LAST_INSERT_ID();

-- Q1
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `sort_order`, `created_at`) 
VALUES (@aid, 'mcq', 'Campaign level defines:', 1, NOW());
SET @qid = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES 
(@qid, 'Objective', 1, 1, NOW()), (@qid, 'Budget', 0, 2, NOW()), (@qid, 'Audience', 0, 3, NOW());

INSERT INTO `training_topics` (`module_id`, `name`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`) 
VALUES (@mod2, 'Meta Ads Basics', 1.00, 1, 1, @aid, 1, NOW());
SET @tid = LAST_INSERT_ID();

INSERT INTO `assignments` (`topic_id`, `name`, `details`, `created_at`) 
VALUES (@tid, 'Meta Ads dummy campaign', 'Create dummy campaign:\n- Campaign objective\n- Ad set (audience)\n- Ad creative (text + image idea)', NOW());


-- ---------------------------------------------------------
-- TOPIC 2: Audience Targeting
-- ---------------------------------------------------------
INSERT INTO `ta_assessments` (`title`, `time_limit_minutes`, `passing_marks`, `status`, `created_at`) 
VALUES ('Audience Targeting Quiz', 10, 100.00, 'active', NOW());
SET @aid = LAST_INSERT_ID();

-- Q1
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `sort_order`, `created_at`) 
VALUES (@aid, 'mcq', 'Lookalike audience is:', 1, NOW());
SET @qid = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES 
(@qid, 'New users similar to existing users', 1, 1, NOW()), (@qid, 'Random users', 0, 2, NOW()), (@qid, 'Competitors', 0, 3, NOW());

INSERT INTO `training_topics` (`module_id`, `name`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`) 
VALUES (@mod2, 'Audience Targeting', 1.00, 1, 1, @aid, 2, NOW());
SET @tid = LAST_INSERT_ID();

INSERT INTO `assignments` (`topic_id`, `name`, `details`, `created_at`) 
VALUES (@tid, 'Define Custom/Lookalike Audience', 'Define:\n- Custom audience\n- Lookalike audience', NOW());


-- ---------------------------------------------------------
-- TOPIC 3: Google Ads Basics
-- ---------------------------------------------------------
INSERT INTO `ta_assessments` (`title`, `time_limit_minutes`, `passing_marks`, `status`, `created_at`) 
VALUES ('Google Ads Basics Quiz', 10, 100.00, 'active', NOW());
SET @aid = LAST_INSERT_ID();

-- Q1
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `sort_order`, `created_at`) 
VALUES (@aid, 'mcq', 'Google Ads mainly uses:', 1, NOW());
SET @qid = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES 
(@qid, 'Keywords', 1, 1, NOW()), (@qid, 'Images', 0, 2, NOW()), (@qid, 'Videos', 0, 3, NOW());

INSERT INTO `training_topics` (`module_id`, `name`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`) 
VALUES (@mod2, 'Google Ads Basics', 1.00, 1, 1, @aid, 3, NOW());
SET @tid = LAST_INSERT_ID();

INSERT INTO `assignments` (`topic_id`, `name`, `details`, `created_at`) 
VALUES (@tid, 'Google Ads Ad Creation', 'Create:\n- 5 keywords\n- 1 ad headline\n- 1 description', NOW());


-- ---------------------------------------------------------
-- TOPIC 4: Keyword Research
-- ---------------------------------------------------------
INSERT INTO `ta_assessments` (`title`, `time_limit_minutes`, `passing_marks`, `status`, `created_at`) 
VALUES ('Keyword Research Quiz', 10, 100.00, 'active', NOW());
SET @aid = LAST_INSERT_ID();

-- Q1
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `sort_order`, `created_at`) 
VALUES (@aid, 'mcq', 'Long-tail keywords:', 1, NOW());
SET @qid = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES 
(@qid, 'More specific', 1, 1, NOW()), (@qid, 'Generic', 0, 2, NOW()), (@qid, 'Short', 0, 3, NOW());

INSERT INTO `training_topics` (`module_id`, `name`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`) 
VALUES (@mod2, 'Keyword Research', 1.00, 1, 1, @aid, 4, NOW());
SET @tid = LAST_INSERT_ID();

INSERT INTO `assignments` (`topic_id`, `name`, `details`, `created_at`) 
VALUES (@tid, 'Keyword Research Extraction', 'Find:\n- 5 short-tail keywords\n- 5 long-tail keywords', NOW());


-- ---------------------------------------------------------
-- TOPIC 5: Ad Copywriting
-- ---------------------------------------------------------
INSERT INTO `ta_assessments` (`title`, `time_limit_minutes`, `passing_marks`, `status`, `created_at`) 
VALUES ('Ad Copywriting Quiz', 10, 100.00, 'active', NOW());
SET @aid = LAST_INSERT_ID();

-- Q1
INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `sort_order`, `created_at`) 
VALUES (@aid, 'mcq', 'CTA means:', 1, NOW());
SET @qid = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES 
(@qid, 'Call To Action', 1, 1, NOW()), (@qid, 'Customer Target Area', 0, 2, NOW()), (@qid, 'Click Tracking Ads', 0, 3, NOW());

INSERT INTO `training_topics` (`module_id`, `name`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`) 
VALUES (@mod2, 'Ad Copywriting', 1.00, 1, 1, @aid, 5, NOW());
SET @tid = LAST_INSERT_ID();

INSERT INTO `assignments` (`topic_id`, `name`, `details`, `created_at`) 
VALUES (@tid, 'Ad Copy Creation', 'Write:\n- 1 headline\n- 1 CTA\n- 1 ad description', NOW());
