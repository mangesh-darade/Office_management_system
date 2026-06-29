<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription_builder_model extends CI_Model
{
    private $table = 'subscription_builder';

    private $plan_order = array('Essential', 'Business', 'Professional', 'Enterprise');

    private $industry_order = array(
        'Retail',
        'Food & Beverages',
        'Manufacturing',
        'Clothing',
        'Services & AMC',
    );

    private $country_order = array(
        'India',
        'United Arab Emirates',
        'Saudi Arabia',
        'Qatar',
        'Oman',
        'Kuwait',
        'Bahrain',
        'United Kingdom',
        'United States',
    );

    private $countries_model = null;

    public function table_exists()
    {
        return $this->db->table_exists($this->table);
    }

    public function get_plan_order()
    {
        return $this->plan_order;
    }

    public function get_plans_in_hierarchy($plan)
    {
        $plan = $this->normalize_plan_label($plan);
        $index = false;
        foreach ($this->plan_order as $i => $label) {
            if (strcasecmp($label, $plan) === 0) {
                $index = $i;
                break;
            }
        }
        if ($index === false) {
            return array($plan);
        }
        return array_slice($this->plan_order, 0, $index + 1);
    }

    public function get_plan_rank($plan)
    {
        $plan = $this->normalize_plan_label($plan);
        foreach ($this->plan_order as $i => $label) {
            if (strcasecmp($label, $plan) === 0) {
                return $i;
            }
        }
        return -1;
    }

    public function normalize_plan_label($plan)
    {
        $plan = trim((string) $plan);
        if (strcasecmp($plan, 'ElintOm') === 0) {
            return 'Enterprise';
        }
        return $plan;
    }

    public function get_industry_order()
    {
        return $this->industry_order;
    }

    public function get_default_country()
    {
        $this->load_countries_model();
        if ($this->countries_model && $this->countries_model->table_exists()) {
            return $this->countries_model->get_default_name();
        }
        return 'India';
    }

    public function get_country_options($chargeable_only = false)
    {
        $options = array();
        foreach ($this->get_distinct_countries_from_catalog($chargeable_only) as $catalog_name) {
            $resolved = $this->resolve_country_meta($catalog_name);
            $options[] = array(
                'name' => $catalog_name,
                'code' => $resolved['code'],
                'mobile_code' => $resolved['mobile_code'],
                'currency_code' => $resolved['currency_code'],
                'currency_symbol' => $resolved['currency_symbol'],
            );
        }
        return $options;
    }

    public function resolve_country_meta($country)
    {
        $this->load_countries_model();
        if ($this->countries_model) {
            return $this->countries_model->resolve_meta($country);
        }

        return array(
            'id' => 0,
            'name' => trim((string) $country) !== '' ? trim((string) $country) : $this->get_default_country(),
            'code' => '',
            'mobile_code' => '',
            'currency_code' => 'INR',
            'currency_symbol' => '₹',
            'sort_order' => 999,
            'is_active' => 1,
        );
    }

    public function get_distinct_countries($chargeable_only = false)
    {
        return $this->get_distinct_countries_from_catalog($chargeable_only);
    }

    private function get_distinct_countries_from_catalog($chargeable_only = false)
    {
        if (!$this->table_exists()) {
            return array($this->get_default_country());
        }
        if (!$this->db->field_exists('country', $this->table)) {
            return array($this->get_default_country());
        }

        $this->db->distinct();
        $this->db->select('country');
        $this->db->from($this->table);
        if ($chargeable_only) {
            $this->db->group_start();
            $this->db->where('per_item_set_up_charges >', 0);
            $this->db->or_where('common_set_up_fees >', 0);
            $this->db->or_where('per_item_per_month_maintenances >', 0);
            $this->db->group_end();
        }
        $rows = $this->db->get()->result();
        $found = array();
        foreach ($rows as $row) {
            $label = trim((string) ($row->country ?? ''));
            if ($label === '') {
                $label = $this->get_default_country();
            }
            $found[] = $label;
        }
        $found = array_values(array_unique($found));
        if (empty($found)) {
            return array($this->get_default_country());
        }
        return $this->sort_by_order($found, $this->country_order);
    }

    private function load_countries_model()
    {
        if ($this->countries_model !== null) {
            return;
        }
        $CI =& get_instance();
        $CI->load->model('Subscription_builder_countries_model', 'subscription_builder_countries');
        $this->countries_model = $CI->subscription_builder_countries;
        if ($this->countries_model) {
            $this->countries_model->ensure_schema();
        }
    }

    public function country_labels_match($selected, $row_country)
    {
        $selected = trim((string) $selected);
        $row_country = trim((string) $row_country);
        if ($selected === '' || $row_country === '') {
            return false;
        }
        if (strcasecmp($selected, $row_country) === 0) {
            return true;
        }

        $aliases = array(
            'uae' => array('united arab emirates', 'ae'),
            'united arab emirates' => array('uae', 'ae'),
            'ae' => array('uae', 'united arab emirates'),
            'ksa' => array('saudi arabia', 'sa'),
            'saudi arabia' => array('ksa', 'sa'),
            'sa' => array('saudi arabia', 'ksa'),
            'uk' => array('united kingdom', 'gb'),
            'united kingdom' => array('uk', 'gb'),
            'gb' => array('united kingdom', 'uk'),
            'us' => array('united states', 'usa'),
            'usa' => array('united states', 'us'),
            'united states' => array('us', 'usa'),
        );

        $selected_key = strtolower($selected);
        $row_key = strtolower($row_country);
        if (isset($aliases[$selected_key]) && in_array($row_key, $aliases[$selected_key], true)) {
            return true;
        }
        if (isset($aliases[$row_key]) && in_array($selected_key, $aliases[$row_key], true)) {
            return true;
        }

        $selected_meta = $this->resolve_country_meta($selected);
        $row_meta = $this->resolve_country_meta($row_country);
        if ($selected_meta['code'] !== '' && $row_meta['code'] !== '' && strcasecmp($selected_meta['code'], $row_meta['code']) === 0) {
            return true;
        }

        return false;
    }

    public function sanitize_import_country($country)
    {
        $country = trim((string) $country);
        if ($country === '') {
            return $this->get_default_country();
        }

        return $country;
    }

    public function normalize_country_label($country)
    {
        return $this->sanitize_import_country($country);
    }

    public function row_matches_country($row, $country)
    {
        $selected = $this->sanitize_import_country($country);
        $row_country = trim((string) ($row->country ?? ''));
        if ($row_country === '') {
            return $this->country_labels_match($selected, $this->get_default_country());
        }

        return $this->country_labels_match($selected, $row_country);
    }

    public function get_distinct_plans()
    {
        if (!$this->table_exists()) {
            return array();
        }
        $this->db->distinct();
        $this->db->select('plan');
        $this->db->from($this->table);
        $rows = $this->db->get()->result();
        $found = array();
        foreach ($rows as $row) {
            $found[] = $this->normalize_plan_label(trim((string) $row->plan));
        }
        $found = array_values(array_unique($found));
        return $this->sort_by_order($found, $this->plan_order);
    }

    public function get_distinct_industries()
    {
        if (!$this->table_exists()) {
            return array();
        }
        $this->db->distinct();
        $this->db->select('industry');
        $this->db->from($this->table);
        $rows = $this->db->get()->result();
        $found = array();
        foreach ($rows as $row) {
            $found[] = trim((string) $row->industry);
        }
        return $this->sort_by_order($found, $this->industry_order);
    }

    public function get_items_for_plan_hierarchy($plan, $industry)
    {
        if (!$this->table_exists()) {
            return array();
        }

        $plans = $this->get_plans_in_hierarchy($plan);
        $legacy_plans = array();
        foreach ($plans as $tier_plan) {
            if (strcasecmp($tier_plan, 'Enterprise') === 0) {
                $legacy_plans[] = 'ElintOm';
            }
        }
        $plans = array_values(array_unique(array_merge($plans, $legacy_plans)));
        $industry = trim((string) $industry);

        $this->db->from($this->table);
        $this->db->where_in('plan', $plans);
        if ($industry !== '') {
            $this->db->where('industry', $industry);
        }
        $this->db->order_by('module', 'ASC');
        $this->db->order_by('feature', 'ASC');
        return $this->db->get()->result();
    }

    public function get_items($filters = array())
    {
        if (!$this->table_exists()) {
            return array();
        }

        $this->db->from($this->table);

        if (!empty($filters['plan'])) {
            $this->db->where('plan', (string) $filters['plan']);
        }
        if (!empty($filters['industry'])) {
            $this->db->where('industry', (string) $filters['industry']);
        }
        if (!empty($filters['country'])) {
            $this->db->where('country', (string) $filters['country']);
        }
        if (!empty($filters['module'])) {
            $this->db->where('module', (string) $filters['module']);
        }
        if (!empty($filters['search'])) {
            $q = trim((string) $filters['search']);
            if ($q !== '') {
                $this->db->group_start();
                $this->db->like('feature', $q);
                $this->db->or_like('module', $q);
                $this->db->or_like('details', $q);
                $this->db->group_end();
            }
        }

        $this->db->order_by('module', 'ASC');
        $this->db->order_by('feature', 'ASC');
        return $this->db->get()->result();
    }

    public function get_catalog($plan, $industry, $country = '')
    {
        $plan = trim((string) $plan);
        $industry = trim((string) $industry);
        $country = $this->sanitize_import_country($country);
        $all_rows = $this->get_items_for_plan_hierarchy($plan, $industry);

        $country_rows = array();
        foreach ($all_rows as $row) {
            if ($this->row_matches_country($row, $country)) {
                $country_rows[] = $row;
            }
        }
        $plan_rows = $this->dedupe_hierarchy_rows($country_rows, $plan);

        $included = array();
        $chargeable = array();
        $included_by_module = array();

        foreach ($plan_rows as $row) {
            $item = $this->row_to_array($row);
            if ($this->is_chargeable_row($row)) {
                $chargeable[] = $item;
                continue;
            }
            $included[] = $item;
            $module_key = $item['module'];
            if (!isset($included_by_module[$module_key])) {
                $included_by_module[$module_key] = array();
            }
            $included_by_module[$module_key][] = $item;
        }

        return array(
            'included' => $included,
            'included_by_module' => $included_by_module,
            'chargeable' => $chargeable,
        );
    }

    public function row_to_array($row)
    {
        return array(
            'id' => (int) $row->id,
            'plan' => trim((string) $row->plan),
            'industry' => trim((string) $row->industry),
            'country' => $this->sanitize_import_country($row->country ?? ''),
            'module' => trim((string) $row->module),
            'feature' => trim((string) $row->feature),
            'details' => trim((string) ($row->details ?? '')),
            'per_item_set_up_charges' => $this->decimal_value($row->per_item_set_up_charges),
            'item_unit' => trim((string) ($row->item_unit ?? '')),
            'common_set_up_fees' => $this->decimal_value($row->common_set_up_fees),
            'per_item_per_month_maintenances' => $this->decimal_value($row->per_item_per_month_maintenances),
        );
    }

    public function is_chargeable_row($row)
    {
        return $this->decimal_value($row->per_item_set_up_charges) > 0
            || $this->decimal_value($row->common_set_up_fees) > 0
            || $this->decimal_value($row->per_item_per_month_maintenances) > 0;
    }

    public function count_all()
    {
        if (!$this->table_exists()) {
            return 0;
        }
        return (int) $this->db->count_all($this->table);
    }

    public function find($id)
    {
        if (!$this->table_exists()) {
            return null;
        }
        $row = $this->db->get_where($this->table, array('id' => (int) $id))->row();
        return $row ? $row : null;
    }

    public function create($data)
    {
        if (!$this->table_exists()) {
            return false;
        }
        $row = $this->normalize_row($data);
        $row['created_at'] = date('Y-m-d H:i:s');
        $row['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $row);
        return (int) $this->db->insert_id();
    }

    public function update($id, $data)
    {
        if (!$this->table_exists()) {
            return false;
        }
        $row = $this->normalize_row($data);
        $row['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $id);
        $this->db->update($this->table, $row);
        return $this->db->affected_rows() >= 0;
    }

    public function delete($id)
    {
        if (!$this->table_exists()) {
            return false;
        }
        $this->db->where('id', (int) $id);
        $this->db->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    public function apply_list_filters($filters = array())
    {
        if (!empty($filters['plan'])) {
            $this->db->where('plan', (string) $filters['plan']);
        }
        if (!empty($filters['industry'])) {
            $this->db->where('industry', (string) $filters['industry']);
        }
        if (!empty($filters['country'])) {
            $this->db->where('country', (string) $filters['country']);
        }
        if (!empty($filters['module'])) {
            $this->db->where('module', (string) $filters['module']);
        }
        if (!empty($filters['search'])) {
            $q = trim((string) $filters['search']);
            if ($q !== '') {
                $this->db->group_start();
                $this->db->like('feature', $q);
                $this->db->or_like('module', $q);
                $this->db->or_like('details', $q);
                $this->db->or_like('plan', $q);
                $this->db->or_like('industry', $q);
                if ($this->db->field_exists('country', $this->table)) {
                    $this->db->or_like('country', $q);
                }
                $this->db->group_end();
            }
        }
    }

    public function count_filtered($filters = array())
    {
        if (!$this->table_exists()) {
            return 0;
        }
        $this->db->from($this->table);
        $this->apply_list_filters($filters);
        return (int) $this->db->count_all_results();
    }

    public function get_paginated($filters = array(), $limit = 50, $offset = 0)
    {
        if (!$this->table_exists()) {
            return array();
        }
        $this->db->from($this->table);
        $this->apply_list_filters($filters);
        $this->db->order_by('plan', 'ASC');
        $this->db->order_by('industry', 'ASC');
        $this->db->order_by('module', 'ASC');
        $this->db->order_by('feature', 'ASC');
        $this->db->limit((int) $limit, (int) $offset);
        return $this->db->get()->result();
    }

    public function import_parsed_rows($rows, $replace_all = false)
    {
        if (!$this->table_exists() || empty($rows)) {
            return 0;
        }
        if ($replace_all) {
            $this->db->empty_table($this->table);
        }
        $now = date('Y-m-d H:i:s');
        $inserted = 0;
        foreach ($rows as $data) {
            $row = $this->normalize_row($data);
            if ($row['plan'] === '' || $row['industry'] === '' || $row['module'] === '' || $row['feature'] === '') {
                continue;
            }
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            $this->db->insert($this->table, $row);
            $inserted++;
        }
        return $inserted;
    }

    public function parse_import_content($content)
    {
        $grid = array();
        $lines = preg_split('/\r\n|\r|\n/', (string) $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $cols = explode("\t", $line);
            if (count($cols) < 4) {
                $cols = str_getcsv($line);
            }
            $grid[] = $cols;
        }

        return $this->parse_import_grid($grid);
    }

    public function parse_import_grid($grid)
    {
        $rows = array();
        $header_map = null;

        foreach ($grid as $cols) {
            if (!is_array($cols)) {
                continue;
            }
            $cols = array_map(function ($val) {
                return trim((string) $val);
            }, $cols);

            if ($this->is_import_row_empty($cols)) {
                continue;
            }

            if ($header_map === null) {
                $first = strtolower(trim((string) ($cols[0] ?? '')));
                if ($first === 'plan') {
                    $header_map = $this->build_import_header_map($cols);
                    continue;
                }
            }

            $parsed = $this->map_import_columns($cols, $header_map);
            if ($parsed !== null) {
                $rows[] = $parsed;
            }
        }

        return $rows;
    }

    private function is_import_row_empty($cols)
    {
        foreach ($cols as $val) {
            if (trim((string) $val) !== '') {
                return false;
            }
        }
        return true;
    }

    private function build_import_header_map($headers)
    {
        $map = array();
        foreach ($headers as $i => $label) {
            $key = strtolower(trim((string) $label));
            $key = str_replace(array(' ', '/'), array('_', '_'), $key);
            $map[$i] = $key;
        }
        return $map;
    }

    private function map_import_columns($cols, $header_map)
    {
        $data = array(
            'plan' => '',
            'industry' => '',
            'country' => $this->get_default_country(),
            'module' => '',
            'feature' => '',
            'details' => '',
            'per_item_set_up_charges' => null,
            'item_unit' => '',
            'common_set_up_fees' => null,
            'per_item_per_month_maintenances' => null,
        );

        if ($header_map) {
            foreach ($header_map as $i => $key) {
                if (!array_key_exists($i, $cols)) {
                    continue;
                }
                $val = trim((string) $cols[$i]);
                if ($key === 'plan') {
                    $data['plan'] = $val;
                } elseif ($key === 'industry') {
                    $data['industry'] = $val;
                } elseif ($key === 'country') {
                    $data['country'] = $this->sanitize_import_country($val);
                } elseif ($key === 'module') {
                    $data['module'] = $val;
                } elseif ($key === 'feature') {
                    $data['feature'] = $val;
                } elseif ($key === 'details') {
                    $data['details'] = $val;
                } elseif ($key === 'per_item_set_up_charges' || $key === 'per_item_setup_charges') {
                    $data['per_item_set_up_charges'] = $this->nullable_decimal($val);
                } elseif ($key === 'item_unit') {
                    $data['item_unit'] = $val;
                } elseif ($key === 'common_set_up_fees' || $key === 'setup_fees') {
                    $data['common_set_up_fees'] = $this->nullable_decimal($val);
                } elseif ($key === 'per_item_per_month_maintenances' || $key === 'per_month') {
                    $data['per_item_per_month_maintenances'] = $this->nullable_decimal($val);
                }
            }

            $this->apply_trailing_import_country($data, $cols, $header_map);
        } else {
            $data = $this->map_import_positional_columns($cols);
        }

        $data['plan'] = $this->normalize_import_plan($data['plan']);

        if ($data['plan'] === '' && $data['feature'] === '') {
            return null;
        }

        return $data;
    }

    private function map_import_positional_columns($cols)
    {
        $data = array(
            'plan' => trim((string) ($cols[0] ?? '')),
            'industry' => trim((string) ($cols[1] ?? '')),
            'country' => $this->get_default_country(),
            'module' => '',
            'feature' => '',
            'details' => '',
            'per_item_set_up_charges' => null,
            'item_unit' => '',
            'common_set_up_fees' => null,
            'per_item_per_month_maintenances' => null,
        );

        if (count($cols) >= 10 && $this->import_value_is_country($cols[9] ?? '')) {
            $data['module'] = trim((string) ($cols[2] ?? ''));
            $data['feature'] = trim((string) ($cols[3] ?? ''));
            $data['details'] = trim((string) ($cols[4] ?? ''));
            $data['per_item_set_up_charges'] = $this->nullable_decimal($cols[5] ?? null);
            $data['item_unit'] = trim((string) ($cols[6] ?? ''));
            $data['common_set_up_fees'] = $this->nullable_decimal($cols[7] ?? null);
            $data['per_item_per_month_maintenances'] = $this->nullable_decimal($cols[8] ?? null);
            $data['country'] = $this->sanitize_import_country($cols[9] ?? '');
            return $data;
        }

        if (count($cols) >= 10 && $this->import_value_is_country($cols[2] ?? '')) {
            $data['country'] = $this->sanitize_import_country($cols[2] ?? '');
            $data['module'] = trim((string) ($cols[3] ?? ''));
            $data['feature'] = trim((string) ($cols[4] ?? ''));
            $data['details'] = trim((string) ($cols[5] ?? ''));
            $data['per_item_set_up_charges'] = $this->nullable_decimal($cols[6] ?? null);
            $data['item_unit'] = trim((string) ($cols[7] ?? ''));
            $data['common_set_up_fees'] = $this->nullable_decimal($cols[8] ?? null);
            $data['per_item_per_month_maintenances'] = $this->nullable_decimal($cols[9] ?? null);
            return $data;
        }

        $data['module'] = trim((string) ($cols[2] ?? ''));
        $data['feature'] = trim((string) ($cols[3] ?? ''));
        $data['details'] = trim((string) ($cols[4] ?? ''));
        $data['per_item_set_up_charges'] = $this->nullable_decimal($cols[5] ?? null);
        $data['item_unit'] = trim((string) ($cols[6] ?? ''));
        $data['common_set_up_fees'] = $this->nullable_decimal($cols[7] ?? null);
        $data['per_item_per_month_maintenances'] = $this->nullable_decimal($cols[8] ?? null);

        return $data;
    }

    private function apply_trailing_import_country(&$data, $cols, $header_map)
    {
        if (empty($header_map) || empty($cols)) {
            return;
        }

        $has_country_header = false;
        foreach ($header_map as $key) {
            if ($key === 'country') {
                $has_country_header = true;
                break;
            }
        }
        if ($has_country_header) {
            return;
        }

        $last_index = count($cols) - 1;
        $max_header_index = max(array_keys($header_map));
        if ($last_index <= $max_header_index) {
            return;
        }

        $trailing = trim((string) ($cols[$last_index] ?? ''));
        if ($trailing !== '' && $this->import_value_is_country($trailing)) {
            $data['country'] = $this->sanitize_import_country($trailing);
        }
    }

    private function import_value_is_country($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }

        $numeric = str_replace(array(',', ' '), '', $value);
        if ($numeric !== '' && is_numeric($numeric)) {
            return false;
        }

        if (strlen($value) > 60) {
            return false;
        }

        $this->load_countries_model();
        if ($this->countries_model && $this->countries_model->table_exists()) {
            if ($this->countries_model->find_by_name($value)) {
                return true;
            }
        }

        static $known = array(
            'india', 'uae', 'usa', 'uk', 'united arab emirates', 'united kingdom',
            'united states', 'saudi arabia', 'qatar', 'oman', 'kuwait', 'bahrain',
        );

        return in_array(strtolower($value), $known, true);
    }

    private function normalize_import_plan($plan)
    {
        $plan = trim((string) $plan);
        if (strcasecmp($plan, 'Businees') === 0 || strcasecmp($plan, 'Buisness') === 0) {
            return 'Business';
        }
        return $this->normalize_plan_label($plan);
    }

    private function normalize_row($data)
    {
        return array(
            'plan' => $this->normalize_import_plan($data['plan'] ?? ''),
            'industry' => trim((string) ($data['industry'] ?? '')),
            'country' => $this->sanitize_import_country($data['country'] ?? ''),
            'module' => trim((string) ($data['module'] ?? '')),
            'feature' => trim((string) ($data['feature'] ?? '')),
            'details' => trim((string) ($data['details'] ?? '')) ?: null,
            'per_item_set_up_charges' => $this->nullable_decimal($data['per_item_set_up_charges'] ?? null),
            'item_unit' => trim((string) ($data['item_unit'] ?? '')) ?: null,
            'common_set_up_fees' => $this->nullable_decimal($data['common_set_up_fees'] ?? null),
            'per_item_per_month_maintenances' => $this->nullable_decimal($data['per_item_per_month_maintenances'] ?? null),
        );
    }

    private function nullable_decimal($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = str_replace(array(',', '₹', ' '), '', (string) $value);
        if ($clean === '' || !is_numeric($clean)) {
            return null;
        }
        return (float) $clean;
    }

    private function decimal_value($value)
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        return (float) $value;
    }

    private function dedupe_hierarchy_rows($rows, $selected_plan)
    {
        $by_key = array();
        foreach ($rows as $row) {
            $module = strtolower(trim((string) $row->module));
            $feature = strtolower(trim((string) $row->feature));
            $key = $module . '|' . $feature;
            $rank = $this->get_plan_rank($row->plan);
            if (!isset($by_key[$key]) || $rank > $by_key[$key]['rank']) {
                $by_key[$key] = array(
                    'row' => $row,
                    'rank' => $rank,
                );
            }
        }

        $deduped = array();
        foreach ($by_key as $entry) {
            $deduped[] = $entry['row'];
        }

        usort($deduped, function ($a, $b) {
            $module_cmp = strcasecmp((string) $a->module, (string) $b->module);
            if ($module_cmp !== 0) {
                return $module_cmp;
            }
            return strcasecmp((string) $a->feature, (string) $b->feature);
        });

        return $deduped;
    }

    private function sort_by_order($items, $order)
    {
        $rank = array();
        foreach ($order as $i => $label) {
            $rank[strtolower($label)] = $i;
        }
        usort($items, function ($a, $b) use ($rank) {
            $ka = strtolower(trim((string) $a));
            $kb = strtolower(trim((string) $b));
            $ra = isset($rank[$ka]) ? $rank[$ka] : 999;
            $rb = isset($rank[$kb]) ? $rank[$kb] : 999;
            if ($ra === $rb) {
                return strcasecmp($a, $b);
            }
            return $ra - $rb;
        });
        return $items;
    }
}
