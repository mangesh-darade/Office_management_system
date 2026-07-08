<?php
/**
 * Comprehensive DB Schema Check - All Modules
 * Database: admin_stadmin_internal_portal
 * 
 * Checks every table+column required by each module.
 * If a column is missing, it is AUTO-CREATED with the defined type.
 * Tables that are missing entirely are flagged (not auto-created to avoid data loss).
 */

$db = new mysqli('localhost', 'root', '', 'admin_stadmin_internal_portal');
if ($db->connect_error) {
    die('Connection failed: ' . $db->connect_error . PHP_EOL);
}

// ============================================================
// SCHEMA DEFINITION
// Format: 'table' => [ 'column' => 'SQL type definition' ]
// Primary keys (id) are checked but not added automatically.
// ============================================================
$schema = [

    // ---- CORE USERS & AUTH ----
    'users' => [
        'id'              => '__PK__',
        'name'            => 'VARCHAR(300) DEFAULT NULL',
        'email'           => 'VARCHAR(300) DEFAULT NULL',
        'full_name'       => 'VARCHAR(300) DEFAULT NULL',
        'password'        => 'VARCHAR(255) DEFAULT NULL',
        'role_id'         => 'INT(11) DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'active'",
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
        'last_login'      => 'DATETIME DEFAULT NULL',
        'profile_photo'   => 'VARCHAR(500) DEFAULT NULL',
    ],
    'roles' => [
        'id'              => '__PK__',
        'name'            => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'description'     => 'TEXT DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'permissions' => [
        'id'              => '__PK__',
        'module'          => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'role_id'         => 'INT(11) DEFAULT NULL',
        'can_access'      => "TINYINT(1) NOT NULL DEFAULT '1'",
    ],
    'role_permissions' => [
        'id'              => '__PK__',
        'role_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'module_key'      => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'can_access'      => "TINYINT(1) NOT NULL DEFAULT '1'",
    ],
    'sessions' => [
        'id'              => 'VARCHAR(128) NOT NULL',
        'ip_address'      => 'VARCHAR(45) NOT NULL DEFAULT ""',
        'timestamp'       => 'INT(10) UNSIGNED NOT NULL DEFAULT 0',
        'data'            => 'BLOB NOT NULL',
    ],

    // ---- EMPLOYEES ----
    'employees' => [
        'id'              => '__PK__',
        'user_id'         => 'INT(11) DEFAULT NULL',
        'name'            => 'VARCHAR(300) NOT NULL DEFAULT ""',
        'emp_code'        => 'VARCHAR(100) DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'active'",
        'department_id'   => 'INT(11) DEFAULT NULL',
        'designation_id'  => 'INT(11) DEFAULT NULL',
        'joining_date'    => 'DATE DEFAULT NULL',
        'phone'           => 'VARCHAR(50) DEFAULT NULL',
        'gender'          => 'VARCHAR(20) DEFAULT NULL',
        'date_of_birth'   => 'DATE DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'departments' => [
        'id'              => '__PK__',
        'name'            => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'description'     => 'TEXT DEFAULT NULL',
    ],
    'designations' => [
        'id'              => '__PK__',
        'name'            => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'department_id'   => 'INT(11) DEFAULT NULL',
    ],

    // ---- TASKS ----
    'tasks' => [
        'id'              => '__PK__',
        'title'           => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'description'     => 'TEXT DEFAULT NULL',
        'status'          => "VARCHAR(100) NOT NULL DEFAULT 'pending'",
        'priority'        => 'VARCHAR(50) DEFAULT NULL',
        'project_id'      => 'INT(11) DEFAULT NULL',
        'assigned_to'     => 'INT(11) DEFAULT NULL',
        'created_by'      => 'INT(11) DEFAULT NULL',
        'due_date'        => 'DATE DEFAULT NULL',
        'start_date'      => 'DATE DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
        'updated_at'      => 'DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP',
    ],
    'task_comments' => [
        'id'              => '__PK__',
        'task_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'user_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'comment'         => 'TEXT NOT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'task_attachments' => [
        'id'              => '__PK__',
        'task_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'user_id'         => 'INT(11) DEFAULT NULL',
        'file_name'       => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'stored_name'     => 'VARCHAR(500) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'task_activity' => [
        'id'              => '__PK__',
        'task_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'user_id'         => 'INT(11) DEFAULT NULL',
        'action'          => 'VARCHAR(200) DEFAULT NULL',
        'detail'          => 'TEXT DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'template_tasks' => [
        'id'              => '__PK__',
        'title'           => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'description'     => 'TEXT DEFAULT NULL',
        'priority'        => 'VARCHAR(50) DEFAULT NULL',
        'created_by'      => 'INT(11) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- PROJECTS ----
    'projects' => [
        'id'              => '__PK__',
        'name'            => 'VARCHAR(300) NOT NULL DEFAULT ""',
        'description'     => 'TEXT DEFAULT NULL',
        'status'          => "VARCHAR(100) NOT NULL DEFAULT 'active'",
        'client_id'       => 'INT(11) DEFAULT NULL',
        'start_date'      => 'DATE DEFAULT NULL',
        'end_date'        => 'DATE DEFAULT NULL',
        'created_by'      => 'INT(11) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
        'department_id'   => 'INT(11) DEFAULT NULL',
    ],
    'project_members' => [
        'id'              => '__PK__',
        'project_id'      => 'INT(11) NOT NULL DEFAULT 0',
        'user_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'role'            => 'VARCHAR(100) DEFAULT NULL',
        'added_at'        => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'project_defects' => [
        'id'              => '__PK__',
        'project_id'      => 'INT(11) NOT NULL DEFAULT 0',
        'title'           => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'description'     => 'TEXT DEFAULT NULL',
        'severity'        => 'VARCHAR(50) DEFAULT NULL',
        'status'          => "VARCHAR(100) NOT NULL DEFAULT 'open'",
        'reported_by'     => 'INT(11) DEFAULT NULL',
        'assigned_to'     => 'INT(11) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'project_releases' => [
        'id'              => '__PK__',
        'project_id'      => 'INT(11) NOT NULL DEFAULT 0',
        'version'         => 'VARCHAR(100) DEFAULT NULL',
        'title'           => 'VARCHAR(500) DEFAULT NULL',
        'release_date'    => 'DATE DEFAULT NULL',
        'created_by'      => 'INT(11) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'project_release_notes' => [
        'id'              => '__PK__',
        'release_id'      => 'INT(11) NOT NULL DEFAULT 0',
        'note'            => 'TEXT NOT NULL',
        'note_type'       => "VARCHAR(50) DEFAULT 'feature'",
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'project_status_history' => [
        'id'              => '__PK__',
        'project_id'      => 'INT(11) NOT NULL DEFAULT 0',
        'old_status'      => 'VARCHAR(100) DEFAULT NULL',
        'new_status'      => 'VARCHAR(100) DEFAULT NULL',
        'changed_by'      => 'INT(11) DEFAULT NULL',
        'changed_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- REQUIREMENTS ----
    'requirements' => [
        'id'                       => '__PK__',
        'title'                    => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'description'              => 'TEXT DEFAULT NULL',
        'status'                   => "VARCHAR(100) NOT NULL DEFAULT 'pending'",
        'project_id'               => 'INT(11) DEFAULT NULL',
        'assigned_to'              => 'INT(11) DEFAULT NULL',
        'created_by'               => 'INT(11) DEFAULT NULL',
        'expected_delivery_date'   => 'DATE DEFAULT NULL',
        'due_date'                 => 'DATE DEFAULT NULL',
        'received_date'            => 'DATE DEFAULT NULL',
        'created_at'               => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
        'updated_at'               => 'DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP',
    ],
    'requirement_comments' => [
        'id'              => '__PK__',
        'requirement_id'  => 'INT(11) NOT NULL DEFAULT 0',
        'user_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'comment'         => 'TEXT NOT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'requirement_attachments' => [
        'id'              => '__PK__',
        'requirement_id'  => 'INT(11) NOT NULL DEFAULT 0',
        'file_name'       => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'stored_name'     => 'VARCHAR(500) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'requirement_versions' => [
        'id'              => '__PK__',
        'requirement_id'  => 'INT(11) NOT NULL DEFAULT 0',
        'version'         => 'INT(11) NOT NULL DEFAULT 1',
        'data'            => 'TEXT DEFAULT NULL',
        'changed_by'      => 'INT(11) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- MY WORKS (Second Brain / Project Tasks) ----
    'my_works' => [
        'id'              => '__PK__',
        'title'           => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'description'     => 'TEXT DEFAULT NULL',
        'status'          => "VARCHAR(100) NOT NULL DEFAULT 'New'",
        'type_id'         => 'INT(11) DEFAULT NULL',
        'project_id'      => 'INT(11) DEFAULT NULL',
        'created_by'      => 'INT(11) DEFAULT NULL',
        'created_for'     => 'INT(11) DEFAULT NULL',
        'due_date'        => 'DATE DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
        'updated_at'      => 'DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP',
        'closing_comment' => 'TEXT DEFAULT NULL',
        'url'             => 'VARCHAR(1000) DEFAULT NULL',
        'tag'             => 'VARCHAR(300) DEFAULT NULL',
        'urgent'          => "TINYINT(1) NOT NULL DEFAULT '0'",
        'important'       => "TINYINT(1) NOT NULL DEFAULT '0'",
    ],
    'my_work_comments' => [
        'id'              => '__PK__',
        'work_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'user_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'comment'         => 'TEXT NOT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'my_work_attachments' => [
        'id'              => '__PK__',
        'work_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'original_name'   => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'stored_name'     => 'VARCHAR(500) DEFAULT NULL',
        'file_size'       => 'INT(11) DEFAULT 0',
        'sort_order'      => 'INT(11) DEFAULT 0',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'my_work_activity' => [
        'id'              => '__PK__',
        'work_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'user_id'         => 'INT(11) DEFAULT NULL',
        'action'          => 'VARCHAR(200) DEFAULT NULL',
        'detail'          => 'TEXT DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'module_types' => [
        'id'              => '__PK__',
        'module'          => 'VARCHAR(100) NOT NULL DEFAULT ""',
        'name'            => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'sort_order'      => 'INT(11) DEFAULT 0',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- ATTENDANCE ----
    'attendance' => [
        'id'              => '__PK__',
        'employee_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'date'            => 'DATE NOT NULL',
        'check_in'        => 'TIME DEFAULT NULL',
        'check_out'       => 'TIME DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'present'",
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'attendance_logs' => [
        'id'              => '__PK__',
        'attendance_id'   => 'INT(11) NOT NULL DEFAULT 0',
        'action'          => 'VARCHAR(50) DEFAULT NULL',
        'logged_at'       => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
        'ip_address'      => 'VARCHAR(50) DEFAULT NULL',
    ],
    'shifts' => [
        'id'              => '__PK__',
        'name'            => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'start_time'      => 'TIME DEFAULT NULL',
        'end_time'        => 'TIME DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- LEAVE ----
    'leave_types' => [
        'id'              => '__PK__',
        'name'            => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'days_allowed'    => 'INT(11) DEFAULT 0',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'leave_requests' => [
        'id'              => '__PK__',
        'employee_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'leave_type_id'   => 'INT(11) NOT NULL DEFAULT 0',
        'from_date'       => 'DATE NOT NULL',
        'to_date'         => 'DATE NOT NULL',
        'reason'          => 'TEXT DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'pending'",
        'approved_by'     => 'INT(11) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'leave_balances' => [
        'id'              => '__PK__',
        'employee_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'leave_type_id'   => 'INT(11) NOT NULL DEFAULT 0',
        'balance'         => 'DECIMAL(5,2) DEFAULT 0.00',
        'year'            => 'INT(4) DEFAULT NULL',
    ],
    'leave_approvals' => [
        'id'              => '__PK__',
        'leave_request_id'=> 'INT(11) NOT NULL DEFAULT 0',
        'approver_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'pending'",
        'comment'         => 'TEXT DEFAULT NULL',
        'decided_at'      => 'DATETIME DEFAULT NULL',
    ],
    'holidays' => [
        'id'              => '__PK__',
        'name'            => 'VARCHAR(300) NOT NULL DEFAULT ""',
        'date'            => 'DATE NOT NULL',
        'type'            => "VARCHAR(50) DEFAULT 'public'",
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- PAYROLL ----
    'salary_structures' => [
        'id'              => '__PK__',
        'employee_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'basic'           => 'DECIMAL(12,2) DEFAULT 0.00',
        'hra'             => 'DECIMAL(12,2) DEFAULT 0.00',
        'allowances'      => 'DECIMAL(12,2) DEFAULT 0.00',
        'deductions'      => 'DECIMAL(12,2) DEFAULT 0.00',
        'net_salary'      => 'DECIMAL(12,2) DEFAULT 0.00',
        'effective_from'  => 'DATE DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'payslips' => [
        'id'              => '__PK__',
        'employee_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'month'           => 'INT(2) NOT NULL',
        'year'            => 'INT(4) NOT NULL',
        'gross'           => 'DECIMAL(12,2) DEFAULT 0.00',
        'net'             => 'DECIMAL(12,2) DEFAULT 0.00',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'draft'",
        'generated_at'    => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- EXPENSES ----
    'expense_categories' => [
        'id'              => '__PK__',
        'name'            => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'expenses' => [
        'id'              => '__PK__',
        'employee_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'category_id'     => 'INT(11) DEFAULT NULL',
        'amount'          => 'DECIMAL(12,2) DEFAULT 0.00',
        'description'     => 'TEXT DEFAULT NULL',
        'date'            => 'DATE NOT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'pending'",
        'receipt'         => 'VARCHAR(500) DEFAULT NULL',
        'approved_by'     => 'INT(11) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- CLIENTS ----
    'clients' => [
        'id'              => '__PK__',
        'name'            => 'VARCHAR(300) NOT NULL DEFAULT ""',
        'email'           => 'VARCHAR(300) DEFAULT NULL',
        'phone'           => 'VARCHAR(50) DEFAULT NULL',
        'company'         => 'VARCHAR(300) DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'active'",
        'created_by'      => 'INT(11) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'client_contacts' => [
        'id'              => '__PK__',
        'client_id'       => 'INT(11) NOT NULL DEFAULT 0',
        'name'            => 'VARCHAR(300) NOT NULL DEFAULT ""',
        'email'           => 'VARCHAR(300) DEFAULT NULL',
        'phone'           => 'VARCHAR(50) DEFAULT NULL',
        'designation'     => 'VARCHAR(200) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- MEETINGS ----
    'scheduled_meetings' => [
        'id'              => '__PK__',
        'title'           => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'description'     => 'TEXT DEFAULT NULL',
        'start_time'      => 'DATETIME NOT NULL',
        'end_time'        => 'DATETIME DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'scheduled'",
        'created_by'      => 'INT(11) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'active_meetings' => [
        'id'              => '__PK__',
        'meeting_id'      => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'host_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'title'           => 'VARCHAR(500) DEFAULT NULL',
        'started_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
        'ended_at'        => 'DATETIME DEFAULT NULL',
    ],

    // ---- ANNOUNCEMENTS ----
    'announcements' => [
        'id'              => '__PK__',
        'title'           => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'content'         => 'TEXT DEFAULT NULL',
        'posted_by'       => 'INT(11) DEFAULT NULL',
        'target_role'     => 'VARCHAR(100) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- NOTIFICATIONS ----
    'notifications' => [
        'id'              => '__PK__',
        'user_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'title'           => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'body'            => 'TEXT DEFAULT NULL',
        'type'            => 'VARCHAR(100) DEFAULT NULL',
        'is_read'         => "TINYINT(1) NOT NULL DEFAULT '0'",
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- TIMESHEETS ----
    'timesheets' => [
        'id'              => '__PK__',
        'employee_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'project_id'      => 'INT(11) DEFAULT NULL',
        'task_id'         => 'INT(11) DEFAULT NULL',
        'date'            => 'DATE NOT NULL',
        'hours'           => 'DECIMAL(5,2) DEFAULT 0.00',
        'description'     => 'TEXT DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'pending'",
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'timesheet_entries' => [
        'id'              => '__PK__',
        'timesheet_id'    => 'INT(11) NOT NULL DEFAULT 0',
        'task_id'         => 'INT(11) DEFAULT NULL',
        'hours'           => 'DECIMAL(5,2) DEFAULT 0.00',
        'note'            => 'TEXT DEFAULT NULL',
    ],

    // ---- RECRUITMENT ----
    'recruitment_job_posts' => [
        'id'              => '__PK__',
        'title'           => 'VARCHAR(300) NOT NULL DEFAULT ""',
        'description'     => 'TEXT DEFAULT NULL',
        'department_id'   => 'INT(11) DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'open'",
        'created_by'      => 'INT(11) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'recruitment_candidates' => [
        'id'              => '__PK__',
        'job_post_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'name'            => 'VARCHAR(300) NOT NULL DEFAULT ""',
        'email'           => 'VARCHAR(300) DEFAULT NULL',
        'phone'           => 'VARCHAR(50) DEFAULT NULL',
        'resume'          => 'VARCHAR(500) DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'applied'",
        'applied_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'recruitment_interviews' => [
        'id'              => '__PK__',
        'candidate_id'    => 'INT(11) NOT NULL DEFAULT 0',
        'interviewer_id'  => 'INT(11) DEFAULT NULL',
        'scheduled_at'    => 'DATETIME DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'scheduled'",
        'feedback'        => 'TEXT DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- REMINDERS ----
    'reminders' => [
        'id'              => '__PK__',
        'user_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'title'           => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'remind_at'       => 'DATETIME NOT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'pending'",
        'sent'            => "TINYINT(1) NOT NULL DEFAULT '0'",
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'reminder_templates' => [
        'id'              => '__PK__',
        'name'            => 'VARCHAR(300) NOT NULL DEFAULT ""',
        'message'         => 'TEXT NOT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'reminder_schedules' => [
        'id'              => '__PK__',
        'reminder_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'scheduled_at'    => 'DATETIME NOT NULL',
        'sent'            => "TINYINT(1) NOT NULL DEFAULT '0'",
    ],

    // ---- REWARDS ----
    'reward_categories' => [
        'id'              => '__PK__',
        'name'            => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'points'          => 'INT(11) DEFAULT 0',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'reward_transactions' => [
        'id'              => '__PK__',
        'employee_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'category_id'     => 'INT(11) DEFAULT NULL',
        'points'          => 'INT(11) DEFAULT 0',
        'reason'          => 'TEXT DEFAULT NULL',
        'awarded_by'      => 'INT(11) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'reward_leaderboard' => [
        'id'              => '__PK__',
        'employee_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'total_points'    => 'INT(11) DEFAULT 0',
        'rank'            => 'INT(11) DEFAULT NULL',
        'updated_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ],
    'user_reward_summary' => [
        'id'              => '__PK__',
        'user_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'total_points'    => 'INT(11) DEFAULT 0',
        'redeemed_points' => 'INT(11) DEFAULT 0',
        'updated_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ],

    // ---- HELPDESK ----
    'helpdesk_tickets' => [
        'id'              => '__PK__',
        'subject'         => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'description'     => 'TEXT DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'open'",
        'priority'        => "VARCHAR(50) NOT NULL DEFAULT 'medium'",
        'created_by'      => 'INT(11) DEFAULT NULL',
        'assigned_to'     => 'INT(11) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- KNOWLEDGE BASE ----
    'kb_articles' => [
        'id'              => '__PK__',
        'title'           => 'VARCHAR(500) NOT NULL DEFAULT ""',
        'content'         => 'LONGTEXT DEFAULT NULL',
        'category'        => 'VARCHAR(200) DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'published'",
        'created_by'      => 'INT(11) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- ASSETS ----
    'assets' => [
        'id'              => '__PK__',
        'name'            => 'VARCHAR(300) NOT NULL DEFAULT ""',
        'asset_code'      => 'VARCHAR(100) DEFAULT NULL',
        'type'            => 'VARCHAR(100) DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'available'",
        'purchase_date'   => 'DATE DEFAULT NULL',
        'purchase_cost'   => 'DECIMAL(12,2) DEFAULT 0.00',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'asset_allocations' => [
        'id'              => '__PK__',
        'asset_id'        => 'INT(11) NOT NULL DEFAULT 0',
        'employee_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'allocated_at'    => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
        'returned_at'     => 'DATETIME DEFAULT NULL',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'active'",
    ],

    // ---- APPROVALS ----
    'approval_requests' => [
        'id'              => '__PK__',
        'module'          => 'VARCHAR(100) NOT NULL DEFAULT ""',
        'reference_id'    => 'INT(11) NOT NULL DEFAULT 0',
        'requested_by'    => 'INT(11) NOT NULL DEFAULT 0',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'pending'",
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'approval_steps' => [
        'id'              => '__PK__',
        'flow_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'approver_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'step_order'      => 'INT(11) DEFAULT 1',
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'pending'",
    ],
    'approval_flows' => [
        'id'              => '__PK__',
        'module'          => 'VARCHAR(100) NOT NULL DEFAULT ""',
        'name'            => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],
    'approval_logs' => [
        'id'              => '__PK__',
        'request_id'      => 'INT(11) NOT NULL DEFAULT 0',
        'approver_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'action'          => 'VARCHAR(50) DEFAULT NULL',
        'comment'         => 'TEXT DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- ACTIVITY LOG ----
    'activity_log' => [
        'id'              => '__PK__',
        'user_id'         => 'INT(11) DEFAULT NULL',
        'module'          => 'VARCHAR(100) DEFAULT NULL',
        'action'          => 'VARCHAR(200) DEFAULT NULL',
        'description'     => 'TEXT DEFAULT NULL',
        'ip_address'      => 'VARCHAR(50) DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- STATUSES (custom per module) ----
    'statuses' => [
        'id'              => '__PK__',
        'module'          => 'VARCHAR(100) NOT NULL DEFAULT ""',
        'code'            => 'VARCHAR(100) NOT NULL DEFAULT ""',
        'name'            => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'color'           => 'VARCHAR(20) DEFAULT NULL',
        'sort_order'      => 'INT(11) DEFAULT 0',
        'is_default'      => "TINYINT(1) NOT NULL DEFAULT '0'",
        'is_closed'       => "TINYINT(1) NOT NULL DEFAULT '0'",
    ],

    // ---- SETTINGS ----
    'settings' => [
        'id'              => '__PK__',
        'key'             => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'value'           => 'TEXT DEFAULT NULL',
        'updated_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ],
    'system_settings' => [
        'id'              => '__PK__',
        'setting_key'     => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'setting_value'   => 'TEXT DEFAULT NULL',
        'updated_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ],

    // ---- CALLS ----
    'calls' => [
        'id'              => '__PK__',
        'caller_id'       => 'INT(11) NOT NULL DEFAULT 0',
        'callee_id'       => 'INT(11) NOT NULL DEFAULT 0',
        'type'            => "VARCHAR(50) NOT NULL DEFAULT 'audio'",
        'status'          => "VARCHAR(50) NOT NULL DEFAULT 'missed'",
        'started_at'      => 'DATETIME DEFAULT NULL',
        'ended_at'        => 'DATETIME DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- DAILY ACTIVITY / WORK LOG ----
    'daily_work_logs' => [
        'id'              => '__PK__',
        'employee_id'     => 'INT(11) NOT NULL DEFAULT 0',
        'date'            => 'DATE NOT NULL',
        'work_done'       => 'TEXT DEFAULT NULL',
        'plan_tomorrow'   => 'TEXT DEFAULT NULL',
        'created_at'      => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    // ---- USER MODULE ACCESS ----
    'user_module_access' => [
        'id'              => '__PK__',
        'user_id'         => 'INT(11) NOT NULL DEFAULT 0',
        'module_key'      => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'can_access'      => "TINYINT(1) NOT NULL DEFAULT '1'",
    ],
    'modules' => [
        'id'              => '__PK__',
        'key'             => 'VARCHAR(200) NOT NULL DEFAULT ""',
        'name'            => 'VARCHAR(300) NOT NULL DEFAULT ""',
        'parent_key'      => 'VARCHAR(200) DEFAULT NULL',
        'sort_order'      => 'INT(11) DEFAULT 0',
    ],
];

// ============================================================
// RUN THE CHECK
// ============================================================
$total_ok      = 0;
$total_created = 0;
$total_missing_tables = [];
$created_list  = [];
$error_list    = [];

echo str_repeat('=', 60) . PHP_EOL;
echo " COMPREHENSIVE DB SCHEMA CHECK — ALL MODULES" . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL . PHP_EOL;

foreach ($schema as $table => $cols) {
    $res = $db->query("SHOW TABLES LIKE '$table'");
    if ($res->num_rows === 0) {
        $total_missing_tables[] = $table;
        echo "[MISSING TABLE] $table\n";
        continue;
    }

    $col_results = ['ok' => [], 'created' => [], 'error' => []];

    foreach ($cols as $col => $definition) {
        if ($definition === '__PK__') {
            $r = $db->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
            $status = ($r->num_rows > 0) ? 'ok' : 'missing_pk';
            if ($status === 'ok') { $total_ok++; $col_results['ok'][] = $col; }
            else { $col_results['error'][] = "$col (PK – manual review)"; }
            continue;
        }

        $r2 = $db->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($r2->num_rows === 0) {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$col` $definition";
            if ($db->query($sql)) {
                $total_created++;
                $col_results['created'][] = $col;
                $created_list[] = "$table.$col";
            } else {
                $col_results['error'][] = "$col (" . $db->error . ")";
                $error_list[] = "$table.$col: " . $db->error;
            }
        } else {
            $total_ok++;
            $col_results['ok'][] = $col;
        }
    }

    $ok_str      = count($col_results['ok'])      > 0 ? ' OK(' . count($col_results['ok']) . ')' : '';
    $created_str = count($col_results['created'])  > 0 ? ' +CREATED(' . implode(', ', $col_results['created']) . ')' : '';
    $err_str     = count($col_results['error'])    > 0 ? ' ERR(' . implode('; ', $col_results['error']) . ')' : '';

    echo "[TABLE: $table]$ok_str$created_str$err_str\n";
}

// ============================================================
// SUMMARY
// ============================================================
echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
echo " SUMMARY" . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
echo "Tables checked:        " . count($schema) . PHP_EOL;
echo "Missing tables:        " . count($total_missing_tables) . PHP_EOL;
echo "Columns OK:            $total_ok" . PHP_EOL;
echo "Columns auto-created:  $total_created" . PHP_EOL;
echo "Errors:                " . count($error_list) . PHP_EOL;

if ($created_list) {
    echo PHP_EOL . "Created columns:" . PHP_EOL;
    foreach ($created_list as $c) { echo "  + $c\n"; }
}
if ($total_missing_tables) {
    echo PHP_EOL . "Missing tables (not auto-created):" . PHP_EOL;
    foreach ($total_missing_tables as $t) { echo "  ! $t\n"; }
}
if ($error_list) {
    echo PHP_EOL . "Errors:" . PHP_EOL;
    foreach ($error_list as $e) { echo "  X $e\n"; }
}

$db->close();
