<?php $this->load->view('partials/header', ['title' => (isset($action) && $action === 'edit') ? 'Edit Designation' : 'Create Designation']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?php echo (isset($action) && $action === 'edit') ? 'Edit Designation' : 'Create Designation'; ?></h1>
  <a class="btn btn-light btn-sm" href="<?php echo site_url('designations'); ?>">Back</a>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <?php echo htmlspecialchars($this->session->flashdata('error')); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>
    <?php echo htmlspecialchars($this->session->flashdata('success')); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="" id="designationForm">
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
        <button type="submit" class="btn btn-primary" id="submitBtn">
          <span class="btn-text"><?php echo (isset($action) && $action === 'edit') ? 'Update' : 'Create'; ?></span>
          <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
        </button>
        <a class="btn btn-light" href="<?php echo site_url('designations'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('designationForm');
  const submitBtn = document.getElementById('submitBtn');
  const codeInput = form.querySelector('input[name="designation_code"]');
  const nameInput = form.querySelector('input[name="designation_name"]');
  
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Clear previous error states
    codeInput.classList.remove('is-invalid');
    nameInput.classList.remove('is-invalid');
    
    // Validation
    let isValid = true;
    
    // Validate designation code
    if (!codeInput.value.trim()) {
      codeInput.classList.add('is-invalid');
      isValid = false;
    }
    
    // Validate designation name
    if (!nameInput.value.trim()) {
      nameInput.classList.add('is-invalid');
      isValid = false;
    }
    
    if (isValid) {
      // Show loading state
      submitBtn.disabled = true;
      submitBtn.querySelector('.btn-text').classList.add('d-none');
      submitBtn.querySelector('.spinner-border').classList.remove('d-none');
      
      // Submit form
      form.submit();
    }
  });
  
  // Clear error states on input
  [codeInput, nameInput].forEach(function(input) {
    input.addEventListener('input', function() {
      this.classList.remove('is-invalid');
    });
  });
});
</script>

<?php $this->load->view('partials/footer'); ?>
