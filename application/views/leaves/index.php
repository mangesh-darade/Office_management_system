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
      <div class="col-12 col-lg-6">
        <div class="card shadow-soft">
          <div class="card-body">
            <h5 class="card-title">Test Email</h5>
            <p class="text-muted">Send a test email to verify email configuration.</p>
            <form method="post" action="<?php echo site_url('leaves/test-email'); ?>" class="row gy-2 gx-2 align-items-end">
              <div class="col-12 col-sm-8">
                <label class="form-label">Recipient Email</label>
                <input type="email" name="to" value="<?php echo htmlspecialchars($this->session->userdata('email') ?? ''); ?>" class="form-control" placeholder="name@example.com">
              </div>
              <div class="col-12 col-sm-auto">
                <button type="submit" class="btn btn-primary">Send Test Email</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-6">
        <div class="card shadow-soft">
          <div class="card-body">
            <h5 class="card-title">Coming Soon</h5>
            <p class="mb-0 text-muted">Apply for leave and approvals inbox will be added here.</p>
          </div>
        </div>
      </div>
    </div>
<?php $this->load->view('partials/footer'); ?>
