<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Knowledge_base extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission', 'rewards'));
        $this->load->library('session');
        $this->load->model('Engagement_model', 'eng');
        require_controller_access('knowledge_base', true);
    }

    public function index()
    {
        $rows = $this->eng->list_kb(array(
            'status' => trim((string) $this->input->get('status')),
            'q' => trim((string) $this->input->get('q')),
        ));
        $this->load->view('knowledge_base/index', array('rows' => $rows, 'filters' => $this->input->get()));
    }

    public function view($id)
    {
        $item = $this->eng->get_kb((int) $id);
        if (!$item || ($item->status !== 'published' && !has_module_access('knowledge_base_edit') && !has_module_access('knowledge_base'))) {
            show_404();
        }
        $this->db->set('view_count', 'view_count + 1', false)->where('id', (int) $id)->update('kb_articles');
        $this->load->view('knowledge_base/view', array('item' => $item));
    }

    public function create()
    {
        require_module_access(array('knowledge_base', 'knowledge_base_add'), true);
        if ($this->input->method() === 'post') {
            $uid = (int) $this->session->userdata('user_id');
            $status = trim((string) $this->input->post('status')) ?: 'draft';
            $id = $this->eng->save_kb(array(
                'title' => trim((string) $this->input->post('title')),
                'summary' => trim((string) $this->input->post('summary')),
                'body' => (string) $this->input->post('body'),
                'category' => trim((string) $this->input->post('category')),
                'tags' => trim((string) $this->input->post('tags')),
                'status' => $status,
                'author_id' => $uid,
                'published_at' => ($status === 'published') ? date('Y-m-d H:i:s') : null,
            ));
            if ($status === 'published') {
                reward_engine_claim('company_blog_linkedin', array(
                    'user_id' => $uid,
                    'actor_id' => $uid,
                    'source_module' => 'knowledge_base',
                    'source_record_id' => $id,
                    'reference_label' => trim((string) $this->input->post('title')),
                ));
            }
            $this->session->set_flashdata('success', 'Article saved.');
            redirect('knowledge-base');
            return;
        }
        $this->load->view('knowledge_base/form', array('action' => 'create', 'item' => null));
    }

    public function edit($id)
    {
        require_module_access(array('knowledge_base', 'knowledge_base_edit'), true);
        $item = $this->eng->get_kb((int) $id);
        if (!$item) {
            show_404();
        }
        if ($this->input->method() === 'post') {
            $oldStatus = (string) $item->status;
            $status = trim((string) $this->input->post('status')) ?: $oldStatus;
            $publishedAt = $item->published_at;
            if ($status === 'published' && $oldStatus !== 'published') {
                $publishedAt = date('Y-m-d H:i:s');
            }
            $this->eng->save_kb(array(
                'title' => trim((string) $this->input->post('title')),
                'summary' => trim((string) $this->input->post('summary')),
                'body' => (string) $this->input->post('body'),
                'category' => trim((string) $this->input->post('category')),
                'tags' => trim((string) $this->input->post('tags')),
                'status' => $status,
                'published_at' => $publishedAt,
            ), (int) $id);
            if ($status === 'published' && $oldStatus !== 'published') {
                reward_engine_claim('company_blog_linkedin', array(
                    'user_id' => (int) $item->author_id,
                    'actor_id' => (int) $this->session->userdata('user_id'),
                    'source_module' => 'knowledge_base',
                    'source_record_id' => (int) $id,
                    'reference_label' => trim((string) $this->input->post('title')),
                ));
            }
            $this->session->set_flashdata('success', 'Article updated.');
            redirect('knowledge-base');
            return;
        }
        $this->load->view('knowledge_base/form', array('action' => 'edit', 'item' => $item));
    }
}
