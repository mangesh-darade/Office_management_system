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

if (!function_exists('my_works_upload_allowed_types')) {
    function my_works_upload_allowed_types()
    {
        return 'gif|jpg|jpeg|png|webp|bmp|pdf|doc|docx|xls|xlsx|ppt|pptx|txt|zip|rar|7z|tar|gz|csv|mp4|webm|mov|avi|mkv|m4v|mp3|wav|ogg|flac|aac|m4a|wmv|flv|mpeg|mpg|3gp';
    }
}

if (!function_exists('my_works_upload_max_bytes')) {
    /** Application attachment cap: 100 MB */
    function my_works_upload_max_bytes()
    {
        return 104857600;
    }
}

if (!function_exists('my_works_upload_max_kb')) {
    function my_works_upload_max_kb()
    {
        return (int) floor(my_works_upload_max_bytes() / 1024);
    }
}

if (!function_exists('my_works_upload_max_mb')) {
    function my_works_upload_max_mb()
    {
        return 100;
    }
}

if (!function_exists('my_works_php_ini_bytes')) {
    function my_works_php_ini_bytes($setting)
    {
        $val = trim((string) ini_get($setting));
        if ($val === '' || $val === '-1') {
            return 0;
        }
        $last = strtolower($val[strlen($val) - 1]);
        $num = (float) $val;
        if ($last === 'g') {
            return (int) round($num * 1073741824);
        }
        if ($last === 'm') {
            return (int) round($num * 1048576);
        }
        if ($last === 'k') {
            return (int) round($num * 1024);
        }
        return (int) round($num);
    }
}

if (!function_exists('my_works_effective_upload_max_bytes')) {
    /**
     * Smallest of app cap (100 MB) and current PHP upload/post limits.
     */
    function my_works_effective_upload_max_bytes()
    {
        $app_bytes = my_works_upload_max_bytes();
        $upload_bytes = my_works_php_ini_bytes('upload_max_filesize');
        $post_bytes = my_works_php_ini_bytes('post_max_size');
        $limits = array($app_bytes);
        if ($upload_bytes > 0) {
            $limits[] = $upload_bytes;
        }
        if ($post_bytes > 0) {
            $limits[] = max(0, $post_bytes - 2097152);
        }
        return (int) min($limits);
    }
}

if (!function_exists('my_works_effective_upload_max_kb')) {
    function my_works_effective_upload_max_kb()
    {
        return max(1, (int) floor(my_works_effective_upload_max_bytes() / 1024));
    }
}

if (!function_exists('my_works_upload_error_message')) {
    function my_works_upload_error_message($error_code)
    {
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        switch ((int) $error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File is too large. Server limit is upload_max_filesize=' . $upload_max
                    . ', post_max_size=' . $post_max . '.';
            case UPLOAD_ERR_PARTIAL:
                return 'Upload was interrupted. Please try again.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Server upload folder is missing. Contact your administrator.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Server could not save the file. Contact your administrator.';
            case UPLOAD_ERR_EXTENSION:
                return 'A server extension blocked this file type.';
            default:
                return 'Could not upload the attachment. Please try again.';
        }
    }
}

if (!function_exists('my_works_sanitize_details_html')) {
    function my_works_sanitize_details_html($html)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }
        $allowed = '<p><br><strong><em><b><i><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre><span><div><del><sub><sup><table><thead><tbody><tr><th><td>';
        $clean = strip_tags($html, $allowed);
        return $clean !== '' ? $clean : null;
    }
}

if (!function_exists('my_works_upload_field_has_files')) {
    /**
     * @param string $key $_FILES key (e.g. attachments, attachment)
     */
    function my_works_upload_field_has_files($key)
    {
        if (!isset($_FILES[$key]) || !isset($_FILES[$key]['name'])) {
            return false;
        }
        $name = $_FILES[$key]['name'];
        if (is_array($name)) {
            foreach ($name as $n) {
                if (trim((string) $n) !== '') {
                    return true;
                }
            }
            return false;
        }
        return trim((string) $name) !== '';
    }
}

if (!function_exists('my_works_handle_upload')) {
    /**
     * @param string $upload_dir Relative to FCPATH
     * @return array|false|null[] false on upload error; [null,null] if no file
     */
    function my_works_handle_upload($upload_dir, $existing = null)
    {
        if (!my_works_upload_field_has_files('attachment')) {
            return array(null, null);
        }
        $CI =& get_instance();
        if (isset($_FILES['attachment']['error']) && (int) $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            $CI->session->set_flashdata('error', my_works_upload_error_message($_FILES['attachment']['error']));
            return false;
        }
        $dir = FCPATH . $upload_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $config = array(
            'upload_path'   => $dir,
            'allowed_types' => my_works_upload_allowed_types(),
            'max_size'      => my_works_upload_max_kb(),
            'encrypt_name'  => true,
        );
        $CI->upload->initialize($config);
        if (!$CI->upload->do_upload('attachment')) {
            $err = strip_tags($CI->upload->display_errors('', ''));
            if ($err === '' || stripos($err, 'filetype') !== false) {
                $err = 'This file type is not allowed or could not be verified. Supported: images, PDF, Office docs, video (mp4, webm, mov, mkv, m4v), audio, zip.';
            }
            $CI->session->set_flashdata('error', $err);
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

if (!function_exists('my_works_handle_uploads')) {
    /**
     * Upload one or more attachments (attachments[] or legacy attachment field).
     *
     * @param string $upload_dir Relative to FCPATH
     * @return array|false Empty array if no files; false on error
     */
    function my_works_handle_uploads($upload_dir)
    {
        $CI =& get_instance();
        $CI->load->helper('my_works_attachment');
        $max_per = my_works_max_attachments_per_submit();
        $results = array();

        $has_multi = my_works_upload_field_has_files('attachments');
        $has_legacy = my_works_upload_field_has_files('attachment');

        if ($has_legacy && !$has_multi) {
            $one = my_works_handle_upload($upload_dir);
            if ($one === false) {
                return false;
            }
            if ($one[0] !== null) {
                $path = FCPATH . $upload_dir . $one[1];
                $size = is_file($path) ? (int) filesize($path) : 0;
                $results[] = array(
                    'original' => $one[0],
                    'stored'   => $one[1],
                    'size'     => $size,
                );
            }
            return $results;
        }

        if (!$has_multi) {
            return array();
        }

        $files = $_FILES['attachments'];
        $names = $files['name'];
        if (!is_array($names)) {
            $names = array($names);
            $files = array(
                'name'     => array($files['name']),
                'type'     => array($files['type']),
                'tmp_name' => array($files['tmp_name']),
                'error'    => array($files['error']),
                'size'     => array($files['size']),
            );
        }

        $count = 0;
        foreach ($names as $name) {
            if (trim((string) $name) !== '') {
                $count++;
            }
        }
        if ($count < 1) {
            return array();
        }
        if ($count > $max_per) {
            $CI->session->set_flashdata('error', 'You can upload up to ' . $max_per . ' files at once.');
            return false;
        }

        $dir = FCPATH . $upload_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $config = array(
            'upload_path'   => $dir,
            'allowed_types' => my_works_upload_allowed_types(),
            'max_size'      => my_works_upload_max_kb(),
            'encrypt_name'  => true,
        );

        foreach ($names as $i => $name) {
            if (trim((string) $name) === '') {
                continue;
            }
            if ((int) $files['error'][$i] !== UPLOAD_ERR_OK) {
                $CI->session->set_flashdata('error', my_works_upload_error_message($files['error'][$i]) . ' (' . $name . ')');
                foreach ($results as $r) {
                    my_works_delete_attachment_file($r['stored']);
                }
                return false;
            }
            $_FILES['mw_single_upload'] = array(
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            );
            $CI->upload->initialize($config);
            if (!$CI->upload->do_upload('mw_single_upload')) {
                $err = strip_tags($CI->upload->display_errors('', ''));
                if ($err === '' || stripos($err, 'filetype') !== false) {
                    $err = 'This file type is not allowed or could not be verified. Supported: images, PDF, Office docs, video (mp4, webm, mov, mkv, m4v), audio, zip.';
                }
                $CI->session->set_flashdata('error', $err . ' (' . $name . ')');
                foreach ($results as $r) {
                    my_works_delete_attachment_file($r['stored']);
                }
                return false;
            }
            $data = $CI->upload->data();
            $results[] = array(
                'original' => $data['orig_name'],
                'stored'   => $data['file_name'],
                'size'     => isset($data['file_size']) ? (int) $data['file_size'] : 0,
            );
        }

        return $results;
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
        $posted_for = (int) $CI->input->post('created_for');
        if ($posted_for <= 0) {
            $CI->session->set_flashdata('error', 'Assigned to is required.');
            return false;
        }
        $created_for = my_works_validate_created_for($posted_for, $can_view_all, $user_id, $role_id);
        if ($created_for === false) {
            return false;
        }
        $CI->load->helper('my_works_status');
        $status = my_works_status_sanitize($CI->input->post('status'), 'new');
        $url = trim((string) $CI->input->post('url'));
        if ($url !== '' && !preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            $CI->session->set_flashdata('error', 'Please enter a valid URL or leave it blank.');
            return false;
        }
        $due = trim((string) $CI->input->post('due_date'));
        if ($due === '') {
            $CI->session->set_flashdata('error', 'Due date is required.');
            return false;
        }
        $due_date = null;
        $parts = explode('-', $due);
        if (count($parts) === 3 && checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            $due_date = $due;
        }
        if ($due_date === null) {
            $CI->session->set_flashdata('error', 'Please enter a valid due date.');
            return false;
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
        $CI->load->helper('estimate_hours');
        $est = estimate_hours_parse($CI->input->post('estimate_hours'));
        if ($est === false) {
            $CI->session->set_flashdata('error', 'Estimate (hrs) must be a number between 0 and 9999.99.');
            return false;
        }
        return array(
            'title'           => $title,
            'details'         => my_works_sanitize_details_html($CI->input->post('details')),
            'tag'             => my_works_normalize_tags($CI->input->post('tag')),
            'url'             => $url !== '' ? $url : null,
            'created_for'     => $created_for,
            'status'          => $status,
            'is_urgent'       => $CI->input->post('is_urgent') ? 1 : 0,
            'is_important'    => $CI->input->post('is_important') ? 1 : 0,
            'due_date'        => $due_date,
            'estimate_hours'  => $est,
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
            'estimate_hours'  => $CI->input->post('estimate_hours'),
            'client_id'       => $CI->input->post('client_id'),
            'project_id'      => $CI->input->post('project_id'),
            'work_type'       => $CI->input->post('work_type'),
            'closing_comment' => $CI->input->post('closing_comment'),
            'is_urgent'       => $CI->input->post('is_urgent'),
            'is_important' => $CI->input->post('is_important'),
        ));
    }
}

if (!function_exists('my_works_validate_quick_payload')) {
    /**
     * Minimal payload for toolbar quick-add (title, details, assignee).
     *
     * @return array|false
     */
    function my_works_validate_quick_payload($can_view_all, $user_id, $role_id)
    {
        $CI =& get_instance();
        $title = trim((string) $CI->input->post('title'));
        if ($title === '') {
            $CI->session->set_flashdata('error', 'Task title is required.');
            return false;
        }
        if (strlen($title) > 255) {
            $CI->session->set_flashdata('error', 'Task title must be 255 characters or fewer.');
            return false;
        }
        $created_for = my_works_validate_created_for((int) $CI->input->post('created_for'), $can_view_all, $user_id, $role_id);
        if ($created_for === false) {
            return false;
        }
        $details = my_works_sanitize_details_html($CI->input->post('details'));

        $CI->load->helper('estimate_hours');
        $est = estimate_hours_parse($CI->input->post('estimate_hours'));
        if ($est === false) {
            $CI->session->set_flashdata('error', 'Estimate (hrs) must be a number between 0 and 9999.99.');
            return false;
        }

        return array(
            'title'       => $title,
            'details'     => $details,
            'created_for' => $created_for,
            'status'      => 'new',
            'is_urgent'   => 0,
            'is_important'=> 0,
            'tag'         => null,
            'url'         => null,
            'due_date'    => null,
            'estimate_hours' => $est,
            'client_id'   => null,
            'project_id'  => null,
            'work_type'   => null,
            'closing_comment' => null,
        );
    }
}

if (!function_exists('my_works_flash_quick_add_old')) {
    function my_works_flash_quick_add_old()
    {
        $CI =& get_instance();
        $CI->session->set_flashdata('mw_quick_add_old', array(
            'title'       => $CI->input->post('title'),
            'details'     => $CI->input->post('details'),
            'created_for' => $CI->input->post('created_for'),
            'estimate_hours' => $CI->input->post('estimate_hours'),
        ));
    }
}
