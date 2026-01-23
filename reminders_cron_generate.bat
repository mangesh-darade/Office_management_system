@echo off
REM Reminder Cron: Generate Today's Schedule
REM This script calls the generate-today endpoint to create reminders from schedules

curl -s "http://localhost/Office_management_system/reminders/cron/generate-today" > nul 2>&1

REM Alternative: If curl is not available, use PowerShell
REM powershell -Command "Invoke-WebRequest -Uri 'http://localhost/Office_management_system/reminders/cron/generate-today' -UseBasicParsing | Out-Null"

exit /b 0
