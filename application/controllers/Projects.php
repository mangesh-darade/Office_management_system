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
        $embed = (bool)$this->input->get('embed');
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
            $this->load->helper('activity');
            log_activity('projects', 'created', (int)$id, 'Project: '.(string)$data['name']);
            $this->session->set_flashdata('success', 'Project created');

            if ($embed) {
                $name = (string)$data['name'];
                $project_id = (int)$id;
                $safe_name = json_encode($name);
                echo "<!doctype html><html><head><meta charset=\"utf-8\"><title>Project created</title></head><body>";
                echo "<script>\n".
                     "if (window.parent && typeof window.parent.onProjectCreated === 'function') {\n".
                     "  window.parent.onProjectCreated(".$project_id.", " . $safe_name . ");\n".
                     "} else {\n".
                     "  window.close && window.close();\n".
                     "}\n".
                     "</script>";
                echo "</body></html>";
                return;
            }

            redirect('projects/'.$id);
            return;
        }
        $this->load->view('projects/form', ['action' => 'create', 'embed' => $embed]);
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
            $this->load->helper('activity');
            log_activity('projects', 'updated', (int)$id, 'Project: '.(string)$data['name']);
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
        $this->load->helper('activity');
        log_activity('projects', 'deleted', (int)$id, 'Project deleted');
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
            $errors = 0;
            $prev_debug = $this->db->db_debug;
            $this->db->db_debug = false;
            while (($row = fgetcsv($handle)) !== false) {
                $data = [
                    'code' => (isset($map['code']) && isset($row[$map['code']])) ? $row[$map['code']] : null,
                    'name' => (isset($map['name']) && isset($row[$map['name']])) ? $row[$map['name']] : null,
                    'status' => (isset($map['status']) && isset($row[$map['status']])) ? $row[$map['status']] : 'planned',
                    'start_date' => (isset($map['start_date']) && isset($row[$map['start_date']])) ? $row[$map['start_date']] : null,
                    'end_date' => (isset($map['end_date']) && isset($row[$map['end_date']])) ? $row[$map['end_date']] : null,
                ];
                if (!empty($data['name'])) {
                    $ok = $this->db->insert('projects', $data);
                    if ($ok) {
                        $inserted++;
                    } else {
                        $errors++;
                        $db_error = $this->db->error();
                        if (!empty($db_error['message'])) {
                            log_message('error', 'Project import error: '.$db_error['message']);
                        }
                    }
                }
            }
            $this->db->db_debug = $prev_debug;
            fclose($handle);
            if ($errors > 0 && $inserted === 0) {
                $this->session->set_flashdata('error', 'No projects were imported. Please check your CSV for duplicate codes or invalid data.');
            } elseif ($errors > 0) {
                $this->session->set_flashdata('success', "Imported $inserted projects. Some rows were skipped due to errors (for example, duplicate codes or invalid data).");
            } else {
                $this->session->set_flashdata('success', "Imported $inserted projects");
            }
            redirect('projects');
            return;
        }
        $this->load->view('projects/import');
    }

    // GET /projects/{id}/members
    public function manage_members($project_id)
    {
        $project_id = (int)$project_id;
        $project = $this->db->where('id', $project_id)->get('projects')->row();
        if (!$project) { show_404(); }

        // Fetch members
        $this->load->model('Project_model');
        $members = $this->Project_model->get_project_members($project_id);

        // Basic search for adding members
        $q = trim((string)$this->input->get('q'));
        $users = [];
        if ($q !== ''){
            $this->db->select('id, email');
            if ($this->db->field_exists('name','users')) { $this->db->select('name'); }
            $this->db->from('users');
            $this->db->group_start()
                     ->like('email', $q)
                     ->or_like('name', $q)
                     ->group_end()
                     ->order_by('email','ASC');
            $users = $this->db->get()->result();
        }

        $this->load->view('projects/members', [
            'project' => $project,
            'members' => $members,
            'users' => $users,
            'q' => $q,
        ]);
    }

    // POST /projects/{id}/add-member
    public function add_member($project_id)
    {
        $project_id = (int)$project_id;
        $user_id = (int)$this->input->post('user_id');
        $role = trim((string)$this->input->post('role')) ?: 'member';
        if (!$user_id) { $this->session->set_flashdata('error', 'Select a user.'); redirect('projects/'.$project_id.'/members'); return; }

        $this->load->model('Project_model');
        $ok = $this->Project_model->add_member($project_id, $user_id, $role);
        if ($ok) { $this->load->helper('activity'); log_activity('projects', 'assigned', $project_id, 'Added member user#'.$user_id.' as '.$role); }
        if ($ok) { $this->session->set_flashdata('success', 'Member added.'); }
        else { $this->session->set_flashdata('error', 'Failed to add member.'); }
        redirect('projects/'.$project_id.'/members');
    }

    // POST /projects/{id}/remove-member/{user_id}
    public function remove_member($project_id, $user_id)
    {
        $project_id = (int)$project_id; $user_id = (int)$user_id;
        // Allow only admin or project creator (if column exists)
        $role_id = (int)$this->session->userdata('role_id');
        if ($role_id !== 1) {
            $project = $this->db->where('id', $project_id)->get('projects')->row();
            if (!$project) { show_404(); }
            if ($this->db->field_exists('created_by','projects')){
                $me = (int)$this->session->userdata('user_id');
                if ((int)$project->created_by !== $me) { show_error('Forbidden', 403); }
            }
        }
        $this->load->model('Project_model');
        $ok = $this->Project_model->remove_member($project_id, $user_id);
        if ($ok) { $this->load->helper('activity'); log_activity('projects', 'updated', $project_id, 'Removed member user#'.$user_id); }
        if ($ok) { $this->session->set_flashdata('success', 'Member removed.'); }
        else { $this->session->set_flashdata('error', 'Failed to remove member.'); }
        redirect('projects/'.$project_id.'/members');
    }

    // POST /projects/{id}/member/{user_id}/role
    public function update_member_role($project_id, $user_id)
    {
        $project_id = (int)$project_id; $user_id = (int)$user_id;
        $role = trim((string)$this->input->post('role')) ?: 'member';
        $this->load->model('Project_model');
        $ok = $this->Project_model->update_member_role($project_id, $user_id, $role);
        if ($ok) { $this->load->helper('activity'); log_activity('projects', 'updated', $project_id, 'Changed role of user#'.$user_id.' to '.$role); }
        if ($ok) { $this->session->set_flashdata('success', 'Role updated.'); }
        else { $this->session->set_flashdata('error', 'Failed to update role.'); }
        redirect('projects/'.$project_id.'/members');
    }
}
