# User Guide — Module Index

Complete guides in **simple English** with **readable screenshots** for every screen.

**Start:** [USER_GUIDE.md](../../USER_GUIDE.md)

**In the app:** sidebar → **User Guide** (all 12 modules with screenshots).

---

## All modules

| Module | Screens | Guide |
|--------|---------|-------|
| 01 Login & Dashboard | 6 | [01-authentication-dashboard.md](01-authentication-dashboard.md) |
| 02 People & Organization | 8 | [02-people-organization.md](02-people-organization.md) |
| 03 Attendance & Leave | 6 | [03-attendance-leave.md](03-attendance-leave.md) |
| 04 Projects & Tasks | 5 | [04-projects-tasks.md](04-projects-tasks.md) |
| 05 Work Tracking | 4 | [05-work-tracking.md](05-work-tracking.md) |
| 06 Reports & Analytics | 8 | [06-reports-analytics.md](06-reports-analytics.md) |
| 07 Communication | 6 | [07-communication.md](07-communication.md) |
| 08 Finance | 6 | [08-finance.md](08-finance.md) |
| 09 Training & Coaching | 5 | [09-training-coaching.md](09-training-coaching.md) |
| 10 Administration | 9 | [10-administration.md](10-administration.md) |
| 11 Engagement & Rewards | 10 | [11-engagement-rewards.md](11-engagement-rewards.md) |
| 12 Office Meals | 7 | [12-office-meals.md](12-office-meals.md) |

**Total: 63+ screenshots + 13 animated videos** at readable size (add engagement screenshots via capture script).

---

## Videos (animated walkthroughs)

| File | Shows |
|------|-------|
| `videos/01-auth/login-demo.webm` | Typing login and signing in |
| `videos/01-auth/dashboard-tour.webm` | Dashboard scroll tour |
| `videos/01-auth/user-guide-tour.webm` | In-app User Guide menu |
| `videos/02-people/employees-tour.webm` | Employees list |
| `videos/03-attendance-leave/attendance-tour.webm` | Attendance screen |
| `videos/03-attendance-leave/leave-apply-tour.webm` | Apply leave form |
| `videos/04-projects/tasks-board-tour.webm` | Task board |
| `videos/05-work/my-works-tour.webm` | My Works |
| `videos/06-reports/reports-tour.webm` | Attendance report |
| `videos/07-communication/chats-tour.webm` | Chats |
| `videos/08-finance/expenses-tour.webm` | Expenses |
| `videos/09-training/assessment-tour.webm` | Training hub |
| `videos/10-admin/settings-tour.webm` | System settings |

---

## Regenerate screenshots

```powershell
$env:OMS_TEST_LOGIN="your_phone_or_email"
$env:OMS_TEST_PASSWORD="your_password"
python tools/capture_user_guide_screenshots.py
```

## Screenshot folders

| Folder | Files |
|--------|-------|
| [images/01-auth/](images/01-auth/) | login, forgot-password, dashboard, profile, profile-edit, notifications |
| [images/02-people/](images/02-people/) | users, employees, departments, designations, shifts, assets, clients, roles |
| [images/03-attendance-leave/](images/03-attendance-leave/) | attendance, leave-apply, leave-my, leave-team, leave-calendar, timesheets |
| [images/04-projects/](images/04-projects/) | projects, requirements, tasks-list, tasks-board, approvals |
| [images/05-work/](images/05-work/) | my-works, daily-activity, daily-activity-list, performance |
| [images/06-reports/](images/06-reports/) | reports-home, attendance-employee, attendance-org, leaves, tasks-assignment, daily-activity, analytics, ai-chat |
| [images/07-communication/](images/07-communication/) | chats, mail, sendgrid, whatsapp, announcements, reminders |
| [images/08-finance/](images/08-finance/) | payroll-payslips, payroll-structures, payroll-generate, expenses, expenses-pending, recruitment |
| [images/09-training/](images/09-training/) | assessment, my-training, lms-admin, external-training, coaching |
| [images/10-admin/](images/10-admin/) | settings, permissions, email-settings, activity, holidays, leave-types, api-integrations, db, superadmin |

---

## Regenerate videos

```powershell
$env:OMS_TEST_LOGIN="your_phone_or_email"
$env:OMS_TEST_PASSWORD="your_password"
python tools/capture_user_guide_videos.py
```

Optional: set `OMS_BASE_URL` if not using localhost WAMP.

---

## Technical route list

Auto-generated URL reference: [_ROUTE_INDEX.md](_ROUTE_INDEX.md)

```bash
python tools/generate_user_guide_index.py
```
