<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Create timesheets tables
 * 
 * Creates timesheets and timesheet_entries tables
 */
class Migration_Create_timesheets_tables extends CI_Migration {

    public function up()
    {
        // Create timesheets table
        if (!$this->db->table_exists('timesheets')) {
            $this->db->query("CREATE TABLE timesheets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                week_start_date DATE NOT NULL,
                week_end_date DATE NOT NULL,
                total_hours DECIMAL(5,2) DEFAULT 0,
                status ENUM('draft','submitted','approved','rejected') DEFAULT 'draft',
                submitted_at DATETIME NULL,
                approved_by INT NULL,
                approved_at DATETIME NULL,
                comments TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_week (user_id, week_start_date),
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_week_start (week_start_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        
        // Create timesheet_entries table
        if (!$this->db->table_exists('timesheet_entries')) {
            $this->db->query("CREATE TABLE timesheet_entries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                timesheet_id INT NOT NULL,
                task_id INT NULL,
                project_id INT NULL,
                work_date DATE NOT NULL,
                hours DECIMAL(5,2) NOT NULL,
                description TEXT NULL,
                billable TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_timesheet_id (timesheet_id),
                INDEX idx_work_date (work_date),
                INDEX idx_project_id (project_id),
                INDEX idx_task_id (task_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function down()
    {
        if ($this->db->table_exists('timesheet_entries')) {
            $this->db->query("DROP TABLE IF EXISTS timesheet_entries");
        }
        
        if ($this->db->table_exists('timesheets')) {
            $this->db->query("DROP TABLE IF EXISTS timesheets");
        }
    }
}

