-- Day 1 Marketing Assessment Seed (SQL only)
-- Uses ta_* tables

-- ==========================================================
-- Ensure latest columns exist (safe for old installations)
-- ==========================================================
SET @db := DATABASE();

-- ta_questions.text_keyword_pass_percent
SET @exists_tq := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'ta_questions'
    AND COLUMN_NAME = 'text_keyword_pass_percent'
);
SET @sql_tq := IF(
  @exists_tq = 0,
  'ALTER TABLE `ta_questions` ADD `text_keyword_pass_percent` DECIMAL(5,2) NOT NULL DEFAULT 50.00 AFTER `model_answer`',
  'SELECT 1'
);
PREPARE stmt_tq FROM @sql_tq; EXECUTE stmt_tq; DEALLOCATE PREPARE stmt_tq;

-- ta_user_answers.selected_option_ids
SET @exists_tua := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'ta_user_answers'
    AND COLUMN_NAME = 'selected_option_ids'
);
SET @sql_tua := IF(
  @exists_tua = 0,
  'ALTER TABLE `ta_user_answers` ADD `selected_option_ids` TEXT NULL AFTER `selected_option_id`',
  'SELECT 1'
);
PREPARE stmt_tua FROM @sql_tua; EXECUTE stmt_tua; DEALLOCATE PREPARE stmt_tua;

START TRANSACTION;

INSERT INTO `ta_assessments`
(`title`,`description`,`time_limit_minutes`,`passing_marks`,`randomize_questions`,`shuffle_options`,`max_attempts`,`allow_retake`,`show_correct_after_submit`,`status`,`created_by`,`created_at`,`updated_at`)
SELECT
  'Day 1 - What Is Marketing',
  'Day 1 assessment with descriptive, case study, and MCQ questions.',
  45, 60.00, 1, 1, 0, 1, 1, 'active', 1, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `ta_assessments` WHERE `title` = 'Day 1 - What Is Marketing'
);

-- Resolve assessment id robustly (works if script is re-run or partially run).
SET @aid := (SELECT `id` FROM `ta_assessments` WHERE `title` = 'Day 1 - What Is Marketing' ORDER BY `id` DESC LIMIT 1);

-- Create/link LMS module + topic (if LMS tables exist)
SET @has_tm := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'training_modules'
);
SET @has_tt := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'training_topics'
);
SET @sql_tm := IF(
  @has_tm = 1,
  'INSERT INTO `training_modules` (`title`,`description`,`status`,`sort_order`,`created_by`,`created_at`,`updated_at`) SELECT ''30-Day Marketing Curriculum'',''Marketing curriculum with daily linked assessments.'',''active'',1,1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `training_modules` WHERE `title` = ''30-Day Marketing Curriculum'')',
  'SELECT 1'
);
PREPARE stmt_tm FROM @sql_tm; EXECUTE stmt_tm; DEALLOCATE PREPARE stmt_tm;

SET @mid := IF(
  @has_tm = 1,
  (SELECT `id` FROM `training_modules` WHERE `title` = '30-Day Marketing Curriculum' ORDER BY `id` DESC LIMIT 1),
  NULL
);

INSERT INTO `training_topics`
(`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT
  @mid,
  NULL,
  'Day 1 - What Is Marketing',
  'Day 1 topic with linked assessment.',
  '',
  1.00,
  0,
  1,
  @aid,
  1,
  NOW(),
  NOW()
WHERE @has_tt = 1
  AND @mid IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `training_topics`
    WHERE `module_id` = @mid
      AND `name` = 'Day 1 - What Is Marketing'
  );

UPDATE `training_topics`
SET `assessment_id` = @aid, `has_assessment` = 1, `updated_at` = NOW()
WHERE @has_tt = 1
  AND `name` = 'Day 1 - What Is Marketing'
LIMIT 1;


-- =========================
-- Text (Descriptive / Case)
-- =========================
-- Safety: if someone runs this middle block only, recover/create @aid.
SET @aid := COALESCE(
  @aid,
  (SELECT `id` FROM `ta_assessments` WHERE `title` = 'Day 1 - What Is Marketing' ORDER BY `id` DESC LIMIT 1)
);

INSERT INTO `ta_questions`
(`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES
(@aid,'text','Explain how marketing connects the right product to the right customer at the right time with an example.',2.00,NULL,'customer needs,target audience,right time,value creation,problem solving,example,customer satisfaction',50.00,NULL,1,NOW(),NOW()),
(@aid,'text','Describe how digital marketing channels help businesses reach customers effectively.',2.00,NULL,'digital channels,search,social media,email,ads,online reach,targeting,engagement',50.00,NULL,2,NOW(),NOW()),
(@aid,'text','Explain why every marketing activity should be linked to a business goal.',2.00,NULL,'business goals,alignment,ROI,focus,efficiency,direction,performance measurement',50.00,NULL,3,NOW(),NOW()),
(@aid,'text','Discuss the 4 Ps of marketing and their role in building a strategy.',2.00,NULL,'product,price,place,promotion,marketing mix,strategy,customer value,integration',50.00,NULL,4,NOW(),NOW()),
(@aid,'text','Explain how measurability makes digital marketing more effective than traditional marketing.',2.00,NULL,'measurability,data tracking,analytics,metrics,ROI,optimization,performance',50.00,NULL,5,NOW(),NOW()),
(@aid,'text','A company launches a product but fails because it targeted the wrong audience. Identify the mistake and suggest a fix.',2.00,NULL,'wrong audience,targeting error,segmentation,customer mismatch,market research,repositioning',50.00,NULL,6,NOW(),NOW()),
(@aid,'text','A business uses social media ads but gets high engagement and no sales. Analyze the issue.',2.00,NULL,'low conversion,high engagement,wrong audience,weak value proposition,poor funnel,landing page issue',50.00,NULL,7,NOW(),NOW()),
(@aid,'text','A brand focuses only on advertising but ignores product quality. What will happen long-term?',2.00,NULL,'poor quality,customer dissatisfaction,negative reviews,loss of trust,brand damage,low retention',50.00,NULL,8,NOW(),NOW()),
(@aid,'text','A company tracks clicks but not purchases. What problem does this create?',2.00,NULL,'no conversion tracking,vanity metrics,missing ROI,poor decisions,funnel gap',50.00,NULL,9,NOW(),NOW()),
(@aid,'text','A premium brand reduces prices heavily and loses its image. What went wrong?',2.00,NULL,'pricing strategy,brand positioning,value dilution,premium perception,customer perception',50.00,NULL,10,NOW(),NOW());

-- ==========
-- MCQ Block
-- ==========

-- 11
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Marketing is best described as:',1.00,NULL,NULL,50.00,NULL,11,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Selling',0,1,NOW()),(@q,'Advertising',0,2,NOW()),(@q,'Connecting right product to right customer',1,3,NOW()),(@q,'Manufacturing',0,4,NOW());

-- 12
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which is a digital marketing channel?',1.00,NULL,NULL,50.00,NULL,12,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Newspaper',0,1,NOW()),(@q,'Billboard',0,2,NOW()),(@q,'Email',1,3,NOW()),(@q,'Flyers',0,4,NOW());

-- 13
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Why is digital marketing powerful?',1.00,NULL,NULL,50.00,NULL,13,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Free',0,1,NOW()),(@q,'Measurable',1,2,NOW()),(@q,'No strategy needed',0,3,NOW()),(@q,'Offline',0,4,NOW());

-- 14
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which is NOT a marketing goal?',1.00,NULL,NULL,50.00,NULL,14,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Sales',0,1,NOW()),(@q,'Awareness',0,2,NOW()),(@q,'Loyalty',0,3,NOW()),(@q,'Reduce quality',1,4,NOW());

-- 15
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Product in 4 Ps refers to:',1.00,NULL,NULL,50.00,NULL,15,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Cost',0,1,NOW()),(@q,'Offering',1,2,NOW()),(@q,'Ads',0,3,NOW()),(@q,'Distribution',0,4,NOW());

-- 16
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Price affects:',1.00,NULL,NULL,50.00,NULL,16,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Demand',0,1,NOW()),(@q,'Perception',0,2,NOW()),(@q,'Profit',0,3,NOW()),(@q,'All of the above',1,4,NOW());

-- 17
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Place refers to:',1.00,NULL,NULL,50.00,NULL,17,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Quality',0,1,NOW()),(@q,'Distribution',1,2,NOW()),(@q,'Ads',0,3,NOW()),(@q,'Price',0,4,NOW());

-- 18
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Promotion includes:',1.00,NULL,NULL,50.00,NULL,18,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Manufacturing',0,1,NOW()),(@q,'Advertising',1,2,NOW()),(@q,'Logistics',0,3,NOW()),(@q,'Pricing',0,4,NOW());

-- 19
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which metric shows real business impact?',1.00,NULL,NULL,50.00,NULL,19,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Likes',0,1,NOW()),(@q,'Views',0,2,NOW()),(@q,'Conversions',1,3,NOW()),(@q,'Impressions',0,4,NOW());

-- 20
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Tracking clicks and purchases helps in:',1.00,NULL,NULL,50.00,NULL,20,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Guessing',0,1,NOW()),(@q,'Decisions',1,2,NOW()),(@q,'Ignoring data',0,3,NOW()),(@q,'Avoid planning',0,4,NOW());

-- 21
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Traditional marketing is:',1.00,NULL,NULL,50.00,NULL,21,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Highly measurable',0,1,NOW()),(@q,'Less trackable',1,2,NOW()),(@q,'Digital',0,3,NOW()),(@q,'Automated',0,4,NOW());

-- 22
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Correct marketing example:',1.00,NULL,NULL,50.00,NULL,22,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Random selling',0,1,NOW()),(@q,'Ignoring needs',0,2,NOW()),(@q,'Targeted ads',1,3,NOW()),(@q,'No promotion',0,4,NOW());

-- 23
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','If no goals are defined, result is:',1.00,NULL,NULL,50.00,NULL,23,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Efficiency',0,1,NOW()),(@q,'Profit',0,2,NOW()),(@q,'Wasted effort',1,3,NOW()),(@q,'Clarity',0,4,NOW());

-- 24
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Measurable marketing means:',1.00,NULL,NULL,50.00,NULL,24,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Guesswork',0,1,NOW()),(@q,'Data tracking',1,2,NOW()),(@q,'Branding only',0,3,NOW()),(@q,'Offline',0,4,NOW());

-- 25
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Poor strategy example:',1.00,NULL,NULL,50.00,NULL,25,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Clear audience',0,1,NOW()),(@q,'No audience',1,2,NOW()),(@q,'Data tracking',0,3,NOW()),(@q,'Goals',0,4,NOW());

-- 26
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Why targeting is important?',1.00,NULL,NULL,50.00,NULL,26,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Saves cost',0,1,NOW()),(@q,'Improves relevance',0,2,NOW()),(@q,'Increases conversions',0,3,NOW()),(@q,'All',1,4,NOW());

-- 27 (multi-correct A,B,C)
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which are digital marketing channels?',1.00,NULL,NULL,50.00,NULL,27,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Email',1,1,NOW()),(@q,'Social Media',1,2,NOW()),(@q,'Search Engines',1,3,NOW()),(@q,'Flyers',0,4,NOW());

-- 28 (multi-correct A,B,C)
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which metrics indicate performance?',1.00,NULL,NULL,50.00,NULL,28,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Clicks',1,1,NOW()),(@q,'Conversions',1,2,NOW()),(@q,'ROI',1,3,NOW()),(@q,'Color of ad',0,4,NOW());

-- 29 (multi-correct A,B,D)
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which are part of 4 Ps?',1.00,NULL,NULL,50.00,NULL,29,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Product',1,1,NOW()),(@q,'Price',1,2,NOW()),(@q,'People',0,3,NOW()),(@q,'Promotion',1,4,NOW());

-- 30 (multi-correct A,B,D)
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid,'mcq','Which actions improve marketing success?',1.00,NULL,NULL,50.00,NULL,30,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Targeting',1,1,NOW()),(@q,'Tracking',1,2,NOW()),(@q,'Ignoring data',0,3,NOW()),(@q,'Optimization',1,4,NOW());

COMMIT;

-- ==========================================================
-- Day 2 Marketing Assessment Seed (MCQ only)
-- ==========================================================
START TRANSACTION;

INSERT INTO `ta_assessments`
(`title`,`description`,`time_limit_minutes`,`passing_marks`,`randomize_questions`,`shuffle_options`,`max_attempts`,`allow_retake`,`show_correct_after_submit`,`status`,`created_by`,`created_at`,`updated_at`)
SELECT
  'Day 2 - Marketing Funnel',
  'Day 2 assessment focused on funnel stages, metrics, and optimization.',
  45, 60.00, 1, 1, 0, 1, 1, 'active', 5, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `ta_assessments` WHERE `title` = 'Day 2 - Marketing Funnel'
);

SET @aid2 := (SELECT `id` FROM `ta_assessments` WHERE `title` = 'Day 2 - Marketing Funnel' ORDER BY `id` DESC LIMIT 1);

-- Q1
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which stage of the funnel focuses on attracting new users?',1.00,NULL,NULL,50.00,NULL,1,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Conversion',0,1,NOW()),(@q,'Retention',0,2,NOW()),(@q,'Awareness',1,3,NOW()),(@q,'Loyalty',0,4,NOW());

-- Q2
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which metric is most relevant in the awareness stage?',1.00,NULL,NULL,50.00,NULL,2,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Conversion Rate',0,1,NOW()),(@q,'Impressions',1,2,NOW()),(@q,'Revenue',0,3,NOW()),(@q,'Retention Rate',0,4,NOW());

-- Q3
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which of the following are part of the consideration stage?',1.00,NULL,NULL,50.00,NULL,3,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Product comparisons',0,1,NOW()),(@q,'Blog reading',0,2,NOW()),(@q,'Email nurturing',0,3,NOW()),(@q,'All of the above',1,4,NOW());

-- Q4
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','What is the main goal of the conversion stage?',1.00,NULL,NULL,50.00,NULL,4,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Generate traffic',0,1,NOW()),(@q,'Build awareness',0,2,NOW()),(@q,'Drive purchase/action',1,3,NOW()),(@q,'Retain customers',0,4,NOW());

-- Q5
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which channels are most effective for awareness?',1.00,NULL,NULL,50.00,NULL,5,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'SEO',0,1,NOW()),(@q,'Social Media',0,2,NOW()),(@q,'Paid Ads',0,3,NOW()),(@q,'All of the above',1,4,NOW());

-- Q6
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which of the following help in retention?',1.00,NULL,NULL,50.00,NULL,6,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Email marketing',0,1,NOW()),(@q,'Loyalty programs',0,2,NOW()),(@q,'Customer support',0,3,NOW()),(@q,'All of the above',1,4,NOW());

-- Q7
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','CTR stands for:',1.00,NULL,NULL,50.00,NULL,7,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Click Through Rate',1,1,NOW()),(@q,'Customer Total Revenue',0,2,NOW()),(@q,'Conversion Tracking Rate',0,3,NOW()),(@q,'Cost To Retain',0,4,NOW());

-- Q8
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which stage uses retargeting ads most effectively?',1.00,NULL,NULL,50.00,NULL,8,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Awareness',0,1,NOW()),(@q,'Consideration',0,2,NOW()),(@q,'Conversion',1,3,NOW()),(@q,'Retention',0,4,NOW());

-- Q9
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which are examples of conversion actions?',1.00,NULL,NULL,50.00,NULL,9,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Purchase',0,1,NOW()),(@q,'Signup',0,2,NOW()),(@q,'Download',0,3,NOW()),(@q,'All of the above',1,4,NOW());

-- Q10
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which metric measures customer loyalty?',1.00,NULL,NULL,50.00,NULL,10,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Bounce Rate',0,1,NOW()),(@q,'Retention Rate',1,2,NOW()),(@q,'Impressions',0,3,NOW()),(@q,'CTR',0,4,NOW());

-- Q11
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which tools help in awareness?',1.00,NULL,NULL,50.00,NULL,11,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Google Ads',0,1,NOW()),(@q,'Facebook Ads',0,2,NOW()),(@q,'SEO tools',0,3,NOW()),(@q,'All of the above',1,4,NOW());

-- Q12 (multi-correct A,B,C)
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Select all correct: Which belong to middle funnel?',1.00,NULL,NULL,50.00,NULL,12,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Email campaigns',1,1,NOW()),(@q,'Case studies',1,2,NOW()),(@q,'Product demos',1,3,NOW()),(@q,'Brand ads',0,4,NOW());

-- Q13 (multi-correct A,B,C)
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Select all correct: Conversion optimization techniques include',1.00,NULL,NULL,50.00,NULL,13,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'A/B Testing',1,1,NOW()),(@q,'CTA improvement',1,2,NOW()),(@q,'Page speed optimization',1,3,NOW()),(@q,'Ignoring UX',0,4,NOW());

-- Q14
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','What is a CTA?',1.00,NULL,NULL,50.00,NULL,14,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Click Tracking Analysis',0,1,NOW()),(@q,'Call To Action',1,2,NOW()),(@q,'Customer Target Area',0,3,NOW()),(@q,'Conversion Tracking Action',0,4,NOW());

-- Q15
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which stage focuses on long-term relationship?',1.00,NULL,NULL,50.00,NULL,15,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Awareness',0,1,NOW()),(@q,'Consideration',0,2,NOW()),(@q,'Conversion',0,3,NOW()),(@q,'Retention',1,4,NOW());

-- Q16
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which funnel stage includes testimonials and reviews?',1.00,NULL,NULL,50.00,NULL,16,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Awareness',0,1,NOW()),(@q,'Consideration',1,2,NOW()),(@q,'Conversion',0,3,NOW()),(@q,'Retention',0,4,NOW());

-- Q17
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which is NOT a funnel stage?',1.00,NULL,NULL,50.00,NULL,17,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Awareness',0,1,NOW()),(@q,'Consideration',0,2,NOW()),(@q,'Distribution',1,3,NOW()),(@q,'Retention',0,4,NOW());

-- Q18
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which strategy increases repeat purchase?',1.00,NULL,NULL,50.00,NULL,18,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Discounts',0,1,NOW()),(@q,'Loyalty programs',0,2,NOW()),(@q,'Email follow-ups',0,3,NOW()),(@q,'All of the above',1,4,NOW());

-- Q19
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which platform is commonly used for retargeting?',1.00,NULL,NULL,50.00,NULL,19,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Google Ads',0,1,NOW()),(@q,'Facebook Ads',0,2,NOW()),(@q,'Both',1,3,NOW()),(@q,'None',0,4,NOW());

-- Q20
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
VALUES (@aid2,'mcq','Which factor impacts conversion most?',1.00,NULL,NULL,50.00,NULL,20,NOW(),NOW());
SET @q := LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES
(@q,'Website UX',0,1,NOW()),(@q,'CTA',0,2,NOW()),(@q,'Trust signals',0,3,NOW()),(@q,'All of the above',1,4,NOW());

COMMIT;

