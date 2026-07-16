"""Google Calendar API auth and helpers."""

from __future__ import annotations

import os
from datetime import datetime, timedelta
from typing import Any, Optional

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
