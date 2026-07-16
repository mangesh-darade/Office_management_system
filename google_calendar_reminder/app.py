"""Flask UI — set Gmail + datetime → create Google Calendar email reminder."""

from __future__ import annotations

import json
import os
import re
from datetime import datetime
from zoneinfo import ZoneInfo

from flask import Flask, flash, redirect, render_template, request, url_for

from calendar_service import create_reminder_event, get_calendar_service

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CONFIG_FILE = os.path.join(BASE_DIR, "config.json")

app = Flask(__name__)
app.secret_key = "google-calendar-reminder-local-ui"

EMAIL_RE = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")


def load_config() -> dict:
    if not os.path.exists(CONFIG_FILE):
        return {
            "calendar_id": "primary",
            "default_reminder_minutes": 30,
            "timezone": "Asia/Kolkata",
            "email": "",
        }
    with open(CONFIG_FILE, "r", encoding="utf-8") as f:
        return json.load(f)


@app.route("/", methods=["GET", "POST"])
def index():
    config = load_config()
    form_defaults = {
        "email": config.get("email") if config.get("email") != "your-email@example.com" else "",
        "title": "",
        "when": "",
        "minutes": str(config.get("default_reminder_minutes", 30)),
        "description": "",
    }

    if request.method == "POST":
        email = (request.form.get("email") or "").strip()
        title = (request.form.get("title") or "").strip()
        when_raw = (request.form.get("when") or "").strip()
        description = (request.form.get("description") or "").strip()
        minutes_raw = (request.form.get("minutes") or "30").strip()

        form_defaults.update(
            {
                "email": email,
                "title": title,
                "when": when_raw,
                "minutes": minutes_raw,
                "description": description,
            }
        )

        errors = []
        if not email or not EMAIL_RE.match(email):
            errors.append("Valid Gmail / email address required.")
        if not title:
            errors.append("Title is required.")
        if not when_raw:
            errors.append("Date and time are required.")

        try:
            reminder_minutes = int(minutes_raw)
            if reminder_minutes < 0:
                raise ValueError
        except ValueError:
            errors.append("Reminder minutes must be a number (0 or more).")
            reminder_minutes = 30

        timezone = config.get("timezone", "Asia/Kolkata")
        start_dt = None
        if when_raw:
            try:
                # HTML datetime-local → 2026-07-16T10:00
                naive = datetime.strptime(when_raw, "%Y-%m-%dT%H:%M")
                start_dt = naive.replace(tzinfo=ZoneInfo(timezone))
            except ValueError:
                errors.append("Invalid date/time format.")
            except Exception as exc:
                errors.append(f"Timezone error ({timezone}): {exc}")

        if errors:
            for msg in errors:
                flash(msg, "error")
            return render_template("index.html", form=form_defaults)

        try:
            service = get_calendar_service()
            event = create_reminder_event(
                service=service,
                title=title,
                start_dt=start_dt,
                email=email,
                reminder_minutes=reminder_minutes,
                description=description,
                calendar_id=config.get("calendar_id", "primary"),
                timezone=timezone,
            )
            flash(
                f"Reminder set for {email} at {when_raw.replace('T', ' ')} "
                f"(email reminder {reminder_minutes} min before).",
                "success",
            )
            if event.get("htmlLink"):
                flash(f"Calendar link: {event['htmlLink']}", "info")
            return redirect(url_for("index"))
        except FileNotFoundError as exc:
            flash(str(exc), "error")
        except Exception as exc:
            flash(f"Failed to create reminder: {exc}", "error")

        return render_template("index.html", form=form_defaults)

    return render_template("index.html", form=form_defaults)


if __name__ == "__main__":
    print("Open UI: http://127.0.0.1:5055")
    app.run(host="127.0.0.1", port=5055, debug=False)
