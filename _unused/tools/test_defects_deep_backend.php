#!/usr/bin/env php
<?php
/**
 * Wrapper — deep Defects model/business test.
 * From project root: php _unused/tools/test_defects_deep_backend.php
 */
chdir(dirname(__DIR__, 2));
passthru('php index.php cli_defects_deep_test run', $code);
exit($code);
