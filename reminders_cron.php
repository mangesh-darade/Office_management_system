<?php
/**
 * Reminder Cron Runner (CLI)
 *
 * Generate schedule queue only. SMTP send-queue is DISABLED —
 * delivery is via Google Calendar (synced when reminders are created).
 *
 * Usage:
 *   php reminders_cron.php generate
 *   php reminders_cron.php send      (no-op; message only)
 *   php reminders_cron.php both     (generate only)
 */

date_default_timezone_set('Asia/Kolkata');

$action = isset($argv[1]) ? strtolower(trim($argv[1])) : 'generate';
$baseUrl = 'http://localhost/Office_management_system';
$results = [];

if ($action === 'generate' || $action === 'both') {
    $url = $baseUrl . '/reminders/cron/generate-today';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $results['generate'] = [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'time' => date('Y-m-d H:i:s'),
    ];
}

if ($action === 'send' || $action === 'both') {
    $results['send'] = [
        'success' => true,
        'http_code' => 0,
        'skipped' => true,
        'message' => 'SMTP send disabled — Google Calendar handles delivery on enqueue',
        'time' => date('Y-m-d H:i:s'),
    ];
}

echo "Reminder Cron Runner\n";
echo "===================\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

if (isset($results['generate'])) {
    echo "Generate Today: " . ($results['generate']['success'] ? 'SUCCESS' : 'FAILED');
    echo " (HTTP " . $results['generate']['http_code'] . ")\n";
}

if (isset($results['send'])) {
    echo "Send Queue: SKIPPED (Google Calendar delivery)\n";
    if (!empty($results['send']['message'])) {
        echo "  " . $results['send']['message'] . "\n";
    }
}

echo "\nDone.\n";
