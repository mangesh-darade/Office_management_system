<?php $this->load->view('partials/header', array('title' => 'Edit Expense Claim', 'active' => 'expenses')); ?>

<div class="container-fluid p-0">
  <div class="row mb-3">
    <div class="col-12">
      <div class="d-flex align-items-center">
        <a href="<?php echo site_url('expenses/view/' . (int) $expense->id); ?>" class="btn btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
        <div>
          <h5 class="mb-0 fw-bold">Edit Expense #<?php echo (int) $expense->id; ?></h5>
          <p class="text-muted mb-0 small">Update and resubmit for approval</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
          <?php endif; ?>

          <?php echo form_open_multipart('expenses/edit/' . (int) $expense->id); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Expense Date</label>
              <input type="date" name="expense_date" class="form-control" required value="<?php echo htmlspecialchars($expense->expense_date); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Category</label>
              <select name="category_id" class="form-select" required id="categorySelect">
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo (int) $cat->id; ?>" data-limit="<?php echo htmlspecialchars($cat->budget_limit); ?>" data-receipt="<?php echo (int) $cat->requires_receipt; ?>" <?php echo (int) $expense->category_id === (int) $cat->id ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat->name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Amount</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" name="amount" class="form-control" step="0.01" required min="0.01" value="<?php echo htmlspecialchars($expense->amount); ?>">
              </div>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Description</label>
              <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($expense->description); ?></textarea>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Receipt / Document</label>
              <?php if (!empty($expense->receipt_path)): ?>
                <p class="small text-muted mb-1">Current: <a href="<?php echo base_url($expense->receipt_path); ?>" target="_blank">View receipt</a></p>
              <?php endif; ?>
              <input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
              <div class="form-text">Leave blank to keep existing file.</div>
            </div>
            <div class="col-12 mt-4">
              <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-2"></i>Save Changes</button>
            </div>
          </div>
          <?php echo form_close(); ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
