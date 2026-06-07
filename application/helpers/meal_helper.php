<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('meal_get_settings')) {
    function meal_get_settings($db)
    {
        meal_schema_ensure($db);
        $defaults = array(
            'breakfast_cutoff' => '08:30:00',
            'lunch_cutoff' => '11:00:00',
            'max_lunch_plates' => 2,
            'max_breakfast_plates' => 3,
            'auto_publish_announcements' => 1,
            'skip_weekends_on_apply' => 0,
            'show_dashboard_announcement' => 1,
            'provider_contact' => '',
        );
        $row = $db->order_by('id', 'ASC')->limit(1)->get('meal_settings')->row();
        if (!$row) {
            return $defaults;
        }
        return array(
            'breakfast_cutoff' => substr((string) $row->breakfast_cutoff, 0, 8),
            'lunch_cutoff' => substr((string) $row->lunch_cutoff, 0, 8),
            'max_lunch_plates' => isset($row->max_lunch_plates) ? (int) $row->max_lunch_plates : 2,
            'max_breakfast_plates' => isset($row->max_breakfast_plates) ? (int) $row->max_breakfast_plates : 3,
            'auto_publish_announcements' => isset($row->auto_publish_announcements) ? (int) $row->auto_publish_announcements : 1,
            'skip_weekends_on_apply' => isset($row->skip_weekends_on_apply) ? (int) $row->skip_weekends_on_apply : 0,
            'show_dashboard_announcement' => isset($row->show_dashboard_announcement) ? (int) $row->show_dashboard_announcement : 1,
            'provider_contact' => isset($row->provider_contact) ? trim((string) $row->provider_contact) : '',
        );
    }
}

if (!function_exists('meal_provider_contact')) {
    /** Provider phone/contact from meal settings (empty string if not set). */
    function meal_provider_contact(?array $settings = null)
    {
        if ($settings === null) {
            $CI =& get_instance();
            $settings = meal_get_settings($CI->db);
        }
        return isset($settings['provider_contact']) ? trim((string) $settings['provider_contact']) : '';
    }
}

if (!function_exists('meal_provider_contact_tel_href')) {
    function meal_provider_contact_tel_href($contact)
    {
        $contact = trim((string) $contact);
        if ($contact === '') {
            return '';
        }
        $digits = preg_replace('/[^\d+]/', '', $contact);
        return $digits !== '' ? 'tel:' . $digits : '';
    }
}

if (!function_exists('meal_show_dashboard_announcement')) {
    function meal_show_dashboard_announcement($db = null)
    {
        $CI =& get_instance();
        if ($db === null) {
            $db = $CI->db;
        }
        $settings = meal_get_settings($db);
        return !empty($settings['show_dashboard_announcement']);
    }
}

if (!function_exists('meal_order_breakfast_plates')) {
    function meal_order_breakfast_plates($order)
    {
        if (!$order) {
            return 0;
        }
        if (isset($order->breakfast_plates) && $order->breakfast_plates !== null && $order->breakfast_plates !== '') {
            return (int) $order->breakfast_plates;
        }
        return !empty($order->want_breakfast) ? 1 : 0;
    }
}

if (!function_exists('meal_order_lunch_tiffin')) {
    /** @return string ''|'half'|'full' */
    function meal_order_lunch_tiffin($order)
    {
        if (!$order) {
            return '';
        }
        if (isset($order->lunch_tiffin) && $order->lunch_tiffin !== null && $order->lunch_tiffin !== '') {
            $t = strtolower(trim((string) $order->lunch_tiffin));
            return meal_normalize_lunch_tiffin($t);
        }
        $plates = isset($order->lunch_plates) ? (int) $order->lunch_plates : 0;
        if ($plates >= 2) {
            return 'full';
        }
        if ($plates === 1) {
            return 'half';
        }
        return '';
    }
}

if (!function_exists('meal_normalize_lunch_tiffin')) {
    /** @return string ''|'half'|'full' */
    function meal_normalize_lunch_tiffin($value)
    {
        $v = strtolower(trim((string) $value));
        if ($v === 'half' || $v === 'full') {
            return $v;
        }
        return '';
    }
}

if (!function_exists('meal_lunch_tiffin_label')) {
    function meal_lunch_tiffin_label($tiffin)
    {
        $t = meal_normalize_lunch_tiffin($tiffin);
        if ($t === 'half') {
            return 'Half tiffin';
        }
        if ($t === 'full') {
            return 'Full tiffin';
        }
        return '';
    }
}

if (!function_exists('meal_format_log_field')) {
    function meal_format_log_field($field_name)
    {
        $map = array(
            'breakfast_plates' => 'Breakfast plates',
            'lunch_tiffin' => 'Lunch tiffin',
            'lunch_plates' => 'Lunch plates',
            'want_breakfast' => 'Breakfast (legacy)',
            'additional_breakfast_plates' => 'Additional breakfast plates',
            'additional_lunch_tiffin' => 'Additional lunch tiffin',
        );
        return isset($map[$field_name]) ? $map[$field_name] : (string) $field_name;
    }
}

if (!function_exists('meal_format_log_value')) {
    function meal_format_log_value($field_name, $value)
    {
        if ($field_name === 'lunch_tiffin' || $field_name === 'additional_lunch_tiffin') {
            $label = meal_lunch_tiffin_label($value);
            return $label !== '' ? $label : '—';
        }
        return (string) $value;
    }
}

if (!function_exists('meal_format_breakfast_value')) {
    function meal_format_breakfast_value($plates)
    {
        $n = (int) $plates;
        if ($n <= 0) {
            return 'No order';
        }
        return $n . ' plate' . ($n > 1 ? 's' : '');
    }
}

if (!function_exists('meal_order_additional_breakfast_plates')) {
    function meal_order_additional_breakfast_plates($order)
    {
        if (!$order || !isset($order->additional_breakfast_plates)) {
            return 0;
        }
        return max(0, (int) $order->additional_breakfast_plates);
    }
}

if (!function_exists('meal_order_additional_lunch_tiffin')) {
    /** @return string ''|'half'|'full' */
    function meal_order_additional_lunch_tiffin($order)
    {
        if (!$order || !isset($order->additional_lunch_tiffin)) {
            return '';
        }
        return meal_normalize_lunch_tiffin($order->additional_lunch_tiffin);
    }
}

if (!function_exists('meal_order_total_breakfast_plates')) {
    function meal_order_total_breakfast_plates($order)
    {
        return meal_order_breakfast_plates($order) + meal_order_additional_breakfast_plates($order);
    }
}

if (!function_exists('meal_format_breakfast_order_line')) {
    /** Human label for main + optional additional breakfast plates. */
    function meal_format_breakfast_order_line($main_plates, $additional_plates = 0)
    {
        $main = max(0, (int) $main_plates);
        $add = max(0, (int) $additional_plates);
        if ($main <= 0 && $add <= 0) {
            return 'No order';
        }
        $parts = array();
        if ($main > 0) {
            $parts[] = meal_format_breakfast_value($main);
        }
        if ($add > 0) {
            $parts[] = $add . ' additional plate' . ($add > 1 ? 's' : '');
        }
        return implode(' + ', $parts);
    }
}

if (!function_exists('meal_format_lunch_order_line')) {
    /** Human label for main + optional additional lunch tiffin. */
    function meal_format_lunch_order_line($main_tiffin, $additional_tiffin = '')
    {
        $main = meal_normalize_lunch_tiffin($main_tiffin);
        $add = meal_normalize_lunch_tiffin($additional_tiffin);
        if ($main === '' && $add === '') {
            return 'No order';
        }
        $parts = array();
        if ($main !== '') {
            $parts[] = meal_lunch_tiffin_label($main);
        }
        if ($add !== '') {
            $parts[] = 'additional ' . strtolower(meal_lunch_tiffin_label($add));
        }
        return implode(' + ', $parts);
    }
}

if (!function_exists('meal_order_breakfast_display')) {
    function meal_order_breakfast_display($order)
    {
        return meal_format_breakfast_order_line(
            meal_order_breakfast_plates($order),
            meal_order_additional_breakfast_plates($order)
        );
    }
}

if (!function_exists('meal_order_lunch_display')) {
    function meal_order_lunch_display($order)
    {
        return meal_format_lunch_order_line(
            meal_order_lunch_tiffin($order),
            meal_order_additional_lunch_tiffin($order)
        );
    }
}

if (!function_exists('meal_change_is_additional')) {
    /** True when change request adds on top of current order (add_bf:* / add_lu:*). */
    function meal_change_is_additional($requested_value)
    {
        $v = strtolower(trim((string) $requested_value));
        return strpos($v, 'add_bf:') === 0 || strpos($v, 'add_lu:') === 0;
    }
}

if (!function_exists('meal_format_request_value')) {
    function meal_format_request_value($meal_type, $value)
    {
        $v = strtolower(trim((string) $value));
        if ($v === 'add_bf:1') {
            return 'Additional 1 plate';
        }
        if ($v === 'add_lu:half') {
            return 'Additional half tiffin';
        }
        if ($v === 'add_lu:full') {
            return 'Additional full tiffin';
        }
        if ($meal_type === 'breakfast') {
            return meal_format_breakfast_value($value);
        }
        if ($meal_type === 'lunch') {
            $label = meal_lunch_tiffin_label($value);
            return $label !== '' ? $label : 'No order';
        }
        return (string) $value;
    }
}

if (!function_exists('meal_normalize_change_value')) {
    /** Normalize stored change-request value for comparison. */
    function meal_normalize_change_value($meal_type, $value)
    {
        if ($meal_type === 'breakfast') {
            return (string) (int) $value;
        }
        return meal_normalize_lunch_tiffin($value);
    }
}

if (!function_exists('meal_change_values_equal')) {
    function meal_change_values_equal($meal_type, $a, $b)
    {
        return meal_normalize_change_value($meal_type, $a) === meal_normalize_change_value($meal_type, $b);
    }
}

if (!function_exists('meal_format_change_summary')) {
    /** e.g. "1 plate → 2 plates" or "Full tiffin → Full tiffin + additional half tiffin" */
    function meal_format_change_summary($meal_type, $current, $requested)
    {
        if (meal_change_is_additional($requested)) {
            $req = strtolower(trim((string) $requested));
            if ($meal_type === 'breakfast' && $req === 'add_bf:1') {
                $main = (int) $current;
                return meal_format_breakfast_order_line($main, 0) . ' → ' . meal_format_breakfast_order_line($main, 1);
            }
            if ($meal_type === 'lunch' && strpos($req, 'add_lu:') === 0) {
                $add = meal_normalize_lunch_tiffin(substr($req, 7));
                $main = meal_normalize_lunch_tiffin($current);
                return meal_format_lunch_order_line($main, '') . ' → ' . meal_format_lunch_order_line($main, $add);
            }
        }
        $from = meal_format_request_value($meal_type, $current);
        $to = meal_format_request_value($meal_type, $requested);
        if ($from === $to) {
            return $from;
        }
        return $from . ' → ' . $to;
    }
}

if (!function_exists('meal_request_status_badge')) {
    function meal_request_status_badge($status)
    {
        $map = array(
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
        );
        return isset($map[$status]) ? $map[$status] : 'secondary';
    }
}

if (!function_exists('meal_role_permission_cache')) {
    /**
     * Meals permissions from DB (can_access = 1), keyed by role_id => module => true.
     *
     * @return array<int, array<string, bool>>
     */
    function meal_role_permission_cache()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = array();
        $CI =& get_instance();
        if (!isset($CI->db) || !$CI->db->table_exists('permissions')) {
            return $cache;
        }
        foreach ($CI->db->where('can_access', 1)->get('permissions')->result() as $p) {
            $mod = strtolower(trim((string) $p->module));
            $rid = (int) $p->role_id;
            if ($mod === '' || $rid <= 0) {
                continue;
            }
            if (!isset($cache[$rid])) {
                $cache[$rid] = array();
            }
            $cache[$rid][$mod] = true;
        }
        return $cache;
    }
}

if (!function_exists('meal_role_has')) {
    /**
     * Meals permission from Permission Manager only — no Super Admin bypass.
     */
    function meal_role_has($module_key, $role_id = null)
    {
        $CI =& get_instance();
        if ($role_id === null) {
            $role_id = (int) $CI->session->userdata('role_id');
        }
        if ($role_id <= 0) {
            return false;
        }
        $key = strtolower(trim((string) $module_key));
        $cache = meal_role_permission_cache();
        return !empty($cache[$role_id][$key]);
    }
}

if (!function_exists('require_meal_access')) {
    function require_meal_access($module, $redirect_to_dashboard = true)
    {
        $keys = is_array($module) ? $module : array($module);
        foreach ($keys as $k) {
            if (meal_role_has($k)) {
                return true;
            }
        }

        $CI =& get_instance();
        if ($redirect_to_dashboard) {
            $CI->session->set_flashdata('access_denied', 'You do not have permission to access Office Meals.');
            redirect('dashboard');
        } else {
            show_error('You do not have permission to access this page.', 403);
        }
        return false;
    }
}

if (!function_exists('meal_can_access')) {
    /** Role check: granular meals key from Permission Manager. */
    function meal_can_access($module_key)
    {
        return meal_role_has($module_key);
    }
}

if (!function_exists('meal_permission_keys')) {
    /** Keys defined in Permissions.php for Office Meals. */
    function meal_permission_keys()
    {
        return array(
            'meals_order',
            'meals_calendar',
            'meals_provider',
            'meals_settings',
            'meals_history',
            'meals_all_orders',
        );
    }
}

if (!function_exists('meal_has_any_access')) {
    function meal_has_any_access()
    {
        foreach (meal_permission_keys() as $key) {
            if (meal_role_has($key)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('meal_can_order')) {
    function meal_can_order()
    {
        return meal_can_access('meals_order');
    }
}

if (!function_exists('meal_can_view_dashboard_announcement')) {
    function meal_can_view_dashboard_announcement($db = null)
    {
        if (!meal_has_any_access()) {
            return false;
        }
        return meal_show_dashboard_announcement($db);
    }
}

if (!function_exists('meal_can_view_all_orders')) {
    function meal_can_view_all_orders()
    {
        return meal_role_has('meals_all_orders');
    }
}

if (!function_exists('meal_can_export')) {
    function meal_can_export()
    {
        return meal_role_has('meals_all_orders')
            || meal_role_has('meals_provider')
            || meal_role_has('meals_history');
    }
}

if (!function_exists('meal_nav_screens')) {
    /** Permission-gated meal screens for sidebar tabs and in-page nav. */
    function meal_nav_screens()
    {
        $screens = array();
        if (meal_can_order()) {
            $screens[] = array(
                'key' => 'meals',
                'href' => 'meals',
                'icon' => 'bi-cup-hot',
                'label' => 'My Orders',
            );
        }
        if (meal_can_access('meals_calendar')) {
            $screens[] = array(
                'key' => 'calendar',
                'href' => 'meals/calendar',
                'icon' => 'bi-calendar3',
                'label' => 'Calendar',
            );
        }
        if (meal_can_access('meals_provider')) {
            $screens[] = array(
                'key' => 'provider',
                'href' => 'meals/provider',
                'icon' => 'bi-truck',
                'label' => 'Provider',
            );
        }
        if (meal_can_access('meals_settings')) {
            $screens[] = array(
                'key' => 'settings',
                'href' => 'meals/settings',
                'icon' => 'bi-gear',
                'label' => 'Settings',
            );
        }
        if (meal_can_access('meals_history')) {
            $screens[] = array(
                'key' => 'history',
                'href' => 'meals/history',
                'icon' => 'bi-clock-history',
                'label' => 'History',
            );
        }
        if (meal_can_view_all_orders()) {
            $screens[] = array(
                'key' => 'all_orders',
                'href' => 'meals/all_orders',
                'icon' => 'bi-list-check',
                'label' => 'All Orders',
            );
        }
        return $screens;
    }
}

if (!function_exists('meal_nav_active_key')) {
    /** Active meal screen key from URI segments. */
    function meal_nav_active_key($active, $active_sub)
    {
        if (strtolower((string) $active) !== 'meals') {
            return '';
        }
        $sub = strtolower((string) $active_sub);
        if ($sub === '' || $sub === 'index') {
            return 'meals';
        }
        $known = array('calendar', 'provider', 'settings', 'history', 'all_orders');
        return in_array($sub, $known, true) ? $sub : 'meals';
    }
}

if (!function_exists('meal_default_route')) {
    /** First screen the user may open from the sidebar. */
    function meal_default_route()
    {
        $screens = meal_nav_screens();
        if (!empty($screens)) {
            return $screens[0]['href'];
        }
        return 'meals';
    }
}

if (!function_exists('meal_week_day_labels')) {
    function meal_week_day_labels()
    {
        return array(
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        );
    }
}

if (!function_exists('meal_monday_of')) {
    function meal_monday_of($date)
    {
        $ts = strtotime($date);
        if ($ts === false) {
            $ts = time();
        }
        $dow = (int) date('N', $ts);
        return date('Y-m-d', strtotime('-' . ($dow - 1) . ' days', $ts));
    }
}

if (!function_exists('meal_cutoff_datetime')) {
    function meal_cutoff_datetime($meal_date, $time_hms)
    {
        $t = trim((string) $time_hms);
        if (strlen($t) === 5) {
            $t .= ':00';
        } else {
            $t = substr($t, 0, 8);
        }
        return $meal_date . ' ' . $t;
    }
}

if (!function_exists('meal_is_breakfast_locked')) {
    function meal_is_breakfast_locked($meal_date, ?array $settings = null)
    {
        return !meal_is_breakfast_editable($meal_date, $settings);
    }
}

if (!function_exists('meal_is_lunch_locked')) {
    function meal_is_lunch_locked($meal_date, ?array $settings = null)
    {
        return !meal_is_lunch_editable($meal_date, $settings);
    }
}

if (!function_exists('meal_is_breakfast_editable')) {
    function meal_is_breakfast_editable($meal_date, ?array $settings = null)
    {
        $CI =& get_instance();
        $settings = $settings ?: meal_get_settings($CI->db);
        if ($meal_date < date('Y-m-d')) {
            return false;
        }
        return date('Y-m-d H:i:s') < meal_cutoff_datetime($meal_date, $settings['breakfast_cutoff']);
    }
}

if (!function_exists('meal_is_lunch_editable')) {
    function meal_is_lunch_editable($meal_date, ?array $settings = null)
    {
        $CI =& get_instance();
        $settings = $settings ?: meal_get_settings($CI->db);
        if ($meal_date < date('Y-m-d')) {
            return false;
        }
        return date('Y-m-d H:i:s') < meal_cutoff_datetime($meal_date, $settings['lunch_cutoff']);
    }
}

if (!function_exists('meal_is_meals_announcement_title')) {
    function meal_is_meals_announcement_title($title)
    {
        return strpos((string) $title, '[Meals]') === 0;
    }
}

if (!function_exists('meal_filter_dashboard_announcements')) {
    /** Hide per-day meal rows — dashboard uses Today/Tomorrow grid instead. */
    function meal_filter_dashboard_announcements($announcements)
    {
        $out = array();
        foreach ((array) $announcements as $a) {
            $title = isset($a->title) ? (string) $a->title : '';
            if (meal_is_meals_announcement_title($title)) {
                continue;
            }
            $out[] = $a;
        }
        return $out;
    }
}

if (!function_exists('meal_dashboard_today_tomorrow')) {
    /**
     * Today + tomorrow meal calendar for dashboard grid.
     *
     * @return array{today:array,tomorrow:array,has_any:bool,settings:array}
     */
    function meal_dashboard_today_tomorrow($db)
    {
        meal_schema_ensure($db);
        $settings = meal_get_settings($db);
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $map = array();
        if ($db->table_exists('meal_calendar')) {
            $rows = $db->where_in('meal_date', array($today, $tomorrow))
                ->get('meal_calendar')->result();
            foreach ($rows as $r) {
                $map[$r->meal_date] = $r;
            }
        }

        $build = function ($date, $dayLabel) use ($map, $settings) {
            $cal = isset($map[$date]) ? $map[$date] : null;
            $hasBf = $cal && (int) $cal->has_breakfast === 1;
            $hasLu = $cal && (int) $cal->has_lunch === 1;
            return array(
                'date' => $date,
                'day_label' => $dayLabel,
                'date_display' => date('D, d M Y', strtotime($date)),
                'has_breakfast' => $hasBf,
                'has_lunch' => $hasLu,
                'breakfast_note' => ($cal && !empty($cal->breakfast_note)) ? (string) $cal->breakfast_note : '',
                'lunch_note' => ($cal && !empty($cal->lunch_note)) ? (string) $cal->lunch_note : '',
                'bf_cutoff' => substr($settings['breakfast_cutoff'], 0, 5),
                'lu_cutoff' => substr($settings['lunch_cutoff'], 0, 5),
                'has_any' => $hasBf || $hasLu,
            );
        };

        $todayCard = $build($today, 'Today');
        $tomorrowCard = $build($tomorrow, 'Tomorrow');

        return array(
            'today' => $todayCard,
            'tomorrow' => $tomorrowCard,
            'has_any' => !empty($todayCard['has_any']) || !empty($tomorrowCard['has_any']),
            'settings' => $settings,
        );
    }
}

if (!function_exists('meal_cleanup_legacy_meal_announcements')) {
    function meal_cleanup_legacy_meal_announcements($db)
    {
        if (!$db->table_exists('announcements')) {
            return;
        }
        $db->like('title', '[Meals]', 'after')
            ->where('title !=', '[Meals] Today & Tomorrow')
            ->delete('announcements');
    }
}

if (!function_exists('meal_sync_announcement_for_date')) {
    /**
     * Publish or update dashboard announcement when meal calendar changes.
     * @deprecated Per-date sync — delegates to consolidated today/tomorrow announcement.
     */
    function meal_sync_announcement_for_date($db, $meal_date, $calendar_row, $posted_by)
    {
        meal_sync_dashboard_announcement($db, $posted_by);
    }
}

if (!function_exists('meal_sync_dashboard_announcement')) {
    /**
     * One dashboard announcement: Today & Tomorrow (content); grid rendered from calendar on dashboard.
     */
    function meal_sync_dashboard_announcement($db, $posted_by)
    {
        $CI =& get_instance();
        $CI->load->helper('announcements_schema');
        announcements_schema_ensure($db);

        if (!$db->table_exists('announcements')) {
            return 0;
        }

        meal_cleanup_legacy_meal_announcements($db);

        $cards = meal_dashboard_today_tomorrow($db);
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $title = '[Meals] Today & Tomorrow';

        $lines = array('Office meals schedule:');
        foreach (array('today', 'tomorrow') as $key) {
            $c = $cards[$key];
            $head = $c['day_label'] . ' (' . $c['date_display'] . ')';
            if (!$c['has_any']) {
                $lines[] = $head . ': No meals scheduled.';
                continue;
            }
            $parts = array();
            if ($c['has_breakfast']) {
                $parts[] = 'Breakfast' . ($c['breakfast_note'] !== '' ? ' — ' . $c['breakfast_note'] : '')
                    . ' (order by ' . $c['bf_cutoff'] . ')';
            }
            if ($c['has_lunch']) {
                $parts[] = 'Lunch' . ($c['lunch_note'] !== '' ? ' — ' . $c['lunch_note'] : '')
                    . ' (order by ' . $c['lu_cutoff'] . ')';
            }
            $lines[] = $head . ': ' . implode('; ', $parts) . '.';
        }
        $lines[] = 'Open the Meals page to place or change your order before cut-off.';

        $data = array(
            'title' => $title,
            'content' => implode("\n", $lines),
            'posted_by' => (int) $posted_by,
            'target_roles' => 'all',
            'priority' => 'high',
            'start_date' => $today,
            'end_date' => $tomorrow,
            'status' => 'published',
            'updated_at' => date('Y-m-d H:i:s'),
        );

        $existing = $db->where('title', $title)->order_by('id', 'DESC')->limit(1)->get('announcements')->row();
        if ($existing) {
            $db->where('id', (int) $existing->id)->update('announcements', $data);
            return (int) $existing->id;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $db->insert('announcements', $data);
        return (int) $db->insert_id();
    }
}

if (!function_exists('meal_active_banners')) {
    /**
     * Announcements to show on Meals page (today through +7 days).
     */
    function meal_active_banners($db, $from_date = null, $days = 8)
    {
        if (!$db->table_exists('meal_calendar')) {
            return array();
        }
        $from = $from_date ?: date('Y-m-d');
        $to = date('Y-m-d', strtotime($from . ' +' . ((int) $days - 1) . ' days'));
        return $db->select('meal_date, has_breakfast, has_lunch, breakfast_note, lunch_note, announcement_id')
            ->from('meal_calendar')
            ->where('meal_date >=', $from)
            ->where('meal_date <=', $to)
            ->order_by('meal_date', 'ASC')
            ->get()->result();
    }
}
