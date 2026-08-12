<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Leave / WFH email notifications (apply + approve/reject).
 */

if (!function_exists('leave_requests_ensure_email_thread_column')) {
    function leave_requests_ensure_email_thread_column()
    {
        $CI =& get_instance();
        if (!$CI->db->table_exists('leave_requests')) {
            return;
        }
        if (!function_exists('schema_table_has_column')) {
            $CI->load->helper('schema_columns');
        }
        if (!schema_table_has_column($CI->db, 'leave_requests', 'apply_email_message_id')) {
            $CI->db->query("ALTER TABLE `leave_requests` ADD COLUMN `apply_email_message_id` VARCHAR(255) NULL DEFAULT NULL AFTER `manager_id`");
        }
    }
}

if (!function_exists('leave_requests_build_message_id')) {
    function leave_requests_build_message_id($leave_id)
    {
        $host = '';
        if (function_exists('get_system_from_email')) {
            $from = trim((string) get_system_from_email());
            if ($from !== '' && strpos($from, '@') !== false) {
                $host = strtolower(substr($from, strpos($from, '@') + 1));
            }
        }
        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1') {
            $parsed = parse_url(site_url(), PHP_URL_HOST);
            $host = is_string($parsed) ? strtolower($parsed) : '';
        }
        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1') {
            $host = 'oms.local';
        }
        return '<leave-' . (int) $leave_id . '-' . time() . '-' . substr(md5(uniqid((string) $leave_id, true)), 0, 8) . '@' . $host . '>';
    }
}

if (!function_exists('leave_requests_get_hr_email')) {
    function leave_requests_get_hr_email()
    {
        $CI =& get_instance();
        $CI->load->model('Setting_model', 'settings');
        $hr_user_id = $CI->settings->get_setting('leave_hr_user_id');
        $hr_user_id = !empty($hr_user_id) ? (int) $hr_user_id : 0;
        if ($hr_user_id <= 0) {
            return null;
        }
        $hr = $CI->db->select('email, name')->from('users')->where('id', $hr_user_id)->get()->row();
        if ($hr && !empty($hr->email)) {
            return array(
                'email' => $hr->email,
                'name' => !empty($hr->name) ? $hr->name : $hr->email,
            );
        }
        return null;
    }
}

if (!function_exists('leave_requests_user_mail')) {
    function leave_requests_user_mail($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return null;
        }
        $CI =& get_instance();
        $row = $CI->db->select('email, name')->from('users')->where('id', $user_id)->get()->row();
        if (!$row || empty($row->email)) {
            return null;
        }
        return array(
            'email' => $row->email,
            'name' => !empty($row->name) ? $row->name : $row->email,
        );
    }
}

if (!function_exists('leave_requests_log_mail_failure')) {
    function leave_requests_log_mail_failure($context)
    {
        $CI =& get_instance();
        $debug = '';
        if (isset($CI->email) && is_object($CI->email) && method_exists($CI->email, 'print_debugger')) {
            $debug = trim(strip_tags($CI->email->print_debugger(array('headers', 'subject'))));
        }
        log_message('error', 'Leave email failed (' . $context . ')' . ($debug !== '' ? (': ' . $debug) : ''));
    }
}

if (!function_exists('leave_requests_apply_subject')) {
    /**
     * Stable subject for leave + WFH apply, and Re: approve/reject threading.
     * Always "New Request" so Leave and WFH share one subject style.
     */
    function leave_requests_apply_subject($employee_label, $request_count = 1)
    {
        $subject = 'New Request - ' . $employee_label;
        $request_count = (int) $request_count;
        if ($request_count > 1) {
            $subject .= ' (' . $request_count . ' requests)';
        }
        return $subject;
    }
}

if (!function_exists('leave_requests_get_last_sent_message_id')) {
    /**
     * CI Email overwrites custom Message-ID in _build_headers().
     * Read the Message-ID that was actually sent.
     */
    function leave_requests_get_last_sent_message_id()
    {
        $CI =& get_instance();
        if (!isset($CI->email) || !is_object($CI->email)) {
            return '';
        }
        try {
            $ref = new ReflectionProperty($CI->email, '_headers');
            $ref->setAccessible(true);
            $headers = $ref->getValue($CI->email);
            if (is_array($headers) && !empty($headers['Message-ID'])) {
                return trim((string) $headers['Message-ID']);
            }
        } catch (Exception $e) {
            return '';
        } catch (Throwable $e) {
            return '';
        }
        return '';
    }
}

if (!function_exists('leave_requests_admin_group_users')) {
    /**
     * Users whose role has group_type = admin (Admin / Manager / Lead roles).
     *
     * @param object $db
     * @return array
     */
    function leave_requests_admin_group_users($db)
    {
        if (!$db || !$db->table_exists('users')) {
            return array();
        }
        $db->reset_query();
        $db->select('u.id, u.name, u.email');
        $db->from('users u');
        if ($db->table_exists('roles') && schema_table_has_column($db, 'roles', 'group_type')) {
            $db->join('roles r', 'r.id = u.role_id', 'inner');
            $db->where('r.group_type', 'admin');
        } else {
            $db->where_in('u.role_id', array(1, 2, 3));
        }
        if (schema_table_has_column($db, 'users', 'status')) {
            $db->where('u.status', 'active');
        }
        $db->order_by('u.name', 'ASC');
        return $db->get()->result();
    }
}

if (!function_exists('leave_requests_normalize_lead_ids')) {
    /**
     * @param mixed $raw
     * @return int[]
     */
    function leave_requests_normalize_lead_ids($raw)
    {
        if (!is_array($raw)) {
            if ($raw === null || $raw === '') {
                return array();
            }
            $raw = array($raw);
        }
        $ids = array();
        foreach ($raw as $v) {
            $id = (int) $v;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }
}

if (!function_exists('leave_requests_parse_cc_user_ids')) {
    /**
     * @param object|null $row leave_requests row
     * @return int[]
     */
    function leave_requests_parse_cc_user_ids($row)
    {
        $ids = array();
        if ($row && !empty($row->notify_cc_user_ids)) {
            $decoded = json_decode((string) $row->notify_cc_user_ids, true);
            if (is_array($decoded)) {
                foreach ($decoded as $v) {
                    $id = (int) $v;
                    if ($id > 0) {
                        $ids[$id] = $id;
                    }
                }
            }
        }
        if ($row && !empty($row->current_approver_id)) {
            $id = (int) $row->current_approver_id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        // Legacy single manager_id (pre multi-lead)
        if ($row && !empty($row->manager_id)) {
            $id = (int) $row->manager_id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }
}

if (!function_exists('leave_requests_notify_applied')) {
    /**
     * @param array $leave_ids
     * @param int $user_id
     * @param int $type_id
     * @param int|int[] $selected_lead_ids Multi-select Lead (admin-group) user ids → email CC
     * @param int|null $selected_admin_id Deprecated; ignored (Manager field removed)
     */
    function leave_requests_notify_applied($leave_ids, $user_id, $type_id, $selected_lead_ids, $selected_admin_id = null)
    {
        $CI =& get_instance();
        if (empty($leave_ids)) {
            return;
        }

        $lead_ids = leave_requests_normalize_lead_ids($selected_lead_ids);
        leave_requests_ensure_email_thread_column();

        try {
            $CI->db->select('lr.*, lt.name AS type_name, u.name AS user_name, u.email AS user_email')
                     ->from('leave_requests lr')
                     ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                     ->join('users u', 'u.id = lr.user_id', 'left')
                     ->where_in('lr.id', $leave_ids)
                     ->order_by('lr.start_date', 'ASC');
            $leaves = $CI->db->get()->result();

            if (empty($leaves)) {
                return;
            }

            $first_leave = $leaves[0];
            $employee_name = !empty($first_leave->user_name) ? $first_leave->user_name : $first_leave->user_email;
            $leave_type = $first_leave->type_name;

            $is_wfh = leave_request_row_is_wfh($first_leave);

            $leave_details = array();
            $total_days = 0;
            foreach ($leaves as $l) {
                if ($l->start_date === $l->end_date) {
                    $date_str = date('M d, Y', strtotime($l->start_date));
                } else {
                    $date_str = date('M d', strtotime($l->start_date)) . ' - ' . date('M d, Y', strtotime($l->end_date));
                }

                $leave_details[] = array(
                    'date' => $date_str,
                    'days' => (float) $l->days,
                    'reason' => !empty($l->reason) ? $l->reason : 'No reason provided'
                );
                $total_days += (float) $l->days;
            }

            $request_count = count($leaves);

            $hr = leave_requests_get_hr_email();
            $hr_email = $hr ? $hr['email'] : null;

            $CI->load->helper('email');
            configure_email_from_settings();

            $smtp_from = get_system_from_email();
            if ($smtp_from === '') {
                log_message('error', 'Leave apply email skipped: SMTP from address not configured');
                return;
            }

            // From = logged-in applicant (Reply-To). SMTP still authenticates as system account.
            $applicant_email = !empty($first_leave->user_email) ? trim((string) $first_leave->user_email) : '';
            if ($applicant_email === '' || !filter_var($applicant_email, FILTER_VALIDATE_EMAIL)) {
                log_message('error', 'Leave apply email skipped: applicant email missing');
                return;
            }

            $request_type = $is_wfh ? 'WFH Request' : 'Leave Request';
            $subject = leave_requests_apply_subject($employee_name, $request_count);

            $message = '<html><body>';
            $message .= '<h3>New ' . esc_view($request_type) . '</h3>';
            $message .= '<p><strong>Employee:</strong> ' . esc_view($employee_name) . ' (' . esc_view($first_leave->user_email) . ')</p>';
            $message .= '<p><strong>' . ($is_wfh ? 'WFH Type' : 'Leave Type') . ':</strong> ' . esc_view($leave_type) . '</p>';
            $message .= '<p><strong>Total Requests:</strong> ' . $request_count . '</p>';
            $message .= '<p><strong>Total Days:</strong> ' . number_format($total_days, 1) . '</p>';

            $message .= '<h4>' . ($is_wfh ? 'WFH' : 'Leave') . ' Details:</h4>';
            $message .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
            $message .= '<tr style="background-color: #f2f2f2;"><th>Date(s)</th><th>Days</th><th>Reason</th></tr>';

            foreach ($leave_details as $detail) {
                $message .= '<tr>';
                $message .= '<td>' . esc_view($detail['date']) . '</td>';
                $message .= '<td>' . number_format($detail['days'], 1) . '</td>';
                $message .= '<td>' . esc_view($detail['reason']) . '</td>';
                $message .= '</tr>';
            }
            $message .= '</table>';

            $message .= '<p><strong>Status:</strong> Pending Approval</p>';
            $message .= '<p>Please review and approve/reject this ' . strtolower($request_type) . ' from the <a href="' . site_url('leave/team') . '">Team Leaves</a> page.</p>';
            $message .= '<p>Thank you.</p>';
            $message .= '</body></html>';

            // To = HR Manager (Settings → Leave); CC = all selected Leads (admin-group multi-select)
            if (!$hr_email) {
                log_message('error', 'Leave apply email skipped: HR Manager not set in Settings → Leave');
                return;
            }

            $primary_to = $hr_email;
            $cc_list = array();
            foreach ($lead_ids as $lid) {
                $lead = leave_requests_user_mail($lid);
                if (!$lead || empty($lead['email'])) {
                    continue;
                }
                $em = trim((string) $lead['email']);
                if ($em === '' || $em === $primary_to || in_array($em, $cc_list, true)) {
                    continue;
                }
                $cc_list[] = $em;
            }

            $thread_leave_id = (int) $first_leave->id;
            $message_id = leave_requests_build_message_id($thread_leave_id);

            $CI->email->clear(true);
            // Authenticate as SMTP account; show applicant as sender via From name + Reply-To
            $CI->email->from($smtp_from, $employee_name);
            $CI->email->reply_to($applicant_email, $employee_name);
            $CI->email->to($primary_to);
            if (!empty($cc_list)) {
                $CI->email->cc($cc_list);
            }
            $CI->email->set_header('Message-ID', $message_id);
            $CI->email->subject($subject);
            $CI->email->message($message);
            $sent = $CI->email->send();

            // Prefer ID we set; fall back to whatever CI actually kept after send
            $sent_id = leave_requests_get_last_sent_message_id();
            if ($sent_id !== '') {
                $message_id = $sent_id;
            }

            if ($sent && schema_table_has_column($CI->db, 'leave_requests', 'apply_email_message_id')) {
                $CI->db->where_in('id', array_map('intval', $leave_ids))
                       ->update('leave_requests', array('apply_email_message_id' => $message_id));
            } elseif (!$sent) {
                leave_requests_log_mail_failure('apply leave_ids=' . implode(',', array_map('intval', $leave_ids)));
            }

        } catch (Exception $e) {
            log_message('error', 'Leave notification email failed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('leave_requests_notify_change')) {
    /**
     * Reply on the same apply-email thread when lead / manager / HR approve or reject.
     *
     * @param int $leave_id
     * @param string $status approved|rejected|step_approved
     * @param string $comments
     * @param int $actor_user_id Approver who took action (for Reply-To)
     */
    function leave_requests_notify_change($leave_id, $status, $comments, $actor_user_id = 0)
    {
        $CI =& get_instance();
        leave_requests_ensure_email_thread_column();

        $row = $CI->db->select('lr.*, lt.name AS type_name, u.name AS user_name, u.email AS user_email')
                        ->from('leave_requests lr')
                        ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                        ->join('users u', 'u.id = lr.user_id', 'left')
                        ->where('lr.id', (int) $leave_id)->get()->row();
        if (!$row || empty($row->user_email)) {
            return;
        }

        $hr = leave_requests_get_hr_email();
        $actor = leave_requests_user_mail($actor_user_id);

        $cc_user_ids = leave_requests_parse_cc_user_ids($row);
        $hr_email = $hr ? $hr['email'] : null;
        $employee_email = trim((string) $row->user_email);

        $is_wfh = leave_request_row_is_wfh($row);

        try {
            $CI->load->helper('email');
            configure_email_from_settings();

            $fromAddr = get_system_from_email();
            if ($fromAddr === '') {
                log_message('error', 'Leave status email skipped: SMTP from address not configured');
                return;
            }

            $CI->email->clear(true);

            $actor_name = $actor ? $actor['name'] : get_company_name();
            $CI->email->from($fromAddr, $actor_name);
            if ($actor && !empty($actor['email'])) {
                $CI->email->reply_to($actor['email'], $actor_name);
            }

            if ($row->start_date === $row->end_date) {
                $date_string = date('M d, Y', strtotime($row->start_date));
            } else {
                $date_string = date('M d', strtotime($row->start_date)) . ' - ' . date('M d, Y', strtotime($row->end_date));
            }

            $is_step = ($status === 'step_approved');
            $is_reject = ($status === 'rejected');
            if ($is_reject) {
                $status_text = 'Rejected';
                $status_color = '#dc3545';
            } elseif ($is_step) {
                $status_text = 'Approved (pending next level)';
                $status_color = '#0d6efd';
            } else {
                $status_text = 'Approved';
                $status_color = '#28a745';
            }
            $request_type = $is_wfh ? 'WFH Request' : 'Leave Request';
            $employee_label = !empty($row->user_name) ? $row->user_name : $row->user_email;

            // Same Gmail/Outlook thread as apply mail
            $thread_id = !empty($row->apply_email_message_id) ? trim((string) $row->apply_email_message_id) : '';
            if ($thread_id !== '') {
                $subject = 'Re: ' . leave_requests_apply_subject($employee_label, 1);
                $CI->email->set_header('In-Reply-To', $thread_id);
                $CI->email->set_header('References', $thread_id);
            } else {
                $subject = 'Re: ' . leave_requests_apply_subject($employee_label, 1);
                log_message('error', 'Leave status email has no apply_email_message_id for leave_id=' . (int) $leave_id . ' (cannot thread reply)');
            }

            $message = '<html><body>';
            $message .= '<h3 style="color: ' . $status_color . ';">' . esc_view($request_type) . ' ' . esc_view($status_text) . '</h3>';
            $message .= '<p>Dear ' . esc_view($employee_label) . ',</p>';
            $message .= '<p>Your ' . strtolower($request_type) . ' has been <strong style="color: ' . $status_color . ';">' . esc_view($status_text) . '</strong>';
            if ($actor) {
                $message .= ' by ' . esc_view($actor['name']);
            }
            $message .= '.</p>';
            $message .= '<p><strong>' . ($is_wfh ? 'WFH' : 'Leave') . ' Details:</strong></p>';
            $message .= '<ul>';
            $message .= '<li><strong>' . ($is_wfh ? 'WFH Type' : 'Leave Type') . ':</strong> ' . esc_view($row->type_name) . '</li>';
            $message .= '<li><strong>Date(s):</strong> ' . esc_view($date_string) . '</li>';
            $message .= '<li><strong>Days:</strong> ' . number_format((float) $row->days, 1) . '</li>';
            $message .= '<li><strong>Status:</strong> ' . esc_view($status_text) . '</li>';
            $message .= '</ul>';
            $comments = trim((string) $comments);
            if ($comments !== '') {
                $message .= '<p><strong>Comments:</strong></p>';
                $message .= '<p>' . nl2br(esc_view($comments)) . '</p>';
            }
            $message .= '<p>You can view your ' . strtolower($request_type) . 's from the <a href="' . site_url('leave/my') . '">My Leaves</a> page.</p>';
            $message .= '<p>Thank you.</p>';
            $message .= '</body></html>';

            // Same conversation people as apply: To = HR, CC = selected Leads + Employee
            $primary_to = null;
            $cc_list = array();

            $first_lead_email = null;
            foreach ($cc_user_ids as $cid) {
                $person = leave_requests_user_mail($cid);
                if (!$person || empty($person['email'])) {
                    continue;
                }
                if ($first_lead_email === null) {
                    $first_lead_email = trim((string) $person['email']);
                }
            }

            if ($hr_email) {
                $primary_to = $hr_email;
            } elseif ($first_lead_email) {
                $primary_to = $first_lead_email;
            } else {
                $primary_to = $employee_email;
            }

            foreach ($cc_user_ids as $cid) {
                $person = leave_requests_user_mail($cid);
                if (!$person || empty($person['email'])) {
                    continue;
                }
                $em = trim((string) $person['email']);
                if ($em === '' || $em === $primary_to || in_array($em, $cc_list, true)) {
                    continue;
                }
                $cc_list[] = $em;
            }
            if ($employee_email !== '' && $employee_email !== $primary_to && !in_array($employee_email, $cc_list, true)) {
                $cc_list[] = $employee_email;
            }
            if ($actor && !empty($actor['email'])
                && $actor['email'] !== $primary_to
                && !in_array($actor['email'], $cc_list, true)) {
                $cc_list[] = $actor['email'];
            }

            $CI->email->to($primary_to);
            if (!empty($cc_list)) {
                $CI->email->cc($cc_list);
            }
            $CI->email->subject($subject);
            $CI->email->message($message);

            $sent = $CI->email->send();
            if (!$sent) {
                leave_requests_log_mail_failure('status leave_id=' . (int) $leave_id . ' to=' . $primary_to);
            }
        } catch (Exception $e) {
            log_message('error', 'Leave approval email notification failed: ' . $e->getMessage());
        }
    }
}
