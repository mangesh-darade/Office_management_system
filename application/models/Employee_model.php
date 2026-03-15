<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employee_model extends CI_Model
{
    private $table = 'employees';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    private function ensure_schema()
    {
        static $done = false;
        if ($done) { return; }
        $done = true;
        if (!$this->db->table_exists($this->table)) {
            return;
        }
        $fields = $this->db->list_fields($this->table);
        $addCol = function($name, $sqlPart) use ($fields){
            if (!in_array($name, $fields, true)){
                $this->db->query("ALTER TABLE `{$this->table}` ADD ".$sqlPart);
            }
        };
        $addCol('location', "`location` varchar(120) DEFAULT NULL AFTER `department`");
        $addCol('bank_name', "`bank_name` varchar(190) DEFAULT NULL AFTER `phone`");
        $addCol('bank_ac_no', "`bank_ac_no` varchar(50) DEFAULT NULL AFTER `bank_name`");
        $addCol('pan_no', "`pan_no` varchar(20) DEFAULT NULL AFTER `bank_ac_no`");
        $addCol('shift_id', "`shift_id` int(11) DEFAULT NULL AFTER `designation`"); // Added shift_id

        if (!$this->db->table_exists('employee_documents')) {
            $sql = "CREATE TABLE `employee_documents` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `employee_id` bigint(20) UNSIGNED NOT NULL,
                `doc_type` varchar(100) DEFAULT NULL,
                `original_name` varchar(255) NOT NULL,
                `file_name` varchar(255) NOT NULL,
                `file_path` varchar(500) NOT NULL,
                `file_size` int(11) DEFAULT NULL,
                `file_type` varchar(100) DEFAULT NULL,
                `uploaded_by` int(11) DEFAULT NULL,
                `uploaded_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_employee` (`employee_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
    }

    public function add_document($data)
    {
        $this->db->insert('employee_documents', $data);
        return $this->db->insert_id();
    }

    public function get_documents($employee_id)
    {
        return $this->db->where('employee_id', (int)$employee_id)
                        ->order_by('uploaded_at', 'DESC')
                        ->get('employee_documents')
                        ->result();
    }

    public function get_document($id)
    {
        return $this->db->where('id', (int)$id)->get('employee_documents')->row();
    }

    public function delete_document($id)
    {
        $this->db->where('id', (int)$id)->delete('employee_documents');
        return $this->db->affected_rows() > 0;
    }

    public function all($limit = 50, $offset = 0, $search = null, $filters = [])
    {
        $this->db->select('e.*, u.email, u.name as user_name, u.role_id');
        $this->db->from($this->table.' e');
        $this->db->join('users u', 'u.id = e.user_id', 'left');
        
        // Apply group filters
        if (!empty($filters)) {
            if (isset($filters['department'])) {
                $this->db->where('e.department', $filters['department']);
            }
        }
        
        if ($search) {
            $this->db->group_start()
                ->like('e.emp_code', $search)
                ->or_like('e.first_name', $search)
                ->or_like('e.last_name', $search)
                ->or_like('u.email', $search)
            ->group_end();
        }
        $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_all($search = null, $filters = [])
    {
        $this->db->from($this->table.' e');
        $this->db->join('users u', 'u.id = e.user_id', 'left');
        
        // Apply group filters
        if (!empty($filters)) {
            if (isset($filters['department'])) {
                $this->db->where('e.department', $filters['department']);
            }
        }
        
        if ($search) {
            $this->db->group_start()
                ->like('e.emp_code', $search)
                ->or_like('e.first_name', $search)
                ->or_like('e.last_name', $search)
                ->or_like('u.email', $search)
            ->group_end();
        }
        return $this->db->count_all_results();
    }

    public function find($id)
    {
        $this->db->select('e.*, u.email, u.name as user_name, u.role_id');
        $this->db->from($this->table.' e');
        $this->db->join('users u', 'u.id = e.user_id', 'left');
        $this->db->where('e.id', $id);
        return $this->db->get()->row();
    }

    public function create($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', (int)$id)->update($this->table, $data);
        return $this->db->affected_rows() > 0;
    }

    public function delete($id)
    {
        $this->db->where('id', $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    public function get_by_user_id($user_id)
    {
        $this->db->select('e.*, u.email, u.name as user_name, u.role_id');
        $this->db->from($this->table.' e');
        $this->db->join('users u', 'u.id = e.user_id', 'left');
        $this->db->where('e.user_id', $user_id);
        return $this->db->get()->row();
    }

    /**
     * Generate a unique employee code
     * Format: E + 3-digit sequential number (e.g., E001, E002)
     * 
     * @param string|null $exclude_code Optional code to exclude from uniqueness check (for edit)
     * @return string Unique employee code
     */
    public function generate_emp_code($exclude_code = null)
    {
        // Try to find the highest existing numeric part
        $this->db->select('emp_code')
                 ->from($this->table)
                 ->where('emp_code IS NOT NULL')
                 ->where('emp_code !=', '')
                 ->like('emp_code', 'E', 'after');
        
        if ($exclude_code) {
            $this->db->where('emp_code !=', $exclude_code);
        }
        
        $results = $this->db->order_by('emp_code', 'DESC')
                           ->limit(100)
                           ->get()
                           ->result();
        
        $max_num = 0;
        foreach ($results as $row) {
            if (preg_match('/^E(\d+)$/i', $row->emp_code, $matches)) {
                $num = (int)$matches[1];
                if ($num > $max_num) {
                    $max_num = $num;
                }
            }
        }
        
        // Generate next code — loop until a unique code is found (handles concurrent inserts)
        $next_num = $max_num + 1;
        $max_attempts = 100;
        $attempts = 0;
        do {
            $code = 'E' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
            $exists = $this->db->where('emp_code', $code)->count_all_results($this->table);
            if ($exists) { $next_num++; }
            $attempts++;
        } while ($exists && $attempts < $max_attempts);

        return $code;
    }

    /**
     * Check if employee code exists
     * 
     * @param string $code Employee code to check
     * @param int|null $exclude_id Employee ID to exclude from check (for edit)
     * @return bool True if exists, false otherwise
     */
    public function emp_code_exists($code, $exclude_id = null)
    {
        $this->db->from($this->table);
        $this->db->where('emp_code', $code);
        if ($exclude_id !== null) {
            $this->db->where('id !=', (int)$exclude_id);
        }
        return $this->db->count_all_results() > 0;
    }
}
