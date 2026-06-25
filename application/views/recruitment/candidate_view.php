<?php $this->load->view('partials/header', ['title' => 'Candidate Profile']); ?>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-1 fw-bold">
        <i class="bi bi-person-badge text-primary me-2"></i>
        <?php echo esc_view($candidate->first_name . ' ' . $candidate->last_name); ?>
      </h4>
      <p class="text-muted mb-0 small">Candidate for: <strong><?php echo esc_view($candidate->job_title ? $candidate->job_title : 'Unknown Position'); ?></strong></p>
    </div>
    <a href="<?php echo site_url('recruitment/candidates'); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle-fill me-2"></i><?php echo esc_view($this->session->flashdata('success')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo esc_view($this->session->flashdata('error')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <!-- Left: Profile Card -->
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-body text-center p-4">
          <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
               style="width:72px;height:72px;font-size:2rem;font-weight:700;">
            <?php echo strtoupper(substr($candidate->first_name, 0, 1)); ?>
          </div>
          <h5 class="fw-bold mb-1"><?php echo esc_view($candidate->first_name . ' ' . $candidate->last_name); ?></h5>
          <p class="text-muted small mb-3"><?php echo esc_view($candidate->email); ?></p>

          <?php
            $badge_map = [
              'applied'      => 'primary',
              'screening'    => 'info',
              'interviewing' => 'warning text-dark',
              'offered'      => 'secondary',
              'hired'        => 'success',
              'rejected'     => 'danger',
            ];
            $badge = isset($badge_map[$candidate->status]) ? $badge_map[$candidate->status] : 'secondary';
          ?>
          <span class="badge bg-<?php echo $badge; ?> fs-6 px-3 py-2"><?php echo ucfirst($candidate->status); ?></span>
        </div>
        <div class="card-footer bg-transparent">
          <dl class="row mb-0 small">
            <?php if ($candidate->phone): ?>
            <dt class="col-5 text-muted">Phone</dt>
            <dd class="col-7"><?php echo esc_view($candidate->phone); ?></dd>
            <?php endif; ?>
            <dt class="col-5 text-muted">Applied</dt>
            <dd class="col-7"><?php echo date('d M Y', strtotime($candidate->created_at)); ?></dd>
            <?php if ($candidate->resume_path): ?>
            <dt class="col-5 text-muted">Resume</dt>
            <dd class="col-7"><a href="<?php echo base_url($candidate->resume_path); ?>" target="_blank" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2"><i class="bi bi-download me-1"></i>Download</a></dd>
            <?php endif; ?>
          </dl>
        </div>
      </div>

      <!-- Update Status -->
      <?php if(function_exists('has_module_access') && (has_module_access('recruitment_candidates') || has_module_access('recruitment') || is_admin_group())): ?>
      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent fw-semibold">Update Status</div>
        <div class="card-body">
          <form method="post" action="<?php echo site_url('recruitment/candidate/' . $candidate->id . '/status'); ?>">
            <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
            <div class="mb-2">
              <select name="status" class="form-select form-select-sm">
                <?php foreach (['applied' => 'Applied', 'screening' => 'Screening', 'interviewing' => 'Interviewing', 'offered' => 'Offered', 'hired' => 'Hired', 'rejected' => 'Rejected'] as $val => $lbl): ?>
                  <option value="<?php echo $val; ?>" <?php echo ($candidate->status === $val) ? 'selected' : ''; ?>>
                    <?php echo $lbl; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm w-100">Update Status</button>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right: Interviews -->
    <div class="col-12 col-lg-8">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
          <span class="fw-semibold"><i class="bi bi-calendar-event me-2"></i>Interviews</span>
          <?php if (!in_array($candidate->status, ['hired', 'rejected']) && function_exists('has_module_access') && (has_module_access('recruitment_interviews') || has_module_access('recruitment') || is_admin_group())): ?>
          <a href="<?php echo site_url('recruitment/schedule-interview/' . $candidate->id); ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Schedule Interview
          </a>
          <?php endif; ?>
        </div>
        <div class="card-body p-0">
          <?php if (empty($interviews)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>No interviews scheduled yet.
            </div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Date & Time</th>
                  <th>Type</th>
                  <th>Interviewer</th>
                  <th>Status</th>
                  <th class="d-none d-md-table-cell">Notes</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($interviews as $iv): ?>
                <tr>
                  <td><?php echo date('d M Y H:i', strtotime($iv->interview_date)); ?></td>
                  <td><?php echo esc_view($iv->type); ?></td>
                  <td><?php echo esc_view($iv->interviewer_name ? $iv->interviewer_name : '—'); ?></td>
                  <td>
                    <?php
                      $ibadge = ['scheduled' => 'warning text-dark', 'completed' => 'success', 'cancelled' => 'secondary'];
                      $ib = isset($ibadge[$iv->status]) ? $ibadge[$iv->status] : 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $ib; ?>"><?php echo ucfirst($iv->status); ?></span>
                  </td>
                  <td class="d-none d-md-table-cell small text-muted"><?php echo esc_view($iv->notes ? $iv->notes : '—'); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Pipeline Progress -->
      <div class="card shadow-sm border-0 mt-3">
        <div class="card-header bg-transparent fw-semibold"><i class="bi bi-funnel me-2"></i>Pipeline Stage</div>
        <div class="card-body">
          <?php
            $stages = ['applied', 'screening', 'interviewing', 'offered', 'hired'];
            $rejected = ($candidate->status === 'rejected');
            $current_idx = array_search($candidate->status, $stages);
          ?>
          <?php if ($rejected): ?>
            <div class="alert alert-danger mb-0"><i class="bi bi-x-circle-fill me-2"></i>This candidate has been <strong>rejected</strong>.</div>
          <?php else: ?>
          <div class="d-flex align-items-center gap-1 flex-wrap">
            <?php foreach ($stages as $i => $stage): ?>
              <?php $done = ($current_idx !== false && $i <= $current_idx); ?>
              <div class="d-flex align-items-center">
                <div class="rounded-pill px-3 py-1 small fw-semibold <?php echo $done ? 'bg-primary text-white' : 'bg-light text-muted border'; ?>">
                  <?php echo ucfirst($stage); ?>
                </div>
                <?php if ($i < count($stages) - 1): ?>
                  <i class="bi bi-chevron-right text-muted mx-1 small"></i>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
