<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Leave / WFH email notifications (apply + approve/reject).
 */

if (!function_exists('leave_requests_notify_applied')) {
    function leave_requests_notify_applied($leave_ids, $user_id, $type_id, $selected_lead_id, $selected_admin_id)
    {
        $CI =& get_instance();
        if (empty($leave_ids)) return;
        
        // Debug: Log email consolidation
        error_log('Leave notification: Processing ' . count($leave_ids) . ' leave requests in single email');
        
        try {
            // Get leave request details
            $CI->db->select('lr.*, lt.name AS type_name, u.name AS user_name, u.email AS user_email')
                     ->from('leave_requests lr')
                     ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                     ->join('users u', 'u.id = lr.user_id', 'left')
                     ->where_in('lr.id', $leave_ids)
                     ->order_by('lr.start_date', 'ASC');
            $leaves = $CI->db->get()->result();
            
            if (empty($leaves)) return;
            
            $first_leave = $leaves[0];
            $employee_name = !empty($first_leave->user_name) ? $first_leave->user_name : $first_leave->user_email;
            $leave_type = $first_leave->type_name;
            
            // Check if this is a WFH request
            $is_wfh = false;
            if (isset($first_leave->reason) && strpos($first_leave->reason, 'WFH:') === 0) {
                $is_wfh = true;
            } elseif (isset($leave_type) && strtolower(trim($leave_type)) === 'work from home') {
                $is_wfh = true;
            }
            
            // Build consolidated leave details
            $leave_details = [];
            $total_days = 0;
            foreach ($leaves as $l) {
                $date_str = '';
                if ($l->start_date === $l->end_date) {
                    $date_str = date('M d, Y', strtotime($l->start_date));
                } else {
                    $date_str = date('M d', strtotime($l->start_date)) . ' - ' . date('M d, Y', strtotime($l->end_date));
                }
                
                $leave_details[] = [
                    'date' => $date_str,
                    'days' => (float)$l->days,
                    'reason' => !empty($l->reason) ? $l->reason : 'No reason provided'
                ];
                $total_days += (float)$l->days;
            }
            
            $reason = !empty($first_leave->reason) ? $first_leave->reason : 'No reason provided';
            $request_count = count($leaves);
            
            // Get selected Lead email
            $lead_email = null;
            $lead_name = null;
            if ($selected_lead_id) {
                $lead = $CI->db->select('email, name')->from('users')->where('id', $selected_lead_id)->get()->row();
                if ($lead && !empty($lead->email)) {
                    $lead_email = $lead->email;
                    $lead_name = !empty($lead->name) ? $lead->name : $lead->email;
                }
            }
            
            // Get selected Admin email
            $admin_email = null;
            $admin_name = null;
            if ($selected_admin_id) {
                $admin = $CI->db->select('email, name')->from('users')->where('id', $selected_admin_id)->get()->row();
                if ($admin && !empty($admin->email)) {
                    $admin_email = $admin->email;
                    $admin_name = !empty($admin->name) ? $admin->name : $admin->email;
                }
            }
            
            // Load email helper and configure from settings
            $CI->load->helper('email');
            configure_email_from_settings();
            
            $fromAddr = get_system_from_email();
            $fromName = get_company_name();
            
            // Build consolidated email content
            $request_type = $is_wfh ? 'WFH Request' : 'Leave Request';
            $subject = 'New ' . $request_type . ' - ' . $employee_name;
            if ($request_count > 1) {
                $subject .= ' (' . $request_count . ' requests)';
            }
            
            $message = '<html><body>';
            $message .= '<h3>New ' . htmlspecialchars($request_type) . '</h3>';
            $message .= '<p><strong>Employee:</strong> ' . htmlspecialchars($employee_name) . ' (' . htmlspecialchars($first_leave->user_email) . ')</p>';
            $message .= '<p><strong>' . ($is_wfh ? 'WFH Type' : 'Leave Type') . ':</strong> ' . htmlspecialchars($leave_type) . '</p>';
            $message .= '<p><strong>Total Requests:</strong> ' . $request_count . '</p>';
            $message .= '<p><strong>Total Days:</strong> ' . number_format($total_days, 1) . '</p>';
            
            // Add detailed leave/WFH breakdown
            $message .= '<h4>' . ($is_wfh ? 'WFH' : 'Leave') . ' Details:</h4>';
            $message .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
            $message .= '<tr style="background-color: #f2f2f2;"><th>Date(s)</th><th>Days</th><th>Reason</th></tr>';
            
            foreach ($leave_details as $detail) {
                $message .= '<tr>';
                $message .= '<td>' . htmlspecialchars($detail['date']) . '</td>';
                $message .= '<td>' . number_format($detail['days'], 1) . '</td>';
                $message .= '<td>' . htmlspecialchars($detail['reason']) . '</td>';
                $message .= '</tr>';
            }
            $message .= '</table>';
            
            $message .= '<p><strong>Status:</strong> Pending Approval</p>';
            $message .= '<p>Please review and approve/reject this ' . strtolower($request_type) . ' from the <a href="' . site_url('leave/team') . '">Team Leaves</a> page.</p>';
            $message .= '<p>Thank you.</p>';
            $message .= '</body></html>';
            
            // Send a SINGLE email:
            // - Lead is always in "To" (if configured)
            // - Manager/Admin is always in "CC" (if configured and different from lead)
            $primary_to = null;
            $cc_list = [];
            
            if ($lead_email) {
                $primary_to = $lead_email;
                if ($admin_email && $admin_email !== $lead_email) {
                    $cc_list[] = $admin_email;
                }
            } elseif ($admin_email) {
                // Fallback: no lead, send directly to admin
                $primary_to = $admin_email;
            }
            
            if ($primary_to) {
                error_log('Leave notification: Sending consolidated email. To: ' . $primary_to . ', CC: ' . implode(',', $cc_list));
                $CI->email->clear(true);
                $CI->email->from($fromAddr, $fromName);
                $CI->email->to($primary_to);
                if (!empty($cc_list)) {
                    $CI->email->cc($cc_list);
                }
                $CI->email->subject($subject);
                $CI->email->message($message);
                @$CI->email->send();
            }
            
        } catch (Exception $e) {
            // Log error but don't break the application
            error_log('Leave notification email failed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('leave_requests_notify_change')) {
    function leave_requests_notify_change($leave_id, $status, $comments)
    {
        $CI =& get_instance();
        // Load settings model to get HR user ID
        $CI->load->model('Setting_model', 'settings');
        
        // Fetch requester email and leave details
        $row = $CI->db->select('lr.*, lt.name AS type_name, u.name AS user_name, u.email AS user_email')
                        ->from('leave_requests lr')
                        ->join('leave_types lt', 'lt.id = lr.type_id', 'left')
                        ->join('users u','u.id = lr.user_id','left')
                        ->where('lr.id', (int)$leave_id)->get()->row();
        if (!$row || empty($row->user_email)) return;
        
        // Get Lead and Manager from leave request (they were selected during application)
        $lead_id = null;
        $manager_id = null;
        
        // Get Lead from current_approver_id (Lead is stored as approver)
        if (!empty($row->current_approver_id)) {
            $lead_id = (int)$row->current_approver_id;
        }
        
        // Get Manager from manager_id field (stored during application)
        if (!empty($row->manager_id)) {
            $manager_id = (int)$row->manager_id;
        }
        
        // Get HR user ID from settings
        $hr_user_id = $CI->settings->get_setting('leave_hr_user_id');
        $hr_user_id = !empty($hr_user_id) ? (int)$hr_user_id : null;
        
        // Get email addresses for Lead, Manager, and HR
        $lead_email = null;
        $lead_name = null;
        if ($lead_id) {
            $lead = $CI->db->select('email, name')->from('users')->where('id', $lead_id)->get()->row();
            if ($lead && !empty($lead->email)) {
                $lead_email = $lead->email;
                $lead_name = !empty($lead->name) ? $lead->name : $lead->email;
            }
        }
        
        $manager_email = null;
        $manager_name = null;
        if ($manager_id) {
            $manager = $CI->db->select('email, name')->from('users')->where('id', $manager_id)->get()->row();
            if ($manager && !empty($manager->email)) {
                $manager_email = $manager->email;
                $manager_name = !empty($manager->name) ? $manager->name : $manager->email;
            }
        }
        
        $hr_email = null;
        $hr_name = null;
        if ($hr_user_id) {
            $hr = $CI->db->select('email, name')->from('users')->where('id', $hr_user_id)->get()->row();
            if ($hr && !empty($hr->email)) {
                $hr_email = $hr->email;
                $hr_name = !empty($hr->name) ? $hr->name : $hr->email;
            }
        }
        
        // Check if this is a WFH request
        $is_wfh = false;
        if (isset($row->reason) && strpos($row->reason, 'WFH:') === 0) {
            $is_wfh = true;
        } elseif (isset($row->type_name) && strtolower(trim($row->type_name)) === 'work from home') {
            $is_wfh = true;
        }
        
        // Best-effort email
        try {
            // Load email helper and configure from settings
            $CI->load->helper('email');
            configure_email_from_settings();
            
            $fromAddr = get_system_from_email();
            $fromName = get_company_name();
            
            $CI->email->clear(true);
            $CI->email->from($fromAddr, $fromName);
            $CI->email->to($row->user_email);
            
            // Build date string
            if ($row->start_date === $row->end_date) {
                $date_string = date('M d, Y', strtotime($row->start_date));
            } else {
                $date_string = date('M d', strtotime($row->start_date)) . ' - ' . date('M d, Y', strtotime($row->end_date));
            }
            
            $status_text = ($status === 'rejected') ? 'Rejected' : 'Approved';
            $status_color = ($status === 'rejected') ? '#dc3545' : '#28a745';
            $request_type = $is_wfh ? 'WFH Request' : 'Leave Request';
            
            $subject = $request_type . ' ' . $status_text . ' - ' . $date_string;
            $message = '<html><body>';
            $message .= '<h3 style="color: ' . $status_color . ';">' . htmlspecialchars($request_type) . ' ' . $status_text . '</h3>';
            $message .= '<p>Dear ' . htmlspecialchars(!empty($row->user_name) ? $row->user_name : $row->user_email) . ',</p>';
            $message .= '<p>Your ' . strtolower($request_type) . ' has been <strong style="color: ' . $status_color . ';">' . $status_text . '</strong>.</p>';
            $message .= '<p><strong>' . ($is_wfh ? 'WFH' : 'Leave') . ' Details:</strong></p>';
            $message .= '<ul>';
            $message .= '<li><strong>' . ($is_wfh ? 'WFH Type' : 'Leave Type') . ':</strong> ' . htmlspecialchars($row->type_name) . '</li>';
            $message .= '<li><strong>Date(s):</strong> ' . htmlspecialchars($date_string) . '</li>';
            $message .= '<li><strong>Days:</strong> ' . number_format((float)$row->days, 1) . '</li>';
            $message .= '<li><strong>Status:</strong> ' . htmlspecialchars($status_text) . '</li>';
            $message .= '</ul>';
            if ($comments) {
                $message .= '<p><strong>Comments:</strong></p>';
                $message .= '<p>' . nl2br(htmlspecialchars($comments)) . '</p>';
            }
            $message .= '<p>You can view your ' . strtolower($request_type) . 's from the <a href="' . site_url('leave/my') . '">My Leaves</a> page.</p>';
            $message .= '<p>Thank you.</p>';
            $message .= '</body></html>';
            
            // Collect all unique recipients to avoid duplicate emails
            // Employee is always the primary recipient
            $all_recipients = [];
            $all_recipients[$row->user_email] = ['email' => $row->user_email, 'name' => !empty($row->user_name) ? $row->user_name : $row->user_email, 'type' => 'employee'];
            
            // Add Lead, Manager, HR (avoid duplicates)
            if ($lead_email && !isset($all_recipients[$lead_email])) {
                $all_recipients[$lead_email] = ['email' => $lead_email, 'name' => $lead_name, 'type' => 'lead'];
            }
            if ($manager_email && !isset($all_recipients[$manager_email])) {
                $all_recipients[$manager_email] = ['email' => $manager_email, 'name' => $manager_name, 'type' => 'manager'];
            }
            if ($hr_email && !isset($all_recipients[$hr_email])) {
                $all_recipients[$hr_email] = ['email' => $hr_email, 'name' => $hr_name, 'type' => 'hr'];
            }
            
            // Send ONE email to employee (primary recipient)
            // Add Lead, Manager, HR in CC if they are different from employee
            $cc_list = [];
            foreach ($all_recipients as $email => $recipient) {
                if ($email !== $row->user_email) {
                    $cc_list[] = $email;
                }
            }
            
            $CI->email->subject($subject);
            $CI->email->message($message);
            if (!empty($cc_list)) {
                $CI->email->cc($cc_list);
            }
            
            // Log before sending
            log_message('info', 'Sending leave status email. To: ' . $row->user_email . ', CC: ' . implode(',', $cc_list));
            
            $sent = $CI->email->send();
            if ($sent) {
                log_message('info', 'Leave status email sent successfully to ' . $row->user_email);
            } else {
                log_message('error', 'Failed to send leave status email to ' . $row->user_email);
                log_message('error', 'Email Debug: ' . $CI->email->print_debugger());
            }
        } catch (Exception $e) {
            // Silently fail - don't break the approval process
            log_message('error', 'Leave approval email notification failed: ' . $e->getMessage());
        }
    }
}
