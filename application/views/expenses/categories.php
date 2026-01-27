<?php $this->load->view('partials/header', ['title' => 'Expense Categories', 'active' => 'expenses']); ?>

<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <a href="<?php echo site_url('expenses'); ?>" class="btn btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <h5 class="mb-0 fw-bold">Expense Categories</h5>
                        <p class="text-muted mb-0 small">Manage expense types and limits</p>
                    </div>
                </div>
                <!-- Note: Add/Edit functionality not implemented in controller yet -->
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Description</th>
                            <th>Budget Limit</th>
                            <th>Features</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $cat): ?>
                        <tr>
                            <td class="ps-4 fw-medium"><?php echo htmlspecialchars($cat->name); ?></td>
                            <td class="text-muted small"><?php echo htmlspecialchars($cat->description); ?></td>
                            <td>
                                <?php if($cat->budget_limit): ?>
                                    <span class="badge bg-light text-dark border"><?php echo number_format($cat->budget_limit, 2); ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">No Limit</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($cat->requires_receipt): ?>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info">Receipt Required</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($cat->is_active): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill me-1"></i>Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
