<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('workdays_between')) {
    /**
     * Calculate working days between two dates (inclusive), excluding weekends and optional holidays table.
     * @param string $start Y-m-d
     * @param string $end   Y-m-d
     * @return float Number of days
     */
    function workdays_between($start, $end) {
        $CI =& get_instance();
        $start_ts = strtotime($start);
        $end_ts = strtotime($end);
        if (!$start_ts || !$end_ts || $end_ts < $start_ts) return 0.0;

        // Fetch holidays into a set for fast lookup if table exists
        $holiday_map = [];
        if (isset($CI->db) && $CI->db->table_exists('holidays')) {
            $rows = $CI->db->select('holiday_date')->from('holidays')->where('status','active')->get()->result();
            foreach ($rows as $r) { $holiday_map[$r->holiday_date] = true; }
        }

        $count = 0.0;
        for ($ts = $start_ts; $ts <= $end_ts; $ts = strtotime('+1 day', $ts)) {
            $dow = date('N', $ts); // 6=Sat,7=Sun
            $ymd = date('Y-m-d', $ts);
            if ($dow >= 6) continue; // weekend
            if (isset($holiday_map[$ymd])) continue; // holiday
            $count += 1.0;
        }
        return $count;
    }
}
