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
    function subscription_builder_quote_money($amount, $quote = array())
    {
        $symbol = '₹';
        if (is_array($quote)) {
            if (!empty($quote['currency_symbol'])) {
                $symbol = trim((string) $quote['currency_symbol']);
            } elseif (!empty($quote['currency_code'])) {
                $symbol = trim((string) $quote['currency_code']);
            }
        }
        return $symbol . ' ' . number_format((float) $amount, 0, '.', ',');
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

if (!function_exists('subscription_builder_quote_issuer_profile')) {
    /**
     * Legal issuer details for ElintOm proposal exports.
     *
     * @return array{name:string,address:string,cin:string,gst:string,website:string,email:string,phone:string}
     */
    function subscription_builder_quote_issuer_profile()
    {
        $CI =& get_instance();
        $CI->load->helper('company');

        $email = trim((string) get_company_email());
        $phone = trim((string) get_company_phone());
        if ($email === '' || $email === 'noreply@example.com') {
            $email = 'sateri.mangesh@gmail.com';
        }
        if ($phone === '' || $phone === '+1-234-567-8900') {
            $phone = '7744010738';
        }

        return array(
            'name'    => 'SATERI DIGITAL PRIVATE LIMITED',
            'address' => "2nd Floor, Neelprabha Apt, Right Bhusari Colony,\nPaud Road, Pune, Maharashtra, India - 411038.",
            'cin'     => 'U72900PN2022PTC215872',
            'gst'     => '27ABJCS6705P1ZM',
            'website' => 'https://sateridigital.com',
            'email'   => $email,
            'phone'   => $phone,
        );
    }
}

if (!function_exists('subscription_builder_quote_normalize_gst_percent')) {
    function subscription_builder_quote_normalize_gst_percent($value)
    {
        $gst_percent = (float) $value;
        if (!in_array($gst_percent, array(0, 5, 18), true)) {
            $gst_percent = 0;
        }
        return $gst_percent;
    }
}

if (!function_exists('subscription_builder_quote_normalize_discount_values')) {
    function subscription_builder_quote_normalize_discount_values($discount_percent, $discount_flat)
    {
        $discount_percent = (float) $discount_percent;
        if ($discount_percent < 0) {
            $discount_percent = 0;
        }
        if ($discount_percent > 100) {
            $discount_percent = 100;
        }

        $discount_flat = (float) $discount_flat;
        if ($discount_flat < 0) {
            $discount_flat = 0;
        }

        return array(
            'percent' => $discount_percent,
            'flat' => $discount_flat,
        );
    }
}

if (!function_exists('subscription_builder_quote_calc_discount_amount')) {
    function subscription_builder_quote_calc_discount_amount($subtotal, $discount_percent, $discount_flat)
    {
        $normalized = subscription_builder_quote_normalize_discount_values($discount_percent, $discount_flat);
        $pct_amount = round($subtotal * $normalized['percent'] / 100, 2);
        $discount_amount = $pct_amount + $normalized['flat'];
        if ($discount_amount > $subtotal) {
            $discount_amount = $subtotal;
        }
        return $discount_amount;
    }
}

if (!function_exists('subscription_builder_quote_has_per_section_financials')) {
    function subscription_builder_quote_has_per_section_financials($quote)
    {
        $keys = array(
            'setup_discount_percent',
            'setup_discount_flat',
            'setup_gst_percent',
            'monthly_discount_percent',
            'monthly_discount_flat',
            'monthly_gst_percent',
        );
        foreach ($keys as $key) {
            if (array_key_exists($key, $quote)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('subscription_builder_quote_split_flat_discount')) {
    function subscription_builder_quote_split_flat_discount($flat_total, $setup_subtotal, $monthly_subtotal)
    {
        $combined = $setup_subtotal + $monthly_subtotal;
        if ($flat_total > $combined) {
            $flat_total = $combined;
        }
        if ($combined <= 0 || $flat_total <= 0) {
            return array('setup' => 0.0, 'monthly' => 0.0);
        }

        $setup_share = round($flat_total * $setup_subtotal / $combined, 2);
        return array(
            'setup' => $setup_share,
            'monthly' => round($flat_total - $setup_share, 2),
        );
    }
}

if (!function_exists('subscription_builder_quote_compute_financials')) {
    function subscription_builder_quote_compute_financials($quote)
    {
        $setup_subtotal = (float) ($quote['total_setup'] ?? 0);
        $monthly_subtotal = (float) ($quote['total_monthly'] ?? 0);

        if (subscription_builder_quote_has_per_section_financials($quote)) {
            $setup_discount = subscription_builder_quote_normalize_discount_values(
                $quote['setup_discount_percent'] ?? 0,
                $quote['setup_discount_flat'] ?? 0
            );
            $monthly_discount = subscription_builder_quote_normalize_discount_values(
                $quote['monthly_discount_percent'] ?? 0,
                $quote['monthly_discount_flat'] ?? 0
            );
            $setup_gst_percent = subscription_builder_quote_normalize_gst_percent($quote['setup_gst_percent'] ?? 0);
            $monthly_gst_percent = subscription_builder_quote_normalize_gst_percent($quote['monthly_gst_percent'] ?? 0);

            $discount_setup = subscription_builder_quote_calc_discount_amount(
                $setup_subtotal,
                $setup_discount['percent'],
                $setup_discount['flat']
            );
            $discount_monthly = subscription_builder_quote_calc_discount_amount(
                $monthly_subtotal,
                $monthly_discount['percent'],
                $monthly_discount['flat']
            );
        } else {
            $legacy_discount = subscription_builder_quote_normalize_discount_values(
                $quote['discount_percent'] ?? 0,
                $quote['discount_flat'] ?? 0
            );
            $setup_gst_percent = subscription_builder_quote_normalize_gst_percent($quote['gst_percent'] ?? 0);
            $monthly_gst_percent = $setup_gst_percent;

            $pct_setup = round($setup_subtotal * $legacy_discount['percent'] / 100, 2);
            $pct_monthly = round($monthly_subtotal * $legacy_discount['percent'] / 100, 2);
            $flat_split = subscription_builder_quote_split_flat_discount(
                $legacy_discount['flat'],
                $setup_subtotal,
                $monthly_subtotal
            );
            $discount_setup = $pct_setup + $flat_split['setup'];
            $discount_monthly = $pct_monthly + $flat_split['monthly'];
            if ($discount_setup > $setup_subtotal) {
                $discount_setup = $setup_subtotal;
            }
            if ($discount_monthly > $monthly_subtotal) {
                $discount_monthly = $monthly_subtotal;
            }

            $setup_discount = array(
                'percent' => $legacy_discount['percent'],
                'flat' => $flat_split['setup'],
            );
            $monthly_discount = array(
                'percent' => $legacy_discount['percent'],
                'flat' => $flat_split['monthly'],
            );
        }

        $setup_after_discount = round(max(0, $setup_subtotal - $discount_setup), 2);
        $monthly_after_discount = round(max(0, $monthly_subtotal - $discount_monthly), 2);
        $gst_setup = round($setup_after_discount * $setup_gst_percent / 100, 2);
        $gst_monthly = round($monthly_after_discount * $monthly_gst_percent / 100, 2);
        $net_setup = round($setup_after_discount + $gst_setup, 2);
        $net_monthly = round($monthly_after_discount + $gst_monthly, 2);

        return array(
            'setup_subtotal' => $setup_subtotal,
            'monthly_subtotal' => $monthly_subtotal,
            'setup_discount_percent' => $setup_discount['percent'],
            'setup_discount_flat' => $setup_discount['flat'],
            'monthly_discount_percent' => $monthly_discount['percent'],
            'monthly_discount_flat' => $monthly_discount['flat'],
            'discount_percent' => $setup_discount['percent'],
            'discount_flat' => $setup_discount['flat'],
            'discount_setup' => $discount_setup,
            'discount_monthly' => $discount_monthly,
            'setup_after_discount' => $setup_after_discount,
            'monthly_after_discount' => $monthly_after_discount,
            'setup_gst_percent' => $setup_gst_percent,
            'monthly_gst_percent' => $monthly_gst_percent,
            'gst_percent' => $setup_gst_percent,
            'gst_setup' => $gst_setup,
            'gst_monthly' => $gst_monthly,
            'net_setup' => $net_setup,
            'net_monthly' => $net_monthly,
        );
    }
}

if (!function_exists('subscription_builder_quote_format_gst_display')) {
    function subscription_builder_quote_format_gst_display($value)
    {
        return rtrim(rtrim(number_format((float) $value, 0, '.', ''), '0'), '.');
    }
}

if (!function_exists('subscription_builder_quote_tax_header')) {
    function subscription_builder_quote_tax_header($financials, $quote = array())
    {
        $setup_gst_percent = (float) ($financials['setup_gst_percent'] ?? $financials['gst_percent'] ?? 0);
        $monthly_gst_percent = (float) ($financials['monthly_gst_percent'] ?? $financials['gst_percent'] ?? 0);
        if (subscription_builder_quote_has_per_section_financials($quote) && $setup_gst_percent !== $monthly_gst_percent) {
            return 'Tax';
        }

        return 'Tax (' . subscription_builder_quote_format_gst_display($setup_gst_percent) . '%)';
    }
}

if (!function_exists('subscription_builder_quote_tax_note')) {
    function subscription_builder_quote_tax_note($financials, $quote = array())
    {
        $setup_gst_percent = (float) ($financials['setup_gst_percent'] ?? $financials['gst_percent'] ?? 0);
        $monthly_gst_percent = (float) ($financials['monthly_gst_percent'] ?? $financials['gst_percent'] ?? 0);
        if (subscription_builder_quote_has_per_section_financials($quote) && $setup_gst_percent !== $monthly_gst_percent) {
            return 'Net payable amounts include Tax at '
                . subscription_builder_quote_format_gst_display($setup_gst_percent) . '% on setup and '
                . subscription_builder_quote_format_gst_display($monthly_gst_percent) . '% on monthly charges where applicable.';
        }

        return 'Net payable amounts include Tax at ' . subscription_builder_quote_format_gst_display($setup_gst_percent) . '% where applicable.';
    }
}

if (!function_exists('subscription_builder_quote_section_discount_label')) {
    function subscription_builder_quote_section_discount_label($discount_percent, $discount_flat, $quote = array())
    {
        $parts = array();
        $discount_percent = (float) $discount_percent;
        $discount_flat = (float) $discount_flat;
        if ($discount_percent > 0) {
            $parts[] = rtrim(rtrim(number_format($discount_percent, 2, '.', ''), '0'), '.') . '%';
        }
        if ($discount_flat > 0) {
            $parts[] = 'Flat ' . subscription_builder_quote_money($discount_flat, $quote);
        }
        if (empty($parts)) {
            return '';
        }

        return implode(' + ', $parts);
    }
}

if (!function_exists('subscription_builder_quote_discount_label')) {
    function subscription_builder_quote_discount_label($financials, $quote = array())
    {
        if (subscription_builder_quote_has_per_section_financials($quote)) {
            return 'Discount';
        }

        $parts = array();
        $discount_percent = (float) ($financials['discount_percent'] ?? 0);
        $discount_flat = (float) ($financials['discount_flat'] ?? 0);
        if ($discount_percent > 0) {
            $parts[] = rtrim(rtrim(number_format($discount_percent, 2, '.', ''), '0'), '.') . '%';
        }
        if ($discount_flat > 0) {
            $parts[] = 'Flat ' . subscription_builder_quote_money($discount_flat, $quote);
        }
        if (empty($parts)) {
            return 'Discount';
        }

        return 'Discount (' . implode(' + ', $parts) . ')';
    }
}

if (!function_exists('subscription_builder_quote_section_discount_note')) {
    function subscription_builder_quote_section_discount_note($section_label, $discount_percent, $discount_flat, $quote = array())
    {
        $parts = array();
        $discount_percent = (float) $discount_percent;
        $discount_flat = (float) $discount_flat;
        if ($discount_percent > 0) {
            $parts[] = rtrim(rtrim(number_format($discount_percent, 2, '.', ''), '0'), '.') . '% discount';
        }
        if ($discount_flat > 0) {
            $parts[] = 'flat discount of ' . subscription_builder_quote_money($discount_flat, $quote);
        }
        if (empty($parts)) {
            return 'No discount applied on ' . $section_label . ' subtotal before Tax.';
        }

        return ucfirst(implode(' and ', $parts)) . ' applied on ' . $section_label . ' subtotal before Tax.';
    }
}

if (!function_exists('subscription_builder_quote_discount_note')) {
    function subscription_builder_quote_discount_note($financials, $quote = array())
    {
        if (subscription_builder_quote_has_per_section_financials($quote)) {
            $notes = array(
                subscription_builder_quote_section_discount_note(
                    'one-time setup',
                    $financials['setup_discount_percent'] ?? 0,
                    $financials['setup_discount_flat'] ?? 0,
                    $quote
                ),
                subscription_builder_quote_section_discount_note(
                    'monthly recurring',
                    $financials['monthly_discount_percent'] ?? 0,
                    $financials['monthly_discount_flat'] ?? 0,
                    $quote
                ),
            );
            return implode(' ', $notes);
        }

        $parts = array();
        $discount_percent = (float) ($financials['discount_percent'] ?? 0);
        $discount_flat = (float) ($financials['discount_flat'] ?? 0);
        if ($discount_percent > 0) {
            $parts[] = rtrim(rtrim(number_format($discount_percent, 2, '.', ''), '0'), '.') . '% discount';
        }
        if ($discount_flat > 0) {
            $parts[] = 'flat discount of ' . subscription_builder_quote_money($discount_flat, $quote);
        }
        if (empty($parts)) {
            return 'No discount applied on subtotals before Tax.';
        }

        return ucfirst(implode(' and ', $parts)) . ' applied on subtotals before Tax.';
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
    function subscription_builder_quote_render_line_rows($lines, $type_label, $quote = array())
    {
        $rows = '';
        $index = 0;
        foreach ((array) $lines as $line) {
            $index++;
            $module = esc_view((string) ($line['module'] ?? '—'), ENT_QUOTES, 'UTF-8');
            $label = esc_view((string) ($line['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            $qty = (int) ($line['qty'] ?? 0);
            $qty_display = $qty > 0 ? (string) $qty : '—';
            $amount = subscription_builder_quote_money($line['amount'] ?? 0, $quote);
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
    function subscription_builder_quote_render_html($quote, $for_print = false, $options = array())
    {
        $CI =& get_instance();
        $CI->load->helper('company');

        $profile = subscription_builder_quote_issuer_profile();
        $company_name = esc_view($profile['name'], ENT_QUOTES, 'UTF-8');
        $company_email = esc_view($profile['email'], ENT_QUOTES, 'UTF-8');
        $company_phone = esc_view($profile['phone'], ENT_QUOTES, 'UTF-8');
        $company_cin = esc_view($profile['cin'], ENT_QUOTES, 'UTF-8');
        $company_gst = esc_view($profile['gst'], ENT_QUOTES, 'UTF-8');
        $company_address = nl2br(esc_view(trim($profile['address']), ENT_QUOTES, 'UTF-8'));

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
        $site_url = esc_view($profile['website'], ENT_QUOTES, 'UTF-8');
        $site_url_link = '<a href="' . $site_url . '" class="sq-website-link" target="_blank" rel="noopener noreferrer">' . $site_url . '</a>';

        $company_block = '<div class="sq-company-name">' . $company_name . '</div>'
            . '<div class="sq-company-meta">'
            . $company_address . '<br><br>'
            . 'CIN: ' . $company_cin . '<br>'
            . 'GST No.: ' . $company_gst
            . ($company_phone !== '' ? '<br>Phone: ' . $company_phone : '')
            . ($company_email !== '' ? ' &nbsp;|&nbsp; Email: ' . $company_email : '')
            . '<br>Website: ' . $site_url_link
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
        $total_setup = subscription_builder_quote_money($financials['net_setup'], $quote);
        $total_monthly = subscription_builder_quote_money($financials['net_monthly'], $quote);
        $setup_subtotal_display = subscription_builder_quote_money($financials['setup_subtotal'], $quote);
        $monthly_subtotal_display = subscription_builder_quote_money($financials['monthly_subtotal'], $quote);
        $discount_setup_display = subscription_builder_quote_money($financials['discount_setup'], $quote);
        $discount_monthly_display = subscription_builder_quote_money($financials['discount_monthly'], $quote);
        $gst_setup_display = subscription_builder_quote_money($financials['gst_setup'], $quote);
        $gst_monthly_display = subscription_builder_quote_money($financials['gst_monthly'], $quote);
        $discount_label = esc_view(subscription_builder_quote_discount_label($financials, $quote), ENT_QUOTES, 'UTF-8');
        $tax_header = esc_view(subscription_builder_quote_tax_header($financials, $quote), ENT_QUOTES, 'UTF-8');
        $tax_note = subscription_builder_quote_tax_note($financials, $quote);
        $discount_note = subscription_builder_quote_discount_note($financials, $quote);

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

        $setup_rows = subscription_builder_quote_render_line_rows($quote['setup_lines'] ?? array(), 'setup charges', $quote);
        $monthly_rows = subscription_builder_quote_render_line_rows($quote['monthly_lines'] ?? array(), 'monthly charges', $quote);

        $financial_summary_html = '<div class="sq-section">'
            . '<div class="sq-section-title">Financial Summary</div>'
            . '<table class="sq-charges-table">'
            . '<thead><tr><th>Type</th><th class="col-amt">Subtotal</th><th class="col-amt">' . $discount_label . '</th><th class="col-amt">' . $tax_header . '</th><th class="col-amt">Net Payable</th></tr></thead>'
            . '<tbody>'
            . '<tr><td>One-Time Setup</td><td class="col-amt">' . $setup_subtotal_display . '</td><td class="col-amt">-' . $discount_setup_display . '</td><td class="col-amt">' . $gst_setup_display . '</td><td class="col-amt">' . $total_setup . '</td></tr>'
            . '<tr><td>Monthly Recurring</td><td class="col-amt">' . $monthly_subtotal_display . '</td><td class="col-amt">-' . $discount_monthly_display . '</td><td class="col-amt">' . $gst_monthly_display . '</td><td class="col-amt">' . $total_monthly . '</td></tr>'
            . '</tbody></table></div>';

        $included_groups = subscription_builder_quote_group_included($quote['included'] ?? array());
        $included_html = '';
        if (!empty($included_groups)) {
            $included_html .= '<div class="sq-section">'
                . '<div class="sq-section-title">Included in ' . $plan . '</div>';
            foreach ($included_groups as $module => $features) {
                $mod_esc = esc_view($module);
                $feature_parts = array();
                foreach ($features as $feature) {
                    $feature_parts[] = esc_view($feature);
                }
                $features_text = implode(', ', $feature_parts);
                $included_html .= '<div class="sq-included-line">'
                    . '<span class="sq-included-module">' . $mod_esc . '</span>'
                    . ' <span class="sq-included-arrow">=&gt;</span> '
                    . '<span class="sq-included-features">' . $features_text . '</span>'
                    . '</div>';
            }
            $included_html .= '</div>';
        }

        $print_btn = '';
        if ($for_print) {
            $save_url = esc_view((string) ($options['save_url'] ?? ''), ENT_QUOTES, 'UTF-8');
            $proposals_url = esc_view((string) ($options['proposals_url'] ?? ''), ENT_QUOTES, 'UTF-8');
            $quote_payload = $options['quote_json'] ?? $quote;
            $quote_json = json_encode($quote_payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            $csrf_name = esc_view($CI->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8');
            $csrf_hash = esc_view($CI->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8');
            $proposals_link = '';
            if ($proposals_url !== '') {
                $proposals_link = '<a href="' . $proposals_url . '" class="sq-toolbar-link">View Saved Proposals</a>';
            }
            $print_btn = '<div class="sq-toolbar no-print">'
                . '<span id="sq-save-status" class="sq-save-status"></span>'
                . '<button type="button" id="sq-save-print-btn">Print / Save as PDF</button>'
                . $proposals_link
                . '</div>'
                . '<script>'
                . '(function(){'
                . 'var saveUrl=' . json_encode($save_url) . ';'
                . 'var quoteData=' . $quote_json . ';'
                . 'var csrfName=' . json_encode($csrf_name) . ';'
                . 'var csrfHash=' . json_encode($csrf_hash) . ';'
                . 'var btn=document.getElementById("sq-save-print-btn");'
                . 'var statusEl=document.getElementById("sq-save-status");'
                . 'if(!btn||!saveUrl){return;}'
                . 'btn.addEventListener("click",function(){'
                . 'btn.disabled=true;'
                . 'if(statusEl){statusEl.textContent="Saving proposal...";}'
                . 'var body="quote_json="+encodeURIComponent(JSON.stringify(quoteData))'
                . '+"&"+encodeURIComponent(csrfName)+"="+encodeURIComponent(csrfHash);'
                . 'fetch(saveUrl,{method:"POST",credentials:"same-origin",headers:{'
                . '"Content-Type":"application/x-www-form-urlencoded; charset=UTF-8",'
                . '"X-Requested-With":"XMLHttpRequest"'
                . '},body:body})'
                . '.then(function(r){return r.json();})'
                . '.then(function(res){'
                . 'if(res&&res.ok){'
                . 'if(statusEl){statusEl.textContent="Saved. Opening print dialog...";}'
                . 'window.print();'
                . '}else{'
                . 'var msg=(res&&res.error)?res.error:"Unable to save proposal.";'
                . 'if(statusEl){statusEl.textContent=msg;}'
                . 'alert(msg);'
                . '}'
                . '})'
                . '.catch(function(){'
                . 'if(statusEl){statusEl.textContent="Save failed.";}'
                . 'alert("Unable to save proposal.");'
                . '})'
                . '.finally(function(){btn.disabled=false;});'
                . '});'
                . '})();'
                . '</script>';
        }

        return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ElintOm Proposal — ' . $plan . '</title>
<style>
  @page { margin: 14mm 12mm; }
  * { box-sizing: border-box; }
  body {
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
    color: #1f2937;
    margin: 0;
    padding: 0;
    font-size: 11px;
    line-height: 1.45;
    background: #fff;
  }
  .sq-page {
    width: 100%;
    max-width: none;
    margin: 0;
    background: #fff;
    border: none;
  }
  .sq-toolbar {
    max-width: 820px;
    margin: 12px auto 0;
    text-align: right;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
  }
  .sq-save-status {
    font-size: 11px;
    color: #4b5563;
    margin-right: auto;
  }
  .sq-toolbar-link {
    font-size: 12px;
    color: #0F0B45;
    text-decoration: none;
    font-weight: 600;
  }
  .sq-toolbar-link:hover {
    text-decoration: underline;
  }
  .sq-toolbar button {
    background: #0F0B45;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 16px;
    font-size: 12px;
    cursor: pointer;
  }
  .sq-header-band {
    background: #0F0B45;
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
    padding: 0;
    border: 0;
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
    color: #0F0B45;
    text-decoration: underline;
  }
  .sq-title-bar {
    background: #e8e7f3;
    border-top: 2px solid #0F0B45;
    border-bottom: 2px solid #0F0B45;
    text-align: center;
    font-weight: 700;
    font-size: 13px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #0F0B45;
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
    border-bottom: 2px solid #0F0B45;
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
  .sq-included-line {
    margin-bottom: 6px;
    padding: 6px 8px;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    font-size: 10px;
    line-height: 1.5;
  }
  .sq-included-module {
    font-weight: 700;
    color: #0F0B45;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }
  .sq-included-arrow {
    color: #6b7280;
    font-weight: 600;
  }
  .sq-included-features {
    color: #166534;
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
    background: #0F0B45;
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
    border-left: 3px solid #0F0B45;
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
  .sq-footer strong { color: #0F0B45; }
  @media print {
    .no-print { display: none !important; }
    body { background: #fff; }
    .sq-page { border: none; max-width: none; margin: 0; }
    .sq-toolbar { display: none; }
  }
  @media screen {
    body { background: #f3f4f6; }
    .sq-page {
      max-width: 820px;
      margin: 12px auto 24px;
      border: 1px solid #e5e7eb;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }
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
        <li>' . esc_view($tax_note, ENT_QUOTES, 'UTF-8') . '</li>
        <li>' . esc_view($discount_note, ENT_QUOTES, 'UTF-8') . '</li>
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

if (!function_exists('subscription_builder_quote_ensure_proposals_dir')) {
    function subscription_builder_quote_ensure_proposals_dir()
    {
        $upload_dir = FCPATH . 'uploads/elintom_proposals/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }
        if (is_dir($upload_dir) && !is_writable($upload_dir)) {
            @chmod($upload_dir, 0777);
        }

        return is_dir($upload_dir) && is_writable($upload_dir);
    }
}

if (!function_exists('subscription_builder_quote_save_pdf')) {
    function subscription_builder_quote_save_pdf($html, $filename)
    {
        $CI =& get_instance();
        $CI->load->helper('dompdf_bootstrap');

        if (!subscription_builder_quote_ensure_proposals_dir()) {
            log_message('error', 'subscription_builder_quote_save_pdf: uploads/elintom_proposals is missing or not writable.');
            return '';
        }

        $upload_dir = FCPATH . 'uploads/elintom_proposals/';
        $safe_name = preg_replace('/[^a-z0-9._-]+/i', '_', (string) $filename);
        if ($safe_name === '') {
            $safe_name = 'elintom_proposal_' . date('Ymd_His') . '.pdf';
        }
        if (!preg_match('/\.pdf$/i', $safe_name)) {
            $safe_name .= '.pdf';
        }

        $absolute_path = $upload_dir . $safe_name;
        $relative_path = 'uploads/elintom_proposals/' . $safe_name;

        $pdf_binary = dompdf_render_html($html, 'A4', 'portrait');
        if ($pdf_binary === false) {
            log_message('error', 'subscription_builder_quote_save_pdf: Dompdf could not render proposal HTML.');
            return '';
        }
        if (@file_put_contents($absolute_path, $pdf_binary) === false) {
            log_message('error', 'subscription_builder_quote_save_pdf: failed writing ' . $absolute_path);
            return '';
        }

        return $relative_path;
    }
}

if (!function_exists('subscription_builder_quote_send_pdf')) {
    function subscription_builder_quote_send_pdf($html, $filename)
    {
        $CI =& get_instance();
        $CI->load->helper('dompdf_bootstrap');

        $safe_name = preg_replace('/[^a-z0-9._-]+/i', '_', basename((string) $filename));
        if ($safe_name === '') {
            $safe_name = 'elintom_proposal_' . date('Ymd_His') . '.pdf';
        }
        if (!preg_match('/\.pdf$/i', $safe_name)) {
            $safe_name .= '.pdf';
        }

        $pdf_binary = dompdf_render_html($html, 'A4', 'portrait');
        if ($pdf_binary === false) {
            return false;
        }

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $CI->output
            ->set_status_header(200)
            ->set_content_type('application/pdf')
            ->set_header('Content-Description: File Transfer')
            ->set_header('Content-Disposition: attachment; filename="' . $safe_name . '"')
            ->set_header('Content-Transfer-Encoding: binary')
            ->set_header('Content-Length: ' . strlen($pdf_binary))
            ->set_header('Cache-Control: private, must-revalidate, max-age=0')
            ->set_header('Pragma: public')
            ->set_header('Expires: 0')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_output($pdf_binary);

        return true;
    }
}

if (!function_exists('subscription_builder_quote_build_export_filename')) {
    function subscription_builder_quote_build_export_filename($quote, $extension)
    {
        $business = trim((string) ($quote['client_business'] ?? ''));
        if ($business === '') {
            $business = trim((string) ($quote['client_name'] ?? ''));
        }
        if ($business === '') {
            $business = trim((string) ($quote['plan'] ?? 'proposal'));
        }

        $slug = preg_replace('/[^a-z0-9]+/i', '_', $business);
        $slug = trim(strtolower($slug), '_');
        if ($slug === '') {
            $slug = 'proposal';
        }

        $ext = ltrim(strtolower((string) $extension), '.');
        if ($ext === '') {
            $ext = 'dat';
        }

        return 'elintom_' . $slug . '_' . date('Y-m-d') . '.' . $ext;
    }
}

if (!function_exists('subscription_builder_quote_render_export_html')) {
    function subscription_builder_quote_render_export_html($quote)
    {
        $CI =& get_instance();
        $CI->load->helper('company');

        $company_name = esc_view(get_company_name(), ENT_QUOTES, 'UTF-8');
        $plan = esc_view((string) ($quote['plan_display'] ?? $quote['plan'] ?? ''), ENT_QUOTES, 'UTF-8');
        $industry = esc_view((string) ($quote['industry'] ?? ''), ENT_QUOTES, 'UTF-8');
        $country = esc_view((string) ($quote['country'] ?? ''), ENT_QUOTES, 'UTF-8');
        $client_name = esc_view(trim((string) ($quote['client_name'] ?? '')), ENT_QUOTES, 'UTF-8');
        $client_business = esc_view(trim((string) ($quote['client_business'] ?? '')), ENT_QUOTES, 'UTF-8');
        $quote_ref = esc_view(subscription_builder_quote_reference($quote), ENT_QUOTES, 'UTF-8');
        $financials = subscription_builder_quote_compute_financials($quote);
        $discount_label = esc_view(subscription_builder_quote_discount_label($financials, $quote), ENT_QUOTES, 'UTF-8');
        $tax_header = esc_view(subscription_builder_quote_tax_header($financials, $quote), ENT_QUOTES, 'UTF-8');
        $tax_note = esc_view(subscription_builder_quote_tax_note($financials, $quote), ENT_QUOTES, 'UTF-8');
        $discount_note = esc_view(subscription_builder_quote_discount_note($financials, $quote), ENT_QUOTES, 'UTF-8');

        $setup_rows = subscription_builder_quote_render_line_rows($quote['setup_lines'] ?? array(), 'setup charges', $quote);
        $monthly_rows = subscription_builder_quote_render_line_rows($quote['monthly_lines'] ?? array(), 'monthly charges', $quote);

        $included_groups = subscription_builder_quote_group_included($quote['included'] ?? array());
        $included_html = '';
        foreach ($included_groups as $module => $features) {
            $feature_parts = array();
            foreach ($features as $feature) {
                $feature_parts[] = esc_view($feature);
            }
            $included_html .= '<tr><td>' . esc_view($module) . '</td><td>' . implode(', ', $feature_parts) . '</td></tr>';
        }
        if ($included_html === '') {
            $included_html = '<tr><td colspan="2">No included features selected</td></tr>';
        }

        $table_style = 'border-collapse:collapse;width:100%;';
        $th_style = 'border:1px solid #d1d5db;padding:6px;background:#f8fafc;text-align:left;';
        $td_style = 'border:1px solid #d1d5db;padding:6px;';
        $amt_style = $td_style . 'text-align:right;';
        $styles = '<style>'
            . 'table{border-collapse:collapse;width:100%;}'
            . 'th,td{border:1px solid #d1d5db;padding:6px;}'
            . 'th{background:#f8fafc;}'
            . '.col-amt{text-align:right;}'
            . '.col-qty,.col-num{text-align:center;}'
            . '</style>';

        return '<html><head><meta charset="UTF-8">' . $styles . '</head><body>'
            . '<h2>' . $company_name . ' - ElintOm Proposal</h2>'
            . '<p><strong>Reference:</strong> ' . $quote_ref . '<br>'
            . '<strong>Generated:</strong> ' . date('d M Y h:i A') . '<br>'
            . '<strong>Plan:</strong> ' . $plan . '<br>'
            . '<strong>Industry:</strong> ' . $industry . '<br>'
            . '<strong>Country:</strong> ' . $country . '<br>'
            . '<strong>Client Name:</strong> ' . ($client_name !== '' ? $client_name : '—') . '<br>'
            . '<strong>Client Business:</strong> ' . ($client_business !== '' ? $client_business : '—') . '</p>'
            . '<h3>One-Time Setup Charges</h3>'
            . '<table style="' . $table_style . '"><thead><tr>'
            . '<th style="' . $th_style . '">#</th>'
            . '<th style="' . $th_style . '">Module</th>'
            . '<th style="' . $th_style . '">Feature / Item</th>'
            . '<th style="' . $th_style . '">Qty</th>'
            . '<th style="' . $th_style . 'text-align:right;">Amount</th>'
            . '</tr></thead><tbody>' . $setup_rows . '</tbody></table>'
            . '<h3>Monthly Recurring Charges</h3>'
            . '<table style="' . $table_style . '"><thead><tr>'
            . '<th style="' . $th_style . '">#</th>'
            . '<th style="' . $th_style . '">Module</th>'
            . '<th style="' . $th_style . '">Feature / Item</th>'
            . '<th style="' . $th_style . '">Qty</th>'
            . '<th style="' . $th_style . 'text-align:right;">Amount</th>'
            . '</tr></thead><tbody>' . $monthly_rows . '</tbody></table>'
            . '<h3>Financial Summary</h3>'
            . '<table style="' . $table_style . '"><thead><tr>'
            . '<th style="' . $th_style . '">Type</th>'
            . '<th style="' . $th_style . 'text-align:right;">Subtotal</th>'
            . '<th style="' . $th_style . 'text-align:right;">' . $discount_label . '</th>'
            . '<th style="' . $th_style . 'text-align:right;">' . $tax_header . '</th>'
            . '<th style="' . $th_style . 'text-align:right;">Net Payable</th>'
            . '</tr></thead><tbody>'
            . '<tr>'
            . '<td style="' . $td_style . '">One-Time Setup</td>'
            . '<td style="' . $amt_style . '">' . subscription_builder_quote_money($financials['setup_subtotal'], $quote) . '</td>'
            . '<td style="' . $amt_style . '">-' . subscription_builder_quote_money($financials['discount_setup'], $quote) . '</td>'
            . '<td style="' . $amt_style . '">' . subscription_builder_quote_money($financials['gst_setup'], $quote) . '</td>'
            . '<td style="' . $amt_style . '">' . subscription_builder_quote_money($financials['net_setup'], $quote) . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td style="' . $td_style . '">Monthly Recurring</td>'
            . '<td style="' . $amt_style . '">' . subscription_builder_quote_money($financials['monthly_subtotal'], $quote) . '</td>'
            . '<td style="' . $amt_style . '">-' . subscription_builder_quote_money($financials['discount_monthly'], $quote) . '</td>'
            . '<td style="' . $amt_style . '">' . subscription_builder_quote_money($financials['gst_monthly'], $quote) . '</td>'
            . '<td style="' . $amt_style . '">' . subscription_builder_quote_money($financials['net_monthly'], $quote) . '</td>'
            . '</tr>'
            . '</tbody></table>'
            . '<h3>Included in ' . $plan . '</h3>'
            . '<table style="' . $table_style . '"><thead><tr>'
            . '<th style="' . $th_style . '">Module</th>'
            . '<th style="' . $th_style . '">Features</th>'
            . '</tr></thead><tbody>' . $included_html . '</tbody></table>'
            . '<h3>Notes</h3>'
            . '<ul>'
            . '<li>' . $tax_note . '</li>'
            . '<li>' . $discount_note . '</li>'
            . '</ul>'
            . '</body></html>';
    }
}

if (!function_exists('subscription_builder_quote_send_excel')) {
    function subscription_builder_quote_send_excel($html, $filename)
    {
        $safe_name = preg_replace('/[^a-z0-9._-]+/i', '_', (string) $filename);
        if ($safe_name === '') {
            $safe_name = 'elintom_proposal_' . date('Ymd_His') . '.xls';
        }
        if (!preg_match('/\.xls$/i', $safe_name)) {
            $safe_name .= '.xls';
        }

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safe_name . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $html;
        exit;
    }
}

if (!function_exists('subscription_builder_quote_send_doc')) {
    function subscription_builder_quote_send_doc($html, $filename)
    {
        $safe_name = preg_replace('/[^a-z0-9._-]+/i', '_', (string) $filename);
        if ($safe_name === '') {
            $safe_name = 'elintom_proposal_' . date('Ymd_His') . '.doc';
        }
        if (!preg_match('/\.doc$/i', $safe_name)) {
            $safe_name .= '.doc';
        }

        header('Content-Type: application/msword; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safe_name . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $html;
        exit;
    }
}
