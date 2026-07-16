<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Todays_plan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array(
            'url', 'form', 'permission', 'schema_columns',
            'todays_plan', 'todays_plan_schema', 'reminders_user',
        ));
        $this->load->library('session');
        $this->load->model('Todays_plan_model', 'plan');
        $this->load->model('Reminder_model', 'reminders');
        $this->reminders->ensure_schema();
        require_module_access(array('todays_plan', 'todays_plan_manage', 'my_works', 'my_works_list'), true);
    }

    public function index()
    {
        $user_id = (int) $this->session->userdata('user_id');
        $tab = strtolower(trim((string) $this->input->get('tab')));
        if ($tab === 'history') {
            $this->history();
            return;
        }

        $plan_date = trim((string) $this->input->get('date'));
        if ($plan_date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $plan_date)) {
            $plan_date = date('Y-m-d');
        }

        $items = $this->plan->list_for_user_date($user_id, $plan_date);

        $this->load->view('todays_plan/index', array(
            'items'         => $items,
            'plan_date'     => $plan_date,
            'active_tab'    => 'today',
            'repeat_types'  => todays_plan_repeat_types(),
            'status_labels' => todays_plan_status_labels(),
        ));
    }

    public function history()
    {
        $user_id = (int) $this->session->userdata('user_id');
        $history_date = trim((string) $this->input->get('date'));
        if ($history_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $history_date)) {
            $history_date = '';
        }

        $history_days = $this->plan->list_history_days($user_id, date('Y-m-d'), 45);
        $history_items = array();
        if ($history_date !== '') {
            $history_items = $this->plan->list_history_items($user_id, $history_date, date('Y-m-d'), 200);
        }

        $this->load->view('todays_plan/index', array(
            'items'          => array(),
            'plan_date'      => date('Y-m-d'),
            'active_tab'     => 'history',
            'history_days'   => $history_days,
            'history_date'   => $history_date,
            'history_items'  => $history_items,
            'repeat_types'   => todays_plan_repeat_types(),
            'status_labels'  => todays_plan_status_labels(),
        ));
    }

    public function save()
    {
        if ($this->input->method() !== 'post') {
            redirect('todays-plan');
            return;
        }

        $user_id = (int) $this->session->userdata('user_id');
        $plan_date = trim((string) $this->input->post('plan_date'));
        $sync_google = (int) $this->input->post('sync_google') === 1;

        if ($plan_date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $plan_date)) {
            $plan_date = date('Y-m-d');
        }

        $rows = $this->_parse_plan_rows();
        if (empty($rows)) {
            $this->session->set_flashdata('error', 'Add at least one plan point with time and title.');
            redirect('todays-plan?date=' . urlencode($plan_date));
            return;
        }

        $saved = 0;
        $synced = 0;
        $sync_failed = 0;
        $this->load->helper('reminders_google');

        foreach ($rows as $row) {
            $id = $this->plan->insert(array(
                'user_id'   => $user_id,
                'plan_date' => $plan_date,
                'plan_time' => $row['plan_time'],
                'title'     => $row['title'],
                'details'   => $row['details'],
                'link_type' => $row['link_type'],
                'link_id'   => $row['link_id'],
                'repeat_type' => $row['repeat_type'],
            ));
            if ($id <= 0) {
                continue;
            }
            $saved++;
            if ($sync_google) {
                $result = todays_plan_sync_google($this->plan, $this->reminders, $id);
                if (!empty($result['ok'])) {
                    $synced++;
                } else {
                    $sync_failed++;
                }
            }
        }

        if ($saved <= 0) {
            $this->session->set_flashdata('error', 'Could not save plan items.');
            redirect('todays-plan?date=' . urlencode($plan_date));
            return;
        }

        $msg = $saved === 1
            ? '1 plan point saved.'
            : $saved . ' plan points saved.';
        if ($sync_google) {
            $msg .= ' Google alerts: ' . $synced . ' scheduled';
            if ($sync_failed > 0) {
                $msg .= ', ' . $sync_failed . ' failed';
            }
            $msg .= '.';
        }
        $this->session->set_flashdata('success', $msg);
        redirect('todays-plan?date=' . urlencode($plan_date));
    }

    public function update($id)
    {
        if ($this->input->method() !== 'post') {
            redirect('todays-plan');
            return;
        }

        $user_id = (int) $this->session->userdata('user_id');
        $id = (int) $id;
        $item = $this->plan->get($id);
        if (!$item || (int) $item->user_id !== $user_id) {
            show_error('Access denied', 403);
            return;
        }

        $plan_date = trim((string) $this->input->post('plan_date'));
        if ($plan_date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $plan_date)) {
            $plan_date = isset($item->plan_date) ? $item->plan_date : date('Y-m-d');
        }

        $plan_time = trim((string) $this->input->post('plan_time'));
        $title = trim((string) $this->input->post('title'));
        $details = trim((string) $this->input->post('details'));
        $repeat_type = trim((string) $this->input->post('repeat_type'));
        $sync_google = (int) $this->input->post('sync_google') === 1;

        if ($plan_time === '' || $title === '') {
            $this->session->set_flashdata('error', 'Time and title are required.');
            redirect('todays-plan?date=' . urlencode($plan_date));
            return;
        }
        if (strlen($plan_time) === 5) {
            $plan_time .= ':00';
        }

        $allowed_repeat = array_keys(todays_plan_repeat_types());
        if (!in_array($repeat_type, $allowed_repeat, true)) {
            $repeat_type = 'once';
        }

        $this->plan->update($id, array(
            'plan_date'   => $plan_date,
            'plan_time'   => $plan_time,
            'title'       => $title,
            'details'     => $details !== '' ? $details : null,
            'repeat_type' => $repeat_type,
            'updated_at'  => date('Y-m-d H:i:s'),
        ));

        if ($sync_google) {
            $this->load->helper('reminders_google');
            $result = todays_plan_sync_google($this->plan, $this->reminders, $id);
            if (!empty($result['ok'])) {
                $this->session->set_flashdata('success', 'Plan updated. Google alert scheduled.');
            } else {
                $this->session->set_flashdata('success', 'Plan updated.');
                $this->session->set_flashdata('error', isset($result['message']) ? $result['message'] : 'Google sync failed.');
            }
        } else {
            $this->session->set_flashdata('success', 'Plan updated.');
        }

        redirect('todays-plan?date=' . urlencode($plan_date));
    }

    /**
     * Parse single or multi-row plan form (plan_time[] / title[]).
     *
     * @return array<int,array{plan_time:string,title:string,details:?string,link_type:?string,link_id:?int,repeat_type:string}>
     */
    private function _parse_plan_rows()
    {
        $times = $this->input->post('plan_time');
        $titles = $this->input->post('title');
        $details_list = $this->input->post('details');
        $link_types = $this->input->post('link_type');
        $link_ids = $this->input->post('link_id');
        $repeat_types = $this->input->post('repeat_type');

        if (!is_array($times)) {
            $times = array($times);
        }
        if (!is_array($titles)) {
            $titles = array($titles);
        }
        if (!is_array($details_list)) {
            $details_list = array($details_list);
        }
        if (!is_array($link_types)) {
            $link_types = array($link_types);
        }
        if (!is_array($link_ids)) {
            $link_ids = array($link_ids);
        }
        if (!is_array($repeat_types)) {
            $repeat_types = array($repeat_types);
        }

        $allowed_types = array_keys(todays_plan_link_types());
        $allowed_repeat = array_keys(todays_plan_repeat_types());
        $rows = array();
        $count = max(count($times), count($titles));

        for ($i = 0; $i < $count; $i++) {
            $plan_time = isset($times[$i]) ? trim((string) $times[$i]) : '';
            $title = isset($titles[$i]) ? trim((string) $titles[$i]) : '';
            if ($plan_time === '' && $title === '') {
                continue;
            }
            if ($plan_time === '' || $title === '') {
                continue;
            }

            $details = isset($details_list[$i]) ? trim((string) $details_list[$i]) : '';
            $link_type = isset($link_types[$i]) ? trim((string) $link_types[$i]) : '';
            $link_id = isset($link_ids[$i]) ? (int) $link_ids[$i] : 0;

            if (!in_array($link_type, $allowed_types, true)) {
                $link_type = '';
            }
            if ($link_type === '' || $link_id <= 0) {
                $link_type = '';
                $link_id = 0;
            }
            if (strlen($plan_time) === 5) {
                $plan_time .= ':00';
            }

            $repeat_type = isset($repeat_types[$i]) ? trim((string) $repeat_types[$i]) : 'once';
            if (!in_array($repeat_type, $allowed_repeat, true)) {
                $repeat_type = 'once';
            }

            $rows[] = array(
                'plan_time'   => $plan_time,
                'title'       => $title,
                'details'     => $details !== '' ? $details : null,
                'link_type'   => $link_type !== '' ? $link_type : null,
                'link_id'     => $link_id > 0 ? $link_id : null,
                'repeat_type' => $repeat_type,
            );
        }

        return $rows;
    }

    public function toggle()
    {
        if ($this->input->method() !== 'post') {
            redirect('todays-plan');
            return;
        }

        $user_id = (int) $this->session->userdata('user_id');
        $id = (int) $this->input->post('id');
        $status = trim((string) $this->input->post('status'));
        $labels = todays_plan_status_labels();
        if (!isset($labels[$status])) {
            $status = 'pending';
        }

        if (!$this->plan->belongs_to_user($id, $user_id)) {
            show_error('Access denied', 403);
            return;
        }

        $this->plan->update($id, array(
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        $item = $this->plan->get($id);
        $date = $item && isset($item->plan_date) ? $item->plan_date : date('Y-m-d');
        $this->session->set_flashdata('success', 'Status updated.');
        redirect('todays-plan?date=' . urlencode($date));
    }

    public function delete($id)
    {
        if ($this->input->method() !== 'post') {
            redirect('todays-plan');
            return;
        }

        $user_id = (int) $this->session->userdata('user_id');
        $id = (int) $id;
        $item = $this->plan->get($id);
        if (!$item || (int) $item->user_id !== $user_id) {
            show_error('Access denied', 403);
            return;
        }

        $this->plan->delete($id, $user_id);
        $this->session->set_flashdata('success', 'Plan item removed.');
        redirect('todays-plan?date=' . urlencode($item->plan_date));
    }

    /**
     * @param int $user_id
     * @return array<string,array<int,string>>
     */
    private function _load_link_options($user_id)
    {
        $out = array(
            'task'        => array(),
            'project'     => array(),
            'requirement' => array(),
            'my_work'     => array(),
        );

        if ($this->db->table_exists('tasks')) {
            $title_col = schema_table_has_column($this->db, 'tasks', 'title') ? 'title' : 'name';
            $this->db->select('id, ' . $title_col . ' AS label', false)->from('tasks');
            if (schema_table_has_column($this->db, 'tasks', 'assigned_to')) {
                $this->db->where('assigned_to', $user_id);
            }
            if (schema_table_has_column($this->db, 'tasks', 'status')) {
                $this->db->where_not_in('status', array('closed', 'done', 'completed', 'cancelled'));
            }
            $this->db->order_by($title_col, 'ASC')->limit(200);
            foreach ($this->db->get()->result() as $r) {
                $out['task'][(int) $r->id] = trim((string) $r->label);
            }
        }

        if ($this->db->table_exists('projects')) {
            $title_col = schema_table_has_column($this->db, 'projects', 'title') ? 'title' : 'name';
            $this->db->select('id, ' . $title_col . ' AS label', false)->from('projects');
            if (schema_table_has_column($this->db, 'projects', 'status')) {
                $this->db->where_not_in('status', array('closed', 'completed', 'cancelled'));
            }
            $this->db->order_by($title_col, 'ASC')->limit(200);
            foreach ($this->db->get()->result() as $r) {
                $out['project'][(int) $r->id] = trim((string) $r->label);
            }
        }

        if ($this->db->table_exists('requirements')) {
            $title_col = schema_table_has_column($this->db, 'requirements', 'title') ? 'title' : 'name';
            $this->db->select('id, ' . $title_col . ' AS label', false)->from('requirements');
            if (schema_table_has_column($this->db, 'requirements', 'assigned_to')) {
                $this->db->where('assigned_to', $user_id);
            }
            $this->db->order_by($title_col, 'ASC')->limit(200);
            foreach ($this->db->get()->result() as $r) {
                $out['requirement'][(int) $r->id] = trim((string) $r->label);
            }
        }

        if ($this->db->table_exists('my_works')) {
            $this->db->select('id, title AS label', false)->from('my_works');
            $this->db->group_start();
            $this->db->where('created_by', $user_id);
            $this->db->or_where('created_for', $user_id);
            $this->db->group_end();
            if (schema_table_has_column($this->db, 'my_works', 'status')) {
                $this->db->where_not_in('status', array('closed'));
            }
            $this->db->order_by('title', 'ASC')->limit(200);
            foreach ($this->db->get()->result() as $r) {
                $out['my_work'][(int) $r->id] = trim((string) $r->label);
            }
        }

        return $out;
    }
}
