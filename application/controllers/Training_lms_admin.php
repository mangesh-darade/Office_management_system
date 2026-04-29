<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin: modules, topics, assignments, grade submissions.
 */
class Training_lms_admin extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Training_lms_module_model', 'lms_mod');
        $this->load->model('Training_lms_topic_model', 'lms_topic');
        $this->load->model('Training_lms_assignment_model', 'lms_assign');
        $this->load->model('Training_lms_enrollment_model', 'lms_enroll');
        $this->load->model('Training_office_feed_model', 'office_feed');
        $this->load->library('session');
        $this->load->helper(array('url', 'form', 'permission', 'training'));

        if (!training_lms_admin_any()) {
            $this->session->set_flashdata('access_denied', 'You do not have access to Training LMS admin.');
            redirect('dashboard');
        }
        $this->_lms_admin_route_gate();

        if ($this->lms_mod->schema_ready()) {
            $this->lms_mod->ensure_extensions();
        }
    }

    /**
     * Per-action screen permissions (sidebar: LMS admin, Assignment submissions, LMS office CSV).
     */
    private function _lms_admin_route_gate()
    {
        $m = $this->router->fetch_method();
        $catalog = array(
            'index', 'module_form', 'save_module', 'delete_module', 'topics', 'module_enrollments',
            'enrollment_save', 'enrollment_remove', 'topic_form', 'save_topic', 'delete_topic',
        );
        $submissions = array('submissions', 'submission_save', 'assignment_submissions_list', 'download');
        $office = array('office_feed', 'office_feed_import_assignments', 'office_feed_export');
        if (in_array($m, $catalog, true)) {
            if (!training_lms_admin_can_catalog()) {
                show_error('Access denied.', 403);
            }
        } elseif (in_array($m, $submissions, true)) {
            if (!training_lms_admin_can_submissions()) {
                show_error('Access denied.', 403);
            }
        } elseif (in_array($m, $office, true)) {
            if (!training_lms_admin_can_office()) {
                show_error('Access denied.', 403);
            }
        }
    }

    public function index()
    {
        if (!$this->lms_mod->schema_ready()) {
            $this->_schema_missing();
            return;
        }
        $data['modules'] = $this->lms_mod->list_with_topic_counts(false);
        $this->load->view('training_lms/admin/index', $data);
    }

    public function module_form($id = null)
    {
        if (!$this->lms_mod->schema_ready()) {
            $this->_schema_missing();
            return;
        }
        $data['row'] = null;
        if ($id !== null && $id !== '') {
            $data['row'] = $this->lms_mod->get((int) $id);
            if (!$data['row']) {
                show_404();
            }
        }
        $this->load->view('training_lms/admin/module_form', $data);
    }

    public function save_module()
    {
        if (!$this->lms_mod->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        $title = trim((string) $this->input->post('title'));
        if ($title === '') {
            $this->session->set_flashdata('error', 'Title is required.');
            redirect($id ? 'training-lms-admin/module/edit/' . $id : 'training-lms-admin/module/create');
            return;
        }
        $now = date('Y-m-d H:i:s');
        $row = array(
            'title' => $title,
            'description' => trim((string) $this->input->post('description')),
            'status' => $this->input->post('status') === 'inactive' ? 'inactive' : 'active',
            'sort_order' => (int) $this->input->post('sort_order'),
            'updated_at' => $now,
        );
        if ($id) {
            $this->lms_mod->update_row($id, $row);
            $this->session->set_flashdata('success', 'Module updated.');
            redirect('training-lms-admin/topics/' . $id);
            return;
        }
        $row['created_by'] = (int) $this->session->userdata('user_id');
        $row['created_at'] = $now;
        $newId = $this->lms_mod->insert_row($row);
        $this->session->set_flashdata('success', 'Module created. Add topics next.');
        redirect('training-lms-admin/topics/' . (int) $newId);
    }

    public function delete_module($id)
    {
        if (!$this->lms_mod->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->lms_mod->delete_row((int) $id);
        $this->session->set_flashdata('success', 'Module deleted.');
        redirect('training-lms-admin');
    }

    public function topics($module_id)
    {
        if (!$this->lms_mod->schema_ready()) {
            $this->_schema_missing();
            return;
        }
        $m = $this->lms_mod->get((int) $module_id);
        if (!$m) {
            show_404();
        }
        $data['module'] = $m;
        $data['topics'] = $this->lms_topic->list_by_module_admin((int) $module_id);
        $data['enrollment_count'] = 0;
        if ($this->lms_enroll->schema_ready()) {
            $data['enrollment_count'] = (int) $this->db->where('module_id', (int) $module_id)->count_all_results('training_enrollments');
        }
        $this->load->view('training_lms/admin/topics', $data);
    }

    public function module_enrollments($module_id)
    {
        if (!$this->lms_mod->schema_ready()) {
            $this->_schema_missing();
            return;
        }
        $m = $this->lms_mod->get((int) $module_id);
        if (!$m) {
            show_404();
        }
        $this->load->model('Employee_model');
        $data['module'] = $m;
        $data['rows'] = $this->lms_enroll->schema_ready() ? $this->lms_enroll->list_rows_for_module((int) $module_id) : array();
        $data['employees'] = $this->Employee_model->all(10000, 0, '', array());
        $data['departments'] = $this->_lms_distinct_departments();
        $this->load->view('training_lms/admin/module_enrollments', $data);
    }

    public function enrollment_save()
    {
        if (!$this->lms_mod->schema_ready() || !$this->lms_enroll->schema_ready()) {
            show_error('Enrollments require training_enrollments table (open any LMS page once to auto-create, or run SQL).', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $module_id = (int) $this->input->post('module_id');
        $m = $this->lms_mod->get($module_id);
        if (!$m) {
            show_404();
        }
        $uid = (int) $this->session->userdata('user_id');
        $mode = (string) $this->input->post('enroll_mode');
        if ($mode === 'bulk_department') {
            $dept = trim((string) $this->input->post('department'));
            if ($dept === '') {
                $this->session->set_flashdata('error', 'Select a department.');
                redirect('training-lms-admin/enrollments/' . $module_id);
                return;
            }
            $userIds = $this->_lms_user_ids_for_department($dept);
            $this->lms_enroll->bulk_enroll_users($module_id, $userIds, $uid);
            $this->session->set_flashdata('success', 'Enrolled ' . count($userIds) . ' user account(s) from department.');
            redirect('training-lms-admin/enrollments/' . $module_id);
            return;
        }
        $empUserId = (int) $this->input->post('user_id');
        if ($empUserId < 1) {
            $this->session->set_flashdata('error', 'Select an employee.');
            redirect('training-lms-admin/enrollments/' . $module_id);
            return;
        }
        $this->lms_enroll->enroll($empUserId, $module_id, $uid);
        $this->session->set_flashdata('success', 'User enrolled.');
        redirect('training-lms-admin/enrollments/' . $module_id);
    }

    public function enrollment_remove()
    {
        if (!$this->lms_enroll->schema_ready()) {
            show_error('Schema missing.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $module_id = (int) $this->input->post('module_id');
        $user_id = (int) $this->input->post('user_id');
        $m = $this->lms_mod->get($module_id);
        if (!$m || $user_id < 1) {
            show_404();
        }
        $this->lms_enroll->delete_enrollment($user_id, $module_id);
        $this->session->set_flashdata('success', 'Enrollment removed.');
        redirect('training-lms-admin/enrollments/' . $module_id);
    }

    public function topic_form($module_id, $topic_id = null)
    {
        if (!$this->lms_mod->schema_ready()) {
            $this->_schema_missing();
            return;
        }
        $m = $this->lms_mod->get((int) $module_id);
        if (!$m) {
            show_404();
        }
        $data['module'] = $m;
        $data['topic'] = null;
        $data['assignment'] = null;
        $data['assessments'] = array();
        $atbl = training_physical_assessments_table();
        if ($atbl !== '') {
            $this->db->order_by('title', 'ASC');
            $data['assessments'] = $this->db->get($atbl)->result();
        }
        $data['sibling_topics'] = $this->lms_topic->list_by_module((int) $module_id);
        if ($topic_id !== null && $topic_id !== '') {
            $data['topic'] = $this->lms_topic->get((int) $topic_id);
            if (!$data['topic'] || (int) $data['topic']->module_id !== (int) $module_id) {
                show_404();
            }
            if ($this->lms_assign->schema_ready()) {
                $data['assignment'] = $this->lms_assign->get_by_topic((int) $topic_id);
            }
        }
        $this->load->view('training_lms/admin/topic_form', $data);
    }

    public function save_topic()
    {
        if (!$this->lms_mod->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $module_id = (int) $this->input->post('module_id');
        $topic_id = (int) $this->input->post('topic_id');
        $name = trim((string) $this->input->post('name'));
        if ($name === '' || $module_id < 1) {
            $this->session->set_flashdata('error', 'Topic name and module are required.');
            redirect('training-lms-admin');
            return;
        }
        $m = $this->lms_mod->get($module_id);
        if (!$m) {
            show_404();
        }

        $has_assign = $this->input->post('has_assignment') ? 1 : 0;
        $has_assess = $this->input->post('has_assessment') ? 1 : 0;
        $assessment_id = (int) $this->input->post('assessment_id');
        if ($assessment_id < 1) {
            $assessment_id = null;
        }
        if (!$has_assess) {
            $assessment_id = null;
        }
        $prereq = (int) $this->input->post('prerequisite_topic_id');
        if ($prereq < 1) {
            $prereq = null;
        }
        if ($prereq !== null) {
            $pt = $this->lms_topic->get($prereq);
            if (!$pt || (int) $pt->module_id !== $module_id || ($topic_id && (int) $prereq === (int) $topic_id)) {
                $prereq = null;
            }
        }

        $now = date('Y-m-d H:i:s');
        $row = array(
            'module_id' => $module_id,
            'prerequisite_topic_id' => $prereq,
            'name' => $name,
            'description' => trim((string) $this->input->post('description')),
            'prerequisites' => trim((string) $this->input->post('prerequisites')),
            'duration_hours' => max(0, (float) $this->input->post('duration_hours')),
            'has_assignment' => $has_assign,
            'has_assessment' => $has_assess,
            'assessment_id' => $assessment_id,
            'sort_order' => (int) $this->input->post('sort_order'),
            'updated_at' => $now,
        );

        if ($topic_id) {
            $this->lms_topic->update_row($topic_id, $row);
            $tid = $topic_id;
        } else {
            $row['created_at'] = $now;
            $tid = $this->lms_topic->insert_row($row);
        }

        if ($this->lms_assign->schema_ready()) {
            if ($has_assign) {
                $aname = trim((string) $this->input->post('assignment_name'));
                $adetails = trim((string) $this->input->post('assignment_details'));
                if ($aname === '') {
                    $aname = $name . ' — Assignment';
                }
                $existing = $this->lms_assign->get_by_topic($tid);
                $maxSub = max(0, (int) $this->input->post('max_submissions'));
                if ($existing) {
                    $this->lms_assign->update_assignment((int) $existing->id, array(
                        'name' => $aname,
                        'details' => $adetails,
                        'max_submissions' => $maxSub,
                        'updated_at' => $now,
                    ));
                } else {
                    $this->lms_assign->insert_assignment(array(
                        'topic_id' => $tid,
                        'name' => $aname,
                        'details' => $adetails,
                        'max_submissions' => $maxSub,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ));
                }
            } else {
                $this->lms_assign->delete_for_topic($tid);
            }
        }

        $this->session->set_flashdata('success', 'Topic saved.');
        redirect('training-lms-admin/topics/' . $module_id);
    }

    public function delete_topic($topic_id)
    {
        if (!$this->lms_mod->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $t = $this->lms_topic->get((int) $topic_id);
        if (!$t) {
            show_404();
        }
        $mid = (int) $t->module_id;
        $this->lms_topic->delete_row((int) $topic_id);
        $this->session->set_flashdata('success', 'Topic deleted.');
        redirect('training-lms-admin/topics/' . $mid);
    }

    public function submissions($topic_id)
    {
        if (!$this->lms_assign->schema_ready()) {
            $this->_schema_missing();
            return;
        }
        $t = $this->lms_topic->get_with_module((int) $topic_id);
        if (!$t) {
            show_404();
        }
        $assign = $this->lms_assign->get_by_topic((int) $topic_id);
        if (!$assign) {
            $this->session->set_flashdata('error', 'This topic has no assignment record.');
            redirect('training-lms-admin/topics/' . (int) $t->module_id);
            return;
        }
        $data['topic'] = $t;
        $data['assignment'] = $assign;
        $rows = $this->lms_assign->list_submissions_for_assignment((int) $assign->id);
        if (!$this->_lms_admin_can_view_all()) {
            $uid = (int) $this->session->userdata('user_id');
            $rows = array_values(array_filter($rows, function ($r) use ($uid) {
                return isset($r->user_id) && (int) $r->user_id === $uid;
            }));
        }
        $data['submissions'] = $rows;
        $data['can_review_submissions'] = $this->_lms_admin_can_view_all();
        $this->load->view('training_lms/admin/submissions', $data);
    }

    public function submission_save()
    {
        if (!$this->lms_assign->schema_ready()) {
            show_error('Schema not installed.', 500);
        }
        if (!$this->_lms_admin_can_view_all()) {
            show_error('Access denied.', 403);
        }
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $sid = (int) $this->input->post('submission_id');
        $sub = $this->lms_assign->get_submission($sid);
        if (!$sub) {
            show_404();
        }
        $status = trim((string) $this->input->post('status'));
        if (!in_array($status, array('pending', 'submitted', 'assessed'), true)) {
            $status = 'submitted';
        }
        $score = $this->input->post('score');
        $score = ($score === '' || $score === null) ? null : (float) $score;
        $now = date('Y-m-d H:i:s');
        $assign = $this->lms_assign->get((int) $sub->assignment_id);
        if (!$assign) {
            redirect('training-lms-admin');
            return;
        }
        $topic_id = (int) $assign->topic_id;
        $upd = array(
            'status' => $status,
            'score' => $score,
            'feedback' => trim((string) $this->input->post('feedback')),
            'updated_at' => $now,
        );
        if ($status === 'assessed') {
            $upd['assessed_at'] = $now;
            $upd['assessed_by'] = (int) $this->session->userdata('user_id');
        } else {
            $upd['assessed_at'] = null;
            $upd['assessed_by'] = null;
        }
        $this->lms_assign->update_submission($sid, $upd);
        $this->session->set_flashdata('success', 'Submission updated.');
        redirect('training-lms-admin/submissions/' . (int) $topic_id);
    }

    /**
     * Assignment submissions overview:
     * - Admin group, super admin (role 1), or training_lms_manage: all rows
     * - Other users (submissions screen access only): own rows
     */
    public function assignment_submissions_list()
    {
        if (!$this->lms_assign->schema_ready()) {
            $this->_schema_missing();
            return;
        }
        $uid = (int) $this->session->userdata('user_id');
        $showAll = $this->_lms_admin_can_view_all();
        $this->db->select('s.id AS submission_id, s.user_id, t.id AS topic_id, m.title AS module_name, t.name AS topic_name, asn.name AS assignment_name, u.name AS submitted_by_name, u.email AS submitted_by_email, s.submitted_at, s.original_filename AS attachment_filename, s.stored_filename, s.score AS assignment_score, grader.name AS assessed_by_name, s.status, s.feedback', false);
        $this->db->from('assignment_submissions s');
        $this->db->join('assignments asn', 'asn.id = s.assignment_id', 'inner');
        $this->db->join('training_topics t', 't.id = asn.topic_id', 'inner');
        $this->db->join('training_modules m', 'm.id = t.module_id', 'inner');
        $this->db->join('users u', 'u.id = s.user_id', 'left');
        $this->db->join('users grader', 'grader.id = s.assessed_by', 'left');
        if (!$showAll && $uid > 0) {
            $this->db->where('s.user_id', $uid);
        }
        $this->db->order_by('s.submitted_at', 'DESC');
        $this->db->order_by('s.id', 'DESC');
        $data['rows'] = $this->db->get()->result();
        $data['show_all_submissions'] = $showAll;
        $data['can_review_submissions'] = $showAll;
        $this->load->view('training_lms/admin/assignment_submissions_list', $data);
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
        if (!$this->_lms_admin_can_view_all()) {
            $uid = (int) $this->session->userdata('user_id');
            if ($uid <= 0 || !isset($sub->user_id) || (int) $sub->user_id !== $uid) {
                show_error('Access denied.', 403);
            }
        }
        $path = FCPATH . 'uploads/lms_assignments/' . $sub->stored_filename;
        if (!is_file($path)) {
            show_error('File not found.', 404);
        }
        $this->load->helper('download');
        force_download($sub->original_filename, file_get_contents($path));
    }

    private function _schema_missing()
    {
        $this->load->view('partials/header', array('title' => 'Training LMS Admin'));
        echo '<div class="container py-5"><div class="alert alert-warning">Run <code>database/training_lms_module.sql</code>.</div></div>';
        $this->load->view('partials/footer');
    }

    private function _lms_admin_can_view_all()
    {
        if ((int) $this->session->userdata('role_id') === 1) {
            return true;
        }
        if (function_exists('is_admin_group') && is_admin_group()) {
            return true;
        }
        return function_exists('has_module_access') && has_module_access('training_lms_manage');
    }

    /**
     * @return int[]
     */
    private function _lms_user_ids_for_department($department)
    {
        $department = trim((string) $department);
        if ($department === '' || !$this->db->table_exists('employees')) {
            return array();
        }
        $q = $this->db->select('user_id')
            ->from('employees')
            ->where('department', $department)
            ->where('user_id IS NOT NULL', null, false)
            ->where('user_id >', 0)
            ->get();
        $out = array();
        foreach ($q->result() as $r) {
            $out[] = (int) $r->user_id;
        }
        return array_values(array_unique($out));
    }

    /**
     * @return string[]
     */
    private function _lms_distinct_departments()
    {
        if (!$this->db->table_exists('employees')) {
            return array();
        }
        $q = $this->db->distinct()
            ->select('department')
            ->from('employees')
            ->where('department IS NOT NULL', null, false)
            ->where('department !=', '')
            ->order_by('department', 'ASC')
            ->get();
        $out = array();
        foreach ($q->result() as $r) {
            $d = trim((string) $r->department);
            if ($d !== '') {
                $out[] = $d;
            }
        }
        return array_values(array_unique($out));
    }
}
