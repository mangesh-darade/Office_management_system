<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Log activity - Simple version
 */
if (!function_exists('log_activity')) {
    function log_activity($module, $action, $record_id = null, $description = ''){
        $CI =& get_instance();
        $CI->load->library('Activity_logger');
        $CI->activity_logger->log($module, $action, $record_id, $description);
    }
}

/**
 * Log activity with change tracking (before/after values)
 * 
 * @param string $module Module name (e.g., 'employees', 'tasks')
 * @param string $action Action type (e.g., 'updated', 'created')
 * @param int $record_id Record ID
 * @param array $old_data Old/previous values (before update)
 * @param array $new_data New/current values (after update)
 * @param string $description Optional description
 */
if (!function_exists('log_activity_with_changes')) {
    function log_activity_with_changes($module, $action, $record_id, $old_data = null, $new_data = null, $description = ''){
        $CI =& get_instance();
        $CI->load->library('Activity_logger');
        
        // Build changes array with before/after values
        $changes = null;
        if ($old_data !== null || $new_data !== null) {
            $changes = array();
            
            // If both old and new data provided, compare and track only changed fields
            if (is_array($old_data) && is_array($new_data)) {
                $changed_fields = array();
                $all_keys = array_unique(array_merge(array_keys($old_data), array_keys($new_data)));
                
                foreach ($all_keys as $key) {
                    $old_value = isset($old_data[$key]) ? $old_data[$key] : null;
                    $new_value = isset($new_data[$key]) ? $new_data[$key] : null;
                    
                    // Only track if value actually changed
                    if ($old_value !== $new_value) {
                        $changed_fields[$key] = array(
                            'before' => $old_value,
                            'after' => $new_value
                        );
                    }
                }
                
                if (!empty($changed_fields)) {
                    $changes['fields'] = $changed_fields;
                }
            } elseif (is_array($old_data)) {
                // Only old data - record what was removed/changed
                $changes['old'] = $old_data;
            } elseif (is_array($new_data)) {
                // Only new data - record what was added
                $changes['new'] = $new_data;
            }
        }
        
        $CI->activity_logger->log($module, $action, $record_id, $description, $changes);
    }
}

/**
 * Log activity for database update - automatically tracks changes
 * 
 * @param string $module Module name
 * @param int $record_id Record ID
 * @param string $table_name Database table name
 * @param array $new_data New data being saved
 * @param string $description Optional description
 */
if (!function_exists('log_db_update')) {
    function log_db_update($module, $record_id, $table_name, $new_data, $description = ''){
        $CI =& get_instance();
        $CI->load->database();
        
        // Get old data from database
        $old_data = null;
        if ($record_id && $CI->db->table_exists($table_name)) {
            $old_record = $CI->db->where('id', (int)$record_id)->get($table_name)->row();
            if ($old_record) {
                $old_data = (array)$old_record;
            }
        }
        
        // Log with change tracking
        log_activity_with_changes($module, 'updated', (int)$record_id, $old_data, $new_data, $description);
    }
}

/**
 * Log activity for database insert
 */
if (!function_exists('log_db_insert')) {
    function log_db_insert($module, $record_id, $new_data, $description = ''){
        log_activity_with_changes($module, 'created', (int)$record_id, null, $new_data, $description);
    }
}

/**
 * Log activity for database delete
 */
if (!function_exists('log_db_delete')) {
    function log_db_delete($module, $record_id, $old_data, $description = ''){
        log_activity_with_changes($module, 'deleted', (int)$record_id, $old_data, null, $description);
    }
}
