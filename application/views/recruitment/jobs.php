<?php $this->load->view('partials/header', ['title' => 'Recruitment - Job Posts']); ?>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-briefcase text-primary me-2"></i>Job Openings</h4>
      <p class="text-muted mb-0 small">Manage open positions and track applicants</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="<?php echo site_url('recruitment/candidates'); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-people me-1"></i>All Candidates
      </a>
      <?php if(function_exists('has_module_access') && (has_module_access('recruitment_export') || has_module_access('recruitment') || is_admin_group())): ?>
      <a href="<?php echo site_url('recruitment/export'); ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-download me-1"></i>Export
      </a>
      <?php endif; ?>
      <?php $is_admin = function_exists('is_admin_group') && is_admin_group(); ?>
      <?php $can_manage_recruitment = $is_admin || (function_exists('has_module_access') && (has_module_access('recruitment_jobs') || has_module_access('recruitment_add') || has_module_access('recruitment'))); ?>
      <?php if($can_manage_recruitment): ?>
      <a href="<?php echo site_url('recruitment/create-job'); ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Post Job
      </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($this->session->flashdata('success')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($this->session->flashdata('error')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Status filter tabs -->
  <ul class="nav nav-pills mb-3 gap-1">
    <li class="nav-item">
      <a class="nav-link <?php echo !$status ? 'active' : ''; ?>" href="<?php echo site_url('recruitment'); ?>">All</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php echo $status === 'open' ? 'active' : ''; ?>" href="<?php echo site_url('recruitment?status=open'); ?>">Open</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php echo $status === 'closed' ? 'active' : ''; ?>" href="<?php echo site_url('recruitment?status=closed'); ?>">Closed</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php echo $status === 'draft' ? 'active' : ''; ?>" href="<?php echo site_url('recruitment?status=draft'); ?>">Draft</a>
    </li>
  </ul>

  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Job Title</th>
              <th class="d-none d-md-table-cell">Department</th>
              <th class="d-none d-sm-table-cell">Positions</th>
              <th>Status</th>
              <th class="d-none d-md-table-cell">Posted</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($jobs)): ?>
              <tr><td colspan="6" class="text-center py-5 text-muted">No job postings found.</td></tr>
            <?php else: ?>
              <?php foreach($jobs as $job): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?php echo htmlspecialchars($job->title); ?></div>
                  <?php if (!empty($job->experience_level)): ?>
                    <small class="text-muted"><?php echo htmlspecialchars($job->experience_level); ?></small>
                  <?php endif; ?>
                </td>
                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($job->department ? $job->department : '—'); ?></td>
                <td class="d-none d-sm-table-cell"><?php echo (int)$job->positions; ?></td>
                <td>
                  <?php
                    $badge = 'secondary';
                    if ($job->status === 'open')   { $badge = 'success'; }
                    if ($job->status === 'draft')  { $badge = 'warning text-dark'; }
                    if ($job->status === 'closed') { $badge = 'secondary'; }
                  ?>
                  <span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($job->status); ?></span>
                </td>
                <td class="d-none d-md-table-cell"><?php echo date('d M Y', strtotime($job->created_at)); ?></td>
                <td class="text-end">
                  <div class="d-flex gap-1 justify-content-end flex-wrap">
                    <a href="<?php echo site_url('recruitment/candidates?job_id=' . $job->id); ?>" class="btn btn-sm btn-outline-info" title="View Candidates">
                      <i class="bi bi-people"></i>
                    </a>
                    <?php if ($job->status === 'open'): ?>
                    <a href="<?php echo site_url('recruitment/apply/' . $job->id); ?>" class="btn btn-sm btn-outline-primary" title="Apply Link" target="_blank">
                      <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($can_manage_recruitment): ?>
                    <a href="<?php echo site_url('recruitment/edit-job/' . $job->id); ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <?php if ($job->status === 'open'): ?>
                    <?php echo form_open('recruitment/close-job/' . $job->id, ['class' => 'd-inline', 'onsubmit' => "return confirm('Close this job posting?');"]); ?>
                      <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                      <button type="submit" class="btn btn-sm btn-outline-secondary" title="Close Job"><i class="bi bi-x-circle"></i></button>
                    <?php echo form_close(); ?>
                    <?php endif; ?>
                    <?php if(function_exists('has_module_access') && (has_module_access('recruitment_delete') || has_module_access('recruitment') || $is_admin)): ?>
                    <?php echo form_open('recruitment/delete-job/' . $job->id, ['class' => 'd-inline', 'onsubmit' => "return confirm('Permanently delete this job posting?');"]); ?>
                      <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    <?php echo form_close(); ?>
                    <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
