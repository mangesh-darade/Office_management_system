<?php
/**
 * Reminder Cron Runner (CLI)
 * 
 * This script can be run via Windows Task Scheduler or command line
 * to execute reminder cron jobs without needing a browser.
 * 
 * Usage:
 *   php reminders_cron.php generate
 *   php reminders_cron.php send
 *   php reminders_cron.php both
 */

// Set timezone (adjust if needed)
date_default_timezone_set('Asia/Kolkata');

// Get command line argument
$action = isset($argv[1]) ? strtolower(trim($argv[1])) : 'both';

// Base URL - adjust if your localhost path is different
$baseUrl = 'http://localhost/Office_management_system';

$results = [];

// Generate today's schedule
if ($action === 'generate' || $action === 'both') {
    $url = $baseUrl . '/reminders/cron/generate-today';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $results['generate'] = [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'time' => date('Y-m-d H:i:s')
    ];
}

// Send queued emails
if ($action === 'send' || $action === 'both') {
    $url = $baseUrl . '/reminders/cron/send-queue';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $results['send'] = [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'time' => date('Y-m-d H:i:s')
    ];
}

// Output results
echo "Reminder Cron Runner\n";
echo "===================\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

if (isset($results['generate'])) {
    echo "Generate Today: " . ($results['generate']['success'] ? 'SUCCESS' : 'FAILED');
    echo " (HTTP " . $results['generate']['http_code'] . ")\n";
}

if (isset($results['send'])) {
    echo "Send Queue: " . ($results['send']['success'] ? 'SUCCESS' : 'FAILED');
    echo " (HTTP " . $results['send']['http_code'] . ")\n";
}

echo "\nDone.\n";
