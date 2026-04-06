-- Marketing 30-day curriculum + topics + Day 1/Day 2 assessments
-- Safe to re-run (uses NOT EXISTS patterns for module/topic/assessment).

SET @db := DATABASE();

-- Ensure latest TA columns exist
SET @exists_tq := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'ta_questions' AND COLUMN_NAME = 'text_keyword_pass_percent'
);
SET @sql_tq := IF(@exists_tq = 0,
  'ALTER TABLE `ta_questions` ADD `text_keyword_pass_percent` DECIMAL(5,2) NOT NULL DEFAULT 50.00 AFTER `model_answer`',
  'SELECT 1'
);
PREPARE s1 FROM @sql_tq; EXECUTE s1; DEALLOCATE PREPARE s1;

SET @exists_tua := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'ta_user_answers' AND COLUMN_NAME = 'selected_option_ids'
);
SET @sql_tua := IF(@exists_tua = 0,
  'ALTER TABLE `ta_user_answers` ADD `selected_option_ids` TEXT NULL AFTER `selected_option_id`',
  'SELECT 1'
);
PREPARE s2 FROM @sql_tua; EXECUTE s2; DEALLOCATE PREPARE s2;

START TRANSACTION;

-- =====================
-- Modules
-- =====================
INSERT INTO `training_modules` (`title`,`description`,`status`,`sort_order`,`created_by`,`created_at`,`updated_at`)
SELECT 'Marketing Fundamentals & Strategy','Days 1-7 core marketing foundations.','active',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `training_modules` WHERE `title` = 'Marketing Fundamentals & Strategy');

INSERT INTO `training_modules` (`title`,`description`,`status`,`sort_order`,`created_by`,`created_at`,`updated_at`)
SELECT 'Performance Marketing & Paid Ads','Days 8-14 paid advertising and budgeting.','active',2,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `training_modules` WHERE `title` = 'Performance Marketing & Paid Ads');

INSERT INTO `training_modules` (`title`,`description`,`status`,`sort_order`,`created_by`,`created_at`,`updated_at`)
SELECT 'Content Marketing & SEO','Days 15-21 SEO and content strategy.','active',3,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `training_modules` WHERE `title` = 'Content Marketing & SEO');

INSERT INTO `training_modules` (`title`,`description`,`status`,`sort_order`,`created_by`,`created_at`,`updated_at`)
SELECT 'Analytics & Data (GA4 + Excel)','Days 22-24 analytics, events, dashboards.','active',4,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `training_modules` WHERE `title` = 'Analytics & Data (GA4 + Excel)');

INSERT INTO `training_modules` (`title`,`description`,`status`,`sort_order`,`created_by`,`created_at`,`updated_at`)
SELECT 'CRM, WhatsApp & Email Marketing','Days 25-27 CRM and lifecycle messaging.','active',5,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `training_modules` WHERE `title` = 'CRM, WhatsApp & Email Marketing');

INSERT INTO `training_modules` (`title`,`description`,`status`,`sort_order`,`created_by`,`created_at`,`updated_at`)
SELECT 'CRO — Conversion Rate Optimization','Days 28-30 conversion optimization and UX.','active',6,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `training_modules` WHERE `title` = 'CRO — Conversion Rate Optimization');

SET @m1 := (SELECT id FROM training_modules WHERE title='Marketing Fundamentals & Strategy' ORDER BY id DESC LIMIT 1);
SET @m2 := (SELECT id FROM training_modules WHERE title='Performance Marketing & Paid Ads' ORDER BY id DESC LIMIT 1);
SET @m3 := (SELECT id FROM training_modules WHERE title='Content Marketing & SEO' ORDER BY id DESC LIMIT 1);
SET @m4 := (SELECT id FROM training_modules WHERE title='Analytics & Data (GA4 + Excel)' ORDER BY id DESC LIMIT 1);
SET @m5 := (SELECT id FROM training_modules WHERE title='CRM, WhatsApp & Email Marketing' ORDER BY id DESC LIMIT 1);
SET @m6 := (SELECT id FROM training_modules WHERE title='CRO — Conversion Rate Optimization' ORDER BY id DESC LIMIT 1);

-- =====================
-- Assessments (Day 1/2)
-- =====================
INSERT INTO `ta_assessments`
(`title`,`description`,`time_limit_minutes`,`passing_marks`,`randomize_questions`,`shuffle_options`,`max_attempts`,`allow_retake`,`show_correct_after_submit`,`status`,`created_by`,`created_at`,`updated_at`)
SELECT 'Day 1 - What Is Marketing','Day 1 assessment with descriptive + MCQ.',45,60.00,1,1,0,1,1,'active',1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `ta_assessments` WHERE `title`='Day 1 - What Is Marketing');
SET @aid1 := (SELECT id FROM ta_assessments WHERE title='Day 1 - What Is Marketing' ORDER BY id DESC LIMIT 1);

INSERT INTO `ta_assessments`
(`title`,`description`,`time_limit_minutes`,`passing_marks`,`randomize_questions`,`shuffle_options`,`max_attempts`,`allow_retake`,`show_correct_after_submit`,`status`,`created_by`,`created_at`,`updated_at`)
SELECT 'Day 2 - Marketing Funnel','Day 2 assessment with descriptive questions.',30,60.00,1,1,0,1,1,'active',1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `ta_assessments` WHERE `title`='Day 2 - Marketing Funnel');
SET @aid2 := (SELECT id FROM ta_assessments WHERE title='Day 2 - Marketing Funnel' ORDER BY id DESC LIMIT 1);

-- =====================
-- Topics (all 30)
-- =====================
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m1,NULL,'Day 1 - What Is Marketing? The Big Picture Module','Marketing basics and 4Ps.','',1.00,1,1,@aid1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m1 AND name='Day 1 - What Is Marketing? The Big Picture Module');

INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m1,NULL,'Day 2 - The Marketing Funnel: Awareness to Retention','Awareness to retention funnel stages.','',1.00,1,1,@aid2,2,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m1 AND name='Day 2 - The Marketing Funnel: Awareness to Retention');

INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m1,NULL,'Day 3 - STP: Segmentation, Targeting & Positioning','STP model and brand positioning.','',1.00,1,0,NULL,3,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m1 AND name='Day 3 - STP: Segmentation, Targeting & Positioning');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m1,NULL,'Day 4 - Building a Customer Persona','Persona creation fundamentals.','',1.00,1,0,NULL,4,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m1 AND name='Day 4 - Building a Customer Persona');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m1,NULL,'Day 5 - Competitor Analysis: Know Your Market','Direct/indirect competitor analysis.','',1.00,1,0,NULL,5,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m1 AND name='Day 5 - Competitor Analysis: Know Your Market');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m1,NULL,'Day 6 - KPIs & Goal Setting: How to Measure Success','KPI and SMART goals.','',1.00,1,0,NULL,6,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m1 AND name='Day 6 - KPIs & Goal Setting: How to Measure Success');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m1,NULL,'Day 7 - Strategy Thinking: Connecting It All','Combine audience, channel, message, offer.','',1.00,1,0,NULL,7,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m1 AND name='Day 7 - Strategy Thinking: Connecting It All');

INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m2,NULL,'Day 8 - Introduction to Paid Advertising','Paid ad basics and objectives.','',1.00,1,0,NULL,8,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m2 AND name='Day 8 - Introduction to Paid Advertising');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m2,NULL,'Day 9 - Meta Ads: Facebook & Instagram Advertising','Meta Ads Manager structure and targeting.','',1.00,1,0,NULL,9,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m2 AND name='Day 9 - Meta Ads: Facebook & Instagram Advertising');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m2,NULL,'Day 10 - Meta Ads: Creative, Copy & Campaign Structure','Hooks, copy, A/B tests, retargeting.','',1.00,1,0,NULL,10,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m2 AND name='Day 10 - Meta Ads: Creative, Copy & Campaign Structure');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m2,NULL,'Day 11 - Google Ads: Search & Display Advertising','Search intent, display, keyword matching.','',1.00,1,0,NULL,11,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m2 AND name='Day 11 - Google Ads: Search & Display Advertising');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m2,NULL,'Day 12 - Google Ads: Bidding, Budgets & Smart Campaigns','Bidding strategies and Performance Max.','',1.00,1,0,NULL,12,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m2 AND name='Day 12 - Google Ads: Bidding, Budgets & Smart Campaigns');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m2,NULL,'Day 13 - LinkedIn Ads & Amazon Ads Basics','B2B and marketplace ad fundamentals.','',1.00,1,0,NULL,13,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m2 AND name='Day 13 - LinkedIn Ads & Amazon Ads Basics');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m2,NULL,'Day 14 - Budgeting, ROAS & ROI Fundamentals','ROAS, CAC, LTV and budget control.','',1.00,1,0,NULL,14,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m2 AND name='Day 14 - Budgeting, ROAS & ROI Fundamentals');

INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m3,NULL,'Day 15 - What Is SEO? The Basics Explained','SEO foundations and ranking factors.','',1.00,1,0,NULL,15,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m3 AND name='Day 15 - What Is SEO? The Basics Explained');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m3,NULL,'Day 16 - Keyword Research: Finding What People Search For','Keyword intent and research tools.','',1.00,1,0,NULL,16,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m3 AND name='Day 16 - Keyword Research: Finding What People Search For');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m3,NULL,'Day 17 - On-Page SEO: Optimizing Your Content','Titles, headers, internal links and structure.','',1.00,1,0,NULL,17,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m3 AND name='Day 17 - On-Page SEO: Optimizing Your Content');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m3,NULL,'Day 18 - Off-Page & Technical SEO Essentials','Backlinks, speed, Core Web Vitals.','',1.00,1,0,NULL,18,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m3 AND name='Day 18 - Off-Page & Technical SEO Essentials');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m3,NULL,'Day 19 - Content Strategy & Planning a Content Calendar','Content pillars and cadence planning.','',1.00,1,0,NULL,19,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m3 AND name='Day 19 - Content Strategy & Planning a Content Calendar');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m3,NULL,'Day 20 - Blog Writing for SEO: Structure & Best Practices','SEO writing and CTA usage.','',1.00,1,0,NULL,20,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m3 AND name='Day 20 - Blog Writing for SEO: Structure & Best Practices');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m3,NULL,'Day 21 - Social Media Content: Creating Content That Performs','Platform-fit social content and engagement.','',1.00,1,0,NULL,21,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m3 AND name='Day 21 - Social Media Content: Creating Content That Performs');

INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m4,NULL,'Day 22 - GA4 Basics: Your Marketing Data Command Centre','GA4 setup and acquisition reports.','',1.00,1,0,NULL,22,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m4 AND name='Day 22 - GA4 Basics: Your Marketing Data Command Centre');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m4,NULL,'Day 23 - Events, Conversions & Understanding User Behaviour','Conversion events and funnels.','',1.00,1,0,NULL,23,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m4 AND name='Day 23 - Events, Conversions & Understanding User Behaviour');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m4,NULL,'Day 24 - Building Marketing Dashboards & Reporting','Looker Studio and reporting rhythm.','',1.00,1,0,NULL,24,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m4 AND name='Day 24 - Building Marketing Dashboards & Reporting');

INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m5,NULL,'Day 25 - Email Marketing Fundamentals','Email ROI, subject lines, segmentation.','',1.00,1,0,NULL,25,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m5 AND name='Day 25 - Email Marketing Fundamentals');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m5,NULL,'Day 26 - CRM Basics & Automation Flows','CRM records and automation sequences.','',1.00,1,0,NULL,26,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m5 AND name='Day 26 - CRM Basics & Automation Flows');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m5,NULL,'Day 27 - WhatsApp Marketing & Lead Nurturing','WhatsApp API and nurture journeys.','',1.00,1,0,NULL,27,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m5 AND name='Day 27 - WhatsApp Marketing & Lead Nurturing');

INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m6,NULL,'Day 28 - What Is CRO & Why It Matters','CRO basics and friction reduction.','',1.00,1,0,NULL,28,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m6 AND name='Day 28 - What Is CRO & Why It Matters');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m6,NULL,'Day 29 - Landing Page Optimization & A/B Testing','Landing page structure and A/B tests.','',1.00,1,0,NULL,29,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m6 AND name='Day 29 - Landing Page Optimization & A/B Testing');
INSERT INTO `training_topics` (`module_id`,`prerequisite_topic_id`,`name`,`description`,`prerequisites`,`duration_hours`,`has_assignment`,`has_assessment`,`assessment_id`,`sort_order`,`created_at`,`updated_at`)
SELECT @m6,NULL,'Day 30 - UX Basics for Marketers & Conversion Metrics','UX factors and conversion recap.','',1.00,1,0,NULL,30,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM training_topics WHERE module_id=@m6 AND name='Day 30 - UX Basics for Marketers & Conversion Metrics');

-- =====================
-- File assignments for LMS topics
-- =====================
SET @has_asn := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'assignments'
);
SET @sql_asn := IF(
  @has_asn = 1,
  'INSERT INTO `assignments` (`topic_id`,`name`,`details`,`max_submissions`,`created_at`,`updated_at`)
   SELECT
     t.id,
     CONCAT(''Assignment - '', t.name),
     CONCAT(''Reflect: How does today''''s topic ('', t.name, '') apply to a brand you know? Jot down 3 examples.''),
     0,
     NOW(),
     NOW()
   FROM `training_topics` t
   LEFT JOIN `assignments` a ON a.topic_id = t.id
   WHERE t.has_assignment = 1
     AND a.id IS NULL',
  'SELECT 1'
);
PREPARE s_asn FROM @sql_asn; EXECUTE s_asn; DEALLOCATE PREPARE s_asn;

-- =====================
-- Day 1 question bank (full, merged)
-- =====================
SET @aid := @aid1;

INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid1,'text','Explain how marketing connects the right product to the right customer at the right time with an example.',2.00,NULL,'customer needs,target audience,right time,value creation,problem solving,example,customer satisfaction',50.00,NULL,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid1 AND sort_order=1);
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid1,'text','Describe how digital marketing channels help businesses reach customers effectively.',2.00,NULL,'digital channels,search,social media,email,ads,online reach,targeting,engagement',50.00,NULL,2,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid1 AND sort_order=2);
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid1,'text','Explain why every marketing activity should be linked to a business goal.',2.00,NULL,'business goals,alignment,ROI,focus,efficiency,direction,performance measurement',50.00,NULL,3,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid1 AND sort_order=3);
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid1,'text','Discuss the 4 Ps of marketing and their role in building a strategy.',2.00,NULL,'product,price,place,promotion,marketing mix,strategy,customer value,integration',50.00,NULL,4,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid1 AND sort_order=4);
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid1,'text','Explain how measurability makes digital marketing more effective than traditional marketing.',2.00,NULL,'measurability,data tracking,analytics,metrics,ROI,optimization,performance',50.00,NULL,5,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid1 AND sort_order=5);
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid1,'text','A company launches a product but fails because it targeted the wrong audience. Identify the mistake and suggest a fix.',2.00,NULL,'wrong audience,targeting error,segmentation,customer mismatch,market research,repositioning',50.00,NULL,6,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid1 AND sort_order=6);
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid1,'text','A business uses social media ads but gets high engagement and no sales. Analyze the issue.',2.00,NULL,'low conversion,high engagement,wrong audience,weak value proposition,poor funnel,landing page issue',50.00,NULL,7,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid1 AND sort_order=7);
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid1,'text','A brand focuses only on advertising but ignores product quality. What will happen long-term?',2.00,NULL,'poor quality,customer dissatisfaction,negative reviews,loss of trust,brand damage,low retention',50.00,NULL,8,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid1 AND sort_order=8);
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid1,'text','A company tracks clicks but not purchases. What problem does this create?',2.00,NULL,'no conversion tracking,vanity metrics,missing ROI,poor decisions,funnel gap',50.00,NULL,9,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid1 AND sort_order=9);
INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid1,'text','A premium brand reduces prices heavily and loses its image. What went wrong?',2.00,NULL,'pricing strategy,brand positioning,value dilution,premium perception,customer perception',50.00,NULL,10,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid1 AND sort_order=10);

-- MCQ 11-30
INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Marketing is best described as:',1.00,NULL,NULL,50.00,NULL,11,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=11);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=11 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Selling',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Advertising',0,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Connecting right product to right customer',1,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Manufacturing',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Which is a digital marketing channel?',1.00,NULL,NULL,50.00,NULL,12,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=12);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=12 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Newspaper',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Billboard',0,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Email',1,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Flyers',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Why is digital marketing powerful?',1.00,NULL,NULL,50.00,NULL,13,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=13);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=13 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Free',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Measurable',1,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'No strategy needed',0,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Offline',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Which is NOT a marketing goal?',1.00,NULL,NULL,50.00,NULL,14,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=14);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=14 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Sales',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Awareness',0,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Loyalty',0,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Reduce quality',1,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Product in 4 Ps refers to:',1.00,NULL,NULL,50.00,NULL,15,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=15);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=15 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Cost',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Offering',1,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Ads',0,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Distribution',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Price affects:',1.00,NULL,NULL,50.00,NULL,16,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=16);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=16 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Demand',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Perception',0,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Profit',0,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'All of the above',1,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Place refers to:',1.00,NULL,NULL,50.00,NULL,17,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=17);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=17 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Quality',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Distribution',1,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Ads',0,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Price',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Promotion includes:',1.00,NULL,NULL,50.00,NULL,18,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=18);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=18 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Manufacturing',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Advertising',1,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Logistics',0,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Pricing',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Which metric shows real business impact?',1.00,NULL,NULL,50.00,NULL,19,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=19);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=19 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Likes',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Views',0,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Conversions',1,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Impressions',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Tracking clicks and purchases helps in:',1.00,NULL,NULL,50.00,NULL,20,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=20);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=20 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Guessing',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Decisions',1,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Ignoring data',0,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Avoid planning',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Traditional marketing is:',1.00,NULL,NULL,50.00,NULL,21,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=21);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=21 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Highly measurable',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Less trackable',1,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Digital',0,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Automated',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Correct marketing example:',1.00,NULL,NULL,50.00,NULL,22,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=22);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=22 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Random selling',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Ignoring needs',0,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Targeted ads',1,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'No promotion',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','If no goals are defined, result is:',1.00,NULL,NULL,50.00,NULL,23,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=23);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=23 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Efficiency',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Profit',0,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Wasted effort',1,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Clarity',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Measurable marketing means:',1.00,NULL,NULL,50.00,NULL,24,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=24);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=24 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Guesswork',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Data tracking',1,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Branding only',0,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Offline',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Poor strategy example:',1.00,NULL,NULL,50.00,NULL,25,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=25);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=25 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Clear audience',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'No audience',1,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Data tracking',0,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Goals',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Why targeting is important?',1.00,NULL,NULL,50.00,NULL,26,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=26);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=26 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Saves cost',0,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Improves relevance',0,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Increases conversions',0,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'All',1,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Which are digital marketing channels?',1.00,NULL,NULL,50.00,NULL,27,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=27);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=27 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Email',1,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Social Media',1,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Search Engines',1,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Flyers',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Which metrics indicate performance?',1.00,NULL,NULL,50.00,NULL,28,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=28);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=28 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Clicks',1,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Conversions',1,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'ROI',1,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Color of ad',0,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Which are part of 4 Ps?',1.00,NULL,NULL,50.00,NULL,29,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=29);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=29 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Product',1,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Price',1,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'People',0,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Promotion',1,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid,'mcq','Which actions improve marketing success?',1.00,NULL,NULL,50.00,NULL,30,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid AND sort_order=30);
SET @q := (SELECT id FROM ta_questions WHERE assessment_id=@aid AND sort_order=30 ORDER BY id DESC LIMIT 1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Targeting',1,1,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=1);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Tracking',1,2,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=2);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Ignoring data',0,3,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=3);
INSERT INTO ta_question_options (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) SELECT @q,'Optimization',1,4,NOW() WHERE NOT EXISTS (SELECT 1 FROM ta_question_options WHERE question_id=@q AND sort_order=4);

-- =====================
-- Day 2 question bank
-- =====================
INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid2,'text','Explain the stages of the marketing funnel and their importance in customer journey.',2.00,NULL,'awareness,consideration,conversion,retention,customer journey,stage importance',50.00,NULL,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid2 AND sort_order=1);
INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid2,'text','How does content strategy differ across different stages of the marketing funnel?',2.00,NULL,'funnel stages,content strategy,awareness content,consideration content,conversion content,retention content',50.00,NULL,2,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid2 AND sort_order=2);
INSERT INTO ta_questions (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)
SELECT @aid2,'text','Explain the role of digital channels in moving users from awareness to conversion.',2.00,NULL,'digital channels,awareness,consideration,conversion,user journey,channel mix',50.00,NULL,3,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM ta_questions WHERE assessment_id=@aid2 AND sort_order=3);

COMMIT;

