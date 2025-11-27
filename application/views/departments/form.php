<?php $this->load->view('partials/header', ['title' => (($action === 'edit') ? 'Edit' : 'Create').' Department']); ?>

<!-- Flash Messages -->
<?php if($this->session->flashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <?php echo htmlspecialchars($this->session->flashdata('error')); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if($this->session->flashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>
    <?php echo htmlspecialchars($this->session->flashdata('success')); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?php echo ($action === 'edit') ? 'Edit' : 'Create'; ?> Department</h1>
  <a class="btn btn-secondary btn-sm" href="<?php echo site_url('departments'); ?>">Back</a>
</div>
<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" id="departmentForm">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Code</label>
          <input class="form-control" name="dept_code" value="<?php echo htmlspecialchars(isset($row->dept_code)?$row->dept_code:''); ?>" required />
        </div>
        <div class="col-md-8">
          <label class="form-label">Name</label>
          <input class="form-control" name="dept_name" value="<?php echo htmlspecialchars(isset($row->dept_name)?$row->dept_name:''); ?>" required />
        </div>
        <div class="col-md-12">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars(isset($row->description)?$row->description:''); ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Manager (User)</label>
          <select class="form-select" name="manager_id">
            <option value="">-- Select --</option>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo (int)$u->id; ?>" <?php echo (isset($row->manager_id) && (int)$row->manager_id===(int)$u->id)?'selected':''; ?>><?php echo htmlspecialchars($u->email.(!empty($u->name)?' ('.$u->name.')':'')); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="mt-3">
        <button class="btn btn-primary" type="submit" id="submitBtn">
          <span class="btn-text"><?php echo ($action === 'edit') ? 'Update' : 'Create'; ?></span>
          <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('departmentForm');
  const submitBtn = document.getElementById('submitBtn');
  const deptCodeInput = form.querySelector('input[name="dept_code"]');
  const deptNameInput = form.querySelector('input[name="dept_name"]');
  
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Clear previous error states
    deptCodeInput.classList.remove('is-invalid');
    deptNameInput.classList.remove('is-invalid');
    
    // Validation
    let isValid = true;
    
    // Validate department code
    if (!deptCodeInput.value.trim()) {
      deptCodeInput.classList.add('is-invalid');
      isValid = false;
    }
    
    // Validate department name
    if (!deptNameInput.value.trim()) {
      deptNameInput.classList.add('is-invalid');
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
  [deptCodeInput, deptNameInput].forEach(function(input) {
    input.addEventListener('input', function() {
      this.classList.remove('is-invalid');
    });
  });
});
</script>

<?php $this->load->view('partials/footer'); ?>
