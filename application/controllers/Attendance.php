<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission','group_filter']);
        $this->load->library(['session','upload','email','pagination']);
        $this->load->model('Attendance_model');
        $this->load->model('Face_model', 'faces');
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
        $canViewAll = $isAdminGroup || in_array($role_id, [1,2], true);
        $canAddAttendance = true; // All logged-in users can add their own attendance
        
        // Get group-based filters
        $filters = get_user_group_filter($user_id, $role_id);
        
        // Check if we should show all records or only today's
        $show_all = $this->input->get('all') === '1';
        $today = date('Y-m-d');
        
        // Count total records for pagination
        $this->db->select('COUNT(*) as total');
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
        
        // Show only today's records by default (unless 'all=1' is in URL)
        if (!$show_all) {
            // Get date column name
            $date_col = 'att_date';
            if (!$this->db->field_exists('att_date', 'attendance')) {
                $date_columns = ['date', 'attendance_date', 'created_at'];
                foreach ($date_columns as $col) {
                    if ($this->db->field_exists($col, 'attendance')) {
                        $date_col = $col;
                        break;
                    }
                }
            }
            $this->db->where('a.'.$date_col, $today);
        }
        
        $total_query = $this->db->get()->row();
        $total_records = $total_query->total;
        
        // Fetch attendance with user email and, if available, employee name
        $this->db->select('a.*, u.email');
        $this->db->from('attendance a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');
        $employee_exists = $this->db->table_exists('employees');
        if ($employee_exists) {
            // Try to join employees by user_id if schema maps
            $this->db->select('e.first_name, e.last_name');
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
        
        // Show only today's records by default (unless 'all=1' is in URL)
        if (!$show_all) {
            // Get date column name
            $date_col = 'att_date';
            if (!$this->db->field_exists('att_date', 'attendance')) {
                $date_columns = ['date', 'attendance_date', 'created_at'];
                foreach ($date_columns as $col) {
                    if ($this->db->field_exists($col, 'attendance')) {
                        $date_col = $col;
                        break;
                    }
                }
            }
            $this->db->where('a.'.$date_col, $today);
        }
        
        $records = $this->db->order_by('a.att_date DESC, a.id DESC')
                           ->limit($per_page, $page)
                           ->get()
                           ->result();
        
        // Pagination config
        $base_url = $show_all ? site_url('attendance/index/all/1') : site_url('attendance/index');
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
        
        $this->load->view('attendance/index', [
            'records' => $records,
            'employee_exists' => $employee_exists,
            'pagination_links' => $pagination_links,
            'total_records' => $total_records,
            'current_page' => $page + 1,
            'per_page' => $per_page,
            'can_add_attendance' => $canAddAttendance,
            'can_view_all' => $canViewAll,
            'show_all' => $show_all,
            'today' => $today,
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
        $canManageAll = (function_exists('is_admin_group') && is_admin_group()) || in_array($role_id, [1,2], true);
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
            if (!in_array($action, ['in', 'out'])) {
                $this->session->set_flashdata('error', 'Invalid action selected');
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

            // Optional face verification: when face_required=1, validate descriptor against stored template
            $face_required = (string)$this->input->post('face_required');
            $face_descriptor = (string)$this->input->post('face_descriptor');
            if ($face_required === '1') {
                if (empty($face_descriptor)) {
                    $this->session->set_flashdata('error', 'Face verification required but no face data provided');
                    redirect('attendance/create');
                    return;
                }
                // Verify face descriptor against stored template for this user
                $stored = $this->faces->get_by_user($user_id);
                if ($stored && !empty($stored->descriptor)) {
                    $threshold = 0.6;
                    $dist = $this->verify_face_descriptor($face_descriptor, $stored->descriptor);
                    if ($dist === null) {
                        $this->session->set_flashdata('error', 'Face verification failed: Invalid face data format');
                        redirect('attendance/create');
                        return;
                    }
                    if ($dist > $threshold) {
                        $this->session->set_flashdata('error', 'Face verification failed: Face does not match registered face');
                        redirect('attendance/create');
                        return;
                    }
                } else {
                    // If no stored face, you might want to register or skip verification
                    $this->session->set_flashdata('error', 'No registered face found for this user');
                    redirect('attendance/create');
                    return;
                }
            }

            // Location handling
            $lat = $this->input->post('lat');
            $lng = $this->input->post('lng');
            $location_name = $this->input->post('location_name');
            
            // Validate location if required
            if (empty($lat) || empty($lng)) {
                $this->session->set_flashdata('warning', 'Location information is missing. Please enable location services for better attendance tracking.');
            }

            // Get current date/time
            $nowDateTime = date('Y-m-d H:i:s');
            $nowTime = date('H:i:s');
            $today = date('Y-m-d');

            // Schema-aware column names
            $col_date = 'att_date';
            $col_in = 'punch_in';
            $col_out = 'punch_out';
            if (!$this->db->field_exists($col_date, 'attendance')) $col_date = 'date';
            if (!$this->db->field_exists($col_in, 'attendance')) $col_in = 'check_in';
            if (!$this->db->field_exists($col_out, 'attendance')) $col_out = 'check_out';

            // Check if user already has a record for today
            $existing = $this->db->where('user_id', $user_id)
                                 ->where($col_date, $today)
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
            
            // Add location fields if they exist in schema
            if ($this->db->field_exists('latitude', 'attendance')) $data['latitude'] = $lat;
            if ($this->db->field_exists('longitude', 'attendance')) $data['longitude'] = $lng;
            if ($this->db->field_exists('lat', 'attendance')) $data['lat'] = $lat;
            if ($this->db->field_exists('lng', 'attendance')) $data['lng'] = $lng;
            if ($this->db->field_exists('geo_lat', 'attendance')) $data['geo_lat'] = $lat;
            if ($this->db->field_exists('geo_lng', 'attendance')) $data['geo_lng'] = $lng;

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
                        $updates[$col_in] = (in_array($inType, ['datetime','timestamp'], true)) ? $nowDateTime : $nowTime;
                        if (array_key_exists('notes', $data)) { $updates['notes'] = $data['notes']; }
                        if (array_key_exists('attachment_path', $data) && $data['attachment_path']) { $updates['attachment_path'] = $data['attachment_path']; }
                        // Update location fields
                        foreach (['latitude','longitude','lat','lng','geo_lat','geo_lng','location_name'] as $field) {
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
                                $updates[$col_out] = $proposedOut;
                                if (array_key_exists('notes', $data)) { $updates['notes'] = $data['notes']; }
                                if (array_key_exists('attachment_path', $data) && $data['attachment_path']) { $updates['attachment_path'] = $data['attachment_path']; }
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
                    try {
                        $this->db->insert('attendance', $data);
                        $this->maybe_send_attendance_email($user_id, 'in', $nowDateTime);
                        $this->session->set_flashdata('success', 'Checked in successfully');
                    } catch (Exception $e) {
                        $this->session->set_flashdata('error', 'Failed to save attendance: ' . $e->getMessage());
                    }
                }
            }
            redirect('attendance');
            return;
        }
        $this->load->view('attendance/create');
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
        if (!$this->db->table_exists('users')) { return; }

        $select = ['email'];
        if ($this->db->field_exists('notify_attendance','users')){ $select[] = 'notify_attendance'; }
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

        $cfg = array('smtp_timeout'=>10,'mailtype'=>'text','newline'=>"\r\n",'crlf'=>"\r\n",'charset'=>'utf-8');
        $this->email->initialize($cfg);
        $this->email->clear(true);
        $fromAddr = getenv('SMTP_USER');
        if (!$fromAddr || $fromAddr==='') { $fromAddr = 'no-reply@example.com'; }
        $fromName = get_company_name();
        $this->email->from($fromAddr, $fromName);
        $this->email->to($user->email);
        $isOut = ($action === 'out');
        $subject = $isOut ? 'Attendance checkout recorded' : 'Attendance check-in recorded';
        $body = "Hello,\n\nYour attendance ".($isOut?'checkout':'check-in')." has been recorded at ".$dateTime.".\n\nThank you.";
        $this->email->subject($subject);
        $this->email->message($body);
        $this->email->send();
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
            return false;
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
        // Typical threshold for face-api embeddings is around 0.5–0.6; use 0.6 as default
        return $dist <= 0.6;
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
        $canManageAll = (function_exists('is_admin_group') && is_admin_group()) || in_array($role_id, [1,2], true);
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
                if (!$this->verify_face_descriptor($tpl->descriptor, $face_descriptor)) {
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
        $canManageAll = (function_exists('is_admin_group') && is_admin_group()) || in_array($role_id, [1,2], true);
        if (!$canManageAll && (int)$row->user_id !== $user_id) { show_error('Forbidden', 403); }
        $this->db->where('id', (int)$id)->delete('attendance');
        $this->session->set_flashdata('success', 'Attendance deleted');
        redirect('attendance');
    }

    // Calculate attendance statistics
    private function calculateAttendanceStatistics() {
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        $canManageAll = (function_exists('is_admin_group') && is_admin_group()) || in_array($role_id, [1,2], true);
        
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
