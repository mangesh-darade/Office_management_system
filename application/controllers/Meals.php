<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meals extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission', 'meal', 'meal_schema'));
        $this->load->library('session');
        $this->load->model('Meal_model', 'meals');
        meal_schema_ensure($this->db);
        if (!meal_has_any_access()) {
            require_meal_access('meals_order', true);
        }
    }

    /** Employee: order breakfast / lunch for upcoming days */
    public function index()
    {
        if (!meal_can_order()) {
            if (meal_has_any_access()) {
                redirect(meal_default_route());
                return;
            }
            require_meal_access('meals_order', true);
        }
        $uid = (int) $this->session->userdata('user_id');
        $settings = $this->meals->get_settings();
        $from = date('Y-m-d');
        $to = date('Y-m-d', strtotime('+1 day'));
        $calendar = $this->meals->list_calendar_range($from, $to);
        $calMap = array();
        foreach ($calendar as $c) {
            $calMap[$c->meal_date] = $c;
        }
        $orders = $this->meals->list_user_orders_range($uid, $from, $to);

        $this->load->view('meals/index', array(
            'settings' => $settings,
            'from' => $from,
            'to' => $to,
            'tomorrow' => $to,
            'cal_map' => $calMap,
            'orders' => $orders,
            'change_requests' => $this->meals->list_user_requests_map($uid, $from),
        ));
    }

    /** AJAX: save today's meal order on flag / plate change */
    public function save_order()
    {
        require_meal_access('meals_order', true);
        if ($this->input->method() !== 'post') {
            return $this->meal_json(array('ok' => false, 'error' => 'Invalid request.'), 405);
        }

        $uid = (int) $this->session->userdata('user_id');
        $settings = $this->meals->get_settings();
        $today = date('Y-m-d');
        $meal_date = preg_replace('/[^0-9\-]/', '', (string) $this->input->post('meal_date'));
        if ($meal_date !== $today) {
            return $this->meal_json(array('ok' => false, 'error' => 'Only today\'s order can be updated.'));
        }

        $fields = array(
            'breakfast_plates' => $this->input->post('breakfast_plates'),
            'lunch_tiffin' => $this->input->post('lunch_tiffin'),
        );
        $res = $this->meals->save_user_order($uid, $meal_date, $fields, $uid, $settings);
        if (empty($res['ok'])) {
            return $this->meal_json(array(
                'ok' => false,
                'error' => isset($res['error']) ? $res['error'] : 'Could not save order.',
            ));
        }

        $order = $this->meals->get_user_order($uid, $meal_date);
        return $this->meal_json(array(
            'ok' => true,
            'changed' => !empty($res['changed']),
            'message' => !empty($res['changed']) ? 'Saved.' : 'No change.',
            'breakfast_plates' => $order ? meal_order_breakfast_plates($order) : 0,
            'lunch_tiffin' => $order ? meal_order_lunch_tiffin($order) : '',
            'breakfast_locked' => !meal_is_breakfast_editable($meal_date, $settings),
            'lunch_locked' => !meal_is_lunch_editable($meal_date, $settings),
        ));
    }

    /** AJAX: submit change request after cut-off lock */
    public function submit_request()
    {
        require_meal_access('meals_order', true);
        if ($this->input->method() !== 'post') {
            return $this->meal_json(array('ok' => false, 'error' => 'Invalid request.'), 405);
        }

        $uid = (int) $this->session->userdata('user_id');
        $today = date('Y-m-d');
        $meal_date = preg_replace('/[^0-9\-]/', '', (string) $this->input->post('meal_date'));
        if ($meal_date !== $today) {
            return $this->meal_json(array('ok' => false, 'error' => 'Requests are only allowed for today.'));
        }

        $meal_type = $this->input->post('meal_type') === 'lunch' ? 'lunch' : 'breakfast';
        $requested = $this->input->post('requested_value');
        $note = trim((string) $this->input->post('employee_note'));

        $res = $this->meals->create_change_request($uid, $meal_date, $meal_type, $requested, $note);
        if (empty($res['ok'])) {
            return $this->meal_json(array('ok' => false, 'error' => isset($res['error']) ? $res['error'] : 'Could not submit request.'));
        }

        return $this->meal_json(array(
            'ok' => true,
            'message' => 'Request sent to provider for approval.',
            'id' => (int) $res['id'],
        ));
    }

    /** Provider: approve or reject a change request */
    public function review_request()
    {
        require_meal_access('meals_provider', true);
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
        }

        $request_id = (int) $this->input->post('request_id');
        $decision = $this->input->post('decision');
        $note = trim((string) $this->input->post('review_note'));
        $date = preg_replace('/[^0-9\-]/', '', (string) $this->input->post('date'));
        $month = preg_replace('/[^0-9\-]/', '', (string) $this->input->post('month'));
        $reviewer = (int) $this->session->userdata('user_id');

        $res = $this->meals->review_change_request($request_id, $reviewer, $decision, $note);
        if (empty($res['ok'])) {
            $this->session->set_flashdata('error', isset($res['error']) ? $res['error'] : 'Could not review request.');
        } else {
            $label = ($res['status'] === 'approved') ? 'approved' : 'rejected';
            $this->session->set_flashdata('success', 'Request ' . $label . '.');
        }

        $redirect = 'meals/provider';
        if ($date !== '') {
            $redirect .= '?date=' . urlencode($date);
            if ($month !== '') {
                $redirect .= '&month=' . urlencode($month);
            }
        }
        redirect($redirect);
    }

    private function meal_json(array $data, $status = 200)
    {
        $this->output->set_status_header((int) $status);
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    /** Admin: meal calendar — which dates have breakfast/lunch */
    public function calendar()
    {
        require_meal_access('meals_calendar', true);
        $month = $this->input->get('month') ?: date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));
        $uid = (int) $this->session->userdata('user_id');

        if ($this->input->method() === 'post') {
            $days = $this->input->post('days');
            if (is_array($days)) {
                foreach ($days as $meal_date => $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $meal_date = preg_replace('/[^0-9\-]/', '', (string) $meal_date);
                    if ($meal_date === '') {
                        continue;
                    }
                    $this->meals->save_calendar_day($meal_date, array(
                        'has_breakfast' => !empty($row['has_breakfast']),
                        'has_lunch' => !empty($row['has_lunch']),
                        'breakfast_note' => isset($row['breakfast_note']) ? trim((string) $row['breakfast_note']) : '',
                        'lunch_note' => isset($row['lunch_note']) ? trim((string) $row['lunch_note']) : '',
                    ), $uid);
                }
            }
            $this->session->set_flashdata('success', 'Meal calendar updated.');
            redirect('meals/calendar?month=' . urlencode($month));
            return;
        }

        $rows = $this->meals->list_calendar_range($from, $to);
        $calMap = array();
        foreach ($rows as $r) {
            $calMap[$r->meal_date] = $r;
        }

        $this->load->view('meals/calendar', array(
            'month' => $month,
            'from' => $from,
            'to' => $to,
            'cal_map' => $calMap,
            'settings' => $this->meals->get_settings(),
        ));
    }

    /** Meal provider: counts + calendar overview */
    public function provider()
    {
        require_meal_access('meals_provider', true);
        $date = $this->input->get('date') ?: date('Y-m-d');
        $date = preg_replace('/[^0-9\-]/', '', $date);
        if ($date === '') {
            $date = date('Y-m-d');
        }

        $month = $this->input->get('month') ?: substr($date, 0, 7);
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));
        $calendar = $this->meals->list_calendar_range($from, $to);
        $counts = $this->meals->provider_counts($date);
        $pendingRequests = $this->meals->list_change_requests($date, 'pending');
        $pendingMap = array();
        foreach ($pendingRequests as $pr) {
            $uid = (int) $pr->user_id;
            if (!isset($pendingMap[$uid])) {
                $pendingMap[$uid] = array('breakfast' => null, 'lunch' => null, 'user_name' => $pr->user_name);
            }
            $pendingMap[$uid][$pr->meal_type] = $pr;
            if (!empty($pr->user_name)) {
                $pendingMap[$uid]['user_name'] = $pr->user_name;
            }
        }

        $this->load->view('meals/provider', array(
            'date' => $date,
            'month' => $month,
            'calendar' => $calendar,
            'counts' => $counts,
            'pending_requests' => $pendingRequests,
            'pending_map' => $pendingMap,
            'settings' => $this->meals->get_settings(),
        ));
    }

    public function settings()
    {
        require_meal_access('meals_settings', true);
        $uid = (int) $this->session->userdata('user_id');
        $weekMenu = $this->meals->get_week_menu_template();
        $settings = $this->meals->get_settings();
        $defaultWeekStart = meal_monday_of(date('Y-m-d'));

        if ($this->input->method() === 'post') {
            $action = trim((string) $this->input->post('action'));
            if ($action === 'apply_week') {
                if (!meal_can_access('meals_calendar') && !meal_can_access('meals_settings')) {
                    show_error('Access denied', 403);
                }
                $weekStart = $this->input->post('week_start') ?: $defaultWeekStart;
                $count = $this->meals->apply_week_menu_to_calendar($weekStart, $uid);
                $this->session->set_flashdata('success', 'Weekly menu applied to ' . $count . ' day(s) on the calendar (Mon–Sun).');
                redirect('meals/settings');
                return;
            }

            $bf = $this->input->post('breakfast_cutoff') ?: '08:30';
            $lu = $this->input->post('lunch_cutoff') ?: '11:00';
            $this->meals->save_settings(array(
                'breakfast_cutoff' => strlen($bf) === 5 ? $bf . ':00' : $bf,
                'lunch_cutoff' => strlen($lu) === 5 ? $lu . ':00' : $lu,
                'max_breakfast_plates' => (int) $this->input->post('max_breakfast_plates'),
                'auto_publish_announcements' => $this->input->post('auto_publish_announcements') ? 1 : 0,
                'skip_weekends_on_apply' => $this->input->post('skip_weekends_on_apply') ? 1 : 0,
                'show_dashboard_announcement' => $this->input->post('show_dashboard_announcement') ? 1 : 0,
                'provider_contact' => trim((string) $this->input->post('provider_contact')),
            ));

            $weekDays = $this->input->post('week_menu');
            if (is_array($weekDays)) {
                $this->meals->save_week_menu_template($weekDays);
            }

            $this->session->set_flashdata('success', 'Meal settings and weekly menu saved.');
            redirect('meals/settings');
            return;
        }

        $this->load->view('meals/settings', array(
            'settings' => $settings,
            'week_menu' => $weekMenu,
            'day_labels' => meal_week_day_labels(),
            'default_week_start' => $defaultWeekStart,
            'can_apply_calendar' => meal_can_access('meals_settings') || meal_can_access('meals_calendar'),
        ));
    }

    public function history()
    {
        require_meal_access('meals_history', true);
        $filters = array(
            'meal_date' => $this->input->get('date') ?: '',
            'user_id' => (int) $this->input->get('user_id'),
        );
        $this->load->view('meals/history', array(
            'rows' => $this->meals->list_order_history($filters, 300),
            'filters' => $filters,
            'users' => $this->db->select('id, name')->where('status', 'active')->order_by('name')->get('users')->result(),
        ));
    }

    /** Admin: all employee orders for a date */
    public function all_orders()
    {
        if (!meal_can_view_all_orders()) {
            require_meal_access('meals_all_orders', true);
        }
        $date = $this->input->get('date') ?: date('Y-m-d');
        $date = preg_replace('/[^0-9\-]/', '', $date);
        if ($date === '') {
            $date = date('Y-m-d');
        }
        $onlyWithOrders = $this->input->get('only') === '1';
        $settings = $this->meals->get_settings();
        $counts = $this->meals->provider_counts($date);
        $rows = $this->meals->list_all_orders_for_date($date, $onlyWithOrders);

        $this->load->view('meals/all_orders', array(
            'date' => $date,
            'settings' => $settings,
            'counts' => $counts,
            'rows' => $rows,
            'only_with_orders' => $onlyWithOrders,
            'calendar' => $this->meals->get_calendar_day($date),
        ));
    }

    public function export()
    {
        require_meal_access(array('meals_all_orders', 'meals_provider', 'meals_history'), true);
        $date = $this->input->get('date') ?: date('Y-m-d');
        $date = preg_replace('/[^0-9\-]/', '', $date);
        $counts = $this->meals->provider_counts($date);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=meal_orders_' . $date . '.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('Meal date', $date));
        fputcsv($out, array('Breakfast total plates', $counts['breakfast_plates']));
        fputcsv($out, array('Lunch half tiffin', $counts['lunch_half']));
        fputcsv($out, array('Lunch full tiffin', $counts['lunch_full']));
        fputcsv($out, array(''));
        fputcsv($out, array('Employee', 'Breakfast plates', 'Lunch tiffin'));
        foreach ($counts['orders'] as $o) {
            $bf = meal_order_total_breakfast_plates($o);
            $ltMain = meal_order_lunch_tiffin($o);
            $ltAdd = meal_order_additional_lunch_tiffin($o);
            fputcsv($out, array(
                $o->user_name,
                $bf > 0 ? meal_order_breakfast_display($o) : '',
                ($ltMain !== '' || $ltAdd !== '') ? meal_order_lunch_display($o) : '',
            ));
        }
        fclose($out);
        exit;
    }
}
