# Add / Edit Form Screens — Compact UI Index

> **Compact UI applied:** wrapper `oms-form-compact` + CSS `assets/css/compact-forms.css`  
> **Regenerate wrappers:** `python tools/apply_compact_form_ui.py`  
> **Business logic:** unchanged (layout/CSS only)

## What changed (visual only)

| Change | Detail |
|--------|--------|
| Wrapper | `<div class="oms-form-compact">` on every add/edit screen |
| Grid | `row g-3` → `row g-2 oms-form-grid` (tighter gaps; 3 cols on desktop for `col-md-6`) |
| Page head | `oms-form-page-head mb-2` — smaller title + back button |
| Card | `oms-form-card` — reduced padding |
| Actions | `oms-form-actions` — compact button bar with top border |
| Controls | Smaller labels, inputs, textareas via CSS (no field names/validation changed) |

---

## Master list (63 screens)

### 01 — People & Org
| # | Screen | View file | Route | Status |
|---|--------|-----------|-------|--------|
| 1 | Department add/edit | `departments/form.php` | `/departments/create`, `/departments/{id}/edit` | ✅ Compact |
| 2 | Designation add/edit | `designations/form.php` | `/designations/create`, `/designations/{id}/edit` | ✅ Compact |
| 3 | Shift add/edit | `shifts/form.php` | `/shifts/create`, `/shifts/edit/{id}` | ✅ Compact |
| 4 | Employee add/edit | `employees/form.php` | `/employees/create`, `/employees/{id}/edit` | ✅ Compact |
| 5 | User add/edit | `users/form.php` | `/users/create`, `/users/edit/{id}` | ✅ Compact |
| 6 | Client add | `clients/create.php` | `/clients/create` | ✅ Compact |
| 7 | Client edit | `clients/edit.php` | `/clients/edit/{id}` | ✅ Compact |

### 02 — Attendance & Leave
| # | Screen | View file | Route | Status |
|---|--------|-----------|-------|--------|
| 8 | Attendance punch/edit | `attendance/create.php`, `attendance/edit.php` | `/attendance/create`, `/attendance/{id}/edit` | ✅ Compact |
| 9 | Leave apply | `leave_requests/apply.php` | `/leave/apply` | ✅ Compact |
| 10 | Leave edit | `leave_requests/edit.php` | `/leave/edit/{id}` | ✅ Compact |

### 03 — Projects & Work
| # | Screen | View file | Route | Status |
|---|--------|-----------|-------|--------|
| 11 | Project add/edit | `projects/form.php` | `/projects/create`, `/projects/{id}/edit` | ✅ Compact |
| 12 | Task add/edit | `tasks/form.php` | `/tasks/create`, `/tasks/{id}/edit` | ✅ Compact |
| 13 | Requirement add | `requirements/create.php` | `/requirements/create` | ✅ Compact |
| 14 | Requirement edit | `requirements/edit.php` | `/requirements/edit/{id}` | ✅ Compact |
| 15 | My Works add/edit | `my_works/form.php` | `/my-works/create`, `/my-works/{id}/edit` | ✅ Compact |
| 16 | My Works create (alt) | `my_works/form_create.php` | `/my-works/create` | ✅ Compact |
| 17 | My Works quick add | `my_works/quick_add.php` | `/my-works/quick-add` | ✅ Compact |
| 18 | Daily activity edit | `daily_activity/edit.php` | `/daily-activity/edit/{id}` | ✅ Compact |
| 19 | Type add/edit | `types/form.php` | `/types/create`, `/types/edit/{id}` | ✅ Compact |
| 20 | Status add/edit | `statuses/form.php` | `/statuses/create`, `/statuses/edit/{id}` | ✅ Compact |
| 21 | Approval workflow | `approvals/form.php` | `/approvals/create`, `/approvals/edit/{id}` | ✅ Compact |

### 04 — HR & Finance
| # | Screen | View file | Route | Status |
|---|--------|-----------|-------|--------|
| 22 | Expense add | `expenses/create.php` | `/expenses/create` | ✅ Compact |
| 23 | Expense edit | `expenses/edit.php` | `/expenses/edit/{id}` | ✅ Compact |
| 24 | Payroll structure | `payroll/structure_form.php` | `/payroll/structure`, `/payroll/structure/{id}` | ✅ Compact |
| 25 | Performance create | `performance/create.php` | `/performance/create` | ✅ Compact |
| 26 | Performance edit | `performance/edit.php` | `/performance/edit/{id}` | ✅ Compact |
| 27 | Performance self-assess | `performance/self_assess.php` | `/performance/self-assess` | ✅ Compact |
| 28 | Recruitment job | `recruitment/create_job.php` | `/recruitment/create-job` | ✅ Compact |
| 29 | Schedule interview | `recruitment/schedule_interview.php` | `/recruitment/schedule-interview/{id}` | ✅ Compact |
| 30 | Asset add/edit | `assets/form.php` | `/assets-mgmt/create`, `/assets-mgmt/edit/{id}` | ✅ Compact |
| 31 | Asset assign | `assets/assign.php` | `/assets-mgmt/assign/{id}` | ✅ Compact |

### 05 — Training & LMS
| # | Screen | View file | Route | Status |
|---|--------|-----------|-------|--------|
| 32 | Assessment create/edit | `training_assessment/create_assessment.php` | `/training-assessment/create` | ✅ Compact |
| 33 | Assessment assign | `training_assessment/assign.php` | `/training-assessment/assign/{id}` | ✅ Compact |
| 34 | Assessment question | `training_assessment/add_question.php` | `/training-assessment/question/add/{id}` | ✅ Compact |
| 35 | LMS module | `training_lms/admin/module_form.php` | `/training-lms-admin/module/create` | ✅ Compact |
| 36 | LMS topic | `training_lms/admin/topic_form.php` | `/training-lms-admin/topic/create/{id}` | ✅ Compact |
| 37 | External training | `external_training/form.php` | `/external-training/create` | ✅ Compact |

### 06 — Coaching
| # | Screen | View file | Route | Status |
|---|--------|-----------|-------|--------|
| 37 | Coaching client | `coaching/clients/form.php` | `/coaching-clients/create` | ✅ Compact |
| 38 | Coaching coach | `coaching/coaches/form.php` | `/coaching-coaches/create` | ✅ Compact |
| 39 | Coaching lead | `coaching/leads/form.php` | `/coaching-leads/create` | ✅ Compact |
| 40 | Workshop form | `coaching/leads/workshop_form.php` | `/coaching-leads/workshop-form` | ✅ Compact |
| 41 | Coaching session | `coaching/sessions/form.php` | `/coaching-sessions/create` | ✅ Compact |

### 07 — Communication & Admin
| # | Screen | View file | Route | Status |
|---|--------|-----------|-------|--------|
| 42 | Announcement | `announcements/form.php` | `/announcements/create` | ✅ Compact |
| 43 | API integration | `api_integrations/form.php` | `/api-integrations/create` | ✅ Compact |
| 44 | Reminder edit | `reminders/edit.php` | `/reminders/edit/{id}` | ✅ Compact |
| 45 | Reminder schedule | `reminders/schedule_form.php` | `/reminders/schedules/create` | ✅ Compact |
| 46 | Email template | `email_settings/edit_template.php` | `/email-settings/edit-template/{id}` | ✅ Compact |
| 47 | Profile edit | `profile/edit.php` | `/profile/edit` | ✅ Compact |
| 48 | Holiday | `settings/holidays/form.php` | `/settings/holidays/create` | ✅ Compact |
| 49 | Leave type | `settings/leave_types/form.php` | `/settings/leave-types/create` | ✅ Compact |
| 50 | Module type | `settings/module_types/form.php` | `/settings/types/create` | ✅ Compact |
| 51 | Subscription builder item | `settings/subscription_builder/form.php` | `/settings/subscription-builder/create` | ✅ Compact |

### 08 — Engagement & Rewards
| # | Screen | View file | Route | Status |
|---|--------|-----------|-------|--------|
| 52 | Release notes | `releases/form.php` | `/releases/create` | ✅ Compact |
| 53 | Defect | `defects/form.php` | `/defects/create` | ✅ Compact |
| 54 | Knowledge base | `knowledge_base/form.php` | `/knowledge-base/create` | ✅ Compact |
| 55 | Helpdesk ticket | `helpdesk/form.php` | `/helpdesk/create` | ✅ Compact |
| 56 | Event | `events/form.php` | `/events/create` | ✅ Compact |
| 57 | Certification request | `certifications/form.php` | `/certifications/create` | ✅ Compact |
| 58 | Customer feedback | `customer_feedback/form.php` | `/customer-feedback/create` | ✅ Compact |
| 59 | Reward rule | `rewards/rule_form.php` | `/rewards/edit-rule` | ✅ Compact |
| 60 | Reward claim | `rewards/submit_claim.php` | `/rewards/submit-claim` | ✅ Compact |
| 61 | Manual reward grant | `rewards/manual_grant.php` | `/rewards/manual-grant` | ✅ Compact |

---

## Excluded (not standard add/edit forms)

| Screen | Reason |
|--------|--------|
| Auth login/register/reset | Public auth — different layout |
| Training assessment take | Candidate proctored UI |
| Coaching workshop register | Public page |
| Settings index / system_settings | Tabbed settings dashboard |
| DB manager, AI chat, analytics | Complex tool UIs |
| List pages with inline modals | Modal forms — separate pass if needed |

---

## Files touched

- `assets/css/compact-forms.css` — shared compact styles
- `application/views/partials/header.php` — loads compact-forms.css globally (scoped)
- `tools/apply_compact_form_ui.py` — batch wrapper script
- **63 view files** under `application/views/` (last audit: all dedicated add/edit covered)

## Audit command

```bash
python tools/audit_compact_forms.py
```
