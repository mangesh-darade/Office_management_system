@echo off
REM Reminder Cron: Send Queued Emails
REM This script calls the send-queue endpoint to send pending reminder emails

curl -s "http://localhost/Office_management_system/reminders/cron/send-queue" > nul 2>&1

REM Alternative: If curl is not available, use PowerShell
REM powershell -Command "Invoke-WebRequest -Uri 'http://localhost/Office_management_system/reminders/cron/send-queue' -UseBasicParsing | Out-Null"

exit /b 0
