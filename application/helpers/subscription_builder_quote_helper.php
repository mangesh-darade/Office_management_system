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

if (!function_exists('subscription_builder_quote_logo_data_uri')) {
    function subscription_builder_quote_logo_data_uri($logo_path)
    {
        $logo_path = trim((string) $logo_path);
        if ($logo_path === '') {
            return '';
        }

        $absolute = FCPATH . ltrim(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $logo_path), DIRECTORY_SEPARATOR);
        if (!is_file($absolute) || !is_readable($absolute)) {
            return '';
        }

        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $absolute);
                finfo_close($finfo);
                if (is_string($detected) && strpos($detected, 'image/') === 0) {
                    $mime = $detected;
                }
            }
        }

        if ($mime === '') {
            $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
            $map = array(
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
            );
            $mime = isset($map[$ext]) ? $map[$ext] : 'image/png';
        }

        $binary = @file_get_contents($absolute);
        if ($binary === false || $binary === '') {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }
}

if (!function_exists('subscription_builder_quote_resolve_logo_path')) {
    function subscription_builder_quote_resolve_logo_path()
    {
        $candidates = array(
            'uploads/settings/branding/sateri-digital-logo-quote-header.png',
            'uploads/settings/d0c4bd77a2b7606a41fca0a6bfda35e4.png',
            'uploads/settings/branding/sateri-digital-logo-transparent.png',
            'uploads/settings/branding/sateri-digital-logo-2000w.png',
        );
        foreach ($candidates as $candidate) {
            $absolute = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            if (is_file($absolute)) {
                return $candidate;
            }
        }

        $path = trim((string) get_company_logo());
        if ($path !== '') {
            $absolute = FCPATH . ltrim(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
            if (is_file($absolute)) {
                return $path;
            }
        }

        return $path;
    }
}

if (!function_exists('subscription_builder_quote_reference')) {
    function subscription_builder_quote_reference($quote)
    {
        $plan = preg_replace('/[^a-z0-9]+/i', '', (string) ($quote['plan'] ?? 'PLAN'));
        $plan = strtoupper(substr($plan, 0, 4));
        if ($plan === '') {
            $plan = 'PLAN';
        }
        return 'EP-' . date('Ymd') . '-' . $plan . '-' . substr(md5(json_encode($quote)), 0, 6);
    }
}

if (!function_exists('subscription_builder_quote_compute_financials')) {
    function subscription_builder_quote_compute_financials($quote)
    {
        $setup_subtotal = (float) ($quote['total_setup'] ?? 0);
        $monthly_subtotal = (float) ($quote['total_monthly'] ?? 0);
        $discount_percent = (float) ($quote['discount_percent'] ?? 0);
        if ($discount_percent < 0) {
            $discount_percent = 0;
        }
        if ($discount_percent > 100) {
            $discount_percent = 100;
        }
        $gst_percent = (float) ($quote['gst_percent'] ?? 0);
        if (!in_array($gst_percent, array(0, 5, 18), true)) {
            $gst_percent = 0;
        }

        $discount_setup = round($setup_subtotal * $discount_percent / 100, 2);
        $discount_monthly = round($monthly_subtotal * $discount_percent / 100, 2);
        $setup_after_discount = round($setup_subtotal - $discount_setup, 2);
        $monthly_after_discount = round($monthly_subtotal - $discount_monthly, 2);
        $gst_setup = round($setup_after_discount * $gst_percent / 100, 2);
        $gst_monthly = round($monthly_after_discount * $gst_percent / 100, 2);
        $net_setup = round($setup_after_discount + $gst_setup, 2);
        $net_monthly = round($monthly_after_discount + $gst_monthly, 2);

        return array(
            'setup_subtotal' => $setup_subtotal,
            'monthly_subtotal' => $monthly_subtotal,
            'discount_percent' => $discount_percent,
            'discount_setup' => $discount_setup,
            'discount_monthly' => $discount_monthly,
            'setup_after_discount' => $setup_after_discount,
            'monthly_after_discount' => $monthly_after_discount,
            'gst_percent' => $gst_percent,
            'gst_setup' => $gst_setup,
            'gst_monthly' => $gst_monthly,
            'net_setup' => $net_setup,
            'net_monthly' => $net_monthly,
        );
    }
}

if (!function_exists('subscription_builder_quote_group_included')) {
    function subscription_builder_quote_group_included($included)
    {
        $groups = array();
        foreach ((array) $included as $row) {
            $module = trim((string) ($row['module'] ?? 'General'));
            if ($module === '') {
                $module = 'General';
            }
            if (!isset($groups[$module])) {
                $groups[$module] = array();
            }
            $feature = trim((string) ($row['feature'] ?? ''));
            if ($feature !== '') {
                $groups[$module][] = $feature;
            }
        }
        ksort($groups);
        return $groups;
    }
}

if (!function_exists('subscription_builder_quote_render_line_rows')) {
    function subscription_builder_quote_render_line_rows($lines, $type_label)
    {
        $rows = '';
        $index = 0;
        foreach ((array) $lines as $line) {
            $index++;
            $module = esc_view((string) ($line['module'] ?? '—'), ENT_QUOTES, 'UTF-8');
            $label = esc_view((string) ($line['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            $qty = (int) ($line['qty'] ?? 0);
            $qty_display = $qty > 0 ? (string) $qty : '—';
            $amount = subscription_builder_quote_money($line['amount'] ?? 0);
            $rows .= '<tr>'
                . '<td class="col-num">' . $index . '</td>'
                . '<td>' . $module . '</td>'
                . '<td>' . $label . '</td>'
                . '<td class="col-qty">' . $qty_display . '</td>'
                . '<td class="col-amt">' . $amount . '</td>'
                . '</tr>';
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="5" class="empty-row">No ' . esc_view($type_label) . ' selected</td></tr>';
        }

        return $rows;
    }
}

if (!function_exists('subscription_builder_quote_render_html')) {
    function subscription_builder_quote_render_html($quote, $for_print = false)
    {
        $CI =& get_instance();
        $CI->load->helper('company');

        $company_name = esc_view(get_company_name(), ENT_QUOTES, 'UTF-8');
        $company_email = esc_view(get_company_email(), ENT_QUOTES, 'UTF-8');
        $company_phone = esc_view(get_company_phone(), ENT_QUOTES, 'UTF-8');
        $company_address_raw = trim(get_company_address());
        $company_address = nl2br(esc_view($company_address_raw));

        $logo_uri = subscription_builder_quote_logo_data_uri(subscription_builder_quote_resolve_logo_path());
        $logo_html = '';
        if ($logo_uri !== '') {
            $logo_html = '<img src="' . $logo_uri . '" alt="' . $company_name . '" class="sq-logo" width="211" height="84" />';
        }

        $plan = esc_view((string) ($quote['plan_display'] ?? $quote['plan'] ?? ''), ENT_QUOTES, 'UTF-8');
        $industry = esc_view((string) ($quote['industry'] ?? ''), ENT_QUOTES, 'UTF-8');
        $client_name = esc_view(trim((string) ($quote['client_name'] ?? '')), ENT_QUOTES, 'UTF-8');
        $client_business = esc_view(trim((string) ($quote['client_business'] ?? '')), ENT_QUOTES, 'UTF-8');
        $quote_ref = esc_view(subscription_builder_quote_reference($quote), ENT_QUOTES, 'UTF-8');
        $generated_date = date('d M Y');
        $generated_time = date('h:i A');
        $valid_until = date('d M Y', strtotime('+30 days'));
        $site_url = esc_view('https://sateridigital.com');
        $site_url_link = '<a href="' . $site_url . '" class="sq-website-link" target="_blank" rel="noopener noreferrer">' . $site_url . '</a>';

        $company_block = '<div class="sq-company-name">' . $company_name . '</div>'
            . '<div class="sq-company-meta">'
            . ($company_address !== '' ? $company_address . '<br>' : '')
            . ($company_phone !== '' ? 'Phone: ' . $company_phone . ' &nbsp;|&nbsp; ' : '')
            . ($company_email !== '' ? 'Email: ' . $company_email . '<br>' : '')
            . 'Website: ' . $site_url_link
            . '</div>';

        if ($logo_html === '') {
            $header_row = '<tr><td class="sq-header-cell" colspan="3">' . $company_block . '</td></tr>';
        } else {
            $header_row = '<tr>'
                . '<td class="sq-logo-cell">' . $logo_html . '</td>'
                . '<td class="sq-company-cell">' . $company_block . '</td>'
                . '<td class="sq-header-spacer">&nbsp;</td>'
                . '</tr>';
        }

        $financials = subscription_builder_quote_compute_financials($quote);
        $total_setup = subscription_builder_quote_money($financials['net_setup']);
        $total_monthly = subscription_builder_quote_money($financials['net_monthly']);
        $setup_subtotal_display = subscription_builder_quote_money($financials['setup_subtotal']);
        $monthly_subtotal_display = subscription_builder_quote_money($financials['monthly_subtotal']);
        $discount_setup_display = subscription_builder_quote_money($financials['discount_setup']);
        $discount_monthly_display = subscription_builder_quote_money($financials['discount_monthly']);
        $gst_setup_display = subscription_builder_quote_money($financials['gst_setup']);
        $gst_monthly_display = subscription_builder_quote_money($financials['gst_monthly']);
        $discount_percent_display = rtrim(rtrim(number_format($financials['discount_percent'], 2, '.', ''), '0'), '.');
        $gst_percent_display = rtrim(rtrim(number_format($financials['gst_percent'], 0, '.', ''), '0'), '.');

        $client_meta = '';
        if ($client_name !== '' || $client_business !== '') {
            $client_meta = '<tr>'
                . '<td>'
                . '<div class="sq-meta-label">Client Name</div>'
                . '<div class="sq-meta-value">' . ($client_name !== '' ? $client_name : '—') . '</div>'
                . '</td>'
                . '<td>'
                . '<div class="sq-meta-label">Client Business</div>'
                . '<div class="sq-meta-value">' . ($client_business !== '' ? $client_business : '—') . '</div>'
                . '</td>'
                . '</tr>';
        }

        $setup_rows = subscription_builder_quote_render_line_rows($quote['setup_lines'] ?? array(), 'setup charges');
        $monthly_rows = subscription_builder_quote_render_line_rows($quote['monthly_lines'] ?? array(), 'monthly charges');

        $financial_summary_html = '<div class="sq-section">'
            . '<div class="sq-section-title">Financial Summary</div>'
            . '<table class="sq-charges-table">'
            . '<thead><tr><th>Type</th><th class="col-amt">Subtotal</th><th class="col-amt">Discount (' . $discount_percent_display . '%)</th><th class="col-amt">GST (' . $gst_percent_display . '%)</th><th class="col-amt">Net Payable</th></tr></thead>'
            . '<tbody>'
            . '<tr><td>One-Time Setup</td><td class="col-amt">' . $setup_subtotal_display . '</td><td class="col-amt">-' . $discount_setup_display . '</td><td class="col-amt">' . $gst_setup_display . '</td><td class="col-amt">' . $total_setup . '</td></tr>'
            . '<tr><td>Monthly Recurring</td><td class="col-amt">' . $monthly_subtotal_display . '</td><td class="col-amt">-' . $discount_monthly_display . '</td><td class="col-amt">' . $gst_monthly_display . '</td><td class="col-amt">' . $total_monthly . '</td></tr>'
            . '</tbody></table></div>';

        $included_groups = subscription_builder_quote_group_included($quote['included'] ?? array());
        $included_html = '';
        if (!empty($included_groups)) {
            $included_html .= '<div class="sq-section">'
                . '<div class="sq-section-title">Included in ' . $plan . ' <span class="sq-badge">No extra charges</span></div>';
            foreach ($included_groups as $module => $features) {
                $mod_esc = esc_view($module);
                $included_html .= '<div class="sq-included-group">'
                    . '<div class="sq-included-module">' . $mod_esc . '</div>'
                    . '<table class="sq-included-table"><tr><td>';
                $chips = '';
                foreach ($features as $feature) {
                    $feat_esc = esc_view($feature);
                    $chips .= '<span class="sq-chip">' . $feat_esc . '</span>';
                }
                $included_html .= $chips . '</td></tr></table></div>';
            }
            $included_html .= '</div>';
        }

        $print_btn = '';
        if ($for_print) {
            $print_btn = '<div class="sq-toolbar no-print">'
                . '<button type="button" onclick="window.print()">Print / Save as PDF</button>'
                . '</div>';
        }

        return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ElintOm Proposal — ' . $plan . '</title>
<style>
  @page { margin: 14mm 12mm; }
  * { box-sizing: border-box; }
  :root {
    --sq-brand: #0F0B45;
    --sq-brand-light: #e8e7f3;
    --sq-brand-accent: #1a1560;
  }
  body {
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
    color: #1f2937;
    margin: 0;
    padding: 0;
    font-size: 11px;
    line-height: 1.45;
    background: #f3f4f6;
  }
  .sq-page {
    max-width: 820px;
    margin: 0 auto;
    background: #fff;
    border: 1px solid #e5e7eb;
  }
  .sq-toolbar {
    max-width: 820px;
    margin: 12px auto 0;
    text-align: right;
  }
  .sq-toolbar button {
    background: var(--sq-brand);
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 16px;
    font-size: 12px;
    cursor: pointer;
  }
  .sq-header-band {
    background: var(--sq-brand);
    color: #fff;
    padding: 10px 18px;
  }
  .sq-header-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
  }
  .sq-header-table td {
    vertical-align: middle;
    border: none;
    padding: 0;
  }
  .sq-header-cell {
    text-align: center;
    vertical-align: middle;
    padding: 0;
  }
  .sq-logo-cell {
    width: 211px;
    vertical-align: middle;
    text-align: left;
    padding: 0;
  }
  .sq-header-spacer {
    width: 211px;
    vertical-align: middle;
    padding: 0;
  }
  .sq-logo {
    display: block;
    width: 211px;
    height: 84px;
    max-width: 211px;
    max-height: 84px;
    margin: 0;
    object-fit: contain;
    object-position: left center;
    background: transparent;
    padding: 0;
    border: 0;
    border-radius: 0;
  }
  .sq-company-cell {
    text-align: center;
    vertical-align: middle;
    padding: 0 8px;
  }
  .sq-company-name {
    font-size: 18px;
    font-weight: 700;
    letter-spacing: 0.4px;
    margin: 0 0 2px;
    text-align: center;
    line-height: 1.2;
  }
  .sq-company-meta {
    font-size: 10px;
    opacity: 0.95;
    line-height: 1.35;
    text-align: center;
    margin: 0;
  }
  .sq-header-band a.sq-website-link {
    color: #fff;
    text-decoration: underline;
  }
  .sq-footer a.sq-website-link {
    color: var(--sq-brand);
    text-decoration: underline;
  }
  .sq-title-bar {
    background: var(--sq-brand-light);
    border-top: 2px solid var(--sq-brand);
    border-bottom: 2px solid var(--sq-brand);
    text-align: center;
    font-weight: 700;
    font-size: 13px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--sq-brand);
    padding: 7px 12px;
  }
  .sq-body {
    padding: 16px 22px 20px;
  }
  .sq-meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
  }
  .sq-meta-table td {
    border: 1px solid #d1d5db;
    padding: 8px 10px;
    vertical-align: top;
    width: 50%;
  }
  .sq-meta-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #6b7280;
    margin-bottom: 2px;
  }
  .sq-meta-value {
    font-size: 12px;
    font-weight: 600;
    color: #111827;
  }
  .sq-meta-sub {
    font-size: 10px;
    color: #4b5563;
    margin-top: 2px;
  }
  .sq-section {
    margin-bottom: 14px;
  }
  .sq-section-title {
    font-size: 12px;
    font-weight: 700;
    color: #374151;
    border-bottom: 2px solid var(--sq-brand);
    padding-bottom: 4px;
    margin-bottom: 8px;
  }
  .sq-badge {
    display: inline-block;
    background: #dcfce7;
    color: #15803d;
    font-size: 9px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: 6px;
    vertical-align: middle;
    text-transform: none;
    letter-spacing: 0;
  }
  .sq-included-group {
    margin-bottom: 8px;
  }
  .sq-included-module {
    font-size: 10px;
    font-weight: 700;
    color: var(--sq-brand);
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
  }
  .sq-included-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 4px;
  }
  .sq-included-table td {
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    padding: 6px 8px;
  }
  .sq-chip {
    display: inline-block;
    background: #fff;
    border: 1px solid #d1fae5;
    color: #166534;
    border-radius: 4px;
    padding: 2px 7px;
    margin: 2px 4px 2px 0;
    font-size: 10px;
  }
  .sq-charges-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
  }
  .sq-charges-table th,
  .sq-charges-table td {
    border: 1px solid #d1d5db;
    padding: 6px 8px;
    text-align: left;
  }
  .sq-charges-table th {
    background: var(--sq-brand);
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }
  .sq-charges-table tr:nth-child(even) td {
    background: #f9fafb;
  }
  .col-num { width: 32px; text-align: center !important; }
  .col-qty { width: 48px; text-align: center !important; }
  .col-amt { width: 110px; text-align: right !important; white-space: nowrap; }
  .empty-row {
    color: #9ca3af;
    font-style: italic;
    text-align: center;
  }
  .sq-totals-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4px;
  }
  .sq-totals-table td {
    border: 1px solid #bbf7d0;
    padding: 10px 12px;
  }
  .sq-total-setup {
    background: #f0fdf4;
  }
  .sq-total-monthly {
    background: #ecfdf5;
  }
  .sq-total-label {
    font-size: 11px;
    color: #374151;
    font-weight: 600;
  }
  .sq-total-amount {
    text-align: right;
    font-size: 16px;
    font-weight: 700;
    color: #16a34a;
    white-space: nowrap;
  }
  .sq-notes {
    margin-top: 14px;
    padding: 10px 12px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-left: 3px solid var(--sq-brand);
    font-size: 10px;
    color: #4b5563;
  }
  .sq-notes strong {
    display: block;
    color: #374151;
    margin-bottom: 4px;
    font-size: 10px;
  }
  .sq-notes ul {
    margin: 4px 0 0;
    padding-left: 16px;
  }
  .sq-notes li { margin-bottom: 2px; }
  .sq-footer {
    border-top: 1px solid #e5e7eb;
    padding: 10px 22px 14px;
    text-align: center;
    font-size: 9px;
    color: #6b7280;
    background: #fafafa;
  }
  .sq-footer strong { color: var(--sq-brand); }
  @media print {
    .no-print { display: none !important; }
    body { background: #fff; }
    .sq-page { border: none; max-width: none; margin: 0; }
    .sq-toolbar { display: none; }
  }
  @media screen {
    .sq-page { margin: 12px auto 24px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
  }
</style>
</head>
<body>
' . $print_btn . '
<div class="sq-page">
  <div class="sq-header-band">
    <table class="sq-header-table">
      ' . $header_row . '
    </table>
  </div>

  <div class="sq-title-bar">ElintOm Proposal</div>

  <div class="sq-body">
    <table class="sq-meta-table">
      <tr>
        <td>
          <div class="sq-meta-label">Proposal Reference</div>
          <div class="sq-meta-value">' . $quote_ref . '</div>
          <div class="sq-meta-sub">Generated: ' . $generated_date . ' at ' . $generated_time . '</div>
        </td>
        <td>
          <div class="sq-meta-label">Valid Until</div>
          <div class="sq-meta-value">' . $valid_until . '</div>
          <div class="sq-meta-sub">Subject to acceptance within validity period</div>
        </td>
      </tr>
      ' . $client_meta . '
      <tr>
        <td>
          <div class="sq-meta-label">Subscription Plan</div>
          <div class="sq-meta-value">' . $plan . '</div>
        </td>
        <td>
          <div class="sq-meta-label">Industry</div>
          <div class="sq-meta-value">' . $industry . '</div>
        </td>
      </tr>
    </table>

    ' . $included_html . '

    <div class="sq-section">
      <div class="sq-section-title">One-Time Setup Charges</div>
      <table class="sq-charges-table">
        <thead>
          <tr>
            <th class="col-num">#</th>
            <th>Module</th>
            <th>Feature / Item</th>
            <th class="col-qty">Qty</th>
            <th class="col-amt">Amount (INR)</th>
          </tr>
        </thead>
        <tbody>' . $setup_rows . '</tbody>
      </table>
      <table class="sq-totals-table">
        <tr class="sq-total-setup">
          <td class="sq-total-label">Net Setup Payable (One Time)</td>
          <td class="sq-total-amount">' . $total_setup . '</td>
        </tr>
      </table>
    </div>

    <div class="sq-section">
      <div class="sq-section-title">Recurring Monthly Charges</div>
      <table class="sq-charges-table">
        <thead>
          <tr>
            <th class="col-num">#</th>
            <th>Module</th>
            <th>Feature / Item</th>
            <th class="col-qty">Qty</th>
            <th class="col-amt">Amount (INR)</th>
          </tr>
        </thead>
        <tbody>' . $monthly_rows . '</tbody>
      </table>
      <table class="sq-totals-table">
        <tr class="sq-total-monthly">
          <td class="sq-total-label">Net Monthly Payable</td>
          <td class="sq-total-amount">' . $total_monthly . '</td>
        </tr>
      </table>
    </div>

    ' . $financial_summary_html . '

    <div class="sq-notes">
      <strong>Terms &amp; Notes</strong>
      <ul>
        <li>Net payable amounts include GST at ' . $gst_percent_display . '% where applicable.</li>
        <li>Discount of ' . $discount_percent_display . '% applied on subtotals before GST.</li>
        <li>Included features are part of the selected plan and carry no additional charge unless upgraded.</li>
        <li>Setup charges are payable once; monthly charges are recurring as per billing cycle.</li>
        <li>This proposal is computer-generated and valid until ' . esc_view($valid_until) . ' unless revised.</li>
      </ul>
    </div>
  </div>

  <div class="sq-footer">
    <strong>' . $company_name . '</strong> &mdash; ElintOm Proposal<br>
    ' . $site_url_link . ' &nbsp;|&nbsp; ' . $company_email . ' &nbsp;|&nbsp; ' . $company_phone . '
  </div>
</div>
</body>
</html>';
    }
}

if (!function_exists('subscription_builder_quote_save_pdf')) {
    function subscription_builder_quote_save_pdf($html, $filename)
    {
        $upload_dir = FCPATH . 'uploads/elintom_proposals/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0755, true);
        }

        $safe_name = preg_replace('/[^a-z0-9._-]+/i', '_', (string) $filename);
        if ($safe_name === '') {
            $safe_name = 'elintom_proposal_' . date('Ymd_His') . '.pdf';
        }
        if (!preg_match('/\.pdf$/i', $safe_name)) {
            $safe_name .= '.pdf';
        }

        $absolute_path = $upload_dir . $safe_name;
        $relative_path = 'uploads/elintom_proposals/' . $safe_name;

        if (class_exists('\\Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            if (@file_put_contents($absolute_path, $dompdf->output()) !== false) {
                return $relative_path;
            }
            return '';
        }

        $html_name = preg_replace('/\.pdf$/i', '.html', $safe_name);
        $html_path = $upload_dir . $html_name;
        if (@file_put_contents($html_path, $html) !== false) {
            return 'uploads/elintom_proposals/' . $html_name;
        }

        return '';
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
