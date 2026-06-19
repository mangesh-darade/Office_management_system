<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('subscription_builder_quote_parse_payload')) {
    function subscription_builder_quote_parse_payload($raw)
    {
        if (is_array($raw)) {
            return $raw;
        }
        $text = trim((string) $raw);
        if ($text === '') {
            return array();
        }
        $decoded = json_decode($text, true);
        return is_array($decoded) ? $decoded : array();
    }
}

if (!function_exists('subscription_builder_quote_money')) {
    function subscription_builder_quote_money($amount)
    {
        return '₹ ' . number_format((float) $amount, 0, '.', ',');
    }
}

if (!function_exists('subscription_builder_quote_render_html')) {
    function subscription_builder_quote_render_html($quote, $for_print = false)
    {
        $CI =& get_instance();
        $CI->load->helper('company');

        $plan = htmlspecialchars((string) ($quote['plan_display'] ?? $quote['plan'] ?? ''), ENT_QUOTES, 'UTF-8');
        $industry = htmlspecialchars((string) ($quote['industry'] ?? ''), ENT_QUOTES, 'UTF-8');
        $company = htmlspecialchars(get_company_name(), ENT_QUOTES, 'UTF-8');
        $generated = date('d M Y, h:i A');
        $total_setup = subscription_builder_quote_money($quote['total_setup'] ?? 0);
        $total_monthly = subscription_builder_quote_money($quote['total_monthly'] ?? 0);

        $setup_rows = '';
        foreach ((array) ($quote['setup_lines'] ?? array()) as $line) {
            $label = htmlspecialchars((string) ($line['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            $amount = subscription_builder_quote_money($line['amount'] ?? 0);
            $setup_rows .= '<tr><td>' . $label . '</td><td class="amt">' . $amount . '</td></tr>';
        }
        if ($setup_rows === '') {
            $setup_rows = '<tr><td colspan="2" class="muted">None selected</td></tr>';
        }

        $monthly_rows = '';
        foreach ((array) ($quote['monthly_lines'] ?? array()) as $line) {
            $label = htmlspecialchars((string) ($line['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            $amount = subscription_builder_quote_money($line['amount'] ?? 0);
            $monthly_rows .= '<tr><td>' . $label . '</td><td class="amt">' . $amount . '</td></tr>';
        }
        if ($monthly_rows === '') {
            $monthly_rows = '<tr><td colspan="2" class="muted">None selected</td></tr>';
        }

        $included_html = '';
        $included = (array) ($quote['included'] ?? array());
        if (!empty($included)) {
            $included_html .= '<h3>Included Features (No extra charges)</h3><ul class="included">';
            foreach ($included as $row) {
                $mod = htmlspecialchars((string) ($row['module'] ?? ''), ENT_QUOTES, 'UTF-8');
                $feat = htmlspecialchars((string) ($row['feature'] ?? ''), ENT_QUOTES, 'UTF-8');
                $included_html .= '<li><strong>' . $mod . ':</strong> ' . $feat . '</li>';
            }
            $included_html .= '</ul>';
        }

        $print_btn = '';
        if ($for_print) {
            $print_btn = '<p class="no-print"><button onclick="window.print()">Print / Save as PDF</button></p>';
        }

        return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Subscription Quote — ' . $plan . '</title>
<style>
  body { font-family: Arial, sans-serif; color: #111; margin: 24px; font-size: 13px; }
  h1 { font-size: 20px; margin: 0 0 4px; color: #5b4fc7; }
  h2 { font-size: 14px; margin: 20px 0 8px; color: #374151; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
  h3 { font-size: 13px; margin: 16px 0 8px; }
  .meta { color: #6b7280; margin-bottom: 16px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
  th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
  th { background: #f9fafb; }
  td.amt, th.amt { text-align: right; white-space: nowrap; }
  .total-box { background: #dcfce7; border: 1px solid #bbf7d0; padding: 12px; margin: 8px 0; border-radius: 6px; }
  .total-box strong { font-size: 18px; color: #16a34a; }
  .muted { color: #9ca3af; font-style: italic; }
  .included { columns: 2; margin: 0; padding-left: 18px; }
  .included li { margin-bottom: 4px; break-inside: avoid; }
  .note { font-size: 11px; color: #6b7280; margin-top: 16px; }
  @media print { .no-print { display: none !important; } body { margin: 12px; } }
</style>
</head>
<body>
' . $print_btn . '
<h1>Subscription Quotation</h1>
<div class="meta">
  <div><strong>' . $company . '</strong></div>
  <div>Plan: <strong>' . $plan . '</strong> &nbsp;|&nbsp; Industry: <strong>' . $industry . '</strong></div>
  <div>Generated: ' . $generated . '</div>
</div>
' . $included_html . '
<h2>Setup Charges (One Time)</h2>
<table><thead><tr><th>Item</th><th class="amt">Amount</th></tr></thead><tbody>' . $setup_rows . '</tbody></table>
<div class="total-box">Net Setup Payable: <strong>' . $total_setup . '</strong></div>
<h2>Monthly Charges</h2>
<table><thead><tr><th>Item</th><th class="amt">Amount</th></tr></thead><tbody>' . $monthly_rows . '</tbody></table>
<div class="total-box">Net Monthly Payable: <strong>' . $total_monthly . '</strong></div>
<p class="note">All prices are exclusive of applicable taxes.</p>
</body>
</html>';
    }
}

if (!function_exists('subscription_builder_quote_send_pdf')) {
    function subscription_builder_quote_send_pdf($html, $filename)
    {
        if (class_exists('\\Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $dompdf->output();
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/\.pdf$/i', '.html', $filename) . '"');
        echo $html;
        exit;
    }
}
