# Office Management System — Agent Instructions

CodeIgniter 3 internal HR/operations portal (PHP 8.4, MySQL, WAMP).

## Rules (read in order)

1. **`.cursorrules`** — production constraints, prohibitions, module map
2. **`.cursor/rules/project-workflow.mdc`** — scope, workflow, API/form conventions (always on)
3. **File-scoped rules** in `.cursor/rules/` — activate when matching files are open

## Key docs

| File | Purpose |
|------|---------|
| `PROJECT_CONTEXT.md` | What this system is |
| `DEVELOPMENT_GUIDELINES.md` | Dev standards |
| `MODULE_DOCUMENTATION.md` | All modules |
| `DATABASE_DOCUMENTATION.md` | Schema reference |

## New features checklist

When adding user-facing functionality, follow `.cursor/rules/new-feature-rbac-and-guide.mdc` (permissions matrix + user guide).

## Source reference

Detailed examples remain in `.github/instructions/` and `.github/copilot-instructions.md`. **Edit `.cursor/rules/*.mdc` for Cursor.**
