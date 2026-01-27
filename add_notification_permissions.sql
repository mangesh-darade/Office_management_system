-- =====================================================
-- Add Notifications Module to Permissions
-- Office Management System
-- Generated: 2026-01-26
-- =====================================================

-- Add notifications module permission for all roles
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'notifications', 1),
(2, 'notifications', 1),
(3, 'notifications', 1),
(4, 'notifications', 1);

-- Verify the permissions were added
SELECT * FROM permissions WHERE module = 'notifications' ORDER BY role_id;
