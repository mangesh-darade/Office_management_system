# Reminder Cron: Send Queued Emails (PowerShell)
# Use this if curl is not available on your system

$url = "http://localhost/Office_management_system/reminders/cron/send-queue"

try {
    $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 60 -ErrorAction Stop
    Write-Host "SUCCESS: Send Queue - HTTP $($response.StatusCode)" -ForegroundColor Green
    exit 0
} catch {
    Write-Host "ERROR: Send Queue - $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
