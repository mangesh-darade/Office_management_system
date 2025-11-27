<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Basic PDF Generator for Payslips
 * Creates simple but valid PDF files
 */
class Basic_pdf_generator {
    
    private $content = '';
    private $y_position = 750;
    private $page_height = 792; // Letter size height
    
    public function __construct() {
        $this->content = '';
    }
    
    public function setTitle($title) {
        // Title will be added to metadata
    }
    
    public function setAuthor($author) {
        // Author will be added to metadata
    }
    
    public function addText($text, $size = 12, $bold = false) {
        $this->y_position -= 20;
        $escaped_text = $this->escape($text);
        $this->content .= "BT /F1 $size Tf 50 {$this->y_position} Td ($escaped_text) Tj ET\n";
    }
    
    public function addLine($label, $value = '', $size = 12) {
        $this->y_position -= 20;
        $escaped_label = $this->escape($label);
        $escaped_value = $this->escape($value);
        
        $this->content .= "BT /F1 $size Tf 50 {$this->y_position} Td ($escaped_label) Tj ET\n";
        if ($value !== '') {
            $this->content .= "BT /F1 $size Tf 300 {$this->y_position} Td ($escaped_value) Tj ET\n";
        }
    }
    
    public function addTable($headers, $rows, $size = 10) {
        // Add headers
        $this->y_position -= 20;
        $x = 50;
        foreach ($headers as $header) {
            $escaped_header = $this->escape($header);
            $this->content .= "BT /F1 $size Tf $x {$this->y_position} Td ($escaped_header) Tj ET\n";
            $x += 200;
        }
        
        // Add rows
        foreach ($rows as $row) {
            $this->y_position -= 15;
            $x = 50;
            foreach ($row as $cell) {
                $escaped_cell = $this->escape($cell);
                $this->content .= "BT /F1 $size Tf $x {$this->y_position} Td ($escaped_cell) Tj ET\n";
                $x += 200;
            }
        }
    }
    
    public function addSeparator() {
        $this->y_position -= 20;
        $this->content .= "50 {$this->y_position} m 500 {$this->y_position} l S\n";
    }
    
    private function escape($text) {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '\\r', '\\n'], $text);
    }
    
    public function output() {
        $content = $this->content . "showpage\n";
        
        $pdf = "%PDF-1.4\n%\n";
        
        // Create objects
        $obj1 = "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n\n";
        
        $obj2 = "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n\n";
        
        $obj3 = "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n/Contents 4 0 R\n/Resources <<\n/Font <<\n/F1 5 0 R\n>>\n>>\n>>\nendobj\n\n";
        
        $content_length = strlen($content);
        $obj4 = "4 0 obj\n<<\n/Length $content_length\n>>\nstream\n" . $content . "endstream\nendobj\n\n";
        
        $obj5 = "5 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica\n>>\nendobj\n\n";
        
        // Calculate offsets
        $offset1 = strlen($pdf);
        $offset2 = $offset1 + strlen($obj1);
        $offset3 = $offset2 + strlen($obj2);
        $offset4 = $offset3 + strlen($obj3);
        $offset5 = $offset4 + strlen($obj4);
        $xref_offset = $offset5 + strlen($obj5);
        
        // Build PDF
        $pdf .= $obj1 . $obj2 . $obj3 . $obj4 . $obj5;
        
        // Cross-reference table
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", $offset1);
        $pdf .= sprintf("%010d 00000 n \n", $offset2);
        $pdf .= sprintf("%010d 00000 n \n", $offset3);
        $pdf .= sprintf("%010d 00000 n \n", $offset4);
        $pdf .= sprintf("%010d 00000 n \n", $offset5);
        
        // Trailer
        $pdf .= "trailer\n<<\n/Size 6\n/Root 1 0 R\n>>\nstartxref\n$xref_offset\n%%EOF";
        
        return $pdf;
    }
}
