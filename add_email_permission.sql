-- Add email_settings permission for all roles
INSERT INTO permissions (module, role_id, can_access) VALUES 
('email_settings', 1, 1),
('email_settings', 2, 1),
('email_settings', 3, 1),
('email_settings', 4, 1)
ON DUPLICATE KEY UPDATE (module, role_id);
