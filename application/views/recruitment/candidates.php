<?php $this->load->view('partials/header', ['title' => 'Candidates']); ?>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-people text-primary me-2"></i>Candidates</h4>
      <p class="text-muted mb-0 small">Review and manage applicants</p>
    </div>
    <div class="d-flex gap-2">
      <?php if(function_exists('has_module_access') && (has_module_access('recruitment_export') || has_module_access('recruitment') || is_admin_group())): ?>
      <a href="<?php echo site_url('recruitment/export'); ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-download me-1"></i>Export CSV
      </a>
      <?php endif; ?>
      <a href="<?php echo site_url('recruitment'); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-briefcase me-1"></i>Jobs
      </a>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle-fill me-2"></i><?php echo esc_view($this->session->flashdata('success')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2">
      <form method="get" action="<?php echo site_url('recruitment/candidates'); ?>" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label small fw-bold text-uppercase text-muted mb-1">Job</label>
          <select name="job_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">— All Jobs —</option>
            <?php foreach ($jobs as $j): ?>
              <option value="<?php echo $j->id; ?>" <?php echo ($job_id == $j->id) ? 'selected' : ''; ?>>
                <?php echo esc_view($j->title); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small fw-bold text-uppercase text-muted mb-1">Status</label>
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">— All Statuses —</option>
            <?php foreach (['applied' => 'Applied', 'screening' => 'Screening', 'interviewing' => 'Interviewing', 'offered' => 'Offered', 'hired' => 'Hired', 'rejected' => 'Rejected'] as $val => $lbl): ?>
              <option value="<?php echo $val; ?>" <?php echo ($status === $val) ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-auto">
          <a href="<?php echo site_url('recruitment/candidates'); ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th class="d-none d-md-table-cell">Job Applied</th>
              <th class="d-none d-sm-table-cell">Email</th>
              <th>Status</th>
              <th class="d-none d-md-table-cell">Applied</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($candidates)): ?>
              <tr><td colspan="6" class="text-center py-5 text-muted">No candidates found.</td></tr>
            <?php else: ?>
              <?php foreach($candidates as $c): ?>
              <?php
                $badge_map = [
                  'applied'      => 'primary',
                  'screening'    => 'info',
                  'interviewing' => 'warning text-dark',
                  'offered'      => 'purple',
                  'hired'        => 'success',
                  'rejected'     => 'danger',
                ];
                $badge = isset($badge_map[$c->status]) ? $badge_map[$c->status] : 'secondary';
              ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?php echo esc_view($c->first_name . ' ' . $c->last_name); ?></div>
                  <?php if ($c->phone): ?>
                    <small class="text-muted"><?php echo esc_view($c->phone); ?></small>
                  <?php endif; ?>
                </td>
                <td class="d-none d-md-table-cell"><?php echo esc_view($c->job_title ? $c->job_title : '—'); ?></td>
                <td class="d-none d-sm-table-cell"><?php echo esc_view($c->email); ?></td>
                <td><span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($c->status); ?></span></td>
                <td class="d-none d-md-table-cell"><?php echo date('d M Y', strtotime($c->created_at)); ?></td>
                <td class="text-end">
                  <div class="d-flex gap-1 justify-content-end">
                    <a href="<?php echo site_url('recruitment/candidate/' . $c->id); ?>" class="btn btn-sm btn-outline-primary" title="View Profile">
                      <i class="bi bi-eye"></i>
                    </a>
                    <?php if (!in_array($c->status, ['hired', 'rejected']) && function_exists('has_module_access') && (has_module_access('recruitment_interviews') || has_module_access('recruitment') || is_admin_group())): ?>
                    <a href="<?php echo site_url('recruitment/schedule-interview/' . $c->id); ?>" class="btn btn-sm btn-outline-warning" title="Schedule Interview">
                      <i class="bi bi-calendar-event"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($c->resume_path): ?>
                    <a href="<?php echo base_url($c->resume_path); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Download Resume">
                      <i class="bi bi-file-earmark-arrow-down"></i>
                    </a>
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
