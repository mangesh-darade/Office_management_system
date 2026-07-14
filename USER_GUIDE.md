# Office Management System — User Guide

End-user help for all modules. **In the app:** sidebar → **User Guide**.

Detailed guides with screenshots live in [`docs/user-guide/`](docs/user-guide/). Source catalog: `docs/user-guide/module_catalog.json`.

## Modules

| # | Module | Guide |
|---|--------|-------|
| 01 | Login & Dashboard | [docs/user-guide/01-authentication-dashboard.md](docs/user-guide/01-authentication-dashboard.md) |
| 02 | People & Organization | [docs/user-guide/02-people-organization.md](docs/user-guide/02-people-organization.md) |
| 03 | Attendance & Leave | [docs/user-guide/03-attendance-leave.md](docs/user-guide/03-attendance-leave.md) |
| 04 | Projects & Tasks | [docs/user-guide/04-projects-tasks.md](docs/user-guide/04-projects-tasks.md) |
| 05 | Work Tracking | [docs/user-guide/05-work-tracking.md](docs/user-guide/05-work-tracking.md) |
| 06 | Reports & Analytics | [docs/user-guide/06-reports-analytics.md](docs/user-guide/06-reports-analytics.md) |
| 07 | Communication | [docs/user-guide/07-communication.md](docs/user-guide/07-communication.md) |
| 08 | Finance | [docs/user-guide/08-finance.md](docs/user-guide/08-finance.md) |
| 09 | Training & Coaching | [docs/user-guide/09-training-coaching.md](docs/user-guide/09-training-coaching.md) |
| 10 | Administration | [docs/user-guide/10-administration.md](docs/user-guide/10-administration.md) |
| 11 | Engagement & Rewards | [docs/user-guide/11-engagement-rewards.md](docs/user-guide/11-engagement-rewards.md) |
| 12 | Office Meals | [docs/user-guide/12-office-meals.md](docs/user-guide/12-office-meals.md) |
| 13 | Sales — Business Assessment | [docs/user-guide/13-sales-eba.md](docs/user-guide/13-sales-eba.md) |
| 14 | Sales — Subscription Builder & Proposals | [docs/user-guide/14-sales-proposals.md](docs/user-guide/14-sales-proposals.md) |

## Maintain the guide

When adding or changing user-facing screens:

1. Update `docs/user-guide/module_catalog.json`
2. Run `python tools/generate_user_guide_modules.py`
3. Optionally capture screenshots: `python tools/capture_user_guide_screenshots.py`

See `.cursor/rules/new-feature-rbac-and-guide.mdc` for the full checklist.

## Index & routes

- Module index: [docs/user-guide/README.md](docs/user-guide/README.md)
- Route list: [docs/user-guide/_ROUTE_INDEX.md](docs/user-guide/_ROUTE_INDEX.md)
