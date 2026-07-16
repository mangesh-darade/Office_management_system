"""
Google Calendar Email Reminder CLI

Usage examples:
  python main.py create --title "Meeting" --when "2026-07-16 10:00" --email you@gmail.com
  python main.py create --title "Call client" --when "2026-07-16 15:30" --minutes 60
  python main.py list
"""

from __future__ import annotations

import argparse
import json
import os
import sys
from datetime import datetime
from zoneinfo import ZoneInfo

from calendar_service import create_reminder_event, get_calendar_service, list_upcoming_events

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CONFIG_FILE = os.path.join(BASE_DIR, "config.json")


def load_config() -> dict:
    if not os.path.exists(CONFIG_FILE):
        example = os.path.join(BASE_DIR, "config.example.json")
        raise FileNotFoundError(
            f"config.json missing. Copy {example} to config.json and set your email."
        )
    with open(CONFIG_FILE, "r", encoding="utf-8") as f:
        return json.load(f)


def parse_when(when_str: str, timezone: str) -> datetime:
    """Parse 'YYYY-MM-DD HH:MM' as timezone-aware datetime."""
    try:
        naive = datetime.strptime(when_str.strip(), "%Y-%m-%d %H:%M")
    except ValueError as exc:
        raise ValueError("Use format: YYYY-MM-DD HH:MM  e.g. 2026-07-16 10:00") from exc
    return naive.replace(tzinfo=ZoneInfo(timezone))


def cmd_create(args: argparse.Namespace) -> int:
    config = load_config()
    email = (args.email or config.get("email") or "").strip()
    if not email or email == "your-email@example.com":
        print("ERROR: Set email in config.json or pass --email")
        return 1

    timezone = config.get("timezone", "Asia/Kolkata")
    calendar_id = config.get("calendar_id", "primary")
    reminder_minutes = (
        args.minutes
        if args.minutes is not None
        else int(config.get("default_reminder_minutes", 30))
    )

    start_dt = parse_when(args.when, timezone)
    service = get_calendar_service()

    event = create_reminder_event(
        service=service,
        title=args.title,
        start_dt=start_dt,
        email=email,
        duration_minutes=args.duration,
        reminder_minutes=reminder_minutes,
        description=args.description or "",
        calendar_id=calendar_id,
        timezone=timezone,
    )

    print("Reminder created.")
    print(f"  Title     : {event.get('summary')}")
    print(f"  Start     : {event['start'].get('dateTime')}")
    print(f"  Email     : {email}")
    print(f"  Reminder  : {reminder_minutes} minutes before (email + popup)")
    print(f"  Event URL : {event.get('htmlLink')}")
    return 0


def cmd_list(args: argparse.Namespace) -> int:
    config = load_config()
    calendar_id = config.get("calendar_id", "primary")
    service = get_calendar_service()
    events = list_upcoming_events(service, calendar_id=calendar_id, max_results=args.limit)

    if not events:
        print("No upcoming events.")
        return 0

    print(f"Upcoming events ({len(events)}):")
    for item in events:
        start = item["start"].get("dateTime") or item["start"].get("date")
        print(f"  - {start}  |  {item.get('summary', '(no title)')}")
    return 0


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Create Google Calendar reminders that email a configured address."
    )
    sub = parser.add_subparsers(dest="command", required=True)

    create_p = sub.add_parser("create", help="Create reminder event")
    create_p.add_argument("--title", required=True, help="Event / reminder title")
    create_p.add_argument(
        "--when",
        required=True,
        help='Start time: "YYYY-MM-DD HH:MM" e.g. "2026-07-16 10:00"',
    )
    create_p.add_argument("--email", default=None, help="Override config.json email")
    create_p.add_argument(
        "--minutes",
        type=int,
        default=None,
        help="Reminder minutes before start (default from config)",
    )
    create_p.add_argument(
        "--duration",
        type=int,
        default=30,
        help="Event duration in minutes (default 30)",
    )
    create_p.add_argument("--description", default="", help="Optional description")

    list_p = sub.add_parser("list", help="List upcoming calendar events")
    list_p.add_argument("--limit", type=int, default=10, help="Max events to show")

    return parser


def main(argv: list[str] | None = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)

    try:
        if args.command == "create":
            return cmd_create(args)
        if args.command == "list":
            return cmd_list(args)
        parser.print_help()
        return 0
    except FileNotFoundError as exc:
        print(f"ERROR: {exc}")
        return 1
    except ValueError as exc:
        print(f"ERROR: {exc}")
        return 1
    except Exception as exc:
        print(f"ERROR: {exc}")
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
