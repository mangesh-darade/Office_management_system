<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Expense categories and expenses tables
 */

if (!function_exists('expenses_schema_ensure')) {
    function expenses_schema_ensure($db)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        // Expense categories table
        if (!$db->table_exists('expense_categories')) {
            $sql = "CREATE TABLE `expense_categories` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `description` text,
                `budget_limit` decimal(10,2) DEFAULT NULL,
                `requires_receipt` tinyint(1) DEFAULT '1',
                `is_active` tinyint(1) DEFAULT '1',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
            
            // Insert default categories
            $categories = [
                ['name' => 'Travel', 'description' => 'Travel expenses', 'budget_limit' => 5000],
                ['name' => 'Food & Meals', 'description' => 'Food and meal expenses', 'budget_limit' => 1000],
                ['name' => 'Accommodation', 'description' => 'Hotel and lodging', 'budget_limit' => 3000],
                ['name' => 'Office Supplies', 'description' => 'Office supplies and stationery', 'budget_limit' => 2000],
                ['name' => 'Communication', 'description' => 'Phone and internet', 'budget_limit' => 1500],
                ['name' => 'Training', 'description' => 'Training and courses', 'budget_limit' => 10000],
                ['name' => 'Other', 'description' => 'Other expenses', 'budget_limit' => 2000]
            ];
            $db->insert_batch('expense_categories', $categories);
        }
        
        // Expenses table
        if (!$db->table_exists('expenses')) {
            $sql = "CREATE TABLE `expenses` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `category_id` int(11) NOT NULL,
                `amount` decimal(10,2) NOT NULL,
                `description` text NOT NULL,
                `expense_date` date NOT NULL,
                `receipt_path` varchar(500) DEFAULT NULL,
                `status` enum('pending','approved','rejected','reimbursed') DEFAULT 'pending',
                `approved_by` int(11) DEFAULT NULL,
                `approved_at` datetime DEFAULT NULL,
                `rejection_reason` text,
                `reimbursed_by` int(11) DEFAULT NULL,
                `reimbursed_at` datetime DEFAULT NULL,
                `reimbursement_reference` varchar(100) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_status` (`status`),
                KEY `idx_expense_date` (`expense_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $db->query($sql);
        }
    }
}
