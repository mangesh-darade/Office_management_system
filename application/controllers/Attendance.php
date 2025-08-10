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

            $data = [
                'user_id' => $user_id,
                'date' => $this->input->post('date') ?: date('Y-m-d'),
                'check_in' => $this->input->post('check_in') ?: null,
                'check_out' => $this->input->post('check_out') ?: null,
                'notes' => trim($this->input->post('notes') ?: ''),
                'attachment_path' => null,
            ];

            // Handle attachment upload (optional)
            if (!empty($_FILES['attachment']['name'])) {
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

            // Persist
            $this->db->insert('attendance', $data);
            $this->session->set_flashdata('success', 'Attendance saved');
            redirect('attendance');
            return;
        }
        $this->load->view('attendance/create');
    }

    // GET/POST /attendance/{id}/edit
    public function edit($id)
    {
        $att = $this->db->where('id', (int)$id)->get('attendance')->row();
        if (!$att) { show_404(); }
        if ($this->input->method() === 'post') {
            $data = [
                'date' => $this->input->post('date') ?: $att->date,
                'check_in' => $this->input->post('check_in') ?: null,
                'check_out' => $this->input->post('check_out') ?: null,
                'notes' => trim($this->input->post('notes') ?: ''),
            ];
            // Optional new attachment
            if (!empty($_FILES['attachment']['name'])) {
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
        $this->db->where('id', (int)$id)->delete('attendance');
        $this->session->set_flashdata('success', 'Attendance deleted');
        redirect('attendance');
    }
}
