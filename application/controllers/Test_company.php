<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Test controller for company helper functionality
 */
class Test_company extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        // Only allow access in development or for admins
        if (ENVIRONMENT === 'production') {
            show_404();
        }
    }
    
    public function index() {
        echo "<h1>Company Helper Test</h1>";
        
        // Test company name
        $company_name = get_company_name();
        echo "<p><strong>Company Name:</strong> " . htmlspecialchars($company_name) . "</p>";
        
        // Test company email
        $company_email = get_company_email();
        echo "<p><strong>Company Email:</strong> " . htmlspecialchars($company_email) . "</p>";
        
        // Test company phone
        $company_phone = get_company_phone();
        echo "<p><strong>Company Phone:</strong> " . htmlspecialchars($company_phone) . "</p>";
        
        // Test company address
        $company_address = get_company_address();
        echo "<p><strong>Company Address:</strong> " . htmlspecialchars($company_address) . "</p>";
        
        // Test company logo
        $company_logo = get_company_logo();
        echo "<p><strong>Company Logo:</strong> " . htmlspecialchars($company_logo) . "</p>";
        
        // Test all details
        $details = get_company_details();
        echo "<h2>All Company Details:</h2>";
        echo "<pre>" . print_r($details, true) . "</pre>";
        
        echo "<p><a href='" . site_url('settings') . "'>Configure Company Settings</a></p>";
    }
}
