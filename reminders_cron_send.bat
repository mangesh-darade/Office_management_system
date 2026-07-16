@echo off
REM Reminder Cron: SMTP send-queue DISABLED
REM Reminders are synced to Google Calendar when created (enqueue).
REM This script is kept so Task Scheduler jobs do not break; it does nothing.

echo Reminder send-queue cron is disabled. Use Google Calendar delivery.
exit /b 0
