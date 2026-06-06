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
        $this->load->helper(['url', 'form', 'permission', 'hierarchy_filter']);
        $this->load->library(['session', 'upload']);
        $this->load->model('Expense_model');
        
        // RBAC Audit: Centralized module access check
        require_controller_access('expenses', true);
        
        $this->ensure_schema();
    }
    
    /**
     * Ensure expense tables exist
     */
    private function ensure_schema() {
        $this->load->helper('expenses_schema');
        expenses_schema_ensure($this->db);
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
        apply_role_hierarchy_filter($this->db, 'expenses.user_id');
        
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
        require_module_access(['expenses_add', 'expenses'], true);
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
        apply_role_hierarchy_filter($this->db, 'expenses.user_id');
        
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
        require_module_access(['expenses_approve', 'expenses'], true);
        
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
        require_module_access(['expenses_approve', 'expenses'], true);
        
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
        require_module_access(['expenses_reimburse', 'expenses'], true);
        
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
        require_module_access(['expenses_categories', 'expenses'], true);
        
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
        require_module_access(['expenses_reports', 'expenses'], true);
        
        // Monthly expenses
        $sql = "SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, 
                       SUM(amount) as total,
                       COUNT(*) as count
                FROM expenses 
                WHERE status != 'rejected'
                GROUP BY month 
                ORDER BY month DESC 
                LIMIT 12";
        $this->db->select("DATE_FORMAT(expense_date, '%Y-%m') as month, SUM(amount) as total, COUNT(*) as count", false);
        $this->db->from('expenses');
        $this->db->where('status !=', 'rejected');
        apply_role_hierarchy_filter($this->db, 'user_id');
        $this->db->group_by("DATE_FORMAT(expense_date, '%Y-%m')", false);
        $this->db->order_by('month', 'DESC');
        $this->db->limit(12);
        $monthly = $this->db->get()->result();
        
        // By category
        $this->db->select('ec.name, SUM(e.amount) as total, COUNT(*) as count', false);
        $this->db->from('expenses e');
        $this->db->join('expense_categories ec', 'ec.id = e.category_id');
        $this->db->where('e.status !=', 'rejected');
        apply_role_hierarchy_filter($this->db, 'e.user_id');
        $this->db->group_by('ec.id');
        $this->db->order_by('total', 'DESC');
        $by_category = $this->db->get()->result();
        
        // By user (top 10)
        $this->db->select('u.name as username, SUM(e.amount) as total, COUNT(*) as count', false);
        $this->db->from('expenses e');
        $this->db->join('users u', 'u.id = e.user_id');
        $this->db->where('e.status !=', 'rejected');
        apply_role_hierarchy_filter($this->db, 'e.user_id');
        $this->db->group_by('u.id');
        $this->db->order_by('total', 'DESC');
        $this->db->limit(10);
        $by_user = $this->db->get()->result();
        
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
        require_module_access(['expenses_export', 'expenses_reports', 'expenses'], true);

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
        apply_role_hierarchy_filter($this->db, 'e.user_id');
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
        redirect('expenses');
    }

    /**
     * Fetch expense with optional hierarchy scope.
     */
    private function _fetch_expense($id, $apply_scope = true)
    {
        $this->db->select('expenses.*, users.name as username, expense_categories.name as category_name');
        $this->db->from('expenses');
        $this->db->join('users', 'users.id = expenses.user_id');
        $this->db->join('expense_categories', 'expense_categories.id = expenses.category_id');
        $this->db->where('expenses.id', (int) $id);
        if ($apply_scope) {
            apply_role_hierarchy_filter($this->db, 'expenses.user_id');
        }
        return $this->db->get()->row();
    }

    private function _can_edit_expense($expense)
    {
        if (!$expense) {
            return false;
        }
        $user_id = (int) $this->session->userdata('user_id');
        if ((int) $expense->user_id !== $user_id) {
            if (!(function_exists('has_module_access') && (has_module_access('expenses_edit') || has_module_access('expenses')) && function_exists('is_admin_group') && is_admin_group())) {
                return false;
            }
        } elseif (!(function_exists('has_module_access') && (has_module_access('expenses_edit') || has_module_access('expenses')))) {
            return false;
        }
        return in_array($expense->status, array('pending', 'rejected'), true);
    }

    private function _can_delete_expense($expense)
    {
        if (!$expense) {
            return false;
        }
        $user_id = (int) $this->session->userdata('user_id');
        $is_owner = ((int) $expense->user_id === $user_id);
        if ($is_owner) {
            return in_array($expense->status, array('pending', 'rejected'), true)
                && function_exists('has_module_access')
                && (has_module_access('expenses_delete') || has_module_access('expenses'));
        }
        return function_exists('has_module_access')
            && (has_module_access('expenses_delete') || has_module_access('expenses'))
            && function_exists('is_admin_group') && is_admin_group();
    }

    /**
     * GET/POST /expenses/edit/{id}
     */
    public function edit($id)
    {
        require_module_access(array('expenses_edit', 'expenses'), true);
        $expense = $this->_fetch_expense($id);
        if (!$expense) {
            show_error('Expense not found', 404);
        }
        if (!$this->_can_edit_expense($expense)) {
            show_error('You cannot edit this expense claim.', 403);
        }

        if ($this->input->method() === 'post') {
            $category_id = (int) $this->input->post('category_id');
            $amount = (float) $this->input->post('amount');
            $description = trim((string) $this->input->post('description'));
            $expense_date = $this->input->post('expense_date');

            if (!$category_id || !$amount || $description === '' || !$expense_date) {
                $this->session->set_flashdata('error', 'All fields are required.');
                redirect('expenses/edit/' . (int) $id);
                return;
            }

            $category = $this->db->get_where('expense_categories', array('id' => $category_id))->row();
            if ($category && $category->budget_limit && $amount > $category->budget_limit) {
                $this->session->set_flashdata('error', 'Amount exceeds category budget limit.');
                redirect('expenses/edit/' . (int) $id);
                return;
            }

            $receipt_path = $expense->receipt_path;
            if (!empty($_FILES['receipt']['name'])) {
                $config = array(
                    'upload_path' => './uploads/expenses/',
                    'allowed_types' => 'jpg|jpeg|png|pdf',
                    'max_size' => 5120,
                    'file_name' => 'expense_' . time() . '_' . (int) $expense->user_id,
                );
                if (!is_dir($config['upload_path'])) {
                    mkdir($config['upload_path'], 0755, true);
                }
                $this->upload->initialize($config);
                if ($this->upload->do_upload('receipt')) {
                    $upload_data = $this->upload->data();
                    $receipt_path = 'uploads/expenses/' . $upload_data['file_name'];
                } else {
                    $this->session->set_flashdata('error', strip_tags($this->upload->display_errors('', '')));
                    redirect('expenses/edit/' . (int) $id);
                    return;
                }
            }

            if ($category && $category->requires_receipt && !$receipt_path) {
                $this->session->set_flashdata('error', 'Receipt is required for this category.');
                redirect('expenses/edit/' . (int) $id);
                return;
            }

            $this->db->where('id', (int) $id)->update('expenses', array(
                'category_id' => $category_id,
                'amount' => $amount,
                'description' => $description,
                'expense_date' => $expense_date,
                'receipt_path' => $receipt_path,
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'rejection_reason' => null,
            ));

            $this->session->set_flashdata('success', 'Expense claim updated.');
            redirect('expenses/view/' . (int) $id);
            return;
        }

        $categories = $this->db->get_where('expense_categories', array('is_active' => 1))->result();
        $this->load->view('expenses/edit', array('expense' => $expense, 'categories' => $categories));
    }

    /**
     * POST /expenses/delete/{id}
     */
    public function delete($id)
    {
        require_module_access(array('expenses_delete', 'expenses'), true);
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $expense = $this->_fetch_expense($id);
        if (!$expense) {
            show_error('Expense not found', 404);
        }
        if (!$this->_can_delete_expense($expense)) {
            show_error('You cannot delete this expense claim.', 403);
        }
        if (!empty($expense->receipt_path)) {
            $path = FCPATH . $expense->receipt_path;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->db->where('id', (int) $id)->delete('expenses');
        $this->session->set_flashdata('success', 'Expense claim deleted.');
        redirect('expenses');
    }

    /**
     * POST /expenses/categories/save — create or update category
     */
    public function save_category()
    {
        require_module_access(array('expenses_categories', 'expenses'), true);
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        $name = trim((string) $this->input->post('name'));
        $description = trim((string) $this->input->post('description'));
        $budget = $this->input->post('budget_limit');
        $budget_limit = ($budget !== '' && $budget !== null) ? (float) $budget : null;
        $requires_receipt = $this->input->post('requires_receipt') ? 1 : 0;
        $is_active = $this->input->post('is_active') ? 1 : 0;

        if ($name === '') {
            $this->session->set_flashdata('error', 'Category name is required.');
            redirect('expenses/categories');
            return;
        }

        $payload = array(
            'name' => $name,
            'description' => $description,
            'budget_limit' => $budget_limit,
            'requires_receipt' => $requires_receipt,
            'is_active' => $is_active,
        );

        if ($id > 0) {
            $this->db->where('id', $id)->update('expense_categories', $payload);
            $this->session->set_flashdata('success', 'Category updated.');
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('expense_categories', $payload);
            $this->session->set_flashdata('success', 'Category created.');
        }
        redirect('expenses/categories');
    }

    /**
     * POST /expenses/categories/toggle/{id}
     */
    public function toggle_category($id)
    {
        require_module_access(array('expenses_categories', 'expenses'), true);
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $row = $this->db->get_where('expense_categories', array('id' => (int) $id))->row();
        if (!$row) {
            show_404();
        }
        $this->db->where('id', (int) $id)->update('expense_categories', array(
            'is_active' => (int) $row->is_active === 1 ? 0 : 1,
        ));
        $this->session->set_flashdata('success', 'Category status updated.');
        redirect('expenses/categories');
    }
}
