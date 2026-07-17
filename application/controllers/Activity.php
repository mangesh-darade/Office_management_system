<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Activity extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission']);
        $this->load->library(['session', 'pagination']);
        
        // RBAC Audit: Centralized module access check
        require_module_access('activity', true);
    }

    // GET /activity
    public function index(){
        try {
            // Filters - PHP 5.6 compatible
            $user_id_input = $this->input->get('user_id');
            $user_id = $user_id_input ? (int)$user_id_input : 0;
            $module = trim((string)$this->input->get('module'));
            $action = trim((string)$this->input->get('action'));
            $from = $this->input->get('from');
            $to = $this->input->get('to');
            
            // Validate date inputs
            if ($from && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
                $from = null;
            }
            if ($to && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                $to = null;
            }
            
            // Validate date range
            if ($from && $to && $from > $to) {
                $this->session->set_flashdata('error', 'Start date must be before end date.');
                $to = null;
            }

            // Build query for counting total records
            $this->db->select('activity_log.id');
            $this->db->from('activity_log');
            $this->db->join('users', 'users.id = activity_log.actor_id', 'left');
            
            if ($user_id) { $this->db->where('activity_log.actor_id', $user_id); }
            if ($module !== '') { $this->db->where('activity_log.entity_type', $module); }
            if ($action !== '') { $this->db->where('activity_log.action', $action); }
            if ($from) { $this->db->where('activity_log.created_at >=', $from.' 00:00:00'); }
            if ($to) { $this->db->where('activity_log.created_at <=', $to.' 23:59:59'); }
            
            $total_rows = $this->db->count_all_results();
            
            // Pagination configuration
            $config['base_url'] = site_url('activity');
            $config['total_rows'] = $total_rows;
            $config['per_page'] = 50; // Records per page
            $config['uri_segment'] = 2;
            $config['page_query_string'] = TRUE;
            $config['query_string_segment'] = 'page';
            $config['reuse_query_string'] = TRUE;
            $config['full_tag_open'] = '<ul class="pagination">';
            $config['full_tag_close'] = '</ul>';
            $config['first_tag_open'] = '<li class="page-item">';
            $config['first_tag_close'] = '</li>';
            $config['last_tag_open'] = '<li class="page-item">';
            $config['last_tag_close'] = '</li>';
            $config['next_tag_open'] = '<li class="page-item">';
            $config['next_tag_close'] = '</li>';
            $config['prev_tag_open'] = '<li class="page-item">';
            $config['prev_tag_close'] = '</li>';
            $config['cur_tag_open'] = '<li class="page-item active"><span class="page-link">';
            $config['cur_tag_close'] = '</span></li>';
            $config['num_tag_open'] = '<li class="page-item">';
            $config['num_tag_close'] = '</li>';
            $config['attributes'] = array('class' => 'page-link');
            
            $this->pagination->initialize($config);
            $page = $this->input->get('page') ? (int)$this->input->get('page') : 0;
            $offset = $page > 0 ? ($page - 1) * $config['per_page'] : 0;

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
            $rows = $this->db->limit($config['per_page'], $offset)->get()->result();

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
        
        // Get unique modules from actual activity logs (dynamic)
        $this->db->select('entity_type as module');
        $this->db->distinct();
        $this->db->from('activity_log');
        $this->db->order_by('entity_type', 'ASC');
        $modules_result = $this->db->get()->result();
        $modules = array();
        foreach ($modules_result as $m) {
            $modules[] = $m->module;
        }
        
        // Fallback to default list if no logs exist yet
        if (empty($modules)) {
            $modules = ['employees','projects','tasks','attendance','leaves','leave_requests','leave_types',
                      'notifications','reports','permissions','chats','calls','settings','requirements',
                      'designations','departments','users','clients','timesheets','assets','spl','daily_activity'];
        }
        
        // Get unique actions from actual activity logs (dynamic)
        $this->db->select('action');
        $this->db->distinct();
        $this->db->from('activity_log');
        $this->db->order_by('action', 'ASC');
        $actions_result = $this->db->get()->result();
        $actions = array();
        foreach ($actions_result as $a) {
            $actions[] = $a->action;
        }
        
        // Fallback to default list if no logs exist yet
        if (empty($actions)) {
            $actions = ['created','updated','deleted','assigned','status_changed','commented',
                       'attachment_added','document_uploaded','bulk_status_changed','restored',
                       'submitted','approved','rejected'];
        }

            $this->load->view('activity/index', [
                'rows' => $rows,
                'users' => $users,
                'filters' => compact('user_id','module','action','from','to'),
                'modules' => $modules,
                'actions' => $actions,
                'pagination' => $this->pagination->create_links(),
                'total_rows' => $total_rows,
                'per_page' => $config['per_page'],
                'offset' => $offset,
            ]);
        } catch (Exception $e) {
            log_message('error', 'Activity index error: ' . $e->getMessage());
            show_error('An error occurred while loading activity logs. Please try again later.', 500);
        }
    }

    // GET /activity/export — batched CSV stream to limit memory use
    public function export(){
        try {
            $user_id_input = $this->input->get('user_id');
            $user_id = $user_id_input ? (int)$user_id_input : 0;
            $module = trim((string)$this->input->get('module'));
            $action = trim((string)$this->input->get('action'));
            $from = $this->input->get('from');
            $to = $this->input->get('to');

            if ($from && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
                $from = null;
            }
            if ($to && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                $to = null;
            }

            $filename = 'activity_'.date('Ymd_His');
            if ($module) { $filename .= '_' . $module; }
            if ($action) { $filename .= '_' . $action; }
            $filename .= '.csv';

            // Probe for rows before sending download headers
            $this->db->select('activity_log.id');
            $this->db->from('activity_log');
            if ($user_id) { $this->db->where('activity_log.actor_id', $user_id); }
            if ($module !== '') { $this->db->where('activity_log.entity_type', $module); }
            if ($action !== '') { $this->db->where('activity_log.action', $action); }
            if ($from) { $this->db->where('activity_log.created_at >=', $from.' 00:00:00'); }
            if ($to) { $this->db->where('activity_log.created_at <=', $to.' 23:59:59'); }
            $this->db->limit(1);
            if ($this->db->get()->num_rows() === 0) {
                $this->session->set_flashdata('error', 'No records found to export.');
                redirect('activity');
                return;
            }

            $batch_size = 500;
            $max_records = 50000;
            $offset = 0;
            $total_exported = 0;
            $header_written = false;

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $output = fopen('php://output', 'w');

            while ($total_exported < $max_records) {
                $this->db->reset_query();
                $this->db->select('activity_log.*, users.name as user_name, users.email as user_email');
                $this->db->from('activity_log');
                $this->db->join('users', 'users.id = activity_log.actor_id', 'left');

                if ($user_id) { $this->db->where('activity_log.actor_id', $user_id); }
                if ($module !== '') { $this->db->where('activity_log.entity_type', $module); }
                if ($action !== '') { $this->db->where('activity_log.action', $action); }
                if ($from) { $this->db->where('activity_log.created_at >=', $from.' 00:00:00'); }
                if ($to) { $this->db->where('activity_log.created_at <=', $to.' 23:59:59'); }

                $this->db->order_by('activity_log.id', 'DESC');
                $this->db->limit($batch_size, $offset);
                $rows = $this->db->get()->result_array();

                if (empty($rows)) {
                    break;
                }

                if (!$header_written) {
                    fputcsv($output, array_keys($rows[0]), ',', '"', '\\');
                    $header_written = true;
                }

                foreach ($rows as $row) {
                    fputcsv($output, $row, ',', '"', '\\');
                    $total_exported++;
                    if ($total_exported >= $max_records) {
                        break;
                    }
                }

                if (count($rows) < $batch_size) {
                    break;
                }

                $offset += $batch_size;
            }

            fclose($output);
            exit;
        } catch (Exception $e) {
            log_message('error', 'Activity export error: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'An error occurred while exporting. Please try again.');
            redirect('activity');
        }
    }

    // GET /activity/get-record-data - AJAX endpoint to fetch actual record data
    public function get_record_data(){
        try {
            $entity_type = trim((string)$this->input->get('entity_type'));
            $entity_id = (int)$this->input->get('entity_id');
            
            if (empty($entity_type) || $entity_id <= 0) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'error' => 'Invalid parameters']));
                return;
            }
            
            // Map entity_type to table name (use table_module_mapping if available)
            $table_name = $entity_type;
            $preferred_tables = array(
                'spl' => 'reward_approval_queue',
                'daily_activity' => 'daily_work_logs',
            );
            if (isset($preferred_tables[$entity_type])) {
                $table_name = $preferred_tables[$entity_type];
            } else {
                $this->config->load('table_module_mapping', true);
                $mapping = $this->config->item('table_module_mapping', 'table_module_mapping');
                if ($mapping && is_array($mapping)) {
                    // Reverse lookup: find table name from module name
                    foreach ($mapping as $table => $module) {
                        if ($module === $entity_type) {
                            $table_name = $table;
                            break;
                        }
                    }
                }
            }
            
            // Check if table exists
            if (!$this->db->table_exists($table_name)) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'error' => 'Table not found']));
                return;
            }
            
            // Fetch record
            $record = $this->db->where('id', $entity_id)->get($table_name)->row();
            
            if (!$record) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'error' => 'Record not found']));
                return;
            }
            
            // Convert to array and remove sensitive fields
            $record_data = (array)$record;
            $sensitive_fields = ['password', 'password_hash', 'token', 'api_key', 'secret'];
            foreach ($sensitive_fields as $field) {
                if (isset($record_data[$field])) {
                    $record_data[$field] = '***HIDDEN***';
                }
            }
            
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'data' => $record_data,
                    'table' => $table_name
                ]));
        } catch (Exception $e) {
            log_message('error', 'Activity get_record_data error: ' . $e->getMessage());
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'error' => 'Error fetching record data']));
        }
    }
}
