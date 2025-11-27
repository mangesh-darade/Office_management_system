<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Simple PDF Generator for Payslips
 * Fallback when dompdf is not available
 */
class Simple_pdf {
    
    private $content = '';
    private $title = '';
    
    public function __construct() {
        $this->content = '';
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function addContent($html) {
        // Convert basic HTML to plain text with formatting
        $text = strip_tags($html);
        // Preserve some basic formatting
        $text = str_replace(['&nbsp;', '&amp;', '&lt;', '&gt;'], [' ', '&', '<', '>'], $text);
        $this->content .= $text;
    }
    
    public function output() {
        // Create a simple text-based PDF-like format
        $output = "%PDF-1.4\n";
        $output .= "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n\n";
        
        $content = "Payslip - " . $this->title . "\n\n" . $this->content;
        $content_len = strlen($content);
        
        $output .= "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n\n";
        $output .= "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n/Contents 4 0 R\n/Resources <<\n/Font <<\n/F1 5 0 R\n>>\n>>\n>>\nendobj\n\n";
        $output .= "4 0 obj\n<<\n/Length $content_len\n>>\nstream\n" . $content . "\nendstream\nendobj\n\n";
        $output .= "5 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica\n>>\nendobj\n\n";
        $output .= "xref\n0 6\n0000000000 65535 f \n0000000010 00000 n \n0000000079 00000 n \n0000000173 00000 n \n0000000301 00000 n \n0000000400 00000 n \n";
        $output .= "trailer\n<<\n/Size 6\n/Root 1 0 R\n>>\nstartxref\n500\n%%EOF";
        
        return $output;
    }
}
