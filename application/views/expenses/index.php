<?php $this->load->view('partials/header', ['title' => 'Expenses', 'active' => 'expenses']); ?>
<div class="container-fluid py-3 oms-fluid-pad">
    <div class="row g-2 mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">Expense Claims</h5>
                    <p class="text-muted mb-0 small">Manage expense claims and reimbursements</p>
                </div>
                <?php if(function_exists('has_module_access') && (has_module_access('expenses_add') || has_module_access('expenses'))): ?>
                <a href="<?php echo site_url('expenses/create'); ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>New Claim
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="flex-shrink-0 me-3">
                            <span class="avatar-sm rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center">
                                <i class="bi bi-hourglass-split"></i>
                            </span>
                        </div>
                        <div>
                            <h6 class="card-title text-muted small mb-0">Pending</h6>
                            <h4 class="mb-0"><?php echo number_format($totals['pending'], 2); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="flex-shrink-0 me-3">
                            <span class="avatar-sm rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center">
                                <i class="bi bi-check-lg"></i>
                            </span>
                        </div>
                        <div>
                            <h6 class="card-title text-muted small mb-0">Approved</h6>
                            <h4 class="mb-0"><?php echo number_format($totals['approved'], 2); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="flex-shrink-0 me-3">
                            <span class="avatar-sm rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center">
                                <i class="bi bi-cash-coin"></i>
                            </span>
                        </div>
                        <div>
                            <h6 class="card-title text-muted small mb-0">Reimbursed</h6>
                            <h4 class="mb-0"><?php echo number_format($totals['reimbursed'], 2); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="flex-shrink-0 me-3">
                            <span class="avatar-sm rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center">
                                <i class="bi bi-x-lg"></i>
                            </span>
                        </div>
                        <div>
                            <h6 class="card-title text-muted small mb-0">Rejected</h6>
                            <h4 class="mb-0"><?php echo number_format($totals['rejected'], 2); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="get" action="<?php echo site_url('expenses'); ?>" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all">All Status</option>
                        <option value="pending" <?php echo $filters['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filters['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="reimbursed" <?php echo $filters['status'] == 'reimbursed' ? 'selected' : ''; ?>>Reimbursed</option>
                        <option value="rejected" <?php echo $filters['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="all">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat->id; ?>" <?php echo $filters['category'] == $cat->id ? 'selected' : ''; ?>>
                            <?php echo esc_view($cat->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo esc_view($filters['from_date']); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo esc_view($filters['to_date']); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                    <?php if($filters['status'] != 'all' || $filters['category'] != 'all' || $filters['from_date'] || $filters['to_date']): ?>
                    <a href="<?php echo site_url('expenses'); ?>" class="btn btn-link btn-sm w-100 text-decoration-none text-muted">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Expenses List -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 border-bottom-0">ID</th>
                            <th class="border-bottom-0">Date</th>
                            <th class="border-bottom-0">User</th>
                            <th class="border-bottom-0">Category</th>
                            <th class="border-bottom-0">Description</th>
                            <th class="border-bottom-0">Amount</th>
                            <th class="border-bottom-0">Status</th>
                            <th class="pe-4 border-bottom-0 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($expenses)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    No expenses found
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($expenses as $expense): ?>
                            <tr>
                                <td class="ps-4">#<?php echo $expense->id; ?></td>
                                <td><?php echo date('M d, Y', strtotime($expense->expense_date)); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width:24px;height:24px;font-size:10px;">
                                            <?php echo strtoupper(substr($expense->username, 0, 1)); ?>
                                        </div>
                                        <span><?php echo esc_view($expense->username); ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?php echo esc_view($expense->category_name); ?></span></td>
                                <td>
                                    <span class="d-inline-block text-truncate" style="max-width: 200px;" title="<?php echo esc_view($expense->description); ?>">
                                        <?php echo esc_view($expense->description); ?>
                                    </span>
                                    <?php if($expense->receipt_path): ?>
                                    <a href="<?php echo base_url($expense->receipt_path); ?>" target="_blank" class="text-decoration-none" title="View Receipt">
                                        <i class="bi bi-paperclip text-primary ms-1"></i>
                                    </a>
                                    <a href="<?php echo base_url($expense->receipt_path); ?>" download class="text-decoration-none" title="Download Receipt">
                                        <i class="bi bi-download text-secondary ms-1 small"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold"><?php echo number_format($expense->amount, 2); ?></td>
                                <td>
                                    <?php 
                                    $statusClass = 'secondary';
                                    if ($expense->status == 'pending') $statusClass = 'warning';
                                    elseif ($expense->status == 'approved') $statusClass = 'info';
                                    elseif ($expense->status == 'reimbursed') $statusClass = 'success';
                                    elseif ($expense->status == 'rejected') $statusClass = 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?> bg-opacity-10 text-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($expense->status); ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end text-nowrap">
                                    <a href="<?php echo site_url('expenses/view/'.$expense->id); ?>" class="btn btn-sm btn-light" title="View"><i class="bi bi-eye"></i></a>
                                    <?php
                                      $uid = (int) $this->session->userdata('user_id');
                                      $canEdit = ((int) $expense->user_id === $uid || (function_exists('is_admin_group') && is_admin_group()))
                                        && in_array($expense->status, array('pending', 'rejected'), true)
                                        && function_exists('has_module_access') && (has_module_access('expenses_edit') || has_module_access('expenses'));
                                      $canDel = (((int) $expense->user_id === $uid && in_array($expense->status, array('pending', 'rejected'), true))
                                        || (function_exists('is_admin_group') && is_admin_group()))
                                        && function_exists('has_module_access') && (has_module_access('expenses_delete') || has_module_access('expenses'));
                                    ?>
                                    <?php if ($canEdit): ?>
                                    <a href="<?php echo site_url('expenses/edit/'.$expense->id); ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <?php endif; ?>
                                    <?php if ($canDel): ?>
                                    <form method="post" action="<?php echo site_url('expenses/delete/'.$expense->id); ?>" class="d-inline" onsubmit="return confirm('Delete this expense claim?');">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm { width: 40px; height: 40px; }
</style>
<?php $this->load->view('partials/footer'); ?>
