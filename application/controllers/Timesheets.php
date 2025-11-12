<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Timesheets extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session']);
        $this->load->model('Timesheet_model','ts');
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
        $this->ensure_schema();
    }

    private function ensure_schema(){
        // Minimal schema to avoid runtime errors if installer wasn't run
        $this->db->query("CREATE TABLE IF NOT EXISTS timesheets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            week_start_date DATE NOT NULL,
            week_end_date DATE NOT NULL,
            total_hours DECIMAL(5,2) DEFAULT 0,
            status ENUM('draft','submitted','approved','rejected') DEFAULT 'draft',
            submitted_at DATETIME NULL,
            approved_by INT NULL,
            approved_at DATETIME NULL,
            comments TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_week (user_id, week_start_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS timesheet_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            timesheet_id INT NOT NULL,
            task_id INT NULL,
            project_id INT NULL,
            work_date DATE NOT NULL,
            hours DECIMAL(5,2) NOT NULL,
            description TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (timesheet_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // GET/POST /timesheets (My Timesheet)
    public function index(){
        $user_id = (int)$this->session->userdata('user_id');
        $week_start = $this->input->get('week');
        if (!$week_start){
            // default to Monday of current week
            $dow = date('N'); // 1..7
            $week_start = date('Y-m-d', strtotime('-'.($dow-1).' days'));
        }

        // Handle add entry (POST)
        if ($this->input->method() === 'post'){
            $timesheet = $this->db->get_where('timesheets', ['user_id'=>$user_id, 'week_start_date'=>$week_start])->row();
            if (!$timesheet){
                $tid = $this->ts->create_timesheet([
                    'user_id' => $user_id,
                    'week_start_date' => $week_start,
                    'week_end_date' => date('Y-m-d', strtotime($week_start.' +6 days')),
                    'status' => 'draft',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } else { $tid = (int)$timesheet->id; }

            $entry = [
                'project_id' => (int)($this->input->post('project_id') ?: 0),
                'task_id' => (int)($this->input->post('task_id') ?: 0),
                'work_date' => $this->input->post('work_date'),
                'hours' => (float)$this->input->post('hours'),
                'description' => trim((string)$this->input->post('description')),
                'created_at' => date('Y-m-d H:i:s')
            ];
            if ($entry['work_date']){ $this->ts->add_entry($tid, $entry); }
            $this->session->set_flashdata('success', 'Entry added.');
            redirect('timesheets?week='.$week_start); return;
        }

        list($row, $entries) = $this->ts->get_user_timesheet($user_id, $week_start);
        $projects = $this->db->select('id,name')->from('projects')->order_by('name','ASC')->get()->result();
        $tasks = $this->db->select('id,title')->from('tasks')->order_by('id','DESC')->limit(500)->get()->result();
        $this->load->view('timesheets/index', [
            'timesheet' => $row,
            'entries' => $entries,
            'week_start' => $week_start,
            'projects' => $projects,
            'tasks' => $tasks,
        ]);
    }

    // POST /timesheets/submit
    public function submit(){
        if ($this->input->method() !== 'post') { show_404(); }
        $user_id = (int)$this->session->userdata('user_id');
        $week_start = $this->input->post('week_start');
        $ts = $this->db->get_where('timesheets', ['user_id'=>$user_id, 'week_start_date'=>$week_start])->row();
        if (!$ts) { $this->session->set_flashdata('error', 'No timesheet found'); redirect('timesheets?week='.$week_start); return; }
        $this->ts->submit_timesheet((int)$ts->id);
        $this->session->set_flashdata('success', 'Timesheet submitted.');
        redirect('timesheets?week='.$week_start);
    }

    // POST /timesheets/approve/{id}
    public function approve($id){
        if ($this->input->method() !== 'post') { show_404(); }
        $manager_id = (int)$this->session->userdata('user_id');
        // Ensure manager is reporting_to for this user
        $ts = $this->db->where('id',(int)$id)->get('timesheets')->row();
        if (!$ts) { show_404(); }
        $ok = true;
        if ($this->db->table_exists('employees')){
            $emp = $this->db->select('reporting_to')->from('employees')->where('user_id',(int)$ts->user_id)->get()->row();
            if ($emp && (int)$emp->reporting_to !== $manager_id) { $ok = false; }
        }
        if (!$ok) { show_error('Forbidden', 403); }
        $comments = trim((string)$this->input->post('comments'));
        $this->ts->approve_reject((int)$id, 'approved', $manager_id, $comments);
        $this->session->set_flashdata('success', 'Timesheet approved.');
        redirect('timesheets');
    }

    // POST /timesheets/reject/{id}
    public function reject($id){
        if ($this->input->method() !== 'post') { show_404(); }
        $manager_id = (int)$this->session->userdata('user_id');
        $comments = trim((string)$this->input->post('comments'));
        $this->ts->approve_reject((int)$id, 'rejected', $manager_id, $comments);
        $this->session->set_flashdata('success', 'Timesheet rejected.');
        redirect('timesheets');
    }

    // GET /timesheets/report
    public function report(){
        $year = (int)($this->input->get('year') ?: date('Y'));
        $month = (int)($this->input->get('month') ?: date('m'));
        $rows = $this->ts->report_monthly_hours($year, $month);
        $this->load->view('timesheets/report', ['rows'=>$rows,'year'=>$year,'month'=>$month]);
    }
}
