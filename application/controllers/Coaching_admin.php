<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching_admin extends Coaching_Controller {

    protected $coaching_permission = 'coaching_admin';

    public function index()
    {
        if ($this->input->method() === 'post') {
            $action = $this->input->post('action');
            if ($action === 'branding') {
                $this->coaching->branding_save([
                    'portal_title' => trim((string) $this->input->post('portal_title')),
                    'primary_color' => trim((string) $this->input->post('primary_color')),
                    'welcome_message' => trim((string) $this->input->post('welcome_message')),
                ]);
            } elseif ($action === 'payments') {
                $cur = $this->coaching->payment_settings();
                $key_secret = trim((string) $this->input->post('key_secret'));
                $webhook_secret = trim((string) $this->input->post('webhook_secret'));
                $this->coaching->payment_settings_save([
                    'gateway' => $this->input->post('gateway'),
                    'key_id' => trim((string) $this->input->post('key_id')),
                    'key_secret' => $key_secret !== '' ? $key_secret : ($cur ? $cur->key_secret : ''),
                    'webhook_secret' => $webhook_secret !== '' ? $webhook_secret : ($cur ? $cur->webhook_secret : ''),
                    'is_active' => (int) $this->input->post('is_active'),
                ]);
            } elseif ($action === 'automation') {
                $this->coaching->automation_save([
                    'name' => trim((string) $this->input->post('name')),
                    'trigger_type' => $this->input->post('trigger_type'),
                    'trigger_config' => json_encode(array('days' => (int) $this->input->post('trigger_days'))),
                    'action_type' => $this->input->post('action_type') ? $this->input->post('action_type') : 'log_reminder',
                    'action_config' => '{}',
                    'is_active' => 1,
                ]);
            }
            $this->session->set_flashdata('success', 'Settings saved.');
            redirect('coaching-admin');
            return;
        }
        $this->load->view('coaching/admin/index', [
            'branding' => $this->coaching->branding(),
            'payments' => $this->coaching->payment_settings(),
            'rules' => $this->coaching->automation_rules_all(),
        ]);
    }

    public function backup()
    {
        $tables = [
            'coaching_coaches', 'coaching_clients', 'coaching_client_coaches', 'coaching_sessions',
            'coaching_goals', 'coaching_homework', 'coaching_leads', 'coaching_workshops',
            'coaching_workshop_registrations', 'coaching_programs', 'coaching_invoices',
            'coaching_installments', 'coaching_coach_payouts', 'coaching_client_resources',
            'coaching_whatsapp_enquiries', 'coaching_whatsapp_broadcasts', 'coaching_automation_rules',
        ];
        $sql = "-- Coaching backup " . date('Y-m-d H:i:s') . "\n";
        foreach ($tables as $t) {
            if (!$this->db->table_exists($t)) {
                continue;
            }
            $rows = $this->db->get($t)->result_array();
            foreach ($rows as $row) {
                $cols = array_map(function ($c) {
                    return '`' . $c . '`';
                }, array_keys($row));
                $vals = array_map(function ($v) {
                    return $this->db->escape($v);
                }, array_values($row));
                $sql .= 'INSERT INTO `' . $t . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ");\n";
            }
        }
        $this->load->helper('download');
        force_download('coaching_backup_' . date('Ymd_His') . '.sql', $sql);
    }

    public function run_automation()
    {
        $this->coaching->run_automation_cron();
        $this->session->set_flashdata('success', 'Automation rules executed.');
        redirect('coaching-admin');
    }
}
