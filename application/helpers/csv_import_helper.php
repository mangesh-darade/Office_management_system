<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('csv_import_file_error_message')) {
    function csv_import_file_error_message($code)
    {
        switch ((int) $code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'Uploaded file is too large.';
            case UPLOAD_ERR_PARTIAL:
                return 'Upload was interrupted. Please try again.';
            case UPLOAD_ERR_NO_FILE:
                return 'Please choose a CSV file to upload.';
            default:
                return 'Please upload a valid CSV file.';
        }
    }
}

if (!function_exists('csv_import_open')) {
    /**
     * @return array{ok:bool,error?:string,handle?:resource,map?:array,line?:int}
     */
    function csv_import_open($field = 'file')
    {
        if (!isset($_FILES[$field]) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
            return array('ok' => false, 'error' => 'Please upload a CSV file.');
        }
        if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return array('ok' => false, 'error' => csv_import_file_error_message($_FILES[$field]['error']));
        }
        $handle = fopen($_FILES[$field]['tmp_name'], 'r');
        if (!$handle) {
            return array('ok' => false, 'error' => 'Unable to read uploaded file.');
        }
        $header = fgetcsv($handle);
        if (!$header || !is_array($header)) {
            fclose($handle);
            return array('ok' => false, 'error' => 'CSV is empty or invalid.');
        }
        $map = array();
        foreach ($header as $i => $col) {
            $key = strtolower(trim((string) $col));
            if ($key !== '') {
                $map[$key] = (int) $i;
            }
        }
        if (empty($map)) {
            fclose($handle);
            return array('ok' => false, 'error' => 'CSV header row is missing or invalid.');
        }
        return array('ok' => true, 'handle' => $handle, 'map' => $map, 'line' => 1);
    }
}

if (!function_exists('csv_import_require_columns')) {
    /**
     * @param array $map
     * @param array $required e.g. ['title','name']
     * @param array $any_of e.g. [['project_name','project']]
     * @return array{ok:bool,error?:string}
     */
    function csv_import_require_columns($map, $required = array(), $any_of = array())
    {
        $missing = array();
        foreach ($required as $col) {
            if (!isset($map[$col])) {
                $missing[] = $col;
            }
        }
        foreach ($any_of as $group) {
            $found = false;
            foreach ($group as $col) {
                if (isset($map[$col])) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missing[] = implode(' or ', $group);
            }
        }
        if (!empty($missing)) {
            return array('ok' => false, 'error' => 'CSV must include columns: ' . implode(', ', $missing) . '.');
        }
        return array('ok' => true);
    }
}

if (!function_exists('csv_import_get')) {
    function csv_import_get($map, $row, $col, $default = '')
    {
        if (!isset($map[$col]) || !isset($row[$map[$col]])) {
            return $default;
        }
        return trim((string) $row[$map[$col]]);
    }
}

if (!function_exists('csv_import_get_any')) {
    function csv_import_get_any($map, $row, $columns, $default = '')
    {
        foreach ($columns as $col) {
            $val = csv_import_get($map, $row, $col, '');
            if ($val !== '') {
                return $val;
            }
        }
        return $default;
    }
}

if (!function_exists('csv_import_add_row_error')) {
    function csv_import_add_row_error(&$errors, $line, $message, $max = 10)
    {
        if (count($errors) >= $max) {
            return;
        }
        $errors[] = 'Row ' . (int) $line . ': ' . $message;
    }
}

if (!function_exists('csv_import_validate_enum')) {
    function csv_import_validate_enum($value, $allowed, $default, &$errors, $line, $label)
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return $default;
        }
        if (!in_array($value, $allowed, true)) {
            csv_import_add_row_error($errors, $line, 'Invalid ' . $label . ' "' . $value . '". Allowed: ' . implode(', ', $allowed) . '.');
            return false;
        }
        return $value;
    }
}

if (!function_exists('csv_import_resolve_project_id')) {
    function csv_import_resolve_project_id($db, $map, $row, &$cache)
    {
        $name = csv_import_get_any($map, $row, array('project_name', 'project'), '');
        if ($name !== '') {
            $key = strtolower($name);
            if (isset($cache[$key])) {
                return (int) $cache[$key];
            }
            $project = $db->select('id')
                ->from('projects')
                ->group_start()
                ->where('name', $name)
                ->or_where('code', $name)
                ->group_end()
                ->order_by('id', 'ASC')
                ->limit(1)
                ->get()
                ->row();
            $project_id = $project ? (int) $project->id : 0;
            $cache[$key] = $project_id;
            return $project_id;
        }
        $raw_id = csv_import_get($map, $row, 'project_id', '');
        if ($raw_id !== '') {
            return (int) $raw_id;
        }
        return 0;
    }
}

if (!function_exists('csv_import_resolve_client_id')) {
    function csv_import_resolve_client_id($db, $name, &$cache)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 0;
        }
        $key = strtolower($name);
        if (isset($cache[$key])) {
            return (int) $cache[$key];
        }
        if (!$db->table_exists('clients')) {
            $cache[$key] = 0;
            return 0;
        }
        $CI =& get_instance();
        if (!function_exists('schema_table_has_column')) {
            $CI->load->helper('schema_columns');
        }
        $db->from('clients')->select('id');
        $db->group_start()->where('company_name', $name);
        if (schema_table_has_column($db, 'clients', 'client_code')) {
            $db->or_where('client_code', $name);
        }
        $db->group_end();
        $client = $db->order_by('id', 'ASC')->limit(1)->get()->row();
        $client_id = $client ? (int) $client->id : 0;
        $cache[$key] = $client_id;
        return $client_id;
    }
}

if (!function_exists('csv_import_fail_redirect')) {
    function csv_import_fail_redirect($message, $default_route)
    {
        $CI =& get_instance();
        $CI->session->set_flashdata('error', (string) $message);
        $return_to = trim((string) $CI->input->post('return_to'));
        if (csv_import_safe_return_path($return_to)) {
            redirect($return_to);
            return;
        }
        redirect($default_route);
    }
}

if (!function_exists('csv_import_safe_return_path')) {
    function csv_import_safe_return_path($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return false;
        }
        if (preg_match('#^(https?:)?//#i', $path)) {
            return false;
        }
        if (strpos($path, '..') !== false) {
            return false;
        }
        return (bool) preg_match('#^projects/\d+(\?tab=(tasks|requirements|defects|releases))?$#', $path);
    }
}

if (!function_exists('csv_import_finish')) {
    /**
     * Set flash messages and redirect after CSV import.
     */
    function csv_import_finish($inserted, $skipped, $row_errors, $entity_label, $redirect_success, $redirect_import)
    {
        $CI =& get_instance();
        $return_to = trim((string) $CI->input->post('return_to'));
        $use_return = csv_import_safe_return_path($return_to);
        if ($inserted === 0) {
            $msg = 'No ' . $entity_label . ' were imported.';
            if (!empty($row_errors)) {
                $msg .= ' ' . implode(' ', array_slice($row_errors, 0, 3));
                if (count($row_errors) > 3) {
                    $msg .= ' (+' . (count($row_errors) - 3) . ' more — see list below)';
                }
            } else {
                $msg .= ' Check column headers and row data.';
            }
            $CI->session->set_flashdata('error', $msg);
            if (!empty($row_errors)) {
                $CI->session->set_flashdata('import_errors', array_slice($row_errors, 0, 15));
            }
            redirect($use_return ? $return_to : $redirect_import);
            return;
        }
        $msg = 'Imported ' . (int) $inserted . ' ' . $entity_label;
        if ($skipped > 0) {
            $msg .= '. ' . (int) $skipped . ' row(s) skipped.';
        }
        $CI->session->set_flashdata('success', $msg);
        if (!empty($row_errors)) {
            $CI->session->set_flashdata('import_errors', array_slice($row_errors, 0, 15));
        }
        redirect($use_return ? $return_to : $redirect_success);
    }
}
