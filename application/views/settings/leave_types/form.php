<?php $this->load->view('partials/header', ['title' => (($action === 'edit') ? 'Edit' : 'Create') . ' Leave Type']); ?>
<div class="oms-form-compact">

<!-- Flash Messages -->
<?php if($this->session->flashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <?php echo esc_view($this->session->flashdata('error')); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if($this->session->flashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>
    <?php echo esc_view($this->session->flashdata('success')); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="oms-form-page-head d-flex justify-content-between align-items-center mb-2">
  <h1 class="h4 mb-0">
    <i class="bi bi-calendar-x me-2"></i><?php echo ($action === 'edit') ? 'Edit' : 'Create'; ?> Leave Type
  </h1>
  <a class="btn btn-secondary btn-sm" href="<?php echo site_url('settings/leave-types'); ?>">
    <i class="bi bi-arrow-left me-1"></i>Back to List
  </a>
</div>

<div class="card shadow-soft oms-form-card">
  <div class="card-body">
    <form method="post" id="leaveTypeForm" data-validate="true">
      <div class="row g-2 oms-form-grid">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Leave Type Name <span class="text-danger">*</span></label>
          <input class="form-control" 
                 name="name" 
                 value="<?php echo esc_view(isset($row->name) ? $row->name : ''); ?>" 
                 placeholder="e.g., Annual Leave, Sick Leave, Casual Leave"
                 required 
                 autofocus />
          <div class="form-text">Unique name for this leave type</div>
        </div>
        
        <div class="col-md-6">
          <label class="form-label fw-semibold">Annual Quota (Days)</label>
          <input type="number" 
                 class="form-control" 
                 name="annual_quota" 
                 value="<?php echo isset($row->annual_quota) ? number_format((float)$row->annual_quota, 1) : '0'; ?>" 
                 step="0.5" 
                 min="0" 
                 placeholder="0" />
          <div class="form-text">Default annual leave days for this type (0 = unlimited or set per employee)</div>
        </div>
        
        <div class="col-md-12">
          <label class="form-label fw-semibold">Description</label>
          <textarea class="form-control" 
                    name="description" 
                    rows="3" 
                    placeholder="Brief description of this leave type..."><?php echo esc_view(isset($row->description) ? $row->description : ''); ?></textarea>
          <div class="form-text">Optional description explaining when this leave type should be used</div>
        </div>
        
        <div class="col-md-6">
          <label class="form-label fw-semibold">Leave Type</label>
          <div class="form-check form-switch">
            <input class="form-check-input" 
                   type="checkbox" 
                   name="is_paid" 
                   value="1" 
                   id="is_paid"
                   <?php echo (!isset($row->is_paid) || (int)$row->is_paid === 1) ? 'checked' : ''; ?> />
            <label class="form-check-label" for="is_paid">
              <strong>Paid Leave</strong>
            </label>
          </div>
          <div class="form-text">Uncheck for unpaid leave types (e.g., Leave Without Pay)</div>
        </div>
      </div>
      
      <div class="mt-4 pt-3 border-top">
        <button class="btn btn-primary" type="submit" id="submitBtn">
          <i class="bi bi-check-lg me-1"></i>
          <span class="btn-text"><?php echo ($action === 'edit') ? 'Update Leave Type' : 'Create Leave Type'; ?></span>
          <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
        </button>
        <a class="btn btn-outline-secondary" href="<?php echo site_url('settings/leave-types'); ?>">
          <i class="bi bi-x-lg me-1"></i>Cancel
        </a>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('leaveTypeForm');
  const submitBtn = document.getElementById('submitBtn');
  const nameInput = form.querySelector('input[name="name"]');
  
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Clear previous error states
    nameInput.classList.remove('is-invalid');
    
    // Validation
    let isValid = true;
    
    // Validate leave type name
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
    } else {
      alert('Please fill in all required fields.');
    }
  });
  
  // Clear error states on input
  nameInput.addEventListener('input', function() {
    this.classList.remove('is-invalid');
  });
});
</script>

</div>
<?php $this->load->view('partials/footer'); ?>

