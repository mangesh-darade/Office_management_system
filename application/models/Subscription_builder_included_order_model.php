<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription_builder_included_order_model extends CI_Model
{
    private $table = 'subscription_builder_included_order';

    public function ensure_schema()
    {
        $this->load->helper('subscription_builder_included_order_schema');
        subscription_builder_included_order_schema_ensure($this->db);
    }

    public function get($plan, $industry)
    {
        $this->ensure_schema();
        if (!$this->db->table_exists($this->table)) {
            return null;
        }
        $row = $this->db->get_where($this->table, array(
            'plan' => trim((string) $plan),
            'industry' => trim((string) $industry),
        ))->row();
        return $row ? $row : null;
    }

    public function get_parsed($plan, $industry)
    {
        $row = $this->get($plan, $industry);
        if (!$row) {
            return array(
                'module_order' => array(),
                'feature_order' => array(),
            );
        }

        $module_order = json_decode((string) ($row->module_order ?? ''), true);
        $feature_order = json_decode((string) ($row->feature_order ?? ''), true);

        return array(
            'module_order' => is_array($module_order) ? $module_order : array(),
            'feature_order' => is_array($feature_order) ? $feature_order : array(),
        );
    }

    public function save($plan, $industry, $module_order, $feature_order)
    {
        $this->ensure_schema();
        if (!$this->db->table_exists($this->table)) {
            return false;
        }

        $plan = trim((string) $plan);
        $industry = trim((string) $industry);
        if ($plan === '' || $industry === '') {
            return false;
        }

        if (!is_array($module_order)) {
            $module_order = array();
        }
        if (!is_array($feature_order)) {
            $feature_order = array();
        }

        $now = date('Y-m-d H:i:s');
        $payload = array(
            'module_order' => json_encode(array_values($module_order)),
            'feature_order' => json_encode($feature_order),
            'updated_at' => $now,
        );

        $existing = $this->get($plan, $industry);
        if ($existing) {
            $this->db->where('id', (int) $existing->id);
            $this->db->update($this->table, $payload);
            return $this->db->affected_rows() >= 0;
        }

        $payload['plan'] = $plan;
        $payload['industry'] = $industry;
        $payload['created_at'] = $now;
        $this->db->insert($this->table, $payload);
        return (int) $this->db->insert_id() > 0;
    }

    public function apply_to_included_by_module($plan, $industry, $included_by_module)
    {
        if (!is_array($included_by_module) || empty($included_by_module)) {
            return $included_by_module;
        }

        $config = $this->get_parsed($plan, $industry);
        $module_order = $config['module_order'];
        $feature_order = $config['feature_order'];

        $sorted = array();

        foreach ($module_order as $module_name) {
            $module_name = trim((string) $module_name);
            if ($module_name === '' || !isset($included_by_module[$module_name])) {
                continue;
            }
            $sorted[$module_name] = $this->sort_module_features(
                $module_name,
                $included_by_module[$module_name],
                isset($feature_order[$module_name]) ? $feature_order[$module_name] : array()
            );
        }

        $remaining_modules = array_diff(array_keys($included_by_module), array_keys($sorted));
        sort($remaining_modules, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($remaining_modules as $module_name) {
            $sorted[$module_name] = $this->sort_module_features(
                $module_name,
                $included_by_module[$module_name],
                isset($feature_order[$module_name]) ? $feature_order[$module_name] : array()
            );
        }

        return $sorted;
    }

    private function sort_module_features($module_name, $items, $id_order)
    {
        if (!is_array($items) || empty($items)) {
            return array();
        }

        $by_id = array();
        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0) {
                $by_id[$id] = $item;
            }
        }

        $sorted = array();
        if (is_array($id_order)) {
            foreach ($id_order as $id) {
                $id = (int) $id;
                if ($id > 0 && isset($by_id[$id])) {
                    $sorted[] = $by_id[$id];
                    unset($by_id[$id]);
                }
            }
        }

        $remaining = array_values($by_id);
        usort($remaining, function ($a, $b) {
            return strcasecmp((string) ($a['feature'] ?? ''), (string) ($b['feature'] ?? ''));
        });

        return array_merge($sorted, $remaining);
    }
}
