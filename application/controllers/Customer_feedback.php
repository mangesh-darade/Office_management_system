<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_feedback extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission', 'rewards'));
        $this->load->library('session');
        $this->load->model('Engagement_model', 'eng');
        require_controller_access('customer_feedback', true);
    }

    public function index()
    {
        $rows = $this->eng->list_feedback(array());
        $this->load->view('customer_feedback/index', array('rows' => $rows));
    }

    public function create()
    {
        require_module_access(array('customer_feedback'), true);
        if ($this->input->method() === 'post') {
            $uid = (int) $this->session->userdata('user_id');
            $rating = max(1, min(5, (int) $this->input->post('rating')));
            $text = trim((string) $this->input->post('feedback_text'));
            $sentiment = null;
            if ($this->db->table_exists('users') && file_exists(APPPATH . 'models/Ai_model.php')) {
                $this->load->model('Ai_model', 'ai');
                if (method_exists($this->ai, 'analyze_sentiment')) {
                    $analysis = $this->ai->analyze_sentiment($text);
                    if (is_array($analysis) && isset($analysis['sentiment'])) {
                        $sentiment = (string) $analysis['sentiment'];
                    }
                }
            }
            $id = $this->eng->save_feedback(array(
                'client_id' => (int) $this->input->post('client_id') ?: null,
                'project_id' => (int) $this->input->post('project_id') ?: null,
                'submitted_by' => $uid,
                'customer_name' => trim((string) $this->input->post('customer_name')),
                'rating' => $rating,
                'feedback_text' => $text,
                'sentiment' => $sentiment,
            ));

            if ($rating >= 4) {
                reward_engine_claim('exceptional_customer_feedback', array(
                    'user_id' => $uid,
                    'actor_id' => $uid,
                    'source_module' => 'customer_feedback',
                    'source_record_id' => $id,
                    'reference_label' => 'Customer feedback (' . $rating . '/5)',
                ));
            }

            $this->session->set_flashdata('success', 'Feedback recorded.');
            redirect('customer-feedback');
            return;
        }
        $this->load->view('customer_feedback/form', array(
            'clients' => $this->eng->client_options(),
            'projects' => $this->eng->project_options(),
        ));
    }
}
