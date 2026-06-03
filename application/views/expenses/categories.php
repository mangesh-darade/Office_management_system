<?php $this->load->view('partials/header', ['title' => 'Expense Categories', 'active' => 'expenses']); ?>

<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <a href="<?php echo site_url('expenses'); ?>" class="btn btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
                    <div>
                        <h5 class="mb-0 fw-bold">Expense Categories</h5>
                        <p class="text-muted mb-0 small">Manage expense types, limits, and receipt rules</p>
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal" data-mode="create">
                    <i class="bi bi-plus-lg me-1"></i>Add Category
                </button>
            </div>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success py-2"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger py-2"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 datatable">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Description</th>
                            <th>Budget Limit</th>
                            <th>Features</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="ps-4 fw-medium"><?php echo htmlspecialchars($cat->name); ?></td>
                            <td class="text-muted small"><?php echo htmlspecialchars($cat->description); ?></td>
                            <td>
                                <?php if ($cat->budget_limit): ?>
                                    <span class="badge bg-light text-dark border"><?php echo number_format($cat->budget_limit, 2); ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">No Limit</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cat->requires_receipt): ?>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info">Receipt Required</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cat->is_active): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary category-edit-btn"
                                    data-id="<?php echo (int) $cat->id; ?>"
                                    data-name="<?php echo htmlspecialchars($cat->name, ENT_QUOTES); ?>"
                                    data-description="<?php echo htmlspecialchars($cat->description, ENT_QUOTES); ?>"
                                    data-budget="<?php echo htmlspecialchars($cat->budget_limit); ?>"
                                    data-receipt="<?php echo (int) $cat->requires_receipt; ?>"
                                    data-active="<?php echo (int) $cat->is_active; ?>">
                                    Edit
                                </button>
                                <form method="post" action="<?php echo site_url('expenses/categories/toggle/' . (int) $cat->id); ?>" class="d-inline">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"><?php echo (int) $cat->is_active ? 'Deactivate' : 'Activate'; ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?php echo site_url('expenses/categories/save'); ?>" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="catId" value="0">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" id="catName" class="form-control" required maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="catDescription" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Budget limit</label>
                    <input type="number" name="budget_limit" id="catBudget" class="form-control" step="0.01" min="0" placeholder="Optional">
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="requires_receipt" value="1" id="catReceipt">
                    <label class="form-check-label" for="catReceipt">Receipt required</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="catActive" checked>
                    <label class="form-check-label" for="catActive">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.category-edit-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('categoryModalTitle').textContent = 'Edit Category';
        document.getElementById('catId').value = btn.getAttribute('data-id');
        document.getElementById('catName').value = btn.getAttribute('data-name');
        document.getElementById('catDescription').value = btn.getAttribute('data-description');
        document.getElementById('catBudget').value = btn.getAttribute('data-budget') || '';
        document.getElementById('catReceipt').checked = btn.getAttribute('data-receipt') === '1';
        document.getElementById('catActive').checked = btn.getAttribute('data-active') === '1';
        new bootstrap.Modal(document.getElementById('categoryModal')).show();
    });
});
document.getElementById('categoryModal').addEventListener('show.bs.modal', function (e) {
    if (e.relatedTarget && e.relatedTarget.getAttribute('data-mode') === 'create') {
        document.getElementById('categoryModalTitle').textContent = 'Add Category';
        document.getElementById('catId').value = '0';
        document.getElementById('catName').value = '';
        document.getElementById('catDescription').value = '';
        document.getElementById('catBudget').value = '';
        document.getElementById('catReceipt').checked = true;
        document.getElementById('catActive').checked = true;
    }
});
</script>

<?php $this->load->view('partials/footer'); ?>
