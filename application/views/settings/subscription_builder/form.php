<?php
$is_edit = isset($action) && $action === 'edit';
$page_title = $is_edit ? 'Edit Catalog Row' : 'Add Catalog Row';
$row = isset($row) ? $row : null;
$plans = isset($plans) ? $plans : array();
$industries = isset($industries) ? $industries : array();
?>
<?php $this->load->view('partials/header', ['title' => $page_title]); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">
    <i class="bi bi-sliders me-2"></i><?php echo esc_view($page_title); ?>
  </h1>
  <a href="<?php echo site_url('settings/subscription-builder'); ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Back to Catalog List
  </a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-header bg-light">
    <h5 class="card-title mb-0"><i class="bi bi-pencil-square me-2"></i><?php echo esc_view($page_title); ?></h5>
  </div>
  <div class="card-body">
    <form method="post" action="">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label fw-semibold">Plan</label>
          <input type="text" class="form-control" name="plan" list="sb-plan-list" value="<?php echo esc_view($row ? $row->plan : ''); ?>" required>
          <datalist id="sb-plan-list">
            <?php foreach ($plans as $plan): ?>
            <option value="<?php echo esc_view($plan); ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Industry</label>
          <input type="text" class="form-control" name="industry" list="sb-industry-list" value="<?php echo esc_view($row ? $row->industry : ''); ?>" required>
          <datalist id="sb-industry-list">
            <?php foreach ($industries as $industry): ?>
            <option value="<?php echo esc_view($industry); ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Module</label>
          <input type="text" class="form-control" name="module" value="<?php echo esc_view($row ? $row->module : ''); ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Feature</label>
          <input type="text" class="form-control" name="feature" value="<?php echo esc_view($row ? $row->feature : ''); ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Details</label>
          <textarea class="form-control" name="details" rows="2"><?php echo esc_view($row && $row->details ? $row->details : ''); ?></textarea>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Per Item Setup</label>
          <input type="number" step="0.01" min="0" class="form-control" name="per_item_set_up_charges" value="<?php echo esc_view($row && $row->per_item_set_up_charges !== null && $row->per_item_set_up_charges !== '' ? $row->per_item_set_up_charges : ''); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Item Unit</label>
          <input type="text" class="form-control" name="item_unit" value="<?php echo esc_view($row && $row->item_unit ? $row->item_unit : ''); ?>" placeholder="e.g. Location">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Common Setup Fees</label>
          <input type="number" step="0.01" min="0" class="form-control" name="common_set_up_fees" value="<?php echo esc_view($row && $row->common_set_up_fees !== null && $row->common_set_up_fees !== '' ? $row->common_set_up_fees : ''); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Per Month (Per Item)</label>
          <input type="number" step="0.01" min="0" class="form-control" name="per_item_per_month_maintenances" value="<?php echo esc_view($row && $row->per_item_per_month_maintenances !== null && $row->per_item_per_month_maintenances !== '' ? $row->per_item_per_month_maintenances : ''); ?>">
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i> Save
        </button>
        <a href="<?php echo site_url('settings/subscription-builder'); ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
