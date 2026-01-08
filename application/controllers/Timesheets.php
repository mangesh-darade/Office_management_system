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

    /**
     * Ensure schema - DEPRECATED: Use migrations instead
     * This method is kept for backward compatibility but should not be used
     * Run migrations using: php index.php migrate
     */
    private function ensure_schema(){
        // Schema changes have been moved to migrations
        // See: application/migrations/002_Create_timesheets_tables.php
        // Run migrations using: php index.php migrate
        log_message('debug', 'Timesheets::ensure_schema() called - consider using migrations instead');
        
        // Only create tables if they don't exist (backward compatibility)
        if (!$this->db->table_exists('timesheets') || !$this->db->table_exists('timesheet_entries')) {
            log_message('error', 'Timesheets tables missing - please run migrations: php index.php migrate');
        }
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
            try {
                // Validate input
                $work_date = $this->input->post('work_date');
                $hours = (float)$this->input->post('hours');
                
                if (empty($work_date)) {
                    $this->session->set_flashdata('error', 'Work date is required.');
                    redirect('timesheets?week='.$week_start);
                    return;
                }
                
                // Validate date format
                $date_parts = explode('-', $work_date);
                if (count($date_parts) !== 3 || !checkdate((int)$date_parts[1], (int)$date_parts[2], (int)$date_parts[0])) {
                    $this->session->set_flashdata('error', 'Invalid date format.');
                    redirect('timesheets?week='.$week_start);
                    return;
                }
                
                // Validate hours (must be positive and reasonable)
                if ($hours <= 0 || $hours > 24) {
                    $this->session->set_flashdata('error', 'Hours must be between 0 and 24.');
                    redirect('timesheets?week='.$week_start);
                    return;
                }
                
                // Check if date is within the week
                $week_end = date('Y-m-d', strtotime($week_start.' +6 days'));
                if ($work_date < $week_start || $work_date > $week_end) {
                    $this->session->set_flashdata('error', 'Work date must be within the selected week.');
                    redirect('timesheets?week='.$week_start);
                    return;
                }
                
                $timesheet = $this->db->get_where('timesheets', ['user_id'=>$user_id, 'week_start_date'=>$week_start])->row();
                if (!$timesheet){
                    $tid = $this->ts->create_timesheet([
                        'user_id' => $user_id,
                        'week_start_date' => $week_start,
                        'week_end_date' => date('Y-m-d', strtotime($week_start.' +6 days')),
                        'status' => 'draft',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } else { 
                    $tid = (int)$timesheet->id;
                    // Don't allow adding entries to submitted/approved timesheets
                    if (in_array($timesheet->status, ['submitted', 'approved'], true)) {
                        $this->session->set_flashdata('error', 'Cannot add entries to a submitted or approved timesheet.');
                        redirect('timesheets?week='.$week_start);
                        return;
                    }
                }

                $entry = [
                    'project_id' => (int)($this->input->post('project_id') ?: 0),
                    'task_id' => (int)($this->input->post('task_id') ?: 0),
                    'work_date' => $work_date,
                    'hours' => $hours,
                    'description' => trim((string)$this->input->post('description')),
                    'billable' => (int)($this->input->post('billable') ?: 1), // Default to billable
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $this->ts->add_entry($tid, $entry);
                $this->session->set_flashdata('success', 'Entry added.');
                redirect('timesheets?week='.$week_start);
                return;
            } catch (Exception $e) {
                log_message('error', 'Timesheet entry error: ' . $e->getMessage());
                $this->session->set_flashdata('error', 'An error occurred while adding the entry.');
                redirect('timesheets?week='.$week_start);
                return;
            }
        }

        list($row, $entries) = $this->ts->get_user_timesheet($user_id, $week_start);
        
        // Get entries with task and project details
        $entries_with_details = [];
        if (!empty($row->id)) {
            $entries_with_details = $this->ts->get_entries_with_details($row->id);
        }
        
        // Get billable hours summary
        $billable_summary = ['billable' => 0, 'non_billable' => 0, 'total' => 0];
        if (!empty($row->id)) {
            $billable_summary = $this->ts->get_billable_hours($row->id);
        }
        
        $projects = $this->db->select('id,name')->from('projects')->order_by('name','ASC')->get()->result();
        $tasks = $this->db->select('id,title')->from('tasks')->order_by('id','DESC')->limit(500)->get()->result();
        
        $this->load->view('timesheets/index', [
            'timesheet' => $row,
            'entries' => $entries_with_details ?: $entries,
            'week_start' => $week_start,
            'projects' => $projects,
            'tasks' => $tasks,
            'billable_summary' => $billable_summary,
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
    
    // GET /timesheets/analytics
    public function analytics(){
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        
        // Only admins and managers can view analytics
        if (!in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
            show_error('Access denied', 403);
            return;
        }
        
        $start_date = $this->input->get('start_date') ?: date('Y-m-01'); // First day of current month
        $end_date = $this->input->get('end_date') ?: date('Y-m-t'); // Last day of current month
        $project_id = $this->input->get('project_id') ? (int)$this->input->get('project_id') : null;
        $user_filter = $this->input->get('user_id') ? (int)$this->input->get('user_id') : null;
        
        // Project analytics
        $project_analytics = $this->ts->get_project_analytics($project_id, $start_date, $end_date);
        
        // User analytics
        $user_analytics = $this->ts->get_user_analytics($user_filter, $start_date, $end_date);
        
        // Task time tracking
        $task_tracking = $this->ts->get_task_time_tracking(null, $start_date, $end_date);
        
        $projects = $this->db->select('id,name')->from('projects')->order_by('name','ASC')->get()->result();
        $users = $this->db->select('id,email')->from('users')->order_by('email','ASC')->get()->result();
        
        $this->load->view('timesheets/analytics', [
            'project_analytics' => $project_analytics,
            'user_analytics' => $user_analytics,
            'task_tracking' => $task_tracking,
            'projects' => $projects,
            'users' => $users,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'selected_project' => $project_id,
            'selected_user' => $user_filter,
        ]);
    }
    
    // GET /timesheets/task-tracking/{task_id}
    public function task_tracking($task_id = null){
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        
        // Only admins and managers can view task tracking
        if (!in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true)) {
            show_error('Access denied', 403);
            return;
        }
        
        if (!$task_id) {
            show_404();
            return;
        }
        
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        
        $tracking = $this->ts->get_task_time_tracking((int)$task_id, $start_date, $end_date);
        
        $task = $this->db->where('id', (int)$task_id)->get('tasks')->row();
        
        $this->load->view('timesheets/task_tracking', [
            'task' => $task,
            'tracking' => $tracking,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]);
    }
}
