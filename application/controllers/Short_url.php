<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Short_url extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('url_shortener');
    }
    
    // Handle short URL redirects - /p/{short_code}
    public function redirect($short_code = '') {
        if (empty($short_code)) {
            show_404();
            return;
        }
        
        // Clean the short code
        $short_code = trim($short_code);
        
        // Expand the short URL
        $original_url = $this->url_shortener->expand($short_code);
        
        if ($original_url) {
            // Redirect to the original URL
            redirect($original_url);
        } else {
            // Show 404 if URL not found or expired
            show_404();
        }
    }
    
    // Optional: Show URL statistics (for admin)
    public function stats() {
        // This could be expanded to show click statistics
        // For now, just redirect to admin dashboard
        if ($this->session->userdata('user_id')) {
            redirect('dashboard');
        } else {
            redirect('auth/login');
        }
    }
}
