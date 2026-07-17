<?php
/**
 * Wrapper — run: php index.php spl_test_cli run
 * (from project root: c:\wamp64\www\Office_management_system)
 */
chdir(dirname(__DIR__, 2));
passthru('php index.php spl_test_cli run', $code);
exit($code);
