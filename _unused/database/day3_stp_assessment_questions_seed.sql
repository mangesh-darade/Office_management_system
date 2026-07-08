-- Day 3 - STP: questions + options for assessment_id = 6
-- Run after `ta_assessments` row with id 6 exists.
-- MCQ uses checkboxes; multi-answer items need every correct option marked is_correct=1.

START TRANSACTION;

SET @aid := 6;

-- 1
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','What does STP stand for?',1.00,NULL,NULL,50.00,NULL,1,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Strategy Target Plan',0,1,NOW()),
(@q,'Segmentation Targeting Positioning',1,2,NOW()),
(@q,'Sales Target Plan',0,3,NOW()),
(@q,'Segment Target Product',0,4,NOW());

-- 2
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which type of segmentation is based on age, gender and income?',1.00,NULL,NULL,50.00,NULL,2,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Geographic',0,1,NOW()),
(@q,'Demographic',1,2,NOW()),
(@q,'Behavioral',0,3,NOW()),
(@q,'Psychographic',0,4,NOW());

-- 3
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which segmentation focuses on lifestyle and interests?',1.00,NULL,NULL,50.00,NULL,3,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Demographic',0,1,NOW()),
(@q,'Geographic',0,2,NOW()),
(@q,'Psychographic',1,3,NOW()),
(@q,'Behavioral',0,4,NOW());

-- 4
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Targeting refers to:',1.00,NULL,NULL,50.00,NULL,4,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Dividing market',0,1,NOW()),
(@q,'Selecting audience',1,2,NOW()),
(@q,'Positioning brand',0,3,NOW()),
(@q,'Selling product',0,4,NOW());

-- 5
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Positioning is about:',1.00,NULL,NULL,50.00,NULL,5,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Pricing',0,1,NOW()),
(@q,'Branding perception',1,2,NOW()),
(@q,'Distribution',0,3,NOW()),
(@q,'Sales',0,4,NOW());

-- 6
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which of the following is an example of behavioral segmentation?',1.00,NULL,NULL,50.00,NULL,6,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Age',0,1,NOW()),
(@q,'Location',0,2,NOW()),
(@q,'Purchase behavior',1,3,NOW()),
(@q,'Income',0,4,NOW());

-- 7
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which factors influence target market selection?',1.00,NULL,NULL,50.00,NULL,7,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Market size',0,1,NOW()),
(@q,'Competition',0,2,NOW()),
(@q,'Profitability',0,3,NOW()),
(@q,'All of the above',1,4,NOW());

-- 8
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which is NOT part of STP?',1.00,NULL,NULL,50.00,NULL,8,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Segmentation',0,1,NOW()),
(@q,'Targeting',0,2,NOW()),
(@q,'Promotion',1,3,NOW()),
(@q,'Positioning',0,4,NOW());

-- 9
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which strategy focuses on one specific group?',1.00,NULL,NULL,50.00,NULL,9,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Mass marketing',0,1,NOW()),
(@q,'Niche marketing',1,2,NOW()),
(@q,'Undifferentiated',0,3,NOW()),
(@q,'Random',0,4,NOW());

-- 10
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which of the following helps in positioning?',1.00,NULL,NULL,50.00,NULL,10,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'USP',0,1,NOW()),
(@q,'Branding',0,2,NOW()),
(@q,'Messaging',0,3,NOW()),
(@q,'All of the above',1,4,NOW());

-- 11 — select all: Demographic, Geographic, Psychographic (not Financial)
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Select all correct: Types of segmentation include:',1.00,NULL,NULL,50.00,NULL,11,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Demographic',1,1,NOW()),
(@q,'Geographic',1,2,NOW()),
(@q,'Psychographic',1,3,NOW()),
(@q,'Financial',0,4,NOW());

-- 12 — select all: Niche, Mass, Personalized (not Ignoring data)
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Select all correct: Good targeting strategies include:',1.00,NULL,NULL,50.00,NULL,12,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Niche targeting',1,1,NOW()),
(@q,'Mass targeting',1,2,NOW()),
(@q,'Personalized targeting',1,3,NOW()),
(@q,'Ignoring data',0,4,NOW());

-- 13
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which segmentation is based on location?',1.00,NULL,NULL,50.00,NULL,13,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Geographic',1,1,NOW()),
(@q,'Behavioral',0,2,NOW()),
(@q,'Demographic',0,3,NOW()),
(@q,'Psychographic',0,4,NOW());

-- 14
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which tool helps identify target audience?',1.00,NULL,NULL,50.00,NULL,14,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Analytics',0,1,NOW()),
(@q,'Surveys',0,2,NOW()),
(@q,'CRM',0,3,NOW()),
(@q,'All of the above',1,4,NOW());

-- 15
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','What is USP?',1.00,NULL,NULL,50.00,NULL,15,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Unique Selling Proposition',1,1,NOW()),
(@q,'User Sales Plan',0,2,NOW()),
(@q,'Unified Strategy Plan',0,3,NOW()),
(@q,'User Segment Profile',0,4,NOW());

-- 16
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which is an example of niche targeting?',1.00,NULL,NULL,50.00,NULL,16,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Selling to all',0,1,NOW()),
(@q,'Selling luxury watches to high-income individuals',1,2,NOW()),
(@q,'Random ads',0,3,NOW()),
(@q,'General audience',0,4,NOW());

-- 17
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which improves customer engagement most?',1.00,NULL,NULL,50.00,NULL,17,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Mass marketing',0,1,NOW()),
(@q,'Personalized targeting',1,2,NOW()),
(@q,'No segmentation',0,3,NOW()),
(@q,'Random targeting',0,4,NOW());

-- 18
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Positioning helps to:',1.00,NULL,NULL,50.00,NULL,18,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Reduce cost',0,1,NOW()),
(@q,'Increase awareness',0,2,NOW()),
(@q,'Build brand perception',1,3,NOW()),
(@q,'Ignore competition',0,4,NOW());

-- 19
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which factor is important for segmentation?',1.00,NULL,NULL,50.00,NULL,19,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Measurable',0,1,NOW()),
(@q,'Accessible',0,2,NOW()),
(@q,'Substantial',0,3,NOW()),
(@q,'All of the above',1,4,NOW());

-- 20
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which is the first step in STP?',1.00,NULL,NULL,50.00,NULL,20,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Targeting',0,1,NOW()),
(@q,'Positioning',0,2,NOW()),
(@q,'Segmentation',1,3,NOW()),
(@q,'Promotion',0,4,NOW());

COMMIT;
