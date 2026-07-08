# Unused / Dev-only files (not used by the live app)

Moved here to keep the project root clean. Safe to delete if you do not need dev tools or one-time seeds.

## Contents

| Path | What it is |
|------|------------|
| `tools/` | Python/PHP audit, screenshot, and import scripts |
| `O_db/` | Old database dump / reference SQL |
| `sql/` | Demo data notes |
| `dev_scripts/` | Root test/debug PHP, one-off PS1, schema checker |
| `samples/` | LMS import sample CSVs (docs only; app uses `assets/samples/` + one file in root `samples/`) |
| `database/` | Marketing/training one-time seed SQL and generator scripts |

## Still at project root (in use)

- `samples/training_assessment_import_sample.csv` — Training Assessment import
- `database/subscription_builder_*` — Subscription Builder module
- `database/training_lms_module.sql`, `database/training_assessment_module.sql` — setup referenced in app messages
- `reminders_cron.php`, `sw.js`, `composer.*`, `index.php`, `docs/`, `application/`, etc.
