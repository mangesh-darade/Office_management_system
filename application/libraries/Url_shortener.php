<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * URL Shortener for Payslip Links
 * Creates short, memorable URLs for email links
 */
class Url_shortener {
    
    private $CI;
    private $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->create_table_if_not_exists();
    }
    
    private function create_table_if_not_exists() {
        // Create short_urls table if it doesn't exist
        $this->CI->db->query("
            CREATE TABLE IF NOT EXISTS short_urls (
                id INT AUTO_INCREMENT PRIMARY KEY,
                short_code VARCHAR(10) UNIQUE NOT NULL,
                original_url TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                clicks INT DEFAULT 0,
                INDEX (short_code),
                INDEX (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ");
    }
    
    public function shorten($long_url, $expires_days = 30) {
        // Check if URL already exists and hasn't expired
        $this->CI->db->where('original_url', $long_url);
        $this->CI->db->where('(expires_at IS NULL OR expires_at > NOW())');
        $existing = $this->CI->db->get('short_urls')->row();
        
        if ($existing) {
            return $this->generate_short_url($existing->short_code);
        }
        
        // Generate new short code
        $short_code = $this->generate_unique_code();
        
        // Calculate expiry date
        $expires_at = date('Y-m-d H:i:s', strtotime("+$expires_days days"));
        
        // Insert new record
        $data = [
            'short_code' => $short_code,
            'original_url' => $long_url,
            'expires_at' => $expires_at
        ];
        
        $this->CI->db->insert('short_urls', $data);
        
        return $this->generate_short_url($short_code);
    }
    
    private function generate_unique_code() {
        do {
            $code = $this->generate_code(6); // 6 characters = ~56.8 billion combinations
            $exists = $this->CI->db->where('short_code', $code)->get('short_urls')->row();
        } while ($exists);
        
        return $code;
    }
    
    private function generate_code($length) {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $this->chars[rand(0, strlen($this->chars) - 1)];
        }
        return $code;
    }
    
    private function generate_short_url($code) {
        $base_url = $this->CI->config->item('base_url');
        return rtrim($base_url, '/') . '/p/' . $code;
    }
    
    public function expand($short_code) {
        $this->CI->db->where('short_code', $short_code);
        $this->CI->db->where('(expires_at IS NULL OR expires_at > NOW())');
        $url = $this->CI->db->get('short_urls')->row();
        
        if ($url) {
            // Increment click count
            $this->CI->db->where('id', $url->id);
            $this->CI->db->set('clicks', 'clicks + 1', FALSE);
            $this->CI->db->update('short_urls');
            
            return $url->original_url;
        }
        
        return false;
    }
    
    public function cleanup_expired() {
        // Delete expired URLs (older than 90 days)
        $this->CI->db->where('expires_at <', date('Y-m-d H:i:s', strtotime('-90 days')));
        $this->CI->db->delete('short_urls');
    }
}
