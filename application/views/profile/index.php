<?php $this->load->view('partials/header', ['title' => 'My Profile']); ?>
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0">My Profile</h1>
  </div>

  <?php if($this->session->flashdata('success')): ?>
    <div class="alert alert-success fade show" role="alert">
      <?php echo htmlspecialchars($this->session->flashdata('success')); ?>
    </div>
  <?php endif; ?>
  <?php if($this->session->flashdata('error')): ?>
    <div class="alert alert-danger fade show" role="alert">
      <?php echo htmlspecialchars($this->session->flashdata('error')); ?>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm fade-in">
        <div class="card-body">
          <div class="d-flex align-items-center mb-3">
            <?php
              $displayName = '';
              if (!empty($user) && !empty($user->name)) {
                $displayName = (string)$user->name;
              } elseif (!empty($employee) && (!empty($employee->first_name) || !empty($employee->last_name))) {
                $displayName = trim(($employee->first_name ?? '').' '.($employee->last_name ?? ''));
              } elseif (!empty($user) && !empty($user->email)) {
                $displayName = (string)$user->email;
              } else {
                $displayName = 'User';
              }
              $initial = strtoupper(substr($displayName, 0, 1));
            ?>
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:56px;height:56px;font-weight:600;">
              <?php echo htmlspecialchars($initial); ?>
            </div>
            <div class="ms-3">
              <div class="fw-semibold">
                <?php echo htmlspecialchars($displayName); ?>
              </div>
              <div class="text-muted small">Role: <?php echo htmlspecialchars($user->role ?? 'member'); ?></div>
            </div>
          </div>
          <div class="small text-muted">Email</div>
          <div class="mb-3"><?php echo htmlspecialchars($user->email ?? ''); ?></div>
          <?php if(!empty($employee)): ?>
            <div class="small text-muted">Department</div>
            <div class="mb-2"><?php echo htmlspecialchars($employee->department ?? '-'); ?></div>
            <div class="small text-muted">Designation</div>
            <div class="mb-2"><?php echo htmlspecialchars($employee->designation ?? '-'); ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-8">
      <div class="card shadow-sm fade-in">
        <div class="card-body">
          <h2 class="h6 mb-3">About</h2>
          <p class="text-muted mb-0">Welcome to Sateri Digital Pvt. Ltd. internal Office Management Tool. Your profile aggregates your user and employee data for quick reference.</p>
        </div>
      </div>
      <div class="card shadow-sm mt-3 fade-in">
        <div class="card-body">
          <h2 class="h6 mb-3">Quick Links</h2>
          <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo site_url('tasks/board'); ?>" class="btn btn-primary">Open Task Board</a>
            <a href="<?php echo site_url('attendance/create'); ?>" class="btn btn-outline-primary">Mark Attendance</a>
            <a href="<?php echo site_url('reports'); ?>" class="btn btn-outline-secondary">View Reports</a>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php $this->load->view('partials/footer'); ?>
