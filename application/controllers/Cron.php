<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller {
    public function __construct() {
        parent::__construct();
        
        // Restrict cron to CLI or authorized token
        if (!$this->input->is_cli_request()) {
            $this->load->model('Setting_model', 'settings');
            $cron_token = $this->input->get('token');
            $expected_token = $this->settings->get_setting('cron_secret_token', '');
            if ($expected_token === '' || $expected_token === null)
            {
                $expected_token = getenv('CRON_TOKEN');
            }
            // Fail closed: no configured token means HTTP cron access is disabled.
            if ($expected_token === false || $expected_token === '' || $expected_token === null)
            {
                log_message('error', 'Cron: HTTP request rejected — cron_secret_token / CRON_TOKEN not configured.');
                show_error('Access denied. Cron endpoints require CLI access or a configured secret token.', 403);
            }
            if (empty($cron_token) || !hash_equals((string)$expected_token, (string)$cron_token)) {
                show_error('Access denied. Cron endpoints require CLI access or valid token.', 403);
            }
        }
        
        $this->load->database();
        $this->load->model('Announcement_model', 'ann');
        $this->load->model('Reminder_model', 'reminders');
    }

    // Process scheduled announcements
    public function process_announcements() {
        $this->load->model('Announcement_model', 'announcements');
        
        try {
            $this->announcements->process_scheduled();
            echo "✅ Scheduled announcements processed successfully at " . date('Y-m-d H:i:s') . "\n";
        } catch (Exception $e) {
            echo "❌ Error processing scheduled announcements: " . $e->getMessage() . "\n";
        }
    }

    // Send queued emails — DISABLED: OMS reminders now sync to Google Calendar on create.
    // Can be called via: http://localhost/Office_management_system/cron/send_emails
    public function send_emails() {
        echo "📧 Reminder SMTP send-queue is disabled.\n";
        echo "   Reminders are delivered via Google Calendar when created/saved.\n";
        echo "   Connect Google: Settings → Google Calendar Reminders\n";
        echo "   Skipped at " . date('Y-m-d H:i:s') . "\n";
    }

    // Process all scheduled tasks
    // Can be called via: http://localhost/Office_management_system/cron/run_all
    public function run_all() {
        echo "🚀 Starting cron job execution at " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 50) . "\n";

        $this->load->helper('schema_automation');
        $schema_modules = oms_ensure_all_schemas();
        echo "📦 Schema ensured: " . (count($schema_modules) ? implode(', ', $schema_modules) : 'none') . "\n";
        echo str_repeat("-", 50) . "\n";
        
        // Process scheduled announcements
        $this->process_announcements();
        echo str_repeat("-", 50) . "\n";
        
        // Send queued emails
        $this->send_emails();
        echo str_repeat("-", 50) . "\n";

        // Coaching session email reminders (24h / 1h)
        $this->coaching_session_reminders();
        echo str_repeat("-", 50) . "\n";

        // Coaching + other dynamic automation rules
        $this->coaching_automation();
        echo str_repeat("-", 50) . "\n";

        // SPL attendance penalties (yesterday missed check-in/out)
        $this->reward_attendance_penalties();
        echo str_repeat("-", 50) . "\n";

        echo str_repeat("=", 50) . "\n";
        
        echo "✅ All cron jobs completed at " . date('Y-m-d H:i:s') . "\n";
    }

    // Generate cron job commands for setup
    // Can be called via: http://localhost/Office_management_system/cron/setup_help
    public function setup_help() {
        echo "📋 Cron Job Setup Instructions\n";
        echo str_repeat("=", 50) . "\n\n";
        
        echo "Add these cron jobs to your system:\n\n";
        
        echo "# Process scheduled announcements every 5 minutes\n";
        echo "*/5 * * * * curl -s " . site_url('cron/process_announcements') . " >/dev/null 2>&1\n\n";
        
        echo "# Send queued emails every 2 minutes\n";
        echo "*/2 * * * * curl -s " . site_url('cron/send_emails') . " >/dev/null 2>&1\n\n";

        echo "# Coaching session reminders every 15 minutes\n";
        echo "*/15 * * * * curl -s " . site_url('cron/coaching_session_reminders') . " >/dev/null 2>&1\n\n";

        echo "# Coaching automation rules every 30 minutes\n";
        echo "*/30 * * * * curl -s " . site_url('cron/coaching_automation') . " >/dev/null 2>&1\n\n";

        echo "# SPL attendance penalties daily at 1:00 AM\n";
        echo "0 1 * * * curl -s " . site_url('cron/reward_attendance_penalties') . " >/dev/null 2>&1\n\n";

        echo "# Daily reminders + attendance Google alerts at 6:00 AM (token required)\n";
        echo "0 6 * * * curl -s \"" . site_url('reminders/cron_attendance_alerts') . "?token=YOUR_CRON_TOKEN\" >/dev/null 2>&1\n\n";

        echo "# SPL monthly consistency review on 1st of month at 2:00 AM\n";
        echo "0 2 1 * * curl -s " . site_url('cron/reward_consistency_monthly') . " >/dev/null 2>&1\n\n";
        
        echo "# Or run all tasks every 5 minutes\n";
        echo "*/5 * * * * curl -s " . site_url('cron/run_all') . " >/dev/null 2>&1\n\n";
        
        echo "Alternative using wget:\n";
        echo "*/5 * * * * wget -q -O - " . site_url('cron/run_all') . " >/dev/null 2>&1\n\n";
        
        echo "Alternative using PHP CLI:\n";
        echo "*/5 * * * * php " . APPPATH . "controllers/cron.php run_all\n\n";
        
        echo "🔍 Test URLs:\n";
        echo "Process Announcements: " . site_url('cron/process_announcements') . "\n";
        echo "Send Emails: " . site_url('cron/send_emails') . "\n";
        echo "Run All: " . site_url('cron/run_all') . "\n";
        echo "Coaching Session Reminders: " . site_url('cron/coaching_session_reminders') . "\n";
        echo "Coaching Automation: " . site_url('cron/coaching_automation') . "\n";
        echo "SPL Attendance Penalties: " . site_url('cron/reward_attendance_penalties') . "\n";
        echo "SPL Consistency Monthly: " . site_url('cron/reward_consistency_monthly') . "\n";
        echo "Attendance Google Alerts: " . site_url('reminders/cron_attendance_alerts') . " (add ?token=)\n";
    }

    /**
     * Penalize yesterday missed check-ins / checkouts for all active users.
     */
    public function reward_attendance_penalties()
    {
        if (!$this->db->table_exists('reward_rules')) {
            echo "⏭ SPL rewards not installed — skipping attendance penalties.\n";
            return;
        }
        $this->load->helper(array('rewards_automation', 'rewards_schema'));
        rewards_schema_ensure($this->db);
        try {
            $stats = rewards_automation_daily_attendance_penalties($this->db);
            if (!empty($stats['skipped'])) {
                echo "⏭ Attendance penalties skipped (non-workday) at " . date('Y-m-d H:i:s') . "\n";
                return;
            }
            echo "✅ Attendance penalties for " . $stats['date'] . ": missed check-in " . (int) $stats['missed_checkin'] . ", missed checkout " . (int) $stats['missed_checkout'] . " at " . date('Y-m-d H:i:s') . "\n";
        } catch (Exception $e) {
            echo "❌ Error running attendance penalties: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Award monthly consistency bonuses for the previous calendar month.
     */
    public function reward_consistency_monthly()
    {
        if (!$this->db->table_exists('reward_rules')) {
            echo "⏭ SPL rewards not installed — skipping consistency review.\n";
            return;
        }
        $this->load->helper(array('rewards_automation', 'rewards_schema'));
        rewards_schema_ensure($this->db);
        try {
            $stats = rewards_automation_consistency_monthly($this->db);
            echo "✅ Consistency review for " . $stats['month'] . ": self-updates " . (int) $stats['self_updates'] . ", on-time " . (int) $stats['on_time'] . ", no missed checkout " . (int) $stats['no_missed_checkout'] . " at " . date('Y-m-d H:i:s') . "\n";
        } catch (Exception $e) {
            echo "❌ Error running consistency review: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Run coaching automation rules (stale goals, etc.).
     */
    public function coaching_automation()
    {
        if (!$this->db->table_exists('coaching_automation_rules')) {
            echo "⏭ Coaching automation not installed — skipping.\n";
            return;
        }
        $this->load->helper('schema_automation');
        oms_ensure_all_schemas();
        $this->load->model('Coaching_model', 'coaching');
        try {
            $count = $this->coaching->run_automation_cron();
            echo "✅ Coaching automation actions: " . (int) $count . " at " . date('Y-m-d H:i:s') . "\n";
        } catch (Exception $e) {
            echo "❌ Error running coaching automation: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Send coaching session reminder emails (24h and 1h before).
     */
    public function coaching_session_reminders()
    {
        if (!$this->db->table_exists('coaching_sessions')) {
            echo "⏭ Coaching tables not installed — skipping session reminders.\n";
            return;
        }
        $this->load->model('Coaching_model', 'coaching');
        try {
            $sent = $this->coaching->process_session_reminder_cron();
            echo "✅ Coaching session reminders sent: " . (int) $sent . " at " . date('Y-m-d H:i:s') . "\n";
        } catch (Exception $e) {
            echo "❌ Error sending coaching session reminders: " . $e->getMessage() . "\n";
        }
    }

    // Test email functionality
    // Can be called via: http://localhost/Office_management_system/cron/test_email
    public function test_email() {
        // Load email helper and configure from settings
        $this->load->helper('email');
        configure_email_from_settings();

        $this->email->from(get_system_from_email(), get_company_name());
        $this->email->to('test@example.com'); // Change to your test email
        $this->email->subject('🧪 Test Email from Announcement System');
        $this->email->message('
            <h2>Test Email</h2>
            <p>This is a test email from the ' . get_company_name() . ' announcement scheduler.</p>
            <p>If you receive this, the email system is working correctly.</p>
            <p><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</p>
        ');

        if ($this->email->send()) {
            echo "✅ Test email sent successfully!\n";
        } else {
            echo "❌ Test email failed: " . $this->email->print_debugger() . "\n";
        }
    }

    // Show system status
    // Can be called via: http://localhost/Office_management_system/cron/status
    public function status() {
        echo "📊 System Status at " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 50) . "\n\n";
        
        // Check database connection
        if ($this->db->conn_id) {
            echo "✅ Database: Connected\n";
        } else {
            echo "❌ Database: Not connected\n";
        }
        
        // Check required tables
        $tables = ['announcements', 'reminders', 'reminder_schedules', 'reminder_templates'];
        foreach ($tables as $table) {
            if ($this->db->table_exists($table)) {
                echo "✅ Table '$table': Exists\n";
            } else {
                echo "❌ Table '$table': Missing\n";
            }
        }
        
        echo "\n📈 Queue Status:\n";
        $queued = $this->db->where('status', 'queued')->count_all_results('reminders');
        $sent = $this->db->where('status', 'sent')->count_all_results('reminders');
        $error = $this->db->where('status', 'error')->count_all_results('reminders');
        
        echo "Queued emails: $queued\n";
        echo "Sent emails: $sent\n";
        echo "Failed emails: $error\n";
        
        echo "\n📢 Announcement Status:\n";
        $draft = $this->db->where('status', 'draft')->count_all_results('announcements');
        $published = $this->db->where('status', 'published')->count_all_results('announcements');
        $scheduled = $this->db->where('status', 'scheduled')->count_all_results('announcements');
        $expired = $this->db->where('status', 'expired')->count_all_results('announcements');
        
        echo "Draft: $draft\n";
        echo "Published: $published\n";
        echo "Scheduled: $scheduled\n";
        echo "Expired: $expired\n";
        
        echo "\n🔄 Recurring Announcements: " . $this->db->where('is_recurring', 1)->count_all_results('announcements') . "\n";
    }

    /** Lock meal orders after breakfast/lunch cut-off (run hourly). */
    public function meals_auto_lock()
    {
        $this->load->helper(array('meal_schema', 'meal'));
        $this->load->model('Meal_model', 'meals');
        meal_schema_ensure($this->db);
        $today = date('Y-m-d');
        $this->meals->apply_auto_locks_for_date($today);
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $this->meals->apply_auto_locks_for_date($tomorrow);
        echo "Meal order locks applied for {$today} and {$tomorrow} at " . date('Y-m-d H:i:s') . "\n";
    }
}
