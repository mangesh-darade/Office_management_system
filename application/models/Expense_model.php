<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Expense_model extends CI_Model {
    
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get expense by ID
     */
    public function get($id)
    {
        $this->db->select('expenses.*, users.name as username, expense_categories.name as category_name');
        $this->db->from('expenses');
        $this->db->join('users', 'users.id = expenses.user_id', 'left');
        $this->db->join('expense_categories', 'expense_categories.id = expenses.category_id', 'left');
        $this->db->where('expenses.id', (int)$id);
        return $this->db->get()->row();
    }
    
    /**
     * Get expenses for user
     */
    public function get_by_user($user_id, $filters = [])
    {
        $this->db->select('expenses.*, expense_categories.name as category_name');
        $this->db->from('expenses');
        $this->db->join('expense_categories', 'expense_categories.id = expenses.category_id', 'left');
        $this->db->where('expenses.user_id', (int)$user_id);
        
        if (isset($filters['status'])) {
            $this->db->where('expenses.status', $filters['status']);
        }
        
        if (isset($filters['from_date'])) {
            $this->db->where('expenses.expense_date >=', $filters['from_date']);
        }
        
        if (isset($filters['to_date'])) {
            $this->db->where('expenses.expense_date <=', $filters['to_date']);
        }
        
        $this->db->order_by('expenses.created_at', 'DESC');
        return $this->db->get()->result();
    }
    
    /**
     * Get pending expenses for approval
     */
    public function get_pending()
    {
        $this->db->select('expenses.*, users.name as username, expense_categories.name as category_name');
        $this->db->from('expenses');
        $this->db->join('users', 'users.id = expenses.user_id', 'left');
        $this->db->join('expense_categories', 'expense_categories.id = expenses.category_id', 'left');
        $this->db->where('expenses.status', 'pending');
        $this->db->order_by('expenses.created_at', 'ASC');
        return $this->db->get()->result();
    }
    
    /**
     * Get approved expenses for reimbursement
     */
    public function get_approved()
    {
        $this->db->select('expenses.*, users.name as username, expense_categories.name as category_name');
        $this->db->from('expenses');
        $this->db->join('users', 'users.id = expenses.user_id', 'left');
        $this->db->join('expense_categories', 'expense_categories.id = expenses.category_id', 'left');
        $this->db->where('expenses.status', 'approved');
        $this->db->order_by('expenses.approved_at', 'ASC');
        return $this->db->get()->result();
    }
    
    /**
     * Get expense statistics
     */
    public function get_statistics($user_id = null)
    {
        $this->db->select('
            COUNT(*) as total_claims,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_count,
            SUM(CASE WHEN status = "reimbursed" THEN 1 ELSE 0 END) as reimbursed_count,
            SUM(CASE WHEN status != "rejected" THEN amount ELSE 0 END) as total_amount,
            SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
            SUM(CASE WHEN status = "reimbursed" THEN amount ELSE 0 END) as reimbursed_amount
        ');
        
        if ($user_id) {
            $this->db->where('user_id', (int)$user_id);
        }
        
        return $this->db->get('expenses')->row();
    }
    
    /**
     * Get category budget usage
     */
    public function get_category_budget_usage($month = null, $year = null)
    {
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');
        
        $hasBudget   = $this->db->table_exists('expense_categories') && $this->db->field_exists('budget_limit', 'expense_categories');
        $hasIsActive = $this->db->table_exists('expense_categories') && $this->db->field_exists('is_active', 'expense_categories');

        $budgetCol  = $hasBudget ? 'ec.budget_limit' : 'NULL AS budget_limit';
        $activeWhere = $hasIsActive ? 'WHERE ec.is_active = 1' : '';

        $sql = "SELECT 
                    ec.id,
                    ec.name,
                    {$budgetCol},
                    COALESCE(SUM(e.amount), 0) as used_amount,
                    COUNT(e.id) as expense_count
                FROM expense_categories ec
                LEFT JOIN expenses e ON e.category_id = ec.id 
                    AND MONTH(e.expense_date) = ? 
                    AND YEAR(e.expense_date) = ?
                    AND e.status != 'rejected'
                {$activeWhere}
                GROUP BY ec.id";
        
        return $this->db->query($sql, [$month, $year])->result();
    }
}
