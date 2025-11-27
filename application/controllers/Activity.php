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

        // Join with users table to get user names
        $this->db->select('activity_log.*, users.name as user_name, users.email as user_email');
        $this->db->from('activity_log');
        $this->db->join('users', 'users.id = activity_log.actor_id', 'left');
        
        if ($user_id) { $this->db->where('activity_log.actor_id', $user_id); }
        if ($module !== '') { $this->db->where('activity_log.entity_type', $module); }
        if ($action !== '') { $this->db->where('activity_log.action', $action); }
        if ($from) { $this->db->where('activity_log.created_at >=', $from.' 00:00:00'); }
        if ($to) { $this->db->where('activity_log.created_at <=', $to.' 23:59:59'); }
        $this->db->order_by('activity_log.id','DESC');
        $rows = $this->db->limit(200)->get()->result();

        // For filters dropdowns - get users with names
        $users = $this->db->select('users.id, users.name, users.email, 
                                   CASE 
                                     WHEN users.name IS NOT NULL AND users.name != "" THEN CONCAT(users.name, " (", users.email, ")")
                                     ELSE users.email 
                                   END as display_name')
                                 ->from('users')
                                 ->order_by('users.name ASC, users.email ASC')
                                 ->limit(500)
                                 ->get()
                                 ->result();
        
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
        
        // Join with users table to get user names for export
        $this->db->select('activity_log.*, users.name as user_name, users.email as user_email');
        $this->db->from('activity_log');
        $this->db->join('users', 'users.id = activity_log.actor_id', 'left');
        $this->db->order_by('activity_log.id','DESC');
        $query = $this->db->get();
        
        $csv = $this->dbutil->csv_from_result($query, ",", "\r\n");
        force_download('activity_'.date('Ymd_His').'.csv', $csv);
    }
}
