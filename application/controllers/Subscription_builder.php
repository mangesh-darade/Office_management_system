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

        $countries = $this->subscription_builder->get_distinct_countries(true);
        $country_options = $this->subscription_builder->get_country_options(true);
        if (empty($countries)) {
            $countries = $this->subscription_builder->get_distinct_countries(false);
            $country_options = $this->subscription_builder->get_country_options(false);
        }
        $default_country = !empty($countries) ? $countries[0] : $this->subscription_builder->get_default_country();
        foreach ($countries as $country) {
            if (strcasecmp($country, $default_country) === 0) {
                $default_country = $country;
                break;
            }
        }
        $default_country_meta = $this->subscription_builder->resolve_country_meta($default_country);
        $default_country_meta['name'] = $default_country;

        $this->load->view('subscription_builder/index', array(
            'plans' => $plans,
            'industries' => $industries,
            'countries' => $countries,
            'country_options' => $country_options,
            'default_country' => $default_country,
            'default_country_meta' => $default_country_meta,
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
        $country = trim((string) $this->input->get('country'));

        if ($plan === '' || $industry === '') {
            return $this->json_response(array(
                'ok' => false,
                'error' => 'Plan and industry are required.',
            ), 400);
        }

        if ($country === '') {
            $country = $this->subscription_builder->get_default_country();
        }
        $country_meta = $this->subscription_builder->resolve_country_meta($country);
        $country_meta['name'] = $country;
        $catalog = $this->subscription_builder->get_catalog($plan, $industry, $country);

        return $this->json_response(array(
            'ok' => true,
            'plan' => $plan,
            'industry' => $industry,
            'country' => $country,
            'country_meta' => $country_meta,
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
            $this->session->set_flashdata('error', 'No proposal to preview. Open Subscription Builder, configure your proposal, then click Preview Proposal.');
            redirect('subscription-builder');
            return;
        }
        $this->load->helper('subscription_builder_quote');
        $html = subscription_builder_quote_render_html($quote, true, array(
            'save_url' => site_url('subscription-builder/quote-save'),
            'proposals_url' => site_url('elintom-proposals'),
            'quote_json' => $quote,
        ));
        $this->output->set_content_type('text/html; charset=UTF-8')->set_output($html);
    }

    public function quote_save()
    {
        require_module_access(array('subscription_builder', 'subscription_builder_list'), true);
        $quote = $this->parse_quote_request();
        if (empty($quote)) {
            return $this->json_response(array(
                'ok' => false,
                'error' => 'No proposal data available. Build a proposal on the Subscription Builder page first.',
            ), 400);
        }

        $result = $this->persist_quote_proposal($quote);
        if (empty($result['ok'])) {
            return $this->json_response(array(
                'ok' => false,
                'error' => $result['error'] ?? 'Unable to save proposal.',
            ), 500);
        }

        return $this->json_response(array(
            'ok' => true,
            'id' => (int) ($result['id'] ?? 0),
            'proposals_url' => site_url('elintom-proposals'),
        ));
    }

    public function quote_pdf()
    {
        require_module_access(array('subscription_builder', 'subscription_builder_list'), true);
        $quote = $this->parse_quote_request();
        if (empty($quote)) {
            $this->session->set_flashdata('error', 'No proposal data available. Build a proposal on the Subscription Builder page first.');
            redirect('subscription-builder');
            return;
        }
        $this->load->helper('subscription_builder_quote');
        $html = subscription_builder_quote_render_html($quote, false);
        $filename = subscription_builder_quote_build_export_filename($quote, 'pdf');
        $result = $this->persist_quote_proposal($quote, $html);
        $download_name = !empty($result['filename']) ? $result['filename'] : $filename;

        if (!subscription_builder_quote_send_pdf($html, $download_name)) {
            $this->session->set_flashdata('error', 'Unable to generate proposal PDF. Ensure Dompdf is installed (composer install).');
            redirect('subscription-builder');
            return;
        }

        if (empty($result['ok'])) {
            $this->session->set_flashdata(
                'warning',
                'Proposal PDF downloaded, but it could not be saved to Saved Proposals. Check uploads/elintom_proposals folder permissions.'
            );
        }
    }

    public function quote_excel()
    {
        require_module_access(array('subscription_builder', 'subscription_builder_list'), true);
        $quote = $this->parse_quote_request();
        if (empty($quote)) {
            $this->session->set_flashdata('error', 'No proposal data available. Build a proposal on the Subscription Builder page first.');
            redirect('subscription-builder');
            return;
        }
        $this->load->helper('subscription_builder_quote');
        $html = subscription_builder_quote_render_export_html($quote);
        $filename = subscription_builder_quote_build_export_filename($quote, 'xls');
        subscription_builder_quote_send_excel($html, $filename);
    }

    public function quote_doc()
    {
        require_module_access(array('subscription_builder', 'subscription_builder_list'), true);
        $quote = $this->parse_quote_request();
        if (empty($quote)) {
            $this->session->set_flashdata('error', 'No proposal data available. Build a proposal on the Subscription Builder page first.');
            redirect('subscription-builder');
            return;
        }
        $this->load->helper('subscription_builder_quote');
        $html = subscription_builder_quote_render_export_html($quote);
        $filename = subscription_builder_quote_build_export_filename($quote, 'doc');
        subscription_builder_quote_send_doc($html, $filename);
    }

    private function persist_quote_proposal($quote, $html = null)
    {
        $this->load->helper('subscription_builder_quote');
        if ($html === null) {
            $html = subscription_builder_quote_render_html($quote, false);
        }

        $filename = subscription_builder_quote_build_export_filename($quote, 'pdf');
        $document_path = subscription_builder_quote_save_pdf($html, $filename);
        if ($document_path === '') {
            return array(
                'ok' => false,
                'error' => 'Unable to save proposal file. Check uploads/elintom_proposals folder permissions.',
            );
        }

        $this->load->helper('elintom_proposals_schema');
        elintom_proposals_schema_ensure($this->db);
        $this->load->model('Elintom_proposals_model', 'elintom_proposals');
        $proposal_id = $this->elintom_proposals->create(array(
            'client_name' => $quote['client_name'] ?? '',
            'client_business' => $quote['client_business'] ?? '',
            'document_path' => $document_path,
            'created_by' => (int) $this->session->userdata('user_id'),
        ));
        if ($proposal_id) {
            $this->load->helper('activity');
            log_activity('elintom_proposals', 'created', $proposal_id, 'Proposal saved for ' . trim((string) ($quote['client_name'] ?? 'client')));
        }

        return array(
            'ok' => true,
            'id' => $proposal_id,
            'document_path' => $document_path,
            'filename' => $filename,
        );
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
