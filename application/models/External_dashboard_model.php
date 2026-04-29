<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class External_dashboard_model extends CI_Model
{
    private $table = 'external_dashboards';

    public function __construct()
    {
        parent::__construct();
    }

    public function ensure_schema()
    {
        if (!$this->db->table_exists($this->table)) {
            $sql = "CREATE TABLE `{$this->table}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `label` varchar(100) NOT NULL,
                `url` varchar(500) NOT NULL,
                `permission_key` varchar(100) NOT NULL,
                `icon_class` varchar(100) NOT NULL DEFAULT 'bi bi-bar-chart-line',
                `gradient_class` varchar(100) NOT NULL DEFAULT 'bg-gradient-primary',
                `sort_order` int(11) NOT NULL DEFAULT 0,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_external_dashboards_permission` (`permission_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }

        $this->seed_defaults();
    }

    public function seed_defaults()
    {
        $rows = [
            [
                'label' => 'Cheers',
                'url' => 'https://lookerstudio.google.com/reporting/abab6db9-72c7-4e14-9908-056db4eb197c',
                'permission_key' => 'dashboard_cheers',
                'icon_class' => 'bi bi-cup-hot-fill',
                'gradient_class' => 'bg-gradient-primary',
                'sort_order' => 1,
                'is_active' => 1,
            ],
            [
                'label' => 'Sateri',
                'url' => 'https://lookerstudio.google.com/reporting/d0c7f05a-a61e-42fd-a4fd-7af3570e00ff',
                'permission_key' => 'dashboard_sateri',
                'icon_class' => 'bi bi-bar-chart-line',
                'gradient_class' => 'bg-gradient-success',
                'sort_order' => 2,
                'is_active' => 1,
            ],
            [
                'label' => 'Srujan',
                'url' => 'https://lookerstudio.google.com/reporting/3eef7afb-6cbb-49ec-88de-5f9afda2594c',
                'permission_key' => 'dashboard_srujan',
                'icon_class' => 'bi bi-bar-chart-line',
                'gradient_class' => 'bg-gradient-warning',
                'sort_order' => 3,
                'is_active' => 1,
            ],
            [
                'label' => 'Simpliworks',
                'url' => 'https://lookerstudio.google.com/reporting/bb2eb1d7-bf64-41d8-9920-125a685bb3e7',
                'permission_key' => 'dashboard_simpliworks',
                'icon_class' => 'bi bi-bar-chart-line',
                'gradient_class' => 'bg-gradient-info',
                'sort_order' => 4,
                'is_active' => 1,
            ],
        ];

        foreach ($rows as $row) {
            $exists = $this->db->where('permission_key', $row['permission_key'])->get($this->table)->row();
            if (!$exists) {
                $this->db->insert($this->table, $row);
            }
        }
    }

    public function get_active()
    {
        return $this->db
            ->from($this->table)
            ->where('is_active', 1)
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get()
            ->result_array();
    }
}

