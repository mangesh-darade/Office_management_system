<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Native PDF Generator for Payslips
 * Creates simple PDF files without external dependencies
 */
class Native_pdf {
    
    private $content = '';
    private $title = '';
    private $author = '';
    private $current_y = 50;
    private $page_height = 842; // A4 height in points
    private $page_width = 595;  // A4 width in points
    private $margin_left = 50;
    private $margin_right = 50;
    private $margin_top = 50;
    private $margin_bottom = 50;
    private $line_height = 14;
    
    public function __construct() {
        $this->author = get_company_name();
        $this->content = '';
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function setAuthor($author) {
        $this->author = $author;
    }
    
    public function addText($text, $size = 12, $bold = false) {
        $font = $bold ? 'B' : '';
        $this->content .= "BT\n";
        $this->content .= "/F1 $size Tf\n";
        $this->content .= "{$this->margin_left} {$this->current_y} Td\n";
        $this->content .= "(" . $this->escapeString($text) . ") Tj\n";
        $this->content .= "ET\n";
        $this->current_y -= $this->line_height;
        
        // Check if we need a new page
        if ($this->current_y < $this->margin_bottom) {
            $this->newPage();
        }
    }
    
    public function addLine($text1, $text2 = '', $size = 12) {
        $this->content .= "BT\n";
        $this->content .= "/F1 $size Tf\n";
        
        // First column
        $this->content .= "{$this->margin_left} {$this->current_y} Td\n";
        $this->content .= "(" . $this->escapeString($text1) . ") Tj\n";
        
        // Second column (right-aligned)
        if ($text2 !== '') {
            $text_width = strlen($text2) * $size * 0.6; // Approximate width
            $x_pos = $this->page_width - $this->margin_right - $text_width;
            $this->content .= "0 -{$this->line_height} Td\n";
            $this->content .= "($x_pos 0) Td\n";
            $this->content .= "(" . $this->escapeString($text2) . ") Tj\n";
        }
        
        $this->content .= "ET\n";
        $this->current_y -= $this->line_height;
        
        if ($this->current_y < $this->margin_bottom) {
            $this->newPage();
        }
    }
    
    public function addTable($headers, $rows, $size = 10) {
        $table_width = $this->page_width - $this->margin_left - $this->margin_right;
        $col_width = $table_width / count($headers);
        
        // Add headers
        $this->content .= "BT\n";
        $this->content .= "/F1 $size Tf\n";
        foreach ($headers as $i => $header) {
            $x = $this->margin_left + ($i * $col_width);
            $this->content .= "$x {$this->current_y} Td\n";
            $this->content .= "(" . $this->escapeString($header) . ") Tj\n";
        }
        $this->content .= "ET\n";
        $this->current_y -= $this->line_height;
        
        // Add rows
        foreach ($rows as $row) {
            if ($this->current_y < $this->margin_bottom + $this->line_height) {
                $this->newPage();
            }
            
            $this->content .= "BT\n";
            $this->content .= "/F1 $size Tf\n";
            foreach ($row as $i => $cell) {
                $x = $this->margin_left + ($i * $col_width);
                $this->content .= "$x {$this->current_y} Td\n";
                $this->content .= "(" . $this->escapeString($cell) . ") Tj\n";
            }
            $this->content .= "ET\n";
            $this->current_y -= $this->line_height;
        }
        
        $this->current_y -= $this->line_height;
    }
    
    public function addSeparator() {
        $y = $this->current_y + 5;
        $this->content .= "0.5 w\n";
        $this->content .= "{$this->margin_left} $y m\n";
        $this->content .= ($this->page_width - $this->margin_right) . " $y l\n";
        $this->content .= "S\n";
        $this->current_y -= $this->line_height;
    }
    
    private function newPage() {
        $this->content .= "showpage\n";
        $this->current_y = $this->page_height - $this->margin_top;
    }
    
    private function escapeString($text) {
        // Escape special characters for PDF
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '\\r', '\\n'], $text);
    }
    
    public function output() {
        $this->current_y = $this->page_height - $this->margin_top;
        
        $obj_count = 0;
        $objects = [];
        
        // Catalog
        $obj_count++;
        $catalog_id = $obj_count;
        $objects[] = "$obj_count 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n\n";
        
        // Pages
        $obj_count++;
        $pages_id = $obj_count;
        $objects[] = "$obj_count 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n\n";
        
        // Page
        $obj_count++;
        $page_id = $obj_count;
        $page_content = "<<\n/Type /Page\n/Parent $pages_id 0 R\n/MediaBox [0 0 {$this->page_width} {$this->page_height}]\n/Contents 4 0 R\n/Resources <<\n/Font <<\n/F1 5 0 R\n>>\n>>\n>>\nendobj\n\n";
        $objects[] = $page_content;
        
        // Content stream
        $obj_count++;
        $content_id = $obj_count;
        $content_stream = $this->content . "showpage\n";
        $content_length = strlen($content_stream);
        $objects[] = "$obj_count 0 obj\n<<\n/Length $content_length\n>>\nstream\n" . $content_stream . "endstream\nendobj\n\n";
        
        // Font
        $obj_count++;
        $font_id = $obj_count;
        $objects[] = "$obj_count 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica\n>>\nendobj\n\n";
        
        // Build PDF
        $pdf = "%PDF-1.4\n";
        
        $xref_offset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 " . ($obj_count + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        
        $offset = $xref_offset + strlen("xref\n0 " . ($obj_count + 1) . "\n");
        foreach ($objects as $obj) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
            $offset += strlen($obj);
        }
        
        $pdf .= "\ntrailer\n<<\n/Size " . ($obj_count + 1) . "\n/Root $catalog_id 0 R\n>>\nstartxref\n$offset\n%%EOF";
        
        foreach ($objects as $obj) {
            $pdf .= $obj;
        }
        
        return $pdf;
    }
}
