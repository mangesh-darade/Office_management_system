<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * FPDF-based PDF Generator for Payslips
 * Creates reliable PDF files using FPDF structure
 */
class Fpdf_generator {
    
    private $page;
    private $y;
    private $title;
    private $author;
    
    public function __construct() {
        $this->page = '';
        $this->y = 50;
        $this->title = 'Payslip';
        $this->author = get_company_name();
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function setAuthor($author) {
        $this->author = $author;
    }
    
    public function addText($text, $size = 12, $bold = false) {
        $this->y += 10;
        $text = $this->escape($text);
        $this->page .= "BT /F1 $size Tf 50 {$this->y} Td ($text) Tj ET\n";
    }
    
    public function addLine($label, $value = '', $size = 12) {
        $this->y += 10;
        $label = $this->escape($label);
        $value = $this->escape($value);
        
        if ($value !== '') {
            $this->page .= "BT /F1 $size Tf 50 {$this->y} Td ($label) Tj ET\n";
            $this->y += 8;
            $this->page .= "BT /F1 $size Tf 300 {$this->y} Td ($value) Tj ET\n";
            $this->y += 2;
        } else {
            $this->page .= "BT /F1 $size Tf 50 {$this->y} Td ($label) Tj ET\n";
        }
    }
    
    public function addTable($headers, $rows, $size = 10) {
        $this->y += 10;
        
        // Headers
        $x = 50;
        foreach ($headers as $header) {
            $header = $this->escape($header);
            $this->page .= "BT /F1 $size Tf $x {$this->y} Td ($header) Tj ET\n";
            $x += 150;
        }
        
        // Rows
        foreach ($rows as $row) {
            $this->y += 8;
            $x = 50;
            foreach ($row as $cell) {
                $cell = $this->escape($cell);
                $this->page .= "BT /F1 $size Tf $x {$this->y} Td ($cell) Tj ET\n";
                $x += 150;
            }
        }
    }
    
    public function addSeparator() {
        $this->y += 10;
        $this->page .= "50 {$this->y} m 500 {$this->y} l S\n";
    }
    
    private function escape($text) {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
    
    public function output() {
        $content = $this->page . "showpage\n";
        
        $pdf = "%PDF-1.4\n";
        
        // Objects
        $objects = [];
        
        // Catalog
        $objects[] = "1 0 obj\n<</Type /Catalog/Pages 2 0 R>>\nendobj\n";
        
        // Pages
        $objects[] = "2 0 obj\n<</Type /Pages/Kids [3 0 R]/Count 1>>\nendobj\n";
        
        // Page
        $objects[] = "3 0 obj\n<</Type /Page/Parent 2 0 R/MediaBox [0 0 612 792]/Contents 4 0 R/Resources << /Font << /F1 5 0 R>>>>>>\nendobj\n";
        
        // Content
        $content_len = strlen($content);
        $objects[] = "4 0 obj\n<</Length $content_len>>\nstream\n" . $content . "endstream\nendobj\n";
        
        // Font
        $objects[] = "5 0 obj\n<</Type /Font/Subtype /Type1/BaseFont /Helvetica>>\nendobj\n";
        
        // Build PDF
        $xref = strlen($pdf) + strlen("xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n");
        
        foreach ($objects as $obj) {
            $pdf .= $obj;
            $xref += strlen($obj);
        }
        
        // Cross-reference
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        
        $offset = strlen("%PDF-1.4\n") + strlen("xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n");
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
            $offset += strlen($objects[$i-1]);
        }
        
        // Trailer
        $pdf .= "trailer\n<</Size " . (count($objects) + 1) . "/Root 1 0 R>>\nstartxref\n" . $offset . "\n%%EOF";
        
        return $pdf;
    }
}
