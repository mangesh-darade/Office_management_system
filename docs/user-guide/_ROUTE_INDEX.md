# Route index (auto-generated)

## 404_override
| URL | Controller action |
|-----|-------------------|
| `/404_override` | `errors/page_missing` |
| `/404_override` | `errors/page_missing` |

## activity
| URL | Controller action |
|-----|-------------------|
| `/activity` | `activity/index` |
| `/activity/export` | `activity/export` |

## ai
| URL | Controller action |
|-----|-------------------|
| `/ai-chat` | `ai_chat/index` |
| `/ai-chat/clear-history` | `ai_chat/clear_history` |
| `/ai-chat/export` | `ai_chat/export` |
| `/ai-chat/send` | `ai_chat/send_message` |
| `/ai-chat/tts` | `ai_chat/tts` |

## ai_chat
| URL | Controller action |
|-----|-------------------|
| `/ai_chat` | `ai_chat/index` |
| `/ai_chat/send_message` | `ai_chat/send_message` |

## analytics
| URL | Controller action |
|-----|-------------------|
| `/analytics` | `analytics/index` |
| `/analytics/analyze-feedback` | `analytics/analyze_feedback` |
| `/analytics/calendar-feed/(:any)` | `analytics/calendar_feed/$1` |
| `/analytics/parse-resume` | `analytics/parse_resume` |
| `/analytics/save-integrations` | `analytics/save_integrations` |
| `/analytics/start-quick-call` | `analytics/start_quick_call` |

## announcements
| URL | Controller action |
|-----|-------------------|
| `/announcements` | `announcements/index` |
| `/announcements/(:num)/delete` | `announcements/delete/$1` |
| `/announcements/(:num)/edit` | `announcements/edit/$1` |
| `/announcements/create` | `announcements/create` |
| `/announcements/save-template` | `announcements/save_template` |
| `/announcements/templates` | `announcements/templates` |

## api
| URL | Controller action |
|-----|-------------------|
| `/api-integrations` | `api_integrations/index` |
| `/api-integrations/create` | `api_integrations/create` |
| `/api-integrations/delete/(:num)` | `api_integrations/delete/$1` |
| `/api-integrations/edit/(:num)` | `api_integrations/edit/$1` |
| `/api-integrations/store` | `api_integrations/store` |
| `/api-integrations/update/(:num)` | `api_integrations/update/$1` |

## approvals
| URL | Controller action |
|-----|-------------------|
| `/approvals` | `approvals/index` |
| `/approvals/create` | `approvals/create` |
| `/approvals/delete/(:num)` | `approvals/delete/$1` |
| `/approvals/edit/(:num)` | `approvals/edit/$1` |
| `/approvals/save` | `approvals/save` |

## assets
| URL | Controller action |
|-----|-------------------|
| `/assets-mgmt` | `assets/index` |
| `/assets-mgmt/assign/(:num)` | `assets/assign/$1` |
| `/assets-mgmt/create` | `assets/create` |
| `/assets-mgmt/edit/(:num)` | `assets/edit/$1` |
| `/assets-mgmt/my` | `assets/my` |
| `/assets-mgmt/return_asset/(:num)` | `assets/return_asset/$1` |

## attendance
| URL | Controller action |
|-----|-------------------|
| `/attendance` | `attendance/index` |
| `/attendance/(:num)/delete` | `attendance/delete/$1` |
| `/attendance/(:num)/edit` | `attendance/edit/$1` |
| `/attendance/create` | `attendance/create` |
| `/attendance/get-data` | `attendance/get_user_monthly_attendance` |
| `/attendance/get_data` | `attendance/get_data` |

## auth
| URL | Controller action |
|-----|-------------------|
| `/auth/send-verify-code` | `auth/send_verify_code` |
| `/auth/verify-2fa` | `auth/verify_2fa` |
| `/auth/verify-code` | `auth/verify_code` |

## calls
| URL | Controller action |
|-----|-------------------|
| `/calls/end/(:num)` | `calls/end/$1` |
| `/calls/incoming-any` | `calls/poll_incoming_any` |
| `/calls/incoming/(:num)` | `calls/poll_incoming/$1` |
| `/calls/poll/(:num)` | `calls/poll_signals/$1` |
| `/calls/signal/(:num)` | `calls/signal/$1` |
| `/calls/start/(:num)` | `calls/start/$1` |

## chats
| URL | Controller action |
|-----|-------------------|
| `/chats` | `chats/index` |
| `/chats/add-participants` | `chats/add_participants` |
| `/chats/app` | `chats/app` |
| `/chats/conversation/(:num)` | `chats/conversation/$1` |
| `/chats/create-group` | `chats/create_group` |
| `/chats/delete-message` | `chats/delete_message` |
| `/chats/edit-message` | `chats/edit_message` |
| `/chats/fetch` | `chats/fetch_messages` |
| `/chats/fetch_messages` | `chats/fetch_messages` |
| `/chats/get-online-status` | `chats/get_online_status` |
| `/chats/get-typing` | `chats/get_typing` |
| `/chats/online-status` | `chats/online_status` |
| `/chats/reaction` | `chats/add_reaction` |
| `/chats/reaction/remove` | `chats/remove_reaction` |
| `/chats/reactions` | `chats/get_reactions` |
| `/chats/remove-participant` | `chats/remove_participant` |
| `/chats/send` | `chats/send_message` |
| `/chats/send_message` | `chats/send_message` |
| `/chats/set-online-status` | `chats/set_online_status` |
| `/chats/set-typing` | `chats/set_typing` |
| `/chats/start-dm` | `chats/start_dm` |
| `/chats/typing` | `chats/typing` |

## clients
| URL | Controller action |
|-----|-------------------|
| `/clients` | `clients/index` |
| `/clients/(:num)/contacts` | `clients/contacts/$1` |
| `/clients/create` | `clients/create` |
| `/clients/delete/(:num)` | `clients/delete/$1` |
| `/clients/edit/(:num)` | `clients/edit/$1` |
| `/clients/export` | `clients/export` |
| `/clients/view/(:num)` | `clients/view/$1` |

## coaching
| URL | Controller action |
|-----|-------------------|
| `/coaching` | `coaching/index` |
| `/coaching-admin` | `coaching_admin/index` |
| `/coaching-admin/backup` | `coaching_admin/backup` |
| `/coaching-admin/run-automation` | `coaching_admin/run_automation` |
| `/coaching-billing` | `coaching_billing/index` |
| `/coaching-billing/create-invoice` | `coaching_billing/create_invoice` |
| `/coaching-billing/invoice/(:num)` | `coaching_billing/invoice/$1` |
| `/coaching-billing/mark-paid/(:num)` | `coaching_billing/mark_paid/$1` |
| `/coaching-billing/payouts` | `coaching_billing/payouts` |
| `/coaching-billing/save-program` | `coaching_billing/save_program` |
| `/coaching-clients` | `coaching_clients/index` |
| `/coaching-clients/create` | `coaching_clients/create` |
| `/coaching-clients/edit/(:num)` | `coaching_clients/edit/$1` |
| `/coaching-clients/view/(:num)` | `coaching_clients/view/$1` |
| `/coaching-coaches` | `coaching_coaches/index` |
| `/coaching-coaches/create` | `coaching_coaches/create` |
| `/coaching-coaches/delete/(:num)` | `coaching_coaches/delete/$1` |
| `/coaching-coaches/edit/(:num)` | `coaching_coaches/edit/$1` |
| `/coaching-goals` | `coaching_goals/index` |
| `/coaching-goals/save-goal` | `coaching_goals/save_goal` |
| `/coaching-goals/save-homework` | `coaching_goals/save_homework` |
| `/coaching-leads` | `coaching_leads/index` |
| `/coaching-leads/convert/(:num)` | `coaching_leads/convert/$1` |
| `/coaching-leads/create` | `coaching_leads/create` |
| `/coaching-leads/edit/(:num)` | `coaching_leads/edit/$1` |
| `/coaching-leads/workshop-form` | `coaching_leads/workshop_form` |
| `/coaching-leads/workshop-form/(:num)` | `coaching_leads/workshop_form/$1` |
| `/coaching-leads/workshop-register/(:num)` | `coaching_leads/workshop_register/$1` |
| `/coaching-leads/workshops` | `coaching_leads/workshops` |
| `/coaching-payments/confirm-manual/(:num)` | `coaching_payments/confirm_manual/$1` |
| `/coaching-payments/pay/(:num)` | `coaching_payments/pay/$1` |
| `/coaching-payments/success` | `coaching_payments/success` |
| `/coaching-payments/verify` | `coaching_payments/verify` |
| `/coaching-portal` | `coaching_portal/index` |
| `/coaching-portal/(:any)` | `coaching_portal/$1` |
| `/coaching-reports` | `coaching_reports/index` |
| `/coaching-resources` | `coaching_resources/index` |
| `/coaching-resources/save` | `coaching_resources/save` |
| `/coaching-sessions` | `coaching_sessions/index` |
| `/coaching-sessions/calendar` | `coaching_sessions/calendar` |
| `/coaching-sessions/create` | `coaching_sessions/create` |
| `/coaching-sessions/delete/(:num)` | `coaching_sessions/delete/$1` |
| `/coaching-sessions/edit/(:num)` | `coaching_sessions/edit/$1` |
| `/coaching-webhooks/razorpay` | `coaching_webhooks/razorpay` |
| `/coaching-webhooks/whatsapp-inbound` | `coaching_webhooks/whatsapp_inbound` |
| `/coaching-whatsapp` | `coaching_whatsapp_crm/index` |
| `/coaching-whatsapp-crm` | `coaching_whatsapp_crm/index` |
| `/coaching-whatsapp-crm/broadcast` | `coaching_whatsapp_crm/broadcast` |
| `/coaching-whatsapp-crm/save-enquiry` | `coaching_whatsapp_crm/save_enquiry` |
| `/coaching/admin` | `coaching_admin/index` |
| `/coaching/payments/(:num)` | `coaching_payments/pay/$1` |

## daily
| URL | Controller action |
|-----|-------------------|
| `/daily-activity` | `daily_activity/index` |
| `/daily-activity/delete/(:num)` | `daily_activity/delete/$1` |
| `/daily-activity/edit/(:num)` | `daily_activity/edit/$1` |
| `/daily-activity/export` | `daily_activity/export` |
| `/daily-activity/list` | `daily_activity/list_all` |
| `/daily-activity/save` | `daily_activity/save` |

## dashboard
| URL | Controller action |
|-----|-------------------|
| `/dashboard` | `dashboard/index` |

## db
| URL | Controller action |
|-----|-------------------|
| `/db` | `db/index` |
| `/db/apply-database-diff` | `db/apply_database_diff` |
| `/db/client-migrations` | `db/client_migrations` |
| `/db/clients` | `db/client_panel` |
| `/db/compare` | `db/compare` |
| `/db/compare-databases` | `db/compare_databases` |
| `/db/compare/drop-db-only` | `db/compare_drop_db_only` |
| `/db/compare/merge` | `db/compare_merge` |
| `/db/compare/scan` | `db/compare_scan` |
| `/db/compare/update-file-missing` | `db/compare_update_file_missing` |
| `/db/create-local-client-db` | `db/create_local_client_database` |
| `/db/databases` | `db/list_databases` |
| `/db/difference` | `db/db_difference` |
| `/db/ensure-schemas` | `db/ensure_schemas` |
| `/db/queries/delete/(\d+)` | `db/delete_query/$1` |
| `/db/queries/export-bulk` | `db/export_bulk_saved_queries` |
| `/db/queries/export/(\d+)` | `db/export_saved_query/$1` |
| `/db/queries/list` | `db/list_queries` |
| `/db/queries/revert/(\d+)` | `db/revert_query/$1` |
| `/db/queries/save` | `db/save_query` |
| `/db/queries/update/(\d+)` | `db/update_query/$1` |
| `/db/save-client-db-host` | `db/save_client_db_host` |
| `/db/test-client-connection` | `db/test_client_connection` |

## default_controller
| URL | Controller action |
|-----|-------------------|
| `/default_controller` | `auth` |
| `/default_controller` | `dashboard` |

## departments
| URL | Controller action |
|-----|-------------------|
| `/departments` | `departments/index` |
| `/departments/(:num)/delete` | `departments/delete/$1` |
| `/departments/(:num)/edit` | `departments/edit/$1` |
| `/departments/(:num)/restore` | `departments/restore/$1` |
| `/departments/create` | `departments/create` |

## designations
| URL | Controller action |
|-----|-------------------|
| `/designations` | `designations/index` |
| `/designations/(:num)/delete` | `designations/delete/$1` |
| `/designations/(:num)/edit` | `designations/edit/$1` |
| `/designations/(:num)/restore` | `designations/restore/$1` |
| `/designations/create` | `designations/create` |

## email
| URL | Controller action |
|-----|-------------------|
| `/email-settings` | `email_settings/index` |
| `/email-settings/edit-template/(:num)` | `email_settings/edit_template/$1` |
| `/email-settings/test-email` | `email_settings/test_email` |
| `/email-settings/update` | `email_settings/update` |
| `/email-settings/user-preferences` | `email_settings/user_preferences` |

## employees
| URL | Controller action |
|-----|-------------------|
| `/employees` | `employees/index` |
| `/employees/(:num)` | `employees/show/$1` |
| `/employees/(:num)/delete` | `employees/delete/$1` |
| `/employees/(:num)/documents` | `employees/documents/$1` |
| `/employees/(:num)/edit` | `employees/edit/$1` |
| `/employees/create` | `employees/create` |
| `/employees/documents/(:num)/delete` | `employees/delete_document/$1` |
| `/employees/documents/(:num)/download` | `employees/download_document/$1` |
| `/employees/import` | `employees/import` |

## expenses
| URL | Controller action |
|-----|-------------------|
| `/expenses` | `expenses/index` |
| `/expenses/approve/(:num)` | `expenses/approve/$1` |
| `/expenses/categories` | `expenses/categories` |
| `/expenses/categories/save` | `expenses/save_category` |
| `/expenses/categories/toggle/(:num)` | `expenses/toggle_category/$1` |
| `/expenses/create` | `expenses/create` |
| `/expenses/delete/(:num)` | `expenses/delete/$1` |
| `/expenses/edit/(:num)` | `expenses/edit/$1` |
| `/expenses/export` | `expenses/export` |
| `/expenses/pending` | `expenses/pending` |
| `/expenses/reimburse/(:num)` | `expenses/reimburse/$1` |
| `/expenses/reject/(:num)` | `expenses/reject/$1` |
| `/expenses/reports` | `expenses/reports` |
| `/expenses/view/(:num)` | `expenses/view/$1` |

## external
| URL | Controller action |
|-----|-------------------|
| `/external-training` | `external_training/index` |
| `/external-training/create` | `external_training/create` |
| `/external-training/delete/(:num)` | `external_training/delete/$1` |
| `/external-training/edit/(:num)` | `external_training/edit/$1` |
| `/external-training/save` | `external_training/save` |
| `/external-training/watch/(:num)` | `external_training/watch/$1` |

## forgot
| URL | Controller action |
|-----|-------------------|
| `/forgot-password` | `auth/forgot_password` |

## install
| URL | Controller action |
|-----|-------------------|
| `/install/schema` | `install/schema` |

## lead
| URL | Controller action |
|-----|-------------------|
| `/lead-mapping` | `lead_mapping/index` |
| `/lead-mapping/save` | `lead_mapping/save` |

## leave
| URL | Controller action |
|-----|-------------------|
| `/leave/apply` | `leave_requests/apply` |
| `/leave/approve/(:num)` | `leave_requests/approve/$1` |
| `/leave/calendar` | `leave_requests/calendar` |
| `/leave/delete/(:num)` | `leave_requests/delete/$1` |
| `/leave/edit/(:num)` | `leave_requests/edit/$1` |
| `/leave/get-employee-tasks/(:num)` | `leave_requests/get_employee_tasks/$1` |
| `/leave/my` | `leave_requests/my` |
| `/leave/reject/(:num)` | `leave_requests/reject/$1` |
| `/leave/team` | `leave_requests/team` |

## leaves
| URL | Controller action |
|-----|-------------------|
| `/leaves` | `leaves/index` |
| `/leaves/export` | `leaves/export_csv` |
| `/leaves/test-email` | `leaves/test_email` |

## login
| URL | Controller action |
|-----|-------------------|
| `/login` | `auth/login` |

## logout
| URL | Controller action |
|-----|-------------------|
| `/logout` | `auth/logout` |

## mail
| URL | Controller action |
|-----|-------------------|
| `/mail` | `mail/index` |
| `/mail/send` | `mail/send` |
| `/mail/test` | `mail/test` |

## my
| URL | Controller action |
|-----|-------------------|
| `/my-works` | `my_works/index` |
| `/my-works/(:num)` | `my_works/show/$1` |
| `/my-works/(:num)/comment` | `my_works/add_comment/$1` |
| `/my-works/(:num)/delete` | `my_works/delete/$1` |
| `/my-works/(:num)/download` | `my_works/download/$1` |
| `/my-works/(:num)/edit` | `my_works/edit/$1` |
| `/my-works/create` | `my_works/create` |
| `/my-works/export` | `my_works/export` |
| `/my-works/update-status` | `my_works/update_status` |

## notifications
| URL | Controller action |
|-----|-------------------|
| `/notifications` | `notifications/index` |
| `/notifications/count` | `notifications/count` |
| `/notifications/delete/(:num)` | `notifications/delete/$1` |
| `/notifications/mark-all-read` | `notifications/mark_all_read` |
| `/notifications/mark-read/(:num)` | `notifications/mark_read/$1` |
| `/notifications/recent` | `notifications/recent` |
| `/notifications/subscribe-push` | `notifications/subscribe_push` |
| `/notifications/unsubscribe-push` | `notifications/unsubscribe_push` |

## payroll
| URL | Controller action |
|-----|-------------------|
| `/payroll` | `payroll/index` |
| `/payroll/export/(:num)` | `payroll/export/$1` |
| `/payroll/generate` | `payroll/generate` |
| `/payroll/payslips` | `payroll/payslips` |
| `/payroll/send-payslips` | `payroll/send_payslips` |
| `/payroll/structure/(:num)` | `payroll/structure/$1` |
| `/payroll/structure/create` | `payroll/structure` |
| `/payroll/structures` | `payroll/structures` |
| `/payroll/view/(:num)` | `payroll/view/$1` |

## performance
| URL | Controller action |
|-----|-------------------|
| `/performance` | `performance/index` |
| `/performance/create` | `performance/create` |
| `/performance/delete/(:num)` | `performance/delete/$1` |
| `/performance/edit/(:num)` | `performance/edit/$1` |
| `/performance/export` | `performance/export` |
| `/performance/self-assess` | `performance/self_assess` |
| `/performance/self-assess/(:num)` | `performance/self_assess/$1` |
| `/performance/view/(:num)` | `performance/view/$1` |

## permissions
| URL | Controller action |
|-----|-------------------|
| `/permissions` | `permissions/index` |
| `/permissions/save` | `permissions/save` |

## profile
| URL | Controller action |
|-----|-------------------|
| `/profile` | `profile/index` |
| `/profile/delete` | `profile/delete_profile` |
| `/profile/edit` | `profile/edit` |
| `/profile/remove-avatar` | `profile/remove_avatar` |

## projects
| URL | Controller action |
|-----|-------------------|
| `/projects` | `projects/index` |
| `/projects/(:num)` | `projects/show/$1` |
| `/projects/(:num)/add-member` | `projects/add_member/$1` |
| `/projects/(:num)/delete` | `projects/delete/$1` |
| `/projects/(:num)/edit` | `projects/edit/$1` |
| `/projects/(:num)/member/(:num)/role` | `projects/update_member_role/$1/$2` |
| `/projects/(:num)/members` | `projects/manage_members/$1` |
| `/projects/(:num)/remove-member/(:num)` | `projects/remove_member/$1/$2` |
| `/projects/create` | `projects/create` |
| `/projects/import` | `projects/import` |

## recruitment
| URL | Controller action |
|-----|-------------------|
| `/recruitment` | `recruitment/index` |
| `/recruitment/apply/(:num)` | `recruitment/apply/$1` |
| `/recruitment/candidate/(:num)` | `recruitment/candidate_view/$1` |
| `/recruitment/candidate/(:num)/status` | `recruitment/candidate_status/$1` |
| `/recruitment/candidates` | `recruitment/candidates` |
| `/recruitment/close-job/(:num)` | `recruitment/close_job/$1` |
| `/recruitment/create-job` | `recruitment/create_job` |
| `/recruitment/delete-job/(:num)` | `recruitment/delete_job/$1` |
| `/recruitment/edit-job/(:num)` | `recruitment/edit_job/$1` |
| `/recruitment/export` | `recruitment/export` |
| `/recruitment/schedule-interview/(:num)` | `recruitment/schedule_interview/$1` |

## register
| URL | Controller action |
|-----|-------------------|
| `/register` | `auth/register` |

## reminders
| URL | Controller action |
|-----|-------------------|
| `/reminders` | `reminders/index` |
| `/reminders/announce` | `reminders/announce` |
| `/reminders/bulk` | `reminders/bulk` |
| `/reminders/cron/generate-today` | `reminders/cron_generate_today` |
| `/reminders/cron/morning` | `reminders/cron_morning` |
| `/reminders/cron/night` | `reminders/cron_night` |
| `/reminders/cron/send-queue` | `reminders/send_queue` |
| `/reminders/cron/send-selected` | `reminders/send_selected` |
| `/reminders/delete-selected` | `reminders/delete_selected` |
| `/reminders/delete/(:num)` | `reminders/delete/$1` |
| `/reminders/edit/(:num)` | `reminders/edit/$1` |
| `/reminders/import` | `reminders/import` |
| `/reminders/import-sample` | `reminders/import_sample` |
| `/reminders/schedules` | `reminders/schedules` |
| `/reminders/schedules/(:num)/activate` | `reminders/schedule_activate/$1` |
| `/reminders/schedules/(:num)/deactivate` | `reminders/schedule_deactivate/$1` |
| `/reminders/schedules/(:num)/delete` | `reminders/schedule_delete/$1` |
| `/reminders/schedules/(:num)/edit` | `reminders/schedule_edit/$1` |
| `/reminders/schedules/create` | `reminders/schedule_create` |
| `/reminders/send` | `reminders/send` |
| `/reminders/send-now/(:num)` | `reminders/send_now/$1` |
| `/reminders/templates` | `reminders/templates` |

## reports
| URL | Controller action |
|-----|-------------------|
| `/reports` | `reports/index` |
| `/reports/attendance` | `reports_attendance/attendance` |
| `/reports/attendance-employee` | `reports_attendance/attendance_employee` |
| `/reports/attendance-employee/(:num)` | `reports_attendance/attendance_employee/$1` |
| `/reports/daily-activity` | `reports_projects/daily_activity` |
| `/reports/expenses` | `reports_hr/expenses` |
| `/reports/export` | `reports/export_csv` |
| `/reports/export-attendance-employee` | `reports_attendance/export_attendance_employee` |
| `/reports/leaves` | `reports_hr/leaves` |
| `/reports/payroll` | `reports_hr/payroll` |
| `/reports/performance` | `reports_hr/performance` |
| `/reports/projects-status` | `reports_projects/projects_status` |
| `/reports/requirements` | `reports_projects/requirements` |
| `/reports/tasks-assignment` | `reports_projects/tasks_assignment` |

## requirements
| URL | Controller action |
|-----|-------------------|
| `/requirements` | `requirements/index` |
| `/requirements/(:num)/add-comment` | `requirements/add_comment/$1` |
| `/requirements/(:num)/comments` | `requirements/get_comments/$1` |
| `/requirements/(:num)/delete-comment/(:num)` | `requirements/delete_comment/$1/$2` |
| `/requirements/board` | `requirements/board` |
| `/requirements/calendar` | `requirements/calendar` |
| `/requirements/create` | `requirements/create` |
| `/requirements/edit/(:num)` | `requirements/edit/$1` |
| `/requirements/export` | `requirements/export` |
| `/requirements/version/(:num)` | `requirements/version/$1` |
| `/requirements/view/(:num)` | `requirements/view/$1` |

## reset
| URL | Controller action |
|-----|-------------------|
| `/reset-password` | `auth/reset_password` |

## roles
| URL | Controller action |
|-----|-------------------|
| `/roles` | `roles/index` |
| `/roles/delete/(:num)` | `roles/delete/$1` |
| `/roles/store` | `roles/store` |
| `/roles/update/(:num)` | `roles/update/$1` |

## sendgrid
| URL | Controller action |
|-----|-------------------|
| `/sendgrid` | `sendgrid/index` |
| `/sendgrid/send` | `sendgrid/send` |
| `/sendgrid/test` | `sendgrid/test` |

## settings
| URL | Controller action |
|-----|-------------------|
| `/settings` | `settings/index` |
| `/settings/holidays` | `settings/holidays` |
| `/settings/holidays/(:num)/delete` | `settings/holidays_delete/$1` |
| `/settings/holidays/(:num)/edit` | `settings/holidays_edit/$1` |
| `/settings/holidays/create` | `settings/holidays_create` |
| `/settings/leave-types` | `settings/leave_types` |
| `/settings/leave-types/(:num)/delete` | `settings/leave_types_delete/$1` |
| `/settings/leave-types/(:num)/edit` | `settings/leave_types_edit/$1` |
| `/settings/leave-types/(:num)/restore` | `settings/leave_types_restore/$1` |
| `/settings/leave-types/create` | `settings/leave_types_create` |
| `/settings/remove-logo` | `settings/remove_logo` |
| `/settings/test-email` | `settings/test_email` |
| `/settings/update` | `settings/update` |
| `/settings/upload-logo` | `settings/upload_logo` |

## shifts
| URL | Controller action |
|-----|-------------------|
| `/shifts` | `shifts/index` |
| `/shifts/create` | `shifts/create` |
| `/shifts/delete/(:num)` | `shifts/delete/$1` |
| `/shifts/edit/(:num)` | `shifts/edit/$1` |

## statuses
| URL | Controller action |
|-----|-------------------|
| `/statuses` | `statuses/index` |
| `/statuses/(:num)` | `statuses/show/$1` |
| `/statuses/create` | `statuses/create` |
| `/statuses/delete/(:num)` | `statuses/delete/$1` |
| `/statuses/edit/(:num)` | `statuses/edit/$1` |
| `/statuses/view/(:num)` | `statuses/view/$1` |

## superadmin
| URL | Controller action |
|-----|-------------------|
| `/superadmin` | `superadmin/index` |
| `/superadmin/(:any)` | `superadmin/$1` |

## system
| URL | Controller action |
|-----|-------------------|
| `/system-settings` | `system_settings/index` |
| `/system-settings/permissions` | `system_settings/permissions` |
| `/system-settings/success-screen` | `system_settings/success_screen` |
| `/system-settings/update-permissions` | `system_settings/update_permissions` |
| `/system-settings/update-settings` | `system_settings/update_settings` |
| `/system-settings/update-success-screen` | `system_settings/update_success_screen` |
| `/system-settings/update-user-access` | `system_settings/update_user_access` |
| `/system-settings/user-access` | `system_settings/user_access` |

## tasks
| URL | Controller action |
|-----|-------------------|
| `/tasks` | `tasks/index` |
| `/tasks/(:num)` | `tasks/show/$1` |
| `/tasks/(:num)/comment` | `tasks/add_comment/$1` |
| `/tasks/(:num)/comments` | `tasks/get_comments/$1` |
| `/tasks/(:num)/delete` | `tasks/delete/$1` |
| `/tasks/(:num)/edit` | `tasks/edit/$1` |
| `/tasks/(:num)/preview` | `tasks/preview/$1` |
| `/tasks/board` | `tasks/board` |
| `/tasks/bulk-update-status` | `tasks/bulk_update_status` |
| `/tasks/comment/(:num)/delete` | `tasks/delete_comment/$1` |
| `/tasks/create` | `tasks/create` |
| `/tasks/import` | `tasks/import` |
| `/tasks/send-all-summaries` | `tasks/send_all_summaries` |
| `/tasks/send-daily-summary` | `tasks/send_daily_summary` |
| `/tasks/update-status` | `tasks/update_status` |

## timesheets
| URL | Controller action |
|-----|-------------------|
| `/timesheets` | `timesheets/index` |
| `/timesheets/analytics` | `timesheets/analytics` |
| `/timesheets/approve/(:num)` | `timesheets/approve/$1` |
| `/timesheets/delete-entry/(:num)` | `timesheets/delete_entry/$1` |
| `/timesheets/reject/(:num)` | `timesheets/reject/$1` |
| `/timesheets/report` | `timesheets/report` |
| `/timesheets/submit` | `timesheets/submit` |
| `/timesheets/task-tracking` | `timesheets/task_tracking` |
| `/timesheets/task-tracking/(:num)` | `timesheets/task_tracking/$1` |

## training
| URL | Controller action |
|-----|-------------------|
| `/training` | `training_lms/index` |
| `/training-assessment` | `training_assessment/dashboard` |
| `/training-assessment/ajax-load-question` | `training_assessment_take/ajax_load_question` |
| `/training-assessment/ajax-run-code` | `training_assessment_take/ajax_run_code` |
| `/training-assessment/ajax-save-answer` | `training_assessment_take/ajax_save_answer` |
| `/training-assessment/ajax-timer-sync` | `training_assessment_take/ajax_timer_sync` |
| `/training-assessment/ajax-upload-screenshot` | `training_assessment_take/ajax_upload_screenshot` |
| `/training-assessment/assign/(:num)` | `training_assessment/assign/$1` |
| `/training-assessment/candidate-profile` | `training_assessment_take/candidate_profile` |
| `/training-assessment/certificate/(:num)` | `training_assessment/certificate/$1` |
| `/training-assessment/create` | `training_assessment/create_assessment` |
| `/training-assessment/dashboard` | `training_assessment/dashboard` |
| `/training-assessment/delete/(:num)` | `training_assessment/delete_assessment/$1` |
| `/training-assessment/duplicate/(:num)` | `training_assessment/duplicate_assessment/$1` |
| `/training-assessment/edit/(:num)` | `training_assessment/create_assessment/$1` |
| `/training-assessment/import` | `training_assessment/import_assessment` |
| `/training-assessment/import/process` | `training_assessment/import_process` |
| `/training-assessment/import/sample` | `training_assessment/import_sample_csv` |
| `/training-assessment/office-export/attempt-detail` | `training_assessment/office_export_attempt_detail` |
| `/training-assessment/office-export/questions` | `training_assessment/office_export_questions` |
| `/training-assessment/preview/(:num)` | `training_assessment/preview_assessment/$1` |
| `/training-assessment/question/add/(:num)` | `training_assessment/add_question/$1` |
| `/training-assessment/question/delete/(:num)` | `training_assessment/delete_question/$1` |
| `/training-assessment/question/duplicate/(:num)` | `training_assessment/duplicate_question/$1` |
| `/training-assessment/question/edit/(:num)` | `training_assessment/edit_question/$1` |
| `/training-assessment/question/import-dashboard` | `training_assessment/import_questions_dashboard_process` |
| `/training-assessment/question/import/(:num)` | `training_assessment/import_questions_process/$1` |
| `/training-assessment/question/import/sample` | `training_assessment/import_questions_sample_csv` |
| `/training-assessment/question/save` | `training_assessment/save_question` |
| `/training-assessment/questions/(:num)` | `training_assessment/question_list/$1` |
| `/training-assessment/questions/reorder` | `training_assessment/reorder_questions` |
| `/training-assessment/report` | `training_assessment/report` |
| `/training-assessment/report/export` | `training_assessment/report_export` |
| `/training-assessment/result-token/(:any)` | `training_assessment_take/result_token/$1` |
| `/training-assessment/result/(:num)` | `training_assessment/result/$1` |
| `/training-assessment/retake-assessment` | `training_assessment_take/retake_assessment` |
| `/training-assessment/save` | `training_assessment/save_assessment` |
| `/training-assessment/screenshots/(:num)` | `training_assessment/screenshots/$1` |
| `/training-assessment/screenshots/(:num)/delete-bulk` | `training_assessment/delete_screenshots_bulk/$1` |
| `/training-assessment/screenshots/(:num)/delete/(:num)` | `training_assessment/delete_screenshot/$1/$2` |
| `/training-assessment/submissions` | `training_assessment/submissions` |
| `/training-assessment/submit-assessment` | `training_assessment_take/submit_assessment` |
| `/training-assessment/take/(:any)` | `training_assessment_take/take_assessment/$1` |
| `/training-lms-admin` | `training_lms_admin/index` |
| `/training-lms-admin/assignment-submissions` | `training_lms_admin/assignment_submissions_list` |
| `/training-lms-admin/download/(:num)` | `training_lms_admin/download/$1` |
| `/training-lms-admin/enrollment-remove` | `training_lms_admin/enrollment_remove` |
| `/training-lms-admin/enrollment-save` | `training_lms_admin/enrollment_save` |
| `/training-lms-admin/enrollments/(:num)` | `training_lms_admin/module_enrollments/$1` |
| `/training-lms-admin/module/create` | `training_lms_admin/module_form` |
| `/training-lms-admin/module/delete/(:num)` | `training_lms_admin/delete_module/$1` |
| `/training-lms-admin/module/edit/(:num)` | `training_lms_admin/module_form/$1` |
| `/training-lms-admin/save-module` | `training_lms_admin/save_module` |
| `/training-lms-admin/save-topic` | `training_lms_admin/save_topic` |
| `/training-lms-admin/submission/save` | `training_lms_admin/submission_save` |
| `/training-lms-admin/submissions/(:num)` | `training_lms_admin/submissions/$1` |
| `/training-lms-admin/topic/create/(:num)` | `training_lms_admin/topic_form/$1` |
| `/training-lms-admin/topic/delete/(:num)` | `training_lms_admin/delete_topic/$1` |
| `/training-lms-admin/topic/edit/(:num)/(:num)` | `training_lms_admin/topic_form/$1/$2` |
| `/training-lms-admin/topics/(:num)` | `training_lms_admin/topics/$1` |
| `/training/complete-topic` | `training_lms/complete_topic` |
| `/training/download/(:num)` | `training_lms/download/$1` |
| `/training/import` | `training_import/index` |
| `/training/import/process` | `training_import/process` |
| `/training/import/sample/(:any)` | `training_import/sample/$1` |
| `/training/module/(:num)` | `training_lms/module/$1` |
| `/training/my-training` | `training_lms/learner_hub` |
| `/training/start-assessment/(:num)` | `training_lms/start_assessment/$1` |
| `/training/submit-assignment` | `training_lms/submit_assignment` |
| `/training/topic/(:num)` | `training_lms/topic/$1` |

## users
| URL | Controller action |
|-----|-------------------|
| `/users` | `users/index` |
| `/users/check-email` | `users/check_email` |
| `/users/check-phone` | `users/check_phone` |
| `/users/create` | `users/create` |
| `/users/delete/(:num)` | `users/delete/$1` |
| `/users/destroy/(:num)` | `users/destroy/$1` |
| `/users/edit/(:num)` | `users/edit/$1` |
| `/users/save_face` | `users/save_face` |
| `/users/store` | `users/store` |
| `/users/update/(:num)` | `users/update/$1` |
| `/users/view/(:num)` | `users/view/$1` |

## whatsapp
| URL | Controller action |
|-----|-------------------|
| `/whatsapp` | `whatsapp/index` |
| `/whatsapp/send` | `whatsapp/send` |
| `/whatsapp/send-report` | `whatsapp/send_report` |
| `/whatsapp/send-task` | `whatsapp/send_task` |
