<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url']);
        $this->load->model('Report_model');
    }

    public function index() {
        // Basic aggregates for charts with safe guards if tables are missing
        $task_status = [];
        $projects_progress = [];
        $leaves_monthly = [];

        if ($this->db->table_exists('tasks')) {
            $task_status = $this->db->select('status, COUNT(*) as cnt')->group_by('status')->get('tasks')->result();
        }
        if ($this->db->table_exists('projects')) {
            $projects_progress = $this->db->select('status, COUNT(*) as cnt')->group_by('status')->get('projects')->result();
        }
        if ($this->db->table_exists('leaves')) {
            $leaves_monthly = $this->db->query("SELECT DATE_FORMAT(start_date, '%Y-%m') as ym, COUNT(*) cnt FROM leaves WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym")->result();
        }

        $data = [
            'task_status' => $task_status,
            'projects_progress' => $projects_progress,
            'leaves_monthly' => $leaves_monthly,
        ];
        $this->load->view('reports/dashboard', $data);
    }

    // GET /reports/export
    public function export_csv()
    {
        $this->load->dbutil();
        // Example combined report: tasks with project and assignee
        $sql = "SELECT t.id, t.title, t.status, p.name AS project, u.email AS assigned_user, t.created_at
                FROM tasks t
                LEFT JOIN projects p ON p.id = t.project_id
                LEFT JOIN users u ON u.id = t.assigned_to
                ORDER BY t.id DESC";
        $query = $this->db->query($sql);
        $csv = $this->dbutil->csv_from_result($query, ",", "\r\n");
        force_download('report_tasks_'.date('Ymd_His').'.csv', $csv);
    }
}
