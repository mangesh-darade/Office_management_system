@echo off
REM Test script to verify reminder cron setup
echo ========================================
echo Testing Reminder Cron Setup
echo ========================================
echo.

echo [1/3] Testing Generate Today endpoint...
curl -s "http://localhost/Office_management_system/reminders/cron/generate-today"
if %errorlevel% equ 0 (
    echo [OK] Generate endpoint is accessible
) else (
    echo [ERROR] Cannot access generate endpoint. Make sure WAMP is running!
    pause
    exit /b 1
)
echo.

echo [2/3] Testing Send Queue endpoint...
curl -s "http://localhost/Office_management_system/reminders/cron/send-queue"
if %errorlevel% equ 0 (
    echo [OK] Send queue endpoint is accessible
) else (
    echo [ERROR] Cannot access send queue endpoint. Make sure WAMP is running!
    pause
    exit /b 1
)
echo.

echo [3/3] Testing batch files...
call reminders_cron_generate.bat
if %errorlevel% equ 0 (
    echo [OK] reminders_cron_generate.bat works
) else (
    echo [WARNING] reminders_cron_generate.bat may have issues
)
call reminders_cron_send.bat
if %errorlevel% equ 0 (
    echo [OK] reminders_cron_send.bat works
) else (
    echo [WARNING] reminders_cron_send.bat may have issues
)
echo.

echo ========================================
echo Test Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Make sure WAMP is running
echo 2. Set up Windows Task Scheduler (see REMINDERS_CRON_SETUP_WAMP.md)
echo 3. Test by creating a reminder schedule and waiting for it to trigger
echo.
pause
