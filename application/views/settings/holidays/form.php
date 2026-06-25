<?php
$is_edit = isset($action) && $action === 'edit';
$page_title = $is_edit ? 'Edit Holiday' : 'Add Holiday';
$row = isset($row) ? $row : null;
?>
<?php $this->load->view('partials/header', ['title' => $page_title]); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">
    <i class="bi bi-calendar-event me-2"></i><?php echo esc_view($page_title); ?>
  </h1>
  <a href="<?php echo site_url('settings/holidays'); ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Back to Holiday List
  </a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-header bg-light">
    <h5 class="card-title mb-0">
      <i class="bi bi-pencil-square me-2"></i><?php echo esc_view($page_title); ?>
    </h5>
  </div>
  <div class="card-body">
    <form method="post" action="">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Holiday Date</label>
          <input type="date" class="form-control" name="holiday_date"
                 value="<?php echo esc_view(isset($row->holiday_date) ? $row->holiday_date : date('Y-m-d')); ?>"
                 required />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Holiday Name</label>
          <input type="text" class="form-control" name="name"
                 value="<?php echo esc_view(isset($row->name) ? $row->name : ''); ?>"
                 placeholder="e.g., New Year, Independence Day" required />
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Status</label>
          <select class="form-select" name="status">
            <option value="active" <?php echo (!isset($row->status) || $row->status === 'active') ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo (isset($row->status) && $row->status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
          </select>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i> Save Holiday
        </button>
        <a href="<?php echo site_url('settings/holidays'); ?>" class="btn btn-outline-secondary">
          <i class="bi bi-x-lg me-1"></i> Cancel
        </a>
      </div>
    </form>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>

