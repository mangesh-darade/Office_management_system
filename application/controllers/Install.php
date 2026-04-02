<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Install extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url']);
    }

    // GET /install/schema
    public function schema()
    {
        // Protect in non-development environments
        if (!in_array(ENVIRONMENT, ['development', 'testing'], true)) {
            show_error('Schema installer is disabled outside of development.', 403);
        }
        // Require admin login when not in CLI
        if (!$this->input->is_cli_request()) {
            $this->load->library('session');
            $role_id = (int)$this->session->userdata('role_id');
            if ($role_id !== 1) {
                show_error('Access denied. Only administrators can run the schema installer.', 403);
            }
        }

        $sql = $this->get_schema_sql();
        $errors = [];
        $executed = 0;

        // Split on semicolons carefully: our script contains no procedures/triggers
        $statements = array_filter(array_map('trim', explode(';', $sql)), function($s){ return strlen($s) > 0; });

        // Disable CI DB debug to prevent hard stops; handle errors manually
        $prev_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        // MySQL error codes to ignore as benign for idempotent runs
        // 1061: Duplicate key name (index exists)
        // 1062: Duplicate entry (seed already inserted with UNIQUE)
        // 1091: Can't DROP; check that column/key exists (in case of future drops)
        $ignore_codes = [1061, 1062, 1091];

        foreach ($statements as $statement) {
            $this->db->query($statement);
            $err = $this->db->error();
            if (!empty($err) && isset($err['code']) && (int)$err['code'] !== 0) {
                if (!in_array((int)$err['code'], $ignore_codes, true)) {
                    $errors[] = $err['code'] . ': ' . $err['message'] . " | SQL: " . $statement;
                }
            } else {
                $executed++;
            }
        }
        // Restore previous debug setting
        $this->db->db_debug = $prev_debug;

        // Simple HTML output
        $base = site_url('/');
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Installer</title>';
        echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></head><body class="p-4">';
        echo '<div class="container">';
        echo '<h1 class="mb-3">Database Schema Installer</h1>';
        echo '<p class="text-muted">Executed statements: <strong>' . (int)$executed . '</strong></p>';
        if ($errors) {
            echo '<div class="alert alert-danger"><strong>Errors:</strong><ul class="mb-0">';
            foreach ($errors as $err) echo '<li><code>' . htmlspecialchars($err) . '</code></li>';
            echo '</ul></div>';
        } else {
            echo '<div class="alert alert-success">All statements executed successfully.</div>';
        }
        echo '<a class="btn btn-primary" href="' . $base . '">Go to Home</a>';
        echo '</div></body></html>';
    }

    private function get_schema_sql()
    {
        return <<<SQL
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  last_login_at DATETIME NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  role_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NULL,
  phone VARCHAR(30) NULL,
  avatar VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_users_role ON users(role_id);

CREATE TABLE IF NOT EXISTS employees (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  emp_code VARCHAR(50) NOT NULL UNIQUE,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NULL,
  gender ENUM('male','female','other') NULL,
  dob DATE NULL,
  personal_email VARCHAR(190) NULL,
  phone VARCHAR(30) NULL,
  address TEXT NULL,
  city VARCHAR(100) NULL,
  state VARCHAR(100) NULL,
  country VARCHAR(100) NULL,
  zipcode VARCHAR(20) NULL,
  join_date DATE NULL,
  probation_end DATE NULL,
  department VARCHAR(120) NULL,
  designation VARCHAR(120) NULL,
  reporting_to BIGINT UNSIGNED NULL,
  employment_type ENUM('full_time','part_time','contract','intern') DEFAULT 'full_time',
  salary_ctc DECIMAL(12,2) NULL,
  emergency_contact_name VARCHAR(120) NULL,
  emergency_contact_phone VARCHAR(30) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_emp_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_emp_reporting FOREIGN KEY (reporting_to) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_employees_reporting_to ON employees(reporting_to);

CREATE TABLE IF NOT EXISTS projects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(190) NOT NULL,
  description TEXT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  status ENUM('planned','active','on_hold','completed','cancelled') NOT NULL DEFAULT 'planned',
  manager_id BIGINT UNSIGNED NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_projects_manager FOREIGN KEY (manager_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_projects_manager ON projects(manager_id);
CREATE INDEX idx_projects_status ON projects(status);

CREATE TABLE IF NOT EXISTS project_members (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  role ENUM('member','lead','viewer') NOT NULL DEFAULT 'member',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_project_user (project_id, user_id),
  CONSTRAINT fk_pm_project FOREIGN KEY (project_id) REFERENCES projects(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_pm_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_pm_user ON project_members(user_id);

CREATE TABLE IF NOT EXISTS project_status_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id BIGINT UNSIGNED NOT NULL,
  old_status ENUM('planned','active','on_hold','completed','cancelled') NULL,
  new_status ENUM('planned','active','on_hold','completed','cancelled') NOT NULL,
  changed_by BIGINT UNSIGNED NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_psh_project FOREIGN KEY (project_id) REFERENCES projects(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_psh_user FOREIGN KEY (changed_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_psh_project ON project_status_history(project_id);

CREATE TABLE IF NOT EXISTS tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(190) NOT NULL,
  description TEXT NULL,
  assigned_to BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  status ENUM('pending','in_progress','completed','blocked') NOT NULL DEFAULT 'pending',
  priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  start_date DATE NULL,
  due_date DATE NULL,
  completed_at DATETIME NULL,
  estimate_hours DECIMAL(6,2) NULL,
  actual_hours DECIMAL(6,2) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tasks_project FOREIGN KEY (project_id) REFERENCES projects(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_tasks_assigned FOREIGN KEY (assigned_to) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_tasks_creator FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_tasks_project ON tasks(project_id);
CREATE INDEX idx_tasks_assigned ON tasks(assigned_to);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_due ON tasks(due_date);

CREATE TABLE IF NOT EXISTS task_comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  comment TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tc_task FOREIGN KEY (task_id) REFERENCES tasks(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_tc_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_tc_task ON task_comments(task_id);

CREATE TABLE IF NOT EXISTS task_attachments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id BIGINT UNSIGNED NOT NULL,
  file_name VARCHAR(190) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NULL,
  size_bytes BIGINT NULL,
  uploaded_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ta_task FOREIGN KEY (task_id) REFERENCES tasks(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ta_user FOREIGN KEY (uploaded_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_ta_task ON task_attachments(task_id);

CREATE TABLE IF NOT EXISTS task_activity (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  action ENUM('created','updated','status_changed','assigned','commented','attachment_added') NOT NULL,
  old_value JSON NULL,
  new_value JSON NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tact_task FOREIGN KEY (task_id) REFERENCES tasks(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_tact_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_tact_task ON task_activity(task_id);

CREATE TABLE IF NOT EXISTS daily_work_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  work_date DATE NOT NULL,
  hours DECIMAL(5,2) NOT NULL,
  notes TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_worklog (user_id, task_id, work_date),
  CONSTRAINT fk_dwl_task FOREIGN KEY (task_id) REFERENCES tasks(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_dwl_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_dwl_user_date ON daily_work_logs(user_id, work_date);

CREATE TABLE IF NOT EXISTS attendance (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  att_date DATE NOT NULL,
  punch_in DATETIME NULL,
  punch_out DATETIME NULL,
  source ENUM('manual','auto') NOT NULL DEFAULT 'manual',
  total_hours DECIMAL(5,2) NULL,
  status ENUM('present','absent','half_day','work_from_home') NOT NULL DEFAULT 'present',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_attendance (user_id, att_date),
  CONSTRAINT fk_att_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_att_user_date ON attendance(user_id, att_date);

CREATE TABLE IF NOT EXISTS attendance_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  event ENUM('punch_in','punch_out','auto_login','auto_logout') NOT NULL,
  event_time DATETIME NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_al_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_al_user_time ON attendance_logs(user_id, event_time);

CREATE TABLE IF NOT EXISTS leave_types (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  annual_quota DECIMAL(5,2) NOT NULL DEFAULT 0,
  is_paid TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS leave_balances (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  type_id BIGINT UNSIGNED NOT NULL,
  year INT NOT NULL,
  opening_balance DECIMAL(5,2) NOT NULL DEFAULT 0,
  accrued DECIMAL(5,2) NOT NULL DEFAULT 0,
  used DECIMAL(5,2) NOT NULL DEFAULT 0,
  closing_balance DECIMAL(5,2) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_leave_balance (user_id, type_id, year),
  CONSTRAINT fk_lb_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_lb_type FOREIGN KEY (type_id) REFERENCES leave_types(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_lb_user_year ON leave_balances(user_id, year);

CREATE TABLE IF NOT EXISTS leave_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  type_id BIGINT UNSIGNED NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  days DECIMAL(5,2) NOT NULL,
  reason TEXT NULL,
  status ENUM('pending','lead_approved','hr_approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  current_approver_id BIGINT UNSIGNED NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_lr_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_lr_type FOREIGN KEY (type_id) REFERENCES leave_types(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_lr_approver FOREIGN KEY (current_approver_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_lr_user_status ON leave_requests(user_id, status);
CREATE INDEX idx_lr_start_date ON leave_requests(start_date);

CREATE TABLE IF NOT EXISTS leave_approvals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  leave_id BIGINT UNSIGNED NOT NULL,
  approver_id BIGINT UNSIGNED NOT NULL,
  level ENUM('lead','hr') NOT NULL,
  decision ENUM('approved','rejected') NOT NULL,
  remarks TEXT NULL,
  decided_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_la_leave FOREIGN KEY (leave_id) REFERENCES leave_requests(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_la_approver FOREIGN KEY (approver_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_la_leave ON leave_approvals(leave_id);

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  type ENUM('task_assigned','leave_request','leave_status','deadline_reminder','system') NOT NULL,
  title VARCHAR(190) NOT NULL,
  body TEXT NULL,
  payload JSON NULL,
  channel ENUM('in_app','email') NOT NULL DEFAULT 'in_app',
  read_at DATETIME NULL,
  sent_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_notif_user ON notifications(user_id);

CREATE TABLE IF NOT EXISTS activity_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_id BIGINT UNSIGNED NULL,
  entity_type VARCHAR(100) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  changes JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_actlog_actor FOREIGN KEY (actor_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_actlog_entity ON activity_log(entity_type, entity_id);

CREATE TABLE IF NOT EXISTS settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(120) NOT NULL UNIQUE,
  `value` TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- seeds
INSERT IGNORE INTO roles (name, description) VALUES
('admin','System Administrator'),
('hr','Human Resources'),
('lead','Team Lead'),
('employee','Regular Employee');

INSERT IGNORE INTO leave_types (name, description, annual_quota, is_paid) VALUES
('CL','Casual Leave', 6, 1),
('SL','Sick Leave', 6, 1),
('PL','Privilege Leave', 12, 1);

INSERT IGNORE INTO settings (`key`, `value`) VALUES
('auto_attendance','false'),
('work_hours_per_day','8'),
('deadline_reminder_hours','24');

CREATE TABLE IF NOT EXISTS `ta_assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `time_limit_minutes` int(11) NOT NULL DEFAULT 30,
  `passing_marks` decimal(5,2) NOT NULL DEFAULT 60.00 COMMENT 'Percentage 0–100',
  `randomize_questions` tinyint(1) NOT NULL DEFAULT 0,
  `shuffle_options` tinyint(1) NOT NULL DEFAULT 0,
  `max_attempts` int(11) NOT NULL DEFAULT 1 COMMENT '0 = unlimited',
  `allow_retake` tinyint(1) NOT NULL DEFAULT 0,
  `show_correct_after_submit` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Learner sees correct answers on result',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ta_assessments_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ta_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(11) NOT NULL,
  `question_type` enum('mcq','text','coding') NOT NULL DEFAULT 'mcq',
  `question_text` text NOT NULL,
  `points` decimal(6,2) NOT NULL DEFAULT 1.00,
  `coding_language` varchar(20) DEFAULT NULL COMMENT 'php or js',
  `model_answer` text COMMENT 'Text rubric / expected keywords, optional auto-compare',
  `coding_expected_output` text COMMENT 'Compared to trimmed execution output for coding',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ta_q_assessment` (`assessment_id`),
  CONSTRAINT `fk_ta_questions_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `ta_assessments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ta_question_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `option_text` varchar(500) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ta_qo_question` (`question_id`),
  CONSTRAINT `fk_ta_qo_question` FOREIGN KEY (`question_id`) REFERENCES `ta_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ta_assessment_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'users.id when assignee is an employee',
  `candidate_name` varchar(190) DEFAULT NULL,
  `candidate_email` varchar(190) DEFAULT NULL,
  `access_token` varchar(64) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` datetime NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `server_ends_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `attempts_used` int(11) NOT NULL DEFAULT 0,
  `question_order` text COMMENT 'CSV of question ids for this attempt',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ta_access_token` (`access_token`),
  KEY `idx_ta_au_assessment` (`assessment_id`),
  KEY `idx_ta_au_user` (`user_id`),
  CONSTRAINT `fk_ta_au_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `ta_assessments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ta_user_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option_id` int(11) DEFAULT NULL,
  `answer_text` text,
  `code_submitted` text,
  `execution_output` text,
  `is_graded_correct` tinyint(1) DEFAULT NULL,
  `points_earned` decimal(6,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ta_au_question` (`assessment_user_id`,`question_id`),
  KEY `idx_ta_ua_question` (`question_id`),
  CONSTRAINT `fk_ta_ua_au` FOREIGN KEY (`assessment_user_id`) REFERENCES `ta_assessment_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ta_ua_question` FOREIGN KEY (`question_id`) REFERENCES `ta_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ta_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_user_id` int(11) NOT NULL,
  `score_percent` decimal(6,2) NOT NULL DEFAULT 0.00,
  `total_points` decimal(8,2) NOT NULL DEFAULT 0.00,
  `earned_points` decimal(8,2) NOT NULL DEFAULT 0.00,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `duration_seconds` int(11) DEFAULT NULL,
  `submitted_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ta_result_au` (`assessment_user_id`),
  CONSTRAINT `fk_ta_res_au` FOREIGN KEY (`assessment_user_id`) REFERENCES `ta_assessment_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'training_assessment', 1),
(1, 'training_assessment_manage', 1),
(1, 'training_assessment_take', 1),
(2, 'training_assessment', 1),
(2, 'training_assessment_manage', 1),
(2, 'training_assessment_take', 1),
(3, 'training_assessment_take', 1),
(4, 'training_assessment_take', 1);

CREATE TABLE IF NOT EXISTS `training_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tm_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `training_topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `prerequisite_topic_id` int(11) DEFAULT NULL COMMENT 'Must complete this topic first (same module recommended)',
  `name` varchar(255) NOT NULL,
  `description` text,
  `prerequisites` text,
  `duration_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `has_assignment` tinyint(1) NOT NULL DEFAULT 0,
  `has_assessment` tinyint(1) NOT NULL DEFAULT 0,
  `assessment_id` int(11) DEFAULT NULL COMMENT 'Links to ta_assessments.id or assessments.id',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tt_module` (`module_id`),
  KEY `idx_tt_prereq` (`prerequisite_topic_id`),
  KEY `idx_tt_assessment` (`assessment_id`),
  CONSTRAINT `fk_tt_module` FOREIGN KEY (`module_id`) REFERENCES `training_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `details` text COMMENT 'Instructions for learner',
  `max_submissions` int(11) NOT NULL DEFAULT 0 COMMENT '0 = unlimited file uploads per user',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assign_topic` (`topic_id`),
  CONSTRAINT `fk_assign_topic` FOREIGN KEY (`topic_id`) REFERENCES `training_topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `assignment_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_ext` varchar(20) NOT NULL DEFAULT '',
  `file_size` int(11) NOT NULL DEFAULT 0,
  `mime_type` varchar(120) DEFAULT NULL,
  `status` enum('pending','submitted','assessed') NOT NULL DEFAULT 'submitted',
  `score` decimal(6,2) DEFAULT NULL,
  `feedback` text,
  `submitted_at` datetime NOT NULL,
  `assessed_at` datetime DEFAULT NULL,
  `assessed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sub_assign` (`assignment_id`),
  KEY `idx_sub_user` (`user_id`),
  CONSTRAINT `fk_sub_assign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `training_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_te_user_module` (`user_id`,`module_id`),
  KEY `idx_te_module` (`module_id`),
  CONSTRAINT `fk_te_module` FOREIGN KEY (`module_id`) REFERENCES `training_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `training_topic_completions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `completed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ttc_user_topic` (`user_id`,`topic_id`),
  KEY `idx_ttc_topic` (`topic_id`),
  CONSTRAINT `fk_ttc_topic` FOREIGN KEY (`topic_id`) REFERENCES `training_topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'training_lms', 1),
(1, 'training_lms_manage', 1),
(2, 'training_lms', 1),
(2, 'training_lms_manage', 1),
(3, 'training_lms', 1),
(4, 'training_lms', 1);

SET FOREIGN_KEY_CHECKS = 1;
SQL;
    }
}
