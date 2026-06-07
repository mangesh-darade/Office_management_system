<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Office Meals — in-app notifications for locked-order change requests.
 */

if (!function_exists('meal_notify_provider_user_ids')) {
    /**
     * User IDs with meals_provider permission (strict — no Super Admin bypass).
     *
     * @param int $exclude_user_id Optional user to skip (e.g. requester)
     * @return int[]
     */
    function meal_notify_provider_user_ids($exclude_user_id = 0)
    {
        $CI =& get_instance();
        if (!isset($CI->db) || !$CI->db->table_exists('users')) {
            return array();
        }

        $role_ids = array();
        if ($CI->db->table_exists('permissions')) {
            $rows = $CI->db->select('role_id')
                ->where('can_access', 1)
                ->where('module', 'meals_provider')
                ->get('permissions')->result();
            foreach ($rows as $row) {
                $role_ids[] = (int) $row->role_id;
            }
        }
        $role_ids = array_values(array_unique(array_filter($role_ids)));

        $CI->db->select('id')->from('users')->where_in('role_id', $role_ids);
        if ((int) $exclude_user_id > 0) {
            $CI->db->where('id !=', (int) $exclude_user_id);
        }
        $users = $CI->db->get()->result();

        $ids = array();
        foreach ($users as $u) {
            $ids[] = (int) $u->id;
        }
        return array_values(array_unique($ids));
    }
}

if (!function_exists('meal_notify_fetch_request')) {
    function meal_notify_fetch_request($request_id)
    {
        $CI =& get_instance();
        if (!$CI->db->table_exists('meal_change_requests')) {
            return null;
        }
        return $CI->db->select('r.*, u.name AS user_name, rev.name AS reviewed_by_name')
            ->from('meal_change_requests r')
            ->join('users u', 'u.id = r.user_id', 'left')
            ->join('users rev', 'rev.id = r.reviewed_by', 'left')
            ->where('r.id', (int) $request_id)
            ->get()->row();
    }
}

if (!function_exists('meal_notify_in_app')) {
    function meal_notify_in_app($user_id, $title, $message, $type, $request_id, $action_url)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return false;
        }
        $CI =& get_instance();
        if (!$CI->db->table_exists('notifications')) {
            return false;
        }
        $CI->load->model('Notification_model', 'meal_notif');
        return (bool) $CI->meal_notif->create(
            $user_id,
            $title,
            $message,
            $type,
            'meals',
            (int) $request_id,
            $action_url
        );
    }
}

if (!function_exists('meal_notify_request_submitted')) {
    /**
     * Notify meal providers when an employee submits a locked-order change request.
     */
    function meal_notify_request_submitted($request_id)
    {
        $CI =& get_instance();
        $CI->load->helper('meal');
        $req = meal_notify_fetch_request($request_id);
        if (!$req) {
            return;
        }

        $mealLabel = $req->meal_type === 'lunch' ? 'Lunch' : 'Breakfast';
        $employee = !empty($req->user_name) ? $req->user_name : 'An employee';
        $dateLabel = date('D, d M Y', strtotime($req->meal_date));
        $changeSummary = meal_format_change_summary($req->meal_type, $req->current_value, $req->requested_value);
        $providerUrl = site_url('meals/provider?date=' . urlencode($req->meal_date));

        $title = 'Meal change request — ' . $mealLabel;
        $message = $employee . ' requested a ' . strtolower($mealLabel) . ' change for ' . $dateLabel
            . ': ' . $changeSummary . '.';

        $provider_ids = meal_notify_provider_user_ids((int) $req->user_id);
        if (!empty($provider_ids) && $CI->db->table_exists('notifications')) {
            $CI->load->model('Notification_model', 'meal_notif');
            $CI->meal_notif->create_bulk(
                $provider_ids,
                $title,
                $message,
                'warning',
                'meals',
                (int) $req->id,
                $providerUrl
            );
        }
    }
}

if (!function_exists('meal_notify_request_reviewed')) {
    /**
     * Notify employee when provider approves or rejects their change request.
     */
    function meal_notify_request_reviewed($request_id, $status, $review_note = '')
    {
        $CI =& get_instance();
        $CI->load->helper('meal');
        $req = meal_notify_fetch_request($request_id);
        if (!$req || (int) $req->user_id < 1) {
            return;
        }

        $status = strtolower(trim((string) $status));
        if ($status !== 'approved' && $status !== 'rejected') {
            return;
        }

        $mealLabel = $req->meal_type === 'lunch' ? 'Lunch' : 'Breakfast';
        $dateLabel = date('D, d M Y', strtotime($req->meal_date));
        $requested = meal_format_request_value($req->meal_type, $req->requested_value);
        $mealsUrl = site_url('meals');
        $approved = ($status === 'approved');

        if ($approved) {
            $title = 'Meal change approved';
            $type = 'success';
            $message = 'Your ' . strtolower($mealLabel) . ' change for ' . $dateLabel . ' was approved: '
                . $requested . '.';
        } else {
            $title = 'Meal change rejected';
            $type = 'error';
            $message = 'Your ' . strtolower($mealLabel) . ' change for ' . $dateLabel . ' was rejected.';
            if ($review_note !== '') {
                $message .= ' Note: ' . $review_note;
            }
        }

        meal_notify_in_app((int) $req->user_id, $title, $message, $type, (int) $req->id, $mealsUrl);
    }
}
