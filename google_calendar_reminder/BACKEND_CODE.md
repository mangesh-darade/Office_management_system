# Google Calendar Reminder — Backend Code

Core backend only: Google OAuth + Calendar API (email reminder).

File in project: `calendar_service.py`

---

## Dependencies

```text
google-api-python-client==2.160.0
google-auth-httplib2==0.2.0
google-auth-oauthlib==1.2.1
tzdata==2025.2
```

---

## Required files

| File | Purpose |
|------|---------|
| `credentials.json` | OAuth Desktop client from Google Cloud |
| `token.json` | Created automatically after first Google login |

---

## Full backend code

```python
"""Google Calendar API auth and helpers."""

from __future__ import annotations

import os
from datetime import datetime, timedelta
from typing import Any

from google.auth.transport.requests import Request
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import InstalledAppFlow
from googleapiclient.discovery import build

SCOPES = ["https://www.googleapis.com/auth/calendar"]

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CREDENTIALS_FILE = os.path.join(BASE_DIR, "credentials.json")
TOKEN_FILE = os.path.join(BASE_DIR, "token.json")


def get_calendar_service():
    """OAuth login and return Calendar API service."""
    if not os.path.exists(CREDENTIALS_FILE):
        raise FileNotFoundError(
            "credentials.json not found.\n"
            "1) Google Cloud Console -> APIs & Services -> Credentials\n"
            "2) Create OAuth client (Desktop app)\n"
            "3) Download JSON and save as credentials.json in this folder"
        )

    creds = None
    if os.path.exists(TOKEN_FILE):
        creds = Credentials.from_authorized_user_file(TOKEN_FILE, SCOPES)

    if not creds or not creds.valid:
        if creds and creds.expired and creds.refresh_token:
            creds.refresh(Request())
        else:
            flow = InstalledAppFlow.from_client_secrets_file(CREDENTIALS_FILE, SCOPES)
            creds = flow.run_local_server(port=0)

        with open(TOKEN_FILE, "w", encoding="utf-8") as token:
            token.write(creds.to_json())

    return build("calendar", "v3", credentials=creds)


def create_reminder_event(
    service,
    title: str,
    start_dt: datetime,
    email: str,
    duration_minutes: int = 30,
    reminder_minutes: int = 30,
    description: str = "",
    calendar_id: str = "primary",
    timezone: str = "Asia/Kolkata",
) -> dict[str, Any]:
    """
    Create a Calendar event with email reminder + invite to email.
    Google will email the invite and send reminder before start time.
    """
    end_dt = start_dt + timedelta(minutes=duration_minutes)

    event_body = {
        "summary": title,
        "description": description or f"Reminder for {email}",
        "start": {
            "dateTime": start_dt.isoformat(),
            "timeZone": timezone,
        },
        "end": {
            "dateTime": end_dt.isoformat(),
            "timeZone": timezone,
        },
        "attendees": [{"email": email}],
        "reminders": {
            "useDefault": False,
            "overrides": [
                {"method": "email", "minutes": reminder_minutes},
                {"method": "popup", "minutes": reminder_minutes},
            ],
        },
    }

    return (
        service.events()
        .insert(
            calendarId=calendar_id,
            body=event_body,
            sendUpdates="all",
        )
        .execute()
    )


def list_upcoming_events(
    service,
    calendar_id: str = "primary",
    max_results: int = 10,
) -> list[dict[str, Any]]:
    """List upcoming events from now."""
    now = datetime.utcnow().isoformat() + "Z"
    result = (
        service.events()
        .list(
            calendarId=calendar_id,
            timeMin=now,
            maxResults=max_results,
            singleEvents=True,
            orderBy="startTime",
        )
        .execute()
    )
    return result.get("items", [])
```

---

## How to call (example)

```python
from datetime import datetime
from zoneinfo import ZoneInfo

from calendar_service import get_calendar_service, create_reminder_event

service = get_calendar_service()

event = create_reminder_event(
    service=service,
    title="Client call",
    start_dt=datetime(2026, 7, 16, 10, 0, tzinfo=ZoneInfo("Asia/Kolkata")),
    email="you@gmail.com",
    reminder_minutes=30,
    description="Bring documents",
)

print(event.get("htmlLink"))
```

---

## Functions

| Function | Input | Output |
|----------|--------|--------|
| `get_calendar_service()` | `credentials.json` / `token.json` | Calendar API service |
| `create_reminder_event(...)` | title, datetime, email, minutes | Google event `dict` |
| `list_upcoming_events(...)` | calendar_id, max_results | list of events |

---

## Flow

1. `get_calendar_service()` → Google OAuth  
2. `create_reminder_event()` → event insert  
3. Google sends invite + email reminder (`minutes` before start)
