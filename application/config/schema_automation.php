<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Central registry for module schema bootstrap + automation hooks.
 * Add new modules in $config['schema_automation'] (config/schema_automation.php).
 */
$config['schema_automation'] = array(
    array('type' => 'helper', 'helper' => 'coaching', 'function' => 'coaching_ensure_schema', 'label' => 'Coaching'),
    array('type' => 'helper', 'helper' => 'permission', 'function' => 'seed_coaching_defaults_if_needed', 'label' => 'Coaching permissions'),
    array('type' => 'model', 'class' => 'Reminder_model', 'alias' => 'reminders', 'method' => 'ensure_schema', 'label' => 'Reminders'),
    array('type' => 'model', 'class' => 'Training_assessment_model', 'alias' => 'training_assessment', 'method' => 'ensure_schema', 'label' => 'Training assessment'),
    array('type' => 'model', 'class' => 'Api_integration_model', 'alias' => 'api_integration', 'method' => 'ensure_schema', 'label' => 'API integrations'),
    array('type' => 'model', 'class' => 'Notification_model', 'alias' => 'notifications', 'method' => 'ensure_schema', 'label' => 'Notifications'),
    array('type' => 'model', 'class' => 'Performance_model', 'alias' => 'performance', 'method' => 'ensure_schema', 'label' => 'Performance'),
    array('type' => 'model', 'class' => 'Recruitment_model', 'alias' => 'recruitment', 'method' => 'ensure_schema', 'label' => 'Recruitment'),
    array('type' => 'model', 'class' => 'Shift_model', 'alias' => 'shifts', 'method' => 'ensure_schema', 'label' => 'Shifts'),
    array('type' => 'model', 'class' => 'Leave_request_model', 'alias' => 'leave_requests', 'method' => 'ensure_schema', 'label' => 'Leave requests'),
);

/** Table name prefixes included in module-aware DB diff highlights */
$config['schema_table_prefixes'] = array(
    'coaching_',
    'training_',
    'my_works',
    'external_training',
    'reminder',
    'api_integration',
);
