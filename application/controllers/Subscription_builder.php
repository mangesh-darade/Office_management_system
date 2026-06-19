<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription_builder extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form', 'permission'));
        $this->load->library(array('session'));
        require_controller_access('subscription_builder', true);
        $this->ensure_schema();
        $this->load->model('Subscription_builder_model', 'subscription_builder');
    }

    private function ensure_schema()
    {
        $this->load->helper('subscription_builder_schema');
        subscription_builder_schema_ensure($this->db);
    }

    public function index()
    {
        require_module_access(array('subscription_builder', 'subscription_builder_list'), true);

        $plans = $this->subscription_builder->get_distinct_plans();
        $industries = $this->subscription_builder->get_distinct_industries();

        $default_plan = !empty($plans) ? $plans[0] : 'Essential';
        foreach ($plans as $plan) {
            if (strcasecmp($plan, 'Professional') === 0) {
                $default_plan = $plan;
                break;
            }
        }

        $default_industry = !empty($industries) ? $industries[0] : 'Retail';
        foreach ($industries as $industry) {
            if (strcasecmp($industry, 'Retail') === 0) {
                $default_industry = $industry;
                break;
            }
        }

        $this->load->view('subscription_builder/index', array(
            'plans' => $plans,
            'industries' => $industries,
            'default_plan' => $default_plan,
            'default_industry' => $default_industry,
            'total_rows' => $this->subscription_builder->count_all(),
            'catalog_url' => site_url('subscription-builder/catalog'),
        ));
    }

    public function catalog()
    {
        require_module_access(array('subscription_builder', 'subscription_builder_list'), true);

        $plan = trim((string) $this->input->get('plan'));
        $industry = trim((string) $this->input->get('industry'));

        if ($plan === '' || $industry === '') {
            return $this->json_response(array(
                'ok' => false,
                'error' => 'Plan and industry are required.',
            ), 400);
        }

        $catalog = $this->subscription_builder->get_catalog($plan, $industry);

        return $this->json_response(array(
            'ok' => true,
            'plan' => $plan,
            'industry' => $industry,
            'included_count' => count($catalog['included']),
            'chargeable_count' => count($catalog['chargeable']),
            'included_by_module' => $catalog['included_by_module'],
            'chargeable' => $catalog['chargeable'],
        ));
    }

    public function quote_preview()
    {
        require_module_access(array('subscription_builder', 'subscription_builder_list'), true);
        $quote = $this->parse_quote_request();
        if (empty($quote)) {
            $this->session->set_flashdata('error', 'No quotation to preview. Open Subscription Builder, configure your quote, then click Preview Quotation.');
            redirect('subscription-builder');
            return;
        }
        $this->load->helper('subscription_builder_quote');
        $html = subscription_builder_quote_render_html($quote, true);
        $this->output->set_content_type('text/html; charset=UTF-8')->set_output($html);
    }

    public function quote_pdf()
    {
        require_module_access(array('subscription_builder', 'subscription_builder_list'), true);
        $quote = $this->parse_quote_request();
        if (empty($quote)) {
            $this->session->set_flashdata('error', 'No quotation data available. Build a quote on the Subscription Builder page first.');
            redirect('subscription-builder');
            return;
        }
        $this->load->helper('subscription_builder_quote');
        $html = subscription_builder_quote_render_html($quote, false);
        $slug = preg_replace('/[^a-z0-9]+/i', '_', (string) ($quote['plan'] ?? 'quote'));
        $filename = 'subscription_quote_' . strtolower($slug) . '_' . date('Y-m-d') . '.pdf';
        subscription_builder_quote_send_pdf($html, $filename);
    }

    private function parse_quote_request()
    {
        $this->load->helper('subscription_builder_quote');
        $raw = $this->input->post('quote_json');
        $quote = subscription_builder_quote_parse_payload($raw);
        if (!empty($quote)) {
            $this->session->set_userdata('subscription_builder_last_quote', json_encode($quote));
            return $quote;
        }

        $stored = $this->session->userdata('subscription_builder_last_quote');
        if ($stored) {
            return subscription_builder_quote_parse_payload($stored);
        }

        return array();
    }

    private function json_response($payload, $status = 200)
    {
        return $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
