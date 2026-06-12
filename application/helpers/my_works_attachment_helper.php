<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('my_works_attachments_upload_dir')) {
    function my_works_attachments_upload_dir()
    {
        return 'uploads/my_works/';
    }
}

if (!function_exists('my_works_max_attachments_per_submit')) {
    function my_works_max_attachments_per_submit()
    {
        return 10;
    }
}

if (!function_exists('my_works_sync_legacy_attachment_columns')) {
    /**
     * Keep legacy my_works.attachment_* in sync with first attachment row.
     */
    function my_works_sync_legacy_attachment_columns($db, $work_id)
    {
        $work_id = (int) $work_id;
        if ($work_id < 1 || !$db->table_exists('my_work_attachments')) {
            return;
        }
        $first = $db->from('my_work_attachments')
            ->where('work_id', $work_id)
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->limit(1)
            ->get()
            ->row();
        if ($first) {
            $db->where('id', $work_id)->update('my_works', array(
                'attachment_original' => $first->original_name,
                'attachment_stored'   => $first->stored_name,
            ));
            return;
        }
        $db->where('id', $work_id)->update('my_works', array(
            'attachment_original' => null,
            'attachment_stored'   => null,
        ));
    }
}

if (!function_exists('my_works_attachment_item_meta')) {
    /**
     * @param int    $work_id
     * @param object $att Row from my_work_attachments
     * @return array
     */
    function my_works_attachment_item_meta($work_id, $att)
    {
        $work_id = (int) $work_id;
        $name = !empty($att->original_name) ? (string) $att->original_name : (string) $att->stored_name;
        $stored = (string) $att->stored_name;
        $kind = my_works_attachment_kind($name, $stored);
        $size_label = '';
        if (!empty($att->file_size)) {
            $size_label = my_works_format_file_size((int) $att->file_size);
        }
        return array(
            'id'           => (int) $att->id,
            'work_id'      => $work_id,
            'name'         => $name,
            'stored_name'  => $stored,
            'kind'         => $kind,
            'icon'         => my_works_attachment_icon_class($kind),
            'label'        => my_works_attachment_badge_label($kind),
            'size_label'   => $size_label,
            'download_url' => site_url('my-works/' . $work_id . '/attachment/' . (int) $att->id . '/download'),
            'preview_url'  => site_url('my-works/' . $work_id . '/attachment/' . (int) $att->id . '/preview'),
            'can_preview'  => in_array($kind, array('video', 'image', 'audio'), true),
            'is_video'     => ($kind === 'video'),
        );
    }
}

if (!function_exists('my_works_attachments_for_work')) {
    function my_works_attachments_for_work($db, $work_id)
    {
        $work_id = (int) $work_id;
        if ($work_id < 1 || !$db->table_exists('my_work_attachments')) {
            return array();
        }
        $rows = $db->from('my_work_attachments')
            ->where('work_id', $work_id)
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get()
            ->result();
        $out = array();
        foreach ($rows as $row) {
            $out[] = my_works_attachment_item_meta($work_id, $row);
        }
        return $out;
    }
}

if (!function_exists('my_works_attachments_bulk_map')) {
    /**
     * @param int[] $work_ids
     * @return array<int, array> work_id => attachment meta arrays
     */
    function my_works_attachments_bulk_map($db, array $work_ids)
    {
        $map = array();
        $work_ids = array_values(array_unique(array_filter(array_map('intval', $work_ids))));
        if (empty($work_ids) || !$db->table_exists('my_work_attachments')) {
            return $map;
        }
        $db->from('my_work_attachments')
            ->where_in('work_id', $work_ids)
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC');
        $rows = $db->get()->result();
        foreach ($rows as $row) {
            $wid = (int) $row->work_id;
            if (!isset($map[$wid])) {
                $map[$wid] = array();
            }
            $map[$wid][] = my_works_attachment_item_meta($wid, $row);
        }
        return $map;
    }
}

if (!function_exists('my_works_attachments_summary')) {
    /**
     * Compact summary for list badges.
     *
     * @param array $attachments attachment meta arrays
     * @return array|null
     */
    function my_works_attachments_summary(array $attachments)
    {
        if (empty($attachments)) {
            return null;
        }
        $kinds = array();
        foreach ($attachments as $att) {
            $kinds[$att['kind']] = true;
        }
        return array(
            'count'  => count($attachments),
            'kinds'  => array_keys($kinds),
            'items'  => $attachments,
            'label'  => count($attachments) === 1
                ? $attachments[0]['label']
                : count($attachments) . ' files',
        );
    }
}

if (!function_exists('my_works_row_attachments')) {
    /**
     * Attachments for a list/detail row (bulk map or legacy fallback).
     *
     * @param object     $row
     * @param array|null $attachments_map work_id => meta arrays
     * @return array
     */
    function my_works_row_attachments($row, $attachments_map = null)
    {
        if (!$row || empty($row->id)) {
            return array();
        }
        $work_id = (int) $row->id;
        if (is_array($attachments_map) && isset($attachments_map[$work_id])) {
            return $attachments_map[$work_id];
        }
        $CI =& get_instance();
        $atts = my_works_attachments_for_work($CI->db, $work_id);
        if (!empty($atts)) {
            return $atts;
        }
        $legacy = my_works_attachment_meta($row);
        return $legacy ? array($legacy) : array();
    }
}

if (!function_exists('my_works_delete_attachment_file')) {
    function my_works_delete_attachment_file($stored_name)
    {
        $stored_name = trim((string) $stored_name);
        if ($stored_name === '') {
            return;
        }
        $path = FCPATH . my_works_attachments_upload_dir() . $stored_name;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
