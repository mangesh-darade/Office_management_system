# Reminder Cron: Generate Today's Schedule (PowerShell)
# Use this if curl is not available on your system

$url = "http://localhost/Office_management_system/reminders/cron/generate-today"

try {
    $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 30 -ErrorAction Stop
    Write-Host "SUCCESS: Generate Today - HTTP $($response.StatusCode)" -ForegroundColor Green
    exit 0
} catch {
    Write-Host "ERROR: Generate Today - $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
