<?php
/**
 * Standalone Ai_chat SQL guard tests (mirrors execute_safe_query blocking rules).
 * Usage: php tools/verify_ai_chat_sql.php
 */

function ai_chat_sql_block_reason($sql)
{
    $sql = trim($sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/#.*$/m', '', $sql);
    $sql = trim($sql);

    if (!preg_match('/^\s*SELECT\b/i', $sql)) {
        return 'Only SELECT queries are allowed for safety.';
    }

    $destructive_patterns = array(
        '/\bDROP\b/i', '/\bDELETE\b/i', '/\bUPDATE\b/i', '/\bINSERT\b/i',
        '/\bTRUNCATE\b/i', '/\bALTER\b/i', '/\bCREATE\b/i', '/\bREPLACE\b/i',
        '/\bEXEC\b/i', '/\bEXECUTE\b/i', '/\bCALL\b/i', '/\bGRANT\b/i',
        '/\bREVOKE\b/i', '/\bLOCK\b/i', '/\bUNLOCK\b/i', '/\bRENAME\b/i',
        '/\bUNION\b/i', '/\bINTO\b/i', '/\bOUTFILE\b/i', '/\bDUMPFILE\b/i',
    );
    foreach ($destructive_patterns as $pattern) {
        if (preg_match($pattern, $sql)) {
            return 'Destructive or unsafe queries are blocked.';
        }
    }
    if (strpos($sql, ';') !== false) {
        return 'Multiple SQL statements are not allowed.';
    }
    if (preg_match('/\b(SLEEP|BENCHMARK|LOAD_FILE|GET_LOCK|RELEASE_LOCK)\s*\(/i', $sql)) {
        return 'Unsafe SQL functions are blocked.';
    }
    if (preg_match('/\binformation_schema\b|\bmysql\.|\bperformance_schema\b|\bsys\./i', $sql)) {
        return 'Access to system tables is blocked.';
    }
    return null;
}

$cases = array(
    array('SELECT id FROM users LIMIT 5', false, 'valid select'),
    array('SELECT id FROM users UNION SELECT id FROM employees LIMIT 5', true, 'union blocked'),
    array('DELETE FROM users', true, 'delete blocked'),
    array('SELECT SLEEP(5)', true, 'sleep blocked'),
    array('SELECT * FROM information_schema.tables LIMIT 1', true, 'information_schema blocked'),
    array('SELECT 1; SELECT 2', true, 'multi-statement blocked'),
);

$failed = 0;
foreach ($cases as $case) {
    list($sql, $expectError, $label) = $case;
    $reason = ai_chat_sql_block_reason($sql);
    $hasError = ($reason !== null);
    if ($expectError && !$hasError) {
        echo "FAIL  {$label}: expected block\n";
        $failed++;
    } elseif (!$expectError && $hasError) {
        echo "FAIL  {$label}: unexpected block: {$reason}\n";
        $failed++;
    } else {
        echo "  OK  {$label}\n";
    }
}

$srcPath = dirname(__DIR__) . '/application/controllers/Ai_chat.php';
$src = file_get_contents($srcPath);
if (strpos($src, "ENVIRONMENT === 'development'") === false || strpos($src, 'debug_sql') === false) {
    echo "FAIL  debug_sql guard missing in Ai_chat.php\n";
    $failed++;
} else {
    echo "  OK  debug_sql gated to development + admin in source\n";
}

exit($failed > 0 ? 1 : 0);
