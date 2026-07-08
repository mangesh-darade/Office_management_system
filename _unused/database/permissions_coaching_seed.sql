-- Optional: idempotent seed for Coaching module permissions.
-- Safe to run multiple times (INSERT IGNORE).
-- Role 1 = Admin, 2 = Manager, 3 = Lead, 5 = Coaching Client (portal only)

INSERT IGNORE INTO `permissions` (`role_id`, `module`, `can_access`) VALUES
(1, 'coaching', 1),
(1, 'coaching_coaches', 1),
(1, 'coaching_clients', 1),
(1, 'coaching_sessions', 1),
(1, 'coaching_goals', 1),
(1, 'coaching_leads', 1),
(1, 'coaching_billing', 1),
(1, 'coaching_reports', 1),
(1, 'coaching_whatsapp_crm', 1),
(1, 'coaching_resources', 1),
(1, 'coaching_admin', 1),
(1, 'coaching_portal', 1),
(2, 'coaching', 1),
(2, 'coaching_coaches', 1),
(2, 'coaching_clients', 1),
(2, 'coaching_sessions', 1),
(2, 'coaching_goals', 1),
(2, 'coaching_leads', 1),
(2, 'coaching_billing', 1),
(2, 'coaching_reports', 1),
(2, 'coaching_whatsapp_crm', 1),
(2, 'coaching_resources', 1),
(2, 'coaching_admin', 1),
(2, 'coaching_portal', 1),
(3, 'coaching', 1),
(3, 'coaching_clients', 1),
(3, 'coaching_sessions', 1),
(3, 'coaching_goals', 1),
(3, 'coaching_leads', 1),
(3, 'coaching_reports', 1),
(3, 'coaching_resources', 1),
(5, 'coaching_portal', 1);

-- Ensure Coaching Client role exists (adjust columns if your roles table differs)
INSERT IGNORE INTO `roles` (`id`, `name`, `group_type`, `is_active`, `sort_order`) VALUES
(5, 'Coaching Client', 'user', 1, 5);
