<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Change Tracker Helper
 * 
 * Automatically tracks and logs changes to database records
 */

/**
 * Track changes before update - call this before updating a record
 * 
 * @param string $table_name Database table name
 * @param int $record_id Record ID
 * @return array|false Old data array or false if not found
 */
if (!function_exists('track_changes_before')) {
    function track_changes_before($table_name, $record_id) {
        $CI =& get_instance();
        $CI->load->database();
        
        if (!$CI->db->table_exists($table_name) || !$record_id) {
            return false;
        }
        
        $old_record = $CI->db->where('id', (int)$record_id)->get($table_name)->row();
        if ($old_record) {
            return (array)$old_record;
        }
        
        return false;
    }
}

/**
 * Track and log changes after update - call this after updating a record
 * 
 * @param string $module Module name (e.g., 'employees', 'tasks')
 * @param string $table_name Database table name
 * @param int $record_id Record ID
 * @param array $old_data Old data (from track_changes_before)
 * @param array $new_data New data (what was saved)
 * @param string $description Optional description
 */
if (!function_exists('track_changes_after')) {
    function track_changes_after($module, $table_name, $record_id, $old_data, $new_data, $description = '') {
        if (!$old_data || !is_array($old_data)) {
            return;
        }
        if (is_object($new_data)) {
            $new_data = (array)$new_data;
        }
        if (!is_array($new_data)) {
            return;
        }
        
        // Load activity helper to ensure log_activity_with_changes is available
        $CI =& get_instance();
        $CI->load->helper('activity');
        
        // Remove system fields that change automatically
        $ignore_fields = array('updated_at', 'created_at', 'id');
        $filtered_old = array();
        $filtered_new = array();
        
        foreach ($old_data as $key => $value) {
            if (!in_array($key, $ignore_fields)) {
                $filtered_old[$key] = $value;
                if (isset($new_data[$key])) {
                    $filtered_new[$key] = $new_data[$key];
                }
            }
        }
        
        // Only log if there are actual changes
        if ($filtered_old !== $filtered_new) {
            log_activity_with_changes($module, 'updated', (int)$record_id, $filtered_old, $filtered_new, $description);
        }
    }
}

/**
 * Auto-log database update - tracks changes automatically
 * 
 * Usage:
 *   $old_data = track_changes_before('employees', $id);
 *   $this->db->where('id', $id)->update('employees', $new_data);
 *   track_changes_after('employees', 'employees', $id, $old_data, $new_data, 'Employee updated');
 * 
 * Or use the simpler version:
 *   auto_log_update('employees', 'employees', $id, $new_data, 'Employee updated');
 * 
 * @param string $module Module name
 * @param string $table_name Table name
 * @param int $record_id Record ID
 * @param array $new_data New data being saved
 * @param string $description Optional description
 */
if (!function_exists('auto_log_update')) {
    function auto_log_update($module, $table_name, $record_id, $new_data, $description = '') {
        // Load activity helper
        $CI =& get_instance();
        $CI->load->helper('activity');
        
        $old_data = track_changes_before($table_name, $record_id);
        if ($old_data) {
            track_changes_after($module, $table_name, $record_id, $old_data, $new_data, $description);
        }
    }
}

/**
 * Auto-log database insert
 * 
 * @param string $module Module name
 * @param string $table_name Table name
 * @param int $record_id New record ID (from insert_id)
 * @param array $new_data Data that was inserted
 * @param string $description Optional description
 */
if (!function_exists('auto_log_insert')) {
    function auto_log_insert($module, $table_name, $record_id, $new_data, $description = '') {
        // Load activity helper
        $CI =& get_instance();
        $CI->load->helper('activity');
        
        if ($record_id && $new_data) {
            log_activity_with_changes($module, 'created', (int)$record_id, null, $new_data, $description);
        }
    }
}

/**
 * Auto-log database delete
 * 
 * @param string $module Module name
 * @param string $table_name Table name
 * @param int $record_id Record ID being deleted
 * @param array $old_data Data before deletion (optional, will fetch if not provided)
 * @param string $description Optional description
 */
if (!function_exists('auto_log_delete')) {
    function auto_log_delete($module, $table_name, $record_id, $old_data = null, $description = '') {
        // Load activity helper
        $CI =& get_instance();
        $CI->load->helper('activity');
        
        if (!$old_data) {
            $old_data = track_changes_before($table_name, $record_id);
        }
        
        if ($old_data) {
            log_activity_with_changes($module, 'deleted', (int)$record_id, $old_data, null, $description);
        }
    }
}

/**
 * Get module name from table name using mapping
 * 
 * @param string $table_name Database table name
 * @return string Module name (defaults to table name if not mapped)
 */
if (!function_exists('get_module_from_table')) {
    function get_module_from_table($table_name) {
        $CI =& get_instance();
        $CI->config->load('table_module_mapping', true);
        $mapping = $CI->config->item('table_module_mapping', 'table_module_mapping');
        
        if (isset($mapping[$table_name])) {
            return $mapping[$table_name];
        }
        
        // Default: use table name as module name
        return $table_name;
    }
}

/**
 * Auto-track database update - automatically determines module from table
 * 
 * @param string $table_name Database table name
 * @param int $record_id Record ID
 * @param array $new_data New data being saved
 * @param string $description Optional description (auto-generated if not provided)
 */
if (!function_exists('auto_track_update')) {
    function auto_track_update($table_name, $record_id, $new_data, $description = '') {
        $CI =& get_instance();
        $CI->load->helper(['activity', 'change_tracker']);
        
        // Get module name from table mapping
        $module = get_module_from_table($table_name);
        
        // Auto-generate description if not provided
        if (empty($description)) {
            $record_name = '';
            if (isset($new_data['name'])) {
                $record_name = $new_data['name'];
            } elseif (isset($new_data['title'])) {
                $record_name = $new_data['title'];
            } elseif (isset($new_data['email'])) {
                $record_name = $new_data['email'];
            }
            $description = ucfirst($module) . ($record_name ? ': ' . $record_name : ' #' . $record_id) . ' updated';
        }
        
        // Track changes (auto_log_update will load activity helper)
        auto_log_update($module, $table_name, $record_id, $new_data, $description);
    }
}

/**
 * Auto-track database insert - automatically determines module from table
 * 
 * @param string $table_name Database table name
 * @param int $record_id New record ID
 * @param array $new_data Data that was inserted
 * @param string $description Optional description
 */
if (!function_exists('auto_track_insert')) {
    function auto_track_insert($table_name, $record_id, $new_data, $description = '') {
        $CI =& get_instance();
        $CI->load->helper(['activity', 'change_tracker']);
        
        // Get module name from table mapping
        $module = get_module_from_table($table_name);
        
        // Auto-generate description if not provided
        if (empty($description)) {
            $record_name = '';
            if (isset($new_data['name'])) {
                $record_name = $new_data['name'];
            } elseif (isset($new_data['title'])) {
                $record_name = $new_data['title'];
            } elseif (isset($new_data['email'])) {
                $record_name = $new_data['email'];
            }
            $description = ucfirst($module) . ($record_name ? ': ' . $record_name : '') . ' created';
        }
        
        // Track insert (auto_log_insert will load activity helper)
        auto_log_insert($module, $table_name, $record_id, $new_data, $description);
    }
}

/**
 * Auto-track database delete - automatically determines module from table
 * 
 * @param string $table_name Database table name
 * @param int $record_id Record ID being deleted
 * @param array $old_data Data before deletion (optional, will fetch if not provided)
 * @param string $description Optional description
 */
if (!function_exists('auto_track_delete')) {
    function auto_track_delete($table_name, $record_id, $old_data = null, $description = '') {
        $CI =& get_instance();
        $CI->load->helper(['activity', 'change_tracker']);
        
        // Get module name from table mapping
        $module = get_module_from_table($table_name);
        
        // Get old data if not provided
        if (!$old_data) {
            $old_data = track_changes_before($table_name, $record_id);
        }
        
        // Auto-generate description if not provided
        if (empty($description) && $old_data) {
            $record_name = '';
            if (isset($old_data['name'])) {
                $record_name = $old_data['name'];
            } elseif (isset($old_data['title'])) {
                $record_name = $old_data['title'];
            } elseif (isset($old_data['email'])) {
                $record_name = $old_data['email'];
            }
            $description = ucfirst($module) . ($record_name ? ': ' . $record_name : ' #' . $record_id) . ' deleted';
        }
        
        // Track delete (auto_log_delete will load activity helper)
        auto_log_delete($module, $table_name, $record_id, $old_data, $description);
    }
}

