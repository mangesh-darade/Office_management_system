<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session','upload']);
        $this->load->model('Attendance_model');
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
        $role_id = (int)$this->session->userdata('role_id');
        $user_id = (int)$this->session->userdata('user_id');
        if (!in_array($role_id, [1,2], true) && $user_id) {
            $this->db->where('a.user_id', $user_id);
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

    // GET/POST /attendance/{id}/edit
    public function edit($id)
    {
        $att = $this->db->where('id', (int)$id)->get('attendance')->row();
        if (!$att) { show_404(); }
        // Ownership: only Admin/HR or owner can edit
        $role_id = (int)$this->session->userdata('role_id');
        $user_id = (int)$this->session->userdata('user_id');
        if (!in_array($role_id, [1,2], true) && (int)$att->user_id !== $user_id) { show_error('Forbidden', 403); }
        if ($this->input->method() === 'post') {
            $col_date = $this->db->field_exists('att_date','attendance') ? 'att_date' : 'date';
            $col_in   = $this->db->field_exists('punch_in','attendance') ? 'punch_in' : 'check_in';
            $col_out  = $this->db->field_exists('punch_out','attendance') ? 'punch_out' : 'check_out';

            $data = [];
            $data[$col_date] = $this->input->post('date') ?: (isset($att->$col_date) ? $att->$col_date : date('Y-m-d'));
            $data[$col_in]   = $this->input->post('check_in') ?: null;
            $data[$col_out]  = $this->input->post('check_out') ?: null;
            if ($this->db->field_exists('notes','attendance')) {
                $data['notes'] = trim($this->input->post('notes') ?: '');
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
            $this->db->where('id', (int)$id)->update('attendance', $data);
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
        if (!in_array($role_id, [1,2], true) && (int)$row->user_id !== $user_id) { show_error('Forbidden', 403); }
        $this->db->where('id', (int)$id)->delete('attendance');
        $this->session->set_flashdata('success', 'Attendance deleted');
        redirect('attendance');
    }
}
