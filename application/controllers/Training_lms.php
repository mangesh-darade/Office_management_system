<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User: training modules, topics, assignment upload, downloads, learner hub.
 */
class Training_lms extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Training_lms_module_model', 'lms_mod');
        $this->load->model('Training_lms_topic_model', 'lms_topic');
        $this->load->model('Training_lms_assignment_model', 'lms_assign');
        $this->load->model('Training_lms_enrollment_model', 'lms_enroll');
        $this->load->library('session');
        $this->load->helper(array('url', 'form', 'permission', 'training'));

        if (!training_tl_learner_any()) {
            $this->session->set_flashdata('access_denied', 'You do not have access to Training LMS.');
            redirect('dashboard');
        }

        if ($this->lms_mod->schema_ready()) {
            $this->lms_mod->ensure_extensions();
        }
    }

    public function index()
    {
        if (!$this->lms_mod->schema_ready()) {
            $this->_schema_missing();
            return;
        }
        $uid = (int) $this->session->userdata('user_id');
        $is_mgr = $this->_is_lms_manager();
        $data['modules'] = $this->lms_mod->list_with_topic_counts_for_user($uid, true, $is_mgr);
        $data = array_merge($data, $this->_training_catalog_context($uid));
        $this->load->view('training_lms/index', $data);
    }

    /**
     * Single page: assigned modules, assessment links, uploads overview.
     */
    public function learner_hub()
    {
        if (!training_tl_show_hub_nav()) {
            show_error('Access denied.', 403);
        }
        if (!$this->lms_mod->schema_ready()) {
            $this->_schema_missing();
            return;
        }
        $uid = (int) $this->session->userdata('user_id');
        $data['modules'] = $this->lms_mod->list_with_topic_counts_for_user($uid, true, $this->_is_lms_manager());
        $data = array_merge($data, $this->_training_catalog_context($uid));
        $data['my_submissions'] = array();
        if ($this->lms_assign->schema_ready()) {
            $this->db->select('s.*, a.name AS assignment_name, t.name AS topic_name, m.title AS module_title');
            $this->db->from('assignment_submissions s');
            $this->db->join('assignments a', 'a.id = s.assignment_id', 'inner');
            $this->db->join('training_topics t', 't.id = a.topic_id', 'inner');
            $this->db->join('training_modules m', 'm.id = t.module_id', 'inner');
            $this->db->where('s.user_id', $uid);
            $this->db->order_by('s.submitted_at', 'DESC');
            $this->db->limit(15);
            $data['my_submissions'] = $this->db->get()->result();
        }
        $data['assessment_assignments'] = array();
        $this->load->model('Training_assessment_model', 'ta');
        if ($this->ta->schema_ready()) {
            $data['assessment_assignments'] = $this->ta->list_assignments_for_user_detailed($uid);
            $this->load->helper('training');
            foreach ($data['assessment_assignments'] as $row) {
                if (!empty($row->completed_at)) {
                    $row->result_url = training_assessment_signed_result_url($row->access_token, $row);
                } else {
                    $row->result_url = '';
                }
            }
        }
        $this->load->view('training_lms/learner_hub', $data);
    }

    public function module($id)
    {
        if (!$this->lms_mod->schema_ready()) {
            $this->_schema_missing();
            return;
        }
        $m = $this->lms_mod->get((int) $id);
        if (!$m || $m->status !== 'active') {
            show_404();
        }
        if (!$this->_learner_can_access_module((int) $id)) {
            show_error('You are not enrolled in this module.', 403);
        }
        $uid = (int) $this->session->userdata('user_id');
        $data['module'] = $m;
        $data['topics'] = $this->lms_topic->list_by_module_with_details((int) $id);
        foreach ($data['topics'] as $row) {
            $row->learner_topic_done = ($uid > 0 && $this->lms_topic->completions_schema_ready())
                ? $this->lms_topic->user_completed_topic($uid, (int) $row->id)
                : false;
        }
        $data = array_merge($data, $this->_training_catalog_context($uid));
        $this->load->view('training_lms/module', $data);
    }

    public function topic($id)
    {
        if (!$this->lms_mod->schema_ready()) {
            $this->_schema_missing();
            return;
        }
        $t = $this->lms_topic->get_with_module((int) $id);
        if (!$t || $t->module_status !== 'active') {
            show_404();
        }
        if (!$this->_learner_can_access_module((int) $t->module_id)) {
            show_error('You are not enrolled in this module.', 403);
        }
        $uid = (int) $this->session->userdata('user_id');
        if (!empty($t->prerequisite_topic_id) && (int) $t->prerequisite_topic_id > 0 && $this->lms_topic->completions_schema_ready()) {
            if (!$this->lms_topic->user_completed_topic($uid, (int) $t->prerequisite_topic_id)) {
                $pt = $this->lms_topic->get((int) $t->prerequisite_topic_id);
                $pn = $pt ? $pt->name : 'the required topic';
                $this->session->set_flashdata('error', 'Complete the prerequisite topic first: ' . $pn);
                redirect('training/topic/' . (int) $t->prerequisite_topic_id);
                return;
            }
        }
        $data['topic'] = $t;
        $data['completions_enabled'] = $this->lms_topic->completions_schema_ready();
        $data['topic_completed'] = $data['completions_enabled'] ? $this->lms_topic->user_completed_topic($uid, (int) $id) : false;
        $data['assignment'] = null;
        $data['my_submissions'] = array();
        $data['submission_quota'] = null;
        if ((int) $t->has_assignment === 1 && $this->lms_assign->schema_ready()) {
            $data['assignment'] = $this->lms_assign->get_by_topic((int) $id);
            if ($data['assignment']) {
                $data['my_submissions'] = $this->lms_assign->list_submissions_for_user_assignment(
                    (int) $data['assignment']->id,
                    $uid
                );
                $maxSub = isset($data['assignment']->max_submissions) ? (int) $data['assignment']->max_submissions : 0;
                $used = $this->lms_assign->count_user_submissions_for_assignment((int) $data['assignment']->id, $uid);
                $data['submission_quota'] = array('used' => $used, 'max' => $maxSub);
            }
        }
        $data['assessment_row'] = null;
        $data['my_assessment_assignment'] = null;
        $atbl = training_physical_assessments_table();
        if ($atbl !== '' && (int) $t->has_assessment === 1 && (int) $t->assessment_id > 0) {
            $data['assessment_row'] = $this->db->where('id', (int) $t->assessment_id)->get($atbl)->row();
            $this->load->model('Training_assessment_model', 'ta');
            if ($this->ta->schema_ready()) {
                $data['my_assessment_assignment'] = $this->ta->get_user_assignment_for_assessment(
                    (int) $t->assessment_id,
                    $uid
                );
                if ($data['my_assessment_assignment']) {
                    $this->load->helper('training');
                    $mau = $data['my_assessment_assignment'];
                    if (!empty($mau->completed_at)) {
                        $mau->result_url = training_assessment_signed_result_url($mau->access_token, $mau);
                    } else {
                        $mau->result_url = '';
                    }
                }
            }
        }
        $this->load->view('training_lms/topic', $data);
    }

    public function complete_topic()
    {
        if (!$this->lms_mod->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $topic_id = (int) $this->input->post('topic_id');
        $t = $this->lms_topic->get_with_module($topic_id);
        if (!$t || $t->module_status !== 'active') {
            show_error('Invalid topic.', 400);
        }
        if (!$this->_learner_can_access_module((int) $t->module_id)) {
            show_error('Access denied.', 403);
        }
        $uid = (int) $this->session->userdata('user_id');
        if ($this->lms_topic->mark_topic_completed($uid, $topic_id)) {
            $this->session->set_flashdata('success', 'Topic marked as complete.');
        } else {
            $this->session->set_flashdata('error', 'Could not save completion. Ensure the database is up to date (open LMS admin once or run the latest LMS SQL).');
        }
        redirect('training/topic/' . $topic_id);
    }

    public function submit_assignment()
    {
        if (!$this->lms_mod->schema_ready() || !$this->lms_assign->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $topic_id = (int) $this->input->post('topic_id');
        $t = $this->lms_topic->get_with_module($topic_id);
        if (!$t || (int) $t->has_assignment !== 1 || $t->module_status !== 'active') {
            show_error('Invalid topic.', 400);
        }
        if (!$this->_learner_can_access_module((int) $t->module_id)) {
            show_error('Access denied.', 403);
        }
        $assign = $this->lms_assign->get_by_topic($topic_id);
        if (!$assign) {
            $this->session->set_flashdata('error', 'No assignment is configured for this topic yet.');
            redirect('training/topic/' . $topic_id);
            return;
        }
        $uid = (int) $this->session->userdata('user_id');
        $maxSub = isset($assign->max_submissions) ? (int) $assign->max_submissions : 0;
        if ($maxSub > 0) {
            $cnt = $this->lms_assign->count_user_submissions_for_assignment((int) $assign->id, $uid);
            if ($cnt >= $maxSub) {
                $this->session->set_flashdata('error', 'You have reached the maximum number of submissions for this assignment.');
                redirect('training/topic/' . $topic_id);
                return;
            }
        }
        if (empty($_FILES['userfile']['name']) || !isset($_FILES['userfile']['tmp_name'])) {
            $this->session->set_flashdata('error', 'Please choose a file to upload.');
            redirect('training/topic/' . $topic_id);
            return;
        }
        $upload = $this->_do_upload($assign->id, $uid);
        if (!empty($upload['error'])) {
            $this->session->set_flashdata('error', $upload['error']);
            redirect('training/topic/' . $topic_id);
            return;
        }
        $now = date('Y-m-d H:i:s');
        $this->lms_assign->insert_submission(array(
            'assignment_id' => (int) $assign->id,
            'user_id' => $uid,
            'stored_filename' => $upload['stored'],
            'original_filename' => $upload['orig'],
            'file_ext' => $upload['ext'],
            'file_size' => (int) $upload['size'],
            'mime_type' => $upload['mime'],
            'status' => 'submitted',
            'score' => null,
            'feedback' => null,
            'submitted_at' => $now,
            'assessed_at' => null,
            'assessed_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $this->session->set_flashdata('success', 'Your file was submitted successfully.');
        redirect('training/topic/' . $topic_id);
    }

    public function download($submission_id)
    {
        if (!$this->lms_assign->schema_ready()) {
            show_404();
        }
        $sub = $this->lms_assign->get_submission((int) $submission_id);
        if (!$sub) {
            show_404();
        }
        $uid = (int) $this->session->userdata('user_id');
        if ((int) $sub->user_id !== $uid && !$this->_is_lms_manager()) {
            show_error('Access denied.', 403);
        }
        $path = FCPATH . 'uploads/lms_assignments/' . $sub->stored_filename;
        if (!is_file($path)) {
            show_error('File not found.', 404);
        }
        $this->load->helper('download');
        force_download($sub->original_filename, file_get_contents($path));
    }

    public function my_submissions()
    {
        if (!training_tl_show_assignment_nav()) {
            show_error('Access denied.', 403);
        }
        if (!$this->lms_assign->schema_ready()) {
            $this->_schema_missing();
            return;
        }
        $uid = (int) $this->session->userdata('user_id');
        $this->db->select('s.*, a.name AS assignment_name, t.name AS topic_name, m.title AS module_title');
        $this->db->from('assignment_submissions s');
        $this->db->join('assignments a', 'a.id = s.assignment_id', 'inner');
        $this->db->join('training_topics t', 't.id = a.topic_id', 'inner');
        $this->db->join('training_modules m', 'm.id = t.module_id', 'inner');
        $this->db->where('s.user_id', $uid);
        $this->db->order_by('s.submitted_at', 'DESC');
        $data['rows'] = $this->db->get()->result();
        $this->load->view('training_lms/my_submissions', $data);
    }

    private function _learner_can_access_module($module_id)
    {
        if ($this->_is_lms_manager()) {
            return true;
        }
        if (!$this->lms_enroll->schema_ready()) {
            return true;
        }
        if (!$this->lms_enroll->is_enrollment_gating_active()) {
            return true;
        }
        $uid = (int) $this->session->userdata('user_id');
        return $this->lms_enroll->user_enrolled_in_module($uid, (int) $module_id);
    }

    /**
     * @param int $uid
     * @return array
     */
    private function _training_catalog_context($uid)
    {
        $is_mgr = $this->_is_lms_manager();
        $gating = $this->lms_enroll->schema_ready() && $this->lms_enroll->is_enrollment_gating_active();
        return array(
            'is_lms_manager' => $is_mgr,
            'enrollment_gating_active' => $gating,
            'learner_waiting_enrollment' => !$is_mgr && $gating && !$this->lms_enroll->user_has_any_enrollment((int) $uid),
        );
    }

    private function _do_upload($assignment_id, $user_id)
    {
        $out = array('error' => '', 'stored' => '', 'orig' => '', 'ext' => '', 'size' => 0, 'mime' => '');
        // Whitelist extensions (documents, images, archives, common media) — no scripts / server-side types.
        $allowed = array(
            'pdf',
            'doc', 'docx', 'dot', 'dotx', 'rtf', 'odt', 'txt', 'csv',
            'xls', 'xlsx', 'xlsm', 'ods',
            'ppt', 'pptx', 'pptm', 'odp',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff', 'svg', 'heic',
            'zip', 'rar', '7z',
            'mp4', 'mov', 'webm', 'mkv', 'mp3', 'wav', 'm4a',
        );
        $max = 52428800;
        // When PHP reports a concrete MIME, it must look compatible with the extension (blocks renamed executables).
        // application/octet-stream and empty MIME are ignored — fixes Windows/WAMP mis-detection of valid PDFs/Office files.
        $mimeMap = array(
            'pdf' => array('application/pdf', 'application/x-pdf'),
            'doc' => array('application/msword', 'application/CDFV2'),
            'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'),
            'dot' => array('application/msword'),
            'dotx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.template', 'application/zip'),
            'rtf' => array('application/rtf', 'text/rtf'),
            'odt' => array('application/vnd.oasis.opendocument.text', 'application/zip'),
            'txt' => array('text/plain', 'text/csv'),
            'csv' => array('text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'),
            'xls' => array('application/vnd.ms-excel'),
            'xlsx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'),
            'xlsm' => array('application/vnd.ms-excel.sheet.macroEnabled.12', 'application/zip'),
            'ods' => array('application/vnd.oasis.opendocument.spreadsheet', 'application/zip'),
            'ppt' => array('application/vnd.ms-powerpoint'),
            'pptx' => array('application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'),
            'pptm' => array('application/vnd.ms-powerpoint.presentation.macroEnabled.12', 'application/zip'),
            'odp' => array('application/vnd.oasis.opendocument.presentation', 'application/zip'),
            'jpg' => array('image/jpeg'),
            'jpeg' => array('image/jpeg'),
            'png' => array('image/png'),
            'gif' => array('image/gif'),
            'webp' => array('image/webp'),
            'bmp' => array('image/bmp', 'image/x-ms-bmp'),
            'tif' => array('image/tiff'),
            'tiff' => array('image/tiff'),
            'svg' => array('image/svg+xml'),
            'heic' => array('image/heic', 'image/heif'),
            'zip' => array('application/zip', 'application/x-zip-compressed'),
            'rar' => array('application/vnd.rar', 'application/x-rar-compressed'),
            '7z' => array('application/x-7z-compressed'),
            'mp4' => array('video/mp4'),
            'mov' => array('video/quicktime'),
            'webm' => array('video/webm'),
            'mkv' => array('video/x-matroska'),
            'mp3' => array('audio/mpeg', 'audio/mp3'),
            'wav' => array('audio/wav', 'audio/x-wav'),
            'm4a' => array('audio/mp4', 'audio/x-m4a'),
        );
        $mimeBlocked = array(
            'application/x-httpd-php', 'application/x-php', 'text/x-php',
            'application/x-msdownload', 'application/x-msdos-program', 'application/x-executable',
            'application/x-sh', 'application/x-csh', 'text/x-shellscript',
        );

        $name = isset($_FILES['userfile']['name']) ? $_FILES['userfile']['name'] : '';
        $tmp = isset($_FILES['userfile']['tmp_name']) ? $_FILES['userfile']['tmp_name'] : '';
        $err = isset($_FILES['userfile']['error']) ? (int) $_FILES['userfile']['error'] : UPLOAD_ERR_NO_FILE;
        $size = isset($_FILES['userfile']['size']) ? (int) $_FILES['userfile']['size'] : 0;

        if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
            $out['error'] = 'Upload failed.';
            return $out;
        }
        if ($size > $max) {
            $out['error'] = 'File too large (max 50 MB).';
            return $out;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, $allowed, true)) {
            $out['error'] = 'This file type is not allowed. Use documents (PDF, Word, Excel, PowerPoint), images, zip, or common audio/video.';
            return $out;
        }

        $dir = FCPATH . 'uploads/lms_assignments/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $safe = 'a' . (int) $assignment_id . '_u' . (int) $user_id . '_' . uniqid('', true) . '.' . $ext;
        $dest = $dir . $safe;
        if (!@move_uploaded_file($tmp, $dest)) {
            $out['error'] = 'Could not save file.';
            return $out;
        }

        $mime = '';
        if (function_exists('mime_content_type')) {
            $mime = (string) @mime_content_type($dest);
        }
        $mimeNorm = strtolower(trim($mime));
        foreach ($mimeBlocked as $bad) {
            if ($mimeNorm !== '' && strpos($mimeNorm, strtolower($bad)) !== false) {
                @unlink($dest);
                $out['error'] = 'This file type is not allowed for security reasons.';
                return $out;
            }
        }
        if ($mimeNorm !== '' && $mimeNorm !== 'application/octet-stream' && isset($mimeMap[$ext])) {
            $okMime = false;
            foreach ($mimeMap[$ext] as $expect) {
                if (strcasecmp($mimeNorm, strtolower(trim($expect))) === 0) {
                    $okMime = true;
                    break;
                }
            }
            if (!$okMime) {
                @unlink($dest);
                $out['error'] = 'File content does not match the file extension. Try re-saving or use a different format.';
                return $out;
            }
        }
        $out['stored'] = $safe;
        $out['orig'] = $name;
        $out['ext'] = $ext;
        $out['size'] = $size;
        $out['mime'] = $mime ? $mime : '';
        return $out;
    }

    private function _is_lms_manager()
    {
        if ((int) $this->session->userdata('role_id') === 1) {
            return true;
        }
        if (!function_exists('has_module_access')) {
            return false;
        }
        return has_module_access('training_lms_manage') || has_module_access('training_screen_lms_admin');
    }

    private function _schema_missing()
    {
        $this->load->view('partials/header', array('title' => 'Module — Training'));
        echo '<div class="container py-5"><div class="alert alert-warning">Training LMS tables are not installed. Run <code>database/training_lms_module.sql</code>.</div></div>';
        $this->load->view('partials/footer');
    }
}
