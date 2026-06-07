<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'group_filter', 'permission', 'dashboard','schema_columns']);
        $this->load->library(['session']);
        $this->load->model('External_dashboard_model', 'external_dashboards');
        
        // RBAC Audit: Centralized module access check
        require_module_access('dashboard', true);
        $this->external_dashboards->ensure_schema();
    }

    public function index(){
        try {
            $user_id = $this->session->userdata('user_id');
            $role_id = $this->session->userdata('role_id');
            
            // Normalized parent keys for stat cards (granular permissions → dashboard groups)
            $accessible_modules = get_accessible_modules(true);
        
        // Get group-based filters
        $filters = get_user_group_filter($user_id, $role_id);
        $accessible_user_ids = get_accessible_user_ids($user_id, $role_id);
        
        // Fetch announcements
        $announcements = [];
        if ($this->db->table_exists('announcements')){
            $today = date('Y-m-d');
            $this->db->from('announcements');
            
            // Check if status column exists
            if (schema_table_has_column($this->db, 'announcements', 'status')) {
                $this->db->where('status','published');
            }
            
            // Check if start_date column exists
            if (schema_table_has_column($this->db, 'announcements', 'start_date')) {
                $this->db->group_start()
                            ->where('start_date <=', $today)
                            ->or_where('start_date IS NULL')
                         ->group_end();
            }
            
            // Check if end_date column exists
            if (schema_table_has_column($this->db, 'announcements', 'end_date')) {
                $this->db->group_start()
                            ->where('end_date >=', $today)
                            ->or_where('end_date IS NULL')
                         ->group_end();
            }
            
            // Check if priority column exists
            if (schema_table_has_column($this->db, 'announcements', 'priority')) {
                $this->db->order_by('priority','DESC');
            }
            
            $this->db->order_by('id','DESC')
                     ->limit(5);
            $announcements = $this->db->get()->result();
        }

        $meal_dashboard = null;
        if ($this->db->table_exists('meal_calendar')) {
            $this->load->helper(array('meal_schema', 'meal'));
            meal_schema_ensure($this->db);
            meal_cleanup_legacy_meal_announcements($this->db);
            $announcements = meal_filter_dashboard_announcements($announcements);
            if (meal_can_view_dashboard_announcement($this->db)) {
                $meal_dashboard = meal_dashboard_today_tomorrow($this->db);
            }
        }
        
        // Fetch dashboard statistics with caching (5 minutes TTL)
        $stats = get_dashboard_stats($user_id, $role_id, 300);

        // Today's punch status for Mark Attendance widget (all logged-in users)
        $mark_attendance = array(
            'has_checkin'   => false,
            'has_checkout'  => false,
            'checkin_time'  => '',
            'checkout_time' => '',
        );
        if ($user_id && $this->db->table_exists('attendance')) {
            $this->load->helper(array('date', 'attendance_punch'));
            $user_timezone = get_user_timezone((int) $user_id);
            $today = get_current_datetime($user_timezone, 'Y-m-d');
            $has_column_fn = function ($field) {
                return attendance_punch_has_column($this->db, $field);
            };
            $today_status = attendance_punch_today_status(
                $this->db,
                $has_column_fn,
                (int) $user_id,
                $today
            );
            $mark_attendance = array(
                'has_checkin'   => !empty($today_status['has_checkin']),
                'has_checkout'  => !empty($today_status['has_checkout']),
                'checkin_time'  => isset($today_status['checkin_time']) ? (string) $today_status['checkin_time'] : '',
                'checkout_time' => isset($today_status['checkout_time']) ? (string) $today_status['checkout_time'] : '',
            );
        }
        
            $this->load->view('dashboard/index', [
                'role_id' => $role_id, 
                'announcements' => $announcements,
                'meal_dashboard' => $meal_dashboard,
                'stats' => $stats,
                'accessible_modules' => $accessible_modules,
                'external_dashboards' => $this->external_dashboards->get_active(),
                'mark_attendance' => $mark_attendance,
            ]);
        } catch (Exception $e) {
            log_message('error', 'Dashboard error: ' . $e->getMessage());
            show_error('An error occurred while loading the dashboard. Please try again later.', 500);
        }
    }
}
