<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Simple PDF Generator for Payslips
 * Creates basic PDF files using a simplified approach
 */
class Simple_pdf_generator {
    
    private $content = '';
    private $title = '';
    private $author = '';
    private $objects = [];
    private $current_obj_id = 0;
    
    public function __construct() {
        $this->author = get_company_name();
        $this->content = '';
        $this->objects = [];
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function setAuthor($author) {
        $this->author = $author;
    }
    
    public function addText($text, $size = 12, $bold = false) {
        $y = 800 - (count($this->objects) * 20);
        $text = $this->escapeString($text);
        $this->content .= "BT /F1 $size Tf 50 $y Td ($text) Tj ET\n";
    }
    
    public function addLine($text1, $text2 = '', $size = 12) {
        $y = 800 - (count($this->objects) * 20);
        $text1 = $this->escapeString($text1);
        $text2 = $this->escapeString($text2);
        
        if ($text2 !== '') {
            $this->content .= "BT /F1 $size Tf 50 $y Td ($text1) Tj ET\n";
            $y -= 15;
            $this->content .= "BT /F1 $size Tf 400 $y Td ($text2) Tj ET\n";
        } else {
            $this->content .= "BT /F1 $size Tf 50 $y Td ($text1) Tj ET\n";
        }
    }
    
    public function addTable($headers, $rows, $size = 10) {
        $y = 800 - (count($this->objects) * 20);
        
        // Add headers
        $x = 50;
        foreach ($headers as $header) {
            $header = $this->escapeString($header);
            $this->content .= "BT /F1 $size Tf $x $y Td ($header) Tj ET\n";
            $x += 200;
        }
        $y -= 20;
        
        // Add rows
        foreach ($rows as $row) {
            $x = 50;
            foreach ($row as $cell) {
                $cell = $this->escapeString($cell);
                $this->content .= "BT /F1 $size Tf $x $y Td ($cell) Tj ET\n";
                $x += 200;
            }
            $y -= 15;
        }
    }
    
    public function addSeparator() {
        $y = 800 - (count($this->objects) * 20);
        $this->content .= "50 $y m 550 $y l S\n";
    }
    
    private function escapeString($text) {
        // Escape special characters for PDF
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '\\r', '\\n'], $text);
    }
    
    public function output() {
        // Create a simple PDF structure
        $pdf_date = date('Y-m-d H:i:s');
        $title = $this->escapeString($this->title);
        $author = $this->escapeString($this->author);
        
        // PDF content stream
        $content_stream = $this->content . "showpage\n";
        
        // Build PDF
        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n\n";
        $pdf .= "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n\n";
        $pdf .= "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n/Contents 4 0 R\n/Resources <<\n/Font <<\n/F1 5 0 R\n>>\n>>\n>>\nendobj\n\n";
        $pdf .= "4 0 obj\n<<\n/Length " . strlen($content_stream) . "\n>>\nstream\n" . $content_stream . "endstream\nendobj\n\n";
        $pdf .= "5 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica\n>>\nendobj\n\n";
        
        // Cross-reference table
        $xref_offset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        
        $offsets = [
            1 => 9,
            2 => 74,
            3 => 130,
            4 => 254,
            5 => strlen($pdf) + 1
        ];
        
        foreach ($offsets as $id => $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        
        // Trailer
        $pdf .= "trailer\n<<\n/Size 6\n/Root 1 0 R\n>>\nstartxref\n$xref_offset\n%%EOF";
        
        return $pdf;
    }
}
