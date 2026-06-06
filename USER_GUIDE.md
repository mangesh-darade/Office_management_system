# Office Management System — User Guide

**Who is this for?** Everyone who uses the app — staff, team leads, HR, and admins.

**Open in the app:** log in → sidebar → **User Guide** (above Log out)

**Language:** Simple English  
**Screenshots:** Every screen has a picture (high resolution, easy to read)  
**Videos:** Short animated walkthroughs with **English voice** (look for the **Voice** badge — click ▶ Play)

Each screen includes:
- **Purpose** — why the module exists
- **View list** — screenshot
- **Add** — create new (screenshot + steps)
- **Edit** — update existing (screenshot + steps)
- **Delete** — how to remove safely  
**Last updated:** June 6, 2026

---

## Module guides (start here)

Each module is a separate guide with **steps + screenshots** for every screen.

| # | Module | What it covers | Open guide |
|---|--------|----------------|------------|
| 01 | **Login & Dashboard** | Sign in, home screen, profile, alerts | [Open →](docs/user-guide/01-authentication-dashboard.md) |
| 02 | **People & Organization** | Users, employees, departments, shifts | [Open →](docs/user-guide/02-people-organization.md) |
| 03 | **Attendance & Leave** | Punch, apply leave, approve leave | [Open →](docs/user-guide/03-attendance-leave.md) |
| 04 | **Projects & Tasks** | Projects, requirements, task board | [Open →](docs/user-guide/04-projects-tasks.md) |
| 05 | **Work Tracking** | My Works, daily activity, appraisals | [Open →](docs/user-guide/05-work-tracking.md) |
| 06 | **Reports & Analytics** | All reports, AI assistant | [Open →](docs/user-guide/06-reports-analytics.md) |
| 07 | **Communication** | Chats, mail, announcements | [Open →](docs/user-guide/07-communication.md) |
| 08 | **Finance** | Payroll, expenses, recruitment | [Open →](docs/user-guide/08-finance.md) |
| 09 | **Training & Coaching** | Tests, LMS, coaching CRM | [Open →](docs/user-guide/09-training-coaching.md) |
| 10 | **Administration** | Settings, permissions, email, DB | [Open →](docs/user-guide/10-administration.md) |

**Full index with image list:** [docs/user-guide/README.md](docs/user-guide/README.md)

---

## Quick start (most used)

| I want to… | Go to… |
|------------|--------|
| Log in | Login page |
| See my summary | **Dashboard** |
| Punch in / out | **User Management → Attendance** |
| Apply for leave | **Leave → Apply Leave** |
| See my tasks | **Project → Task** (board) |
| Log my work | **My Works** |
| Chat with a colleague | **Chats** |
| Change my details | Top right → **Profile** |
| Log out | **Log out** (bottom of sidebar) |

---

## Roles — what you can see

| Role | Typical access |
|------|----------------|
| **Staff** | Dashboard, My Works, own attendance/leave, assigned tasks, chats |
| **Team Lead** | Above + team leave approval and team attendance |
| **HR / Admin** | Users, employees, reports, payroll, some settings |
| **Super Admin** | Everything including DB manager and coaching admin |

Missing a menu? Ask admin to grant access in **Settings → Permission Manager**.

---

## Screenshots & animated videos

| Type | Folder | In the app |
|------|--------|------------|
| **Screenshots** | `docs/user-guide/images/` | Shown on each guide page |
| **Videos** | `docs/user-guide/videos/` | Click **Play** on the video player |

**Refresh screenshots:**

```powershell
$env:OMS_TEST_LOGIN="your_phone_or_email"
$env:OMS_TEST_PASSWORD="your_password"
python tools/capture_user_guide_screenshots.py
```

**Refresh animated videos (then add voice):**

```powershell
$env:OMS_TEST_LOGIN="your_phone_or_email"
$env:OMS_TEST_PASSWORD="your_password"
python tools/capture_user_guide_videos.py
python tools/add_voice_to_guide_videos.py
```

**Regenerate guide text from catalog** (after editing `docs/user-guide/module_catalog.json`):

```powershell
python tools/generate_user_guide_modules.py
python tools/capture_user_guide_screenshots.py
```

---

## Help

| Problem | Try this |
|---------|----------|
| Cannot log in | Check email/phone and password. Complete 2FA if asked. |
| Access denied | You need permission — contact admin. |
| Punch fails | Turn on location/camera. Check shift settings. |
| Empty export | Select date range and employees first. |
| No email | Admin checks **Email Settings**. Check profile notification options. |

---

*Pick a module from the table above and follow the steps with screenshots.*
