-- =====================================================
-- LEAVE TYPES INSERT QUERIES
-- Office Management System
-- =====================================================

-- Table Structure:
-- CREATE TABLE IF NOT EXISTS leave_types (
--   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--   name VARCHAR(50) NOT NULL UNIQUE,
--   description VARCHAR(255) NULL,
--   annual_quota DECIMAL(5,2) NOT NULL DEFAULT 0,
--   is_paid TINYINT(1) NOT NULL DEFAULT 1,
--   created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
--   updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- BASIC INSERT QUERY FORMAT
-- =====================================================

-- Single Insert Query
INSERT INTO leave_types (name, description, annual_quota, is_paid) 
VALUES ('Leave Type Name', 'Description of leave type', 12.00, 1);

-- =====================================================
-- INSERT WITH IGNORE (Prevents duplicate errors)
-- =====================================================

INSERT IGNORE INTO leave_types (name, description, annual_quota, is_paid) 
VALUES ('CL', 'Casual Leave', 6.00, 1);

-- =====================================================
-- MULTIPLE INSERT QUERIES (Batch Insert)
-- =====================================================

INSERT IGNORE INTO leave_types (name, description, annual_quota, is_paid) VALUES
('CL', 'Casual Leave', 6.00, 1),
('SL', 'Sick Leave', 6.00, 1),
('PL', 'Privilege Leave', 12.00, 1),
('EL', 'Earned Leave', 12.00, 1),
('ML', 'Maternity Leave', 90.00, 1),
('PL', 'Paternity Leave', 7.00, 1),
('LWP', 'Leave Without Pay', 0.00, 0),
('WFH', 'Work From Home', 0.00, 1);

-- =====================================================
-- COMMON LEAVE TYPES WITH EXAMPLES
-- =====================================================

-- Casual Leave (CL)
INSERT IGNORE INTO leave_types (name, description, annual_quota, is_paid) 
VALUES ('CL', 'Casual Leave - For personal work', 6.00, 1);

-- Sick Leave (SL)
INSERT IGNORE INTO leave_types (name, description, annual_quota, is_paid) 
VALUES ('SL', 'Sick Leave - For medical reasons', 6.00, 1);

-- Privilege Leave / Earned Leave (PL/EL)
INSERT IGNORE INTO leave_types (name, description, annual_quota, is_paid) 
VALUES ('PL', 'Privilege Leave - Annual vacation leave', 12.00, 1);

-- Maternity Leave (ML)
INSERT IGNORE INTO leave_types (name, description, annual_quota, is_paid) 
VALUES ('ML', 'Maternity Leave - For expecting mothers', 90.00, 1);

-- Paternity Leave
INSERT IGNORE INTO leave_types (name, description, annual_quota, is_paid) 
VALUES ('Paternity Leave', 'Paternity Leave - For new fathers', 7.00, 1);

-- Leave Without Pay (LWP)
INSERT IGNORE INTO leave_types (name, description, annual_quota, is_paid) 
VALUES ('LWP', 'Leave Without Pay - Unpaid leave', 0.00, 0);

-- Work From Home (WFH)
INSERT IGNORE INTO leave_types (name, description, annual_quota, is_paid) 
VALUES ('Work From Home', 'Work From Home - Remote work request', 0.00, 1);

-- Compensatory Off (Comp Off)
INSERT IGNORE INTO leave_types (name, description, annual_quota, is_paid) 
VALUES ('Comp Off', 'Compensatory Off - For working on holidays', 0.00, 1);

-- Half Day Leave
INSERT IGNORE INTO leave_types (name, description, annual_quota, is_paid) 
VALUES ('Half Day', 'Half Day Leave - For half day absence', 0.00, 1);

-- Emergency Leave
INSERT IGNORE INTO leave_types (name, description, annual_quota, is_paid) 
VALUES ('Emergency Leave', 'Emergency Leave - For urgent situations', 3.00, 1);

-- =====================================================
-- INSERT WITH DEFAULT VALUES
-- =====================================================

-- Using DEFAULT for is_paid (will be 1)
INSERT INTO leave_types (name, description, annual_quota) 
VALUES ('Test Leave', 'Test Leave Type', 5.00);

-- Using DEFAULT for annual_quota (will be 0)
INSERT INTO leave_types (name, description, is_paid) 
VALUES ('Unlimited Leave', 'Leave with no quota limit', 1);

-- Using DEFAULT for both
INSERT INTO leave_types (name, description) 
VALUES ('Special Leave', 'Special leave type');

-- =====================================================
-- INSERT WITH NULL DESCRIPTION
-- =====================================================

INSERT INTO leave_types (name, annual_quota, is_paid) 
VALUES ('Simple Leave', 10.00, 1);

-- =====================================================
-- PHP CODEIGNITER EXAMPLE
-- =====================================================

/*
// Using CodeIgniter Query Builder
$data = array(
    'name' => 'Casual Leave',
    'description' => 'For personal work',
    'annual_quota' => 6.00,
    'is_paid' => 1
);
$this->db->insert('leave_types', $data);

// Or using Model
$this->load->model('Leave_type_model');
$data = array(
    'name' => 'Sick Leave',
    'description' => 'For medical reasons',
    'annual_quota' => 6.00,
    'is_paid' => 1
);
$this->Leave_type_model->create($data);
*/

-- =====================================================
-- UPDATE EXISTING LEAVE TYPE
-- =====================================================

-- Update leave type
UPDATE leave_types 
SET description = 'Updated description', 
    annual_quota = 8.00 
WHERE name = 'CL';

-- =====================================================
-- DELETE LEAVE TYPE (Use with caution)
-- =====================================================

-- Delete specific leave type
-- DELETE FROM leave_types WHERE name = 'Test Leave';

-- =====================================================
-- SELECT QUERIES FOR REFERENCE
-- =====================================================

-- Get all leave types
SELECT * FROM leave_types ORDER BY name ASC;

-- Get specific leave type
SELECT * FROM leave_types WHERE name = 'CL';

-- Get paid leave types only
SELECT * FROM leave_types WHERE is_paid = 1;

-- Get unpaid leave types only
SELECT * FROM leave_types WHERE is_paid = 0;

-- Get leave types with quota > 0
SELECT * FROM leave_types WHERE annual_quota > 0;

-- =====================================================
-- NOTES
-- =====================================================
-- 1. 'name' field is UNIQUE - cannot have duplicate names
-- 2. 'annual_quota' is DECIMAL(5,2) - max 999.99 days
-- 3. 'is_paid' is TINYINT(1) - 1 for paid, 0 for unpaid
-- 4. Use INSERT IGNORE to prevent errors on duplicate names
-- 5. created_at and updated_at are automatically managed
-- 6. id is AUTO_INCREMENT - don't need to specify
