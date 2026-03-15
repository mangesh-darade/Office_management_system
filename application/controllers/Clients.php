<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clients extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form','permission','error_handler']);
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
                `demo_url` varchar(255) DEFAULT NULL,
                `pos_url` varchar(255) DEFAULT NULL,
                `address` text,
                `city` varchar(100) DEFAULT NULL,
                `state` varchar(100) DEFAULT NULL,
                `country` varchar(100) DEFAULT 'India',
                `zip_code` varchar(20) DEFAULT NULL,
                `gstin` varchar(50) DEFAULT NULL,
                `pan_number` varchar(20) DEFAULT NULL,
                `industry` varchar(100) DEFAULT NULL,
                `onboarding_date` date DEFAULT NULL,
                `client_type` varchar(30) DEFAULT 'company',
                `account_manager_id` int(11) DEFAULT NULL,
                `status` varchar(20) DEFAULT 'active',
                `notes` text,
                `db_name` varchar(255) DEFAULT NULL,
                `db_username` varchar(255) DEFAULT NULL,
                `db_password` varchar(255) DEFAULT NULL,
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
        if ($this->db->table_exists('clients')){
            $fields = [
                'demo_url' => "ALTER TABLE `clients` ADD `demo_url` varchar(255) DEFAULT NULL AFTER `website`",
                'pos_url' => "ALTER TABLE `clients` ADD `pos_url` varchar(255) DEFAULT NULL AFTER `demo_url`",
                'onboarding_date' => "ALTER TABLE `clients` ADD `onboarding_date` date DEFAULT NULL AFTER `industry`",
                'db_name' => "ALTER TABLE `clients` ADD `db_name` varchar(255) DEFAULT NULL AFTER `notes`",
                'db_username' => "ALTER TABLE `clients` ADD `db_username` varchar(255) DEFAULT NULL AFTER `db_name`",
                'db_password' => "ALTER TABLE `clients` ADD `db_password` varchar(255) DEFAULT NULL AFTER `db_username`"
            ];
            foreach ($fields as $field => $sql){
                if (!$this->db->field_exists($field, 'clients')){
                    $this->db->query($sql);
                }
            }
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

    private function upload_logo($existing = null){
        if (!isset($_FILES['logo']) || empty($_FILES['logo']['name'])){
            return $existing;
        }
        $base_path = FCPATH.'uploads/clients';
        if (!is_dir($base_path)){
            @mkdir($base_path, 0755, true);
        }
        $config = [
            'upload_path' => $base_path,
            'allowed_types' => 'gif|jpg|jpeg|png|webp|svg',
            'max_size' => 2048,
            'encrypt_name' => true,
        ];
        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('logo')){
            $error = trim(strip_tags($this->upload->display_errors('', '')));
            if ($error !== ''){
                $this->session->set_flashdata('error', $error);
            }
            return $existing;
        }
        $data = $this->upload->data();
        return 'uploads/clients/'.$data['file_name'];
    }

    // GET /clients
    public function index(){
        try {
            $filters = [
                'status' => $this->input->get('status'),
                'client_type' => $this->input->get('client_type'),
                'search' => $this->input->get('q')
            ];
            
            $result = safe_db_operation(function() use ($filters) {
                return $this->clients->get_clients($filters, null, 0);
            }, 'Unable to load clients. Please try again.');
            
            if (!$result['success']) {
                $this->session->set_flashdata('error', $result['error']);
                $rows = [];
            } else {
                $rows = $result['data'];
            }
            
            $this->load->view('clients/index', ['rows'=>$rows, 'filters'=>$filters]);
            
        } catch (Exception $e) {
            $error_message = handle_database_error($e, 'Unable to load clients list. Please try again.');
            $this->session->set_flashdata('error', $error_message);
            $this->load->view('clients/index', ['rows'=>[], 'filters'=>[]]);
        } catch (Throwable $e) {
            $error_message = handle_database_error($e, 'Unable to load clients list. Please try again.');
            $this->session->set_flashdata('error', $error_message);
            $this->load->view('clients/index', ['rows'=>[], 'filters'=>[]]);
        }
    }

    // GET/POST /clients/create
    public function create(){
        if ($this->input->method() === 'post'){
            try {
                // Validation rules - Mandatory fields
                $this->load->library('form_validation');
                
                // Mandatory fields
                $this->form_validation->set_rules('company_name', 'Company Name', 'required|trim|min_length[2]|max_length[255]');
                $this->form_validation->set_rules('contact_person', 'Contact Person', 'required|trim|min_length[2]|max_length[200]');
                $this->form_validation->set_rules('phone', 'Phone', 'required|trim|min_length[10]|max_length[20]|regex_match[/^[0-9+\s\-\(\)]+$/]');
                
               
              
              
                if ($this->form_validation->run() == FALSE) {
                    $errors = validation_errors();
                    $this->session->set_flashdata('error', handle_validation_error($errors));
                    redirect('clients/create');
                    return;
                }
                
                // Additional business logic validation
                $company_name = trim($this->input->post('company_name'));
                $contact_person = trim($this->input->post('contact_person'));
                $email = trim($this->input->post('email'));
                $phone = trim($this->input->post('phone'));
                
                // Double-check mandatory fields (server-side validation)
                if (empty($company_name)) {
                    $this->session->set_flashdata('error', 'Company Name is required.');
                    redirect('clients/create');
                    return;
                }
                if (empty($contact_person)) {
                    $this->session->set_flashdata('error', 'Contact Person is required.');
                    redirect('clients/create');
                    return;
                }
                if (empty($phone)) {
                    $this->session->set_flashdata('error', 'Phone is required.');
                    redirect('clients/create');
                    return;
                }
                
                // Check if email already exists
                if (!empty($email)) {
                    if (!method_exists($this->clients, 'email_exists')) {
                        log_message('error', 'Client_model::email_exists() method not found');
                        $this->session->set_flashdata('error', 'System error: Unable to validate email. Please contact administrator.');
                        redirect('clients/create');
                        return;
                    }
                    if ($this->clients->email_exists($email)) {
                        $this->session->set_flashdata('error', 'This email address is already registered. Please use a different email.');
                        redirect('clients/create');
                        return;
                    }
                }
                
                // Check if phone already exists
                if (!empty($phone)) {
                    if (!method_exists($this->clients, 'phone_exists')) {
                        log_message('error', 'Client_model::phone_exists() method not found');
                        $this->session->set_flashdata('error', 'System error: Unable to validate phone. Please contact administrator.');
                        redirect('clients/create');
                        return;
                    }
                    if ($this->clients->phone_exists($phone)) {
                        $this->session->set_flashdata('error', 'This phone number is already registered. Please use a different phone number.');
                        redirect('clients/create');
                        return;
                    }
                }
                
                // Generate client code
                $client_code = $this->clients->generate_client_code();
                if (empty($client_code)) {
                    $this->session->set_flashdata('error', 'Unable to generate client code. Please try again.');
                    redirect('clients/create');
                    return;
                }
                
                // Upload logo
                $logo_path = $this->upload_logo();
                
                // Prepare data
                $data = [
                    'client_code' => $client_code,
                    'company_name' => $company_name,
                    'contact_person' => $contact_person,
                    'email' => $email,
                    'phone' => $phone,
                    'alternate_phone' => trim($this->input->post('alternate_phone')),
                    'website' => trim($this->input->post('website')),
                    'demo_url' => trim($this->input->post('demo_url')),
                    'pos_url' => trim($this->input->post('pos_url')),
                    'address' => trim($this->input->post('address')),
                    'city' => trim($this->input->post('city')),
                    'state' => trim($this->input->post('state')),
                    'country' => trim($this->input->post('country')) ?: 'India',
                    'zip_code' => trim($this->input->post('zip_code')),
                    'gstin' => trim($this->input->post('gstin')),
                    'pan_number' => trim($this->input->post('pan_number')),
                    'industry' => trim($this->input->post('industry')),
                    'onboarding_date' => $this->input->post('onboarding_date') ?: null,
                    'client_type' => $this->input->post('client_type') ?: 'company',
                    'account_manager_id' => $this->input->post('account_manager_id') !== '' ? (int)$this->input->post('account_manager_id') : null,
                    'notes' => trim($this->input->post('notes')),
                    'db_name' => trim($this->input->post('db_name')),
                    'db_username' => trim($this->input->post('db_username')),
                    'db_password' => trim($this->input->post('db_password')),
                    'logo' => $logo_path,
                    'status' => 'active',
                    'created_by' => (int)$this->session->userdata('user_id'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                
                // Create client with error handling
                $result = safe_db_operation(function() use ($data) {
                    return $this->clients->create_client($data);
                }, 'Failed to create client. Please try again.');
                
                if (!$result['success']) {
                    $this->session->set_flashdata('error', $result['error']);
                    redirect('clients/create');
                    return;
                }
                
                $id = $result['data'];
                if (empty($id) || $id <= 0) {
                    $this->load->helper('notification');
                    $error_msg = get_notification_message('clients', 'create', 'error');
                    $this->session->set_flashdata('error', $error_msg);
                    redirect('clients/create');
                    return;
                }
                
                $this->load->helper('notification');
                $success_msg = get_notification_message('clients', 'create', 'success');
                $this->session->set_flashdata('success', $success_msg);
                redirect('clients/view/'.$id);
                return;
                
            } catch (Exception $e) {
                $error_message = handle_database_error($e, 'An error occurred while creating the client. Please try again.');
                $this->session->set_flashdata('error', $error_message);
                redirect('clients/create');
                return;
            } catch (Throwable $e) {
                $error_message = handle_database_error($e, 'An error occurred while creating the client. Please try again.');
                $this->session->set_flashdata('error', $error_message);
                redirect('clients/create');
                return;
            }
        }
        
        try {
            $managers = $this->clients->get_account_managers();
            $this->load->view('clients/create', ['managers'=>$managers]);
        } catch (Exception $e) {
            $error_message = handle_database_error($e, 'Unable to load client creation form. Please try again.');
            $this->session->set_flashdata('error', $error_message);
            redirect('clients');
            return;
        }
    }

    // GET /clients/view/{id}
    public function view($id){
        $id = (int)$id;
        if ($id <= 0) {
            show_404();
            return;
        }
        
        try {
            $result = safe_db_operation(function() use ($id) {
                return $this->clients->get_client($id);
            }, 'Unable to load client details. Please try again.');
            
            if (!$result['success'] || !$result['data']) {
                if (!$result['data']) {
                    show_404();
                    return;
                }
                $this->session->set_flashdata('error', $result['error']);
                redirect('clients');
                return;
            }
            
            $c = $result['data'];
            
            $contacts_result = safe_db_operation(function() use ($id) {
                return $this->clients->get_client_contacts($id);
            }, 'Unable to load client contacts. Please try again.');
            
            $contacts = $contacts_result['success'] ? $contacts_result['data'] : [];
            
            if (!$contacts_result['success']) {
                $this->session->set_flashdata('error', $contacts_result['error']);
            }
            
            $this->load->view('clients/view', ['client'=>$c, 'contacts'=>$contacts]);
            
        } catch (Exception $e) {
            $error_message = handle_database_error($e, 'Unable to load client details. Please try again.');
            $this->session->set_flashdata('error', $error_message);
            redirect('clients');
            return;
        } catch (Throwable $e) {
            $error_message = handle_database_error($e, 'Unable to load client details. Please try again.');
            $this->session->set_flashdata('error', $error_message);
            redirect('clients');
            return;
        }
    }

    public function edit($id){
        $id = (int)$id;
        if ($id <= 0) {
            show_404();
            return;
        }
        
        try {
            $c = $this->clients->get_client($id);
            if (!$c) { 
                show_404();
                return;
            }
            
            if ($this->input->method() === 'post'){
                try {
                    // Validation - Mandatory fields only
                    $this->load->library('form_validation');
                    
                    // Mandatory fields
                    $this->form_validation->set_rules('company_name', 'Company Name', 'required|trim|min_length[2]|max_length[255]');
                    $this->form_validation->set_rules('contact_person', 'Contact Person', 'required|trim|min_length[2]|max_length[200]');
                    $this->form_validation->set_rules('phone', 'Phone', 'required|trim|min_length[10]|max_length[20]|regex_match[/^[0-9+\s\-\(\)]+$/]');
                    
                    if ($this->form_validation->run() == FALSE) {
                        $errors = validation_errors();
                        $this->session->set_flashdata('error', handle_validation_error($errors));
                        redirect('clients/edit/'.$id);
                        return;
                    }
                    
                    // Extract and validate mandatory fields
                    $company_name = trim($this->input->post('company_name'));
                    $contact_person = trim($this->input->post('contact_person'));
                    $phone = trim($this->input->post('phone'));
                    $email = trim($this->input->post('email'));
                    
                    // Double-check mandatory fields (server-side validation)
                    if (empty($company_name)) {
                        $this->session->set_flashdata('error', 'Company Name is required.');
                        redirect('clients/edit/'.$id);
                        return;
                    }
                    if (empty($contact_person)) {
                        $this->session->set_flashdata('error', 'Contact Person is required.');
                        redirect('clients/edit/'.$id);
                        return;
                    }
                    if (empty($phone)) {
                        $this->session->set_flashdata('error', 'Phone is required.');
                        redirect('clients/edit/'.$id);
                        return;
                    }
                    
                    // Check email uniqueness (excluding current client)
                    if (!empty($email) && method_exists($this->clients, 'email_exists')) {
                        if ($this->clients->email_exists($email, $id)) {
                            $this->session->set_flashdata('error', 'This email address is already registered to another client.');
                            redirect('clients/edit/'.$id);
                            return;
                        }
                    }
                    
                    // Check phone uniqueness (excluding current client)
                    if (!empty($phone) && method_exists($this->clients, 'phone_exists')) {
                        if ($this->clients->phone_exists($phone, $id)) {
                            $this->session->set_flashdata('error', 'This phone number is already registered to another client.');
                            redirect('clients/edit/'.$id);
                            return;
                        }
                    }
                    
                    $logo_path = $this->upload_logo(isset($c->logo) ? $c->logo : null);
                    $data = [
                        'company_name' => $company_name,
                        'contact_person' => $contact_person,
                        'email' => $email,
                        'phone' => $phone,
                        'alternate_phone' => trim($this->input->post('alternate_phone')),
                        'website' => trim($this->input->post('website')),
                        'demo_url' => trim($this->input->post('demo_url')),
                        'pos_url' => trim($this->input->post('pos_url')),
                        'address' => trim($this->input->post('address')),
                        'city' => trim($this->input->post('city')),
                        'state' => trim($this->input->post('state')),
                        'country' => trim($this->input->post('country')) ?: 'India',
                        'zip_code' => trim($this->input->post('zip_code')),
                        'gstin' => trim($this->input->post('gstin')),
                        'pan_number' => trim($this->input->post('pan_number')),
                        'industry' => trim($this->input->post('industry')),
                        'onboarding_date' => $this->input->post('onboarding_date') ?: null,
                        'client_type' => $this->input->post('client_type') ?: 'company',
                        'account_manager_id' => $this->input->post('account_manager_id') !== '' ? (int)$this->input->post('account_manager_id') : null,
                        'notes' => trim($this->input->post('notes')),
                        'db_name' => trim($this->input->post('db_name')),
                        'db_username' => trim($this->input->post('db_username')),
                        'db_password' => trim($this->input->post('db_password')),
                        'logo' => $logo_path,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $status = $this->input->post('status');
                    if ($status !== null && $status !== ''){
                        $data['status'] = $status;
                    }
                    
                    // Update with error handling
                    $result = safe_db_operation(function() use ($id, $data) {
                        return $this->clients->update_client($id, $data);
                    }, 'Failed to update client. Please try again.');
                    
                    if (!$result['success']) {
                        $this->session->set_flashdata('error', $result['error']);
                        redirect('clients/edit/'.$id);
                        return;
                    }
                    
                    $this->load->helper('notification');
                    $success_msg = get_notification_message('clients', 'update', 'success');
                    $this->session->set_flashdata('success', $success_msg);
                    redirect('clients/view/'.$id);
                    return;
                    
                } catch (Exception $e) {
                    $error_message = handle_database_error($e, 'An error occurred while updating the client. Please try again.');
                    $this->session->set_flashdata('error', $error_message);
                    redirect('clients/edit/'.$id);
                    return;
                } catch (Throwable $e) {
                    $error_message = handle_database_error($e, 'An error occurred while updating the client. Please try again.');
                    $this->session->set_flashdata('error', $error_message);
                    redirect('clients/edit/'.$id);
                    return;
                }
            }
            
            $managers = $this->clients->get_account_managers();
            $this->load->view('clients/edit', ['client'=>$c, 'managers'=>$managers]);
            
        } catch (Exception $e) {
            $error_message = handle_database_error($e, 'Unable to load client. Please try again.');
            $this->session->set_flashdata('error', $error_message);
            redirect('clients');
            return;
        }
    }
    
    // GET /clients/export
    public function export(){
        $filters = [
            'status' => $this->input->get('status'),
            'client_type' => $this->input->get('client_type'),
            'search' => $this->input->get('q')
        ];
        
        // Get all clients for export
        $clients = $this->clients->get_clients($filters, null, 0);
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="clients_export_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'Client Code',
            'Company Name',
            'Contact Person',
            'Email',
            'Phone',
            'Alternate Phone',
            'Website',
            'Demo URL',
            'POS URL',
            'Address',
            'City',
            'State',
            'Country',
            'ZIP Code',
            'GSTIN',
            'PAN Number',
            'Industry',
            'Onboarding Date',
            'Client Type',
            'Status',
            'Notes',
            'Created At'
        ]);
        
        // CSV data rows
        foreach ($clients as $client) {
            fputcsv($output, [
                $client->client_code,
                $client->company_name,
                $client->contact_person,
                $client->email,
                $client->phone,
                $client->alternate_phone,
                $client->website,
                $client->demo_url,
                $client->pos_url,
                $client->address,
                $client->city,
                $client->state,
                $client->country,
                $client->zip_code,
                $client->gstin,
                $client->pan_number,
                $client->industry,
                $client->onboarding_date,
                $client->client_type,
                $client->status,
                $client->notes,
                $client->created_at
            ]);
        }
        
        // Close output stream
        fclose($output);
        exit;
    }

    /**
     * POST /clients/delete/{id}
     */
    public function delete($id = null){
        $id = (int)$id;
        if (!$id) { show_404(); }
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); }

        if (!function_exists('has_module_access') || (!has_module_access('clients_delete') && !has_module_access('clients'))) {
            show_error('Access Denied', 403);
        }

        $client = $this->clients->get_client($id);
        if (!$client) { show_404(); }

        $this->clients->delete_client($id);
        $this->session->set_flashdata('success', 'Client "' . htmlspecialchars($client->company_name) . '" deleted successfully.');
        redirect('clients');
    }

    /**
     * GET /clients/{id}/contacts
     * View contacts for a specific client (JSON or view)
     */
    public function contacts($id = null){
        $id = (int)$id;
        if (!$id) { show_404(); }

        $client = $this->clients->get_client($id);
        if (!$client) { show_404(); }

        // Return JSON if AJAX, otherwise redirect to client view
        if ($this->input->is_ajax_request()) {
            $contacts = array();
            if ($this->db->table_exists('client_contacts')) {
                $contacts = $this->db->where('client_id', $id)->order_by('id', 'ASC')->get('client_contacts')->result();
            }
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'contacts' => $contacts]);
            exit;
        }

        redirect('clients/view/' . $id);
    }
}
