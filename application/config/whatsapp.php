<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WhatsApp text templates used by task/report notifications (plain body).
 * Meta Cloud API credentials live in Settings → API Integrations.
 */
$config['task_assignment_template'] = "📋 *New Task Assigned*\n\n*Task:* {task_title}\n*Project:* {project_name}\n*Priority:* {priority}\n*Due Date:* {due_date}\n*Description:* {description}\n\nView details: {task_url}";
$config['task_update_template'] = "🔄 *Task Updated*\n\n*Task:* {task_title}\n*Status:* {status}\n*Updated By:* {updated_by}\n\nView details: {task_url}";
$config['report_template'] = "📊 *{report_type} Report*\n\n*Employee:* {employee_name}\n*Period:* {period}\n*Summary:*\n{summary}\n\nView full report: {report_url}";
