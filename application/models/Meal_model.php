<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meal_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('meal_schema', 'meal'));
        meal_schema_ensure($this->db);
    }

    public function get_settings()
    {
        return meal_get_settings($this->db);
    }

    public function save_settings(array $data)
    {
        $row = $this->db->order_by('id', 'ASC')->limit(1)->get('meal_settings')->row();
        $payload = array(
            'breakfast_cutoff' => $data['breakfast_cutoff'],
            'lunch_cutoff' => $data['lunch_cutoff'],
            'max_breakfast_plates' => isset($data['max_breakfast_plates']) ? (int) $data['max_breakfast_plates'] : 3,
            'auto_publish_announcements' => !empty($data['auto_publish_announcements']) ? 1 : 0,
            'skip_weekends_on_apply' => !empty($data['skip_weekends_on_apply']) ? 1 : 0,
            'show_dashboard_announcement' => !empty($data['show_dashboard_announcement']) ? 1 : 0,
            'provider_contact' => isset($data['provider_contact']) ? trim((string) $data['provider_contact']) : '',
            'updated_at' => date('Y-m-d H:i:s'),
        );
        if (strlen($payload['provider_contact']) > 50) {
            $payload['provider_contact'] = substr($payload['provider_contact'], 0, 50);
        }
        if ($payload['provider_contact'] === '') {
            $payload['provider_contact'] = null;
        }
        if ($payload['max_breakfast_plates'] < 1) {
            $payload['max_breakfast_plates'] = 1;
        }
        if ($payload['max_breakfast_plates'] > 3) {
            $payload['max_breakfast_plates'] = 3;
        }
        if ($row) {
            $this->db->where('id', (int) $row->id)->update('meal_settings', $payload);
            return (int) $row->id;
        }
        $this->db->insert('meal_settings', $payload);
        return (int) $this->db->insert_id();
    }

    public function get_week_menu_template()
    {
        meal_schema_ensure($this->db);
        $rows = $this->db->order_by('day_of_week', 'ASC')->get('meal_week_menu')->result();
        $map = array();
        foreach ($rows as $r) {
            $map[(int) $r->day_of_week] = $r;
        }
        foreach (array_keys(meal_week_day_labels()) as $dow) {
            if (!isset($map[$dow])) {
                $map[$dow] = (object) array(
                    'day_of_week' => $dow,
                    'has_breakfast' => 0,
                    'has_lunch' => 0,
                    'breakfast_menu' => '',
                    'lunch_menu' => '',
                );
            }
        }
        ksort($map);
        return $map;
    }

    public function save_week_menu_template(array $days)
    {
        meal_schema_ensure($this->db);
        foreach ($days as $dow => $row) {
            if (!is_array($row)) {
                continue;
            }
            $dow = (int) $dow;
            if ($dow < 1 || $dow > 7) {
                continue;
            }
            $payload = array(
                'day_of_week' => $dow,
                'has_breakfast' => !empty($row['has_breakfast']) ? 1 : 0,
                'has_lunch' => !empty($row['has_lunch']) ? 1 : 0,
                'breakfast_menu' => isset($row['breakfast_menu']) ? trim((string) $row['breakfast_menu']) : '',
                'lunch_menu' => isset($row['lunch_menu']) ? trim((string) $row['lunch_menu']) : '',
                'updated_at' => date('Y-m-d H:i:s'),
            );
            if ($payload['breakfast_menu'] === '') {
                $payload['breakfast_menu'] = null;
            }
            if ($payload['lunch_menu'] === '') {
                $payload['lunch_menu'] = null;
            }
            $existing = $this->db->where('day_of_week', $dow)->get('meal_week_menu')->row();
            if ($existing) {
                $this->db->where('id', (int) $existing->id)->update('meal_week_menu', $payload);
            } else {
                $this->db->insert('meal_week_menu', $payload);
            }
        }
    }

    /**
     * Copy weekly menu template onto calendar dates (Mon–Sun from week_start).
     *
     * @return int dates updated
     */
    public function apply_week_menu_to_calendar($week_start, $user_id)
    {
        $week_start = meal_monday_of($week_start);
        $template = $this->get_week_menu_template();
        $settings = $this->get_settings();
        $count = 0;

        for ($i = 0; $i < 7; $i++) {
            $meal_date = date('Y-m-d', strtotime($week_start . ' +' . $i . ' days'));
            $dow = (int) date('N', strtotime($meal_date));
            if (!empty($settings['skip_weekends_on_apply']) && $dow >= 6) {
                $this->save_calendar_day($meal_date, array(
                    'has_breakfast' => false,
                    'has_lunch' => false,
                    'breakfast_note' => '',
                    'lunch_note' => '',
                ), $user_id);
                $count++;
                continue;
            }
            if (!isset($template[$dow])) {
                continue;
            }
            $t = $template[$dow];
            $this->save_calendar_day($meal_date, array(
                'has_breakfast' => (int) $t->has_breakfast === 1,
                'has_lunch' => (int) $t->has_lunch === 1,
                'breakfast_note' => (string) ($t->breakfast_menu ?? ''),
                'lunch_note' => (string) ($t->lunch_menu ?? ''),
            ), $user_id);
            $count++;
        }
        return $count;
    }

    public function get_calendar_day($meal_date)
    {
        return $this->db->where('meal_date', $meal_date)->get('meal_calendar')->row();
    }

    public function list_calendar_range($from, $to)
    {
        return $this->db->where('meal_date >=', $from)
            ->where('meal_date <=', $to)
            ->order_by('meal_date', 'ASC')
            ->get('meal_calendar')->result();
    }

    public function save_calendar_day($meal_date, array $data, $user_id)
    {
        $existing = $this->get_calendar_day($meal_date);
        $payload = array(
            'meal_date' => $meal_date,
            'has_breakfast' => !empty($data['has_breakfast']) ? 1 : 0,
            'has_lunch' => !empty($data['has_lunch']) ? 1 : 0,
            'breakfast_note' => isset($data['breakfast_note']) && $data['breakfast_note'] !== '' ? $data['breakfast_note'] : null,
            'lunch_note' => isset($data['lunch_note']) && $data['lunch_note'] !== '' ? $data['lunch_note'] : null,
            'updated_by' => (int) $user_id,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        if ($existing) {
            $this->db->where('id', (int) $existing->id)->update('meal_calendar', $payload);
            $id = (int) $existing->id;
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('meal_calendar', $payload);
            $id = (int) $this->db->insert_id();
        }

        $row = $this->get_calendar_day($meal_date);
        if ($row) {
            $settings = meal_get_settings($this->db);
            if (!empty($settings['auto_publish_announcements'])) {
                meal_sync_announcement_for_date($this->db, $meal_date, $row, $user_id);
            }
            $row = $this->get_calendar_day($meal_date);
        }
        return $row;
    }

    public function get_user_order($user_id, $meal_date)
    {
        return $this->db->where('user_id', (int) $user_id)
            ->where('meal_date', $meal_date)
            ->get('meal_orders')->row();
    }

    public function list_user_orders_range($user_id, $from, $to)
    {
        $rows = $this->db->where('user_id', (int) $user_id)
            ->where('meal_date >=', $from)
            ->where('meal_date <=', $to)
            ->get('meal_orders')->result();
        $map = array();
        foreach ($rows as $r) {
            $map[$r->meal_date] = $r;
        }
        return $map;
    }

    public function save_user_order($user_id, $meal_date, array $input, $changed_by, ?array $settings = null, $override_lock = false)
    {
        $settings = $settings ?: $this->get_settings();
        $cal = $this->get_calendar_day($meal_date);
        if (!$cal || ((int) $cal->has_breakfast === 0 && (int) $cal->has_lunch === 0)) {
            return array('ok' => false, 'changed' => false, 'error' => 'Meals are not available on this date.');
        }

        $existing = $this->get_user_order($user_id, $meal_date);
        $breakfastPlates = 0;
        $lunchTiffin = '';

        if ((int) $cal->has_breakfast === 1) {
            if (!$override_lock && !meal_is_breakfast_editable($meal_date, $settings)) {
                if ($existing) {
                    $breakfastPlates = meal_order_breakfast_plates($existing);
                }
            } else {
                $plates = isset($input['breakfast_plates']) ? (int) $input['breakfast_plates'] : 0;
                $maxPlates = isset($settings['max_breakfast_plates']) ? (int) $settings['max_breakfast_plates'] : 3;
                if ($maxPlates < 1) {
                    $maxPlates = 1;
                }
                $allowed = range(0, $maxPlates);
                $breakfastPlates = in_array($plates, $allowed, true) ? $plates : 0;
            }
        }

        if ((int) $cal->has_lunch === 1) {
            if (!$override_lock && !meal_is_lunch_editable($meal_date, $settings)) {
                if ($existing) {
                    $lunchTiffin = meal_order_lunch_tiffin($existing);
                }
            } else {
                $raw = isset($input['lunch_tiffin']) ? $input['lunch_tiffin'] : '';
                if ($raw === '' && isset($input['lunch_plates'])) {
                    $legacy = (int) $input['lunch_plates'];
                    $raw = $legacy >= 2 ? 'full' : ($legacy === 1 ? 'half' : '');
                }
                $lunchTiffin = meal_normalize_lunch_tiffin($raw);
            }
        }

        $existingBfPlates = $existing ? meal_order_breakfast_plates($existing) : 0;
        $existingLunchTiffin = $existing ? meal_order_lunch_tiffin($existing) : '';
        $additionalBfPlates = $existing ? meal_order_additional_breakfast_plates($existing) : 0;
        $additionalLunchTiffin = $existing ? meal_order_additional_lunch_tiffin($existing) : '';
        if (array_key_exists('additional_breakfast_plates', $input)) {
            $additionalBfPlates = max(0, (int) $input['additional_breakfast_plates']);
        }
        if (array_key_exists('additional_lunch_tiffin', $input)) {
            $additionalLunchTiffin = meal_normalize_lunch_tiffin($input['additional_lunch_tiffin']);
        }
        if ($breakfastPlates === 0) {
            $additionalBfPlates = 0;
        }
        if ($lunchTiffin === '') {
            $additionalLunchTiffin = '';
        }
        if ($existing
            && $existingBfPlates === $breakfastPlates
            && $existingLunchTiffin === $lunchTiffin
            && $additionalBfPlates === meal_order_additional_breakfast_plates($existing)
            && $additionalLunchTiffin === meal_order_additional_lunch_tiffin($existing)) {
            return array('ok' => true, 'changed' => false, 'id' => (int) $existing->id);
        }

        if (!$existing && $breakfastPlates === 0 && $lunchTiffin === '' && $additionalBfPlates === 0 && $additionalLunchTiffin === '') {
            return array('ok' => true, 'changed' => false);
        }

        $now = date('Y-m-d H:i:s');
        $payload = array(
            'user_id' => (int) $user_id,
            'meal_date' => $meal_date,
            'want_breakfast' => $breakfastPlates > 0 ? 1 : 0,
            'breakfast_plates' => $breakfastPlates,
            'lunch_plates' => 0,
            'lunch_tiffin' => $lunchTiffin,
            'additional_breakfast_plates' => $additionalBfPlates,
            'additional_lunch_tiffin' => $additionalLunchTiffin,
            'updated_at' => $now,
        );

        if ($existing) {
            $this->log_order_changes($existing, $payload, $changed_by);
            if (!meal_is_breakfast_editable($meal_date, $settings) && empty($existing->breakfast_locked_at)) {
                $payload['breakfast_locked_at'] = $now;
            }
            if (!meal_is_lunch_editable($meal_date, $settings) && empty($existing->lunch_locked_at)) {
                $payload['lunch_locked_at'] = $now;
            }
            $this->db->where('id', (int) $existing->id)->update('meal_orders', $payload);
            return array('ok' => true, 'changed' => true, 'id' => (int) $existing->id);
        }

        if (!meal_is_breakfast_editable($meal_date, $settings)) {
            $payload['breakfast_locked_at'] = $now;
        }
        if (!meal_is_lunch_editable($meal_date, $settings)) {
            $payload['lunch_locked_at'] = $now;
        }
        $payload['created_at'] = $now;
        $this->db->insert('meal_orders', $payload);
        $this->log_order_changes(null, $payload, $changed_by);
        return array('ok' => true, 'changed' => true, 'id' => (int) $this->db->insert_id());
    }

    private function log_order_changes($existing, array $newPayload, $changed_by)
    {
        $fields = array('breakfast_plates', 'lunch_tiffin', 'additional_breakfast_plates', 'additional_lunch_tiffin');
        foreach ($fields as $f) {
            if ($f === 'breakfast_plates') {
                $old = $existing ? (string) meal_order_breakfast_plates($existing) : '';
                $nv = (string) $newPayload['breakfast_plates'];
            } elseif ($f === 'lunch_tiffin') {
                $old = $existing ? meal_order_lunch_tiffin($existing) : '';
                $nv = (string) $newPayload['lunch_tiffin'];
            } elseif ($f === 'additional_breakfast_plates') {
                $old = $existing ? (string) meal_order_additional_breakfast_plates($existing) : '0';
                $nv = (string) (isset($newPayload['additional_breakfast_plates']) ? (int) $newPayload['additional_breakfast_plates'] : 0);
            } else {
                $old = $existing ? meal_order_additional_lunch_tiffin($existing) : '';
                $nv = (string) (isset($newPayload['additional_lunch_tiffin']) ? $newPayload['additional_lunch_tiffin'] : '');
            }
            if ($old === $nv) {
                continue;
            }
            $this->db->insert('meal_order_log', array(
                'user_id' => (int) $newPayload['user_id'],
                'meal_date' => $newPayload['meal_date'],
                'field_name' => $f,
                'old_value' => $old,
                'new_value' => $nv,
                'changed_by' => (int) $changed_by,
            ));
        }
    }

    public function provider_counts($meal_date)
    {
        $cal = $this->get_calendar_day($meal_date);

        $rows = $this->db->select('o.*, u.name AS user_name')
            ->from('meal_orders o')
            ->join('users u', 'u.id = o.user_id', 'left')
            ->where('o.meal_date', $meal_date)
            ->order_by('u.name', 'ASC')
            ->get()->result();

        $orders = array();
        foreach ($rows as $o) {
            if (meal_order_total_breakfast_plates($o) > 0 || meal_order_lunch_tiffin($o) !== '' || meal_order_additional_lunch_tiffin($o) !== '') {
                $orders[] = $o;
            }
        }

        $breakfastPlates = 0;
        $breakfastOrders = 0;
        $lunchHalf = 0;
        $lunchFull = 0;
        $lunchOrders = 0;
        foreach ($orders as $o) {
            $bp = meal_order_total_breakfast_plates($o);
            if ($bp > 0) {
                $breakfastPlates += $bp;
                $breakfastOrders++;
            }
            foreach (array(meal_order_lunch_tiffin($o), meal_order_additional_lunch_tiffin($o)) as $lt) {
                if ($lt === 'half') {
                    $lunchHalf++;
                    $lunchOrders++;
                } elseif ($lt === 'full') {
                    $lunchFull++;
                    $lunchOrders++;
                }
            }
        }

        return array(
            'meal_date' => $meal_date,
            'calendar' => $cal,
            'breakfast_plates' => $breakfastPlates,
            'breakfast_orders' => $breakfastOrders,
            'breakfast_yes' => $breakfastOrders,
            'lunch_plates' => $lunchHalf + $lunchFull,
            'lunch_half' => $lunchHalf,
            'lunch_full' => $lunchFull,
            'lunch_orders' => $lunchOrders,
            'orders' => $orders,
        );
    }

    public function list_order_history($filters = array(), $limit = 200)
    {
        $this->db->select('l.*, u.name AS user_name, c.name AS changed_by_name')
            ->from('meal_order_log l')
            ->join('users u', 'u.id = l.user_id', 'left')
            ->join('users c', 'c.id = l.changed_by', 'left')
            ->order_by('l.changed_at', 'DESC')
            ->limit((int) $limit);

        if (!empty($filters['meal_date'])) {
            $this->db->where('l.meal_date', $filters['meal_date']);
        }
        if (!empty($filters['user_id'])) {
            $this->db->where('l.user_id', (int) $filters['user_id']);
        }
        return $this->db->get()->result();
    }

    /**
     * All active users with meal order for a date (left join — empty = no order).
     *
     * @param string $meal_date
     * @param bool $only_with_orders
     * @return array
     */
    public function list_all_orders_for_date($meal_date, $only_with_orders = false)
    {
        $this->db->select('u.id AS user_id, u.name AS user_name, o.id AS order_id, o.breakfast_plates, o.want_breakfast, o.lunch_tiffin, o.lunch_plates, o.additional_breakfast_plates, o.additional_lunch_tiffin, o.breakfast_locked_at, o.lunch_locked_at, o.updated_at')
            ->from('users u')
            ->join(
                'meal_orders o',
                'o.user_id = u.id AND o.meal_date = ' . $this->db->escape($meal_date),
                'left',
                false
            )
            ->where('u.status', 'active')
            ->order_by('u.name', 'ASC');

        $rows = $this->db->get()->result();
        if (!$only_with_orders) {
            return $rows;
        }

        $filtered = array();
        foreach ($rows as $row) {
            $bf = meal_order_total_breakfast_plates($row);
            $lt = meal_order_lunch_tiffin($row);
            if ($bf > 0 || $lt !== '' || meal_order_additional_lunch_tiffin($row) !== '') {
                $filtered[] = $row;
            }
        }
        return $filtered;
    }

    public function apply_auto_locks_for_date($meal_date)
    {
        $settings = $this->get_settings();
        $now = date('Y-m-d H:i:s');
        $orders = $this->db->where('meal_date', $meal_date)->get('meal_orders')->result();
        foreach ($orders as $o) {
            $upd = array();
            if (!meal_is_breakfast_editable($meal_date, $settings) && empty($o->breakfast_locked_at)) {
                $upd['breakfast_locked_at'] = $now;
            }
            if (!meal_is_lunch_editable($meal_date, $settings) && empty($o->lunch_locked_at)) {
                $upd['lunch_locked_at'] = $now;
            }
            if (!empty($upd)) {
                $this->db->where('id', (int) $o->id)->update('meal_orders', $upd);
            }
        }
    }

    public function get_user_request_for_meal($user_id, $meal_date, $meal_type, $status = 'pending')
    {
        meal_schema_ensure($this->db);
        if (!$this->db->table_exists('meal_change_requests')) {
            return null;
        }
        return $this->db->where('user_id', (int) $user_id)
            ->where('meal_date', $meal_date)
            ->where('meal_type', $meal_type)
            ->where('status', $status)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('meal_change_requests')->row();
    }

    public function list_user_requests_map($user_id, $meal_date)
    {
        meal_schema_ensure($this->db);
        $map = array('breakfast' => null, 'lunch' => null);
        if (!$this->db->table_exists('meal_change_requests')) {
            return $map;
        }
        $rows = $this->db->where('user_id', (int) $user_id)
            ->where('meal_date', $meal_date)
            ->order_by('id', 'DESC')
            ->get('meal_change_requests')->result();

        $byType = array('breakfast' => array(), 'lunch' => array());
        foreach ($rows as $r) {
            $type = (string) $r->meal_type;
            if (isset($byType[$type])) {
                $byType[$type][] = $r;
            }
        }

        foreach (array('breakfast', 'lunch') as $type) {
            $list = isset($byType[$type]) ? $byType[$type] : array();
            foreach ($list as $r) {
                if ($r->status === 'pending') {
                    $map[$type] = $r;
                    break;
                }
            }
            if ($map[$type] !== null) {
                continue;
            }
            foreach ($list as $r) {
                if ($r->status === 'approved') {
                    $map[$type] = $r;
                    break;
                }
            }
            if ($map[$type] !== null) {
                continue;
            }
            foreach ($list as $r) {
                if ($r->status === 'rejected') {
                    $map[$type] = $r;
                    break;
                }
            }
        }
        return $map;
    }

    public function list_change_requests($meal_date, $status = 'pending')
    {
        meal_schema_ensure($this->db);
        if (!$this->db->table_exists('meal_change_requests')) {
            return array();
        }
        $this->db->select('r.*, u.name AS user_name, rev.name AS reviewed_by_name')
            ->from('meal_change_requests r')
            ->join('users u', 'u.id = r.user_id', 'left')
            ->join('users rev', 'rev.id = r.reviewed_by', 'left')
            ->where('r.meal_date', $meal_date)
            ->order_by('r.created_at', 'ASC');
        if ($status !== '') {
            $this->db->where('r.status', $status);
        }
        return $this->db->get()->result();
    }

    public function create_change_request($user_id, $meal_date, $meal_type, $requested_value, $employee_note = '')
    {
        meal_schema_ensure($this->db);
        $settings = $this->get_settings();
        $meal_type = $meal_type === 'lunch' ? 'lunch' : 'breakfast';
        $cal = $this->get_calendar_day($meal_date);
        if (!$cal) {
            return array('ok' => false, 'error' => 'Meals are not scheduled on this date.');
        }
        if ($meal_type === 'breakfast' && (int) $cal->has_breakfast !== 1) {
            return array('ok' => false, 'error' => 'Breakfast is not available on this date.');
        }
        if ($meal_type === 'lunch' && (int) $cal->has_lunch !== 1) {
            return array('ok' => false, 'error' => 'Lunch is not available on this date.');
        }
        if ($meal_type === 'breakfast' && meal_is_breakfast_editable($meal_date, $settings)) {
            return array('ok' => false, 'error' => 'Breakfast is still open — change your order directly.');
        }
        if ($meal_type === 'lunch' && meal_is_lunch_editable($meal_date, $settings)) {
            return array('ok' => false, 'error' => 'Lunch is still open — change your order directly.');
        }

        if ($meal_type === 'breakfast') {
            $plates = (int) $requested_value;
            $max = isset($settings['max_breakfast_plates']) ? (int) $settings['max_breakfast_plates'] : 3;
            if ($max < 1) {
                $max = 1;
            }
            if (!in_array($plates, range(0, $max), true)) {
                return array('ok' => false, 'error' => 'Invalid breakfast plate count.');
            }
            $requested_value = (string) $plates;
        } elseif (meal_change_is_additional($requested_value)) {
            $reqKey = strtolower(trim((string) $requested_value));
            $order = $this->get_user_order($user_id, $meal_date);
            if ($meal_type === 'breakfast' && $reqKey === 'add_bf:1') {
                $main = $order ? meal_order_breakfast_plates($order) : 0;
                if ($main < 1) {
                    return array('ok' => false, 'error' => 'Order breakfast first before requesting an additional plate.');
                }
                if ($order && meal_order_additional_breakfast_plates($order) > 0) {
                    return array('ok' => false, 'error' => 'Additional breakfast plate is already on your order.');
                }
                $max = isset($settings['max_breakfast_plates']) ? (int) $settings['max_breakfast_plates'] : 3;
                if ($main + 1 > $max) {
                    return array('ok' => false, 'error' => 'Cannot add another plate — max limit reached.');
                }
                $requested_value = 'add_bf:1';
            } elseif ($meal_type === 'lunch' && ($reqKey === 'add_lu:half' || $reqKey === 'add_lu:full')) {
                $main = $order ? meal_order_lunch_tiffin($order) : '';
                if ($main === '') {
                    return array('ok' => false, 'error' => 'Order lunch first before requesting an additional tiffin.');
                }
                if ($order && meal_order_additional_lunch_tiffin($order) !== '') {
                    return array('ok' => false, 'error' => 'Additional lunch tiffin is already on your order.');
                }
                $requested_value = $reqKey;
            } else {
                return array('ok' => false, 'error' => 'Invalid additional meal request.');
            }
        } else {
            $requested_value = meal_normalize_lunch_tiffin($requested_value);
            if ($requested_value === '' && $requested_value !== '0') {
                // allow empty = no lunch
            }
        }

        $existingReq = $this->get_user_request_for_meal($user_id, $meal_date, $meal_type, 'pending');
        if ($existingReq) {
            return array('ok' => false, 'error' => 'You already have a pending request for this meal.');
        }

        if (!isset($order)) {
            $order = $this->get_user_order($user_id, $meal_date);
        }
        $current = $meal_type === 'breakfast'
            ? (string) ($order ? meal_order_breakfast_plates($order) : 0)
            : ($order ? meal_order_lunch_tiffin($order) : '');

        if (!meal_change_is_additional($requested_value) && meal_change_values_equal($meal_type, $current, $requested_value)) {
            return array('ok' => false, 'error' => 'Requested value is the same as your current order.');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->insert('meal_change_requests', array(
            'user_id' => (int) $user_id,
            'meal_date' => $meal_date,
            'meal_type' => $meal_type,
            'current_value' => $current,
            'requested_value' => $requested_value,
            'employee_note' => $employee_note !== '' ? $employee_note : null,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ));

        $request_id = (int) $this->db->insert_id();
        if ($request_id > 0) {
            $this->load->helper('meal_notify');
            meal_notify_request_submitted($request_id);
        }

        return array('ok' => true, 'id' => $request_id);
    }

    public function review_change_request($request_id, $reviewer_id, $decision, $review_note = '')
    {
        meal_schema_ensure($this->db);
        $req = $this->db->where('id', (int) $request_id)->get('meal_change_requests')->row();
        if (!$req) {
            return array('ok' => false, 'error' => 'Request not found.');
        }
        if ($req->status !== 'pending') {
            return array('ok' => false, 'error' => 'This request was already reviewed.');
        }

        $decision = strtolower(trim((string) $decision));
        if ($decision !== 'approved' && $decision !== 'rejected') {
            return array('ok' => false, 'error' => 'Invalid decision.');
        }

        $now = date('Y-m-d H:i:s');
        if ($decision === 'rejected') {
            $this->db->where('id', (int) $req->id)->update('meal_change_requests', array(
                'status' => 'rejected',
                'reviewed_by' => (int) $reviewer_id,
                'review_note' => $review_note !== '' ? $review_note : null,
                'reviewed_at' => $now,
                'updated_at' => $now,
            ));
            $this->load->helper('meal_notify');
            meal_notify_request_reviewed((int) $req->id, 'rejected', $review_note);
            return array('ok' => true, 'status' => 'rejected');
        }

        $existing = $this->get_user_order((int) $req->user_id, $req->meal_date);
        $input = array(
            'breakfast_plates' => $existing ? meal_order_breakfast_plates($existing) : 0,
            'lunch_tiffin' => $existing ? meal_order_lunch_tiffin($existing) : '',
            'additional_breakfast_plates' => $existing ? meal_order_additional_breakfast_plates($existing) : 0,
            'additional_lunch_tiffin' => $existing ? meal_order_additional_lunch_tiffin($existing) : '',
        );
        $requested = strtolower(trim((string) $req->requested_value));
        if (meal_change_is_additional($requested)) {
            if ($req->meal_type === 'breakfast' && $requested === 'add_bf:1') {
                $input['additional_breakfast_plates'] = (int) $input['additional_breakfast_plates'] + 1;
            } elseif ($req->meal_type === 'lunch' && ($requested === 'add_lu:half' || $requested === 'add_lu:full')) {
                $input['additional_lunch_tiffin'] = $requested === 'add_lu:full' ? 'full' : 'half';
            }
        } elseif ($req->meal_type === 'breakfast') {
            $input['breakfast_plates'] = (int) $req->requested_value;
            if ((int) $req->requested_value === 0) {
                $input['additional_breakfast_plates'] = 0;
            }
        } else {
            $input['lunch_tiffin'] = meal_normalize_lunch_tiffin($req->requested_value);
            if ($input['lunch_tiffin'] === '') {
                $input['additional_lunch_tiffin'] = '';
            }
        }

        $save = $this->save_user_order((int) $req->user_id, $req->meal_date, $input, (int) $reviewer_id, null, true);
        if (empty($save['ok'])) {
            return array('ok' => false, 'error' => isset($save['error']) ? $save['error'] : 'Could not apply order.');
        }

        $this->db->where('id', (int) $req->id)->update('meal_change_requests', array(
            'status' => 'approved',
            'reviewed_by' => (int) $reviewer_id,
            'review_note' => $review_note !== '' ? $review_note : null,
            'reviewed_at' => $now,
            'updated_at' => $now,
        ));

        $this->load->helper('meal_notify');
        meal_notify_request_reviewed((int) $req->id, 'approved', $review_note);

        return array('ok' => true, 'status' => 'approved');
    }
}
