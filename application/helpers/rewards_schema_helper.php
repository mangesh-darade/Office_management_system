<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('rewards_schema_ensure')) {
    function rewards_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!$db->table_exists('reward_categories')) {
            $db->query("CREATE TABLE `reward_categories` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `code` varchar(50) NOT NULL,
                `name` varchar(100) NOT NULL,
                `description` text,
                `icon_class` varchar(80) DEFAULT 'bi bi-star',
                `sort_order` int(11) NOT NULL DEFAULT 0,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_reward_cat_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('reward_levels')) {
            $db->query("CREATE TABLE `reward_levels` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `code` varchar(30) NOT NULL,
                `name` varchar(50) NOT NULL,
                `min_lifetime_points` decimal(10,2) NOT NULL DEFAULT 0,
                `max_lifetime_points` decimal(10,2) DEFAULT NULL,
                `badge_icon` varchar(80) DEFAULT 'bi bi-award',
                `badge_color` varchar(20) DEFAULT '#6c757d',
                `sort_order` int(11) NOT NULL DEFAULT 0,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_reward_level_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('reward_rules')) {
            $db->query("CREATE TABLE `reward_rules` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `category_id` int(11) DEFAULT NULL,
                `code` varchar(80) NOT NULL,
                `name` varchar(150) NOT NULL,
                `description` text,
                `trigger_event` varchar(80) NOT NULL,
                `condition_json` text,
                `points` decimal(8,2) NOT NULL DEFAULT 0,
                `max_per_day` int(11) DEFAULT NULL,
                `max_per_period` int(11) DEFAULT NULL,
                `period_type` enum('day','week','month','quarter','year') DEFAULT NULL,
                `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
                `approval_role` varchar(50) DEFAULT NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `effective_from` date DEFAULT NULL,
                `effective_to` date DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_reward_rule_code` (`code`),
                KEY `idx_reward_rules_trigger` (`trigger_event`,`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('reward_transactions')) {
            $db->query("CREATE TABLE `reward_transactions` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `rule_id` int(11) DEFAULT NULL,
                `category_id` int(11) DEFAULT NULL,
                `points` decimal(8,2) NOT NULL,
                `status` enum('pending','approved','rejected','reversed') NOT NULL DEFAULT 'approved',
                `source_module` varchar(50) NOT NULL,
                `source_record_id` bigint(20) DEFAULT NULL,
                `source_event` varchar(80) NOT NULL,
                `idempotency_key` varchar(191) NOT NULL,
                `reference_label` varchar(255) DEFAULT NULL,
                `granted_by` int(11) DEFAULT NULL,
                `approved_by` int(11) DEFAULT NULL,
                `approved_at` datetime DEFAULT NULL,
                `period_key` varchar(20) DEFAULT NULL,
                `notes` text,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_reward_idempotency` (`idempotency_key`),
                KEY `idx_rt_user_created` (`user_id`,`created_at`),
                KEY `idx_rt_period` (`user_id`,`period_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('reward_approval_queue')) {
            $db->query("CREATE TABLE `reward_approval_queue` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `submitted_by` int(11) NOT NULL,
                `rule_id` int(11) DEFAULT NULL,
                `requested_points` decimal(8,2) NOT NULL,
                `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
                `approver_id` int(11) DEFAULT NULL,
                `submitted_at` datetime NOT NULL,
                `decided_at` datetime DEFAULT NULL,
                `decision_comment` text,
                `reference_url` varchar(500) DEFAULT NULL,
                `source_module` varchar(50) DEFAULT NULL,
                `source_record_id` bigint(20) DEFAULT NULL,
                `transaction_id` bigint(20) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_raq_status` (`status`,`submitted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('reward_evidence')) {
            $db->query("CREATE TABLE `reward_evidence` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `approval_queue_id` bigint(20) NOT NULL,
                `file_path` varchar(500) DEFAULT NULL,
                `file_name` varchar(255) DEFAULT NULL,
                `external_url` varchar(500) DEFAULT NULL,
                `description` text,
                `uploaded_by` int(11) NOT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_re_queue` (`approval_queue_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('reward_leaderboard')) {
            $db->query("CREATE TABLE `reward_leaderboard` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `department_id` int(11) DEFAULT NULL,
                `period_type` enum('monthly','quarterly','yearly','all_time') NOT NULL,
                `period_key` varchar(20) NOT NULL,
                `points_earned` decimal(10,2) NOT NULL DEFAULT 0,
                `points_lost` decimal(10,2) NOT NULL DEFAULT 0,
                `net_points` decimal(10,2) NOT NULL DEFAULT 0,
                `rank_overall` int(11) DEFAULT NULL,
                `rank_department` int(11) DEFAULT NULL,
                `level_code` varchar(30) DEFAULT NULL,
                `computed_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_lb_user_period` (`user_id`,`period_type`,`period_key`),
                KEY `idx_lb_rank` (`period_type`,`period_key`,`rank_overall`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('reward_audit_logs')) {
            $db->query("CREATE TABLE `reward_audit_logs` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `entity_type` varchar(50) NOT NULL,
                `entity_id` bigint(20) NOT NULL,
                `action` varchar(50) NOT NULL,
                `actor_id` int(11) DEFAULT NULL,
                `old_values` text,
                `new_values` text,
                `ip_address` varchar(45) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_ral_entity` (`entity_type`,`entity_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$db->table_exists('user_reward_summary')) {
            $db->query("CREATE TABLE `user_reward_summary` (
                `user_id` int(11) NOT NULL,
                `lifetime_points` decimal(10,2) NOT NULL DEFAULT 0,
                `current_level_code` varchar(30) DEFAULT 'starter',
                `month_points` decimal(10,2) NOT NULL DEFAULT 0,
                `last_awarded_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        rewards_schema_seed_defaults($db);
    }
}

if (!function_exists('rewards_schema_upsert_category')) {
    function rewards_schema_upsert_category($db, array $c)
    {
        $row = $db->where('code', $c['code'])->get('reward_categories')->row();
        if ($row) {
            $db->where('id', (int) $row->id)->update('reward_categories', array(
                'name' => $c['name'],
                'description' => isset($c['description']) ? $c['description'] : null,
                'icon_class' => isset($c['icon_class']) ? $c['icon_class'] : 'bi bi-star',
                'sort_order' => (int) $c['sort_order'],
                'is_active' => 1,
            ));
            return (int) $row->id;
        }
        $db->insert('reward_categories', $c);
        return (int) $db->insert_id();
    }
}

if (!function_exists('rewards_schema_get_suppressed_rule_codes')) {
    function rewards_schema_get_suppressed_rule_codes($db)
    {
        $codes = array();
        if (!$db->table_exists('settings')) {
            return $codes;
        }
        $row = $db->where('key', 'spl_suppressed_rule_codes')->get('settings')->row();
        if (!$row || $row->value === '' || $row->value === null) {
            return $codes;
        }
        $decoded = json_decode((string) $row->value, true);
        if (!is_array($decoded)) {
            return $codes;
        }
        foreach ($decoded as $code) {
            $code = trim((string) $code);
            if ($code !== '') {
                $codes[$code] = true;
            }
        }
        return $codes;
    }
}

if (!function_exists('rewards_schema_suppress_rule_code')) {
    function rewards_schema_suppress_rule_code($db, $code)
    {
        $code = trim((string) $code);
        if ($code === '') {
            return false;
        }
        $codes = rewards_schema_get_suppressed_rule_codes($db);
        if (isset($codes[$code])) {
            return true;
        }
        $codes[$code] = true;
        $json = json_encode(array_keys($codes));
        if ($json === false) {
            return false;
        }
        $exists = $db->where('key', 'spl_suppressed_rule_codes')->get('settings')->row();
        if ($exists) {
            $db->where('id', (int) $exists->id)->update('settings', array('value' => $json));
            return true;
        }
        $db->insert('settings', array('key' => 'spl_suppressed_rule_codes', 'value' => $json));
        return true;
    }
}

if (!function_exists('rewards_schema_upsert_rule')) {
    function rewards_schema_upsert_rule($db, array $catIds, array $r)
    {
        $cat = isset($r['category']) ? $r['category'] : 'recognition';
        unset($r['category']);
        $code = $r['code'];
        $suppressed = rewards_schema_get_suppressed_rule_codes($db);
        if (isset($suppressed[$code])) {
            return 0;
        }
        $data = array(
            'code' => $code,
            'name' => $r['name'],
            'description' => isset($r['description']) ? $r['description'] : null,
            'trigger_event' => $r['trigger_event'],
            'condition_json' => isset($r['condition_json']) ? $r['condition_json'] : '{}',
            'points' => (float) $r['points'],
            'max_per_day' => array_key_exists('max_per_day', $r) ? $r['max_per_day'] : null,
            'max_per_period' => isset($r['max_per_period']) ? $r['max_per_period'] : null,
            'period_type' => isset($r['period_type']) ? $r['period_type'] : null,
            'requires_approval' => isset($r['requires_approval']) ? (int) $r['requires_approval'] : 0,
            'approval_role' => isset($r['approval_role']) ? $r['approval_role'] : null,
            'category_id' => isset($catIds[$cat]) ? $catIds[$cat] : null,
            'is_active' => isset($r['is_active']) ? (int) $r['is_active'] : 1,
        );
        $row = $db->where('code', $code)->get('reward_rules')->row();
        if ($row) {
            $db->where('id', (int) $row->id)->update('reward_rules', $data);
            return (int) $row->id;
        }
        $db->insert('reward_rules', $data);
        return (int) $db->insert_id();
    }
}

if (!function_exists('rewards_schema_seed_defaults')) {
    function rewards_schema_seed_defaults($db)
    {
        $cats = array(
            array('code' => 'daily', 'name' => 'Daily Attendance', 'icon_class' => 'bi bi-sun', 'sort_order' => 1),
            array('code' => 'learning', 'name' => 'Learning', 'icon_class' => 'bi bi-mortarboard', 'sort_order' => 2),
            array('code' => 'team', 'name' => 'Team Support', 'icon_class' => 'bi bi-people', 'sort_order' => 3),
            array('code' => 'delivery', 'name' => 'Delivery', 'icon_class' => 'bi bi-rocket', 'sort_order' => 4),
            array('code' => 'innovation', 'name' => 'Innovation', 'icon_class' => 'bi bi-lightbulb', 'sort_order' => 5),
            array('code' => 'recognition', 'name' => 'Recognition', 'icon_class' => 'bi bi-trophy', 'sort_order' => 6),
            array('code' => 'social', 'name' => 'Social', 'icon_class' => 'bi bi-emoji-smile', 'sort_order' => 7),
            array('code' => 'company', 'name' => 'Company', 'icon_class' => 'bi bi-building', 'sort_order' => 8),
            array('code' => 'consistency', 'name' => 'Consistency', 'icon_class' => 'bi bi-calendar2-check', 'sort_order' => 9),
            array('code' => 'knowledge_center', 'name' => 'Knowledge Center', 'icon_class' => 'bi bi-journal-bookmark', 'sort_order' => 10),
            array('code' => 'behavioral', 'name' => 'Behavioral', 'icon_class' => 'bi bi-heart', 'sort_order' => 11),
        );
        foreach ($cats as $c) {
            rewards_schema_upsert_category($db, $c);
        }

        $levels = array(
            array('code' => 'starter', 'name' => 'Starter', 'min_lifetime_points' => 0, 'max_lifetime_points' => 499.99, 'badge_color' => '#adb5bd', 'sort_order' => 1),
            array('code' => 'bronze', 'name' => 'Bronze', 'min_lifetime_points' => 500, 'max_lifetime_points' => 1499.99, 'badge_color' => '#cd7f32', 'sort_order' => 2),
            array('code' => 'silver', 'name' => 'Silver', 'min_lifetime_points' => 1500, 'max_lifetime_points' => 3499.99, 'badge_color' => '#c0c0c0', 'sort_order' => 3),
            array('code' => 'gold', 'name' => 'Gold', 'min_lifetime_points' => 3500, 'max_lifetime_points' => 7499.99, 'badge_color' => '#ffc107', 'sort_order' => 4),
            array('code' => 'platinum', 'name' => 'Platinum', 'min_lifetime_points' => 7500, 'max_lifetime_points' => 14999.99, 'badge_color' => '#0dcaf0', 'sort_order' => 5),
            array('code' => 'legend', 'name' => 'Legend', 'min_lifetime_points' => 15000, 'max_lifetime_points' => null, 'badge_color' => '#6610f2', 'sort_order' => 6),
        );
        foreach ($levels as $l) {
            $exists = $db->where('code', $l['code'])->get('reward_levels')->row();
            if (!$exists) {
                $db->insert('reward_levels', $l);
            }
        }

        $catIds = array();
        foreach ($db->get('reward_categories')->result() as $row) {
            $catIds[$row->code] = (int) $row->id;
        }

        $claim = function ($type) {
            return json_encode(array('claim_type' => $type));
        };

        $rules = array(
            // --- Daily ---
            array('code' => 'office_closing_checklist', 'name' => 'Office Closing Checklist', 'description' => 'Form for submission.', 'trigger_event' => 'office_closing_checklist', 'condition_json' => '{}', 'points' => 30, 'max_per_day' => 1, 'requires_approval' => 1, 'category' => 'daily'),
            array('code' => 'self_work_update_submitted', 'name' => 'Self Work Update Submitted', 'description' => 'Daily Activity submitted.', 'trigger_event' => 'daily_activity_logged', 'condition_json' => '{}', 'points' => 20, 'max_per_day' => 1, 'requires_approval' => 0, 'category' => 'daily'),
            array('code' => 'project_status_update_submitted', 'name' => 'Project Status Update Submitted', 'description' => 'Project status shared.', 'trigger_event' => 'project_status_update', 'condition_json' => '{}', 'points' => 20, 'max_per_day' => 1, 'requires_approval' => 1, 'category' => 'daily'),

            // --- Daily Attendance (auto on punch) ---
            array('code' => 'attendance_on_time_checkin', 'name' => 'On-Time Check-In', 'description' => 'First attendance punch on or before Shift Start + 5 minutes.', 'trigger_event' => 'attendance_checkin', 'condition_json' => '{"attendance_tier":"on_time"}', 'points' => 20, 'max_per_day' => 1, 'category' => 'daily'),
            array('code' => 'attendance_late_checkin', 'name' => 'Late Check-In', 'description' => 'First attendance punch after Grace Period and before 11:00 AM (0 points). Punches between Shift+5 and Grace award nothing.', 'trigger_event' => 'attendance_checkin', 'condition_json' => '{"attendance_tier":"late"}', 'points' => 0, 'max_per_day' => 1, 'category' => 'daily'),
            array('code' => 'attendance_very_late_checkin', 'name' => 'Very Late Check-In', 'description' => 'First attendance punch at 11:00 AM or later.', 'trigger_event' => 'attendance_checkin', 'condition_json' => '{"attendance_tier":"very_late"}', 'points' => -10, 'max_per_day' => 1, 'category' => 'daily'),
            array('code' => 'attendance_early_valid_checkout', 'name' => 'Early Valid Check-Out', 'description' => 'Last attendance punch between 5:00 PM and Shift End.', 'trigger_event' => 'attendance_checkout', 'condition_json' => '{"checkout_tier":"early_valid"}', 'points' => 10, 'max_per_day' => 1, 'category' => 'daily'),
            array('code' => 'attendance_complete_shift', 'name' => 'Complete Shift', 'description' => 'Last attendance punch after Shift End.', 'trigger_event' => 'attendance_checkout', 'condition_json' => '{"checkout_tier":"complete_shift"}', 'points' => 20, 'max_per_day' => 1, 'category' => 'daily'),
            array('code' => 'attendance_perfect_attendance', 'name' => 'Perfect Attendance', 'description' => 'On-time check-in and last punch after Shift End (replaces separate On-Time + Complete Shift awards).', 'trigger_event' => 'attendance_checkout', 'condition_json' => '{"checkout_tier":"perfect"}', 'points' => 30, 'max_per_day' => 1, 'category' => 'daily'),
            array('code' => 'attendance_checkin_missed', 'name' => 'Check-In Missed', 'description' => 'Working day with no check-in punch (cron or prior-day scan).', 'trigger_event' => 'attendance_penalty', 'condition_json' => '{"penalty_type":"missed_checkin"}', 'points' => -10, 'max_per_day' => 1, 'category' => 'daily'),
            array('code' => 'attendance_checkout_missed', 'name' => 'Check-Out Missed', 'description' => 'Previous working day checkout missing or last punch before 11:00 AM.', 'trigger_event' => 'attendance_penalty', 'condition_json' => '{"penalty_type":"missed_checkout"}', 'points' => -10, 'max_per_day' => 1, 'category' => 'daily'),

            array('code' => 'preapproved_leave_wfh', 'name' => 'Preapproved Leaves or WFHs', 'description' => 'Auto when leave/WFH is approved before the leave date.', 'trigger_event' => 'leave_approved', 'condition_json' => '{"leave_outcome":"preapproved"}', 'points' => 50, 'max_per_day' => 1, 'requires_approval' => 0, 'category' => 'daily'),
            array('code' => 'preapproved_leave_wfh_penalty', 'name' => 'Preapproved Leaves or WFHs — Deallocate', 'description' => 'Auto when leave/WFH is rejected without prior approval.', 'trigger_event' => 'leave_penalty', 'condition_json' => '{"leave_outcome":"rejected_unapproved"}', 'points' => -200, 'max_per_day' => 1, 'requires_approval' => 0, 'category' => 'daily'),
            array('code' => 'timely_wfh_late_intimation', 'name' => 'Timely Intimation of WFH or Late Coming', 'description' => 'Auto when same-day leave/WFH is approved and submitted before the timely cutoff.', 'trigger_event' => 'leave_approved', 'condition_json' => '{"leave_outcome":"timely"}', 'points' => 50, 'max_per_day' => 1, 'requires_approval' => 0, 'category' => 'daily'),
            array('code' => 'timely_wfh_late_intimation_penalty', 'name' => 'Timely Intimation — Deallocate', 'description' => 'Auto when same-day leave/WFH is rejected after the timely cutoff.', 'trigger_event' => 'leave_penalty', 'condition_json' => '{"leave_outcome":"late_intimation"}', 'points' => -200, 'max_per_day' => 1, 'requires_approval' => 0, 'category' => 'daily'),

            // --- Learning ---
            array('code' => 'learning_course_completed', 'name' => 'Learning Course Completed', 'description' => 'Learning course completed.', 'trigger_event' => 'lms_topic_completed', 'condition_json' => '{}', 'points' => 50, 'requires_approval' => 1, 'category' => 'learning'),
            array('code' => 'external_certification', 'name' => 'External Certification', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'certification_approved', 'condition_json' => '{}', 'points' => 100, 'requires_approval' => 0, 'category' => 'learning'),
            array('code' => 'daily_learning_concept', 'name' => 'Daily Learning Concept', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('daily_learning_concept'), 'points' => 10, 'requires_approval' => 1, 'category' => 'learning'),
            array('code' => 'weekly_quiz_90_plus', 'name' => 'Weekly Quiz 90% & Above', 'description' => 'To be submitted by Lead or auto from assessment score.', 'trigger_event' => 'assessment_passed', 'condition_json' => '{"passed":true,"min_score":90}', 'points' => 50, 'requires_approval' => 1, 'approval_role' => 'lead', 'category' => 'learning'),

            // --- Team Support ---
            array('code' => 'cheer_submitted', 'name' => 'Submitting Cheers Note', 'description' => 'Cheers form submitted.', 'trigger_event' => 'peer_cheer_sent', 'condition_json' => '{}', 'points' => 25, 'max_per_day' => 5, 'requires_approval' => 1, 'category' => 'team'),
            array('code' => 'cheer_received', 'name' => 'Receiving Cheers Note', 'description' => 'Cheers form submitted by colleague.', 'trigger_event' => 'peer_cheer_received', 'condition_json' => '{}', 'points' => 20, 'max_per_day' => 10, 'requires_approval' => 1, 'category' => 'team'),
            array('code' => 'knowledge_sharing_session', 'name' => 'Knowledge Sharing Session Conducted', 'description' => 'Submit entry for approval.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('knowledge_sharing_session'), 'points' => 200, 'requires_approval' => 1, 'category' => 'team'),

            // --- Delivery ---
            array('code' => 'major_release', 'name' => 'Major Release', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'release_completed', 'condition_json' => '{}', 'points' => 100, 'requires_approval' => 1, 'category' => 'delivery'),
            array('code' => 'delivery_before_deadline', 'name' => 'Delivery Before Deadline', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('delivery_before_deadline'), 'points' => 100, 'requires_approval' => 1, 'category' => 'delivery'),
            array('code' => 'critical_production_issue', 'name' => 'Critical Production Issue Resolved', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('critical_production_issue'), 'points' => 150, 'requires_approval' => 1, 'category' => 'delivery'),
            array('code' => 'exceptional_customer_feedback', 'name' => 'Exceptional Customer Feedback', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('exceptional_customer_feedback'), 'points' => 250, 'requires_approval' => 1, 'category' => 'delivery'),
            array('code' => 'zero_defect_delivery', 'name' => 'Zero Defect Delivery', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('zero_defect_delivery'), 'points' => 100, 'requires_approval' => 1, 'category' => 'delivery'),

            // --- Innovation ---
            array('code' => 'improvement_idea_implemented', 'name' => 'Improvement Idea Implemented', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('improvement_idea_implemented'), 'points' => 200, 'requires_approval' => 1, 'category' => 'innovation'),
            array('code' => 'automation_developed', 'name' => 'Automation Developed', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('automation_developed'), 'points' => 300, 'requires_approval' => 1, 'category' => 'innovation'),
            array('code' => 'idea_submitted', 'name' => 'Idea Submitted', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('idea_submitted'), 'points' => 20, 'requires_approval' => 1, 'category' => 'innovation'),

            // --- Recognition ---
            array('code' => 'excellent_performance', 'name' => 'Excellent Performance', 'description' => 'Admin / Medha can add.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('excellent_performance'), 'points' => 500, 'requires_approval' => 1, 'approval_role' => 'admin', 'category' => 'recognition'),
            array('code' => 'above_beyond_contribution', 'name' => 'Above & Beyond Contribution', 'description' => 'Admin / Medha can add.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('above_beyond_contribution'), 'points' => 500, 'requires_approval' => 1, 'approval_role' => 'admin', 'category' => 'recognition'),
            array('code' => 'exceptional_ownership', 'name' => 'Exceptional Ownership', 'description' => 'Admin / Medha can add.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('exceptional_ownership'), 'points' => 300, 'requires_approval' => 1, 'approval_role' => 'admin', 'category' => 'recognition'),

            // --- Social ---
            array('code' => 'birthday_celebration_organizer', 'name' => 'Birthday Celebration Organizer', 'description' => 'To be submitted by Lead.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('birthday_celebration_organizer'), 'points' => 50, 'requires_approval' => 1, 'approval_role' => 'lead', 'category' => 'social'),
            array('code' => 'event_organizer', 'name' => 'Event Organizer', 'description' => 'To be submitted by Lead.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('event_organizer'), 'points' => 100, 'requires_approval' => 1, 'approval_role' => 'lead', 'category' => 'social'),
            array('code' => 'team_moments_contributor', 'name' => 'Team Moments Contributor (photos/videos)', 'description' => 'To be submitted by Lead.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('team_moments_contributor'), 'points' => 50, 'requires_approval' => 1, 'approval_role' => 'lead', 'category' => 'social'),
            array('code' => 'csr_social_initiative', 'name' => 'CSR / Social Initiative Participation', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('csr_social_initiative'), 'points' => 100, 'requires_approval' => 1, 'category' => 'social'),
            array('code' => 'company_event_representative', 'name' => 'Representing Company at Event/Meetup', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('company_event_representative'), 'points' => 200, 'requires_approval' => 1, 'category' => 'social'),
            array('code' => 'company_blog_linkedin', 'name' => 'Company Blog / LinkedIn Post Contribution', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('company_blog_linkedin'), 'points' => 100, 'requires_approval' => 1, 'category' => 'social'),

            // --- Company ---
            array('code' => 'referral_hired', 'name' => 'Company Referral Hired Successfully', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('referral_hired'), 'points' => 500, 'requires_approval' => 1, 'category' => 'company'),
            array('code' => 'interview_panel_participation', 'name' => 'Interview Panel Participation', 'description' => 'Admin / Medha can add.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('interview_panel_participation'), 'points' => 100, 'requires_approval' => 1, 'approval_role' => 'admin', 'category' => 'company'),
            array('code' => 'office_activities_contribution', 'name' => 'Contribution for Office Related Activities', 'description' => 'Admin / Medha can add.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('office_activities_contribution'), 'points' => 100, 'requires_approval' => 1, 'approval_role' => 'admin', 'category' => 'company'),

            // --- Consistency (monthly auto review) ---
            array('code' => 'self_updates_20_days', 'name' => 'Self Updates 20 Days Continuous', 'description' => 'Auto for monthly review.', 'trigger_event' => 'consistency_review', 'condition_json' => '{"streak_type":"self_updates","days":20}', 'points' => 100, 'max_per_period' => 1, 'period_type' => 'month', 'requires_approval' => 1, 'category' => 'consistency'),
            array('code' => 'ontime_20_days', 'name' => 'On-Time 20 Days Continuous', 'description' => 'Auto for monthly review.', 'trigger_event' => 'consistency_review', 'condition_json' => '{"streak_type":"on_time","days":20}', 'points' => 100, 'max_per_period' => 1, 'period_type' => 'month', 'requires_approval' => 1, 'category' => 'consistency'),
            array('code' => 'no_missed_checkout_month', 'name' => 'No Missed Checkout Entire Month', 'description' => 'Auto for monthly review.', 'trigger_event' => 'consistency_review', 'condition_json' => '{"streak_type":"no_missed_checkout"}', 'points' => 100, 'max_per_period' => 1, 'period_type' => 'month', 'requires_approval' => 1, 'category' => 'consistency'),

            // --- Knowledge Center ---
            array('code' => 'ai_automation_created', 'name' => 'AI Automation Created', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('ai_automation_created'), 'points' => 200, 'requires_approval' => 1, 'category' => 'knowledge_center'),
            array('code' => 'ai_prompt_shared', 'name' => 'AI Prompt Shared & Adopted by Team', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('ai_prompt_shared'), 'points' => 50, 'requires_approval' => 1, 'category' => 'knowledge_center'),
            array('code' => 'ai_productivity_improvement', 'name' => 'AI-based Productivity Improvement', 'description' => 'Submit entry for approval with team member names.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('ai_productivity_improvement'), 'points' => 100, 'requires_approval' => 1, 'category' => 'knowledge_center'),

            // --- Behavioral ---
            array('code' => 'positive_attitude', 'name' => 'Positive Attitude', 'description' => 'Submit entry for approval.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('positive_attitude'), 'points' => 50, 'requires_approval' => 1, 'category' => 'behavioral'),
            array('code' => 'positive_attitude_penalty', 'name' => 'Positive Attitude — Deallocate', 'description' => 'Behavioral deallocation.', 'trigger_event' => 'reward_claim', 'condition_json' => $claim('positive_attitude_penalty'), 'points' => -200, 'requires_approval' => 1, 'approval_role' => 'admin', 'category' => 'behavioral'),
        );

        $activeCodes = array();
        foreach ($rules as $r) {
            rewards_schema_upsert_rule($db, $catIds, $r);
            $activeCodes[] = $r['code'];
        }

        if (!empty($activeCodes)) {
            $db->where_not_in('code', $activeCodes)->update('reward_rules', array('is_active' => 0));
        }
    }
}
