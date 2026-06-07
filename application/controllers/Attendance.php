<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance extends CI_Controller {
    private static $email_sent_tracker = []; // Track sent emails to prevent duplicates
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission','group_filter','hierarchy_filter','company','schema_columns','attendance_punch','attendance_bulk','attendance_export','attendance_notify','attendance_geo','attendance_list']);
        $this->load->library(['session','upload','email','pagination']);
        $this->load->model('Attendance_model');
        $this->load->model('Face_model', 'faces');
        $this->load->model('Setting_model', 'settings');
        $this->load->model('Holiday_model', 'holidays');
        
        $user_id = (int)$this->session->userdata('user_id');
        if (!$user_id) {
            redirect('auth/login');
            return;
        }

        $method = (string)$this->router->fetch_method();
        $attendance_list_access = [
            'attendance', 'attendance_list', 'attendance_edit',
            'attendance_delete', 'attendance_view_all', 'attendance_export',
            'reports_attendance', 'reports_attendance_employee',
        ];

        // Self punch — any logged-in user
        if ($method === 'create') {
            return;
        }

        switch ($method) {
            case 'bulk_operations':
                require_module_access(['attendance_bulk', 'attendance'], true);
                break;
            case 'export':
                require_module_access(get_attendance_export_module_keys(), true);
                break;
            case 'edit':
                require_module_access(['attendance_edit', 'attendance'], true);
                break;
            case 'delete':
                require_module_access(['attendance_delete', 'attendance'], true);
                break;
            case 'index':
            case 'get_user_monthly_attendance':
                require_module_access($attendance_list_access, true);
                break;
            default:
                require_module_access($attendance_list_access, true);
                break;
        }
    }

    /**
     * Check whether a column exists on the attendance table (one list_fields call per request).
     *
     * @param string $field
     * @return bool
     */
    private function attendance_field_exists($field)
    {
        return attendance_punch_has_column($this->db, $field);
    }

    /**
     * @return callable(string):bool
     */
    private function attendance_has_column_fn()
    {
        return function ($field) {
            return $this->attendance_field_exists($field);
        };
    }

    public function index() {
        // Pagination configuration
        $page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $total_records = 0;
        
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        
        $canViewAll = has_module_access('attendance_view_all') || is_admin_group();
        $canAddAttendance = true; // All logged-in users can add their own attendance
        
        // Get group-based filters
        $filters = get_user_group_filter($user_id, $role_id);
        
        // Count total distinct users for pagination
        $this->db->select('COUNT(DISTINCT a.user_id) as total');
        $this->db->from('attendance a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        
        apply_role_hierarchy_filter($this->db, 'a.user_id', $user_id, $role_id);
        
        $total_query = $this->db->get()->row();
        $total_records = $total_query->total;
        
        // Fetch distinct users with their latest attendance and count
        // Get name only from users table, not from employees table
        $this->db->select('a.user_id, u.email, u.name as user_name, COUNT(*) as attendance_count, MAX(a.att_date) as last_attendance_date');
        $this->db->from('attendance a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        
        apply_role_hierarchy_filter($this->db, 'a.user_id', $user_id, $role_id);
        
        $this->db->group_by('a.user_id, u.email, u.name');
        
        // Order by user name alphabetically (A-Z) - only from users table
        $this->db->order_by('u.name', 'ASC');
        
        // Get all records - DataTables will handle pagination client-side
        $records = $this->db->get()->result();
        
        // Check edit and delete permissions
        $this->load->helper('permission');
        $canEditAttendance = function_exists('has_module_access') && (has_module_access('attendance_edit') || has_module_access('attendance'));
        $canDeleteAttendance = function_exists('has_module_access') && (has_module_access('attendance_delete') || has_module_access('attendance'));
        $canExportAttendance = can_access_attendance_export();
        
        $this->load->view('attendance/index', [
            'records' => $records,
            'total_records' => $total_records,
            'can_add_attendance' => $canAddAttendance,
            'can_view_all' => $canViewAll,
            'can_edit_attendance' => $canEditAttendance,
            'can_delete_attendance' => $canDeleteAttendance,
            'can_export_attendance' => $canExportAttendance,
            'current_user_id' => $user_id,
            'is_admin_group' => is_admin_group(),
            'current_role_id' => $role_id
        ]);
    }

    // Get user's monthly attendance data for popup
    public function get_user_monthly_attendance() {
        $requested_user_id = (int)$this->input->post('user_id');
        $current_user_id   = (int)$this->session->userdata('user_id');
        $current_role_id   = (int)$this->session->userdata('role_id');

        if (!$requested_user_id) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            return;
        }

        // IDOR protection using hierarchy access list
        $allowed_ids = get_accessible_hierarchy_user_ids($current_user_id, $current_role_id);
        if (!empty($allowed_ids) && !in_array($requested_user_id, $allowed_ids, true)) {
            show_error('Forbidden', 403);
        }

        $user_id      = $requested_user_id;
        $filter_type  = $this->input->post('filter_type');
        $filter_value = $this->input->post('filter_value');
        $page         = (int) $this->input->post('page') ?: 1;
        $per_page     = 10;

        $hasColumn = $this->attendance_has_column_fn();
        $col_date = attendance_punch_resolve_date_column($hasColumn);

        $result = attendance_list_fetch_user_popup(
            $this->db,
            $user_id,
            $col_date,
            $filter_type,
            $filter_value,
            $page,
            $per_page
        );

        $attendance_data = array();
        foreach ($result['records'] as $r) {
            $attendance_data[] = attendance_list_popup_row($r, $col_date, $current_user_id, $current_role_id);
        }

        echo json_encode(array(
            'success' => true,
            'data' => $attendance_data,
            'pagination' => array(
                'current_page'  => $page,
                'total_pages'   => $result['total_pages'],
                'total_records' => $result['total'],
                'per_page'      => $per_page,
                'has_prev'      => $page > 1,
                'has_next'      => $page < $result['total_pages'],
            ),
        ));
    }

    // Bulk operations for attendance
    public function bulk_operations() {
        if ($this->input->method() === 'post') {
            $operation = $this->input->post('bulk_action');
            $selected_ids = $this->input->post('selected_ids');
            
            if (empty($operation) || empty($selected_ids) || !is_array($selected_ids)) {
                $this->session->set_flashdata('error', 'Please select records and an operation');
                redirect('attendance');
                return;
            }

            // Validate and sanitize IDs
            $valid_ids = array_filter($selected_ids, function($id) {
                return is_numeric($id) && $id > 0;
            });
            
            if (empty($valid_ids)) {
                $this->session->set_flashdata('error', 'Invalid record IDs selected');
                redirect('attendance');
                return;
            }

            $affected_count = 0;
            $error_count = 0;

            try {
                switch ($operation) {
                    case 'delete':
                        $affected_count = attendance_bulk_delete($this->db, $valid_ids) ? count($valid_ids) : 0;
                        break;
                    case 'mark_present':
                        $affected_count = attendance_bulk_mark_present($this->db, $valid_ids);
                        break;
                    case 'clear_checkout':
                        $affected_count = attendance_bulk_clear_checkout($this->db, $valid_ids);
                        break;
                    default:
                        $this->session->set_flashdata('error', 'Invalid operation selected');
                        redirect('attendance');
                        return;
                }

                if ($affected_count > 0) {
                    $this->session->set_flashdata('success', "Operation completed successfully. {$affected_count} records affected.");
                } else {
                    $this->session->set_flashdata('error', 'No records were affected by the operation.');
                }
            } catch (Exception $e) {
                $this->session->set_flashdata('error', 'Operation failed: ' . $e->getMessage());
            }
        }

        redirect('attendance');
    }

    // GET/POST /attendance/create
    public function create()
    {
        if ($this->input->method() === 'post') {
            $user_id = (int)$this->session->userdata('user_id');
            if (!$user_id) { 
                $this->session->set_flashdata('error', 'Please login to mark attendance');
                redirect('login'); 
                return;
            }

            // Prevent marking attendance on company holidays
            $this->load->helper(['date', 'attendance_punch']);
            $user_timezone = get_user_timezone($user_id);
            $today = get_current_datetime($user_timezone, 'Y-m-d');
            $holiday_row = attendance_punch_active_holiday($this->db, $today);
            if ($holiday_row) {
                $this->session->set_flashdata('error', attendance_punch_holiday_block_message($holiday_row));
                redirect('attendance/create');
                return;
            }

            // Enhanced validation
            $action = $this->input->post('action');
            if (!in_array($action, ['in', 'out'], true)) {
                $this->load->helper('date');
                $user_timezone = get_user_timezone($user_id);
                $today = get_current_datetime($user_timezone, 'Y-m-d');
                $error_key = attendance_punch_invalid_action_error(
                    $this->db,
                    $this->attendance_has_column_fn(),
                    $user_id,
                    $today
                );
                if ($error_key === 'already_marked') {
                    $this->load->helper('notification');
                    $error_msg = get_notification_message('attendance', 'already_marked', 'error');
                    $this->session->set_flashdata('error', $error_msg);
                } else {
                    $this->session->set_flashdata('error', $error_key);
                }
                redirect('attendance/create');
                return;
            }

            // Validate attachment if provided
            $attachment_path = '';
            if (!empty($_FILES['attachment']['name'])) {
                $config['upload_path'] = FCPATH.'uploads/attendance/';
                $config['allowed_types'] = 'jpg|jpeg|png|pdf|doc|docx';
                $config['max_size'] = 4096; // 4MB
                $config['file_name'] = 'att_'.$user_id.'_'.date('Y-m-d_H-i-s').'_'.rand(1000,9999);
                $config['overwrite'] = true;
                
                if (!is_dir($config['upload_path'])) {
                    mkdir($config['upload_path'], 0755, true);
                }
                
                $this->upload->initialize($config);
                
                if (!$this->upload->do_upload('attachment')) {
                    $this->session->set_flashdata('error', 'File upload failed: ' . $this->upload->display_errors('', ' '));
                    redirect('attendance/create');
                    return;
                }
                
                $upload_data = $this->upload->data();
                $attachment_path = 'uploads/attendance/' . $upload_data['file_name'];
            }

            // Face verification (optional based on setting)
            $face_descriptor = (string)$this->input->post('face_descriptor');
            $face_check = attendance_punch_verify_face_for_create(
                $this->settings,
                $this->faces,
                $user_id,
                $face_descriptor
            );
            if (!$face_check['ok']) {
                if (isset($face_check['error']) && $face_check['error'] === 'face_verification_failed') {
                    $this->load->helper('notification');
                    $error_msg = get_notification_message('attendance', 'face_verification_failed', 'error');
                    $this->session->set_flashdata('error', $error_msg);
                } else {
                    $this->session->set_flashdata('error', $face_check['error']);
                }
                redirect('attendance/create');
                return;
            }

            // Location handling - MANDATORY
            $lat = $this->input->post('lat');
            $lng = $this->input->post('lng');
            $location_name = $this->input->post('location_name');
            
            // Validate location is mandatory
            if (empty($lat) || empty($lng)) {
                $this->load->helper('notification');
                $error_msg = get_notification_message('attendance', 'location_mismatch', 'error');
                $this->session->set_flashdata('error', $error_msg);
                redirect('attendance/create');
                return;
            }
            
            // Validate location against office coordinates if strict mode enabled
            $location_check = attendance_punch_validate_location_strict(
                $this->settings,
                $lat,
                $lng,
                'attendance_geo_calculate_distance'
            );
            if (!$location_check['ok']) {
                $this->load->helper('notification');
                $error_msg = get_notification_message('attendance', 'location_mismatch', 'error');
                $distance = isset($location_check['distance']) ? $location_check['distance'] : 0;
                $allowed_radius = isset($location_check['radius']) ? $location_check['radius'] : 0;
                $error_msg = str_replace('{distance}', round($distance, 0), $error_msg);
                $error_msg = str_replace('{radius}', round($allowed_radius, 0), $error_msg);
                $this->session->set_flashdata('error', $error_msg . ' (Distance: ' . round($distance, 0) . 'm, Allowed: ' . round($allowed_radius, 0) . 'm)');
                redirect('attendance/create');
                return;
            }

            // Get current date/time with timezone support
            $this->load->helper('date');
            $user_timezone = get_user_timezone($user_id);
            $nowDateTime = get_current_datetime($user_timezone, 'Y-m-d H:i:s');
            $nowTime = get_current_datetime($user_timezone, 'H:i:s');
            $today = get_current_datetime($user_timezone, 'Y-m-d');

            // Schema-aware column names
            $timeCols = attendance_punch_resolve_time_columns($this->attendance_has_column_fn());
            $col_date = $timeCols['col_date'];
            $col_in = $timeCols['col_in'];
            $col_out = $timeCols['col_out'];
            $hasPunchIn = $timeCols['hasPunchIn'];
            $hasCheckIn = $timeCols['hasCheckIn'];
            $hasPunchOut = $timeCols['hasPunchOut'];
            $hasCheckOut = $timeCols['hasCheckOut'];
            $db = $this->db;
            $getColType = function ($table, $column) use ($db) {
                return attendance_schema_column_type($db, $table, $column);
            };
            $geoGroups = attendance_punch_geo_field_groups();

            // Check if user already has a record for today (use limit 1 to ensure single record)
            $existing = $this->db->where('user_id', $user_id)
                                 ->where($col_date, $today)
                                 ->limit(1)
                                 ->get('attendance')
                                 ->row();

            // Get raw input notes
            $input_notes = trim((string)$this->input->post('notes'));

            // Prepare data array
            $data = [
                'user_id' => $user_id,
                'attachment_path' => $attachment_path,
                'ip_address' => $this->input->ip_address(),
                $col_date => $today
            ];

            // Get Employee Shift
            $this->load->model('Shift_model');
            $this->load->model('Employee_model');
            $employee = $this->Employee_model->get_by_user_id($user_id);
            $shift_id = ($employee && isset($employee->shift_id)) ? $employee->shift_id : 1; // Default to General Shift
            $shift = $this->Shift_model->get($shift_id);
            
            if ($shift) {
                $data['shift_id'] = $shift->id;
            }
            
            $getLocationName = function ($lat, $lng, $postName) {
                $locFromPost = trim((string) $postName);
                if ($locFromPost !== '') {
                    return $locFromPost;
                }
                if ($lat !== null && $lng !== null && $lat !== '' && $lng !== '') {
                    return attendance_geo_reverse_geocode($lat, $lng);
                }

                return null;
            };

            attendance_punch_merge_geo_fields(
                $this->attendance_has_column_fn(),
                $data,
                $lat,
                $lng,
                $location_name,
                $action,
                $getLocationName
            );

            if ($existing) {
                // Update existing record
                $times = attendance_punch_read_existing_times(
                    $existing,
                    $col_in,
                    $col_out,
                    $hasPunchIn,
                    $hasCheckIn,
                    $hasPunchOut,
                    $hasCheckOut
                );
                $cin = $times['cin'];
                $cout = $times['cout'];

                if ($action === 'in') {
                    if (empty($cin)) {
                        // First check-in of the day
                        $updates = [];
                        attendance_punch_apply_check_in_columns(
                            $getColType,
                            $updates,
                            $nowDateTime,
                            $nowTime,
                            $hasPunchIn,
                            $hasCheckIn,
                            $col_in
                        );

                        $checkInNotes = attendance_punch_check_in_notes($input_notes);
                        if ($checkInNotes !== null) {
                            $updates['notes'] = $checkInNotes;
                        }

                        $checkInStatus = attendance_punch_shift_check_in_status($shift, $nowDateTime, $today);
                        if ($checkInStatus !== null) {
                            $updates['status'] = $checkInStatus;
                        }

                        if (array_key_exists('attachment_path', $data) && $data['attachment_path']) {
                            $updates['attachment_path'] = $data['attachment_path'];
                        }
                        attendance_punch_copy_keyed_fields(
                            $updates,
                            $data,
                            array_merge($geoGroups['shared'], $geoGroups['in'])
                        );
                        $this->db->where('id', (int)$existing->id)->update('attendance', $updates);
                        $this->maybe_send_attendance_email($user_id, 'in', $nowDateTime);
                        $checkInStatus = isset($updates['status']) ? (string) $updates['status'] : 'present';
                        $this->rewards_after_checkin($user_id, (int) $existing->id, $checkInStatus, $today);
                        $success_msg = get_notification_message('attendance', 'create', 'success');
                        $this->session->set_flashdata('success', $success_msg);
                    } else {
                        $this->session->set_flashdata('error', 'You have already checked in today. Please check out first.');
                    }
                } else { // action out
                    if (!empty($cin)) {
                        if (empty($cout)) {
                            // Check-out logic with time validation
                            $proposedOut = attendance_punch_proposed_checkout_time(
                                $getColType,
                                $nowDateTime,
                                $nowTime,
                                $hasPunchOut,
                                $hasCheckOut,
                                $col_out
                            );

                            // Validate checkout time is after check-in
                            if (attendance_punch_is_valid_checkout_time($cin, $proposedOut, $outType)) {
                                $updates = [];
                                attendance_punch_apply_check_out_columns(
                                    $getColType,
                                    $updates,
                                    $nowDateTime,
                                    $nowTime,
                                    $hasPunchOut,
                                    $hasCheckOut,
                                    $col_out
                                );

                                $checkOutNotes = attendance_punch_check_out_notes(
                                    isset($existing->notes) ? $existing->notes : '',
                                    $input_notes
                                );
                                if ($checkOutNotes !== null) {
                                    $updates['notes'] = $checkOutNotes;
                                }

                                if (array_key_exists('attachment_path', $data) && $data['attachment_path']) {
                                    $updates['attachment_path'] = $data['attachment_path'];
                                }
                                attendance_punch_copy_keyed_fields($updates, $data, $geoGroups['out']);

                                $currentStatus = isset($existing->status) ? $existing->status : 'present';
                                attendance_punch_apply_early_leave_status(
                                    $updates,
                                    $shift,
                                    $proposedOut,
                                    $today,
                                    $currentStatus
                                );
                                
                                $this->db->where('id', (int)$existing->id)->update('attendance', $updates);
                                $this->maybe_send_attendance_email($user_id, 'out', $nowDateTime);
                                $this->load->helper(array('notification', 'rewards'));
                                reward_engine_dispatch('attendance_checkout', array(
                                    'user_id' => $user_id,
                                    'source_module' => 'attendance',
                                    'source_record_id' => (int) $existing->id,
                                    'reference_label' => 'Check-out',
                                    'payload' => array(),
                                ));
                                $success_msg = get_notification_message('attendance', 'create', 'success');
                                $this->session->set_flashdata('success', $success_msg);
                            } else {
                                $this->session->set_flashdata('error', 'Checkout time cannot be before check-in time or on the same day.');
                            }
                        } else {
                            $this->session->set_flashdata('error', 'You have already checked out today.');
                        }
                    } else {
                        $this->session->set_flashdata('error', 'You must check in before checking out.');
                    }
                }
            } else {
                if ($action === 'out') {
                    $this->session->set_flashdata('error', 'You must check in before checking out.');
                } else {
                    // First check-in of the day
                    attendance_punch_apply_check_in_columns(
                        $getColType,
                        $data,
                        $nowDateTime,
                        $nowTime,
                        $hasPunchIn,
                        $hasCheckIn,
                        $col_in
                    );

                    $checkInNotes = attendance_punch_check_in_notes($input_notes);
                    if ($checkInNotes !== null) {
                        $data['notes'] = $checkInNotes;
                    }

                    $checkInStatus = attendance_punch_shift_check_in_status($shift, $nowDateTime, $today);
                    if ($checkInStatus !== null) {
                        $data['status'] = $checkInStatus;
                    }

                    // Populate human-readable location name if schema and coordinates are available
                    if ($this->attendance_field_exists('location_name')) {
                        $locFromPost = trim((string)$this->input->post('location_name'));
                        if ($locFromPost !== '') {
                            $data['location_name'] = $locFromPost;
                        } elseif ($lat !== null && $lng !== null && $lat !== '' && $lng !== '') {
                            $locName = attendance_geo_reverse_geocode($lat, $lng);
                            if ($locName) { $data['location_name'] = $locName; }
                        }
                    }
                    // Double-check for existing record to prevent race condition duplicates
                    $existing_final = $this->db->where('user_id', $user_id)
                                               ->where($col_date, $today)
                                               ->limit(1)
                                               ->get('attendance')
                                               ->row();
                    
                    if ($existing_final) {
                        // Record exists (race condition), update instead of insert
                        $updates = attendance_punch_build_race_check_in_updates(
                            $getColType,
                            $this->attendance_has_column_fn(),
                            $data,
                            $nowDateTime,
                            $nowTime,
                            $hasPunchIn,
                            $hasCheckIn,
                            $col_in,
                            $input_notes,
                            $geoGroups
                        );
                        $this->db->where('id', (int)$existing_final->id)->update('attendance', $updates);
                        $this->maybe_send_attendance_email($user_id, 'in', $nowDateTime);
                        $checkInStatus = isset($updates['status']) ? (string) $updates['status'] : (isset($data['status']) ? (string) $data['status'] : 'present');
                        $this->rewards_after_checkin($user_id, (int) $existing_final->id, $checkInStatus, $today);
                        $this->load->helper('notification');
                        $success_msg = get_notification_message('attendance', 'create', 'success');
                        $this->session->set_flashdata('success', $success_msg);
                    } else {
                        // No existing record, safe to insert
                        try {
                            $this->db->insert('attendance', $data);
                            $attId = (int) $this->db->insert_id();
                            $this->maybe_send_attendance_email($user_id, 'in', $nowDateTime);
                            $checkInStatus = isset($data['status']) ? (string) $data['status'] : 'present';
                            $this->rewards_after_checkin($user_id, $attId, $checkInStatus, $today);
                        $success_msg = get_notification_message('attendance', 'create', 'success');
                        $this->session->set_flashdata('success', $success_msg);
                        } catch (Exception $e) {
                            // Handle duplicate key error (race condition)
                            if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'uq_attendance') !== false || strpos($e->getMessage(), '1062') !== false) {
                                // Record was inserted by another request, update it
                                $existing_after = $this->db->where('user_id', $user_id)
                                                           ->where($col_date, $today)
                                                           ->limit(1)
                                                           ->get('attendance')
                                                           ->row();
                                
                                if ($existing_after) {
                                    $updates = attendance_punch_build_race_check_in_updates(
                                        $getColType,
                                        $this->attendance_has_column_fn(),
                                        $data,
                                        $nowDateTime,
                                        $nowTime,
                                        $hasPunchIn,
                                        $hasCheckIn,
                                        $col_in,
                                        $input_notes,
                                        $geoGroups
                                    );
                                    $this->db->where('id', (int)$existing_after->id)->update('attendance', $updates);
                                    $this->maybe_send_attendance_email($user_id, 'in', $nowDateTime);
                                    $checkInStatus = isset($updates['status']) ? (string) $updates['status'] : (isset($data['status']) ? (string) $data['status'] : 'present');
                                    $this->rewards_after_checkin($user_id, (int) $existing_after->id, $checkInStatus, $today);
                                    $this->load->helper('notification');
                        $success_msg = get_notification_message('attendance', 'create', 'success');
                        $this->session->set_flashdata('success', $success_msg);
                                } else {
                                    $this->session->set_flashdata('error', 'Failed to save attendance: Record conflict');
                                }
                            } else {
                                $this->session->set_flashdata('error', 'Failed to save attendance: ' . $e->getMessage());
                            }
                        }
                    }
                }
            }
            redirect('attendance');
            return;
        }
        
        // Check existing attendance status for today
        $this->load->helper('date');
        $user_id = (int)$this->session->userdata('user_id');
        $user_timezone = get_user_timezone($user_id);
        $today = get_current_datetime($user_timezone, 'Y-m-d');
        $todayStatus = attendance_punch_today_status(
            $this->db,
            $this->attendance_has_column_fn(),
            $user_id,
            $today
        );
        $existing = $todayStatus['existing'];
        $attendance_status = array(
            'has_checkin'   => $todayStatus['has_checkin'],
            'has_checkout'  => $todayStatus['has_checkout'],
            'checkin_time'  => $todayStatus['checkin_time'],
            'checkout_time' => $todayStatus['checkout_time'],
        );
        
        // Get all attendance-related settings
        $auto_capture_setting = $this->settings->get_setting('attendance_auto_capture', 'yes');
        $auto_capture_enabled = ($auto_capture_setting === 'yes' || $auto_capture_setting === '1' || $auto_capture_setting === 1 || $auto_capture_setting === true);
        
        $face_verification_setting = $this->settings->get_setting('attendance_face_verification_required', 'yes');
        $face_verification_enabled = attendance_punch_setting_is_enabled($face_verification_setting);
        
        $has_registered_face = false;
        if ($face_verification_enabled) {
            $stored_face = $this->faces->get_by_user($user_id);
            if ($stored_face && !empty($stored_face->descriptor)) {
                $has_registered_face = true;
            }
        } else {
            $has_registered_face = true;
        }
        
        $auto_submit_setting = $this->settings->get_setting('attendance_auto_submit', 'no');
        $auto_submit_enabled = ($auto_submit_setting === 'yes' || $auto_submit_setting === '1' || $auto_submit_setting === 1 || $auto_submit_setting === true);
        
        // Get office hours and grace period
        // Get office hours and grace period from Settings first (default)
        $office_start_time = $this->settings->get_setting('attendance_start_time', '09:30');
        $office_end_time = $this->settings->get_setting('attendance_end_time', '18:30');
        $grace_minutes = (int)$this->settings->get_setting('attendance_grace_minutes', 15);
        $standard_working_hours = (float)$this->settings->get_setting('attendance_standard_working_hours', $this->settings->get_setting('standard_working_hours', 8));
        
        // Override with Employee Shift if available
        $this->load->model('Employee_model');
        $this->load->model('Shift_model');
        $employee = $this->Employee_model->get_by_user_id($user_id);
        if ($employee && isset($employee->shift_id)) {
            $shift = $this->Shift_model->get($employee->shift_id);
            if ($shift) {
                // Use Shift Timings
                $office_start_time = date('H:i', strtotime($shift->start_time));
                $office_end_time = date('H:i', strtotime($shift->end_time));
                $grace_minutes = (int)$shift->late_grace_period;
                // Calculate standard hours from shift duration
                $start_ts = strtotime($shift->start_time);
                $end_ts = strtotime($shift->end_time);
                $diff = $end_ts - $start_ts;
                if ($diff > 0) {
                    $standard_working_hours = round($diff / 3600, 1);
                }
            }
        }
        
        // Get weekend days
        $weekends_str = $this->settings->get_setting('attendance_weekends', '0,6');
        $weekend_days = !empty($weekends_str) ? explode(',', $weekends_str) : ['0', '6'];
        $weekend_days = array_map('trim', $weekend_days);
        
        // Check if today is a weekend
        $current_day_of_week = (int)date('w', strtotime($today)); // 0 = Sunday, 6 = Saturday
        $is_weekend = in_array((string)$current_day_of_week, $weekend_days, true);

        // Check if today is a holiday (using holidays table)
        $is_holiday = false;
        $holiday_name = '';
        if ($this->db->table_exists('holidays')) {
            $holiday_row = $this->db->select('name, status')
                                    ->from('holidays')
                                    ->where('holiday_date', $today)
                                    ->limit(1)
                                    ->get()
                                    ->row();
            if ($holiday_row && isset($holiday_row->status) && $holiday_row->status === 'active') {
                $is_holiday = true;
                $holiday_name = isset($holiday_row->name) ? (string)$holiday_row->name : '';
            }
        }
        
        // Get location settings
        $location_strict = $this->settings->get_setting('system_enable_location_strict', 'no');
        $location_strict_enabled = ($location_strict === 'yes' || $location_strict === '1' || $location_strict === 1 || $location_strict === true);
        $office_latitude = $this->settings->get_setting('system_office_latitude', '');
        $office_longitude = $this->settings->get_setting('system_office_longitude', '');
        $attendance_radius = (float)$this->settings->get_setting('system_attendance_radius_meters', 100);
        
        // Get late mark notification setting
        $late_mark_notification = $this->settings->get_setting('attendance_late_mark_notification', 'no');
        $late_mark_enabled = ($late_mark_notification === 'yes' || $late_mark_notification === '1' || $late_mark_notification === 1 || $late_mark_notification === true);
        
        // Calculate expected check-in time (start time + grace period)
        $expected_checkin_time = '';
        if (!empty($office_start_time)) {
            $start_timestamp = strtotime($today . ' ' . $office_start_time . ':00');
            if ($start_timestamp !== false) {
                $expected_timestamp = $start_timestamp + ($grace_minutes * 60);
                $expected_checkin_time = date('H:i:s', $expected_timestamp);
            }
        }
        
        $this->load->view('attendance/create', [
            'attendance_status' => $attendance_status,
            'auto_capture_enabled' => $auto_capture_enabled,
            'face_verification_enabled' => $face_verification_enabled,
            'has_registered_face' => $has_registered_face,
            'auto_submit_enabled' => $auto_submit_enabled,
            'office_start_time' => $office_start_time,
            'office_end_time' => $office_end_time,
            'grace_minutes' => $grace_minutes,
            'standard_working_hours' => $standard_working_hours,
            'is_weekend' => $is_weekend,
            'is_holiday' => $is_holiday,
            'holiday_name' => $holiday_name,
            'weekend_days' => $weekend_days,
            'location_strict_enabled' => $location_strict_enabled,
            'office_latitude' => $office_latitude,
            'office_longitude' => $office_longitude,
            'attendance_radius' => $attendance_radius,
            'late_mark_enabled' => $late_mark_enabled,
            'expected_checkin_time' => $expected_checkin_time,
            'today' => $today
        ]);
    }

    /**
     * Award check-in points and scan for prior missed checkouts.
     */
    private function rewards_after_checkin($user_id, $attendance_id, $status, $today)
    {
        $this->load->helper(array('rewards', 'rewards_automation'));
        reward_engine_dispatch('attendance_checkin', array(
            'user_id' => (int) $user_id,
            'source_module' => 'attendance',
            'source_record_id' => (int) $attendance_id,
            'reference_label' => 'Check-in',
            'payload' => array('status' => (string) $status),
        ));
        rewards_automation_after_checkin($this->db, (int) $user_id, $today);
    }

    private function maybe_send_attendance_email($user_id, $action, $dateTime){
        $email_key = $user_id . '_' . $action . '_' . date('Y-m-d H:i:s', strtotime($dateTime));
        if (isset(self::$email_sent_tracker[$email_key])) {
            return;
        }

        $user = attendance_notify_load_user($this->db, $user_id);
        if (!attendance_notify_user_wants_email($this->db, $user)) {
            return;
        }

        $late_mark_enabled = ($this->settings->get_setting('attendance_late_mark_notification', 'no') === 'yes');

        $this->load->helper('email');
        configure_email_from_settings();
        $fromAddr = get_system_from_email();
        $fromName = get_company_name();

        $isOut = ($action === 'out');
        $user_name = !empty($user->name) ? $user->name : $user->email;

        $late_info = null;
        $is_late = false;
        if (!$isOut && $late_mark_enabled && $action === 'in') {
            $late_info = attendance_notify_calculate_late_time($this->settings, $dateTime);
            if ($late_info && !empty($late_info['is_late'])) {
                $is_late = true;
            }
        }

        if (!$isOut && $action === 'in' && $is_late) {
            $mail = attendance_notify_late_employee_email($user_name, $dateTime, $late_info);
            attendance_notify_send_email(
                $this->email,
                $fromAddr,
                $fromName,
                $user->email,
                $mail['subject'],
                $mail['body']
            );
            self::$email_sent_tracker[$email_key] = true;
            $this->send_late_mark_to_managers($user_id, $user_name, $dateTime, $late_info);
            return;
        }

        $mail = attendance_notify_regular_punch_email($user_name, $dateTime, $isOut, $late_info, $late_mark_enabled);
        attendance_notify_send_email(
            $this->email,
            $fromAddr,
            $fromName,
            $user->email,
            $mail['subject'],
            $mail['body']
        );
        self::$email_sent_tracker[$email_key] = true;
    }

    private function calculate_late_time($checkinDateTime){
        return attendance_notify_calculate_late_time($this->settings, $checkinDateTime);
    }

    private function send_late_mark_to_managers($user_id, $user_name, $checkin_time, $late_info){
        $recipients = attendance_notify_late_manager_recipients($this->db, $this->settings, $user_id);
        if (empty($recipients)) {
            return;
        }

        $this->load->helper('email');
        configure_email_from_settings();
        $fromAddr = get_system_from_email();
        $fromName = get_company_name();
        $mail = attendance_notify_late_manager_email($user_name, $checkin_time, $late_info);

        $primary = $recipients[0];
        $cc = array();
        for ($i = 1, $n = count($recipients); $i < $n; $i++) {
            $cc[] = $recipients[$i]['email'];
        }

        try {
            attendance_notify_send_email(
                $this->email,
                $fromAddr,
                $fromName,
                $primary['email'],
                $mail['subject'],
                $mail['body'],
                $cc
            );
        } catch (Exception $e) {
            error_log('Late mark notification email failed: ' . $e->getMessage());
        }
    }

    private function verify_face_descriptor($stored_json, $current_json){
        return attendance_punch_face_distance($stored_json, $current_json);
    }

    // GET/POST /attendance/{id}/edit
    public function edit($id)
    {
        $id = (int)$id;
        if ($id <= 0) { show_404(); }

        $att = $this->db->where('id', $id)->limit(1)->get('attendance')->row();
        if (!$att) { show_404(); }

        // Ownership: only Admin/HR or owner can edit
        $role_id = (int)$this->session->userdata('role_id');
        $user_id = (int)$this->session->userdata('user_id');
        
        if ((int)$att->user_id !== $user_id) {
            require_module_access(['attendance_edit', 'attendance'], true);
        }
        if ($this->input->method() === 'post') {
            $face_check = attendance_punch_verify_face_for_edit(
                $this->faces,
                $user_id,
                (string) $this->input->post('face_required'),
                (string) $this->input->post('face_descriptor')
            );
            if (!$face_check['ok']) {
                $this->session->set_flashdata('error', $face_check['error']);
                redirect('attendance/'.$id.'/edit');
                return;
            }

            $hasColumn = $this->attendance_has_column_fn();
            $data = array();
            if ($this->attendance_field_exists('notes')) {
                $data['notes'] = trim($this->input->post('notes') ?: '');
            }
            $data = array_merge(
                $data,
                attendance_punch_merge_edit_geo_fields(
                    $hasColumn,
                    $this->input->post('lat'),
                    $this->input->post('lng'),
                    'attendance_geo_reverse_geocode'
                )
            );
            // Optional new attachment
            if ($this->attendance_field_exists('attachment_path') && !empty($_FILES['attachment']['name'])) {
                $upload_path = FCPATH.'uploads/attendance/';
                if (!is_dir($upload_path)) { @mkdir($upload_path, 0755, true); }
                $config = [
                    'upload_path' => $upload_path,
                    'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx',
                    'max_size' => 4096,
                    'encrypt_name' => true,
                ];
                $this->upload->initialize($config);
                if ($this->upload->do_upload('attachment')) {
                    $up = $this->upload->data();
                    $data['attachment_path'] = 'uploads/attendance/'.$up['file_name'];
                } else {
                    $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
                    redirect('attendance/'.$id.'/edit');
                    return;
                }
            }
            if (!empty($data)) {
                $this->db->where('id', (int)$id)->update('attendance', $data);
            }
            $this->load->helper('notification');
            $success_msg = get_notification_message('attendance', 'update', 'success');
            $this->session->set_flashdata('success', $success_msg);
            redirect('attendance');
            return;
        }
        $this->load->view('attendance/edit', ['att' => $att]);
    }

    // POST /attendance/{id}/delete
    public function delete($id)
    {
        // Destructive actions must be POST only
        if ($this->input->method() !== 'post') {
            show_error('Method Not Allowed', 405);
        }

        // Ownership: only Admin/HR or owner can delete
        $row = $this->db->where('id', (int)$id)->get('attendance')->row();
        if (!$row) { show_404(); }
        $role_id = (int)$this->session->userdata('role_id');
        $user_id = (int)$this->session->userdata('user_id');
        
        if ((int)$row->user_id !== $user_id) {
            require_module_access(['attendance_delete', 'attendance'], true);
        }
        $this->db->where('id', (int)$id)->delete('attendance');
        $this->load->helper('notification');
        $success_msg = get_notification_message('attendance', 'delete', 'success');
        $this->session->set_flashdata('success', $success_msg);
        redirect('attendance');
    }

    // Export selected attendance records to Excel
    public function export() {
        $format = $this->input->post('format') ?: $this->input->get('format');
        $userIdsStr = $this->input->post('user_ids') ?: $this->input->get('user_ids');
        
        if (!in_array($format, ['excel', 'pdf'])) {
            $this->session->set_flashdata('error', 'Invalid export format.');
            redirect('attendance');
            return;
        }
        
        if (empty($userIdsStr)) {
            $this->session->set_flashdata('error', 'No employees selected for export.');
            redirect('attendance');
            return;
        }
        
        $userIds = array_filter(array_map('intval', explode(',', $userIdsStr)));
        if (empty($userIds)) {
            $this->session->set_flashdata('error', 'Invalid employee selection.');
            redirect('attendance');
            return;
        }
        
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');

        $records = attendance_export_fetch_summary($this->db, $userIds, $user_id, $role_id);

        if (empty($records)) {
            $this->session->set_flashdata('error', 'No attendance records found for selected employees.');
            redirect('attendance');
            return;
        }

        if ($format === 'excel') {
            attendance_export_send_csv($records);
        } else {
            attendance_export_send_pdf($records);
        }
    }
}
