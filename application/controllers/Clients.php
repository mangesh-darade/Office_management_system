<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clients extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission']);
        $this->load->library(['session']);
        if (!(int)$this->session->userdata('user_id')) { redirect('auth/login'); }
        if (!function_exists('has_module_access') || !has_module_access('clients')) { show_error('Access Denied', 403); }
        $this->ensure_schema();
        $this->load->model('Client_model','clients');
    }

    private function ensure_schema(){
        if (!$this->db->table_exists('clients')){
            $sql = "CREATE TABLE `clients` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `client_code` varchar(50) NOT NULL,
                `company_name` varchar(255) NOT NULL,
                `contact_person` varchar(200) DEFAULT NULL,
                `email` varchar(255) DEFAULT NULL,
                `phone` varchar(20) DEFAULT NULL,
                `alternate_phone` varchar(20) DEFAULT NULL,
                `website` varchar(255) DEFAULT NULL,
                `address` text,
                `city` varchar(100) DEFAULT NULL,
                `state` varchar(100) DEFAULT NULL,
                `country` varchar(100) DEFAULT 'India',
                `zip_code` varchar(20) DEFAULT NULL,
                `gstin` varchar(50) DEFAULT NULL,
                `pan_number` varchar(20) DEFAULT NULL,
                `industry` varchar(100) DEFAULT NULL,
                `client_type` varchar(30) DEFAULT 'company',
                `account_manager_id` int(11) DEFAULT NULL,
                `status` varchar(20) DEFAULT 'active',
                `notes` text,
                `logo` varchar(255) DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_client_code` (`client_code`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
        if (!$this->db->table_exists('client_contacts')){
            $sql2 = "CREATE TABLE `client_contacts` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `client_id` int(11) NOT NULL,
                `contact_name` varchar(200) NOT NULL,
                `designation` varchar(100) DEFAULT NULL,
                `email` varchar(255) DEFAULT NULL,
                `phone` varchar(20) DEFAULT NULL,
                `is_primary` tinyint(1) DEFAULT 0,
                `department` varchar(100) DEFAULT NULL,
                `notes` text,
                `status` varchar(20) DEFAULT 'active',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_client` (`client_id`),
                KEY `idx_primary` (`is_primary`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql2);
        }
    }

    // GET /clients
    public function index(){
        $filters = [
            'status' => $this->input->get('status'),
            'client_type' => $this->input->get('client_type'),
            'search' => $this->input->get('q')
        ];
        $rows = $this->clients->get_clients($filters, null, 0);
        $this->load->view('clients/index', ['rows'=>$rows, 'filters'=>$filters]);
    }

    // GET/POST /clients/create
    public function create(){
        if ($this->input->method() === 'post'){
            $client_code = $this->clients->generate_client_code();
            $data = [
                'client_code' => $client_code,
                'company_name' => trim($this->input->post('company_name')),
                'contact_person' => trim($this->input->post('contact_person')),
                'email' => trim($this->input->post('email')),
                'phone' => trim($this->input->post('phone')),
                'alternate_phone' => trim($this->input->post('alternate_phone')),
                'website' => trim($this->input->post('website')),
                'address' => trim($this->input->post('address')),
                'city' => trim($this->input->post('city')),
                'state' => trim($this->input->post('state')),
                'country' => trim($this->input->post('country')),
                'zip_code' => trim($this->input->post('zip_code')),
                'gstin' => trim($this->input->post('gstin')),
                'pan_number' => trim($this->input->post('pan_number')),
                'industry' => trim($this->input->post('industry')),
                'client_type' => $this->input->post('client_type') ?: 'company',
                'account_manager_id' => $this->input->post('account_manager_id') !== '' ? (int)$this->input->post('account_manager_id') : null,
                'notes' => trim($this->input->post('notes')),
                'status' => 'active',
                'created_by' => (int)$this->session->userdata('user_id'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $id = $this->clients->create_client($data);
            $this->session->set_flashdata('success','Client created');
            redirect('clients/view/'.$id);
            return;
        }
        $managers = $this->clients->get_account_managers();
        $this->load->view('clients/create', ['managers'=>$managers]);
    }

    // GET /clients/view/{id}
    public function view($id){
        $c = $this->clients->get_client((int)$id);
        if (!$c) { show_404(); }
        $contacts = $this->clients->get_client_contacts((int)$id);
        $this->load->view('clients/view', ['client'=>$c, 'contacts'=>$contacts]);
    }
}
