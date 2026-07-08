-- Day 4 - Customer Persona: questions + options for assessment_id = 7
-- Run after `ta_assessments` row with id 7 exists.
-- MCQ uses checkboxes; multi-answer items need every correct option marked is_correct=1.

START TRANSACTION;

SET @aid := 7;

-- 1
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','What is a customer persona?',1.00,NULL,NULL,50.00,NULL,1,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Real customer',0,1,NOW()),
(@q,'Fictional profile',1,2,NOW()),
(@q,'Random data',0,3,NOW()),
(@q,'Sales report',0,4,NOW());

-- 2
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which data is part of demographics?',1.00,NULL,NULL,50.00,NULL,2,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Age',0,1,NOW()),
(@q,'Income',0,2,NOW()),
(@q,'Gender',0,3,NOW()),
(@q,'All of the above',1,4,NOW());

-- 3
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which data is part of psychographics?',1.00,NULL,NULL,50.00,NULL,3,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Age',0,1,NOW()),
(@q,'Interests',1,2,NOW()),
(@q,'Gender',0,3,NOW()),
(@q,'Location',0,4,NOW());

-- 4
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which tool helps collect customer data?',1.00,NULL,NULL,50.00,NULL,4,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Surveys',0,1,NOW()),
(@q,'Analytics',0,2,NOW()),
(@q,'CRM',0,3,NOW()),
(@q,'All of the above',1,4,NOW());

-- 5
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','What is the main purpose of personas?',1.00,NULL,NULL,50.00,NULL,5,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Random targeting',0,1,NOW()),
(@q,'Better targeting',1,2,NOW()),
(@q,'Increase cost',0,3,NOW()),
(@q,'Ignore audience',0,4,NOW());

-- 6
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which improves engagement most?',1.00,NULL,NULL,50.00,NULL,6,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Mass marketing',0,1,NOW()),
(@q,'Personalized content',1,2,NOW()),
(@q,'No targeting',0,3,NOW()),
(@q,'Random ads',0,4,NOW());

-- 7
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which of the following is a pain point?',1.00,NULL,NULL,50.00,NULL,7,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Problem faced by user',1,1,NOW()),
(@q,'Age',0,2,NOW()),
(@q,'Income',0,3,NOW()),
(@q,'Gender',0,4,NOW());

-- 8
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which helps in persona creation?',1.00,NULL,NULL,50.00,NULL,8,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Data analysis',0,1,NOW()),
(@q,'Customer feedback',0,2,NOW()),
(@q,'Research',0,3,NOW()),
(@q,'All of the above',1,4,NOW());

-- 9
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which is NOT part of persona?',1.00,NULL,NULL,50.00,NULL,9,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Goals',0,1,NOW()),
(@q,'Interests',0,2,NOW()),
(@q,'Random guess',1,3,NOW()),
(@q,'Behavior',0,4,NOW());

-- 10
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which type of data is lifestyle?',1.00,NULL,NULL,50.00,NULL,10,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Demographic',0,1,NOW()),
(@q,'Psychographic',1,2,NOW()),
(@q,'Geographic',0,3,NOW()),
(@q,'Behavioral',0,4,NOW());

-- 11 — Goals, Pain points, Interests (not Random data)
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Select all correct: Persona elements include:',1.00,NULL,NULL,50.00,NULL,11,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Goals',1,1,NOW()),
(@q,'Pain points',1,2,NOW()),
(@q,'Interests',1,3,NOW()),
(@q,'Random data',0,4,NOW());

-- 12 — Surveys, Interviews, Analytics (not Guesswork)
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Select all correct: Data sources include:',1.00,NULL,NULL,50.00,NULL,12,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Surveys',1,1,NOW()),
(@q,'Interviews',1,2,NOW()),
(@q,'Analytics',1,3,NOW()),
(@q,'Guesswork',0,4,NOW());

-- 13
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which segmentation helps persona building?',1.00,NULL,NULL,50.00,NULL,13,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Demographic',0,1,NOW()),
(@q,'Psychographic',0,2,NOW()),
(@q,'Behavioral',0,3,NOW()),
(@q,'All of the above',1,4,NOW());

-- 14
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which improves conversion?',1.00,NULL,NULL,50.00,NULL,14,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Personalized marketing',1,1,NOW()),
(@q,'Random targeting',0,2,NOW()),
(@q,'No strategy',0,3,NOW()),
(@q,'Mass ads',0,4,NOW());

-- 15
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','What is a buyer persona also called?',1.00,NULL,NULL,50.00,NULL,15,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Customer profile',1,1,NOW()),
(@q,'Sales report',0,2,NOW()),
(@q,'Product data',0,3,NOW()),
(@q,'Market size',0,4,NOW());

-- 16
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which factor defines user behavior?',1.00,NULL,NULL,50.00,NULL,16,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Actions',0,1,NOW()),
(@q,'Usage pattern',0,2,NOW()),
(@q,'Buying habit',0,3,NOW()),
(@q,'All of the above',1,4,NOW());

-- 17
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which helps understand customer needs?',1.00,NULL,NULL,50.00,NULL,17,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Persona',1,1,NOW()),
(@q,'Random ads',0,2,NOW()),
(@q,'Guesswork',0,3,NOW()),
(@q,'Ignoring data',0,4,NOW());

-- 18
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which is important for personalization?',1.00,NULL,NULL,50.00,NULL,18,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Data',0,1,NOW()),
(@q,'Insights',0,2,NOW()),
(@q,'Personas',0,3,NOW()),
(@q,'All of the above',1,4,NOW());

-- 19
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which is first step in persona creation?',1.00,NULL,NULL,50.00,NULL,19,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Data collection',1,1,NOW()),
(@q,'Campaign',0,2,NOW()),
(@q,'Ads',0,3,NOW()),
(@q,'Sales',0,4,NOW());

-- 20
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which improves marketing ROI?',1.00,NULL,NULL,50.00,NULL,20,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Personas',1,1,NOW()),
(@q,'Random ads',0,2,NOW()),
(@q,'No targeting',0,3,NOW()),
(@q,'Guesswork',0,4,NOW());

COMMIT;
