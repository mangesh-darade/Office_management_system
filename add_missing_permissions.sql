-- =====================================================
-- SQL Insert Queries for Missing Permissions
-- Office Management System
-- Generated: 2026-01-26
-- =====================================================

-- This file contains INSERT queries to add missing module permissions
-- to the permissions table for all roles (Admin, Manager, Lead, Staff)

-- =====================================================
-- MISSING MODULES FROM SIDEBAR
-- =====================================================

-- 1. SendGrid Email API Module
-- Admin Group (Roles 1, 2, 3) - Full Access
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'sendgrid', 1),
(2, 'sendgrid', 1),
(3, 'sendgrid', 1),
(4, 'sendgrid', 0);

-- 2. Email Settings Module
-- Admin Group (Roles 1, 2, 3) - Full Access
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'email_settings', 1),
(2, 'email_settings', 1),
(3, 'email_settings', 1),
(4, 'email_settings', 0);

-- 3. Role Management Module
-- Admin Only (Role 1) - Full Access
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'roles', 1),
(2, 'roles', 0),
(3, 'roles', 0),
(4, 'roles', 0);

-- 4. Admin Access Module
-- Admin Group (Roles 1, 2, 3) - Full Access
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'admin', 1),
(2, 'admin', 1),
(3, 'admin', 1),
(4, 'admin', 0);

-- 5. Notifications Module
-- All Roles - Full Access
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'notifications', 1),
(2, 'notifications', 1),
(3, 'notifications', 1),
(4, 'notifications', 1);

-- =====================================================
-- OPTIONAL: COMPLETE PERMISSIONS RESET
-- =====================================================
-- Uncomment the following section if you want to regenerate
-- ALL permissions from scratch (WARNING: This will delete existing permissions)

/*
-- Clear existing permissions
TRUNCATE TABLE `permissions`;

-- Dashboard Module (All Roles)
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'dashboard', 1),
(2, 'dashboard', 1),
(3, 'dashboard', 1),
(4, 'dashboard', 1);

-- User Management Modules
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'users', 1), (2, 'users', 1), (3, 'users', 1), (4, 'users', 0),
(1, 'users_list', 1), (2, 'users_list', 1), (3, 'users_list', 1), (4, 'users_list', 0),
(1, 'users_add', 1), (2, 'users_add', 1), (3, 'users_add', 0), (4, 'users_add', 0),
(1, 'users_edit', 1), (2, 'users_edit', 1), (3, 'users_edit', 0), (4, 'users_edit', 0),
(1, 'users_delete', 1), (2, 'users_delete', 1), (3, 'users_delete', 0), (4, 'users_delete', 0),
(1, 'employees', 1), (2, 'employees', 1), (3, 'employees', 1), (4, 'employees', 0),
(1, 'employees_list', 1), (2, 'employees_list', 1), (3, 'employees_list', 1), (4, 'employees_list', 0),
(1, 'employees_add', 1), (2, 'employees_add', 1), (3, 'employees_add', 0), (4, 'employees_add', 0),
(1, 'employees_edit', 1), (2, 'employees_edit', 1), (3, 'employees_edit', 0), (4, 'employees_edit', 0),
(1, 'employees_delete', 1), (2, 'employees_delete', 1), (3, 'employees_delete', 0), (4, 'employees_delete', 0),
(1, 'departments', 1), (2, 'departments', 1), (3, 'departments', 1), (4, 'departments', 0),
(1, 'designations', 1), (2, 'designations', 1), (3, 'designations', 1), (4, 'designations', 0),
(1, 'permissions', 1), (2, 'permissions', 0), (3, 'permissions', 0), (4, 'permissions', 0);

-- Project Management Modules
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'projects', 1), (2, 'projects', 1), (3, 'projects', 1), (4, 'projects', 1),
(1, 'projects_list', 1), (2, 'projects_list', 1), (3, 'projects_list', 1), (4, 'projects_list', 1),
(1, 'projects_add', 1), (2, 'projects_add', 1), (3, 'projects_add', 1), (4, 'projects_add', 0),
(1, 'projects_edit', 1), (2, 'projects_edit', 1), (3, 'projects_edit', 1), (4, 'projects_edit', 0),
(1, 'projects_delete', 1), (2, 'projects_delete', 1), (3, 'projects_delete', 0), (4, 'projects_delete', 0),
(1, 'tasks', 1), (2, 'tasks', 1), (3, 'tasks', 1), (4, 'tasks', 1),
(1, 'tasks_list', 1), (2, 'tasks_list', 1), (3, 'tasks_list', 1), (4, 'tasks_list', 1),
(1, 'tasks_add', 1), (2, 'tasks_add', 1), (3, 'tasks_add', 1), (4, 'tasks_add', 0),
(1, 'tasks_edit', 1), (2, 'tasks_edit', 1), (3, 'tasks_edit', 1), (4, 'tasks_edit', 1),
(1, 'tasks_delete', 1), (2, 'tasks_delete', 1), (3, 'tasks_delete', 1), (4, 'tasks_delete', 0),
(1, 'requirements', 1), (2, 'requirements', 1), (3, 'requirements', 1), (4, 'requirements', 1),
(1, 'requirements_list', 1), (2, 'requirements_list', 1), (3, 'requirements_list', 1), (4, 'requirements_list', 1),
(1, 'requirements_add', 1), (2, 'requirements_add', 1), (3, 'requirements_add', 1), (4, 'requirements_add', 0),
(1, 'requirements_edit', 1), (2, 'requirements_edit', 1), (3, 'requirements_edit', 1), (4, 'requirements_edit', 1),
(1, 'requirements_delete', 1), (2, 'requirements_delete', 1), (3, 'requirements_delete', 1), (4, 'requirements_delete', 0),
(1, 'timesheets', 1), (2, 'timesheets', 1), (3, 'timesheets', 1), (4, 'timesheets', 1),
(1, 'timesheets_list', 1), (2, 'timesheets_list', 1), (3, 'timesheets_list', 1), (4, 'timesheets_list', 1),
(1, 'timesheets_add', 1), (2, 'timesheets_add', 1), (3, 'timesheets_add', 1), (4, 'timesheets_add', 1),
(1, 'timesheets_edit', 1), (2, 'timesheets_edit', 1), (3, 'timesheets_edit', 1), (4, 'timesheets_edit', 1),
(1, 'timesheets_delete', 1), (2, 'timesheets_delete', 1), (3, 'timesheets_delete', 1), (4, 'timesheets_delete', 0);

-- Attendance & Leave Modules
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'attendance', 1), (2, 'attendance', 1), (3, 'attendance', 1), (4, 'attendance', 1),
(1, 'attendance_list', 1), (2, 'attendance_list', 1), (3, 'attendance_list', 1), (4, 'attendance_list', 1),
(1, 'attendance_add', 1), (2, 'attendance_add', 1), (3, 'attendance_add', 1), (4, 'attendance_add', 1),
(1, 'attendance_edit', 1), (2, 'attendance_edit', 1), (3, 'attendance_edit', 1), (4, 'attendance_edit', 1),
(1, 'attendance_delete', 1), (2, 'attendance_delete', 1), (3, 'attendance_delete', 1), (4, 'attendance_delete', 0),
(1, 'attendance_bulk', 1), (2, 'attendance_bulk', 1), (3, 'attendance_bulk', 1), (4, 'attendance_bulk', 0),
(1, 'leave_requests', 1), (2, 'leave_requests', 1), (3, 'leave_requests', 1), (4, 'leave_requests', 1),
(1, 'leaves_list', 1), (2, 'leaves_list', 1), (3, 'leaves_list', 1), (4, 'leaves_list', 1),
(1, 'leaves_add', 1), (2, 'leaves_add', 1), (3, 'leaves_add', 1), (4, 'leaves_add', 1),
(1, 'leaves_edit', 1), (2, 'leaves_edit', 1), (3, 'leaves_edit', 1), (4, 'leaves_edit', 1),
(1, 'leaves_delete', 1), (2, 'leaves_delete', 1), (3, 'leaves_delete', 1), (4, 'leaves_delete', 0);

-- Communication Modules
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'chats', 1), (2, 'chats', 1), (3, 'chats', 1), (4, 'chats', 1),
(1, 'chats_list', 1), (2, 'chats_list', 1), (3, 'chats_list', 1), (4, 'chats_list', 1),
(1, 'chats_add', 1), (2, 'chats_add', 1), (3, 'chats_add', 1), (4, 'chats_add', 1),
(1, 'announcements', 1), (2, 'announcements', 1), (3, 'announcements', 1), (4, 'announcements', 1),
(1, 'announcements_list', 1), (2, 'announcements_list', 1), (3, 'announcements_list', 1), (4, 'announcements_list', 1),
(1, 'announcements_add', 1), (2, 'announcements_add', 1), (3, 'announcements_add', 1), (4, 'announcements_add', 0),
(1, 'announcements_edit', 1), (2, 'announcements_edit', 1), (3, 'announcements_edit', 1), (4, 'announcements_edit', 0),
(1, 'announcements_delete', 1), (2, 'announcements_delete', 1), (3, 'announcements_delete', 1), (4, 'announcements_delete', 0),
(1, 'calls', 1), (2, 'calls', 1), (3, 'calls', 1), (4, 'calls', 1);

-- Business Management Modules
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'clients', 1), (2, 'clients', 1), (3, 'clients', 1), (4, 'clients', 0),
(1, 'clients_list', 1), (2, 'clients_list', 1), (3, 'clients_list', 1), (4, 'clients_list', 0),
(1, 'clients_add', 1), (2, 'clients_add', 1), (3, 'clients_add', 1), (4, 'clients_add', 0),
(1, 'clients_edit', 1), (2, 'clients_edit', 1), (3, 'clients_edit', 1), (4, 'clients_edit', 0),
(1, 'clients_delete', 1), (2, 'clients_delete', 1), (3, 'clients_delete', 0), (4, 'clients_delete', 0),
(1, 'payroll', 1), (2, 'payroll', 1), (3, 'payroll', 1), (4, 'payroll', 0),
(1, 'assets_mgmt', 1), (2, 'assets_mgmt', 1), (3, 'assets_mgmt', 1), (4, 'assets_mgmt', 0),
(1, 'assets_list', 1), (2, 'assets_list', 1), (3, 'assets_list', 1), (4, 'assets_list', 0),
(1, 'assets_add', 1), (2, 'assets_add', 1), (3, 'assets_add', 1), (4, 'assets_add', 0),
(1, 'assets_edit', 1), (2, 'assets_edit', 1), (3, 'assets_edit', 1), (4, 'assets_edit', 0),
(1, 'assets_delete', 1), (2, 'assets_delete', 1), (3, 'assets_delete', 0), (4, 'assets_delete', 0);

-- Reports & Analytics Modules
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'reports', 1), (2, 'reports', 1), (3, 'reports', 1), (4, 'reports', 1),
(1, 'reports_overview', 1), (2, 'reports_overview', 1), (3, 'reports_overview', 1), (4, 'reports_overview', 1),
(1, 'reports_requirements', 1), (2, 'reports_requirements', 1), (3, 'reports_requirements', 1), (4, 'reports_requirements', 1),
(1, 'reports_tasks_assignment', 1), (2, 'reports_tasks_assignment', 1), (3, 'reports_tasks_assignment', 1), (4, 'reports_tasks_assignment', 1),
(1, 'reports_projects_status', 1), (2, 'reports_projects_status', 1), (3, 'reports_projects_status', 1), (4, 'reports_projects_status', 1),
(1, 'reports_leaves', 1), (2, 'reports_leaves', 1), (3, 'reports_leaves', 1), (4, 'reports_leaves', 1),
(1, 'reports_attendance', 1), (2, 'reports_attendance', 1), (3, 'reports_attendance', 1), (4, 'reports_attendance', 1),
(1, 'reports_attendance_employee', 1), (2, 'reports_attendance_employee', 1), (3, 'reports_attendance_employee', 1), (4, 'reports_attendance_employee', 1);

-- System Administration Modules
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'settings', 1), (2, 'settings', 1), (3, 'settings', 1), (4, 'settings', 0),
(1, 'db', 1), (2, 'db', 0), (3, 'db', 0), (4, 'db', 0),
(1, 'reminders', 1), (2, 'reminders', 1), (3, 'reminders', 1), (4, 'reminders', 0),
(1, 'reminders_list', 1), (2, 'reminders_list', 1), (3, 'reminders_list', 1), (4, 'reminders_list', 0),
(1, 'reminders_add', 1), (2, 'reminders_add', 1), (3, 'reminders_add', 1), (4, 'reminders_add', 0),
(1, 'reminders_edit', 1), (2, 'reminders_edit', 1), (3, 'reminders_edit', 1), (4, 'reminders_edit', 0),
(1, 'reminders_delete', 1), (2, 'reminders_delete', 1), (3, 'reminders_delete', 1), (4, 'reminders_delete', 0),
(1, 'activity', 1), (2, 'activity', 1), (3, 'activity', 1), (4, 'activity', 0),
(1, 'mail', 1), (2, 'mail', 1), (3, 'mail', 1), (4, 'mail', 0),
(1, 'sendgrid', 1), (2, 'sendgrid', 1), (3, 'sendgrid', 1), (4, 'sendgrid', 0),
(1, 'email_settings', 1), (2, 'email_settings', 1), (3, 'email_settings', 1), (4, 'email_settings', 0),
(1, 'whatsapp', 1), (2, 'whatsapp', 1), (3, 'whatsapp', 1), (4, 'whatsapp', 0),
(1, 'roles', 1), (2, 'roles', 0), (3, 'roles', 0), (4, 'roles', 0),
(1, 'statuses', 1), (2, 'statuses', 1), (3, 'statuses', 1), (4, 'statuses', 0),
(1, 'api_integrations', 1), (2, 'api_integrations', 1), (3, 'api_integrations', 1), (4, 'api_integrations', 0),
(1, 'admin', 1), (2, 'admin', 1), (3, 'admin', 1), (4, 'admin', 0),
(1, 'notifications', 1), (2, 'notifications', 1), (3, 'notifications', 1), (4, 'notifications', 1);
*/

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check all permissions for a specific module
-- SELECT * FROM permissions WHERE module = 'sendgrid' ORDER BY role_id;

-- Check all permissions for a specific role
-- SELECT * FROM permissions WHERE role_id = 1 ORDER BY module;

-- Count total permissions per role
-- SELECT role_id, COUNT(*) as total_permissions FROM permissions GROUP BY role_id;

-- Find modules without permissions
-- SELECT DISTINCT module FROM permissions WHERE can_access = 0;

-- =====================================================
-- END OF FILE
-- =====================================================
