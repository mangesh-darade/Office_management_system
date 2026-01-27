-- =====================================================
-- Add Expense Management Permissions
-- Office Management System
-- Generated: 2026-01-26
-- =====================================================

-- Add expense management permissions
-- Admin, Manager, Lead can manage expenses
-- Staff can only submit their own expenses

INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'expenses', 1),
(2, 'expenses', 1),
(3, 'expenses', 1),
(4, 'expenses', 1);
-- Granular permissions for expense operations
INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'expenses_approve', 1),
(2, 'expenses_approve', 1),
(3, 'expenses_approve', 0),
(4, 'expenses_approve', 0);

INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'expenses_reimburse', 1),
(2, 'expenses_reimburse', 1),
(3, 'expenses_reimburse', 0),
(4, 'expenses_reimburse', 0);

INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'expenses_reports', 1),
(2, 'expenses_reports', 1),
(3, 'expenses_reports', 1),
(4, 'expenses_reports', 0);

INSERT INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'expenses_categories', 1),
(2, 'expenses_categories', 0),
(3, 'expenses_categories', 0),
(4, 'expenses_categories', 0);

-- Verify the permissions were added
SELECT * FROM permissions WHERE module LIKE 'expenses%' ORDER BY module, role_id;
