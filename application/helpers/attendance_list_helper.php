<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Attendance list / popup helpers (monthly view, schema utilities).
 */

if (!function_exists('attendance_list_apply_user_status_tab')) {
    /**
     * Filter users query by active/inactive tab.
     *
     * @param CI_DB_query_builder $db
     * @param string $userAlias e.g. u
     * @param string $statusTab active|inactive
     */
    function attendance_list_apply_user_status_tab($db, $userAlias = 'u', $statusTab = 'active')
    {
        if (!function_exists('schema_table_has_column') || !schema_table_has_column($db, 'users', 'status')) {
            return;
        }

        $statusTab = ($statusTab === 'inactive') ? 'inactive' : 'active';
        $col = $userAlias . '.status';

        if ($statusTab === 'inactive') {
            $db->group_start();
            $db->where($col, 'inactive');
            $db->or_where($col, 0);
            $db->group_end();
            return;
        }

        $db->group_start();
        $db->where($col, 'active');
        $db->or_where($col, 1);
        $db->group_end();
    }
}

if (!function_exists('attendance_schema_column_type')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string $table
     * @param string $column
     * @return string
     */
    function attendance_schema_column_type($db, $table, $column)
    {
        try {
            foreach ($db->field_data($table) as $f) {
                if (isset($f->name) && $f->name === $column) {
                    return isset($f->type) ? strtolower($f->type) : '';
                }
            }
        } catch (Exception $e) {
            // Non-fatal on legacy installs.
        }

        return '';
    }
}

if (!function_exists('attendance_punch_is_empty_time')) {
    /**
     * @param mixed $value
     * @return bool
     */
    function attendance_punch_is_empty_time($value)
    {
        if (!isset($value)) {
            return true;
        }
        $s = trim((string) $value);
        if ($s === '' || $s === '0') {
            return true;
        }

        return in_array($s, array('00:00', '00:00:00', '0000-00-00', '0000-00-00 00:00:00'), true);
    }
}

if (!function_exists('attendance_punch_is_valid_checkout_time')) {
    /**
     * @param string $checkIn
     * @param string $checkOut
     * @param string $outType
     * @return bool
     */
    function attendance_punch_is_valid_checkout_time($checkIn, $checkOut, $outType)
    {
        if (empty($checkIn) || empty($checkOut)) {
            return false;
        }

        if (in_array($outType, array('time'), true)) {
            $checkInTime = strtotime('1970-01-01 ' . $checkIn);
            $checkOutTime = strtotime('1970-01-01 ' . $checkOut);
            if ($checkInTime === false || $checkOutTime === false) {
                return false;
            }
            $timeDiff = $checkOutTime - $checkInTime;

            return $timeDiff > 0 || $timeDiff < -12 * 3600;
        }

        $checkInTime = strtotime($checkIn);
        $checkOutTime = strtotime($checkOut);
        if ($checkInTime === false || $checkOutTime === false) {
            return false;
        }

        return $checkOutTime > $checkInTime;
    }
}

if (!function_exists('attendance_list_apply_date_filter')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string $col_date
     * @param string $filter_type
     * @param mixed $filter_value
     * @param string $alias
     * @return void
     */
    function attendance_list_apply_date_filter($db, $col_date, $filter_type, $filter_value, $alias = 'a')
    {
        $col = $alias . '.' . $col_date;
        switch ($filter_type) {
            case 'date':
                $db->where($col, $filter_value);
                break;
            case 'month':
                $db->where('DATE_FORMAT(' . $col . ', "%Y-%m") =', $filter_value);
                break;
            case 'year':
                $db->where('YEAR(' . $col . ')', $filter_value);
                break;
            default:
                $db->where('DATE_FORMAT(' . $col . ', "%Y-%m") =', date('Y-m'));
                break;
        }
    }
}

if (!function_exists('attendance_list_resolve_display_status')) {
    /**
     * @param object $record
     * @param string $cin
     * @param string $cout
     * @return string
     */
    function attendance_list_resolve_display_status($record, $cin, $cout)
    {
        $status = isset($record->status) ? $record->status : 'incomplete';
        if ($status !== 'incomplete' && !empty($status)) {
            return $status;
        }
        if ($cin && $cout) {
            return 'present';
        }
        if ($cin && !$cout) {
            return 'incomplete';
        }

        return 'absent';
    }
}

if (!function_exists('attendance_list_format_time_display')) {
    /**
     * @param string $value
     * @return string
     */
    function attendance_list_format_time_display($value)
    {
        $value = attendance_punch_normalize_time($value);
        if ($value && strpos($value, ' ') !== false) {
            return trim(explode(' ', $value)[1]);
        }

        return $value;
    }
}

if (!function_exists('attendance_list_checkin_location_label')) {
    function attendance_list_checkin_location_label($record, $cin)
    {
        if (isset($record->checkin_location_name) && !empty($record->checkin_location_name)) {
            return $record->checkin_location_name;
        }
        if (isset($record->checkin_lat, $record->checkin_lng) && !empty($record->checkin_lat) && !empty($record->checkin_lng)) {
            return $record->checkin_lat . ', ' . $record->checkin_lng;
        }
        if (isset($record->location_name) && !empty($record->location_name) && $cin) {
            return $record->location_name;
        }

        return '';
    }
}

if (!function_exists('attendance_list_checkout_location_label')) {
    function attendance_list_checkout_location_label($record, $cout)
    {
        if (isset($record->checkout_location_name) && !empty($record->checkout_location_name)) {
            return $record->checkout_location_name;
        }
        if (isset($record->checkout_lat, $record->checkout_lng) && !empty($record->checkout_lat) && !empty($record->checkout_lng)) {
            return $record->checkout_lat . ', ' . $record->checkout_lng;
        }
        if (isset($record->location_name) && !empty($record->location_name) && $cout) {
            return $record->location_name;
        }

        return '';
    }
}

if (!function_exists('attendance_list_popup_row')) {
    /**
     * @param object $record
     * @param string $col_date
     * @param int $current_user_id
     * @param int $current_role_id
     * @return array
     */
    function attendance_list_popup_row($record, $col_date, $current_user_id, $current_role_id)
    {
        $cin = isset($record->punch_in) ? $record->punch_in : (isset($record->check_in) ? $record->check_in : '');
        $cout = isset($record->punch_out) ? $record->punch_out : (isset($record->check_out) ? $record->check_out : '');
        $cin = attendance_punch_normalize_time($cin);
        $cout = attendance_punch_normalize_time($cout);

        $is_admin = is_admin_group() || has_module_access('attendance_view_all');
        $can_edit = has_module_access('attendance_edit') || has_module_access('attendance');
        $can_delete = has_module_access('attendance_delete') || has_module_access('attendance');
        $record_user_id = isset($record->user_id) ? (int) $record->user_id : 0;

        return array(
            'id'                => $record->id,
            'user_id'           => $record_user_id,
            'date'              => isset($record->$col_date) ? $record->$col_date : '',
            'check_in'          => attendance_list_format_time_display($cin),
            'check_out'         => attendance_list_format_time_display($cout),
            'status'            => attendance_list_resolve_display_status($record, $cin, $cout),
            'notes'             => isset($record->notes) ? $record->notes : '',
            'location'          => isset($record->location_name) ? $record->location_name : '',
            'checkin_location'  => attendance_list_checkin_location_label($record, $cin),
            'checkout_location' => attendance_list_checkout_location_label($record, $cout),
            'can_edit'          => $can_edit && ($is_admin || $record_user_id === $current_user_id),
            'can_delete'        => $can_delete && ($is_admin || $record_user_id === $current_user_id),
        );
    }
}

if (!function_exists('attendance_list_fetch_user_popup')) {
    /**
     * @param CI_DB_query_builder $db
     * @param int $user_id
     * @param string $col_date
     * @param string $filter_type
     * @param mixed $filter_value
     * @param int $page
     * @param int $per_page
     * @return array{records:array,total:int,total_pages:int}
     */
    function attendance_list_fetch_user_popup($db, $user_id, $col_date, $filter_type, $filter_value, $page, $per_page)
    {
        $db->from('attendance a');
        $db->where('a.user_id', (int) $user_id);
        attendance_list_apply_date_filter($db, $col_date, $filter_type, $filter_value);
        $total = (int) $db->count_all_results();

        $total_pages = $per_page > 0 ? (int) ceil($total / $per_page) : 0;
        $offset = max(0, ($page - 1) * $per_page);

        $db->select('a.*, u.email');
        $db->from('attendance a');
        $db->join('users u', 'u.id = a.user_id', 'left');
        $db->where('a.user_id', (int) $user_id);
        attendance_list_apply_date_filter($db, $col_date, $filter_type, $filter_value);
        $records = $db->order_by('a.' . $col_date, 'DESC')
            ->limit($per_page, $offset)
            ->get()
            ->result();

        return array(
            'records'     => $records,
            'total'       => $total,
            'total_pages' => $total_pages,
        );
    }
}

if (!function_exists('attendance_list_calculate_statistics')) {
    /**
     * Legacy dashboard stats helper (currently unused by controllers).
     *
     * @param CI_DB_query_builder $db
     * @param int $user_id
     * @param int $role_id
     * @return array
     */
    function attendance_list_calculate_statistics($db, $user_id, $role_id)
    {
        $db->from('attendance a');
        $db->join('users u', 'u.id = a.user_id', 'left');
        $db->join('employees e', 'e.user_id = a.user_id', 'left');
        apply_role_hierarchy_filter($db, 'a.user_id', $user_id, $role_id);

        $all_records = $db->get()->result();
        $stats = array(
            'total_records'    => count($all_records),
            'present_today'    => 0,
            'pending_checkout' => 0,
            'absent_today'     => 0,
            'attendance_rate'  => 0,
        );

        $today = date('Y-m-d');
        foreach ($all_records as $record) {
            $cin = isset($record->punch_in) ? $record->punch_in : (isset($record->check_in) ? $record->check_in : '');
            $cout = isset($record->punch_out) ? $record->punch_out : (isset($record->check_out) ? $record->check_out : '');
            $att_date = isset($record->att_date) ? $record->att_date : (isset($record->date) ? $record->date : '');

            if (!empty($cin) && $cin !== '00:00:00') {
                $stats['present_today']++;
            }
            if (!empty($cin) && (empty($cout) || $cout === '00:00:00')) {
                $stats['pending_checkout']++;
            }
            if ($att_date === $today && (empty($cin) || $cin === '00:00:00')) {
                $stats['absent_today']++;
            }
        }

        $total_expected = $stats['present_today'] + $stats['absent_today'];
        if ($total_expected > 0) {
            $stats['attendance_rate'] = round(($stats['present_today'] / $total_expected) * 100, 1);
        }

        return $stats;
    }
}
