<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared CRUD helpers for org-structure modules (departments, designations).
 */
trait Org_structure_trait
{
    /**
     * Add deleted_at column when missing (soft-delete support).
     *
     * @param string $table
     * @return void
     */
    protected function org_ensure_deleted_at_column($table)
    {
        $this->load->helper('schema_columns');
        if ($this->db->table_exists($table) && !schema_table_has_column($this->db, $table, 'deleted_at')) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN deleted_at DATETIME NULL AFTER status");
        }
    }

    /**
     * Migrate single-column unique index to (code, deleted_at) composite for soft-delete restores.
     *
     * @param string $table
     * @param string $code_column
     * @param array  $legacy_index_names
     * @param string|null $composite_key
     * @return void
     */
    protected function org_ensure_composite_code_index($table, $code_column, array $legacy_index_names, $composite_key = null)
    {
        if (!$this->db->table_exists($table)) {
            return;
        }

        $composite_key = $composite_key ?: ('uq_' . $code_column . '_active');
        $exists = $this->db->query(
            'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ' . $this->db->escape($composite_key)
        );
        if ($exists->num_rows() > 0) {
            return;
        }

        foreach ($legacy_index_names as $index_name) {
            $check = $this->db->query(
                'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ' . $this->db->escape($index_name)
            );
            if ($check->num_rows() > 0) {
                try {
                    $this->db->query('ALTER TABLE `' . $table . '` DROP INDEX `' . $index_name . '`');
                } catch (Exception $e) {
                    // Index may already be gone on some installs.
                }
            }
        }

        try {
            $this->db->query(
                'ALTER TABLE `' . $table . '` ADD UNIQUE KEY `' . $composite_key . '` (`' . $code_column . '`, deleted_at)'
            );
        } catch (Exception $e) {
            // Non-fatal — unique constraint may already exist under another name.
        }
    }

    /**
     * Soft-delete with change-tracker audit log and flash message.
     *
     * @param string $module Route segment (e.g. departments)
     * @param string $table  DB table name
     * @param object $model  Model with soft_delete()
     * @param int    $id
     * @param string|null $log_description
     * @return void
     */
    protected function org_soft_delete_record($module, $table, $model, $id, $log_description = null)
    {
        if ($this->input->method() !== 'post') {
            show_error('Method Not Allowed', 405);
        }

        $this->load->helper('change_tracker');
        $old_data = track_changes_before($table, (int) $id);
        $model->soft_delete((int) $id);

        $description = $log_description ?: (ucfirst(rtrim($module, 's')) . ' removed');
        auto_log_delete($module, $table, (int) $id, $old_data, $description);

        $this->session->set_flashdata('success', get_notification_message($module, 'delete', 'success'));
        redirect($module);
    }

    /**
     * Restore soft-deleted row; detect active duplicate by code field.
     *
     * @param string $module       Route segment
     * @param string $table        DB table name
     * @param object $model        Model with find(), deleted_only(), restore()
     * @param int    $id
     * @param string $code_field   Column holding unique business code
     * @param string $entity_label Human label for flash messages
     * @return void
     */
    protected function org_restore_by_code($module, $table, $model, $id, $code_field, $entity_label)
    {
        $id = (int) $id;
        $row = $model->find($id);

        if (!$row) {
            foreach ($model->deleted_only() as $candidate) {
                if ((int) $candidate->id === $id) {
                    $row = $candidate;
                    break;
                }
            }
        }

        if (!$row) {
            $this->session->set_flashdata('error', $entity_label . ' not found');
            redirect($module);
            return;
        }

        if ($model->restore($id)) {
            $this->load->helper('activity');
            log_activity($module, 'restored', $id, $entity_label . ' restored');
            $this->session->set_flashdata('success', get_notification_message($module, 'restore', 'success'));
        } else {
            $this->db->from($table);
            $this->db->where($code_field, $row->{$code_field});
            $this->db->where('status', 'active');
            $this->db->where('id !=', $id);
            $conflict = $this->db->get();

            if ($conflict->num_rows() > 0) {
                $this->session->set_flashdata(
                    'error',
                    'Cannot restore: Another ' . strtolower($entity_label) . ' with code "'
                    . $row->{$code_field} . '" already exists. Please delete or modify the conflicting record first.'
                );
            } else {
                $this->session->set_flashdata('error', 'Failed to restore ' . strtolower($entity_label));
            }
        }

        redirect($module);
    }
}
