<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * My Works form validation and uploads.
 */

if (!function_exists('my_works_clients_for_dropdown')) {
    function my_works_clients_for_dropdown($db)
    {
        if (!$db->table_exists('clients')) {
            return array();
        }
        return $db->select('id, company_name')
            ->from('clients')
            ->order_by('company_name', 'ASC')
            ->get()
            ->result();
    }
}

if (!function_exists('my_works_projects_for_dropdown')) {
    /**
     * @param CI_DB_query_builder $db
     * @param int                 $client_id Filter by client when projects.client_id exists
     */
    function my_works_projects_for_dropdown($db, $client_id = 0)
    {
        if (!$db->table_exists('projects')) {
            return array();
        }
        $select = 'id, name';
        $has_client = schema_table_has_column($db, 'projects', 'client_id');
        if ($has_client) {
            $select .= ', client_id';
        }
        $db->select($select)->from('projects');
        if ($has_client && (int) $client_id > 0) {
            $db->where('client_id', (int) $client_id);
        }
        if (function_exists('apply_role_hierarchy_filter')) {
            if (schema_table_has_column($db, 'projects', 'created_by')) {
                apply_role_hierarchy_filter($db, 'created_by');
            } elseif (schema_table_has_column($db, 'projects', 'manager_id')) {
                apply_role_hierarchy_filter($db, 'manager_id');
            }
        }
        return $db->order_by('name', 'ASC')->get()->result();
    }
}

if (!function_exists('my_works_validate_client_project')) {
    function my_works_validate_client_project($db, $client_id, $project_id)
    {
        $client_id = (int) $client_id;
        $project_id = (int) $project_id;
        $out = array('client_id' => null, 'project_id' => null);

        if ($client_id > 0) {
            if (!$db->table_exists('clients') || !$db->get_where('clients', array('id' => $client_id))->row()) {
                $CI =& get_instance();
                $CI->session->set_flashdata('error', 'Please select a valid client.');
                return false;
            }
            $out['client_id'] = $client_id;
        }

        if ($project_id > 0) {
            if (!$db->table_exists('projects')) {
                $CI =& get_instance();
                $CI->session->set_flashdata('error', 'Please select a valid project.');
                return false;
            }
            $project = $db->get_where('projects', array('id' => $project_id))->row();
            if (!$project) {
                $CI =& get_instance();
                $CI->session->set_flashdata('error', 'Please select a valid project.');
                return false;
            }
            if ($out['client_id'] !== null
                && schema_table_has_column($db, 'projects', 'client_id')
                && isset($project->client_id)
                && (int) $project->client_id > 0
                && (int) $project->client_id !== (int) $out['client_id']) {
                $CI =& get_instance();
                $CI->session->set_flashdata('error', 'Selected project does not belong to the chosen client.');
                return false;
            }
            if ($out['client_id'] === null
                && schema_table_has_column($db, 'projects', 'client_id')
                && isset($project->client_id)
                && (int) $project->client_id > 0) {
                $out['client_id'] = (int) $project->client_id;
            }
            $out['project_id'] = $project_id;
        }

        return $out;
    }
}

if (!function_exists('my_works_clear_dashboard_cache')) {
    function my_works_clear_dashboard_cache($user_id, $role_id)
    {
        if (function_exists('clear_dashboard_cache')) {
            clear_dashboard_cache((int) $user_id, (int) $role_id);
        }
    }
}

if (!function_exists('my_works_handle_upload')) {
    /**
     * @param string $upload_dir Relative to FCPATH
     * @return array|false|null[] false on upload error; [null,null] if no file
     */
    function my_works_handle_upload($upload_dir, $existing = null)
    {
        if (empty($_FILES['attachment']['name'])) {
            return array(null, null);
        }
        $CI =& get_instance();
        $dir = FCPATH . $upload_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $config = array(
            'upload_path'   => $dir,
            'allowed_types' => 'gif|jpg|jpeg|png|pdf|doc|docx|xls|xlsx|ppt|pptx|txt|zip|rar|csv',
            'max_size'      => 10240,
            'encrypt_name'  => true,
        );
        $CI->upload->initialize($config);
        if (!$CI->upload->do_upload('attachment')) {
            $CI->session->set_flashdata('error', strip_tags($CI->upload->display_errors('', '')));
            return false;
        }
        $data = $CI->upload->data();
        if ($existing && !empty($existing->attachment_stored)) {
            $old = FCPATH . $upload_dir . $existing->attachment_stored;
            if (is_file($old)) {
                @unlink($old);
            }
        }
        return array($data['orig_name'], $data['file_name']);
    }
}

if (!function_exists('my_works_validate_created_for')) {
    function my_works_validate_created_for($created_for, $can_view_all, $user_id, $role_id)
    {
        $created_for = (int) $created_for;
        $user_id = (int) $user_id;
        if ($created_for <= 0) {
            return $user_id > 0 ? $user_id : false;
        }
        if (!my_works_user_in_assign_scope($created_for, $can_view_all, $user_id, $role_id)) {
            $CI =& get_instance();
            $CI->session->set_flashdata('error', 'You cannot assign work to that user.');
            return false;
        }
        return $created_for;
    }
}

if (!function_exists('my_works_validate_payload')) {
    function my_works_validate_payload($is_edit, $existing, $can_view_all, $user_id, $role_id)
    {
        $CI =& get_instance();
        $title = trim((string) $CI->input->post('title'));
        if ($title === '') {
            $CI->session->set_flashdata('error', 'Task title is required.');
            return false;
        }
        $created_for = my_works_validate_created_for((int) $CI->input->post('created_for'), $can_view_all, $user_id, $role_id);
        if ($created_for === false) {
            return false;
        }
        $status = trim((string) $CI->input->post('status'));
        if (!in_array($status, array('new', 'in_progress', 'closed'), true)) {
            $status = 'new';
        }
        $url = trim((string) $CI->input->post('url'));
        if ($url !== '' && !preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            $CI->session->set_flashdata('error', 'Please enter a valid URL or leave it blank.');
            return false;
        }
        $due = trim((string) $CI->input->post('due_date'));
        $due_date = null;
        if ($due !== '') {
            $parts = explode('-', $due);
            if (count($parts) === 3 && checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
                $due_date = $due;
            }
        }
        $client_project = my_works_validate_client_project(
            $CI->db,
            (int) $CI->input->post('client_id'),
            (int) $CI->input->post('project_id')
        );
        if ($client_project === false) {
            return false;
        }
        $CI->load->helper('types');
        $work_type = module_type_validate_code($CI->input->post('work_type'), 'my_works', true);
        if ($work_type === false) {
            $CI->session->set_flashdata('error', 'Please select a valid type.');
            return false;
        }
        $closing_comment = trim((string) $CI->input->post('closing_comment'));
        return array(
            'title'           => $title,
            'details'         => trim((string) $CI->input->post('details')),
            'tag'             => my_works_normalize_tags($CI->input->post('tag')),
            'url'             => $url !== '' ? $url : null,
            'created_for'     => $created_for,
            'status'          => $status,
            'is_urgent'       => $CI->input->post('is_urgent') ? 1 : 0,
            'is_important'    => $CI->input->post('is_important') ? 1 : 0,
            'due_date'        => $due_date,
            'client_id'       => $client_project['client_id'],
            'project_id'      => $client_project['project_id'],
            'work_type'       => $work_type !== '' ? $work_type : null,
            'closing_comment' => $closing_comment !== '' ? $closing_comment : null,
        );
    }
}

if (!function_exists('my_works_flash_form_old')) {
    function my_works_flash_form_old()
    {
        $CI =& get_instance();
        $CI->session->set_flashdata('mw_form_old', array(
            'title'        => $CI->input->post('title'),
            'details'      => $CI->input->post('details'),
            'tag'          => $CI->input->post('tag'),
            'url'          => $CI->input->post('url'),
            'created_for'  => $CI->input->post('created_for'),
            'status'       => $CI->input->post('status'),
            'due_date'     => $CI->input->post('due_date'),
            'client_id'       => $CI->input->post('client_id'),
            'project_id'      => $CI->input->post('project_id'),
            'work_type'       => $CI->input->post('work_type'),
            'closing_comment' => $CI->input->post('closing_comment'),
            'is_urgent'       => $CI->input->post('is_urgent'),
            'is_important' => $CI->input->post('is_important'),
        ));
    }
}
