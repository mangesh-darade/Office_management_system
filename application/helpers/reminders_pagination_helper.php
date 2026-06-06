<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reminders dashboard pagination (Bootstrap 5).
 */

if (!function_exists('reminders_dashboard_pagination_config')) {
    function reminders_dashboard_pagination_config($filter, $total_rows, $per_page)
    {
        $baseUrl = site_url('reminders/dashboard');
        if ($filter) {
            $baseUrl .= '?filter=' . urlencode($filter);
        }
        return array(
            'base_url'             => $baseUrl,
            'total_rows'           => $total_rows,
            'per_page'             => $per_page,
            'uri_segment'          => 3,
            'num_links'            => 5,
            'use_page_numbers'     => false,
            'page_query_string'    => false,
            'query_string_segment' => 'page',
            'first_url'            => $baseUrl,
            'suffix'               => $filter ? '?filter=' . urlencode($filter) : '',
            'full_tag_open'        => '<nav aria-label="Reminders pagination"><ul class="pagination justify-content-center mb-0">',
            'full_tag_close'       => '</ul></nav>',
            'first_link'           => '&laquo; First',
            'first_tag_open'       => '<li class="page-item">',
            'first_tag_close'      => '</li>',
            'last_link'            => 'Last &raquo;',
            'last_tag_open'        => '<li class="page-item">',
            'last_tag_close'       => '</li>',
            'next_link'            => 'Next &rsaquo;',
            'next_tag_open'        => '<li class="page-item">',
            'next_tag_close'       => '</li>',
            'prev_link'            => '&lsaquo; Prev',
            'prev_tag_open'        => '<li class="page-item">',
            'prev_tag_close'       => '</li>',
            'cur_tag_open'         => '<li class="page-item active"><span class="page-link">',
            'cur_tag_close'        => '</span></li>',
            'num_tag_open'         => '<li class="page-item">',
            'num_tag_close'        => '</li>',
            'attributes'           => array('class' => 'page-link'),
        );
    }
}

if (!function_exists('reminders_dashboard_filter')) {
    function reminders_dashboard_filter($input)
    {
        $filter = $input->get('filter');
        $allowed = array('queued', 'sent', 'error');
        return in_array($filter, $allowed, true) ? $filter : null;
    }
}
