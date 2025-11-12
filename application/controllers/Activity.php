<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Activity extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session']);
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
    }

    // GET /activity
    public function index(){
        // Filters
        $user_id = (int)($this->input->get('user_id') ?: 0);
        $module = trim((string)$this->input->get('module'));
        $action = trim((string)$this->input->get('action'));
        $from = $this->input->get('from');
        $to = $this->input->get('to');

        $this->db->from('activity_log');
        if ($user_id) { $this->db->where('actor_id', $user_id); }
        if ($module !== '') { $this->db->where('entity_type', $module); }
        if ($action !== '') { $this->db->where('action', $action); }
        if ($from) { $this->db->where('created_at >=', $from.' 00:00:00'); }
        if ($to) { $this->db->where('created_at <=', $to.' 23:59:59'); }
        $this->db->order_by('id','DESC');
        $rows = $this->db->limit(200)->get()->result();

        // For filters dropdowns
        $users = $this->db->select('id,email')->from('users')->order_by('email','ASC')->limit(500)->get()->result();
        $modules = ['employees','projects','tasks','attendance','leaves','notifications','reports','permissions','chats','calls','settings'];
        $actions = ['created','updated','deleted','assigned','status_changed','commented','attachment_added'];

        $this->load->view('activity/index', [
            'rows' => $rows,
            'users' => $users,
            'filters' => compact('user_id','module','action','from','to'),
            'modules' => $modules,
            'actions' => $actions,
        ]);
    }

    // GET /activity/export
    public function export(){
        $this->load->dbutil();
        $this->db->from('activity_log')->order_by('id','DESC');
        $query = $this->db->get();
        $csv = $this->dbutil->csv_from_result($query, ",", "\r\n");
        force_download('activity_'.date('Ymd_His').'.csv', $csv);
    }
}
