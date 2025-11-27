<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('Announcement_model', 'ann');
        $this->load->model('Reminder_model', 'reminders');
        // Disable session for cron jobs
        $this->load->driver('session');
        $this->session->sess_expiration = 0;
    }

    // Process scheduled announcements
    // Can be called via: http://localhost/Office_management_system/cron/process_announcements
    public function process_announcements() {
        $this->load->model('Announcements', 'announcements');
        
        try {
            $this->announcements->process_scheduled();
            echo "✅ Scheduled announcements processed successfully at " . date('Y-m-d H:i:s') . "\n";
        } catch (Exception $e) {
            echo "❌ Error processing scheduled announcements: " . $e->getMessage() . "\n";
        }
    }

    // Send queued emails
    // Can be called via: http://localhost/Office_management_system/cron/send_emails
    public function send_emails() {
        try {
            $queue = $this->reminders->fetch_queue(50);
            $sent = 0;
            $failed = 0;

            foreach ($queue as $reminder) {
                // Load email library
                $this->load->library('email');
                
                // Configure email
                $config['mailtype'] = 'html';
                $config['charset'] = 'utf-8';
                $config['wordwrap'] = TRUE;
                $this->email->initialize($config);

                // Set email parameters
                $from_email = $reminder->from_email ?: 'noreply@officemanagement.com';
                $from_name = $reminder->from_name ?: get_company_name();
                
                $this->email->from($from_email, $from_name);
                $this->email->to($reminder->email);
                $this->email->subject($reminder->subject);
                $this->email->message($reminder->body);

                // Send email
                if ($this->email->send()) {
                    $this->reminders->mark_sent($reminder->id);
                    $sent++;
                } else {
                    $this->reminders->mark_error($reminder->id);
                    $failed++;
                }
            }

            echo "📧 Email queue processed: {$sent} sent, {$failed} failed at " . date('Y-m-d H:i:s') . "\n";
            
        } catch (Exception $e) {
            echo "❌ Error sending emails: " . $e->getMessage() . "\n";
        }
    }

    // Process all scheduled tasks
    // Can be called via: http://localhost/Office_management_system/cron/run_all
    public function run_all() {
        echo "🚀 Starting cron job execution at " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 50) . "\n";
        
        // Process scheduled announcements
        $this->process_announcements();
        echo str_repeat("-", 50) . "\n";
        
        // Send queued emails
        $this->send_emails();
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
    }

    // Test email functionality
    // Can be called via: http://localhost/Office_management_system/cron/test_email
    public function test_email() {
        $this->load->library('email');
        
        $config['mailtype'] = 'html';
        $config['charset'] = 'utf-8';
        $config['wordwrap'] = TRUE;
        $this->email->initialize($config);

        $this->email->from('noreply@officemanagement.com', get_company_name());
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
}
