@echo off
echo Adding email_settings permission to all roles...

mysql -u root -p'' internal_portel internal_portel -e "
INSERT INTO permissions (module, role_id, can_access) VALUES 
('email_settings', 1, 1),
('email_settings', 2, 1),
('email_settings', 3, 1),
('email_settings', 4, 1)
ON DUPLICATE KEY UPDATE (module, role_id);"
