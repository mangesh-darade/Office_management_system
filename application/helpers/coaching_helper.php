<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!defined('ROLE_COACHING_CLIENT')) {
    define('ROLE_COACHING_CLIENT', 5);
}

if (!function_exists('coaching_ensure_schema')) {
    /**
     * Create all coaching module tables (idempotent).
     */
    function coaching_ensure_schema()
    {
        $CI =& get_instance();
        if (!isset($CI->db)) {
            $CI->load->database();
        }
        $db = $CI->db;
        $charset = 'DEFAULT CHARSET=utf8mb4';

        $tables = [
            "CREATE TABLE IF NOT EXISTS coaching_coaches (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(120) NULL,
                bio TEXT NULL,
                hourly_rate DECIMAL(12,2) DEFAULT 0,
                commission_pct DECIMAL(5,2) DEFAULT 0,
                status ENUM('active','inactive') DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_coach_user (user_id)
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_clients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                crm_client_id INT NULL COMMENT 'optional link to clients table',
                full_name VARCHAR(200) NOT NULL,
                email VARCHAR(255) NULL,
                phone VARCHAR(20) NULL,
                company VARCHAR(255) NULL,
                primary_coach_id INT NULL,
                status ENUM('active','inactive','prospect') DEFAULT 'active',
                portal_enabled TINYINT(1) DEFAULT 0,
                notes TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_coach (primary_coach_id),
                KEY idx_user (user_id)
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_client_coaches (
                id INT AUTO_INCREMENT PRIMARY KEY,
                coaching_client_id INT NOT NULL,
                coach_id INT NOT NULL,
                is_primary TINYINT(1) DEFAULT 0,
                assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_client_coach (coaching_client_id, coach_id)
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                coaching_client_id INT NOT NULL,
                coach_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                scheduled_at DATETIME NOT NULL,
                duration_minutes INT DEFAULT 60,
                location VARCHAR(255) NULL,
                meeting_link VARCHAR(500) NULL,
                status ENUM('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
                notes_internal TEXT NULL,
                notes_client TEXT NULL,
                homework_summary TEXT NULL,
                created_by INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_scheduled (scheduled_at),
                KEY idx_client (coaching_client_id),
                KEY idx_coach (coach_id)
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_goals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                coaching_client_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                target_date DATE NULL,
                progress_pct TINYINT UNSIGNED DEFAULT 0,
                status ENUM('active','completed','paused','cancelled') DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_client (coaching_client_id)
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_homework (
                id INT AUTO_INCREMENT PRIMARY KEY,
                coaching_client_id INT NOT NULL,
                session_id INT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                due_date DATE NULL,
                status ENUM('pending','done') DEFAULT 'pending',
                client_notes TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_client (coaching_client_id)
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_leads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(200) NOT NULL,
                email VARCHAR(255) NULL,
                phone VARCHAR(20) NULL,
                source VARCHAR(100) NULL,
                workshop_id INT NULL,
                status ENUM('new','contacted','qualified','converted','lost') DEFAULT 'new',
                notes TEXT NULL,
                assigned_coach_id INT NULL,
                converted_client_id INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_status (status)
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_workshops (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                workshop_date DATETIME NULL,
                location VARCHAR(255) NULL,
                online_link VARCHAR(500) NULL,
                capacity INT DEFAULT 0,
                status ENUM('draft','published','completed','cancelled') DEFAULT 'draft',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_workshop_registrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                workshop_id INT NOT NULL,
                lead_id INT NULL,
                full_name VARCHAR(200) NOT NULL,
                email VARCHAR(255) NULL,
                phone VARCHAR(20) NULL,
                registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY idx_workshop (workshop_id)
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_programs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
                installment_count INT DEFAULT 1,
                status ENUM('active','inactive') DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_invoices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                coaching_client_id INT NOT NULL,
                program_id INT NULL,
                invoice_no VARCHAR(50) NOT NULL,
                total_amount DECIMAL(12,2) NOT NULL,
                paid_amount DECIMAL(12,2) DEFAULT 0,
                status ENUM('draft','sent','partial','paid','cancelled') DEFAULT 'draft',
                notes TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_invoice_no (invoice_no),
                KEY idx_client (coaching_client_id)
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_installments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_id INT NOT NULL,
                installment_no INT NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                due_date DATE NOT NULL,
                paid_at DATETIME NULL,
                payment_gateway VARCHAR(50) NULL,
                payment_ref VARCHAR(255) NULL,
                status ENUM('pending','paid','overdue','cancelled') DEFAULT 'pending',
                KEY idx_invoice (invoice_id)
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_coach_payouts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                coach_id INT NOT NULL,
                session_id INT NULL,
                amount DECIMAL(12,2) NOT NULL,
                payout_date DATE NULL,
                status ENUM('pending','paid','cancelled') DEFAULT 'pending',
                notes TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY idx_coach (coach_id)
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_client_resources (
                id INT AUTO_INCREMENT PRIMARY KEY,
                coaching_client_id INT NULL COMMENT 'NULL = shared library',
                title VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NULL,
                external_url VARCHAR(500) NULL,
                resource_type ENUM('document','video','link','worksheet','sop') DEFAULT 'document',
                visible_to_client TINYINT(1) DEFAULT 1,
                created_by INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY idx_client (coaching_client_id)
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_whatsapp_enquiries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                phone VARCHAR(20) NOT NULL,
                contact_name VARCHAR(200) NULL,
                message TEXT NULL,
                status ENUM('open','replied','closed') DEFAULT 'open',
                assigned_user_id INT NULL,
                lead_id INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_status (status)
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_whatsapp_broadcasts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                audience_filter TEXT NULL COMMENT 'JSON',
                message TEXT NOT NULL,
                sent_count INT DEFAULT 0,
                sent_at DATETIME NULL,
                created_by INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_automation_rules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                trigger_type VARCHAR(80) NOT NULL,
                trigger_config TEXT NULL,
                action_type VARCHAR(80) NOT NULL,
                action_config TEXT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_payment_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                gateway ENUM('razorpay','payu','phonepe','manual') DEFAULT 'manual',
                key_id VARCHAR(255) NULL,
                key_secret VARCHAR(500) NULL,
                webhook_secret VARCHAR(255) NULL,
                is_active TINYINT(1) DEFAULT 0,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_branding (
                id INT AUTO_INCREMENT PRIMARY KEY,
                portal_title VARCHAR(255) DEFAULT 'Client Portal',
                portal_logo VARCHAR(500) NULL,
                primary_color VARCHAR(20) DEFAULT '#0d6efd',
                welcome_message TEXT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB {$charset}",

            "CREATE TABLE IF NOT EXISTS coaching_payment_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                installment_id INT NOT NULL,
                razorpay_order_id VARCHAR(100) NOT NULL,
                amount_paise INT NOT NULL,
                status ENUM('created','paid','failed') DEFAULT 'created',
                razorpay_payment_id VARCHAR(100) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_rzp_order (razorpay_order_id),
                KEY idx_installment (installment_id)
            ) ENGINE=InnoDB {$charset}",
        ];

        foreach ($tables as $sql) {
            $db->query($sql);
        }

        if ($db->table_exists('coaching_sessions')) {
            foreach (['reminder_24h_sent' => 'TINYINT(1) DEFAULT 0', 'reminder_1h_sent' => 'TINYINT(1) DEFAULT 0', 'confirmation_sent' => 'TINYINT(1) DEFAULT 0'] as $col => $def) {
                if (!$db->field_exists($col, 'coaching_sessions')) {
                    $db->query("ALTER TABLE coaching_sessions ADD `{$col}` {$def}");
                }
            }
        }
        if ($db->table_exists('coaching_whatsapp_enquiries') && !$db->field_exists('lead_id', 'coaching_whatsapp_enquiries')) {
            $db->query('ALTER TABLE coaching_whatsapp_enquiries ADD lead_id INT NULL AFTER assigned_user_id');
        }
        if ($db->table_exists('coaching_clients') && !$db->field_exists('crm_client_id', 'coaching_clients')) {
            $db->query('ALTER TABLE coaching_clients ADD crm_client_id INT NULL COMMENT \'optional link to clients table\' AFTER user_id');
        }

        if ($db->get_where('coaching_payment_settings', ['id' => 1])->num_rows() === 0) {
            $db->insert('coaching_payment_settings', ['gateway' => 'manual', 'is_active' => 1]);
        }
        if ($db->get_where('coaching_branding', ['id' => 1])->num_rows() === 0) {
            $db->insert('coaching_branding', ['portal_title' => 'Coaching Client Portal']);
        }

        if (function_exists('seed_coaching_defaults_if_needed')) {
            seed_coaching_defaults_if_needed();
        }
    }
}

if (!function_exists('coaching_crm_available')) {
    function coaching_crm_available()
    {
        $CI =& get_instance();
        return $CI->db->table_exists('clients');
    }
}

if (!function_exists('coaching_crm_clients_for_select')) {
    /**
     * Active CRM clients for coaching client form (reuses Client_model).
     *
     * @return array
     */
    function coaching_crm_clients_for_select()
    {
        if (!coaching_crm_available()) {
            return array();
        }
        $CI =& get_instance();
        $CI->load->model('Client_model', 'client_model');
        $rows = $CI->client_model->get_clients();
        $out = array();
        foreach ($rows as $r) {
            $label = trim((string) $r->company_name);
            if ($label === '') {
                $label = trim((string) $r->contact_person);
            }
            if (!empty($r->client_code)) {
                $label .= ' (' . $r->client_code . ')';
            }
            if ($label === '') {
                $label = 'Client #' . (int) $r->id;
            }
            $out[] = (object) array('id' => (int) $r->id, 'label' => $label);
        }
        return $out;
    }
}

if (!function_exists('coaching_require_access')) {
    function coaching_require_access($module = 'coaching')
    {
        $CI =& get_instance();
        $role_id = (int) $CI->session->userdata('role_id');
        if ($role_id === 1) {
            return;
        }
        if ($role_id === ROLE_COACHING_CLIENT) {
            show_error('Use the client portal.', 403);
            exit;
        }
        require_module_access($module, true);
    }
}

if (!function_exists('coaching_portal_user_id')) {
    function coaching_portal_user_id()
    {
        $CI =& get_instance();
        return (int) $CI->session->userdata('user_id');
    }
}

if (!function_exists('coaching_login_redirect')) {
    function coaching_login_redirect($role_id)
    {
        if ((int) $role_id === ROLE_COACHING_CLIENT) {
            return 'coaching-portal';
        }
        return 'dashboard';
    }
}

if (!function_exists('coaching_any_access')) {
    function coaching_any_access()
    {
        if ((int) get_instance()->session->userdata('role_id') === 1) {
            return true;
        }
        $keys = [
            'coaching', 'coaching_coaches', 'coaching_clients', 'coaching_sessions',
            'coaching_goals', 'coaching_leads', 'coaching_billing', 'coaching_reports',
            'coaching_whatsapp_crm', 'coaching_resources', 'coaching_admin',
        ];
        foreach ($keys as $k) {
            if (has_module_access($k)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('coaching_razorpay')) {
    /** @return Razorpay_lib */
    function coaching_razorpay()
    {
        $CI =& get_instance();
        $CI->load->model('Coaching_model', 'coaching');
        $s = $CI->coaching->payment_settings();
        $CI->load->library('Razorpay_lib', [
            'key_id' => $s ? $s->key_id : '',
            'key_secret' => $s ? $s->key_secret : '',
            'webhook_secret' => $s ? $s->webhook_secret : '',
        ]);
        return $CI->razorpay_lib;
    }
}
