<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Table to Module Mapping
 * 
 * Maps database table names to module names for automatic activity logging
 * This allows automatic tracking of changes across all modules
 */
$config['table_module_mapping'] = array(
    // Employee Management
    'employees' => 'employees',
    'departments' => 'departments',
    'designations' => 'designations',
    
    // Project Management
    'projects' => 'projects',
    'project_members' => 'projects',
    'requirements' => 'requirements',
    
    // Task Management
    'tasks' => 'tasks',
    'task_comments' => 'tasks',
    'task_attachments' => 'tasks',
    'task_activity' => 'tasks',
    
    // Attendance
    'attendance' => 'attendance',
    'attendance_logs' => 'attendance',
    
    // Leave Management
    'leave_requests' => 'leave_requests',
    'leave_types' => 'leave_types',
    'leave_balances' => 'leave_balances',
    'leave_approvals' => 'leave_requests',
    
    // Timesheet
    'timesheets' => 'timesheets',
    'timesheet_entries' => 'timesheets',
    
    // User Management
    'users' => 'users',
    'roles' => 'roles',
    'permissions' => 'permissions',
    
    // Settings
    'settings' => 'settings',
    
    // Notifications
    'notifications' => 'notifications',
    
    // Chats & Calls
    'conversations' => 'chats',
    'messages' => 'chats',
    'calls' => 'calls',
    'call_participants' => 'calls',
    
    // Assets
    'assets' => 'assets',
    
    // Reminders
    'reminders' => 'reminders',
    
    // Announcements
    'announcements' => 'announcements',
    
    // Clients
    'clients' => 'clients',
);

