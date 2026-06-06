<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bulk attendance operations (extracted from Attendance controller).
 */

if (!function_exists('attendance_bulk_delete')) {
    /**
     * @param CI_DB_query_builder $db
     * @param array $ids
     * @return bool
     */
    function attendance_bulk_delete($db, array $ids)
    {
        $db->where_in('id', $ids);

        return $db->delete('attendance');
    }
}

if (!function_exists('attendance_bulk_mark_present')) {
    /**
     * Set default checkout for records that have check-in but no check-out.
     *
     * @param CI_DB_query_builder $db
     * @param array $ids
     * @param string $default_checkout
     * @return int
     */
    function attendance_bulk_mark_present($db, array $ids, $default_checkout = '18:00:00')
    {
        $db->where_in('id', $ids);
        $db->where('punch_in IS NOT NULL');
        $db->where('punch_in !=', '00:00:00');
        $db->where('(punch_out IS NULL OR punch_out = "00:00:00")');
        $db->update('attendance', array('punch_out' => $default_checkout));

        return $db->affected_rows();
    }
}

if (!function_exists('attendance_bulk_clear_checkout')) {
    /**
     * @param CI_DB_query_builder $db
     * @param array $ids
     * @return int
     */
    function attendance_bulk_clear_checkout($db, array $ids)
    {
        $db->where_in('id', $ids);
        $db->update('attendance', array('punch_out' => null));

        return $db->affected_rows();
    }
}

if (!function_exists('attendance_bulk_run')) {
    /**
     * @param CI_DB_query_builder $db
     * @param string $operation delete|mark_present|clear_checkout
     * @param array $ids
     * @return int|false affected rows, or false for unknown operation
     */
    function attendance_bulk_run($db, $operation, array $ids)
    {
        switch ($operation) {
            case 'delete':
                attendance_bulk_delete($db, $ids);

                return count($ids);
            case 'mark_present':
                return attendance_bulk_mark_present($db, $ids);
            case 'clear_checkout':
                return attendance_bulk_clear_checkout($db, $ids);
            default:
                return false;
        }
    }
}
