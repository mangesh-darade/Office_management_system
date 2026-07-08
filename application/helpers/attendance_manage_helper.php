<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('attendance_manage_load_dependencies')) {
    function attendance_manage_load_dependencies()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        $CI =& get_instance();
        if (!function_exists('attendance_punch_resolve_time_columns')) {
            $CI->load->helper('attendance_punch');
        }
        if (!function_exists('attendance_schema_column_type')) {
            $CI->load->helper('attendance_list');
        }
        if (!function_exists('schema_table_has_column')) {
            $CI->load->helper('schema_columns');
        }
        if (!function_exists('apply_role_hierarchy_filter')) {
            $CI->load->helper('hierarchy_filter');
        }

        $loaded = true;
    }
}

if (!function_exists('attendance_manage_has_column_fn')) {
    function attendance_manage_has_column_fn($db)
    {
        return function ($column) use ($db) {
            return $db->field_exists($column, 'attendance');
        };
    }
}

if (!function_exists('attendance_manage_resolve_context')) {
    function attendance_manage_resolve_context($db)
    {
        attendance_manage_load_dependencies();
        $has_column = attendance_manage_has_column_fn($db);
        $time_cols = attendance_punch_resolve_time_columns($has_column);

        return array(
            'has_column'   => $has_column,
            'col_date'     => $time_cols['col_date'],
            'col_in'       => $time_cols['col_in'],
            'col_out'      => $time_cols['col_out'],
            'has_punch_in' => $time_cols['hasPunchIn'],
            'has_check_in' => $time_cols['hasCheckIn'],
            'has_punch_out'=> $time_cols['hasPunchOut'],
            'has_check_out'=> $time_cols['hasCheckOut'],
            'has_status'   => $has_column('status'),
            'has_notes'    => $has_column('notes'),
            'has_checkin_location'  => $has_column('checkin_location_name'),
            'has_checkout_location' => $has_column('checkout_location_name'),
            'has_checkin_lat'       => $has_column('checkin_lat'),
            'has_checkin_lng'       => $has_column('checkin_lng'),
            'has_checkout_lat'      => $has_column('checkout_lat'),
            'has_checkout_lng'      => $has_column('checkout_lng'),
            'has_location_name'     => $has_column('location_name'),
            'get_col_type' => function ($table, $column) use ($db) {
                return attendance_schema_column_type($db, $table, $column);
            },
        );
    }
}

if (!function_exists('attendance_manage_normalize_input_time')) {
    function attendance_manage_normalize_input_time($time)
    {
        $time = trim((string) $time);
        if ($time === '') {
            return '';
        }
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        return '';
    }
}

if (!function_exists('attendance_manage_value_for_time_column')) {
    function attendance_manage_value_for_time_column($get_col_type, $column, $date, $time)
    {
        $time = attendance_manage_normalize_input_time($time);
        if ($time === '') {
            return null;
        }

        $type = $get_col_type('attendance', $column);
        if (in_array($type, array('datetime', 'timestamp'), true)) {
            return $date . ' ' . $time;
        }

        return $time;
    }
}

if (!function_exists('attendance_manage_extract_time_display')) {
    function attendance_manage_extract_time_display($value)
    {
        $value = attendance_punch_normalize_time($value);
        if ($value === '') {
            return '';
        }
        if (strpos($value, ' ') !== false) {
            $parts = explode(' ', $value);
            $time = trim(end($parts));
            return strlen($time) >= 5 ? substr($time, 0, 5) : $time;
        }

        return strlen($value) >= 5 ? substr($value, 0, 5) : $value;
    }
}

if (!function_exists('attendance_manage_fetch_users')) {
    function attendance_manage_fetch_users($db)
    {
        attendance_manage_load_dependencies();
        if (!$db->table_exists('users')) {
            return array();
        }

        $db->select('u.id, u.name, u.email');
        $db->from('users u');
        if (schema_table_has_column($db, 'users', 'status')) {
            $db->where('u.status', 'active');
        }
        $db->order_by('u.name', 'ASC');

        return $db->get()->result();
    }
}

if (!function_exists('attendance_manage_fetch_records')) {
    function attendance_manage_fetch_records($db, array $filters, $current_user_id, $current_role_id)
    {
        attendance_manage_load_dependencies();
        if (!$db->table_exists('attendance')) {
            return array();
        }

        $ctx = attendance_manage_resolve_context($db);
        $col_date = $ctx['col_date'];

        $db->select('a.*, u.name as user_name, u.email as user_email');
        $db->from('attendance a');
        $db->join('users u', 'u.id = a.user_id', 'left');

        if (function_exists('apply_role_hierarchy_filter')) {
            apply_role_hierarchy_filter($db, 'a.user_id', $current_user_id, $current_role_id);
        }

        if (!empty($filters['user_id'])) {
            $db->where('a.user_id', (int) $filters['user_id']);
        }
        if (!empty($filters['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['from_date'])) {
            $db->where('a.' . $col_date . ' >=', $filters['from_date']);
        }
        if (!empty($filters['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['to_date'])) {
            $db->where('a.' . $col_date . ' <=', $filters['to_date']);
        }

        return $db->order_by('a.' . $col_date, 'DESC')
            ->limit(250)
            ->get()
            ->result();
    }
}

if (!function_exists('attendance_manage_find_existing_for_date')) {
    function attendance_manage_find_existing_for_date($db, $user_id, $date, $exclude_id = 0)
    {
        attendance_manage_load_dependencies();
        $ctx = attendance_manage_resolve_context($db);
        $db->from('attendance');
        $db->where('user_id', (int) $user_id);
        $db->where($ctx['col_date'], $date);
        if ((int) $exclude_id > 0) {
            $db->where('id !=', (int) $exclude_id);
        }

        return $db->limit(1)->get()->row();
    }
}

if (!function_exists('attendance_manage_validate_form')) {
    function attendance_manage_validate_form(array $post, $is_edit = false)
    {
        $errors = array();

        $user_id = isset($post['user_id']) ? (int) $post['user_id'] : 0;
        if (!$is_edit && $user_id <= 0) {
            $errors['user_id'] = 'Please select an employee.';
        }

        $att_date = isset($post['att_date']) ? trim((string) $post['att_date']) : '';
        if ($att_date === '') {
            $errors['att_date'] = 'Attendance date is required.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $att_date)) {
            $errors['att_date'] = 'Invalid date format. Use YYYY-MM-DD.';
        } else {
            $d = date_create_from_format('Y-m-d', $att_date);
            if (!$d || $d->format('Y-m-d') !== $att_date) {
                $errors['att_date'] = 'Invalid attendance date.';
            }
        }

        $check_in = isset($post['check_in']) ? trim((string) $post['check_in']) : '';
        $check_out = isset($post['check_out']) ? trim((string) $post['check_out']) : '';

        if ($check_in !== '' && attendance_manage_normalize_input_time($check_in) === '') {
            $errors['check_in'] = 'Invalid check-in time.';
        }
        if ($check_out !== '' && attendance_manage_normalize_input_time($check_out) === '') {
            $errors['check_out'] = 'Invalid check-out time.';
        }

        if ($check_in !== '' && $check_out !== '') {
            $in_norm = attendance_manage_normalize_input_time($check_in);
            $out_norm = attendance_manage_normalize_input_time($check_out);
            if (strtotime($att_date . ' ' . $in_norm) !== false
                && strtotime($att_date . ' ' . $out_norm) !== false
                && strtotime($att_date . ' ' . $out_norm) < strtotime($att_date . ' ' . $in_norm)) {
                $errors['check_out'] = 'Check-out time cannot be before check-in time.';
            }
        }

        return $errors;
    }
}

if (!function_exists('attendance_manage_build_save_data')) {
    function attendance_manage_build_save_data($db, array $post, $target_user_id = 0)
    {
        attendance_manage_load_dependencies();
        $ctx = attendance_manage_resolve_context($db);
        $get_col_type = $ctx['get_col_type'];
        $att_date = trim((string) $post['att_date']);
        $check_in = isset($post['check_in']) ? trim((string) $post['check_in']) : '';
        $check_out = isset($post['check_out']) ? trim((string) $post['check_out']) : '';
        $notes = isset($post['notes']) ? trim((string) $post['notes']) : '';

        $data = array(
            $ctx['col_date'] => $att_date,
        );

        if ($target_user_id > 0) {
            $data['user_id'] = (int) $target_user_id;
        }

        $in_value = attendance_manage_value_for_time_column($get_col_type, $ctx['col_in'], $att_date, $check_in);
        $out_value = attendance_manage_value_for_time_column($get_col_type, $ctx['col_out'], $att_date, $check_out);

        if ($ctx['has_punch_in']) {
            $data['punch_in'] = $in_value;
        }
        if ($ctx['has_check_in']) {
            $data['check_in'] = $in_value;
        }
        if (!$ctx['has_punch_in'] && !$ctx['has_check_in']) {
            $data[$ctx['col_in']] = $in_value;
        }

        if ($ctx['has_punch_out']) {
            $data['punch_out'] = $out_value;
        }
        if ($ctx['has_check_out']) {
            $data['check_out'] = $out_value;
        }
        if (!$ctx['has_punch_out'] && !$ctx['has_check_out']) {
            $data[$ctx['col_out']] = $out_value;
        }

        if ($ctx['has_status']) {
            $status = isset($post['status']) ? strtolower(trim((string) $post['status'])) : 'present';
            $allowed = array('present', 'absent', 'late', 'half_day', 'early_leave', 'work_from_home', 'holiday', 'on_leave');
            if (!in_array($status, $allowed, true)) {
                $status = 'present';
            }
            $data['status'] = $status;
        }

        if ($ctx['has_notes']) {
            $data['notes'] = $notes;
        }

        $check_in_loc = isset($post['check_in_location']) ? trim((string) $post['check_in_location']) : '';
        $check_out_loc = isset($post['check_out_location']) ? trim((string) $post['check_out_location']) : '';

        if ($ctx['has_checkin_location']) {
            $data['checkin_location_name'] = $check_in_loc !== '' ? mb_substr($check_in_loc, 0, 255) : null;
        }
        if ($ctx['has_checkout_location']) {
            $data['checkout_location_name'] = $check_out_loc !== '' ? mb_substr($check_out_loc, 0, 255) : null;
        }

        $check_in_lat = isset($post['check_in_lat']) ? trim((string) $post['check_in_lat']) : '';
        $check_in_lng = isset($post['check_in_lng']) ? trim((string) $post['check_in_lng']) : '';
        $check_out_lat = isset($post['check_out_lat']) ? trim((string) $post['check_out_lat']) : '';
        $check_out_lng = isset($post['check_out_lng']) ? trim((string) $post['check_out_lng']) : '';

        if ($ctx['has_checkin_lat']) {
            $data['checkin_lat'] = $check_in_lat !== '' ? $check_in_lat : null;
        }
        if ($ctx['has_checkin_lng']) {
            $data['checkin_lng'] = $check_in_lng !== '' ? $check_in_lng : null;
        }
        if ($ctx['has_checkout_lat']) {
            $data['checkout_lat'] = $check_out_lat !== '' ? $check_out_lat : null;
        }
        if ($ctx['has_checkout_lng']) {
            $data['checkout_lng'] = $check_out_lng !== '' ? $check_out_lng : null;
        }

        if ($db->field_exists('updated_at', 'attendance')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return $data;
    }
}

if (!function_exists('attendance_manage_row_display')) {
    function attendance_manage_row_display($record, array $ctx)
    {
        attendance_manage_load_dependencies();
        $col_date = $ctx['col_date'];
        $col_in = $ctx['col_in'];
        $col_out = $ctx['col_out'];

        $date_val = isset($record->$col_date) ? (string) $record->$col_date : '';
        $in_raw = isset($record->$col_in) ? $record->$col_in : '';
        if ($in_raw === '' && isset($record->punch_in)) {
            $in_raw = $record->punch_in;
        }
        if ($in_raw === '' && isset($record->check_in)) {
            $in_raw = $record->check_in;
        }

        $out_raw = isset($record->$col_out) ? $record->$col_out : '';
        if ($out_raw === '' && isset($record->punch_out)) {
            $out_raw = $record->punch_out;
        }
        if ($out_raw === '' && isset($record->check_out)) {
            $out_raw = $record->check_out;
        }

        $cin_display = attendance_manage_extract_time_display($in_raw);
        $cout_display = attendance_manage_extract_time_display($out_raw);

        $check_in_location = '';
        $check_out_location = '';
        if (function_exists('attendance_list_checkin_location_label')) {
            $check_in_location = attendance_list_checkin_location_label($record, $in_raw);
        } elseif (isset($record->checkin_location_name) && $record->checkin_location_name !== '') {
            $check_in_location = (string) $record->checkin_location_name;
        }

        if (function_exists('attendance_list_checkout_location_label')) {
            $check_out_location = attendance_list_checkout_location_label($record, $out_raw);
        } elseif (isset($record->checkout_location_name) && $record->checkout_location_name !== '') {
            $check_out_location = (string) $record->checkout_location_name;
        }

        return array(
            'date'              => $date_val,
            'check_in'          => $cin_display,
            'check_out'         => $cout_display,
            'check_in_location' => $check_in_location,
            'check_out_location'=> $check_out_location,
            'check_in_lat'      => isset($record->checkin_lat) ? (string) $record->checkin_lat : '',
            'check_in_lng'      => isset($record->checkin_lng) ? (string) $record->checkin_lng : '',
            'check_out_lat'     => isset($record->checkout_lat) ? (string) $record->checkout_lat : '',
            'check_out_lng'     => isset($record->checkout_lng) ? (string) $record->checkout_lng : '',
            'status'            => isset($record->status) ? (string) $record->status : '',
            'notes'             => isset($record->notes) ? (string) $record->notes : '',
        );
    }
}
