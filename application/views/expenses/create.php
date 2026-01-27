<?php $this->load->view('partials/header', ['title' => 'New Expense Claim', 'active' => 'expenses']); ?>

<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center">
                <a href="<?php echo site_url('expenses'); ?>" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h5 class="mb-0 fw-bold">New Expense Claim</h5>
                    <p class="text-muted mb-0 small">Submit a new expense for reimbursement</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <?php if($this->session->flashdata('error')): ?>
                        <div class="alert alert-danger mb-4"><?php echo $this->session->flashdata('error'); ?></div>
                    <?php endif; ?>
                    
                    <?php echo form_open_multipart('expenses/create', ['class' => 'needs-validation', 'novalidate' => '']); ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expense Date</label>
                            <input type="date" name="expense_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="category_id" class="form-select" required id="categorySelect">
                                <option value="">Select Category</option>
                                <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat->id; ?>" 
                                        data-limit="<?php echo $cat->budget_limit; ?>"
                                        data-receipt="<?php echo $cat->requires_receipt; ?>">
                                    <?php echo htmlspecialchars($cat->name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text" id="categoryHelp">Select a category to see limits.</div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-semibold">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="amount" class="form-control" step="0.01" required min="0.01">
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3" required placeholder="Describe the expense detail..."></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-semibold">Receipt / Document</label>
                            <input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            <div class="form-text" id="receiptHelp">Receipt might be required depending on category. Supported: JPG, PNG, PDF. Max 5MB.</div>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-2"></i>Submit Claim
                            </button>
                        </div>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('categorySelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const limit = option.getAttribute('data-limit');
    const requiresReceipt = option.getAttribute('data-receipt') === '1';
    
    let helpText = '';
    if (limit && limit > 0) {
        helpText = 'Budget limit: ' + parseFloat(limit).toFixed(2);
    }
    document.getElementById('categoryHelp').textContent = helpText;
    
    const receiptInput = document.querySelector('input[name="receipt"]');
    if (requiresReceipt) {
        receiptInput.setAttribute('required', '');
        document.getElementById('receiptHelp').innerHTML = '<span class="text-danger">* Receipt is required for this category.</span> Supported: JPG, PNG, PDF.';
    } else {
        receiptInput.removeAttribute('required');
        document.getElementById('receiptHelp').textContent = 'Receipt is optional for this category. Supported: JPG, PNG, PDF.';
    }
});
</script>

<?php $this->load->view('partials/footer'); ?>
