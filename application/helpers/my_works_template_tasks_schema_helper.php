<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('my_works_template_tasks_schema_ensure')) {
    /**
     * @param CI_DB_query_builder $db
     * @return void
     */
    function my_works_template_tasks_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!$db->table_exists('template_tasks')) {
            $db->query("CREATE TABLE `template_tasks` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `team` varchar(100) NOT NULL DEFAULT '',
                `template_type` varchar(150) NOT NULL DEFAULT '',
                `title` varchar(255) NOT NULL,
                `estimate_hours` decimal(6,2) DEFAULT NULL,
                `sort_order` int(11) NOT NULL DEFAULT 0,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_template_tasks_team` (`team`),
                KEY `idx_template_tasks_type` (`template_type`),
                KEY `idx_template_tasks_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            if (!schema_table_has_column($db, 'template_tasks', 'estimate_hours')) {
                $db->query('ALTER TABLE `template_tasks` ADD `estimate_hours` DECIMAL(6,2) NULL AFTER `title`');
            }
        }

        my_works_template_tasks_seed_defaults($db);
    }
}

if (!function_exists('my_works_template_tasks_seed_defaults')) {
    /**
     * @param CI_DB_query_builder $db
     * @return void
     */
    function my_works_template_tasks_seed_defaults($db)
    {
        if (!$db->table_exists('template_tasks')) {
            return;
        }

        $team = 'Srujan';
        $type = 'Setup & Onboarding';
        $exists = (int) $db->where('team', $team)
            ->where('template_type', $type)
            ->count_all_results('template_tasks');
        if ($exists > 0) {
            return;
        }

        $titles = array(
            'Client kickoff meeting',
            'Brand questionnaire',
            'Asset collection',
            'Access requests',
            'NDA/contracts',
            'GA4 setup',
            'GTM setup',
            'Meta Business Manager access',
            'Google Ads access',
            'Merchant Center access',
            'Pixel installation',
            'Conversion setup',
            'WhatsApp/API access',
            'Competitor audit',
            'Elintom Setup',
            'Audience Target',
        );

        $now = date('Y-m-d H:i:s');
        $order = 1;
        foreach ($titles as $title) {
            $db->insert('template_tasks', array(
                'team' => $team,
                'template_type' => $type,
                'title' => $title,
                'sort_order' => $order,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ));
            $order++;
        }
    }
}
