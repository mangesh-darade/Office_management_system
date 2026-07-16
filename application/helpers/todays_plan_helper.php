<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('todays_plan_repeat_types')) {
    function todays_plan_repeat_types()
    {
        return array(
            'once'      => 'Today',
            'recurring' => 'Every day',
        );
    }
}

if (!function_exists('todays_plan_link_types')) {
    function todays_plan_link_types()
    {
        return array(
            ''            => 'None',
            'task'        => 'Task',
            'project'     => 'Project',
            'requirement' => 'Requirement',
            'my_work'     => 'My Work',
        );
    }
}

if (!function_exists('todays_plan_status_labels')) {
    function todays_plan_status_labels()
    {
        return array(
            'pending' => 'Pending',
            'done'    => 'Done',
            'skipped' => 'Skipped',
        );
    }
}

if (!function_exists('todays_plan_link_label')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string              $link_type
     * @param int                 $link_id
     * @return string
     */
    function todays_plan_link_label($db, $link_type, $link_id)
    {
        $link_type = trim((string) $link_type);
        $link_id = (int) $link_id;
        if ($link_type === '' || $link_id <= 0) {
            return '';
        }

        $table = '';
        $title_col = 'title';
        if ($link_type === 'task' && $db->table_exists('tasks')) {
            $table = 'tasks';
            if (!schema_table_has_column($db, 'tasks', 'title')) {
                $title_col = 'name';
            }
        } elseif ($link_type === 'project' && $db->table_exists('projects')) {
            $table = 'projects';
            if (!schema_table_has_column($db, 'projects', 'title')) {
                $title_col = 'name';
            }
        } elseif ($link_type === 'requirement' && $db->table_exists('requirements')) {
            $table = 'requirements';
        } elseif ($link_type === 'my_work' && $db->table_exists('my_works')) {
            $table = 'my_works';
        }

        if ($table === '' || !schema_table_has_column($db, $table, $title_col)) {
            return ucfirst(str_replace('_', ' ', $link_type)) . ' #' . $link_id;
        }

        $row = $db->select($title_col)->from($table)->where('id', $link_id)->get()->row();
        if (!$row || !isset($row->$title_col)) {
            return ucfirst(str_replace('_', ' ', $link_type)) . ' #' . $link_id;
        }
        return trim((string) $row->$title_col);
    }
}

if (!function_exists('todays_plan_link_url')) {
    function todays_plan_link_url($link_type, $link_id)
    {
        $link_type = trim((string) $link_type);
        $link_id = (int) $link_id;
        if ($link_id <= 0) {
            return '';
        }
        if ($link_type === 'task') {
            return site_url('tasks/' . $link_id);
        }
        if ($link_type === 'project') {
            return site_url('projects/' . $link_id);
        }
        if ($link_type === 'requirement') {
            return site_url('requirements/view/' . $link_id);
        }
        if ($link_type === 'my_work') {
            return site_url('my-works/' . $link_id);
        }
        return '';
    }
}

if (!function_exists('todays_plan_sync_google')) {
    /**
     * @param Todays_plan_model $plan_model
     * @param Reminder_model    $reminder_model
     * @param int               $item_id
     * @return array{ok:bool,message:string}
     */
    function todays_plan_sync_google($plan_model, $reminder_model, $item_id)
    {
        $item = $plan_model->get((int) $item_id);
        if (!$item) {
            return array('ok' => false, 'message' => 'Plan item not found.');
        }

        $CI =& get_instance();
        $CI->load->helper('reminders_user');

        $contact = reminders_fetch_user_contact($CI->db, (int) $item->user_id);
        if ($contact['email'] === '') {
            $plan_model->update((int) $item->id, array(
                'google_sync_status' => 'error',
                'updated_at'         => date('Y-m-d H:i:s'),
            ));
            return array('ok' => false, 'message' => 'User email missing.');
        }

        $send_at = $item->plan_date . ' ' . substr((string) $item->plan_time, 0, 8);
        $ts = strtotime($send_at);
        if ($ts !== false && $ts < time() - 60) {
            $send_at = date('Y-m-d H:i:s', time() + 120);
        }

        $link_label = todays_plan_link_label($CI->db, $item->link_type, (int) $item->link_id);
        $body = isset($item->details) ? trim((string) $item->details) : '';
        if ($link_label !== '') {
            $body = ($body !== '' ? $body . "\n\n" : '') . 'Linked: ' . $link_label;
            $url = todays_plan_link_url($item->link_type, (int) $item->link_id);
            if ($url !== '') {
                $body .= "\n" . $url;
            }
        }

        $rid = $reminder_model->enqueue(array(
            'user_id' => (int) $item->user_id,
            'email'   => $contact['email'],
            'type'    => 'todays_plan',
            'subject' => trim((string) $item->title),
            'body'    => $body,
            'send_at' => $send_at,
        ));

        $sync_status = 'error';
        if ($rid > 0) {
            $row = $reminder_model->get($rid);
            $sync_status = ($row && isset($row->status) && $row->status === 'sent') ? 'sent' : 'queued';
        }

        $plan_model->update((int) $item->id, array(
            'reminder_id'        => $rid > 0 ? $rid : null,
            'google_sync_status' => $sync_status,
            'updated_at'         => date('Y-m-d H:i:s'),
        ));

        if ($sync_status === 'error') {
            return array('ok' => false, 'message' => 'Google Calendar sync failed.');
        }
        return array('ok' => true, 'message' => 'Alert scheduled via Google Calendar.');
    }
}
