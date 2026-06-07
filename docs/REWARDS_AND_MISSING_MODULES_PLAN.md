# Missing Modules & Rewards — Implementation Plan

## Phase 0 — Gap (completed in blueprint)

| Missing module | MVP scope | Reward trigger |
|----------------|-----------|----------------|
| Release Management | `project_releases` linked to projects | `release_completed` |
| Knowledge Base | `kb_articles` CRUD + publish | `kb_article_published` |
| Helpdesk / Ticketing | `helpdesk_tickets` create/resolve | `ticket_resolved` |
| Event Management | `company_events` calendar list | `event_attended` (manual check-in later) |
| Certifications | `employee_certifications` + approval | `certification_approved` |
| Customer Feedback | `customer_feedback_entries` | `feedback_submitted` |
| Native Cheers / R&R | **Rewards module** (replaces Looker card) | all automated + manual rules |

## Phase 1 — Missing modules (this delivery)

1. `engagement_schema_helper.php` — all six module tables
2. `Engagement_model.php` — shared CRUD
3. Controllers + list/form views per module
4. Routes, sidebar, Permission Manager keys, `permission_helper` map

## Phase 2 — Rewards engine (this delivery)

1. `rewards_schema_helper.php` — rules, transactions, levels, leaderboard, approvals, audit
2. `Reward_model.php` + `Reward_engine` library
3. `Rewards` controller — dashboard, history, leaderboard, peer cheer, admin rules
4. Seed default rules (attendance, daily activity, tasks, learning, releases, certs)
5. Cron hook placeholder in `Cron.php` for leaderboard rebuild

## Phase 3 — Automation hooks (this delivery)

| Location | Event |
|----------|-------|
| `Attendance::create()` | `attendance_checkin`, `attendance_checkout` |
| `Daily_activity::save()` | `daily_activity_logged` |
| `Tasks::update_status()` | `task_completed` |
| `Training_lms::complete_topic()` | `lms_topic_completed` |
| `Training_assessment_model::finalize_result()` | `assessment_passed` |
| `Releases` complete action | `release_completed` |
| `Certifications` approve | `certification_approved` |
| `Knowledge_base` publish | `kb_article_published` |
| `Helpdesk` resolve | `ticket_resolved` |
| `Rewards::send_cheer()` | `peer_cheer_received` |

## Phase 4 — Follow-up (not in this PR)

- Missed punch cron rules
- Full approval queue UI for Category B claims
- Replace external Cheers dashboard card with native widget
- User guide + screenshot capture
- `project_status_history` population on project edit

## RBAC keys

**Engagement:** `releases`, `knowledge_base`, `helpdesk`, `events`, `certifications`, `customer_feedback`

**Rewards:** `rewards`, `rewards_leaderboard`, `rewards_submit`, `rewards_approve`, `rewards_admin`, `rewards_rules`, `rewards_manual_grant`

## Effort

| Phase | Status |
|-------|--------|
| Phase 1 Missing modules MVP | In progress |
| Phase 2 Rewards core | In progress |
| Phase 3 Hooks | In progress |
| Phase 4 Polish | Backlog |

## Cursor rule compliance (new-feature-rbac-and-guide)

- [x] `Permissions.php` matrix — Engagement & Rewards section
- [x] `permission_helper.php` controller map + dashboard groups
- [x] `seed_engagement_rewards_permissions_if_needed()` — auto-grant on schema bootstrap
- [x] Controller + view gates aligned
- [x] Sidebar + mobile nav (`header.php`)
- [x] `module_catalog.json` module 11 + `generate_user_guide_modules.py`
- [x] `USER_GUIDE.md` + `user_guide_helper.php` module 11
- [x] Audit scripts: **0 missing keys**
