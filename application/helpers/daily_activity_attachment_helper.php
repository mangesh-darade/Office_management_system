<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daily Activity log file attachments (images, PDF, Office, etc.).
 */

if (!function_exists('daily_activity_attachments_upload_dir')) {
    function daily_activity_attachments_upload_dir()
    {
        return 'uploads/daily_activity/';
    }
}

if (!function_exists('daily_activity_max_attachments_per_submit')) {
    function daily_activity_max_attachments_per_submit()
    {
        return 10;
    }
}

if (!function_exists('daily_activity_attachments_schema_ensure')) {
    /**
     * @param CI_DB_query_builder $db
     * @return void
     */
    function daily_activity_attachments_schema_ensure($db)
    {
        if ($db->table_exists('daily_work_log_attachments')) {
            return;
        }
        $db->query("CREATE TABLE `daily_work_log_attachments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `log_id` int(11) NOT NULL,
            `original_name` varchar(255) NOT NULL,
            `stored_name` varchar(255) NOT NULL,
            `mime_type` varchar(120) DEFAULT NULL,
            `file_size` int(11) DEFAULT NULL,
            `sort_order` int(11) NOT NULL DEFAULT 0,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_da_att_log` (`log_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
}

if (!function_exists('daily_activity_delete_attachment_file')) {
    function daily_activity_delete_attachment_file($stored_name)
    {
        $stored_name = trim((string) $stored_name);
        if ($stored_name === '' || strpos($stored_name, '..') !== false || strpos($stored_name, '/') !== false || strpos($stored_name, '\\') !== false) {
            return;
        }
        $path = FCPATH . daily_activity_attachments_upload_dir() . $stored_name;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

if (!function_exists('daily_activity_format_file_size')) {
    function daily_activity_format_file_size($bytes)
    {
        $bytes = (int) $bytes;
        if ($bytes < 1) {
            return '';
        }
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 1) . ' MB';
    }
}

if (!function_exists('daily_activity_attachment_kind')) {
    /**
     * @return string video|image|audio|document|archive|file
     */
    function daily_activity_attachment_kind($original_name, $stored_name = '')
    {
        $name = (string) $original_name;
        if ($name === '' && $stored_name !== '') {
            $name = (string) $stored_name;
        }
        $parts = explode('.', $name);
        $ext = strtolower((string) end($parts));
        if (in_array($ext, array('mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v', 'wmv', 'flv', 'mpeg', 'mpg', '3gp'), true)) {
            return 'video';
        }
        if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'), true)) {
            return 'image';
        }
        if (in_array($ext, array('mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a'), true)) {
            return 'audio';
        }
        if (in_array($ext, array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'), true)) {
            return 'document';
        }
        if (in_array($ext, array('zip', 'rar', '7z', 'tar', 'gz'), true)) {
            return 'archive';
        }
        return 'file';
    }
}

if (!function_exists('daily_activity_attachment_mime_type')) {
    function daily_activity_attachment_mime_type($filename)
    {
        $parts = explode('.', (string) $filename);
        $ext = strtolower((string) end($parts));
        $map = array(
            'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime',
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
            'webp' => 'image/webp', 'bmp' => 'image/bmp',
            'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg',
            'pdf' => 'application/pdf',
        );
        return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
    }
}

if (!function_exists('daily_activity_attachment_icon_class')) {
    function daily_activity_attachment_icon_class($kind)
    {
        $icons = array(
            'video'    => 'bi-file-play-fill',
            'image'    => 'bi-file-image-fill',
            'audio'    => 'bi-file-music-fill',
            'document' => 'bi-file-earmark-text-fill',
            'archive'  => 'bi-file-zip-fill',
            'file'     => 'bi-file-earmark-fill',
        );
        return isset($icons[$kind]) ? $icons[$kind] : $icons['file'];
    }
}

if (!function_exists('daily_activity_attachment_badge_label')) {
    function daily_activity_attachment_badge_label($kind)
    {
        $labels = array(
            'video' => 'Video', 'image' => 'Image', 'audio' => 'Audio',
            'document' => 'Document', 'archive' => 'Archive', 'file' => 'File',
        );
        return isset($labels[$kind]) ? $labels[$kind] : 'File';
    }
}

if (!function_exists('daily_activity_attachment_item_meta')) {
    /**
     * @param int    $log_id
     * @param object $att
     * @return array
     */
    function daily_activity_attachment_item_meta($log_id, $att)
    {
        $log_id = (int) $log_id;
        $name = !empty($att->original_name) ? (string) $att->original_name : (string) $att->stored_name;
        $stored = (string) $att->stored_name;
        $kind = daily_activity_attachment_kind($name, $stored);
        $size_label = '';
        if (!empty($att->file_size)) {
            $size_label = daily_activity_format_file_size((int) $att->file_size);
        }
        return array(
            'id'           => (int) $att->id,
            'log_id'       => $log_id,
            'name'         => $name,
            'stored_name'  => $stored,
            'kind'         => $kind,
            'icon'         => daily_activity_attachment_icon_class($kind),
            'label'        => daily_activity_attachment_badge_label($kind),
            'size_label'   => $size_label,
            'download_url' => site_url('daily-activity/' . $log_id . '/attachment/' . (int) $att->id . '/download'),
            'preview_url'  => site_url('daily-activity/' . $log_id . '/attachment/' . (int) $att->id . '/preview'),
            'can_preview'  => in_array($kind, array('video', 'image', 'audio'), true),
            'is_video'     => ($kind === 'video'),
        );
    }
}

if (!function_exists('daily_activity_attachments_for_log')) {
    function daily_activity_attachments_for_log($db, $log_id)
    {
        $log_id = (int) $log_id;
        if ($log_id < 1 || !$db->table_exists('daily_work_log_attachments')) {
            return array();
        }
        $rows = $db->from('daily_work_log_attachments')
            ->where('log_id', $log_id)
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get()
            ->result();
        $out = array();
        foreach ($rows as $row) {
            $out[] = daily_activity_attachment_item_meta($log_id, $row);
        }
        return $out;
    }
}

if (!function_exists('daily_activity_attachments_bulk_map')) {
    /**
     * @param int[] $log_ids
     * @return array<int, array>
     */
    function daily_activity_attachments_bulk_map($db, array $log_ids)
    {
        $map = array();
        $log_ids = array_values(array_unique(array_filter(array_map('intval', $log_ids))));
        if (empty($log_ids) || !$db->table_exists('daily_work_log_attachments')) {
            return $map;
        }
        $rows = $db->from('daily_work_log_attachments')
            ->where_in('log_id', $log_ids)
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get()
            ->result();
        foreach ($rows as $row) {
            $lid = (int) $row->log_id;
            if (!isset($map[$lid])) {
                $map[$lid] = array();
            }
            $map[$lid][] = daily_activity_attachment_item_meta($lid, $row);
        }
        return $map;
    }
}

if (!function_exists('daily_activity_attachment_find')) {
    function daily_activity_attachment_find($db, $log_id, $attachment_id)
    {
        $log_id = (int) $log_id;
        $attachment_id = (int) $attachment_id;
        if ($log_id < 1 || $attachment_id < 1 || !$db->table_exists('daily_work_log_attachments')) {
            return null;
        }
        return $db->get_where('daily_work_log_attachments', array(
            'id' => $attachment_id,
            'log_id' => $log_id,
        ))->row();
    }
}

if (!function_exists('daily_activity_attachment_max_sort')) {
    function daily_activity_attachment_max_sort($db, $log_id)
    {
        $log_id = (int) $log_id;
        if ($log_id < 1 || !$db->table_exists('daily_work_log_attachments')) {
            return 0;
        }
        $row = $db->select_max('sort_order', 'max_sort')
            ->from('daily_work_log_attachments')
            ->where('log_id', $log_id)
            ->get()
            ->row();
        return ($row && isset($row->max_sort)) ? (int) $row->max_sort : 0;
    }
}

if (!function_exists('daily_activity_attachment_insert')) {
    /**
     * @return int insert id
     */
    function daily_activity_attachment_insert($db, $log_id, $original, $stored, $file_size = 0, $sort_order = 0, $mime = null)
    {
        $db->insert('daily_work_log_attachments', array(
            'log_id' => (int) $log_id,
            'original_name' => (string) $original,
            'stored_name' => (string) $stored,
            'mime_type' => $mime !== null && $mime !== '' ? (string) $mime : null,
            'file_size' => (int) $file_size,
            'sort_order' => (int) $sort_order,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return (int) $db->insert_id();
    }
}

if (!function_exists('daily_activity_attachment_delete_row')) {
    function daily_activity_attachment_delete_row($db, $log_id, $attachment_id)
    {
        $db->where('id', (int) $attachment_id)
            ->where('log_id', (int) $log_id)
            ->delete('daily_work_log_attachments');
        return $db->affected_rows() > 0;
    }
}

if (!function_exists('daily_activity_delete_all_attachments')) {
    function daily_activity_delete_all_attachments($db, $log_id)
    {
        $log_id = (int) $log_id;
        if ($log_id < 1 || !$db->table_exists('daily_work_log_attachments')) {
            return;
        }
        $rows = $db->from('daily_work_log_attachments')->where('log_id', $log_id)->get()->result();
        foreach ($rows as $row) {
            daily_activity_delete_attachment_file($row->stored_name);
        }
        $db->where('log_id', $log_id)->delete('daily_work_log_attachments');
    }
}

if (!function_exists('daily_activity_upload_error_message')) {
    function daily_activity_upload_error_message($error_code)
    {
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        switch ((int) $error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File is too large. Server limit is upload_max_filesize=' . $upload_max
                    . ', post_max_size=' . $post_max . '.';
            case UPLOAD_ERR_PARTIAL:
                return 'Upload was interrupted. Please try again.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Server upload folder is missing. Contact your administrator.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Server could not save the file. Contact your administrator.';
            case UPLOAD_ERR_EXTENSION:
                return 'A server extension blocked this file type.';
            default:
                return 'Could not upload the attachment. Please try again.';
        }
    }
}

if (!function_exists('daily_activity_handle_uploads')) {
    /**
     * @return array|false Empty array if none; false on error
     */
    function daily_activity_handle_uploads()
    {
        $CI =& get_instance();
        $CI->load->helper('my_works_form');
        $upload_dir = daily_activity_attachments_upload_dir();
        $max_per = daily_activity_max_attachments_per_submit();
        $results = array();

        if (empty($_FILES['attachments']) || empty($_FILES['attachments']['name'])) {
            return array();
        }

        $files = $_FILES['attachments'];
        $names = $files['name'];
        if (!is_array($names)) {
            $names = array($names);
            $files = array(
                'name'     => array($files['name']),
                'type'     => array($files['type']),
                'tmp_name' => array($files['tmp_name']),
                'error'    => array($files['error']),
                'size'     => array($files['size']),
            );
        }

        $count = 0;
        foreach ($names as $name) {
            if (trim((string) $name) !== '') {
                $count++;
            }
        }
        if ($count < 1) {
            return array();
        }
        if ($count > $max_per) {
            $CI->session->set_flashdata('error', 'You can upload up to ' . $max_per . ' files at once.');
            return false;
        }

        $dir = FCPATH . $upload_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $max_kb = function_exists('my_works_upload_max_kb') ? my_works_upload_max_kb() : 102400;
        $allowed = function_exists('my_works_upload_allowed_types')
            ? my_works_upload_allowed_types()
            : 'gif|jpg|jpeg|png|webp|bmp|pdf|doc|docx|xls|xlsx|ppt|pptx|txt|zip|rar|7z|csv|mp4|webm|mov|mp3|wav';

        $config = array(
            'upload_path'   => $dir,
            'allowed_types' => $allowed,
            'max_size'      => $max_kb,
            'encrypt_name'  => true,
        );

        foreach ($names as $i => $name) {
            if (trim((string) $name) === '') {
                continue;
            }
            if ((int) $files['error'][$i] !== UPLOAD_ERR_OK) {
                $CI->session->set_flashdata('error', daily_activity_upload_error_message($files['error'][$i]) . ' (' . $name . ')');
                foreach ($results as $r) {
                    daily_activity_delete_attachment_file($r['stored']);
                }
                return false;
            }
            $_FILES['da_single_upload'] = array(
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            );
            $CI->upload->initialize($config);
            if (!$CI->upload->do_upload('da_single_upload')) {
                $err = strip_tags($CI->upload->display_errors('', ''));
                if ($err === '' || stripos($err, 'filetype') !== false) {
                    $err = 'This file type is not allowed. Supported: images, PDF, Office, video, audio, zip.';
                }
                $CI->session->set_flashdata('error', $err . ' (' . $name . ')');
                foreach ($results as $r) {
                    daily_activity_delete_attachment_file($r['stored']);
                }
                return false;
            }
            $data = $CI->upload->data();
            $path = $dir . $data['file_name'];
            $bytes = is_file($path) ? (int) filesize($path) : 0;
            $results[] = array(
                'original' => $data['orig_name'],
                'stored'   => $data['file_name'],
                'size'     => $bytes,
                'mime'     => isset($data['file_type']) ? $data['file_type'] : null,
            );
        }

        return $results;
    }
}

if (!function_exists('daily_activity_handle_image_upload')) {
    /**
     * Single image for Summernote picture button.
     *
     * @return array|false {url, stored, original} or false
     */
    function daily_activity_handle_image_upload($field = 'file')
    {
        $CI =& get_instance();
        $upload_dir = daily_activity_attachments_upload_dir();
        $dir = FCPATH . $upload_dir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (empty($_FILES[$field]) || (int) $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return false;
        }
        if ((int) $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        $max_kb = function_exists('my_works_upload_max_kb') ? my_works_upload_max_kb() : 102400;
        $CI->load->helper('my_works_form');
        $config = array(
            'upload_path'   => $dir,
            'allowed_types' => 'gif|jpg|jpeg|png|webp|bmp',
            'max_size'      => $max_kb,
            'encrypt_name'  => true,
        );
        $CI->upload->initialize($config);
        if (!$CI->upload->do_upload($field)) {
            return false;
        }
        $data = $CI->upload->data();
        return array(
            'url'      => base_url($upload_dir . $data['file_name']),
            'stored'   => $data['file_name'],
            'original' => $data['orig_name'],
        );
    }
}
