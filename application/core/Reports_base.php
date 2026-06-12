<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared bootstrap and name-resolution helpers for report controllers.
 */
class Reports_base extends CI_Controller {

    /** @var array<int,string> */
    protected $_user_name_cache = array();

    /** @var array<int,string> */
    protected $_client_name_cache = array();

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','permission','hierarchy_filter','attendance','attendance_report','attendance_report_export','schema_columns','types']);
        $this->load->library('session');
        $this->load->model('Report_model');
        if ($this->db->table_exists('settings')) {
            $this->load->model('Setting_model', 'settings');
        }
        
        // RBAC Audit: Centralized module access check
        require_module_access(get_controller_module_access_keys('reports'), true);
    }

    protected function get_client_name($client_id) {
        $client_id = (int) $client_id;
        if ($client_id <= 0 || !$this->db->table_exists('clients')) {
            return '—';
        }

        if (array_key_exists($client_id, $this->_client_name_cache))
        {
            return $this->_client_name_cache[$client_id];
        }
        
        $client = $this->db->select('company_name')->from('clients')->where('id', $client_id)->get()->row();
        $name = $client ? $client->company_name : '—';
        $this->_client_name_cache[$client_id] = $name;

        return $name;
    }

    /**
     * @param array<int> $client_ids
     * @return void
     */
    protected function prefetch_client_names(array $client_ids)
    {
        if (!$this->db->table_exists('clients'))
        {
            return;
        }

        $client_ids = array_unique(array_filter(array_map('intval', $client_ids)));
        $missing = array();
        foreach ($client_ids as $client_id)
        {
            if ($client_id > 0 && !array_key_exists($client_id, $this->_client_name_cache))
            {
                $missing[] = $client_id;
            }
        }

        if (empty($missing))
        {
            return;
        }

        $rows = $this->db->select('id, company_name')
            ->from('clients')
            ->where_in('id', $missing)
            ->get()
            ->result();

        foreach ($missing as $client_id)
        {
            $this->_client_name_cache[$client_id] = '—';
        }

        foreach ($rows as $client)
        {
            $this->_client_name_cache[(int) $client->id] = $client->company_name;
        }
    }
    protected function get_user_name($user_id) {
        $user_id = (int) $user_id;
        if ($user_id <= 0)
        {
            return 'User #0';
        }

        if (array_key_exists($user_id, $this->_user_name_cache))
        {
            return $this->_user_name_cache[$user_id];
        }

        if (!$this->db->table_exists('users')) {
            return 'User #' . $user_id;
        }
        
        $this->db->select('u.id, u.email');
        $this->apply_user_employee_name_selects('u', 'e');
        
        $this->db->from('users u')->where('u.id', $user_id);
        apply_role_hierarchy_filter($this->db, 'u.id');
        $user = $this->db->get()->row();
        
        if (!$user) {
            $this->_user_name_cache[$user_id] = 'User #' . $user_id;
            return $this->_user_name_cache[$user_id];
        }

        $this->_user_name_cache[$user_id] = $this->format_user_display_name($user);
        return $this->_user_name_cache[$user_id];
    }

    /**
     * Batch-load user display names into $_user_name_cache to avoid N+1 queries in report loops.
     * Resolves name from users.name/full_name with employees table fallback when present.
     *
     * @param array<int> $user_ids
     * @return void
     */
    protected function prefetch_user_names(array $user_ids)
    {
        if (!$this->db->table_exists('users'))
        {
            return;
        }

        $user_ids = array_unique(array_filter(array_map('intval', $user_ids)));
        $missing = array();
        foreach ($user_ids as $user_id)
        {
            if ($user_id > 0 && !array_key_exists($user_id, $this->_user_name_cache))
            {
                $missing[] = $user_id;
            }
        }

        if (empty($missing))
        {
            return;
        }

        $this->db->select('u.id, u.email');
        $this->apply_user_employee_name_selects('u', 'e');

        $this->db->from('users u');
        $this->db->where_in('u.id', $missing);
        apply_role_hierarchy_filter($this->db, 'u.id');
        $rows = $this->db->get()->result();

        foreach ($missing as $user_id)
        {
            $this->_user_name_cache[$user_id] = 'User #' . $user_id;
        }

        foreach ($rows as $user)
        {
            $this->_user_name_cache[(int) $user->id] = $this->format_user_display_name($user);
        }
    }

    /**
     * @param object $user
     * @return string
     */
    protected function format_user_display_name($user)
    {
        $empParts = array();
        if (isset($user->emp_first_name) && trim((string) $user->emp_first_name) !== '') { $empParts[] = trim((string) $user->emp_first_name); }
        if (isset($user->emp_last_name) && trim((string) $user->emp_last_name) !== '') { $empParts[] = trim((string) $user->emp_last_name); }
        if (!empty($empParts)) { return trim(implode(' ', $empParts)); }
        if (isset($user->emp_full_name) && trim((string) $user->emp_full_name) !== '') { return trim((string) $user->emp_full_name); }
        if (isset($user->full_name) && trim((string) $user->full_name) !== '') { return trim((string) $user->full_name); }
        if (isset($user->name) && trim((string) $user->name) !== '') { return trim((string) $user->name); }

        return $user->email;
    }

    /** @var array|null Cached attendance table columns (legacy alias) */
    protected $_attendance_table_fields = null;

    /**
     * Cached column check for any table.
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    protected function schema_has_column($table, $column)
    {
        return schema_table_has_column($this->db, $table, $column);
    }

    /**
     * @return array{hasEmpTable:bool,hasEmpName:bool,hasFullName:bool,hasName:bool}
     */
    protected function user_employee_name_flags()
    {
        $hasEmpTable = $this->db->table_exists('employees')
            && $this->schema_has_column('employees', 'user_id');

        return array(
            'hasEmpTable' => $hasEmpTable,
            'hasEmpName'  => $hasEmpTable && $this->schema_has_column('employees', 'name'),
            'hasFullName' => $this->schema_has_column('users', 'full_name'),
            'hasName'     => $this->schema_has_column('users', 'name'),
        );
    }

    /**
     * SQL expression for user display name (export queries).
     *
     * @param string $userAlias
     * @param string $empAlias
     * @param array|null $flags
     * @return string
     */
    protected function user_display_name_sql_expr($userAlias = 'u', $empAlias = 'e', $flags = null)
    {
        if ($flags === null) {
            $flags = $this->user_employee_name_flags();
        }

        if ($flags['hasEmpName']) {
            return 'COALESCE(NULLIF(TRIM(' . $empAlias . '.name),""), NULLIF(TRIM(' . $userAlias . '.full_name),""), NULLIF(TRIM(' . $userAlias . '.name),""), ' . $userAlias . '.email)';
        }
        if ($flags['hasFullName']) {
            return 'COALESCE(NULLIF(TRIM(' . $userAlias . '.full_name),""), NULLIF(TRIM(' . $userAlias . '.name),""), ' . $userAlias . '.email)';
        }
        if ($flags['hasName']) {
            return 'COALESCE(NULLIF(TRIM(' . $userAlias . '.name),""), ' . $userAlias . '.email)';
        }

        return $userAlias . '.email';
    }

    /**
     * Add user/employee name columns to the active query builder.
     *
     * @param string $userAlias
     * @param string $empAlias
     * @param array $options middle_name, department, active_only
     * @return void
     */
    protected function apply_user_employee_name_selects(
        $userAlias = 'u',
        $empAlias = 'e',
        array $options = array()
    ) {
        if ($this->schema_has_column('users', 'full_name')) {
            $this->db->select($userAlias . '.full_name');
        }
        if ($this->schema_has_column('users', 'name')) {
            $this->db->select($userAlias . '.name');
        }
        if (!empty($options['active_only']) && $this->schema_has_column('users', 'status')) {
            $this->db->where($userAlias . '.status', 'active');
        }

        if ($this->db->table_exists('employees') && $this->schema_has_column('employees', 'user_id')) {
            $this->db->join(
                'employees ' . $empAlias,
                $empAlias . '.user_id = ' . $userAlias . '.id',
                'left'
            );

            $empMap = array(
                'name'       => 'emp_name',
                'full_name'  => 'emp_full_name',
                'first_name' => 'emp_first_name',
                'last_name'  => 'emp_last_name',
            );
            if (!empty($options['middle_name'])) {
                $empMap['middle_name'] = 'emp_middle_name';
            }

            foreach ($empMap as $col => $alias) {
                if ($this->schema_has_column('employees', $col)) {
                    $this->db->select($empAlias . '.' . $col . ' AS ' . $alias);
                }
            }

            if (!empty($options['department']) && $this->schema_has_column('employees', 'department')) {
                $this->db->select($empAlias . '.department');
            }
        }
    }

    /**
     * Check attendance table column existence (one list_fields per request).
     *
     * @param string $column
     * @return bool
     */
    protected function attendance_table_has_column($column)
    {
        return $this->schema_has_column('attendance', $column);
    }
}
