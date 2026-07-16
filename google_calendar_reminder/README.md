# Google Calendar Email Reminder (Python)

Standalone tool (outside the PHP app). Creates a Google Calendar event and sends an **email reminder** to the address you set in `config.json`.

## What it does

1. OAuth login with your Google account (first run opens browser)
2. Creates a calendar event at the time you give
3. Invites the filled email + sets Calendar **email reminder** X minutes before

## Setup (one time)

### 1. Python packages

```powershell
cd c:\wamp64\www\Office_management_system\google_calendar_reminder
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
```

### 2. Google Cloud Console

1. Open [Google Cloud Console](https://console.cloud.google.com/)
2. Create a project (or pick one)
3. **APIs & Services → Library** → enable **Google Calendar API**
4. **APIs & Services → OAuth consent screen** → External (or Internal if Workspace) → add your Gmail as test user
5. **Credentials → Create Credentials → OAuth client ID → Desktop app**
6. Download the JSON → rename/save as `credentials.json` in this folder

### 3. Config

```powershell
Copy-Item config.example.json config.json
```

Edit `config.json`:

```json
{
  "email": "you@gmail.com",
  "calendar_id": "primary",
  "default_reminder_minutes": 30,
  "timezone": "Asia/Kolkata"
}
```

## Web UI (recommended)

```powershell
cd c:\wamp64\www\Office_management_system\google_calendar_reminder
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
python app.py
```

Open **http://127.0.0.1:5055** — enter Gmail, title, date/time, click **Create reminder**.

## CLI usage

```powershell
# Create reminder (invite + email reminder 30 min before)
python main.py create --title "Client call" --when "2026-07-16 10:00"

# Custom email and remind 60 minutes before
python main.py create --title "Payment due" --when "2026-07-20 09:00" --email other@gmail.com --minutes 60

# Optional description / duration
python main.py create --title "Meeting" --when "2026-07-16 15:30" --duration 45 --description "Bring docs"

# List upcoming events
python main.py list
```

First run: browser opens → sign in → allow Calendar access → `token.json` is saved (reuse later).

## Files

| File | Purpose |
|------|---------|
| `main.py` | CLI entry |
| `calendar_service.py` | Auth + Calendar API |
| `config.json` | Your email / timezone (do not commit) |
| `credentials.json` | OAuth client secret from Google (do not commit) |
| `token.json` | Saved login (do not commit) |

## Notes

- Reminder email is sent by **Google Calendar** (not by this script’s SMTP).
- Attendee invite email may arrive when the event is created (`sendUpdates=all`).
- The timed reminder (email method) fires ~`minutes` before event start for the calendar owner account you authorized.
- Keep this folder separate from the Office Management PHP app; it does not touch CI3 / MySQL.
