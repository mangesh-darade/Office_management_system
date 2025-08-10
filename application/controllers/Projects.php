<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session']);
        $this->load->model('Project_model');
    }

    public function index() {
        $projects = $this->Project_model->all();
        $this->load->view('projects/list', ['projects' => $projects]);
    }

    // GET /projects/create, POST /projects/create
    public function create()
    {
        if ($this->input->method() === 'post') {
            $data = [
                'code' => trim($this->input->post('code')),
                'name' => trim($this->input->post('name')),
                'status' => $this->input->post('status') ?: 'planned',
                'start_date' => $this->input->post('start_date') ?: null,
                'end_date' => $this->input->post('end_date') ?: null,
            ];
            $this->db->insert('projects', $data);
            $id = $this->db->insert_id();
            $this->session->set_flashdata('success', 'Project created');
            redirect('projects/'.$id);
            return;
        }
        $this->load->view('projects/form', ['action' => 'create']);
    }

    // GET /projects/{id}
    public function show($id)
    {
        $project = $this->db->where('id', (int)$id)->get('projects')->row();
        if (!$project) show_404();
        $this->load->view('projects/view', ['project' => $project]);
    }

    // GET /projects/{id}/edit, POST /projects/{id}/edit
    public function edit($id)
    {
        $project = $this->db->where('id', (int)$id)->get('projects')->row();
        if (!$project) show_404();
        if ($this->input->method() === 'post') {
            $data = [
                'code' => trim($this->input->post('code')),
                'name' => trim($this->input->post('name')),
                'status' => $this->input->post('status') ?: 'planned',
                'start_date' => $this->input->post('start_date') ?: null,
                'end_date' => $this->input->post('end_date') ?: null,
            ];
            $this->db->where('id', (int)$id)->update('projects', $data);
            $this->session->set_flashdata('success', 'Project updated');
            redirect('projects/'.$id);
            return;
        }
        $this->load->view('projects/form', ['action' => 'edit', 'project' => $project]);
    }

    // POST /projects/{id}/delete
    public function delete($id)
    {
        $this->db->where('id', (int)$id)->delete('projects');
        $this->session->set_flashdata('success', 'Project deleted');
        redirect('projects');
    }

    // GET/POST /projects/import
    public function import()
    {
        if ($this->input->method() === 'post') {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->session->set_flashdata('error', 'Please upload a valid CSV file');
                redirect('projects/import');
                return;
            }
            $handle = fopen($_FILES['file']['tmp_name'], 'r');
            if (!$handle) { $this->session->set_flashdata('error', 'Unable to read uploaded file'); redirect('projects/import'); return; }
            $header = fgetcsv($handle);
            if (!$header) { fclose($handle); $this->session->set_flashdata('error', 'CSV is empty'); redirect('projects/import'); return; }
            $map = []; foreach ($header as $i=>$c) { $map[strtolower(trim($c))] = $i; }
            $inserted = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $data = [
                    'code' => $row[$map['code']] ?? null,
                    'name' => $row[$map['name']] ?? null,
                    'status' => $row[$map['status']] ?? 'planned',
                    'start_date' => $row[$map['start_date']] ?? null,
                    'end_date' => $row[$map['end_date']] ?? null,
                ];
                if (!empty($data['name'])) { $this->db->insert('projects', $data); $inserted++; }
            }
            fclose($handle);
            $this->session->set_flashdata('success', "Imported $inserted projects");
            redirect('projects');
            return;
        }
        $this->load->view('projects/import');
    }
}
