-- =============================================================================
-- 30-day Marketing curriculum: 6 modules, 30 topics (linear prerequisites),
-- file assignment per topic, DAY 1 assessment (10 text + 19 MCQ).
--
-- Prerequisites (run once, with backup):
--   database/training_lms_module.sql
--   database/training_assessment_module.sql
--
-- Legacy installs: replace ta_assessments → assessments, ta_questions → questions,
-- ta_question_options → question_options.
--
-- Re-running duplicates rows — delete prior rows or edit titles before re-run.
-- Adjust created_by (default 1) if needed.
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- DAY 1 Assessment (linked to first topic only)
-- -----------------------------------------------------------------------------
INSERT INTO `ta_assessments` (`title`, `description`, `time_limit_minutes`, `passing_marks`, `randomize_questions`, `shuffle_options`, `max_attempts`, `allow_retake`, `show_correct_after_submit`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('DAY 1 Assessment: What Is Marketing? (Big Picture Module)', 'Written response and MCQ knowledge check for Day 1.', 120, 60.00, 0, 0, 2, 1, 1, 'active', 1, NOW(), NULL);
SET @d1_asm = LAST_INSERT_ID();

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'text', 'Explain how marketing goes beyond just selling. Use a real-life example to show how timing and audience matter.', 1.00, NULL, NULL, NULL, 1, NOW(), NULL),
(@d1_asm, 'text', 'Describe how digital marketing helps businesses connect with the right audience more efficiently than traditional marketing.', 1.00, NULL, NULL, NULL, 2, NOW(), NULL),
(@d1_asm, 'text', 'Explain the importance of aligning marketing activities with business goals. What happens if this alignment is missing?', 1.00, NULL, NULL, NULL, 3, NOW(), NULL),
(@d1_asm, 'text', 'Discuss the 4 Ps of marketing and explain how they work together rather than independently.', 1.00, NULL, NULL, NULL, 4, NOW(), NULL),
(@d1_asm, 'text', 'Explain how measurability in digital marketing improves decision-making and ROI.', 1.00, NULL, NULL, NULL, 5, NOW(), NULL),
(@d1_asm, 'text', 'A startup launches a great product but targets the wrong audience and gets poor sales. Identify the core marketing mistake and suggest a correction strategy.', 1.00, NULL, NULL, NULL, 6, NOW(), NULL),
(@d1_asm, 'text', 'A company runs ads on social media but sees high clicks and very low purchases. Analyze what might be going wrong and suggest improvements.', 1.00, NULL, NULL, NULL, 7, NOW(), NULL),
(@d1_asm, 'text', 'A premium brand reduces prices drastically to increase sales but ends up losing its brand image. Which ''P'' was mishandled and why?', 1.00, NULL, NULL, NULL, 8, NOW(), NULL),
(@d1_asm, 'text', 'A business focuses only on promotion without improving product quality. What long-term impact will this have?', 1.00, NULL, NULL, NULL, 9, NOW(), NULL),
(@d1_asm, 'text', 'An e-commerce company tracks views and clicks but not conversions. What key insight are they missing and how will it affect decisions?', 1.00, NULL, NULL, NULL, 10, NOW(), NULL);

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Marketing is best described as:', 1.00, NULL, NULL, NULL, 11, NOW(), NULL);
SET @d1_q11 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q11, 'Selling products', 0, 0, NOW()),
(@d1_q11, 'Advertising only', 0, 1, NOW()),
(@d1_q11, 'Connecting the right product to the right customer', 1, 2, NOW()),
(@d1_q11, 'Manufacturing goods', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Which scenario best represents effective marketing?', 1.00, NULL, NULL, NULL, 12, NOW(), NULL);
SET @d1_q12 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q12, 'Selling to everyone', 0, 0, NOW()),
(@d1_q12, 'Selling at any time', 0, 1, NOW()),
(@d1_q12, 'Targeting the right audience at the right time', 1, 2, NOW()),
(@d1_q12, 'Focusing only on production', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Which of the following is a digital marketing channel?', 1.00, NULL, NULL, NULL, 13, NOW(), NULL);
SET @d1_q13 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q13, 'Newspaper', 0, 0, NOW()),
(@d1_q13, 'Billboard', 0, 1, NOW()),
(@d1_q13, 'Email', 1, 2, NOW()),
(@d1_q13, 'Flyers', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Why is digital marketing considered powerful?', 1.00, NULL, NULL, NULL, 14, NOW(), NULL);
SET @d1_q14 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q14, 'It is always free', 0, 0, NOW()),
(@d1_q14, 'It replaces all traditional marketing', 0, 1, NOW()),
(@d1_q14, 'It is measurable', 1, 2, NOW()),
(@d1_q14, 'It requires no strategy', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Which of the following is NOT a marketing goal?', 1.00, NULL, NULL, NULL, 15, NOW(), NULL);
SET @d1_q15 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q15, 'Increase sales', 0, 0, NOW()),
(@d1_q15, 'Improve loyalty', 0, 1, NOW()),
(@d1_q15, 'Build awareness', 0, 2, NOW()),
(@d1_q15, 'Reduce product quality', 1, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'In the 4 Ps, ''Product'' refers to:', 1.00, NULL, NULL, NULL, 16, NOW(), NULL);
SET @d1_q16 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q16, 'Cost strategy', 0, 0, NOW()),
(@d1_q16, 'The offering itself', 1, 1, NOW()),
(@d1_q16, 'Advertising methods', 0, 2, NOW()),
(@d1_q16, 'Distribution channel', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'In the 4 Ps, ''Price'' affects:', 1.00, NULL, NULL, NULL, 17, NOW(), NULL);
SET @d1_q17 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q17, 'Customer perception and demand', 1, 0, NOW()),
(@d1_q17, 'Only profit', 0, 1, NOW()),
(@d1_q17, 'Only cost', 0, 2, NOW()),
(@d1_q17, 'Only advertising', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', '''Place'' in marketing refers to:', 1.00, NULL, NULL, NULL, 18, NOW(), NULL);
SET @d1_q18 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q18, 'Product quality', 0, 0, NOW()),
(@d1_q18, 'Distribution and availability', 1, 1, NOW()),
(@d1_q18, 'Promotion strategy', 0, 2, NOW()),
(@d1_q18, 'Pricing model', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Which activity falls under ''Promotion''?', 1.00, NULL, NULL, NULL, 19, NOW(), NULL);
SET @d1_q19 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q19, 'Manufacturing', 0, 0, NOW()),
(@d1_q19, 'Logistics', 0, 1, NOW()),
(@d1_q19, 'Advertising', 1, 2, NOW()),
(@d1_q19, 'Pricing', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'A mismatch between product and audience primarily affects:', 1.00, NULL, NULL, NULL, 20, NOW(), NULL);
SET @d1_q20 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q20, 'Profit only', 0, 0, NOW()),
(@d1_q20, 'Marketing effectiveness', 1, 1, NOW()),
(@d1_q20, 'Manufacturing cost', 0, 2, NOW()),
(@d1_q20, 'Logistics', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Which metric shows actual business impact?', 1.00, NULL, NULL, NULL, 21, NOW(), NULL);
SET @d1_q21 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q21, 'Likes', 0, 0, NOW()),
(@d1_q21, 'Views', 0, 1, NOW()),
(@d1_q21, 'Conversions', 1, 2, NOW()),
(@d1_q21, 'Impressions', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Tracking user clicks and purchases helps marketers to:', 1.00, NULL, NULL, NULL, 22, NOW(), NULL);
SET @d1_q22 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q22, 'Reduce effort', 0, 0, NOW()),
(@d1_q22, 'Make data-driven decisions', 1, 1, NOW()),
(@d1_q22, 'Avoid planning', 0, 2, NOW()),
(@d1_q22, 'Ignore customers', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Traditional marketing differs from digital marketing because:', 1.00, NULL, NULL, NULL, 23, NOW(), NULL);
SET @d1_q23 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q23, 'It is cheaper', 0, 0, NOW()),
(@d1_q23, 'It is measurable', 0, 1, NOW()),
(@d1_q23, 'It is less trackable', 1, 2, NOW()),
(@d1_q23, 'It uses AI', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Which example shows correct use of marketing?', 1.00, NULL, NULL, NULL, 24, NOW(), NULL);
SET @d1_q24 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q24, 'Selling randomly', 0, 0, NOW()),
(@d1_q24, 'Ignoring customer needs', 0, 1, NOW()),
(@d1_q24, 'Targeted ads based on user behavior', 1, 2, NOW()),
(@d1_q24, 'No promotion', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'If marketing is not tied to goals, the result is:', 1.00, NULL, NULL, NULL, 25, NOW(), NULL);
SET @d1_q25 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q25, 'Higher efficiency', 0, 0, NOW()),
(@d1_q25, 'Clear direction', 0, 1, NOW()),
(@d1_q25, 'Wasted effort', 1, 2, NOW()),
(@d1_q25, 'Increased profit', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Which of the following best explains ''measurable marketing''?', 1.00, NULL, NULL, NULL, 26, NOW(), NULL);
SET @d1_q26 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q26, 'Guesswork', 0, 0, NOW()),
(@d1_q26, 'Tracking data like clicks and sales', 1, 1, NOW()),
(@d1_q26, 'Only branding', 0, 2, NOW()),
(@d1_q26, 'Only offline ads', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Which situation reflects poor marketing strategy?', 1.00, NULL, NULL, NULL, 27, NOW(), NULL);
SET @d1_q27 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q27, 'Targeted messaging', 0, 0, NOW()),
(@d1_q27, 'Clear goals', 0, 1, NOW()),
(@d1_q27, 'No defined audience', 1, 2, NOW()),
(@d1_q27, 'Data tracking', 0, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Why is targeting important in marketing?', 1.00, NULL, NULL, NULL, 28, NOW(), NULL);
SET @d1_q28 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q28, 'Saves money', 0, 0, NOW()),
(@d1_q28, 'Improves relevance', 0, 1, NOW()),
(@d1_q28, 'Increases conversions', 0, 2, NOW()),
(@d1_q28, 'All of the above', 1, 3, NOW());

INSERT INTO `ta_questions` (`assessment_id`, `question_type`, `question_text`, `points`, `coding_language`, `model_answer`, `coding_expected_output`, `sort_order`, `created_at`, `updated_at`) VALUES
(@d1_asm, 'mcq', 'Which is the biggest advantage of digital marketing over traditional marketing?', 1.00, NULL, NULL, NULL, 29, NOW(), NULL);
SET @d1_q29 = LAST_INSERT_ID();
INSERT INTO `ta_question_options` (`question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`) VALUES
(@d1_q29, 'Reach only', 0, 0, NOW()),
(@d1_q29, 'Measurability', 1, 1, NOW()),
(@d1_q29, 'Cost always low', 0, 2, NOW()),
(@d1_q29, 'No competition', 0, 3, NOW());

-- -----------------------------------------------------------------------------
-- Modules
-- -----------------------------------------------------------------------------
INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('Marketing Fundamentals & Strategy', 'Days 1–7: marketing basics, funnel, STP, persona, competitors, KPIs, strategy.', 'active', 500, NULL, NOW(), NULL);
SET @m1 = LAST_INSERT_ID();

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('Performance Marketing & Paid Ads', 'Days 8–14: paid ads, Meta, Google, LinkedIn, Amazon, budgeting, ROAS.', 'active', 510, NULL, NOW(), NULL);
SET @m2 = LAST_INSERT_ID();

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('Content Marketing & SEO', 'Days 15–21: SEO, keywords, on/off-page, content calendar, blog, social content.', 'active', 520, NULL, NOW(), NULL);
SET @m3 = LAST_INSERT_ID();

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('Analytics & Data (GA4 + Excel)', 'Days 22–24: GA4, events, conversions, dashboards, reporting.', 'active', 530, NULL, NOW(), NULL);
SET @m4 = LAST_INSERT_ID();

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('CRM, WhatsApp & Email Marketing', 'Days 25–27: email, CRM, automation, WhatsApp.', 'active', 540, NULL, NOW(), NULL);
SET @m5 = LAST_INSERT_ID();

INSERT INTO `training_modules` (`title`, `description`, `status`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
('CRO — Conversion Rate Optimization', 'Days 28–30: CRO, landing pages, A/B tests, UX metrics.', 'active', 550, NULL, NOW(), NULL);
SET @m6 = LAST_INSERT_ID();

-- -----------------------------------------------------------------------------
-- Topics + assignments (prerequisite = previous day globally)
-- duration_hours: 8.00 = one training day
-- -----------------------------------------------------------------------------

-- Day 1
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m1, NULL, 'DAY 1 — What Is Marketing? The Big Picture Module', '1. Marketing is the process of connecting the right product to the right person at the right time.\r\n2. Digital marketing uses online channels — search, social, email, and ads — to reach customers.\r\n3. Every marketing action should be tied to a business goal (sales, awareness, loyalty).\r\n4. The 4 Ps framework: Product, Price, Place, Promotion — these are the pillars of any marketing strategy.\r\n5. Digital marketing is measurable — unlike traditional media, you can track every click, view, and purchase.', '', 8.00, 1, 1, @d1_asm, 1, NOW(), NULL);
SET @t01 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t01, 'DAY 1 — Reflect', 'Reflect: How does today''s topic (What Is Marketing? The Big Picture) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 2
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m1, @t01, 'DAY 2 — The Marketing Funnel: Awareness to Retention', '1. The funnel has 3 stages — Top (Awareness), Middle (Consideration), Bottom (Conversion + Retention).\r\n2. Awareness: People first learn your brand exists — through ads, SEO, or social media.\r\n3. Consideration: They compare you with competitors and look for reasons to trust you.\r\n4. Conversion: They take action — buy, sign up, or inquire. This is your goal moment.\r\n5. Retention: Keeping customers coming back is cheaper than acquiring new ones — focus on this too.', '', 8.00, 1, 0, NULL, 2, NOW(), NULL);
SET @t02 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t02, 'DAY 2 — Reflect', 'Reflect: How does today''s topic (The Marketing Funnel) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 3
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m1, @t02, 'DAY 3 — STP: Segmentation, Targeting & Positioning', '1. Segmentation: Divide the market into groups — by age, income, interest, behavior, or geography.\r\n2. Targeting: Choose which segment(s) your brand will focus on — you can''t serve everyone equally.\r\n3. Positioning: Define how your brand is perceived vs. competitors — what makes you different and better.\r\n4. Example: Nike segments athletes, targets urban youth aged 16–30, and positions as ''peak performance''.\r\n5. Good positioning answers: Why should THIS person choose US over everyone else?', '', 8.00, 1, 0, NULL, 3, NOW(), NULL);
SET @t03 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t03, 'DAY 3 — Reflect', 'Reflect: How does today''s topic (STP) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 4
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m1, @t03, 'DAY 4 — Building a Customer Persona', '1. A persona is a fictional but data-driven profile of your ideal customer.\r\n2. Include: Name, age, job, income, goals, frustrations, preferred channels, and buying behavior.\r\n3. Personas help you write better copy, choose the right platforms, and design better campaigns.\r\n4. Bad persona: ''Women aged 25–40.'' Good persona: ''Priya, 29, busy working mom who shops online at night for convenience.''\r\n5. Most brands have 2–4 core personas. Start with 1 and get it really right.', '', 8.00, 1, 0, NULL, 4, NOW(), NULL);
SET @t04 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t04, 'DAY 4 — Reflect', 'Reflect: How does today''s topic (Building a Customer Persona) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 5
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m1, @t04, 'DAY 5 — Competitor Analysis: Know Your Market', '1. Competitor analysis helps you understand the landscape your brand competes in.\r\n2. Look at 3 types: Direct (same product), Indirect (solves same problem differently), and Aspirational (brands you want to be like).\r\n3. Analyze their: Pricing, messaging, social media presence, ad strategy, and customer reviews.\r\n4. Tools to use: Google Search, Meta Ad Library, SimilarWeb, and customer review sites like G2.\r\n5. Goal: Find gaps — what are they doing poorly that you can do better?', '', 8.00, 1, 0, NULL, 5, NOW(), NULL);
SET @t05 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t05, 'DAY 5 — Reflect', 'Reflect: How does today''s topic (Competitor Analysis) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 6
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m1, @t05, 'DAY 6 — KPIs & Goal Setting: How to Measure Success', '1. KPI = Key Performance Indicator. These are the numbers that tell you if your marketing is working.\r\n2. Common KPIs: Impressions, Clicks, CTR (click-through rate), Conversions, ROAS, CAC, and LTV.\r\n3. SMART goals: Specific, Measurable, Achievable, Relevant, Time-bound.\r\n4. Example: ''Increase website traffic by 30% in 60 days through SEO and paid search.''\r\n5. Vanity metrics (likes, followers) feel good but don''t always drive business results — focus on revenue-linked KPIs.', '', 8.00, 1, 0, NULL, 6, NOW(), NULL);
SET @t06 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t06, 'DAY 6 — Reflect', 'Reflect: How does today''s topic (KPIs & Goal Setting) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 7
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m1, @t06, 'DAY 7 — Strategy Thinking — Connecting It All', '1. A marketing strategy is not a list of tactics — it''s a plan connecting business goals to customer actions.\r\n2. The strategy formula: Target Audience + Channel + Message + Offer + Measurement = Campaign.\r\n3. Always start with the customer, not the product. What do they want? Then show how your product helps.\r\n4. Strategy vs. Tactics: Strategy is ''we''ll grow brand awareness via social media.'' Tactics are ''we''ll post 3x/week on Instagram.''\r\n5. Today: review your notes from Days 1–6 and write a 1-paragraph marketing strategy for any brand.', '', 8.00, 1, 0, NULL, 7, NOW(), NULL);
SET @t07 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t07, 'DAY 7 — Reflect', 'Reflect: How does today''s topic (Day 7) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 8
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m2, @t07, 'DAY 8 — Introduction to Paid Advertising', '1. Paid advertising = paying platforms to show your content to targeted audiences.\r\n2. Unlike organic content, paid ads give you immediate reach and precise control over who sees them.\r\n3. The 3 core ad objectives: Awareness (reach), Consideration (engagement/clicks), Conversion (sales/leads).\r\n4. Campaign structure: Campaign → Ad Set / Ad Group → Ad. Each level controls different settings.\r\n5. Key metrics to know: Impressions, Reach, CTR, CPC (cost-per-click), CPM, ROAS, and Conversion Rate.', '', 8.00, 1, 0, NULL, 1, NOW(), NULL);
SET @t08 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t08, 'DAY 8 — Reflect', 'Reflect: How does today''s topic (Introduction to Paid Advertising) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 9
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m2, @t08, 'DAY 9 — Meta Ads: Facebook & Instagram Advertising', '1. Meta Ads Manager lets you run ads across Facebook, Instagram, Messenger, and the Audience Network.\r\n2. Campaign objectives: Awareness, Traffic, Engagement, Leads, App Promotion, and Sales.\r\n3. Ad set level: You control budget, schedule, audience targeting, and placements here.\r\n4. Audience targeting options: Core audiences (demographics), Custom audiences (your data), Lookalike audiences.\r\n5. Ad formats: Single image, carousel (multiple images), video, collection, and Stories/Reels.', '', 8.00, 1, 0, NULL, 2, NOW(), NULL);
SET @t09 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t09, 'DAY 9 — Reflect', 'Reflect: How does today''s topic (Meta Ads) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 10
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m2, @t09, 'DAY 10 — Meta Ads: Creative, Copy & Campaign Structure', '1. Ad copy formula: Hook (stop the scroll) → Value (what''s in it for them?) → CTA (tell them what to do).\r\n2. The hook is the most important part — you have 1–2 seconds to stop someone from scrolling.\r\n3. Creative types: Static images perform for sales, video for awareness, carousels for showcasing multiple products.\r\n4. Always run 2–3 ad variations per ad set to test which creative and copy resonates best (A/B testing).\r\n5. Retargeting: Show ads to people who visited your site or interacted with your content — higher conversion rates.', '', 8.00, 1, 0, NULL, 3, NOW(), NULL);
SET @t10 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t10, 'DAY 10 — Reflect', 'Reflect: How does today''s topic (Meta Ads) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 11
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m2, @t10, 'DAY 11 — Google Ads: Search & Display Advertising', '1. Google Search Ads show up when someone actively searches for a product/service — high intent traffic.\r\n2. Display Ads are image/banner ads shown across millions of websites in the Google Display Network.\r\n3. Keyword match types: Broad match (wide), Phrase match (contained), Exact match (precise control).\r\n4. Quality Score: Google rates your ad based on CTR, relevance, and landing page experience (1–10 score).\r\n5. Higher Quality Score = lower cost per click — relevant ads cost less than irrelevant ones.', '', 8.00, 1, 0, NULL, 4, NOW(), NULL);
SET @t11 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t11, 'DAY 11 — Reflect', 'Reflect: How does today''s topic (Google Ads) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 12
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m2, @t11, 'DAY 12 — Google Ads: Bidding, Budgets & Smart Campaigns', '1. Manual CPC: You set the max you''ll pay per click — good for beginners who want control.\r\n2. Smart Bidding strategies: Target CPA, Target ROAS, Maximize Conversions (AI-driven, needs data to work).\r\n3. Ad extensions boost your ad''s real estate: Sitelinks, callouts, structured snippets, and call extensions.\r\n4. Negative keywords: Words that prevent your ad from showing for irrelevant searches — saves wasted spend.\r\n5. Performance Max: AI-driven campaign type that runs across all Google surfaces (Search, Display, YouTube, Gmail).', '', 8.00, 1, 0, NULL, 5, NOW(), NULL);
SET @t12 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t12, 'DAY 12 — Reflect', 'Reflect: How does today''s topic (Google Ads) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 13
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m2, @t12, 'DAY 13 — LinkedIn Ads & Amazon Ads Basics', '1. LinkedIn Ads: Best for B2B targeting — you can target by job title, company size, industry, and seniority.\r\n2. LinkedIn ad formats: Sponsored Content (feed posts), Message Ads (direct to inbox), Lead Gen Forms.\r\n3. LinkedIn CPCs are higher ($5–$15+) but leads are higher quality for B2B — the ROI often justifies the cost.\r\n4. Amazon Ads: Sponsored Products (keyword-targeted within search results), Sponsored Brands (banner at top).\r\n5. Amazon ads work best when your product listing is already optimized with good images, title, and reviews.', '', 8.00, 1, 0, NULL, 6, NOW(), NULL);
SET @t13 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t13, 'DAY 13 — Reflect', 'Reflect: How does today''s topic (LinkedIn Ads & Amazon Ads Basics) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 14
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m2, @t13, 'DAY 14 — Budgeting, ROAS & ROI Fundamentals', '1. ROAS (Return on Ad Spend) = Revenue generated / Ad spend. A ROAS of 3x means $3 earned per $1 spent.\r\n2. A good ROAS depends on your industry and margins — ecommerce typically targets 3–5x ROAS.\r\n3. CAC (Customer Acquisition Cost) = Total ad spend / Number of customers acquired.\r\n4. LTV (Lifetime Value) = Average purchase value × purchase frequency × customer lifespan.\r\n5. Budget rule: Never spend more than you can afford to test. Start small, prove the concept, then scale.', '', 8.00, 1, 0, NULL, 7, NOW(), NULL);
SET @t14 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t14, 'DAY 14 — Reflect', 'Reflect: How does today''s topic (Budgeting, ROAS & ROI Fundamentals) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 15
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m3, @t14, 'DAY 15 — What Is SEO? The Basics Explained', '1. SEO (Search Engine Optimization) = making your website show up in Google search results organically (for free).\r\n2. How search works: Google crawls → indexes → ranks pages based on relevance, quality, and authority.\r\n3. 3 types of SEO: On-page (content & structure), Off-page (backlinks & authority), Technical (speed & crawlability).\r\n4. Ranking factors: Keywords, content quality, backlinks, page speed, mobile friendliness, and user experience.\r\n5. SEO is a long-term game — results typically take 3–6 months but the traffic is free and compounding.', '', 8.00, 1, 0, NULL, 1, NOW(), NULL);
SET @t15 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t15, 'DAY 15 — Reflect', 'Reflect: How does today''s topic (What Is SEO? The Basics Explained) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 16
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m3, @t15, 'DAY 16 — Keyword Research: Finding What People Search For', '1. A keyword is any word or phrase someone types into a search engine to find information.\r\n2. Keyword types: Informational (''how to run Facebook ads''), Navigational (''Meta Ads Manager login''), Commercial (''best ad agency Dubai'').\r\n3. Search Volume: How many times a keyword is searched per month. High volume ≠ always the right target.\r\n4. Keyword Difficulty (KD): How hard it is to rank on page 1. Beginners should target KD under 30.\r\n5. Free tools: Google Keyword Planner, Ubersuggest, AnswerThePublic. Paid: Ahrefs, SEMrush.', '', 8.00, 1, 0, NULL, 2, NOW(), NULL);
SET @t16 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t16, 'DAY 16 — Reflect', 'Reflect: How does today''s topic (Keyword Research) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 17
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m3, @t16, 'DAY 17 — On-Page SEO: Optimizing Your Content', '1. Title Tag: The clickable headline in search results — include your main keyword, keep it under 60 characters.\r\n2. Meta Description: The snippet below the title — it doesn''t directly affect ranking but drives CTR.\r\n3. Header tags (H1, H2, H3): Structure your content clearly — H1 is your page title, H2s are your sections.\r\n4. Keyword placement: Include your target keyword in the title, first 100 words, headers, and naturally throughout.\r\n5. Internal linking: Link to other pages on your own site — this spreads authority and keeps visitors on your site longer.', '', 8.00, 1, 0, NULL, 3, NOW(), NULL);
SET @t17 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t17, 'DAY 17 — Reflect', 'Reflect: How does today''s topic (On Page SEO) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 18
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m3, @t17, 'DAY 18 — Off-Page & Technical SEO Essentials', '1. Backlinks = other websites linking to yours. Google sees this as a vote of trust — more quality links = higher authority.\r\n2. Domain Authority (DA): A score (1–100) indicating how likely a site is to rank. Higher is better.\r\n3. Technical SEO covers: Page speed, mobile responsiveness, HTTPS security, XML sitemap, and robots.txt.\r\n4. Core Web Vitals: Google''s metrics for user experience — LCP (load time), CLS (visual stability), INP (interactivity).\r\n5. Tools: Google Search Console (free), PageSpeed Insights (free), Screaming Frog (crawling).', '', 8.00, 1, 0, NULL, 4, NOW(), NULL);
SET @t18 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t18, 'DAY 18 — Reflect', 'Reflect: How does today''s topic (Off Page & Technical SEO Essentials) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 19
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m3, @t18, 'DAY 19 — Content Strategy & Planning a Content Calendar', '1. Content strategy = a plan for what to create, why, for whom, and how often.\r\n2. Content pillars: 3–5 core topics your brand will consistently create content around.\r\n3. Content mix: Educational (teach), Inspirational (motivate), Entertaining (engage), Promotional (sell) — ideally in 3:1 ratio.\r\n4. A content calendar maps out: Topic, platform, format, publish date, and who''s responsible.\r\n5. Consistency beats frequency — posting 3x/week every week is better than 10x one week and 0 the next.', '', 8.00, 1, 0, NULL, 5, NOW(), NULL);
SET @t19 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t19, 'DAY 19 — Reflect', 'Reflect: How does today''s topic (Content Strategy & Planning a Content Calendar) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 20
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m3, @t19, 'DAY 20 — Blog Writing for SEO: Structure & Best Practices', '1. A good SEO blog post has: A clear keyword focus, a strong hook, scannable structure (headers + bullets), and a CTA.\r\n2. Ideal blog length: 800–1,500 words for most topics. In-depth guides can be 2,000–3,000+ words.\r\n3. The first 100 words are critical — hook the reader and introduce the value immediately.\r\n4. Use real examples, data, and images to increase time-on-page (a positive ranking signal).\r\n5. End with a CTA: What should the reader do next? Subscribe, contact, download, or read another post.', '', 8.00, 1, 0, NULL, 6, NOW(), NULL);
SET @t20 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t20, 'DAY 20 — Reflect', 'Reflect: How does today''s topic (Blog Writing for SEO) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 21
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m3, @t20, 'DAY 21 — Social Media Content: Creating Content That Performs', '1. Every platform has a different content style: Instagram = visual, LinkedIn = professional insight, Twitter/X = quick takes.\r\n2. The hook rule applies to social too — the first line of your caption must stop the scroll.\r\n3. Video content drives the highest organic reach on most platforms in 2024–2025.\r\n4. Hashtags: Use 3–10 relevant hashtags on Instagram; LinkedIn hashtags are less important than reach.\r\n5. Engagement rate = (Likes + Comments + Shares) / Reach × 100. A rate above 3% is considered good.', '', 8.00, 1, 0, NULL, 7, NOW(), NULL);
SET @t21 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t21, 'DAY 21 — Reflect', 'Reflect: How does today''s topic (Social Media Content) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 22
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m4, @t21, 'DAY 22 — GA4 Basics: Your Marketing Data Command Centre', '1. GA4 (Google Analytics 4) is Google''s current analytics platform — it replaced Universal Analytics in 2023.\r\n2. GA4 is event-based: every action (page view, click, scroll, purchase) is tracked as an ''event''.\r\n3. Key reports: Realtime (live visitors), Acquisition (where they came from), Engagement (what they did), Monetisation (revenue).\r\n4. Traffic sources: Organic Search, Direct, Referral, Paid Search, Social, Email — each tells a different story.\r\n5. Set up GA4: Create a property → add the tracking code to your website → verify data is flowing.', '', 8.00, 1, 0, NULL, 1, NOW(), NULL);
SET @t22 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t22, 'DAY 22 — Reflect', 'Reflect: How does today''s topic (GA4 Basics) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 23
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m4, @t22, 'DAY 23 — Events, Conversions & Understanding User Behaviour', '1. In GA4, a Conversion = a key business event (e.g. form submission, purchase, sign-up).\r\n2. You can mark any event as a conversion in GA4 — this tells Google what matters most to your business.\r\n3. Engagement metrics: Engaged Sessions (30+ seconds or 2+ pages), Engagement Rate, and Average Engagement Time.\r\n4. Funnel Exploration: See where users drop off in your conversion funnel — crucial for CRO.\r\n5. Excel for marketing: Use pivot tables, VLOOKUP, and conditional formatting to analyze campaign data efficiently.', '', 8.00, 1, 0, NULL, 2, NOW(), NULL);
SET @t23 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t23, 'DAY 23 — Reflect', 'Reflect: How does today''s topic (Events, Conversions & Understanding User Behaviour) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 24
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m4, @t23, 'DAY 24 — Building Marketing Dashboards & Reporting', '1. A good dashboard answers: How are we performing? Why? And what should we do next?\r\n2. Dashboard essentials: Traffic trend, top channels, conversion rate, top pages, and revenue/leads.\r\n3. Looker Studio (free): Connect GA4, Google Ads, and other sources to build visual dashboards.\r\n4. Reporting rhythm: Weekly pulse (quick stats), Monthly deep-dive (trends and insights), Quarterly review (strategy).\r\n5. Data storytelling: Lead with the insight, not the number. ''Organic traffic grew 40% — here''s why and what''s next.''', '', 8.00, 1, 0, NULL, 3, NOW(), NULL);
SET @t24 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t24, 'DAY 24 — Reflect', 'Reflect: How does today''s topic (Building Marketing Dashboards & Reporting) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 25
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m5, @t24, 'DAY 25 — Email Marketing Fundamentals', '1. Email is the highest ROI channel in digital marketing — averaging $36 return for every $1 spent.\r\n2. Email types: Newsletter (content), Promotional (offers), Transactional (receipts/alerts), Lifecycle (behaviour-triggered).\r\n3. Email anatomy: Subject line, Pre-header, Header image, Body copy, CTA button, Footer (unsubscribe link).\r\n4. Subject line tips: Keep it under 50 characters, create curiosity or urgency, avoid spam trigger words (FREE, CLICK NOW).\r\n5. Segmentation: Send different emails to different groups based on behavior, purchase history, or lifecycle stage.', '', 8.00, 1, 0, NULL, 1, NOW(), NULL);
SET @t25 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t25, 'DAY 25 — Reflect', 'Reflect: How does today''s topic (Email Marketing Fundamentals) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 26
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m5, @t25, 'DAY 26 — CRM Basics & Automation Flows', '1. CRM (Customer Relationship Management) = software that tracks every interaction with a lead or customer.\r\n2. CRM data includes: contact details, purchase history, email opens, support tickets, and sales stage.\r\n3. Popular CRMs: HubSpot (free tier), Zoho, Salesforce, Pipedrive — each suits different business sizes.\r\n4. Email automation: Triggered sequences sent automatically based on user actions (sign up, purchase, inactivity).\r\n5. Common flows: Welcome series (onboard new subscribers), Abandoned cart (recover lost sales), Re-engagement (win back cold leads).', '', 8.00, 1, 0, NULL, 2, NOW(), NULL);
SET @t26 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t26, 'DAY 26 — Reflect', 'Reflect: How does today''s topic (CRM Basics & Automation Flows) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 27
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m5, @t26, 'DAY 27 — WhatsApp Marketing & Lead Nurturing', '1. WhatsApp Business API allows brands to send transactional and marketing messages to opted-in customers.\r\n2. WhatsApp open rates are 90%+ compared to email''s 20–25% — it''s the most read channel.\r\n3. Use cases: Order confirmations, shipping updates, appointment reminders, promotional broadcasts, and customer support.\r\n4. Lead nurturing = building a relationship with a lead over time before they''re ready to buy.\r\n5. Nurturing sequence: Educate → Engage → Build Trust → Present Offer → Follow Up → Convert.', '', 8.00, 1, 0, NULL, 3, NOW(), NULL);
SET @t27 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t27, 'DAY 27 — Reflect', 'Reflect: How does today''s topic (WhatsApp Marketing & Lead Nurturing) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 28
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m6, @t27, 'DAY 28 — What Is CRO & Why It Matters', '1. CRO = increasing the percentage of visitors who complete a desired action (purchase, sign up, call).\r\n2. Conversion Rate = (Conversions / Total Visitors) × 100. Industry average for ecommerce is 1–3%.\r\n3. CRO is the most capital-efficient growth lever — double your conversion rate and you double revenue without extra ad spend.\r\n4. Friction = anything that makes it harder for a user to convert: slow pages, confusing copy, too many fields, unclear CTAs.\r\n5. Heatmaps (Hotjar, Microsoft Clarity) show you where users click, scroll, and drop off — start there.', '', 8.00, 1, 0, NULL, 1, NOW(), NULL);
SET @t28 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t28, 'DAY 28 — Reflect', 'Reflect: How does today''s topic (What Is CRO & Why It Matters) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 29
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m6, @t28, 'DAY 29 — Landing Page Optimization & A/B Testing', '1. A high-converting landing page has: Clear headline, subheadline, social proof, benefit bullets, CTA, and no distractions.\r\n2. Above the fold: Everything visible without scrolling. Your headline and CTA must both be visible here.\r\n3. Social proof: Reviews, testimonials, client logos, case studies, and media mentions reduce purchase anxiety.\r\n4. A/B Test = showing version A to 50% of visitors and version B to the other 50% — the winner stays.\r\n5. Test one element at a time: headline, CTA color, form length, hero image, or pricing display.', '', 8.00, 1, 0, NULL, 2, NOW(), NULL);
SET @t29 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t29, 'DAY 29 — Reflect', 'Reflect: How does today''s topic (Landing Page Optimization & A/B Testing) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);

-- Day 30
INSERT INTO `training_topics` (`module_id`, `prerequisite_topic_id`, `name`, `description`, `prerequisites`, `duration_hours`, `has_assignment`, `has_assessment`, `assessment_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(@m6, @t29, 'DAY 30 — UX Basics for Marketers & Conversion Metrics', '1. UX (User Experience) for marketers = making it as easy as possible for a visitor to take the action you want.\r\n2. Clarity over cleverness: If your headline makes someone think too hard, you''ve already lost them.\r\n3. Form optimization: Every extra field reduces conversions by ~10%. Only ask for what you absolutely need.\r\n4. Page speed: 53% of mobile users abandon a page that takes more than 3 seconds to load.\r\n5. Day 30 recap: You''ve now covered strategy, ads, SEO, analytics, email, CRM, WhatsApp, and CRO. You''re ready for the final project!', '', 8.00, 1, 0, NULL, 3, NOW(), NULL);
SET @t30 = LAST_INSERT_ID();
INSERT INTO `assignments` (`topic_id`, `name`, `details`, `max_submissions`, `created_at`, `updated_at`) VALUES
(@t30, 'DAY 30 — Reflect', 'Reflect: How does today''s topic (UX Basics for Marketers & Conversion Metrics) apply to a brand you know? Jot down 3 examples.', 0, NOW(), NULL);
