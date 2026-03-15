<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Expenses Controller
 * 
 * Handles expense claim submission, approval, and reimbursement
 */
class Expenses extends CI_Controller {
    
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'form', 'permission']);
        $this->load->library(['session', 'upload']);
        $this->load->model('Expense_model');
        
        // Check if user is logged in
        if (!(int)$this->session->userdata('user_id')) {
            redirect('auth/login');
        }
        
        // Check module access
        require_module_access('expenses');
        
        $this->ensure_schema();
    }
    
    /**
     * Ensure expense tables exist
     */
    private function ensure_schema()
    {
        // Expense categories table
        if (!$this->db->table_exists('expense_categories')) {
            $sql = "CREATE TABLE `expense_categories` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `description` text,
                `budget_limit` decimal(10,2) DEFAULT NULL,
                `requires_receipt` tinyint(1) DEFAULT '1',
                `is_active` tinyint(1) DEFAULT '1',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
            
            // Insert default categories
            $categories = [
                ['name' => 'Travel', 'description' => 'Travel expenses', 'budget_limit' => 5000],
                ['name' => 'Food & Meals', 'description' => 'Food and meal expenses', 'budget_limit' => 1000],
                ['name' => 'Accommodation', 'description' => 'Hotel and lodging', 'budget_limit' => 3000],
                ['name' => 'Office Supplies', 'description' => 'Office supplies and stationery', 'budget_limit' => 2000],
                ['name' => 'Communication', 'description' => 'Phone and internet', 'budget_limit' => 1500],
                ['name' => 'Training', 'description' => 'Training and courses', 'budget_limit' => 10000],
                ['name' => 'Other', 'description' => 'Other expenses', 'budget_limit' => 2000]
            ];
            $this->db->insert_batch('expense_categories', $categories);
        }
        
        // Expenses table
        if (!$this->db->table_exists('expenses')) {
            $sql = "CREATE TABLE `expenses` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `category_id` int(11) NOT NULL,
                `amount` decimal(10,2) NOT NULL,
                `description` text NOT NULL,
                `expense_date` date NOT NULL,
                `receipt_path` varchar(500) DEFAULT NULL,
                `status` enum('pending','approved','rejected','reimbursed') DEFAULT 'pending',
                `approved_by` int(11) DEFAULT NULL,
                `approved_at` datetime DEFAULT NULL,
                `rejection_reason` text,
                `reimbursed_by` int(11) DEFAULT NULL,
                `reimbursed_at` datetime DEFAULT NULL,
                `reimbursement_reference` varchar(100) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_status` (`status`),
                KEY `idx_expense_date` (`expense_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $this->db->query($sql);
        }
    }
    
    /**
     * GET /expenses
     * List expenses (my expenses or all for managers)
     */
    public function index()
    {
        $user_id = (int)$this->session->userdata('user_id');
        $role_id = (int)$this->session->userdata('role_id');
        
        // Filters
        $status = $this->input->get('status') ?: 'all';
        $category = $this->input->get('category') ?: 'all';
        $from_date = $this->input->get('from_date');
        $to_date = $this->input->get('to_date');
        
        // Build query
        $this->db->select('expenses.*, users.name as username, expense_categories.name as category_name');
        $this->db->from('expenses');
        $this->db->join('users', 'users.id = expenses.user_id');
        $this->db->join('expense_categories', 'expense_categories.id = expenses.category_id');
        
        // Role-based filtering
        if (!is_admin_group()) {
            // Staff can only see their own expenses
            $this->db->where('expenses.user_id', $user_id);
        }
        
        // Status filter
        if ($status !== 'all') {
            $this->db->where('expenses.status', $status);
        }
        
        // Category filter
        if ($category !== 'all') {
            $this->db->where('expenses.category_id', (int)$category);
        }
        
        // Date range filter
        if ($from_date) {
            $this->db->where('expenses.expense_date >=', $from_date);
        }
        if ($to_date) {
            $this->db->where('expenses.expense_date <=', $to_date);
        }
        
        $this->db->order_by('expenses.created_at', 'DESC');
        $expenses = $this->db->get()->result();
        
        // Get categories for filter
        $categories = $this->db->get_where('expense_categories', ['is_active' => 1])->result();
        
        // Calculate totals
        $totals = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'reimbursed' => 0
        ];
        
        foreach ($expenses as $expense) {
            if (isset($totals[$expense->status])) {
                $totals[$expense->status] += $expense->amount;
            }
        }
        
        $data = [
            'expenses' => $expenses,
            'categories' => $categories,
            'totals' => $totals,
            'filters' => [
                'status' => $status,
                'category' => $category,
                'from_date' => $from_date,
                'to_date' => $to_date
            ]
        ];
        
        $this->load->view('expenses/index', $data);
    }
    
    /**
     * GET/POST /expenses/create
     * Submit new expense claim
     */
    public function create()
    {
        if (function_exists('has_module_access') && !has_module_access('expenses_add') && !has_module_access('expenses')) {
            show_error('You do not have permission to create expense requests.', 403);
        }
        $user_id = (int)$this->session->userdata('user_id');
        
        if ($this->input->method() === 'post') {
            // Validate input
            $category_id = (int)$this->input->post('category_id');
            $amount = (float)$this->input->post('amount');
            $description = trim($this->input->post('description'));
            $expense_date = $this->input->post('expense_date');
            
            // Validation
            if (!$category_id || !$amount || !$description || !$expense_date) {
                $this->session->set_flashdata('error', 'All fields are required.');
                redirect('expenses/create');
                return;
            }
            
            // Check budget limit - block if exceeded
            $category = $this->db->get_where('expense_categories', ['id' => $category_id])->row();
            if ($category && $category->budget_limit && $amount > $category->budget_limit) {
                $this->session->set_flashdata('error', 'Amount exceeds category budget limit of ' . number_format($category->budget_limit, 2) . '. Please reduce the amount or contact your manager.');
                redirect('expenses/create');
                return;
            }
            
            // Handle receipt upload
            $receipt_path = null;
            if (!empty($_FILES['receipt']['name'])) {
                $config['upload_path'] = './uploads/expenses/';
                $config['allowed_types'] = 'jpg|jpeg|png|pdf';
                $config['max_size'] = 5120; // 5MB
                $config['file_name'] = 'expense_' . time() . '_' . $user_id;
                
                // Create directory if not exists
                if (!is_dir($config['upload_path'])) {
                    mkdir($config['upload_path'], 0755, true);
                }
                
                $this->upload->initialize($config);
                
                if ($this->upload->do_upload('receipt')) {
                    $upload_data = $this->upload->data();
                    $receipt_path = 'uploads/expenses/' . $upload_data['file_name'];
                } else {
                    $this->session->set_flashdata('error', $this->upload->display_errors());
                    redirect('expenses/create');
                    return;
                }
            }
            
            // Check if receipt is required
            if ($category && $category->requires_receipt && !$receipt_path) {
                $this->session->set_flashdata('error', 'Receipt is required for this category.');
                redirect('expenses/create');
                return;
            }
            
            // Insert expense
            $data = [
                'user_id' => $user_id,
                'category_id' => $category_id,
                'amount' => $amount,
                'description' => $description,
                'expense_date' => $expense_date,
                'receipt_path' => $receipt_path,
                'status' => 'pending'
            ];
            
            $this->db->insert('expenses', $data);
            $expense_id = $this->db->insert_id();
            
            // Send notification to managers
            $this->load->helper('notification');
            $managers = $this->db->get_where('users', ['role_id' => 2])->result(); // Managers
            foreach ($managers as $manager) {
                create_notification(
                    $manager->id,
                    'New Expense Claim',
                    $this->session->userdata('username') . ' submitted an expense claim for ' . number_format($amount, 2),
                    'info',
                    'expenses',
                    $expense_id,
                    site_url('expenses/view/' . $expense_id)
                );
            }
            
            $this->session->set_flashdata('success', 'Expense claim submitted successfully.');
            redirect('expenses');
        }
        
        // GET request - show form
        $categories = $this->db->get_where('expense_categories', ['is_active' => 1])->result();
        
        $data = ['categories' => $categories];
        $this->load->view('expenses/create', $data);
    }
    
    /**
     * GET /expenses/view/{id}
     * View expense details
     */
    public function view($id)
    {
        $user_id = (int)$this->session->userdata('user_id');
        
        $this->db->select('expenses.*, users.name as username, expense_categories.name as category_name');
        $this->db->from('expenses');
        $this->db->join('users', 'users.id = expenses.user_id');
        $this->db->join('expense_categories', 'expense_categories.id = expenses.category_id');
        $this->db->where('expenses.id', (int)$id);
        
        // Non-admin can only view their own
        if (!is_admin_group()) {
            $this->db->where('expenses.user_id', $user_id);
        }
        
        $expense = $this->db->get()->row();
        
        if (!$expense) {
            show_error('Expense not found', 404);
        }
        
        $data = ['expense' => $expense];
        $this->load->view('expenses/view', $data);
    }
    
    /**
     * POST /expenses/{id}/approve
     * Approve expense claim
     */
    public function approve($id)
    {
        if (!is_admin_group() && !(function_exists('has_module_access') && has_module_access('expenses_approve'))) {
            show_error('Access denied', 403);
        }
        
        $user_id = (int)$this->session->userdata('user_id');
        
        $this->db->where('id', (int)$id);
        $this->db->update('expenses', [
            'status' => 'approved',
            'approved_by' => $user_id,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
        
        // Get expense details and notify employee
        $expense = $this->db->get_where('expenses', ['id' => (int)$id])->row();
        if ($expense) {
            $this->load->helper('notification');
            create_notification(
                $expense->user_id,
                'Expense Approved',
                'Your expense claim for ' . number_format($expense->amount, 2) . ' has been approved.',
                'success',
                'expenses',
                $id,
                site_url('expenses/view/' . $id)
            );
        }
        
        $this->session->set_flashdata('success', 'Expense approved successfully.');
        redirect('expenses');
    }
    
    /**
     * POST /expenses/{id}/reject
     * Reject expense claim
     */
    public function reject($id)
    {
        if (!is_admin_group() && !(function_exists('has_module_access') && has_module_access('expenses_approve'))) {
            show_error('Access denied', 403);
        }
        
        $user_id = (int)$this->session->userdata('user_id');
        $reason = trim($this->input->post('reason'));
        
        $this->db->where('id', (int)$id);
        $this->db->update('expenses', [
            'status' => 'rejected',
            'approved_by' => $user_id,
            'approved_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason
        ]);
        
        // Get expense details and notify employee
        $expense = $this->db->get_where('expenses', ['id' => (int)$id])->row();
        if ($expense) {
            $this->load->helper('notification');
            create_notification(
                $expense->user_id,
                'Expense Rejected',
                'Your expense claim for ' . number_format($expense->amount, 2) . ' has been rejected. Reason: ' . $reason,
                'error',
                'expenses',
                $id,
                site_url('expenses/view/' . $id)
            );
        }
        
        $this->session->set_flashdata('success', 'Expense rejected.');
        redirect('expenses');
    }
    
    /**
     * POST /expenses/{id}/reimburse
     * Mark expense as reimbursed
     */
    public function reimburse($id)
    {
        if (!is_admin_group() && !(function_exists('has_module_access') && has_module_access('expenses_reimburse'))) {
            show_error('Access denied', 403);
        }
        
        $user_id = (int)$this->session->userdata('user_id');
        $reference = trim($this->input->post('reference'));
        
        $this->db->where('id', (int)$id);
        $this->db->where('status', 'approved');
        $this->db->update('expenses', [
            'status' => 'reimbursed',
            'reimbursed_by' => $user_id,
            'reimbursed_at' => date('Y-m-d H:i:s'),
            'reimbursement_reference' => $reference
        ]);
        
        // Get expense details and notify employee
        $expense = $this->db->get_where('expenses', ['id' => (int)$id])->row();
        if ($expense) {
            $this->load->helper('notification');
            create_notification(
                $expense->user_id,
                'Expense Reimbursed',
                'Your expense claim for ' . number_format($expense->amount, 2) . ' has been reimbursed. Reference: ' . $reference,
                'success',
                'expenses',
                $id,
                site_url('expenses/view/' . $id)
            );
        }
        
        $this->session->set_flashdata('success', 'Expense marked as reimbursed.');
        redirect('expenses');
    }
    
    /**
     * GET /expenses/categories
     * Manage expense categories (Admin only)
     */
    public function categories()
    {
        if (!is_admin_group() && !(function_exists('has_module_access') && has_module_access('expenses_categories'))) {
            show_error('Access denied', 403);
        }
        
        $categories = $this->db->get('expense_categories')->result();
        
        $data = ['categories' => $categories];
        $this->load->view('expenses/categories', $data);
    }
    
    /**
     * GET /expenses/reports
     * Expense reports and analytics
     */
    public function reports()
    {
        if (!is_admin_group() && !(function_exists('has_module_access') && has_module_access('expenses_reports'))) {
            show_error('Access denied', 403);
        }
        
        // Monthly expenses
        $sql = "SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, 
                       SUM(amount) as total,
                       COUNT(*) as count
                FROM expenses 
                WHERE status != 'rejected'
                GROUP BY month 
                ORDER BY month DESC 
                LIMIT 12";
        $monthly = $this->db->query($sql)->result();
        
        // By category
        $sql = "SELECT ec.name, 
                       SUM(e.amount) as total,
                       COUNT(*) as count
                FROM expenses e
                JOIN expense_categories ec ON ec.id = e.category_id
                WHERE e.status != 'rejected'
                GROUP BY ec.id
                ORDER BY total DESC";
        $by_category = $this->db->query($sql)->result();
        
        // By user (top 10)
        $sql = "SELECT u.name as username, 
                       SUM(e.amount) as total,
                       COUNT(*) as count
                FROM expenses e
                JOIN users u ON u.id = e.user_id
                WHERE e.status != 'rejected'
                GROUP BY u.id
                ORDER BY total DESC
                LIMIT 10";
        $by_user = $this->db->query($sql)->result();
        
        $data = [
            'monthly' => $monthly,
            'by_category' => $by_category,
            'by_user' => $by_user
        ];
        
        $this->load->view('expenses/reports', $data);
    }

    /**
     * GET /expenses/pending
     * Alias for index with status=pending
     */
    public function pending()
    {
        redirect('expenses?status=pending');
    }

    /**
     * GET /expenses/report
     * Alias for reports
     */
    public function report()
    {
        $this->reports();
    }

    /**
     * GET /expenses/export
     * Export all expenses to CSV
     */
    public function export()
    {
        if (!is_admin_group() && !(function_exists('has_module_access') && (has_module_access('expenses_export') || has_module_access('expenses_reports') || has_module_access('expenses')))) {
            show_error('Access denied', 403);
        }

        $date_from = $this->input->get('date_from') ? $this->input->get('date_from') : date('Y-m-01');
        $date_to   = $this->input->get('date_to')   ? $this->input->get('date_to')   : date('Y-m-d');
        $status    = $this->input->get('status')    ? $this->input->get('status')    : '';

        $this->db->select('e.id, u.name as employee_name, ec.name as category, e.amount, e.expense_date, e.description, e.status, e.created_at');
        $this->db->from('expenses e');
        $this->db->join('users u', 'u.id = e.user_id', 'left');
        $this->db->join('expense_categories ec', 'ec.id = e.category_id', 'left');
        $this->db->where('e.expense_date >=', $date_from);
        $this->db->where('e.expense_date <=', $date_to);
        if ($status !== '') { $this->db->where('e.status', $status); }
        $this->db->order_by('e.expense_date', 'DESC');
        $rows = $this->db->get()->result();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="expenses_' . $date_from . '_to_' . $date_to . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Employee', 'Category', 'Amount', 'Date', 'Description', 'Status', 'Created At']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r->id,
                $r->employee_name,
                $r->category,
                $r->amount,
                $r->expense_date,
                strip_tags($r->description),
                $r->status,
                $r->created_at,
            ]);
        }
        fclose($out);
        exit;
    }
}
