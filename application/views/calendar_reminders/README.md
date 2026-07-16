# Google Calendar Reminders (CodeIgniter)

PHP-only Google Calendar email reminders inside OMS.

## URLs

| Path | Purpose |
|------|---------|
| `/calendar-reminders` | Create reminder form |
| `/calendar-reminders/settings` | OAuth Client ID / Secret |
| `/calendar-reminders/connect` | Start Google OAuth |
| `/calendar-reminders/oauth-callback` | OAuth return |

## Setup

1. Google Cloud → enable **Google Calendar API**
2. Create OAuth client type **Web application**
3. Add the redirect URI shown on the Settings page
4. Permission Manager → enable **Google Calendar Reminders** → Save
5. Settings → Google Calendar Reminders → paste Client ID/Secret → **Connect Google**

## Files

- `application/controllers/Calendar_reminders.php`
- `application/libraries/Google_calendar_lib.php`
- `application/views/calendar_reminders/*`
- Token/credentials: `application/cache/google_calendar/` (gitignored)
