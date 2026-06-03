<?php $this->load->view('partials/header', ['title' => 'Expense Details', 'active' => 'expenses']); ?>

<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <a href="<?php echo site_url('expenses'); ?>" class="btn btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <h5 class="mb-0 fw-bold">Expense Details #<?php echo $expense->id; ?></h5>
                    </div>
                </div>
                <div>
                     <?php 
                    $statusClass = 'secondary';
                    if ($expense->status == 'pending') $statusClass = 'warning';
                    elseif ($expense->status == 'approved') $statusClass = 'info';
                    elseif ($expense->status == 'reimbursed') $statusClass = 'success';
                    elseif ($expense->status == 'rejected') $statusClass = 'danger';
                    ?>
                    <span class="badge bg-<?php echo $statusClass; ?> text-<?php echo $statusClass; ?> bg-opacity-10 py-2 px-3 border border-<?php echo $statusClass; ?>">
                        <?php echo strtoupper($expense->status); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Details Column -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">Claim Information</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <span class="text-muted small d-block mb-1">Employee</span>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle bg-light border d-flex align-items-center justify-content-center me-2" style="width:36px;height:36px;">
                                    <?php echo strtoupper(substr($expense->username, 0, 1)); ?>
                                </div>
                                <span class="fw-medium"><?php echo htmlspecialchars($expense->username); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block mb-1">Date</span>
                            <span class="fw-medium fs-5"><?php echo date('F d, Y', strtotime($expense->expense_date)); ?></span>
                        </div>
                    </div>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <span class="text-muted small d-block mb-1">Category</span>
                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($expense->category_name); ?></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block mb-1">Amount</span>
                            <span class="fw-bold fs-4 text-primary"><?php echo number_format($expense->amount, 2); ?></span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <span class="text-muted small d-block mb-1">Description</span>
                        <div class="p-3 bg-light rounded border-0">
                            <?php echo nl2br(htmlspecialchars($expense->description)); ?>
                        </div>
                    </div>

                    <?php if($expense->rejection_reason): ?>
                    <div class="alert alert-danger bg-danger bg-opacity-10 border-danger text-danger">
                        <strong>Rejection Reason:</strong><br>
                        <?php echo nl2br(htmlspecialchars($expense->rejection_reason)); ?>
                    </div>
                    <?php endif; ?>

                    <?php if($expense->reimbursement_reference): ?>
                    <div class="alert alert-success bg-success bg-opacity-10 border-success text-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Reimbursed:</strong> <?php echo htmlspecialchars($expense->reimbursement_reference); ?>
                        <div class="small mt-1">
                            <?php if($expense->reimbursed_at) echo 'On ' . date('M d, Y', strtotime($expense->reimbursed_at)); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Receipt Section -->
            <?php if($expense->receipt_path): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">Receipt</h6>
                </div>
                <div class="card-body p-4 text-center bg-light">
                    <?php 
                    $ext = strtolower(pathinfo($expense->receipt_path, PATHINFO_EXTENSION));
                    $fileUrl = base_url($expense->receipt_path);
                    if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                    ?>
                        <img src="<?php echo $fileUrl; ?>" class="img-fluid rounded shadow-sm" style="max-height: 500px;" alt="Receipt">
                        <div class="mt-2">
                            <a href="<?php echo $fileUrl; ?>" download class="btn btn-primary btn-sm">
                                <i class="bi bi-download me-2"></i>Download Image
                            </a>
                        </div>
                    <?php elseif($ext == 'pdf'): ?>
                        <div class="ratio ratio-16x9">
                            <iframe src="<?php echo $fileUrl; ?>" class="rounded shadow-sm"></iframe>
                        </div>
                        <div class="mt-2">
                             <a href="<?php echo $fileUrl; ?>" target="_blank" class="btn btn-outline-primary btn-sm me-2">
                                <i class="bi bi-box-arrow-up-right me-2"></i>Open PDF
                            </a>
                            <a href="<?php echo $fileUrl; ?>" download class="btn btn-primary btn-sm">
                                <i class="bi bi-download me-2"></i>Download PDF
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="py-5">
                            <i class="bi bi-file-earmark-text display-4 text-muted"></i>
                            <div class="mt-3">
                                <a href="<?php echo $fileUrl; ?>" target="_blank" class="btn btn-primary">
                                    <i class="bi bi-download me-2"></i>Download Receipt
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Actions Column -->
        <div class="col-lg-4">
            <?php
              $uid = (int) $this->session->userdata('user_id');
              $canEdit = ((int) $expense->user_id === $uid || (function_exists('is_admin_group') && is_admin_group()))
                && in_array($expense->status, array('pending', 'rejected'), true)
                && function_exists('has_module_access') && (has_module_access('expenses_edit') || has_module_access('expenses'));
              $canDel = (((int) $expense->user_id === $uid && in_array($expense->status, array('pending', 'rejected'), true))
                || (function_exists('is_admin_group') && is_admin_group()))
                && function_exists('has_module_access') && (has_module_access('expenses_delete') || has_module_access('expenses'));
            ?>
            <?php if ($canEdit || $canDel): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3"><h6 class="mb-0 fw-bold">Your Actions</h6></div>
                <div class="card-body d-grid gap-2">
                    <?php if ($canEdit): ?>
                    <a href="<?php echo site_url('expenses/edit/' . (int) $expense->id); ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit claim</a>
                    <?php endif; ?>
                    <?php if ($canDel): ?>
                    <form method="post" action="<?php echo site_url('expenses/delete/' . (int) $expense->id); ?>" onsubmit="return confirm('Delete this expense claim permanently?');">
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash me-1"></i>Delete</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if(function_exists('has_module_access') && (has_module_access('expenses_approve') || has_module_access('expenses'))): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">Actions</h6>
                </div>
                <div class="card-body">
                    <?php if($expense->status == 'pending'): ?>
                        <div class="d-grid gap-2">
                            <form action="<?php echo site_url('expenses/approve/'.$expense->id); ?>" method="post">
                                <button type="submit" class="btn btn-success w-100" onclick="return confirm('Approve this expense claim?');">
                                    <i class="bi bi-check-lg me-2"></i>Approve
                                </button>
                            </form>
                            
                            <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-lg me-2"></i>Reject
                            </button>
                        </div>
                    <?php elseif($expense->status == 'approved'): ?>
                         <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#reimburseModal">
                            <i class="bi bi-cash-coin me-2"></i>Process Reimbursement
                        </button>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-info-circle mb-2 d-block fs-4"></i>
                            No actions available
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Timeline/History (Simplified) -->
             <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">History</h6>
                </div>
                <div class="card-body">
                     <ul class="list-unstyled mb-0 small">
                        <li class="mb-3 position-relative ps-4">
                            <span class="position-absolute top-0 start-0 translate-middle-x bg-light border border-2 rounded-circle" style="width: 10px; height: 10px; border-color: var(--bs-primary)!important;"></span>
                            <div class="fw-bold">Created</div>
                            <div class="text-muted"><?php echo date('M d, Y H:i', strtotime($expense->created_at)); ?></div>
                        </li>
                        <?php if($expense->approved_at): ?>
                        <li class="mb-3 position-relative ps-4">
                            <span class="position-absolute top-0 start-0 translate-middle-x bg-success border border-2 border-white rounded-circle" style="width: 10px; height: 10px;"></span>
                            <div class="fw-bold"><?php echo $expense->status == 'rejected' ? 'Rejected' : 'Approved'; ?></div>
                            <div class="text-muted"><?php echo date('M d, Y H:i', strtotime($expense->approved_at)); ?></div>
                        </li>
                        <?php endif; ?>
                        <?php if($expense->reimbursed_at): ?>
                        <li class="position-relative ps-4">
                            <span class="position-absolute top-0 start-0 translate-middle-x bg-info border border-2 border-white rounded-circle" style="width: 10px; height: 10px;"></span>
                            <div class="fw-bold">Reimbursed</div>
                            <div class="text-muted"><?php echo date('M d, Y H:i', strtotime($expense->reimbursed_at)); ?></div>
                        </li>
                        <?php endif; ?>
                     </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?php echo site_url('expenses/reject/'.$expense->id); ?>" method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Reason for Rejection</label>
                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject Expense</button>
            </div>
        </form>
    </div>
</div>

<!-- Reimburse Modal -->
<div class="modal fade" id="reimburseModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?php echo site_url('expenses/reimburse/'.$expense->id); ?>" method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Reimbursement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Enter the reference number or transaction ID for this reimbursement.</p>
                <div class="mb-3">
                    <label class="form-label">Reference ID / Check #</label>
                    <input type="text" name="reference" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Confirm Reimbursement</button>
            </div>
        </form>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
