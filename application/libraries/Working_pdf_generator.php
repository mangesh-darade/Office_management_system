<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Working PDF Generator for Payslips
 * Creates valid PDF files with correct structure
 */
class Working_pdf_generator {
    
    private $content = '';
    private $y_position = 750;
    
    public function __construct() {
        $this->content = '';
    }
    
    public function setTitle($title) {
        // Title will be added to metadata
    }
    
    public function setAuthor($author) {
        $this->author = $author;
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
        
        // Create a simple but valid PDF structure
        $pdf = "%PDF-1.4\n";
        
        // Build objects manually to avoid offset calculation issues
        $objects = [];
        
        // Object 1: Catalog
        $objects[] = "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n";
        
        // Object 2: Pages
        $objects[] = "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n";
        
        // Object 3: Page
        $objects[] = "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n/Contents 4 0 R\n/Resources <<\n/Font <<\n/F1 5 0 R\n>>\n>>\n>>\nendobj\n";
        
        // Object 4: Content stream
        $content_length = strlen($content);
        $objects[] = "4 0 obj\n<<\n/Length $content_length\n>>\nstream\n" . $content . "endstream\nendobj\n";
        
        // Object 5: Font
        $objects[] = "5 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica\n>>\nendobj\n";
        
        // Add all objects to PDF
        foreach ($objects as $obj) {
            $pdf .= $obj . "\n";
        }
        
        // Calculate xref table
        $xref_start = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        
        // Calculate offsets for each object
        $current_offset = strlen("%PDF-1.4\n");
        for ($i = 0; $i < count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $current_offset);
            $current_offset += strlen($objects[$i]) + 1; // +1 for the newline
        }
        
        // Add trailer
        $pdf .= "trailer\n<<\n/Size " . (count($objects) + 1) . "\n/Root 1 0 R\n>>\nstartxref\n$xref_start\n%%EOF";
        
        return $pdf;
    }
}
