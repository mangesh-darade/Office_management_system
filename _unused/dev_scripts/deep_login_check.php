<?php
/**
 * Deep logged-in module smoke test (CLI only).
 * Usage: set TEST_LOGIN and TEST_PASS env vars, then:
 *   php _unused/dev_scripts/deep_login_check.php
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$base = 'http://localhost/Office_management_system/';
$loginId = getenv('TEST_LOGIN');
$password = getenv('TEST_PASS');
if ($loginId === false || $loginId === '' || $password === false || $password === '') {
    fwrite(STDERR, "Set TEST_LOGIN and TEST_PASS environment variables.\n");
    exit(1);
}

$cookieFile = tempnam(sys_get_temp_dir(), 'oms_ck_');

$routes = array(
    'dashboard', 'my-works', 'spl/dashboard', 'daily-activity', 'daily-activity/list',
    'clients', 'employees', 'chats/app', 'recruitment', 'recruitment/candidates',
    'performance', 'performance/self-assess', 'coaching', 'coaching-clients',
    'coaching-coaches', 'coaching-sessions', 'coaching-goals', 'coaching-leads',
    'coaching-billing', 'coaching-reports', 'coaching-whatsapp-crm', 'coaching-resources',
    'coaching-admin', 'training-assessment', 'training-assessment/import',
    'training-assessment/report', 'training-assessment/submissions', 'training/import',
    'training/my-training', 'training', 'external-training', 'training-lms-admin',
    'training-lms-admin/assignment-submissions', 'users', 'roles', 'assets-mgmt',
    'attendance', 'attendance/create', 'shifts', 'departments', 'designations',
    'payroll/payslips', 'payroll/structures', 'payroll/generate', 'reports/payroll',
    'expenses', 'expenses/pending', 'expenses/categories', 'expenses/report',
    'leave/apply', 'leave/my', 'leave/team', 'leave/calendar',
    'projects', 'projects/dashboard', 'tasks/my-dashboard', 'projects/matrix',
    'requirements', 'tasks/board', 'timesheets', 'timesheets/report', 'timesheets/analytics',
    'releases', 'defects', 'ai_chat', 'announcements', 'notifications',
    'mail', 'sendgrid', 'whatsapp', 'analytics', 'reports', 'reports/requirements',
    'reports/tasks-assignment', 'reports/projects-status', 'reports/leaves',
    'reports/attendance', 'reports/attendance/employee', 'reports/daily-activity',
    'reports/expenses', 'reports/performance', 'reports/hr', 'settings',
    'settings/holidays', 'settings/attendance-manage', 'permissions', 'activity',
    'guide', 'profile', 'approvals', 'reminders/dashboard', 'meetings',
    'subscription-builder', 'eba-platform', 'elintom-proposals', 'db',
    'api-integrations', 'system-settings', 'superadmin', 'calls', 'lead-mapping',
    'office-meals', 'meals', 'daily-activity/export',
);

function oms_http($url, $cookieFile, $post = null, $follow = true)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'OMS-DeepCheck/1.0',
    ));
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err = curl_error($ch);
    curl_close($ch);
    return array(
        'body' => ($body === false) ? '' : $body,
        'code' => isset($info['http_code']) ? (int) $info['http_code'] : 0,
        'url' => isset($info['url']) ? $info['url'] : $url,
        'error' => $err,
    );
}

function oms_has_php_error($body)
{
    $patterns = array(
        'Fatal error',
        'Parse error',
        'Uncaught Error',
        'Uncaught Exception',
        'Call to undefined function',
        'Severity: error',
        'A PHP Error was encountered',
    );
    foreach ($patterns as $p) {
        if (stripos($body, $p) !== false) {
            return $p;
        }
    }
    return '';
}

function oms_snippet($body, $needle)
{
    $pos = stripos($body, $needle);
    if ($pos === false) {
        return '';
    }
    $start = max(0, $pos - 40);
    return trim(preg_replace('/\s+/', ' ', substr($body, $start, 160)));
}

// Warm session
oms_http(rtrim($base, '/') . '/auth/login', $cookieFile);

$loginResp = oms_http(rtrim($base, '/') . '/auth/login', $cookieFile, array(
    'login' => $loginId,
    'password' => $password,
));

$loginBody = $loginResp['body'];
$loginUrl = $loginResp['url'];

if (stripos($loginUrl, 'verify-2fa') !== false || stripos($loginUrl, 'verify_2fa') !== false) {
    fwrite(STDERR, "LOGIN_BLOCKED: 2FA required — cannot automate further.\n");
    exit(2);
}

$dash = oms_http(rtrim($base, '/') . '/dashboard', $cookieFile);
$loggedIn = (
    stripos($dash['body'], 'Sign In') === false
    && stripos($dash['body'], 'loginPassword') === false
    && stripos($dash['body'], 'Welcome Back') === false
    && $dash['code'] === 200
);

if (!$loggedIn) {
    fwrite(STDERR, "LOGIN_FAILED: still on login or dashboard unreachable (HTTP {$dash['code']}).\n");
    if (stripos($loginBody, 'Invalid credentials') !== false) {
        fwrite(STDERR, "Reason: Invalid credentials\n");
    }
    exit(3);
}

echo "LOGIN_OK\n";
echo str_repeat('-', 72) . "\n";
printf("%-42s %6s %s\n", 'ROUTE', 'HTTP', 'STATUS');
echo str_repeat('-', 72) . "\n";

$ok = 0;
$warn = 0;
$fail = 0;
$results = array();

foreach ($routes as $route) {
    $url = rtrim($base, '/') . '/' . ltrim($route, '/');
    $resp = oms_http($url, $cookieFile);
    $code = $resp['code'];
    $body = $resp['body'];
    $finalUrl = $resp['url'];

    $phpErr = oms_has_php_error($body);
    $onLogin = (stripos($finalUrl, 'auth/login') !== false || stripos($body, 'loginPassword') !== false);
    $forbidden = ($code === 403);
    $notFound = ($code === 404);
    $serverErr = ($code >= 500);

    if ($phpErr !== '' || $serverErr) {
        $status = 'FAIL';
        $detail = $phpErr !== '' ? $phpErr : "HTTP $code";
        $fail++;
    } elseif ($onLogin) {
        $status = 'AUTH?';
        $detail = 'redirected login';
        $warn++;
    } elseif ($forbidden) {
        $status = '403';
        $detail = 'forbidden';
        $warn++;
    } elseif ($notFound) {
        $status = '404';
        $detail = 'not found';
        $warn++;
    } elseif ($code >= 200 && $code < 400) {
        $status = 'OK';
        $detail = '';
        $ok++;
    } else {
        $status = 'WARN';
        $detail = "HTTP $code";
        $warn++;
    }

    $results[] = array('route' => $route, 'code' => $code, 'status' => $status, 'detail' => $detail, 'body' => $body);
    printf("%-42s %6d %-8s %s\n", $route, $code, $status, $detail);
}

echo str_repeat('-', 72) . "\n";
echo "SUMMARY: OK=$ok WARN=$warn FAIL=$fail TOTAL=" . count($routes) . "\n";

if ($fail > 0) {
    echo "\nFAIL DETAILS:\n";
    foreach ($results as $r) {
        if ($r['status'] !== 'FAIL') {
            continue;
        }
        $snip = oms_snippet($r['body'], $r['detail']);
        echo "  {$r['route']}: {$r['detail']}";
        if ($snip !== '') {
            echo " — $snip";
        }
        echo "\n";
    }
}

@unlink($cookieFile);
exit($fail > 0 ? 1 : 0);
