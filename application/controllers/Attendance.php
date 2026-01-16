<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance extends CI_Controller {
    private static $email_sent_tracker = []; // Track sent emails to prevent duplicates
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission','group_filter','company']);
        $this->load->library(['session','upload','email','pagination']);
        $this->load->model('Attendance_model');
        $this->load->model('Face_model', 'faces');
        $this->load->model('Setting_model', 'settings');
    }

    public function index() {
        // Pagination configuration
        $per_page = 10;
        $page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $total_records = 0;
        
        // Get current user info for role-based access
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        $isAdminGroup = (function_exists('is_admin_group') && is_admin_group());
        $canViewAll = $isAdminGroup || in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true);
        $canAddAttendance = true; // All logged-in users can add their own attendance
        
        // Get group-based filters
        $filters = get_user_group_filter($user_id, $role_id);
        
        // Count total distinct users for pagination
        $this->db->select('COUNT(DISTINCT a.user_id) as total');
        $this->db->from('attendance a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        $this->db->join('employees e', 'e.user_id = a.user_id', 'left');
        
        // Apply group-based filtering
        if (!$canViewAll) {
            if (can_view_group_data($role_id)) {
                // Managers can see department attendance
                if (!empty($filters['attendance'])) {
                    apply_group_filter_to_query($this->db, 'attendance', $filters);
                }
            } else {
                // Regular users see only their own attendance
                $this->db->where('a.user_id', $user_id);
            }
        }
        
        $total_query = $this->db->get()->row();
        $total_records = $total_query->total;
        
        // Fetch distinct users with their latest attendance and count
        $this->db->select('a.user_id, u.email, e.first_name, e.last_name, COUNT(*) as attendance_count, MAX(a.att_date) as last_attendance_date');
        $this->db->from('attendance a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        $employee_exists = $this->db->table_exists('employees');
        if ($employee_exists) {
            $this->db->join('employees e', 'e.user_id = a.user_id', 'left');
        }
        
        // Apply group-based filtering
        if (!$canViewAll) {
            if (can_view_group_data($role_id)) {
                // Managers can see department attendance
                if (!empty($filters['attendance'])) {
                    apply_group_filter_to_query($this->db, 'attendance', $filters);
                }
            } else {
                // Regular users see only their own attendance
                $this->db->where('a.user_id', $user_id);
            }
        }
        
        $this->db->group_by('a.user_id, u.email, e.first_name, e.last_name');
        
        // Order by employee name (first_name, last_name) in ascending order, fallback to email
        if ($employee_exists) {
            $this->db->order_by('e.first_name', 'ASC');
            $this->db->order_by('e.last_name', 'ASC');
            $this->db->order_by('u.email', 'ASC');
        } else {
            $this->db->order_by('u.email', 'ASC');
        }
        
        $records = $this->db->limit($per_page, $page)
                           ->get()
                           ->result();
        
        // Pagination config
        $base_url = site_url('attendance/index');
        $config['base_url'] = $base_url;
        $config['total_rows'] = $total_records;
        $config['per_page'] = $per_page;
        $config['uri_segment'] = 3;
        $config['num_links'] = 5;
        $config['full_tag_open'] = '<nav class="d-flex justify-content-center mt-3"><ul class="pagination">';
        $config['full_tag_close'] = '</ul></nav>';
        $config['first_link'] = '&laquo; First';
        $config['first_tag_open'] = '<li class="page-item">';
        $config['first_tag_close'] = '</li>';
        $config['last_link'] = 'Last &raquo;';
        $config['last_tag_open'] = '<li class="page-item">';
        $config['last_tag_close'] = '</li>';
        $config['next_link'] = 'Next &rarr;';
        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_tag_close'] = '</li>';
        $config['prev_link'] = '&larr; Prev';
        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><span class="page-link">';
        $config['cur_tag_close'] = '</span></li>';
        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $config['attributes'] = ['class' => 'page-link'];
        
        $this->pagination->initialize($config);
        $pagination_links = $this->pagination->create_links();
        
        // Check edit and delete permissions
        $this->load->helper('permission');
        $canEditAttendance = function_exists('has_module_access') && has_module_access('attendance_edit');
        $canDeleteAttendance = function_exists('has_module_access') && has_module_access('attendance_delete');
        
        $this->load->view('attendance/index', [
            'records' => $records,
            'employee_exists' => $employee_exists,
            'pagination_links' => $pagination_links,
            'total_records' => $total_records,
            'current_page' => $page + 1,
            'per_page' => $per_page,
            'can_add_attendance' => $canAddAttendance,
            'can_view_all' => $canViewAll,
            'can_edit_attendance' => $canEditAttendance,
            'can_delete_attendance' => $canDeleteAttendance,
            'current_user_id' => $user_id,
            'is_admin_group' => $isAdminGroup,
            'current_role_id' => $role_id
        ]);
    }

    // Get user's monthly attendance data for popup
    public function get_user_monthly_attendance() {
        $user_id = $this->input->post('user_id');
        $filter_type = $this->input->post('filter_type'); // 'date', 'month', 'year'
        $filter_value = $this->input->post('filter_value');
        $page = (int)$this->input->post('page') ?: 1;
        $per_page = 10; // Records per page in popup
        
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            return;
        }
        
        // Schema-aware column names
        $col_date = 'att_date';
        if (!$this->db->field_exists('att_date', 'attendance')) {
            $date_columns = ['date', 'attendance_date', 'created_at'];
            foreach ($date_columns as $col) {
                if ($this->db->field_exists($col, 'attendance')) {
                    $col_date = $col;
                    break;
                }
            }
        }
        
        $col_in = 'punch_in';
        $col_out = 'punch_out';
        if (!$this->db->field_exists($col_in, 'attendance')) $col_in = 'check_in';
        if (!$this->db->field_exists($col_out, 'attendance')) $col_out = 'check_out';
        
        // Get total records count
        $this->db->from('attendance a');
        $this->db->where('a.user_id', (int)$user_id);
        
        // Apply filters
        switch ($filter_type) {
            case 'date':
                $this->db->where('a.' . $col_date, $filter_value);
                break;
            case 'month':
                $this->db->where('DATE_FORMAT(a.' . $col_date . ', "%Y-%m") =', $filter_value);
                break;
            case 'year':
                $this->db->where('YEAR(a.' . $col_date . ')', $filter_value);
                break;
            default:
                // Default to current month
                $this->db->where('DATE_FORMAT(a.' . $col_date . ', "%Y-%m") =', date('Y-m'));
        }
        
        $total_records = $this->db->count_all_results();
        
        // Calculate pagination
        $total_pages = ceil($total_records / $per_page);
        $offset = ($page - 1) * $per_page;
        
        // Fetch paginated records
        $this->db->select('a.*, u.email');
        $this->db->from('attendance a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        $this->db->where('a.user_id', (int)$user_id);
        
        // Apply filters again
        switch ($filter_type) {
            case 'date':
                $this->db->where('a.' . $col_date, $filter_value);
                break;
            case 'month':
                $this->db->where('DATE_FORMAT(a.' . $col_date . ', "%Y-%m") =', $filter_value);
                break;
            case 'year':
                $this->db->where('YEAR(a.' . $col_date . ')', $filter_value);
                break;
            default:
                $this->db->where('DATE_FORMAT(a.' . $col_date . ', "%Y-%m") =', date('Y-m'));
        }
        
        $records = $this->db->order_by('a.' . $col_date . ' DESC')
                           ->limit($per_page, $offset)
                           ->get()
                           ->result();
        
        $attendance_data = [];
        foreach ($records as $r) {
            $cin = isset($r->punch_in) ? $r->punch_in : (isset($r->check_in) ? $r->check_in : '');
            $cout = isset($r->punch_out) ? $r->punch_out : (isset($r->check_out) ? $r->check_out : '');
            
            if ($cin === '00:00:00' || $cin === '0000-00-00 00:00:00') { $cin = ''; }
            if ($cout === '00:00:00' || $cout === '0000-00-00 00:00:00') { $cout = ''; }
            
            $cin_disp = $cin;
            $cout_disp = $cout;
            if ($cin_disp && strpos($cin_disp, ' ') !== false) { $cin_disp = trim(explode(' ', $cin_disp)[1]); }
            if ($cout_disp && strpos($cout_disp, ' ') !== false) { $cout_disp = trim(explode(' ', $cout_disp)[1]); }
            
            // Determine status
            $status = 'incomplete';
            if ($cin && $cout) {
                $status = 'present';
            } elseif ($cin && !$cout) {
                $status = 'incomplete';
            } else {
                $status = 'absent';
            }
            
            // Get check-in location
            $checkin_location = '';
            if (isset($r->checkin_location_name) && !empty($r->checkin_location_name)) {
                $checkin_location = $r->checkin_location_name;
            } elseif (isset($r->checkin_lat) && isset($r->checkin_lng) && !empty($r->checkin_lat) && !empty($r->checkin_lng)) {
                $checkin_location = $r->checkin_lat . ', ' . $r->checkin_lng;
            } elseif (isset($r->location_name) && !empty($r->location_name) && $cin) {
                $checkin_location = $r->location_name; // Fallback to old location_name for check-in
            }
            
            // Get check-out location
            $checkout_location = '';
            if (isset($r->checkout_location_name) && !empty($r->checkout_location_name)) {
                $checkout_location = $r->checkout_location_name;
            } elseif (isset($r->checkout_lat) && isset($r->checkout_lng) && !empty($r->checkout_lat) && !empty($r->checkout_lng)) {
                $checkout_location = $r->checkout_lat . ', ' . $r->checkout_lng;
            } elseif (isset($r->location_name) && !empty($r->location_name) && $cout) {
                $checkout_location = $r->location_name; // Fallback to old location_name for check-out
            }
            
            // Get current user info for permission checks
            $current_user_id = (int)$this->session->userdata('user_id');
            $current_role_id = (int)$this->session->userdata('role_id');
            $is_admin = (function_exists('is_admin_group') && is_admin_group()) || in_array($current_role_id, [ROLE_ADMIN, ROLE_MANAGER], true);
            $can_edit = function_exists('has_module_access') && has_module_access('attendance_edit');
            $can_delete = function_exists('has_module_access') && has_module_access('attendance_delete');
            
            // Check ownership - users can only edit/delete their own records unless admin
            $record_user_id = isset($r->user_id) ? (int)$r->user_id : 0;
            $can_edit_this = $can_edit && ($is_admin || $record_user_id === $current_user_id);
            $can_delete_this = $can_delete && ($is_admin || $record_user_id === $current_user_id);
            
            $attendance_data[] = [
                'id' => $r->id,
                'user_id' => $record_user_id,
                'date' => isset($r->$col_date) ? $r->$col_date : '',
                'check_in' => $cin_disp,
                'check_out' => $cout_disp,
                'status' => $status,
                'notes' => isset($r->notes) ? $r->notes : '',
                'location' => isset($r->location_name) ? $r->location_name : '', // Backward compatibility
                'checkin_location' => $checkin_location,
                'checkout_location' => $checkout_location,
                'can_edit' => $can_edit_this,
                'can_delete' => $can_delete_this
            ];
        }
        
        echo json_encode([
            'success' => true, 
            'data' => $attendance_data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_records' => $total_records,
                'per_page' => $per_page,
                'has_prev' => $page > 1,
                'has_next' => $page < $total_pages
            ]
        ]);
    }

    // Bulk operations for attendance
    public function bulk_operations() {
        // Check bulk operations permission specifically
        if (!function_exists('has_module_access') || !has_module_access('attendance_bulk')) {
            show_error('You do not have permission to perform bulk operations on attendance.', 403);
        }
        
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        
        // Check permissions - only admins can perform bulk operations
        $canManageAll = (function_exists('is_admin_group') && is_admin_group()) || in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true);
        if (!$canManageAll) {
            $this->session->set_flashdata('error', 'You do not have permission to perform bulk operations');
            redirect('attendance');
            return;
        }

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
                        $affected_count = $this->bulk_delete($valid_ids);
                        break;
                    case 'mark_present':
                        $affected_count = $this->bulk_mark_present($valid_ids);
                        break;
                    case 'clear_checkout':
                        $affected_count = $this->bulk_clear_checkout($valid_ids);
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

    private function bulk_delete($ids) {
        $this->db->where_in('id', $ids);
        return $this->db->delete('attendance');
    }

    private function bulk_mark_present($ids) {
        // This could be used to mark records as present if they have check-in but no checkout
        $this->db->where_in('id', $ids);
        $this->db->where('punch_in IS NOT NULL');
        $this->db->where('punch_in !=', '00:00:00');
        $this->db->where('(punch_out IS NULL OR punch_out = "00:00:00")');
        
        // Set a default checkout time (e.g., 6:00 PM)
        $data = ['punch_out' => '18:00:00'];
        $this->db->update('attendance', $data);
        
        return $this->db->affected_rows();
    }

    private function bulk_clear_checkout($ids) {
        // Clear checkout times for selected records
        $this->db->where_in('id', $ids);
        $data = ['punch_out' => null];
        $this->db->update('attendance', $data);
        
        return $this->db->affected_rows();
    }

    // GET/POST /attendance/create
    public function create()
    {
        // Check create permission specifically
        if (!function_exists('has_module_access') || !has_module_access('attendance_add')) {
            show_error('You do not have permission to add attendance.', 403);
        }
        
        if ($this->input->method() === 'post') {
            $user_id = (int)$this->session->userdata('user_id');
            if (!$user_id) { 
                $this->session->set_flashdata('error', 'Please login to mark attendance');
                redirect('login'); 
                return;
            }

            // Enhanced validation
            $action = $this->input->post('action');
            if (!in_array($action, ['in', 'out'], true)) {
                // Check if user already completed attendance to show appropriate message
                $this->load->helper('date');
                $user_timezone = get_user_timezone($user_id);
                $today = get_current_datetime($user_timezone, 'Y-m-d');
                $col_date = 'att_date';
                $col_in = 'punch_in';
                $col_out = 'punch_out';
                if (!$this->db->field_exists($col_date, 'attendance')) $col_date = 'date';
                if (!$this->db->field_exists($col_in, 'attendance')) $col_in = 'check_in';
                if (!$this->db->field_exists($col_out, 'attendance')) $col_out = 'check_out';
                
                $existing_check = $this->db->where('user_id', $user_id)
                                           ->where($col_date, $today)
                                           ->limit(1)
                                           ->get('attendance')
                                           ->row();
                
                if ($existing_check) {
                    $cin = isset($existing_check->$col_in) ? $existing_check->$col_in : '';
                    $cout = isset($existing_check->$col_out) ? $existing_check->$col_out : '';
                    if ($cin === '00:00:00' || $cin === '0000-00-00 00:00:00') { $cin = ''; }
                    if ($cout === '00:00:00' || $cout === '0000-00-00 00:00:00') { $cout = ''; }
                    
                    if (!empty($cin) && !empty($cout)) {
                        $this->session->set_flashdata('error', 'You have already completed attendance for today. Check-in and check-out are already recorded.');
                    } elseif (!empty($cin)) {
                        $this->session->set_flashdata('error', 'You have already checked in today. Please select check-out action.');
                    } else {
                        $this->session->set_flashdata('error', 'Invalid action selected. Please select either check-in or check-out.');
                    }
                } else {
                    $this->session->set_flashdata('error', 'Invalid action selected. Please select either check-in or check-out.');
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

            // Mandatory face verification
            $face_required = (string)$this->input->post('face_required');
            $face_descriptor = (string)$this->input->post('face_descriptor');
            
            // Face verification is mandatory
            if (empty($face_descriptor)) {
                $this->session->set_flashdata('error', 'Face verification is mandatory. Please capture your face before submitting.');
                redirect('attendance/create');
                return;
            }
            
            // Verify face descriptor against stored template for this user
            $stored = $this->faces->get_by_user($user_id);
            if ($stored && !empty($stored->descriptor)) {
                $threshold = 0.6;
                $dist = $this->verify_face_descriptor($face_descriptor, $stored->descriptor);
                if ($dist === null) {
                    $this->session->set_flashdata('error', 'Face verification failed: Invalid face data format. Please try capturing your face again.');
                    redirect('attendance/create');
                    return;
                }
                if ($dist > $threshold) {
                    $this->session->set_flashdata('error', 'Face verification failed: Your face does not match the registered face. Please ensure you are using the same face as registered in your profile.');
                    redirect('attendance/create');
                    return;
                }
            } else {
                $this->session->set_flashdata('error', 'No registered face found for this user. Please register your face in your profile first.');
                redirect('attendance/create');
                return;
            }

            // Location handling - MANDATORY
            $lat = $this->input->post('lat');
            $lng = $this->input->post('lng');
            $location_name = $this->input->post('location_name');
            
            // Validate location is mandatory
            if (empty($lat) || empty($lng)) {
                $this->session->set_flashdata('error', 'Location is mandatory. Please enable location services and allow location access.');
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
            $col_date = 'att_date';
            $col_in = 'punch_in';
            $col_out = 'punch_out';
            if (!$this->db->field_exists($col_date, 'attendance')) $col_date = 'date';
            if (!$this->db->field_exists($col_in, 'attendance')) $col_in = 'check_in';
            if (!$this->db->field_exists($col_out, 'attendance')) $col_out = 'check_out';

            // Check if user already has a record for today (use limit 1 to ensure single record)
            $existing = $this->db->where('user_id', $user_id)
                                 ->where($col_date, $today)
                                 ->limit(1)
                                 ->get('attendance')
                                 ->row();

            // Prepare data array
            $data = [
                'user_id' => $user_id,
                'notes' => $this->input->post('notes'),
                'attachment_path' => $attachment_path,
                'ip_address' => $this->input->ip_address(),
                $col_date => $today  // Add the date field
            ];
            
            // Helper function to get location name
            $self = $this;
            $getLocationName = function($lat, $lng, $postName) use ($self) {
                $locFromPost = trim((string)$postName);
                if ($locFromPost !== '') {
                    return $locFromPost;
                } elseif ($lat !== null && $lng !== null && $lat !== '' && $lng !== '') {
                    return $self->reverse_geocode($lat, $lng);
                }
                return null;
            };
            
            // Add location fields if they exist in schema (for backward compatibility)
            if ($this->db->field_exists('latitude', 'attendance')) $data['latitude'] = $lat;
            if ($this->db->field_exists('longitude', 'attendance')) $data['longitude'] = $lng;
            if ($this->db->field_exists('lat', 'attendance')) $data['lat'] = $lat;
            if ($this->db->field_exists('lng', 'attendance')) $data['lng'] = $lng;
            if ($this->db->field_exists('geo_lat', 'attendance')) $data['geo_lat'] = $lat;
            if ($this->db->field_exists('geo_lng', 'attendance')) $data['geo_lng'] = $lng;
            if ($this->db->field_exists('location_name','attendance')) {
                $locName = $getLocationName($lat, $lng, $location_name);
                if ($locName) { $data['location_name'] = $locName; }
            }
            
            // Save check-in location separately if action is 'in'
            if ($action === 'in') {
                if ($this->db->field_exists('checkin_lat', 'attendance')) $data['checkin_lat'] = $lat;
                if ($this->db->field_exists('checkin_lng', 'attendance')) $data['checkin_lng'] = $lng;
                if ($this->db->field_exists('checkin_location_name', 'attendance')) {
                    $checkinLocName = $getLocationName($lat, $lng, $location_name);
                    if ($checkinLocName) { $data['checkin_location_name'] = $checkinLocName; }
                }
            }
            
            // Save check-out location separately if action is 'out'
            if ($action === 'out') {
                if ($this->db->field_exists('checkout_lat', 'attendance')) $data['checkout_lat'] = $lat;
                if ($this->db->field_exists('checkout_lng', 'attendance')) $data['checkout_lng'] = $lng;
                if ($this->db->field_exists('checkout_location_name', 'attendance')) {
                    $checkoutLocName = $getLocationName($lat, $lng, $location_name);
                    if ($checkoutLocName) { $data['checkout_location_name'] = $checkoutLocName; }
                }
            }

            if ($existing) {
                // Update existing record
                $cin = isset($existing->$col_in) ? $existing->$col_in : '';
                $cout = isset($existing->$col_out) ? $existing->$col_out : '';
                if ($cin === '00:00:00' || $cin === '0000-00-00 00:00:00') { $cin = ''; }
                if ($cout === '00:00:00' || $cout === '0000-00-00 00:00:00') { $cout = ''; }

                if ($action === 'in') {
                    if (empty($cin)) {
                        // First check-in of the day
                        $inType = $this->get_column_type('attendance', $col_in);
                        $updates = [];
                        $updates[$col_in] = (in_array($inType, ['datetime','timestamp'], true)) ? $nowDateTime : $nowTime;
                        if (array_key_exists('notes', $data)) { $updates['notes'] = $data['notes']; }
                        if (array_key_exists('attachment_path', $data) && $data['attachment_path']) { $updates['attachment_path'] = $data['attachment_path']; }
                        // Update location fields (backward compatibility)
                        foreach (['latitude','longitude','lat','lng','geo_lat','geo_lng','location_name'] as $field) {
                            if (array_key_exists($field, $data)) { $updates[$field] = $data[$field]; }
                        }
                        // Update check-in location fields
                        foreach (['checkin_lat','checkin_lng','checkin_location_name'] as $field) {
                            if (array_key_exists($field, $data)) { $updates[$field] = $data[$field]; }
                        }
                        $this->db->where('id', (int)$existing->id)->update('attendance', $updates);
                        $this->maybe_send_attendance_email($user_id, 'in', $nowDateTime);
                        $this->session->set_flashdata('success', 'Checked in successfully');
                    } else {
                        $this->session->set_flashdata('error', 'You have already checked in today. Please check out first.');
                    }
                } else { // action out
                    if (!empty($cin)) {
                        if (empty($cout)) {
                            // Check-out logic with time validation
                            $outType = $this->get_column_type('attendance', $col_out);
                            $proposedOut = (in_array($outType, ['datetime','timestamp'], true)) ? $nowDateTime : $nowTime;
                            

                            // Validate checkout time is after check-in
                            if ($this->is_valid_checkout_time($cin, $proposedOut, $outType)) {
                                $updates = [];
                                $updates[$col_out] = $proposedOut;
                                if (array_key_exists('notes', $data)) { $updates['notes'] = $data['notes']; }
                                if (array_key_exists('attachment_path', $data) && $data['attachment_path']) { $updates['attachment_path'] = $data['attachment_path']; }
                                // Update check-out location fields
                                foreach (['checkout_lat','checkout_lng','checkout_location_name'] as $field) {
                                    if (array_key_exists($field, $data)) { $updates[$field] = $data[$field]; }
                                }
                                $this->db->where('id', (int)$existing->id)->update('attendance', $updates);
                                $this->maybe_send_attendance_email($user_id, 'out', $nowDateTime);
                                $this->session->set_flashdata('success', 'Checked out successfully');
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
                    $inType = $this->get_column_type('attendance', $col_in);
                    $data[$col_in] = (in_array($inType, ['datetime','timestamp'], true)) ? $nowDateTime : $nowTime;
                    // Populate human-readable location name if schema and coordinates are available
                    if ($this->db->field_exists('location_name','attendance')) {
                        $locFromPost = trim((string)$this->input->post('location_name'));
                        if ($locFromPost !== '') {
                            $data['location_name'] = $locFromPost;
                        } elseif ($lat !== null && $lng !== null && $lat !== '' && $lng !== '') {
                            $locName = $this->reverse_geocode($lat, $lng);
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
                        $updates = [];
                        $updates[$col_in] = (in_array($inType, ['datetime','timestamp'], true)) ? $nowDateTime : $nowTime;
                        foreach (['notes', 'attachment_path', 'ip_address'] as $field) {
                            if (isset($data[$field])) $updates[$field] = $data[$field];
                        }
                        foreach (['latitude','longitude','lat','lng','geo_lat','geo_lng','location_name'] as $field) {
                            if (isset($data[$field]) && $this->db->field_exists($field, 'attendance')) {
                                $updates[$field] = $data[$field];
                            }
                        }
                        foreach (['checkin_lat','checkin_lng','checkin_location_name'] as $field) {
                            if (isset($data[$field]) && $this->db->field_exists($field, 'attendance')) {
                                $updates[$field] = $data[$field];
                            }
                        }
                        $this->db->where('id', (int)$existing_final->id)->update('attendance', $updates);
                        $this->maybe_send_attendance_email($user_id, 'in', $nowDateTime);
                        $this->session->set_flashdata('success', 'Checked in successfully');
                    } else {
                        // No existing record, safe to insert
                        try {
                            $this->db->insert('attendance', $data);
                            $this->maybe_send_attendance_email($user_id, 'in', $nowDateTime);
                            $this->session->set_flashdata('success', 'Checked in successfully');
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
                                    $updates = [];
                                    $updates[$col_in] = (in_array($inType, ['datetime','timestamp'], true)) ? $nowDateTime : $nowTime;
                                    foreach (['notes', 'attachment_path', 'ip_address'] as $field) {
                                        if (isset($data[$field])) $updates[$field] = $data[$field];
                                    }
                                    foreach (['latitude','longitude','lat','lng','geo_lat','geo_lng','location_name'] as $field) {
                                        if (isset($data[$field]) && $this->db->field_exists($field, 'attendance')) {
                                            $updates[$field] = $data[$field];
                                        }
                                    }
                                    foreach (['checkin_lat','checkin_lng','checkin_location_name'] as $field) {
                                        if (isset($data[$field]) && $this->db->field_exists($field, 'attendance')) {
                                            $updates[$field] = $data[$field];
                                        }
                                    }
                                    $this->db->where('id', (int)$existing_after->id)->update('attendance', $updates);
                                    $this->maybe_send_attendance_email($user_id, 'in', $nowDateTime);
                                    $this->session->set_flashdata('success', 'Checked in successfully');
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
        $col_date = 'att_date';
        $col_in = 'punch_in';
        $col_out = 'punch_out';
        if (!$this->db->field_exists($col_date, 'attendance')) $col_date = 'date';
        if (!$this->db->field_exists($col_in, 'attendance')) $col_in = 'check_in';
        if (!$this->db->field_exists($col_out, 'attendance')) $col_out = 'check_out';
        
        $existing = null;
        $attendance_status = [
            'has_checkin' => false,
            'has_checkout' => false,
            'checkin_time' => '',
            'checkout_time' => ''
        ];
        
        if ($user_id) {
            $existing = $this->db->where('user_id', $user_id)
                                 ->where($col_date, $today)
                                 ->get('attendance')
                                 ->row();
            
            if ($existing) {
                $cin = isset($existing->$col_in) ? $existing->$col_in : '';
                $cout = isset($existing->$col_out) ? $existing->$col_out : '';
                if ($cin === '00:00:00' || $cin === '0000-00-00 00:00:00') { $cin = ''; }
                if ($cout === '00:00:00' || $cout === '0000-00-00 00:00:00') { $cout = ''; }
                
                $attendance_status['has_checkin'] = !empty($cin);
                $attendance_status['has_checkout'] = !empty($cout);
                $attendance_status['checkin_time'] = $cin;
                $attendance_status['checkout_time'] = $cout;
            }
        }
        
        // Get auto capture setting (default: enabled/yes)
        $auto_capture_setting = $this->settings->get_setting('attendance_auto_capture', 'yes');
        $auto_capture_enabled = ($auto_capture_setting === 'yes' || $auto_capture_setting === '1' || $auto_capture_setting === 1 || $auto_capture_setting === true);
        
        $this->load->view('attendance/create', [
            'attendance_status' => $attendance_status,
            'auto_capture_enabled' => $auto_capture_enabled
        ]);
    }

    private function get_column_type($table, $column){
        try {
            $fields = $this->db->field_data($table);
            foreach ($fields as $f){
                if (isset($f->name) && $f->name === $column){
                    $t = isset($f->type) ? strtolower($f->type) : '';
                    return $t;
                }
            }
        } catch (Exception $e) {}
        return '';
    }

    private function is_empty_time($v){
        if (!isset($v)) return true;
        $s = trim((string)$v);
        if ($s === '' || $s === '0') return true;
        $zeros = ['00:00', '00:00:00', '0000-00-00', '0000-00-00 00:00:00'];
        return in_array($s, $zeros, true);
    }

    private function is_valid_checkout_time($checkIn, $checkOut, $outType){
        if (empty($checkIn) || empty($checkOut)) return false;
        
        // Handle time-only fields
        if (in_array($outType, ['time'], true)) {
            // For time fields, we just check basic validity
            // Since both are same day, check-out should be after check-in
            $checkInTime = strtotime('1970-01-01 ' . $checkIn);
            $checkOutTime = strtotime('1970-01-01 ' . $checkOut);
            
            if ($checkInTime === false || $checkOutTime === false) return false;
            
            // Allow checkout next day (after midnight) but not same day before check-in
            $timeDiff = $checkOutTime - $checkInTime;
            return $timeDiff > 0 || $timeDiff < -12 * 3600; // Allow next day checkout
        }
        
        // Handle datetime fields
        $checkInTime = strtotime($checkIn);
        $checkOutTime = strtotime($checkOut);
        
        if ($checkInTime === false || $checkOutTime === false) return false;
        
        // Checkout must be after check-in
        return $checkOutTime > $checkInTime;
    }

    private function maybe_send_attendance_email($user_id, $action, $dateTime){
        // Prevent duplicate emails in the same request
        $email_key = $user_id . '_' . $action . '_' . date('Y-m-d H:i:s', strtotime($dateTime));
        if (isset(self::$email_sent_tracker[$email_key])) {
            return; // Email already sent for this check-in/check-out
        }
        
        if (!$this->db->table_exists('users')) { return; }

        $select = ['email'];
        if ($this->db->field_exists('notify_attendance','users')){ $select[] = 'notify_attendance'; }
        if ($this->db->field_exists('name','users')){ $select[] = 'name'; }
        $user = $this->db->select(implode(',', $select), false)->from('users')->where('id',(int)$user_id)->get()->row();
        if (!$user || !isset($user->email) || $user->email === '') { return; }

        $notify = 1;
        if ($this->db->field_exists('notify_attendance','users')){
            $raw = isset($user->notify_attendance) ? $user->notify_attendance : 1;
            if (is_numeric($raw)) {
                $notify = ((int)$raw === 1) ? 1 : 0;
            } else if (is_string($raw)) {
                $notify = in_array(strtolower(trim((string)$raw)), ['1','yes','true','enabled'], true) ? 1 : 0;
            }
        }
        if (!$notify) { return; }

        // Check if late mark notification is enabled
        $late_mark_enabled = $this->settings->get_setting('attendance_late_mark_notification', 'no');
        $late_mark_enabled = ($late_mark_enabled === 'yes') ? true : false;
        
        // Initialize email config
        $cfg = array('smtp_timeout'=>10,'mailtype'=>'html','newline'=>"\r\n",'crlf'=>"\r\n",'charset'=>'utf-8');
        $this->email->initialize($cfg);
        $this->email->clear(true);
        $fromAddr = getenv('SMTP_USER');
        if (!$fromAddr || $fromAddr==='') { $fromAddr = 'no-reply@example.com'; }
        $fromName = get_company_name();
        $this->email->from($fromAddr, $fromName);
        
        $isOut = ($action === 'out');
        $user_name = !empty($user->name) ? $user->name : $user->email;
        
        // For check-in, check if late and calculate late time
        $late_info = null;
        $is_late = false;
        
        if (!$isOut && $late_mark_enabled && $action === 'in') {
            $late_info = $this->calculate_late_time($dateTime);
            if ($late_info && isset($late_info['is_late']) && $late_info['is_late'] === true) {
                $is_late = true;
            }
        }
        
        // Send ONLY ONE email - either late mark or regular check-in/check-out
        if (!$isOut && $action === 'in' && $is_late) {
            // LATE CHECK-IN - Send late mark email to employee ONLY
            $subject = 'Late Mark - Attendance Check-in';
            $body = '<html><body>';
            $body .= '<h3 style="color: #dc3545;">Late Mark Notification</h3>';
            $body .= '<p>Hello ' . htmlspecialchars($user_name) . ',</p>';
            $body .= '<p>Your attendance check-in has been recorded at <strong>' . htmlspecialchars($dateTime) . '</strong>.</p>';
            $body .= '<p style="color: #dc3545; font-weight: bold;">You are marked LATE.</p>';
            $body .= '<p><strong>Late Time:</strong></p>';
            $body .= '<ul>';
            $body .= '<li>Hours: ' . $late_info['hours'] . '</li>';
            $body .= '<li>Minutes: ' . $late_info['minutes'] . '</li>';
            $body .= '<li>Seconds: ' . $late_info['seconds'] . '</li>';
            $body .= '</ul>';
            $body .= '<p><strong>Total Late Time:</strong> ' . htmlspecialchars($late_info['formatted']) . '</p>';
            $body .= '<p>Expected start time: ' . htmlspecialchars($late_info['expected_time']) . '</p>';
            $body .= '<p>Thank you.</p>';
            $body .= '</body></html>';
            
            // Send ONLY ONE email to employee
            $this->email->clear(true);
            $this->email->from($fromAddr, $fromName);
            $this->email->to($user->email);
            $this->email->subject($subject);
            $this->email->message($body);
            @$this->email->send();
            
            // Mark as sent to prevent duplicates
            self::$email_sent_tracker[$email_key] = true;
            
            // Send separate email to HR and Manager (NOT to employee - different recipients)
            $this->send_late_mark_to_managers($user_id, $user_name, $dateTime, $late_info);
            return; // EXIT - No other email should be sent
        }
        
        // REGULAR CHECK-IN or CHECK-OUT (NOT LATE) - Send regular email
        $subject = $isOut ? 'Attendance checkout recorded' : 'Attendance check-in recorded';
        $body = '<html><body>';
        $body .= '<h3>Attendance ' . ($isOut ? 'Check-out' : 'Check-in') . ' Recorded</h3>';
        $body .= '<p>Hello ' . htmlspecialchars($user_name) . ',</p>';
        $body .= '<p>Your attendance ' . ($isOut ? 'checkout' : 'check-in') . ' has been recorded at <strong>' . htmlspecialchars($dateTime) . '</strong>.</p>';
        if (!$isOut && $late_mark_enabled && $late_info && isset($late_info['is_late']) && $late_info['is_late'] === false) {
            $body .= '<p style="color: #28a745; font-weight: bold;">You are on time. Good job!</p>';
        }
        $body .= '<p>Thank you.</p>';
        $body .= '</body></html>';
        
        // Send ONLY ONE email to employee (regular check-in/check-out)
        $this->email->clear(true);
        $this->email->from($fromAddr, $fromName);
        $this->email->to($user->email);
        $this->email->subject($subject);
        $this->email->message($body);
        @$this->email->send();
        
        // Mark as sent to prevent duplicates
        self::$email_sent_tracker[$email_key] = true;
    }
    
    /**
     * Calculate late time if check-in is after start time + grace period
     */
    private function calculate_late_time($checkinDateTime){
        // Get attendance settings
        $start_time = $this->settings->get_setting('attendance_start_time', '09:30');
        $grace_minutes = (int)$this->settings->get_setting('attendance_grace_minutes', 15);
        
        // Parse check-in time
        $checkin_timestamp = strtotime($checkinDateTime);
        if ($checkin_timestamp === false) {
            return null;
        }
        
        // Get today's date from check-in datetime
        $today = date('Y-m-d', $checkin_timestamp);
        
        // Calculate expected check-in time (start time + grace period)
        $expected_datetime = $today . ' ' . $start_time;
        $expected_timestamp = strtotime($expected_datetime);
        $expected_timestamp = $expected_timestamp + ($grace_minutes * 60); // Add grace minutes
        
        // Check if check-in is late
        if ($checkin_timestamp <= $expected_timestamp) {
            return [
                'is_late' => false,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0,
                'formatted' => '0 hours 0 minutes 0 seconds',
                'expected_time' => date('Y-m-d H:i:s', $expected_timestamp)
            ];
        }
        
        // Calculate late time in seconds
        $late_seconds = $checkin_timestamp - $expected_timestamp;
        
        // Calculate hours, minutes, seconds
        $hours = floor($late_seconds / 3600);
        $minutes = floor(($late_seconds % 3600) / 60);
        $seconds = $late_seconds % 60;
        
        // Format late time
        $formatted_parts = [];
        if ($hours > 0) {
            $formatted_parts[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
        }
        if ($minutes > 0) {
            $formatted_parts[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }
        if ($seconds > 0 || empty($formatted_parts)) {
            $formatted_parts[] = $seconds . ' second' . ($seconds > 1 ? 's' : '');
        }
        $formatted = implode(' ', $formatted_parts);
        
        return [
            'is_late' => true,
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => $seconds,
            'formatted' => $formatted,
            'expected_time' => date('Y-m-d H:i:s', $expected_timestamp),
            'late_seconds' => $late_seconds
        ];
    }
    
    /**
     * Send late mark notification to HR and Managers
     */
    private function send_late_mark_to_managers($user_id, $user_name, $checkin_time, $late_info){
        // Get HR user ID from settings
        $hr_user_id = $this->settings->get_setting('leave_hr_user_id');
        $hr_user_id = !empty($hr_user_id) ? (int)$hr_user_id : null;
        
        // Get user's manager/reporting_to
        $manager_id = null;
        if ($this->db->table_exists('employees')) {
            $emp = $this->db->where('user_id', $user_id)->get('employees')->row();
            if ($emp && !empty($emp->reporting_to)) {
                $manager_id = (int)$emp->reporting_to;
            }
        }
        
        // Collect recipients (EXCLUDE the employee to prevent duplicate emails)
        $recipients = [];
        if ($hr_user_id && $hr_user_id !== $user_id) {
            // Only add HR if they are not the employee checking in
            $hr = $this->db->select('email, name')->from('users')->where('id', $hr_user_id)->get()->row();
            if ($hr && !empty($hr->email)) {
                $recipients[] = [
                    'email' => $hr->email,
                    'name' => !empty($hr->name) ? $hr->name : $hr->email
                ];
            }
        }
        if ($manager_id && $manager_id !== $hr_user_id && $manager_id !== $user_id) {
            // Only add Manager if they are not the employee checking in and not already added as HR
            $manager = $this->db->select('email, name')->from('users')->where('id', $manager_id)->get()->row();
            if ($manager && !empty($manager->email)) {
                $recipients[] = [
                    'email' => $manager->email,
                    'name' => !empty($manager->name) ? $manager->name : $manager->email
                ];
            }
        }
        
        // Send email to each recipient
        if (!empty($recipients)) {
            $cfg = array('smtp_timeout'=>10,'mailtype'=>'html','newline'=>"\r\n",'crlf'=>"\r\n",'charset'=>'utf-8');
            $fromAddr = getenv('SMTP_USER');
            if (!$fromAddr || $fromAddr==='') { $fromAddr = 'no-reply@example.com'; }
            $fromName = get_company_name();
            
            $subject = 'Late Mark - ' . htmlspecialchars($user_name) . ' - ' . date('Y-m-d', strtotime($checkin_time));
            
            foreach ($recipients as $recipient) {
                try {
                    $this->email->clear(true);
                    $this->email->initialize($cfg);
                    $this->email->from($fromAddr, $fromName);
                    $this->email->to($recipient['email']);
                    $this->email->subject($subject);
                    
                    $body = '<html><body>';
                    $body .= '<h3 style="color: #dc3545;">Late Mark Notification</h3>';
                    $body .= '<p>Hello ' . htmlspecialchars($recipient['name']) . ',</p>';
                    $body .= '<p><strong>Employee:</strong> ' . htmlspecialchars($user_name) . '</p>';
                    $body .= '<p><strong>Check-in Time:</strong> ' . htmlspecialchars($checkin_time) . '</p>';
                    $body .= '<p style="color: #dc3545; font-weight: bold;">Employee is marked LATE.</p>';
                    $body .= '<p><strong>Late Time Details:</strong></p>';
                    $body .= '<ul>';
                    $body .= '<li>Hours: ' . $late_info['hours'] . '</li>';
                    $body .= '<li>Minutes: ' . $late_info['minutes'] . '</li>';
                    $body .= '<li>Seconds: ' . $late_info['seconds'] . '</li>';
                    $body .= '</ul>';
                    $body .= '<p><strong>Total Late Time:</strong> ' . htmlspecialchars($late_info['formatted']) . '</p>';
                    $body .= '<p><strong>Expected Check-in Time:</strong> ' . htmlspecialchars($late_info['expected_time']) . '</p>';
                    $body .= '<p>Thank you.</p>';
                    $body .= '</body></html>';
                    
                    $this->email->message($body);
                    @$this->email->send();
                } catch (Exception $e) {
                    error_log('Late mark notification email to ' . $recipient['email'] . ' failed: ' . $e->getMessage());
                }
            }
        }
    }

    private function reverse_geocode($lat, $lng){
        $lat = trim((string)$lat);
        $lng = trim((string)$lng);
        if ($lat === '' || $lng === '') { return null; }
        $url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='.
                rawurlencode($lat).'&lon='.
                rawurlencode($lng);
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: " . get_company_name() . "/1.0\r\n",
                'timeout' => 5,
            ],
        ];
        $ctx = stream_context_create($opts);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) { return null; }
        $j = json_decode($resp, true);
        if (!is_array($j)) { return null; }
        if (!empty($j['display_name'])) { return (string)$j['display_name']; }
        if (!empty($j['address']) && is_array($j['address'])){
            $addr = $j['address'];
            $parts = [];
            foreach (['road','suburb','city','state','country'] as $k){
                if (!empty($addr[$k])) { $parts[] = $addr[$k]; }
            }
            if (!empty($parts)) { return implode(', ', $parts); }
        }
        return null;
    }

    private function verify_face_descriptor($stored_json, $current_json){
        $a = json_decode($stored_json, true);
        $b = json_decode($current_json, true);
        if (!is_array($a) || !is_array($b) || count($a) !== count($b) || count($a) === 0) {
            return null; // Return null for invalid format
        }
        $sum = 0.0;
        $n = count($a);
        for ($i = 0; $i < $n; $i++) {
            $da = isset($a[$i]) ? (float)$a[$i] : 0.0;
            $db = isset($b[$i]) ? (float)$b[$i] : 0.0;
            $d = $da - $db;
            $sum += $d * $d;
        }
        $dist = sqrt($sum);
        // Return the distance value (typical threshold for face-api embeddings is around 0.5–0.6)
        return $dist;
    }

    // GET/POST /attendance/{id}/edit
    public function edit($id)
    {
        // Check edit permission specifically
        if (!function_exists('has_module_access') || !has_module_access('attendance_edit')) {
            show_error('You do not have permission to edit attendance.', 403);
        }
        
        $att = $this->db->where('id', (int)$id)->get('attendance')->row();
        if (!$att) { show_404(); }
        // Ownership: only Admin/HR or owner can edit
        $role_id = (int)$this->session->userdata('role_id');
        $user_id = (int)$this->session->userdata('user_id');
        $canManageAll = (function_exists('is_admin_group') && is_admin_group()) || in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true);
        if (!$canManageAll && (int)$att->user_id !== $user_id) { show_error('Forbidden', 403); }
        if ($this->input->method() === 'post') {
            // Optional face verification: mirror create() behavior when descriptor is provided
            $face_required = (string)$this->input->post('face_required');
            $face_descriptor = (string)$this->input->post('face_descriptor');
            if ($face_required === '1') {
                if ($face_descriptor === '') {
                    $this->session->set_flashdata('error', 'Face verification failed: no descriptor provided.');
                    redirect('attendance/'.$id.'/edit');
                    return;
                }
                $tpl = $this->faces->get_by_user($user_id);
                if (!$tpl || empty($tpl->descriptor)) {
                    $this->session->set_flashdata('error', 'Face template not found for this user. Please register face in User profile first.');
                    redirect('attendance/'.$id.'/edit');
                    return;
                }
                $threshold = 0.6;
                $dist = $this->verify_face_descriptor($tpl->descriptor, $face_descriptor);
                if ($dist === null || $dist > $threshold) {
                    $this->session->set_flashdata('error', 'Face not recognized. Please try again.');
                    redirect('attendance/'.$id.'/edit');
                    return;
                }
            }
            $col_date = $this->db->field_exists('att_date','attendance') ? 'att_date' : 'date';
            $col_in   = $this->db->field_exists('punch_in','attendance') ? 'punch_in' : 'check_in';
            $col_out  = $this->db->field_exists('punch_out','attendance') ? 'punch_out' : 'check_out';

            $data = [];
            // Do not overwrite date/check-in/check-out from form; keep backend values
            if ($this->db->field_exists('notes','attendance')) {
                $data['notes'] = trim($this->input->post('notes') ?: '');
            }
            $lat = $this->input->post('lat');
            $lng = $this->input->post('lng');
            if ($lat !== null && $lng !== null) {
                $latCol = null;
                $lngCol = null;
                foreach (['latitude','lat','geo_lat'] as $c) {
                    if ($this->db->field_exists($c, 'attendance')) { $latCol = $c; break; }
                }
                foreach (['longitude','lng','geo_lng'] as $c) {
                    if ($this->db->field_exists($c, 'attendance')) { $lngCol = $c; break; }
                }
                if ($latCol && $lngCol) {
                    $data[$latCol] = (string)$lat;
                    $data[$lngCol] = (string)$lng;
                }
                if ($this->db->field_exists('location_name','attendance')) {
                    $latTrim = trim((string)$lat);
                    $lngTrim = trim((string)$lng);
                    if ($latTrim !== '' && $lngTrim !== '') {
                        $locName = $this->reverse_geocode($latTrim, $lngTrim);
                        if ($locName) { $data['location_name'] = $locName; }
                    }
                }
            }
            // Optional new attachment
            if ($this->db->field_exists('attachment_path','attendance') && !empty($_FILES['attachment']['name'])) {
                $upload_path = FCPATH.'uploads/attendance/';
                if (!is_dir($upload_path)) { @mkdir($upload_path, 0777, true); }
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
            $this->session->set_flashdata('success', 'Attendance updated');
            redirect('attendance');
            return;
        }
        $this->load->view('attendance/edit', ['att' => $att]);
    }

    // POST/GET /attendance/{id}/delete
    public function delete($id)
    {
        // Check delete permission specifically
        if (!function_exists('has_module_access') || !has_module_access('attendance_delete')) {
            show_error('You do not have permission to delete attendance.', 403);
        }
        
        // Ownership: only Admin/HR or owner can delete
        $row = $this->db->where('id', (int)$id)->get('attendance')->row();
        if (!$row) { show_404(); }
        $role_id = (int)$this->session->userdata('role_id');
        $user_id = (int)$this->session->userdata('user_id');
        $canManageAll = (function_exists('is_admin_group') && is_admin_group()) || in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true);
        if (!$canManageAll && (int)$row->user_id !== $user_id) { show_error('Forbidden', 403); }
        $this->db->where('id', (int)$id)->delete('attendance');
        $this->session->set_flashdata('success', 'Attendance deleted');
        redirect('attendance');
    }

    // Calculate attendance statistics
    private function calculateAttendanceStatistics() {
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        $canManageAll = (function_exists('is_admin_group') && is_admin_group()) || in_array($role_id, [ROLE_ADMIN, ROLE_MANAGER], true);
        
        // Base query
        $this->db->from('attendance a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        $this->db->join('employees e', 'e.user_id = a.user_id', 'left');
        
        // Apply permissions
        if (!$canManageAll) {
            $this->db->where('a.user_id', $user_id);
        }
        
        // Get all records for statistics
        $all_records = $this->db->get()->result();
        
        $stats = [
            'total_records' => count($all_records),
            'present_today' => 0,
            'pending_checkout' => 0,
            'absent_today' => 0,
            'attendance_rate' => 0
        ];
        
        $today = date('Y-m-d');
        
        foreach ($all_records as $record) {
            // Check if present today
            $cin = isset($record->punch_in) ? $record->punch_in : (isset($record->check_in) ? $record->check_in : '');
            $cout = isset($record->punch_out) ? $record->punch_out : (isset($record->check_out) ? $record->check_out : '');
            $att_date = isset($record->att_date) ? $record->att_date : (isset($record->date) ? $record->date : '');
            
            if (!empty($cin) && $cin !== '00:00:00') {
                $stats['present_today']++;
            }
            
            // Check for pending checkout
            if (!empty($cin) && (empty($cout) || $cout === '00:00:00')) {
                $stats['pending_checkout']++;
            }
            
            // Check today's attendance for rate calculation
            if ($att_date === $today) {
                if (empty($cin) || $cin === '00:00:00') {
                    $stats['absent_today']++;
                }
            }
        }
        
        // Calculate attendance rate
        $total_expected = $stats['present_today'] + $stats['absent_today'];
        if ($total_expected > 0) {
            $stats['attendance_rate'] = round(($stats['present_today'] / $total_expected) * 100, 1);
        }
        
        return $stats;
    }
}
