# Day 1 Marketing Assessment Seed (Training & Assessment)

This document provides:

- Full table/column mapping used by this seed
- Ready SQL insert script for your provided Day 1 dataset
- Support for:
  - Descriptive / Case Study as `text` question type
  - MCQ single-correct and multi-correct
  - Keyword scoring via `model_answer` + `text_keyword_pass_percent`

> Note: App can run on legacy table names (`assessments`, `questions`, `question_options`, etc.) or prefixed names (`ta_*`).  
> SQL below uses `ta_*`. For legacy installs, replace table names accordingly.

---

## Tables and Columns Used

### `ta_assessments`

| Column | Used Value |
|---|---|
| `id` | auto |
| `title` | `Day 1 - What Is Marketing` |
| `description` | Day 1 mixed question bank |
| `time_limit_minutes` | 45 |
| `passing_marks` | 60.00 |
| `randomize_questions` | 1 |
| `shuffle_options` | 1 |
| `max_attempts` | 0 |
| `allow_retake` | 1 |
| `show_correct_after_submit` | 1 |
| `status` | `active` |
| `created_by` | 1 (change as needed) |
| `created_at` | `NOW()` |
| `updated_at` | `NOW()` |

### `ta_questions`

| Column | Used Value |
|---|---|
| `id` | auto |
| `assessment_id` | `@aid` |
| `question_type` | `text` or `mcq` |
| `question_text` | from your dataset |
| `points` | 2.00 (text), 1.00 (mcq) |
| `coding_language` | `NULL` |
| `model_answer` | keywords for `text`; `NULL` for `mcq` |
| `text_keyword_pass_percent` | 50.00 for text |
| `coding_expected_output` | `NULL` |
| `sort_order` | sequence number |
| `created_at` | `NOW()` |
| `updated_at` | `NOW()` |

### `ta_question_options` (MCQ only)

| Column | Used Value |
|---|---|
| `id` | auto |
| `question_id` | `@q` |
| `option_text` | option label |
| `is_correct` | 1 or 0 (multi-correct supported) |
| `sort_order` | 1..N |
| `created_at` | `NOW()` |

### Optional LMS Link: `training_topics`

If you want this assessment linked to LMS topic row:

| Column | Used Value |
|---|---|
| `assessment_id` | `@aid` |
| `name` | `Day 1 - What Is Marketing` |
| `has_assessment` | 1 |

---

## SQL Insert Script

```sql
START TRANSACTION;

INSERT INTO `ta_assessments`
(`title`,`description`,`time_limit_minutes`,`passing_marks`,`randomize_questions`,`shuffle_options`,`max_attempts`,`allow_retake`,`show_correct_after_submit`,`status`,`created_by`,`created_at`,`updated_at`)
VALUES
('Day 1 - What Is Marketing',
 'Day 1 assessment with descriptive, case study, and MCQ questions.',
 45, 60.00, 1, 1, 0, 1, 1, 'active', 1, NOW(), NOW());

SET @aid := LAST_INSERT_ID();

-- Optional topic link (safe update if topic already exists)
UPDATE `training_topics`
SET `assessment_id` = @aid, `has_assessment` = 1, `updated_at` = NOW()
WHERE `name` = 'Day 1 - What Is Marketing'
LIMIT 1;

-- =========================
-- Text (Descriptive / Case)
-- =========================
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
```

---

## Quick Notes

- Descriptive and Case Study are inserted as `question_type='text'`.
- `Keywords` column from your sheet is inserted into `model_answer`.
- With your latest code update, keyword scoring threshold is per-question via `text_keyword_pass_percent`.
- Multi-correct MCQ rows are already marked by setting multiple options `is_correct=1`.

