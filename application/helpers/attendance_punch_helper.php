<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helpers for attendance punch-in/out (extracted from Attendance controller).
 */

if (!function_exists('attendance_punch_active_holiday')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string $date Y-m-d
     * @return object|null
     */
    function attendance_punch_active_holiday($db, $date)
    {
        if (!$db->table_exists('holidays')) {
            return null;
        }

        $row = $db->select('name, status')
            ->from('holidays')
            ->where('holiday_date', $date)
            ->limit(1)
            ->get()
            ->row();

        if ($row && isset($row->status) && $row->status === 'active') {
            return $row;
        }

        return null;
    }
}

if (!function_exists('attendance_punch_holiday_block_message')) {
    /**
     * @param object|null $holiday
     * @return string
     */
    function attendance_punch_holiday_block_message($holiday)
    {
        $msg = 'Attendance cannot be marked on company holidays.';
        if ($holiday && isset($holiday->name) && trim((string) $holiday->name) !== '') {
            $msg .= ' Today is: ' . trim((string) $holiday->name) . '.';
        }

        return $msg;
    }
}

if (!function_exists('attendance_punch_normalize_time')) {
    /**
     * @param mixed $value
     * @return string
     */
    function attendance_punch_normalize_time($value)
    {
        $value = trim((string) $value);
        if ($value === '00:00:00' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        return $value;
    }
}

if (!function_exists('attendance_punch_validate_location_strict')) {
    /**
     * Validate punch coordinates against office geofence when strict mode is on.
     *
     * @param object $settings Setting_model or compatible get_setting()
     * @param mixed  $lat
     * @param mixed  $lng
     * @param callable $distance_fn function($lat1,$lon1,$lat2,$lon2): float meters
     * @return array{ok:bool,error?:string,distance?:float,radius?:float}
     */
    function attendance_punch_validate_location_strict($settings, $lat, $lng, callable $distance_fn)
    {
        if ($settings->get_setting('system_enable_location_strict', 'no') !== 'yes') {
            return array('ok' => true);
        }

        $office_lat = $settings->get_setting('system_office_latitude', '');
        $office_lng = $settings->get_setting('system_office_longitude', '');
        $allowed_radius = (float) $settings->get_setting('system_attendance_radius_meters', 100);

        if ($office_lat === '' || $office_lng === '') {
            return array('ok' => true);
        }

        $distance = (float) $distance_fn($lat, $lng, $office_lat, $office_lng);
        if ($distance > $allowed_radius) {
            return array(
                'ok'       => false,
                'distance' => $distance,
                'radius'   => $allowed_radius,
            );
        }

        return array('ok' => true, 'distance' => $distance, 'radius' => $allowed_radius);
    }
}

if (!function_exists('attendance_punch_merge_geo_fields')) {
    /**
     * Merge latitude/longitude/location columns into punch data when columns exist.
     *
     * @param callable $has_column function(string $column): bool
     * @param array    $data       By reference
     * @param mixed    $lat
     * @param mixed    $lng
     * @param mixed    $location_name
     * @param string   $action     in|out
     * @param callable|null $resolve_location_name function($lat,$lng,$postName): ?string
     * @return void
     */
    function attendance_punch_merge_geo_fields(
        callable $has_column,
        array &$data,
        $lat,
        $lng,
        $location_name,
        $action,
        callable $resolve_location_name = null
    ) {
        if ($has_column('latitude')) { $data['latitude'] = $lat; }
        if ($has_column('longitude')) { $data['longitude'] = $lng; }
        if ($has_column('lat')) { $data['lat'] = $lat; }
        if ($has_column('lng')) { $data['lng'] = $lng; }
        if ($has_column('geo_lat')) { $data['geo_lat'] = $lat; }
        if ($has_column('geo_lng')) { $data['geo_lng'] = $lng; }

        $locName = null;
        if ($resolve_location_name !== null) {
            $locName = $resolve_location_name($lat, $lng, $location_name);
        }

        if ($has_column('location_name') && $locName) {
            $data['location_name'] = $locName;
        }

        if ($action === 'in') {
            if ($has_column('checkin_lat')) { $data['checkin_lat'] = $lat; }
            if ($has_column('checkin_lng')) { $data['checkin_lng'] = $lng; }
            if ($has_column('checkin_location_name') && $locName) {
                $data['checkin_location_name'] = $locName;
            }
        }

        if ($action === 'out') {
            if ($has_column('checkout_lat')) { $data['checkout_lat'] = $lat; }
            if ($has_column('checkout_lng')) { $data['checkout_lng'] = $lng; }
            if ($has_column('checkout_location_name') && $locName) {
                $data['checkout_location_name'] = $locName;
            }
        }
    }
}

if (!function_exists('attendance_punch_read_existing_times')) {
    /**
     * @param object $existing
     * @param string $col_in
     * @param string $col_out
     * @param bool   $hasPunchIn
     * @param bool   $hasCheckIn
     * @param bool   $hasPunchOut
     * @param bool   $hasCheckOut
     * @return array{cin:string,cout:string}
     */
    function attendance_punch_read_existing_times(
        $existing,
        $col_in,
        $col_out,
        $hasPunchIn,
        $hasCheckIn,
        $hasPunchOut,
        $hasCheckOut
    ) {
        $cin = '';
        if ($hasPunchIn && isset($existing->punch_in) && !empty($existing->punch_in)) {
            $cin = $existing->punch_in;
        } elseif ($hasCheckIn && isset($existing->check_in) && !empty($existing->check_in)) {
            $cin = $existing->check_in;
        } elseif (isset($existing->$col_in)) {
            $cin = $existing->$col_in;
        }

        $cout = '';
        if ($hasPunchOut && isset($existing->punch_out) && !empty($existing->punch_out)) {
            $cout = $existing->punch_out;
        } elseif ($hasCheckOut && isset($existing->check_out) && !empty($existing->check_out)) {
            $cout = $existing->check_out;
        } elseif (isset($existing->$col_out)) {
            $cout = $existing->$col_out;
        }

        return array(
            'cin'  => attendance_punch_normalize_time($cin),
            'cout' => attendance_punch_normalize_time($cout),
        );
    }
}

if (!function_exists('attendance_punch_resolve_time_columns')) {
    /**
     * Resolve legacy attendance date/in/out column names.
     *
     * @param callable $has_column function(string $column): bool
     * @return array{col_date:string,col_in:string,col_out:string,hasPunchIn:bool,hasCheckIn:bool,hasPunchOut:bool,hasCheckOut:bool}
     */
    function attendance_punch_resolve_time_columns(callable $has_column)
    {
        $col_date = $has_column('att_date') ? 'att_date' : 'date';
        $col_in = $has_column('punch_in') ? 'punch_in' : 'check_in';
        $col_out = $has_column('punch_out') ? 'punch_out' : 'check_out';

        return array(
            'col_date'    => $col_date,
            'col_in'      => $col_in,
            'col_out'     => $col_out,
            'hasPunchIn'  => $has_column('punch_in'),
            'hasCheckIn'  => $has_column('check_in'),
            'hasPunchOut' => $has_column('punch_out'),
            'hasCheckOut' => $has_column('check_out'),
        );
    }
}

if (!function_exists('attendance_punch_time_for_column')) {
    /**
     * @param callable $get_column_type function(string $table, string $column): string
     */
    function attendance_punch_time_for_column(
        callable $get_column_type,
        $table,
        $column,
        $nowDateTime,
        $nowTime
    ) {
        $type = $get_column_type($table, $column);

        return (in_array($type, array('datetime', 'timestamp'), true)) ? $nowDateTime : $nowTime;
    }
}

if (!function_exists('attendance_punch_apply_check_in_columns')) {
    function attendance_punch_apply_check_in_columns(
        callable $get_column_type,
        array &$target,
        $nowDateTime,
        $nowTime,
        $hasPunchIn,
        $hasCheckIn,
        $col_in
    ) {
        if ($hasPunchIn) {
            $target['punch_in'] = attendance_punch_time_for_column(
                $get_column_type,
                'attendance',
                'punch_in',
                $nowDateTime,
                $nowTime
            );
        }
        if ($hasCheckIn) {
            $target['check_in'] = attendance_punch_time_for_column(
                $get_column_type,
                'attendance',
                'check_in',
                $nowDateTime,
                $nowTime
            );
        }
        if (!$hasPunchIn && !$hasCheckIn) {
            $target[$col_in] = attendance_punch_time_for_column(
                $get_column_type,
                'attendance',
                $col_in,
                $nowDateTime,
                $nowTime
            );
        }
    }
}

if (!function_exists('attendance_punch_apply_check_out_columns')) {
    function attendance_punch_apply_check_out_columns(
        callable $get_column_type,
        array &$target,
        $nowDateTime,
        $nowTime,
        $hasPunchOut,
        $hasCheckOut,
        $col_out
    ) {
        if ($hasPunchOut) {
            $target['punch_out'] = attendance_punch_time_for_column(
                $get_column_type,
                'attendance',
                'punch_out',
                $nowDateTime,
                $nowTime
            );
        }
        if ($hasCheckOut) {
            $target['check_out'] = attendance_punch_time_for_column(
                $get_column_type,
                'attendance',
                'check_out',
                $nowDateTime,
                $nowTime
            );
        }
        if (!$hasPunchOut && !$hasCheckOut) {
            $target[$col_out] = attendance_punch_time_for_column(
                $get_column_type,
                'attendance',
                $col_out,
                $nowDateTime,
                $nowTime
            );
        }
    }
}

if (!function_exists('attendance_punch_proposed_checkout_time')) {
    function attendance_punch_proposed_checkout_time(
        callable $get_column_type,
        $nowDateTime,
        $nowTime,
        $hasPunchOut,
        $hasCheckOut,
        $col_out
    ) {
        if ($hasPunchOut) {
            return attendance_punch_time_for_column(
                $get_column_type,
                'attendance',
                'punch_out',
                $nowDateTime,
                $nowTime
            );
        }
        if ($hasCheckOut) {
            return attendance_punch_time_for_column(
                $get_column_type,
                'attendance',
                'check_out',
                $nowDateTime,
                $nowTime
            );
        }

        return attendance_punch_time_for_column(
            $get_column_type,
            'attendance',
            $col_out,
            $nowDateTime,
            $nowTime
        );
    }
}

if (!function_exists('attendance_punch_shift_check_in_status')) {
    /**
     * @param object|null $shift
     * @return string|null present|late
     */
    function attendance_punch_shift_check_in_status($shift, $nowDateTime, $today)
    {
        if (!$shift) {
            return null;
        }

        $checkInTimeObj = new DateTime($nowDateTime);
        $shiftStartTime = new DateTime($today . ' ' . $shift->start_time);
        $lateGrace = new DateInterval('PT' . (int) $shift->late_grace_period . 'M');
        $shiftStartTime->add($lateGrace);

        return ($checkInTimeObj > $shiftStartTime) ? 'late' : 'present';
    }
}

if (!function_exists('attendance_punch_apply_early_leave_status')) {
    function attendance_punch_apply_early_leave_status(
        array &$updates,
        $shift,
        $proposedOut,
        $today,
        $currentStatus
    ) {
        if (!$shift) {
            return;
        }

        $checkOutTimeObj = new DateTime($proposedOut);
        $shiftEndTime = new DateTime($today . ' ' . $shift->end_time);
        $earlyGrace = new DateInterval('PT' . (int) $shift->early_exit_grace_period . 'M');
        $shiftEndTime->sub($earlyGrace);

        if ($checkOutTimeObj < $shiftEndTime && $currentStatus !== 'late') {
            $updates['status'] = 'early_leave';
        }
    }
}

if (!function_exists('attendance_punch_check_in_notes')) {
    /**
     * @return string|null
     */
    function attendance_punch_check_in_notes($input_notes)
    {
        $input_notes = trim((string) $input_notes);

        return ($input_notes !== '') ? 'Check-In: ' . $input_notes : null;
    }
}

if (!function_exists('attendance_punch_check_out_notes')) {
    /**
     * @return string|null
     */
    function attendance_punch_check_out_notes($existing_notes, $input_notes)
    {
        $input_notes = trim((string) $input_notes);
        if ($input_notes === '') {
            return null;
        }

        $existing_notes = trim((string) $existing_notes);
        if ($existing_notes !== '') {
            return $existing_notes . ' | Check-Out: ' . $input_notes;
        }

        return 'Check-Out: ' . $input_notes;
    }
}

if (!function_exists('attendance_punch_copy_keyed_fields')) {
    function attendance_punch_copy_keyed_fields(array &$updates, array $data, array $fields)
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }
    }
}

if (!function_exists('attendance_punch_copy_set_fields')) {
    function attendance_punch_copy_set_fields(
        array &$updates,
        array $data,
        array $fields,
        callable $has_column
    ) {
        foreach ($fields as $field) {
            if (isset($data[$field]) && $has_column($field)) {
                $updates[$field] = $data[$field];
            }
        }
    }
}

if (!function_exists('attendance_punch_copy_isset_fields')) {
    function attendance_punch_copy_isset_fields(array &$updates, array $data, array $fields)
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updates[$field] = $data[$field];
            }
        }
    }
}

if (!function_exists('attendance_punch_build_race_check_in_updates')) {
    function attendance_punch_build_race_check_in_updates(
        callable $getColType,
        callable $hasColumn,
        array $data,
        $nowDateTime,
        $nowTime,
        $hasPunchIn,
        $hasCheckIn,
        $col_in,
        $input_notes,
        array $geoGroups
    ) {
        $updates = array();
        attendance_punch_apply_check_in_columns(
            $getColType,
            $updates,
            $nowDateTime,
            $nowTime,
            $hasPunchIn,
            $hasCheckIn,
            $col_in
        );
        $checkInNotes = attendance_punch_check_in_notes($input_notes);
        if ($checkInNotes !== null) {
            $updates['notes'] = $checkInNotes;
        }
        attendance_punch_copy_isset_fields($updates, $data, array('attachment_path', 'ip_address'));
        attendance_punch_copy_set_fields(
            $updates,
            $data,
            array_merge($geoGroups['shared'], $geoGroups['in']),
            $hasColumn
        );

        return $updates;
    }
}

if (!function_exists('attendance_punch_geo_field_groups')) {
    /**
     * @return array{shared:array,in:array,out:array}
     */
    function attendance_punch_geo_field_groups()
    {
        return array(
            'shared' => array('latitude', 'longitude', 'lat', 'lng', 'geo_lat', 'geo_lng', 'location_name'),
            'in'     => array('checkin_lat', 'checkin_lng', 'checkin_location_name'),
            'out'    => array('checkout_lat', 'checkout_lng', 'checkout_location_name'),
        );
    }
}

if (!function_exists('attendance_punch_column_map')) {
    /**
     * @param CI_DB_query_builder $db
     * @return array<string,bool>
     */
    function attendance_punch_column_map($db)
    {
        static $cache = null;
        static $cache_key = null;

        $key = spl_object_hash($db);
        if ($cache === null || $cache_key !== $key) {
            $cache = array();
            $cache_key = $key;
            if ($db->table_exists('attendance')) {
                foreach ($db->list_fields('attendance') as $col) {
                    $cache[$col] = true;
                }
            }
        }

        return $cache;
    }
}

if (!function_exists('attendance_punch_has_column')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string $field
     * @return bool
     */
    function attendance_punch_has_column($db, $field)
    {
        $map = attendance_punch_column_map($db);

        return isset($map[$field]);
    }
}

if (!function_exists('attendance_punch_resolve_date_column')) {
    /**
     * @param callable $has_column
     * @param array|null $fallbacks
     * @return string
     */
    function attendance_punch_resolve_date_column(callable $has_column, array $fallbacks = null)
    {
        if ($fallbacks === null) {
            $fallbacks = array('date', 'attendance_date', 'created_at');
        }
        if ($has_column('att_date')) {
            return 'att_date';
        }
        foreach ($fallbacks as $col) {
            if ($has_column($col)) {
                return $col;
            }
        }

        return 'att_date';
    }
}

if (!function_exists('attendance_punch_setting_is_enabled')) {
    /**
     * @param mixed $value
     * @return bool
     */
    function attendance_punch_setting_is_enabled($value)
    {
        return ($value === 'yes' || $value === '1' || $value === 1 || $value === true);
    }
}

if (!function_exists('attendance_punch_face_distance')) {
    /**
     * Euclidean distance between two face-api descriptor JSON arrays.
     *
     * @param string $json_a
     * @param string $json_b
     * @return float|null null when format invalid
     */
    function attendance_punch_face_distance($json_a, $json_b)
    {
        $a = json_decode($json_a, true);
        $b = json_decode($json_b, true);
        if (!is_array($a) || !is_array($b) || count($a) !== count($b) || count($a) === 0) {
            return null;
        }

        $sum = 0.0;
        $n = count($a);
        for ($i = 0; $i < $n; $i++) {
            $da = isset($a[$i]) ? (float) $a[$i] : 0.0;
            $dbv = isset($b[$i]) ? (float) $b[$i] : 0.0;
            $d = $da - $dbv;
            $sum += $d * $d;
        }

        return sqrt($sum);
    }
}

if (!function_exists('attendance_punch_verify_face_for_create')) {
    /**
     * @param object $settings Setting_model with get_setting()
     * @param object $faces Face_model with get_by_user()
     * @param int $user_id
     * @param string $face_descriptor
     * @return array{ok:bool,error?:string}
     */
    function attendance_punch_verify_face_for_create($settings, $faces, $user_id, $face_descriptor)
    {
        $required = $settings->get_setting('attendance_face_verification_required', 'yes');
        if (!attendance_punch_setting_is_enabled($required)) {
            return array('ok' => true);
        }

        if (trim((string) $face_descriptor) === '') {
            return array('ok' => false, 'error' => 'face_verification_failed');
        }

        $stored = $faces->get_by_user($user_id);
        if (!$stored || empty($stored->descriptor)) {
            return array(
                'ok'    => false,
                'error' => 'No registered face found for this user. Please register your face in your profile first.',
            );
        }

        $dist = attendance_punch_face_distance($face_descriptor, $stored->descriptor);
        if ($dist === null) {
            return array(
                'ok'    => false,
                'error' => 'Face verification failed: Invalid face data format. Please try capturing your face again.',
            );
        }
        if ($dist > 0.6) {
            return array(
                'ok'    => false,
                'error' => 'Face verification failed: Your face does not match the registered face. Please ensure you are using the same face as registered in your profile.',
            );
        }

        return array('ok' => true);
    }
}

if (!function_exists('attendance_punch_verify_face_for_edit')) {
    /**
     * @param object $faces Face_model with get_by_user()
     * @param int $user_id
     * @param string $face_required
     * @param string $face_descriptor
     * @return array{ok:bool,error?:string}
     */
    function attendance_punch_verify_face_for_edit($faces, $user_id, $face_required, $face_descriptor)
    {
        if ((string) $face_required !== '1') {
            return array('ok' => true);
        }

        if (trim((string) $face_descriptor) === '') {
            return array('ok' => false, 'error' => 'Face verification failed: no descriptor provided.');
        }

        $tpl = $faces->get_by_user($user_id);
        if (!$tpl || empty($tpl->descriptor)) {
            return array(
                'ok'    => false,
                'error' => 'Face template not found for this user. Please register face in User profile first.',
            );
        }

        $dist = attendance_punch_face_distance($tpl->descriptor, $face_descriptor);
        if ($dist === null || $dist > 0.6) {
            return array('ok' => false, 'error' => 'Face not recognized. Please try again.');
        }

        return array('ok' => true);
    }
}

if (!function_exists('attendance_punch_invalid_action_error')) {
    /**
     * @param CI_DB_query_builder $db
     * @param callable $has_column
     * @param int $user_id
     * @param string $today Y-m-d
     * @return string
     */
    function attendance_punch_invalid_action_error($db, callable $has_column, $user_id, $today)
    {
        $cols = attendance_punch_resolve_time_columns($has_column);
        $existing = $db->where('user_id', $user_id)
            ->where($cols['col_date'], $today)
            ->limit(1)
            ->get('attendance')
            ->row();

        if (!$existing) {
            return 'Invalid action selected. Please select either check-in or check-out.';
        }

        $times = attendance_punch_read_existing_times(
            $existing,
            $cols['col_in'],
            $cols['col_out'],
            $cols['hasPunchIn'],
            $cols['hasCheckIn'],
            $cols['hasPunchOut'],
            $cols['hasCheckOut']
        );

        if ($times['cin'] !== '' && $times['cout'] !== '') {
            return 'already_marked';
        }
        if ($times['cin'] !== '') {
            return 'You have already checked in today. Please select check-out action.';
        }

        return 'Invalid action selected. Please select either check-in or check-out.';
    }
}

if (!function_exists('attendance_punch_today_status')) {
    /**
     * @param CI_DB_query_builder $db
     * @param callable $has_column
     * @param int $user_id
     * @param string $today Y-m-d
     * @return array{existing:object|null,has_checkin:bool,has_checkout:bool,checkin_time:string,checkout_time:string}
     */
    function attendance_punch_today_status($db, callable $has_column, $user_id, $today)
    {
        $cols = attendance_punch_resolve_time_columns($has_column);
        $status = array(
            'existing'       => null,
            'has_checkin'  => false,
            'has_checkout' => false,
            'checkin_time' => '',
            'checkout_time'=> '',
        );

        if (!$user_id) {
            return $status;
        }

        $existing = $db->where('user_id', $user_id)
            ->where($cols['col_date'], $today)
            ->get('attendance')
            ->row();

        if (!$existing) {
            return $status;
        }

        $times = attendance_punch_read_existing_times(
            $existing,
            $cols['col_in'],
            $cols['col_out'],
            $cols['hasPunchIn'],
            $cols['hasCheckIn'],
            $cols['hasPunchOut'],
            $cols['hasCheckOut']
        );

        $status['existing'] = $existing;
        $status['has_checkin'] = ($times['cin'] !== '');
        $status['has_checkout'] = ($times['cout'] !== '');
        $status['checkin_time'] = $times['cin'];
        $status['checkout_time'] = $times['cout'];

        return $status;
    }
}

if (!function_exists('attendance_punch_first_column')) {
    /**
     * @param callable $has_column
     * @param array $candidates
     * @return string|null
     */
    function attendance_punch_first_column(callable $has_column, array $candidates)
    {
        foreach ($candidates as $col) {
            if ($has_column($col)) {
                return $col;
            }
        }

        return null;
    }
}

if (!function_exists('attendance_punch_merge_edit_geo_fields')) {
    /**
     * @param callable $has_column
     * @param mixed $lat
     * @param mixed $lng
     * @param callable|null $geocode_fn function($lat,$lng): ?string
     * @return array<string,string>
     */
    function attendance_punch_merge_edit_geo_fields(callable $has_column, $lat, $lng, callable $geocode_fn = null)
    {
        $data = array();
        if ($lat === null || $lng === null) {
            return $data;
        }

        $latCol = attendance_punch_first_column($has_column, array('latitude', 'lat', 'geo_lat'));
        $lngCol = attendance_punch_first_column($has_column, array('longitude', 'lng', 'geo_lng'));
        if ($latCol && $lngCol) {
            $data[$latCol] = (string) $lat;
            $data[$lngCol] = (string) $lng;
        }

        if ($has_column('location_name')) {
            $latTrim = trim((string) $lat);
            $lngTrim = trim((string) $lng);
            if ($latTrim !== '' && $lngTrim !== '' && $geocode_fn !== null) {
                $locName = $geocode_fn($latTrim, $lngTrim);
                if ($locName) {
                    $data['location_name'] = $locName;
                }
            }
        }

        return $data;
    }
}
