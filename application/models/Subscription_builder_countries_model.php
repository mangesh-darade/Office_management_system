<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription_builder_countries_model extends CI_Model
{
    private $table = 'subscription_builder_countries';

    public function table_exists()
    {
        return $this->db->table_exists($this->table);
    }

    public function ensure_schema()
    {
        $this->load->helper('subscription_builder_countries_schema');
        subscription_builder_countries_schema_ensure($this->db);
    }

    public function get_default_name()
    {
        return 'India';
    }

    public function get_all_active()
    {
        if (!$this->table_exists()) {
            return array();
        }

        $this->db->from($this->table);
        $this->db->where('is_active', 1);
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('name', 'ASC');
        $rows = $this->db->get()->result();

        $list = array();
        $seen_codes = array();
        foreach ($rows as $row) {
            $item = $this->row_to_array($row);
            $dedupe_key = strtolower($item['code'] !== '' ? $item['code'] : $item['name']);
            if (isset($seen_codes[$dedupe_key])) {
                continue;
            }
            $seen_codes[$dedupe_key] = true;
            $list[] = $item;
        }

        return $list;
    }

    public function get_active_names()
    {
        $names = array();
        foreach ($this->get_all_active() as $row) {
            $names[] = $row['name'];
        }
        return $names;
    }

    public function find_by_name($name)
    {
        $name = trim((string) $name);
        if ($name === '' || !$this->table_exists()) {
            return null;
        }

        $candidates = array($name);
        $alias_target = $this->resolve_lookup_name($name);
        if ($alias_target !== $name) {
            $candidates[] = $alias_target;
        }

        $static = $this->static_currency_map();
        foreach (array(strtolower($name), strtolower($alias_target)) as $lookup_key) {
            if ($lookup_key !== '' && isset($static[$lookup_key]['code'])) {
                $candidates[] = $static[$lookup_key]['code'];
            }
        }

        $candidates = array_values(array_unique(array_filter($candidates)));
        foreach ($candidates as $candidate) {
            $row = $this->fetch_active_country_row($candidate);
            if ($row) {
                return $this->row_to_array($row);
            }
        }

        return null;
    }

    private function fetch_active_country_row($candidate)
    {
        $candidate = trim((string) $candidate);
        if ($candidate === '') {
            return null;
        }

        $this->db->from($this->table);
        $this->db->where('is_active', 1);
        $this->db->group_start();
        $this->db->where('name', $candidate);
        $this->db->or_where('code', strtoupper($candidate));
        $this->db->group_end();
        $this->db->order_by('sort_order', 'ASC');
        $this->db->limit(1);
        $row = $this->db->get()->row();

        return $row ? $row : null;
    }

    public function get_default_meta()
    {
        $meta = $this->find_by_name($this->get_default_name());
        if ($meta) {
            return $meta;
        }

        return $this->fallback_meta($this->get_default_name());
    }

    public function resolve_meta($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return $this->get_default_meta();
        }

        $meta = $this->find_by_name($name);
        if ($meta) {
            return $meta;
        }

        return $this->fallback_meta($name);
    }

    public function row_to_array($row)
    {
        return array(
            'id' => (int) $row->id,
            'name' => trim((string) $row->name),
            'code' => strtoupper(trim((string) $row->code)),
            'mobile_code' => trim((string) $row->mobile_code),
            'currency_code' => strtoupper(trim((string) $row->currency_code)),
            'currency_symbol' => trim((string) $row->currency_symbol),
            'sort_order' => (int) $row->sort_order,
            'is_active' => (int) $row->is_active,
        );
    }

    private function resolve_lookup_name($name)
    {
        $key = strtolower(trim((string) $name));
        $aliases = $this->country_alias_map();
        if (isset($aliases[$key])) {
            return $aliases[$key];
        }

        return trim((string) $name);
    }

    private function country_alias_map()
    {
        return array(
            'uae' => 'UAE',
            'ae' => 'UAE',
            'united arab emirates' => 'UAE',
            'ksa' => 'Saudi Arabia',
            'sa' => 'Saudi Arabia',
            'uk' => 'United Kingdom',
            'gb' => 'United Kingdom',
            'us' => 'United States',
            'usa' => 'United States',
        );
    }

    private function static_currency_map()
    {
        return array(
            'india' => array('code' => 'IN', 'mobile_code' => '91', 'currency_code' => 'INR', 'currency_symbol' => '₹'),
            'united arab emirates' => array('code' => 'AE', 'mobile_code' => '971', 'currency_code' => 'AED', 'currency_symbol' => 'AED'),
            'uae' => array('code' => 'AE', 'mobile_code' => '971', 'currency_code' => 'AED', 'currency_symbol' => 'AED'),
            'saudi arabia' => array('code' => 'SA', 'mobile_code' => '966', 'currency_code' => 'SAR', 'currency_symbol' => 'SAR'),
            'qatar' => array('code' => 'QA', 'mobile_code' => '974', 'currency_code' => 'QAR', 'currency_symbol' => 'QAR'),
            'oman' => array('code' => 'OM', 'mobile_code' => '968', 'currency_code' => 'OMR', 'currency_symbol' => 'OMR'),
            'kuwait' => array('code' => 'KW', 'mobile_code' => '965', 'currency_code' => 'KWD', 'currency_symbol' => 'KWD'),
            'bahrain' => array('code' => 'BH', 'mobile_code' => '973', 'currency_code' => 'BHD', 'currency_symbol' => 'BHD'),
            'united kingdom' => array('code' => 'GB', 'mobile_code' => '44', 'currency_code' => 'GBP', 'currency_symbol' => '£'),
            'united states' => array('code' => 'US', 'mobile_code' => '1', 'currency_code' => 'USD', 'currency_symbol' => '$'),
        );
    }

    private function fallback_meta($name)
    {
        $label = trim((string) $name);
        $lookup_key = strtolower($this->resolve_lookup_name($label));
        $map = $this->static_currency_map();
        if (isset($map[$lookup_key])) {
            $currency = $map[$lookup_key];
            return array(
                'id' => 0,
                'name' => $label,
                'code' => $currency['code'],
                'mobile_code' => $currency['mobile_code'],
                'currency_code' => $currency['currency_code'],
                'currency_symbol' => $currency['currency_symbol'],
                'sort_order' => 999,
                'is_active' => 1,
            );
        }

        return array(
            'id' => 0,
            'name' => $label,
            'code' => '',
            'mobile_code' => '',
            'currency_code' => 'INR',
            'currency_symbol' => '₹',
            'sort_order' => 999,
            'is_active' => 1,
        );
    }
}
