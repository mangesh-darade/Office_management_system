<?php $this->load->view('partials/header', ['title' => 'Leave Management']); ?>
  
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
      <h1 class="h4 mb-2 mb-sm-0">Leave Management</h1>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?php echo site_url('leaves/export'); ?>">Export Leaves CSV</a>
      </div>
    </div>

    <?php if($this->session->flashdata('success')): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
    <?php endif; ?>
    <?php if($this->session->flashdata('error')): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-12">
        <div class="card shadow-soft">
          <div class="card-body">
            <h5 class="card-title">Quick Actions</h5>
            <p class="text-muted">Jump directly to the main leave workflows.</p>
            <div class="d-grid gap-2">
              <a href="<?php echo site_url('leave/apply'); ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil-square me-1"></i> Apply Leave
              </a>
              <a href="<?php echo site_url('leave/my'); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-person-check me-1"></i> My Leaves
              </a>
              <a href="<?php echo site_url('leave/team'); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-people me-1"></i> Team Leaves
              </a>
              <a href="<?php echo site_url('leave/calendar'); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-calendar3 me-1"></i> Leave Calendar
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php $this->load->view('partials/footer'); ?>
