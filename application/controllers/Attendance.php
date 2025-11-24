<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission']);
        $this->load->library(['session','upload']);
        $this->load->model('Attendance_model');
        $this->load->model('Face_model', 'faces');
    }

    public function index() {
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
        // Non-admin/HR see only their own attendance
        $user_id = (int)$this->session->userdata('user_id');
        if ($user_id) {
            $isAdminGroup = (function_exists('is_admin_group') && is_admin_group());
            if (!$isAdminGroup) {
                $this->db->where('a.user_id', $user_id);
            }
        }
        $records = $this->db->order_by($employee_exists ? 'e.first_name' : 'u.email', 'asc')->get()->result();
        $this->load->view('attendance/index', [
            'records' => $records,
            'employee_exists' => $employee_exists,
        ]);
    }

    // GET/POST /attendance/create
    public function create()
    {
        if ($this->input->method() === 'post') {
            $user_id = (int)$this->session->userdata('user_id');
            if (!$user_id) { redirect('login'); return; }

            // Optional face verification: when face_required=1, validate descriptor against stored template
            $face_required = (string)$this->input->post('face_required');
            $face_descriptor = (string)$this->input->post('face_descriptor');
            if ($face_required === '1') {
                if ($face_descriptor === '') {
                    $this->session->set_flashdata('error', 'Face verification failed: no descriptor provided.');
                    redirect('attendance/create');
                    return;
                }
                $tpl = $this->faces->get_by_user($user_id);
                if (!$tpl || empty($tpl->descriptor)) {
                    $this->session->set_flashdata('error', 'Face template not found for this user. Please register face in User profile first.');
                    redirect('attendance/create');
                    return;
                }
                if (!$this->verify_face_descriptor($tpl->descriptor, $face_descriptor)) {
                    $this->session->set_flashdata('error', 'Face not recognized. Please try again.');
                    redirect('attendance/create');
                    return;
                }
            }
            // Map to schema columns (supports both legacy and installer schema)
            $col_date = $this->db->field_exists('att_date','attendance') ? 'att_date' : 'date';
            $col_in   = $this->db->field_exists('punch_in','attendance') ? 'punch_in' : 'check_in';
            $col_out  = $this->db->field_exists('punch_out','attendance') ? 'punch_out' : 'check_out';

            // Auto date/time: always use today for date
            $today = date('Y-m-d');
            $nowTime = date('H:i:s');
            $nowDateTime = date('Y-m-d H:i:00');

            $data = [ 'user_id' => $user_id ];
            $data[$col_date] = $today;

            // Optional columns if present in schema
            if ($this->db->field_exists('notes','attendance')) {
                $data['notes'] = trim($this->input->post('notes') ?: '');
            }
            if ($this->db->field_exists('attachment_path','attendance')) {
                $data['attachment_path'] = null;
            }

            // Geolocation (lat/lng) and IP capture if present in schema
            $lat = $this->input->post('lat');
            $lng = $this->input->post('lng');
            if ($lat !== null && $lng !== null) {
                // Determine latitude column
                $latCol = null; $lngCol = null;
                foreach (['latitude','lat','geo_lat'] as $c) { if ($this->db->field_exists($c,'attendance')) { $latCol = $c; break; } }
                foreach (['longitude','lng','geo_lng'] as $c) { if ($this->db->field_exists($c,'attendance')) { $lngCol = $c; break; } }
                if ($latCol && $lngCol) {
                    $data[$latCol] = (string)$lat;
                    $data[$lngCol] = (string)$lng;
                }
            }
            if ($this->db->field_exists('ip_address','attendance')) {
                $data['ip_address'] = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
            }

            // Handle attachment upload (optional)
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
                    redirect('attendance/create');
                    return;
                }
            }

            // Persist logic based on requested action (IN/OUT) for today
            $existing = $this->db->from('attendance')
                                 ->where('user_id', (int)$user_id)
                                 ->where($col_date, $today)
                                 ->limit(1)
                                 ->get()->row();
            $action = strtolower(trim((string)$this->input->post('action')));
            if ($action !== 'in' && $action !== 'out') { $action = 'in'; }

            if ($existing) {
                $updates = [];
                $hasIn = !$this->is_empty_time($existing->$col_in);
                $hasOut = !$this->is_empty_time($existing->$col_out);
                if ($action === 'in') {
                    if ($hasIn) {
                        // Already checked in; allow notes/attachment update only
                        if (array_key_exists('notes', $data)) { $updates['notes'] = $data['notes']; }
                        if (array_key_exists('attachment_path', $data) && $data['attachment_path']) { $updates['attachment_path'] = $data['attachment_path']; }
                        if (!empty($updates)) {
                            $this->db->where('id', (int)$existing->id)->update('attendance', $updates);
                        }
                        $this->session->set_flashdata('error', 'Already checked in for today.');
                    } else {
                        $inType = $this->get_column_type('attendance', $col_in);
                        $updates[$col_in] = (in_array($inType, ['datetime','timestamp'], true)) ? $nowDateTime : $nowTime;
                        if (array_key_exists('notes', $data)) { $updates['notes'] = $data['notes']; }
                        if (array_key_exists('attachment_path', $data) && $data['attachment_path']) { $updates['attachment_path'] = $data['attachment_path']; }
                        $this->db->where('id', (int)$existing->id)->update('attendance', $updates);
                        $this->session->set_flashdata('success', 'Checked in successfully');
                    }
                } else { // action === 'out'
                    if (!$hasIn) {
                        $this->session->set_flashdata('error', 'You must check in before checking out.');
                    } else if ($hasOut) {
                        $this->session->set_flashdata('error', 'Already checked out for today.');
                    } else {
                        // Ensure checkout time is >= checkin
                        $checkInRaw = isset($existing->$col_in) ? (string)$existing->$col_in : '';
                        $checkInTime = $checkInRaw;
                        if (strpos($checkInRaw, ' ') !== false) { $checkInTime = trim(explode(' ', $checkInRaw)[1]); }
                        if ($checkInTime === '' || substr($nowTime,0,5) >= substr($checkInTime,0,5)) {
                            $outType = $this->get_column_type('attendance', $col_out);
                            $updates[$col_out] = (in_array($outType, ['datetime','timestamp'], true)) ? $nowDateTime : $nowTime;
                            if (array_key_exists('notes', $data)) { $updates['notes'] = $data['notes']; }
                            if (array_key_exists('attachment_path', $data) && $data['attachment_path']) { $updates['attachment_path'] = $data['attachment_path']; }
                            $this->db->where('id', (int)$existing->id)->update('attendance', $updates);
                            $this->session->set_flashdata('success', 'Checked out successfully');
                        } else {
                            $this->session->set_flashdata('error', 'Checkout time cannot be before check-in time.');
                        }
                    }
                }
            } else {
                if ($action === 'out') {
                    $this->session->set_flashdata('error', 'You must check in before checking out.');
                } else { // action in on new day
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
                    $this->db->insert('attendance', $data);
                    $this->session->set_flashdata('success', 'Checked in successfully');
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
                'header' => "User-Agent: OfficeMgmt/1.0\r\n",
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
}
