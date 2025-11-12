<?php $this->load->view('partials/header', ['title' => (isset($action) && $action === 'edit') ? 'Edit Designation' : 'Create Designation']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?php echo (isset($action) && $action === 'edit') ? 'Edit Designation' : 'Create Designation'; ?></h1>
  <a class="btn btn-light btn-sm" href="<?php echo site_url('designations'); ?>">Back</a>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Code</label>
          <input type="text" name="designation_code" class="form-control" value="<?php echo htmlspecialchars(isset($row) && isset($row->designation_code) ? $row->designation_code : ''); ?>" required>
        </div>
        <div class="col-md-8">
          <label class="form-label">Name</label>
          <input type="text" name="designation_name" class="form-control" value="<?php echo htmlspecialchars(isset($row) && isset($row->designation_name) ? $row->designation_name : ''); ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Department</label>
          <select name="department_id" class="form-select">
            <option value="">-- Select Department --</option>
            <?php if (isset($departments) && is_array($departments)):
              $currentDept = (isset($row) && isset($row->department_id)) ? (int)$row->department_id : 0;
              foreach ($departments as $d): ?>
                <option value="<?php echo (int)$d->id; ?>" <?php echo ($currentDept === (int)$d->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d->dept_name); ?></option>
            <?php endforeach; endif; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Level</label>
          <input type="number" name="level" min="1" class="form-control" value="<?php echo htmlspecialchars((string)(isset($row) && isset($row->level) ? (int)$row->level : 1)); ?>">
        </div>
      </div>
      <div class="mt-4">
        <button type="submit" class="btn btn-primary"><?php echo (isset($action) && $action === 'edit') ? 'Update' : 'Create'; ?></button>
        <a class="btn btn-light" href="<?php echo site_url('designations'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
