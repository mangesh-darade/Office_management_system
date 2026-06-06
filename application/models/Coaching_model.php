<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coaching_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('coaching');
        coaching_ensure_schema();
    }

    // --- Coaches ---
    public function coaches_all($active_only = true)
    {
        $this->db->select('c.*, u.name, u.email, u.phone');
        $this->db->from('coaching_coaches c');
        $this->db->join('users u', 'u.id = c.user_id', 'left');
        if ($active_only) {
            $this->db->where('c.status', 'active');
        }
        $this->db->order_by('u.name', 'ASC');
        return $this->db->get()->result();
    }

    public function coach_get($id)
    {
        return $this->db->select('c.*, u.name, u.email')
            ->from('coaching_coaches c')
            ->join('users u', 'u.id = c.user_id', 'left')
            ->where('c.id', (int) $id)
            ->get()->row();
    }

    public function coach_by_user($user_id)
    {
        return $this->db->get_where('coaching_coaches', ['user_id' => (int) $user_id])->row();
    }

    public function coach_save($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('coaching_coaches', $data);
            return (int) $id;
        }
        $this->db->insert('coaching_coaches', $data);
        return (int) $this->db->insert_id();
    }

    // --- Clients ---
    public function clients_all($filters = [])
    {
        $this->db->select('cc.*, ch.user_id AS coach_user_id, u.name AS coach_name, c.company_name AS crm_company_name, c.client_code AS crm_client_code');
        $this->db->from('coaching_clients cc');
        $this->db->join('coaching_coaches ch', 'ch.id = cc.primary_coach_id', 'left');
        $this->db->join('users u', 'u.id = ch.user_id', 'left');
        if ($this->db->table_exists('clients')) {
            $this->db->join('clients c', 'c.id = cc.crm_client_id', 'left');
        }
        if (!empty($filters['coach_id'])) {
            $this->db->where('cc.primary_coach_id', (int) $filters['coach_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('cc.status', $filters['status']);
        }
        $this->db->order_by('cc.full_name', 'ASC');
        return $this->db->get()->result();
    }

    public function client_get($id)
    {
        $this->db->select('cc.*');
        if ($this->db->table_exists('clients')) {
            $this->db->select('c.company_name AS crm_company_name, c.client_code AS crm_client_code', false);
        }
        $this->db->from('coaching_clients cc');
        if ($this->db->table_exists('clients')) {
            $this->db->join('clients c', 'c.id = cc.crm_client_id', 'left');
        }
        $this->db->where('cc.id', (int) $id);
        return $this->db->get()->row();
    }

    public function client_by_user($user_id)
    {
        return $this->db->get_where('coaching_clients', ['user_id' => (int) $user_id])->row();
    }

    public function client_save($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('coaching_clients', $data);
            return (int) $id;
        }
        $this->db->insert('coaching_clients', $data);
        return (int) $this->db->insert_id();
    }

    public function client_assign_coaches($client_id, array $coach_ids, $primary_coach_id = null)
    {
        $client_id = (int) $client_id;
        $this->db->delete('coaching_client_coaches', ['coaching_client_id' => $client_id]);
        foreach ($coach_ids as $cid) {
            $cid = (int) $cid;
            if ($cid <= 0) {
                continue;
            }
            $this->db->insert('coaching_client_coaches', [
                'coaching_client_id' => $client_id,
                'coach_id' => $cid,
                'is_primary' => ($primary_coach_id && (int) $primary_coach_id === $cid) ? 1 : 0,
            ]);
        }
        if ($primary_coach_id) {
            $this->db->where('id', $client_id)->update('coaching_clients', [
                'primary_coach_id' => (int) $primary_coach_id,
            ]);
        }
    }

    public function client_coach_ids($client_id)
    {
        $rows = $this->db->select('coach_id')
            ->from('coaching_client_coaches')
            ->where('coaching_client_id', (int) $client_id)
            ->get()->result();
        return array_map(function ($r) { return (int) $r->coach_id; }, $rows);
    }

    public function create_portal_user($email, $full_name, $password_plain)
    {
        if (!$this->db->table_exists('users')) {
            return null;
        }
        $exists = $this->db->get_where('users', ['email' => $email])->row();
        if ($exists) {
            return (int) $exists->id;
        }
        $payload = [
            'name' => $full_name,
            'email' => $email,
            'password_hash' => password_hash($password_plain, PASSWORD_DEFAULT),
            'role_id' => ROLE_COACHING_CLIENT,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        if (schema_table_has_column($this->db, 'users', 'phone')) {
            $payload['phone'] = '';
        }
        $this->db->insert('users', $payload);
        return (int) $this->db->insert_id();
    }

    // --- Sessions ---
    public function sessions_list($filters = [])
    {
        $this->db->select('s.*, cc.full_name AS client_name, u.name AS coach_name');
        $this->db->from('coaching_sessions s');
        $this->db->join('coaching_clients cc', 'cc.id = s.coaching_client_id', 'left');
        $this->db->join('coaching_coaches ch', 'ch.id = s.coach_id', 'left');
        $this->db->join('users u', 'u.id = ch.user_id', 'left');
        if (!empty($filters['coach_id'])) {
            $this->db->where('s.coach_id', (int) $filters['coach_id']);
        }
        if (!empty($filters['client_id'])) {
            $this->db->where('s.coaching_client_id', (int) $filters['client_id']);
        }
        if (!empty($filters['from'])) {
            $this->db->where('s.scheduled_at >=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $this->db->where('s.scheduled_at <=', $filters['to']);
        }
        $this->db->order_by('s.scheduled_at', 'ASC');
        return $this->db->get()->result();
    }

    public function session_get($id)
    {
        return $this->db->get_where('coaching_sessions', ['id' => (int) $id])->row();
    }

    public function session_save($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('coaching_sessions', $data);
            return (int) $id;
        }
        $this->db->insert('coaching_sessions', $data);
        return (int) $this->db->insert_id();
    }

    // --- Goals & homework ---
    public function goals_for_client($client_id)
    {
        return $this->db->where('coaching_client_id', (int) $client_id)
            ->order_by('target_date', 'ASC')
            ->get('coaching_goals')->result();
    }

    public function goal_get($id)
    {
        return $this->db->get_where('coaching_goals', ['id' => (int) $id])->row();
    }

    public function goal_save($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('coaching_goals', $data);
            return (int) $id;
        }
        $this->db->insert('coaching_goals', $data);
        return (int) $this->db->insert_id();
    }

    public function homework_for_client($client_id)
    {
        return $this->db->where('coaching_client_id', (int) $client_id)
            ->order_by('due_date', 'ASC')
            ->get('coaching_homework')->result();
    }

    public function homework_get($id)
    {
        return $this->db->get_where('coaching_homework', ['id' => (int) $id])->row();
    }

    public function homework_save($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('coaching_homework', $data);
            return (int) $id;
        }
        $this->db->insert('coaching_homework', $data);
        return (int) $this->db->insert_id();
    }

    // --- Leads & workshops ---
    public function leads_all($status = null)
    {
        if ($status) {
            $this->db->where('status', $status);
        }
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('coaching_leads')->result();
    }

    public function lead_get($id)
    {
        return $this->db->get_where('coaching_leads', ['id' => (int) $id])->row();
    }

    public function lead_save($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('coaching_leads', $data);
            return (int) $id;
        }
        $this->db->insert('coaching_leads', $data);
        return (int) $this->db->insert_id();
    }

    public function workshops_all()
    {
        return $this->db->order_by('workshop_date', 'DESC')->get('coaching_workshops')->result();
    }

    public function workshop_get($id)
    {
        return $this->db->get_where('coaching_workshops', ['id' => (int) $id])->row();
    }

    public function workshop_save($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('coaching_workshops', $data);
            return (int) $id;
        }
        $this->db->insert('coaching_workshops', $data);
        return (int) $this->db->insert_id();
    }

    public function workshop_register($workshop_id, $data)
    {
        $lead_id = $this->lead_save([
            'full_name' => $data['full_name'],
            'email' => isset($data['email']) ? $data['email'] : null,
            'phone' => isset($data['phone']) ? $data['phone'] : null,
            'source' => 'workshop',
            'workshop_id' => (int) $workshop_id,
            'status' => 'new',
        ]);
        $this->db->insert('coaching_workshop_registrations', [
            'workshop_id' => (int) $workshop_id,
            'lead_id' => $lead_id,
            'full_name' => $data['full_name'],
            'email' => isset($data['email']) ? $data['email'] : null,
            'phone' => isset($data['phone']) ? $data['phone'] : null,
        ]);
        return (int) $this->db->insert_id();
    }

    // --- Billing ---
    public function programs_all()
    {
        return $this->db->order_by('name', 'ASC')->get('coaching_programs')->result();
    }

    public function program_get($id)
    {
        return $this->db->get_where('coaching_programs', ['id' => (int) $id])->row();
    }

    public function program_save($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('coaching_programs', $data);
            return (int) $id;
        }
        $this->db->insert('coaching_programs', $data);
        return (int) $this->db->insert_id();
    }

    public function invoices_all($client_id = null)
    {
        $this->db->select('i.*, cc.full_name AS client_name, p.name AS program_name');
        $this->db->from('coaching_invoices i');
        $this->db->join('coaching_clients cc', 'cc.id = i.coaching_client_id', 'left');
        $this->db->join('coaching_programs p', 'p.id = i.program_id', 'left');
        if ($client_id) {
            $this->db->where('i.coaching_client_id', (int) $client_id);
        }
        $this->db->order_by('i.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function invoice_get($id)
    {
        return $this->db->get_where('coaching_invoices', ['id' => (int) $id])->row();
    }

    public function invoice_create_with_installments($client_id, $program_id, $total, $installment_count)
    {
        $invoice_no = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        $this->db->insert('coaching_invoices', [
            'coaching_client_id' => (int) $client_id,
            'program_id' => $program_id ? (int) $program_id : null,
            'invoice_no' => $invoice_no,
            'total_amount' => $total,
            'paid_amount' => 0,
            'status' => 'sent',
        ]);
        $invoice_id = (int) $this->db->insert_id();
        $count = max(1, (int) $installment_count);
        $each = round($total / $count, 2);
        $remainder = $total - ($each * $count);
        for ($i = 1; $i <= $count; $i++) {
            $amt = $each + ($i === $count ? $remainder : 0);
            $this->db->insert('coaching_installments', [
                'invoice_id' => $invoice_id,
                'installment_no' => $i,
                'amount' => $amt,
                'due_date' => date('Y-m-d', strtotime('+' . ($i * 30) . ' days')),
                'status' => 'pending',
            ]);
        }
        return $invoice_id;
    }

    public function installments_for_invoice($invoice_id)
    {
        return $this->db->where('invoice_id', (int) $invoice_id)
            ->order_by('installment_no', 'ASC')
            ->get('coaching_installments')->result();
    }

    public function mark_installment_paid($installment_id, $gateway = 'manual', $ref = '')
    {
        $inst = $this->db->get_where('coaching_installments', ['id' => (int) $installment_id])->row();
        if (!$inst) {
            return false;
        }
        $this->db->where('id', (int) $installment_id)->update('coaching_installments', [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
            'payment_gateway' => $gateway,
            'payment_ref' => $ref,
        ]);
        $invoice = $this->invoice_get($inst->invoice_id);
        if ($invoice) {
            $paid = (float) $invoice->paid_amount + (float) $inst->amount;
            $status = $paid >= (float) $invoice->total_amount ? 'paid' : 'partial';
            $this->db->where('id', (int) $invoice->id)->update('coaching_invoices', [
                'paid_amount' => $paid,
                'status' => $status,
            ]);
        }
        return true;
    }

    public function payouts_all()
    {
        $this->db->select('p.*, u.name AS coach_name');
        $this->db->from('coaching_coach_payouts p');
        $this->db->join('coaching_coaches c', 'c.id = p.coach_id', 'left');
        $this->db->join('users u', 'u.id = c.user_id', 'left');
        $this->db->order_by('p.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function payout_save($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('coaching_coach_payouts', $data);
            return (int) $id;
        }
        $this->db->insert('coaching_coach_payouts', $data);
        return (int) $this->db->insert_id();
    }

    // --- Resources, WhatsApp, automation, settings ---
    public function resources_list($client_id = null)
    {
        if ($client_id) {
            $this->db->group_start();
            $this->db->where('coaching_client_id', (int) $client_id);
            $this->db->or_where('coaching_client_id IS NULL', null, false);
            $this->db->group_end();
        }
        return $this->db->order_by('created_at', 'DESC')->get('coaching_client_resources')->result();
    }

    public function resource_save($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('coaching_client_resources', $data);
            return (int) $id;
        }
        $this->db->insert('coaching_client_resources', $data);
        return (int) $this->db->insert_id();
    }

    public function enquiries_all($status = null)
    {
        if ($status) {
            $this->db->where('status', $status);
        }
        return $this->db->order_by('created_at', 'DESC')->get('coaching_whatsapp_enquiries')->result();
    }

    public function enquiry_save($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('coaching_whatsapp_enquiries', $data);
            return (int) $id;
        }
        $this->db->insert('coaching_whatsapp_enquiries', $data);
        return (int) $this->db->insert_id();
    }

    public function broadcasts_all()
    {
        return $this->db->order_by('created_at', 'DESC')->get('coaching_whatsapp_broadcasts')->result();
    }

    public function broadcast_save($data)
    {
        $this->db->insert('coaching_whatsapp_broadcasts', $data);
        return (int) $this->db->insert_id();
    }

    public function automation_rules_all()
    {
        return $this->db->order_by('id', 'ASC')->get('coaching_automation_rules')->result();
    }

    public function automation_save($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int) $id)->update('coaching_automation_rules', $data);
            return (int) $id;
        }
        $this->db->insert('coaching_automation_rules', $data);
        return (int) $this->db->insert_id();
    }

    public function payment_settings()
    {
        return $this->db->get_where('coaching_payment_settings', ['id' => 1])->row();
    }

    public function payment_settings_save($data)
    {
        $this->db->where('id', 1)->update('coaching_payment_settings', $data);
    }

    public function branding()
    {
        return $this->db->get_where('coaching_branding', ['id' => 1])->row();
    }

    public function branding_save($data)
    {
        $this->db->where('id', 1)->update('coaching_branding', $data);
    }

    public function dashboard_stats()
    {
        return [
            'active_clients' => (int) $this->db->where('status', 'active')->count_all_results('coaching_clients'),
            'coaches' => (int) $this->db->where('status', 'active')->count_all_results('coaching_coaches'),
            'sessions_this_week' => (int) $this->db
                ->where('scheduled_at >=', date('Y-m-d 00:00:00', strtotime('monday this week')))
                ->where('scheduled_at <=', date('Y-m-d 23:59:59', strtotime('sunday this week')))
                ->where('status', 'scheduled')
                ->count_all_results('coaching_sessions'),
            'open_leads' => (int) $this->db->where_in('status', ['new', 'contacted', 'qualified'])->count_all_results('coaching_leads'),
            'revenue_paid' => (float) (($row = $this->db->select_sum('paid_amount')->get('coaching_invoices')->row()) && isset($row->paid_amount) ? $row->paid_amount : 0),
            'pending_installments' => (int) $this->db->where('status', 'pending')->count_all_results('coaching_installments'),
        ];
    }

    public function run_automation_cron()
    {
        $this->load->library('coaching_automation');
        return $this->coaching_automation->run_automation_cron();
    }

    // --- Razorpay orders ---
    public function payment_order_save($installment_id, $razorpay_order_id, $amount_paise)
    {
        $this->db->insert('coaching_payment_orders', [
            'installment_id' => (int) $installment_id,
            'razorpay_order_id' => $razorpay_order_id,
            'amount_paise' => (int) $amount_paise,
            'status' => 'created',
        ]);
        return (int) $this->db->insert_id();
    }

    public function payment_order_by_rzp_id($razorpay_order_id)
    {
        return $this->db->get_where('coaching_payment_orders', [
            'razorpay_order_id' => $razorpay_order_id,
        ])->row();
    }

    public function payment_order_mark_paid($razorpay_order_id, $payment_id)
    {
        $this->db->where('razorpay_order_id', $razorpay_order_id)->update('coaching_payment_orders', [
            'status' => 'paid',
            'razorpay_payment_id' => $payment_id,
        ]);
    }

    public function installment_row($installment_id)
    {
        $this->db->from('coaching_installments i');
        $this->db->join('coaching_invoices inv', 'inv.id = i.invoice_id');
        $this->db->join('coaching_clients cc', 'cc.id = inv.coaching_client_id');
        $this->db->where('i.id', (int) $installment_id);
        return $this->db->get()->row();
    }

    // --- Session email reminders ---
    public function sessions_needing_reminder($hours_before, $flag_column)
    {
        $this->load->library('coaching_automation');
        return $this->coaching_automation->sessions_needing_reminder($hours_before, $flag_column);
    }

    public function session_mark_reminder_sent($session_id, $flag_column)
    {
        $this->load->library('coaching_automation');
        return $this->coaching_automation->session_mark_reminder_sent($session_id, $flag_column);
    }

    public function process_session_reminder_cron()
    {
        $this->load->library('coaching_automation');
        return $this->coaching_automation->process_session_reminder_cron();
    }
}
