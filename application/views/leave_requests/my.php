<?php $this->load->view('partials/header', ['title' => 'My Leaves']); ?>
<div class="container-fluid py-3">
<div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
  <div>
    <h1 class="h4 mb-1 fw-bold"><i class="bi bi-calendar2-check text-primary me-2"></i>My Leave Requests</h1>
    <p class="text-muted small mb-0">View and track your leave applications</p>
  </div>
  <?php if(function_exists('has_module_access') && (has_module_access('leaves_add') || has_module_access('leaves') || has_module_access('leave_requests'))): ?>
  <a class="btn btn-primary btn-sm mt-2 mt-sm-0" href="<?php echo site_url('leave/apply'); ?>"><i class="bi bi-plus-lg me-1"></i>Apply Leave</a>
  <?php endif; ?>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <form method="get" class="row g-2">
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="">All</option>
          <?php $statuses=['pending','lead_approved','hr_approved','approved','rejected','cancelled'];
          foreach ($statuses as $st): ?>
            <option value="<?php echo $st; ?>" <?php echo (isset($filters['status']) && $filters['status']===$st)?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ', $st)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">From</label>
        <input type="date" class="form-control" name="from" value="<?php echo esc_view(isset($filters['start_date']) ? $filters['start_date'] : ''); ?>" />
      </div>
      <div class="col-md-3">
        <label class="form-label">To</label>
        <input type="date" class="form-control" name="to" value="<?php echo esc_view(isset($filters['end_date']) ? $filters['end_date'] : ''); ?>" />
      </div>
      <div class="col-md-3 align-self-end">
        <button class="btn btn-outline-secondary">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>Type</th>
            <th>Dates</th>
            <th>Days</th>
            <th>Status</th>
            <th>Applied On</th>
            <th>Reason</th>
            <th>Comments</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center text-muted">No leave requests found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><?php echo esc_view(isset($r->type_name) ? $r->type_name : ''); ?></td>
              <td>
                <?php
                  $sd = isset($r->start_date) ? (string)$r->start_date : '';
                  $ed = isset($r->end_date) ? (string)$r->end_date : '';
                  if ($sd !== '' && $sd === $ed) {
                    echo esc_view($sd);
                  } else {
                    echo esc_view($sd.' to '.$ed);
                  }
                ?>
              </td>
              <td>
                <?php
                  $daysVal = isset($r->days) ? (float)$r->days : 0.0;
                  $daysText = (fmod($daysVal, 1.0) === 0.0)
                    ? (string)(int)$daysVal
                    : rtrim(rtrim(number_format($daysVal, 2, '.', ''), '0'), '.');
                  if ($daysVal === 0.5) {
                    $daysText .= ' (Half Day)';
                  }
                  echo esc_view($daysText);
                ?>
              </td>
              <td>
                <?php 
                  $status = strtolower($r->status);
                  $badge_class = 'bg-secondary';
                  if (in_array($status, ['lead_approved', 'hr_approved', 'approved'], true)) {
                    $badge_class = 'bg-success';
                  } elseif ($status === 'rejected') {
                    $badge_class = 'bg-danger';
                  } elseif ($status === 'pending') {
                    $badge_class = 'bg-warning text-dark';
                  } elseif ($status === 'cancelled') {
                    $badge_class = 'bg-secondary';
                  }
                ?>
                <span class="badge <?php echo $badge_class; ?>"><?php echo esc_view(ucfirst(str_replace('_',' ', $r->status))); ?></span>
              </td>
              <td><?php echo esc_view(isset($r->created_at) ? $r->created_at : ''); ?></td>
              <td style="max-width: 340px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php echo esc_view(isset($r->reason) ? $r->reason : ''); ?>
              </td>
              <td style="max-width: 300px;">
                <?php
                  $my_decision = isset($r->decision) ? strtolower(trim((string) $r->decision)) : '';
                  $my_comments = isset($r->comments) ? trim((string) $r->comments) : '';
                  if ($my_comments !== '' && $my_decision !== '' && strcasecmp($my_comments, $my_decision) === 0) {
                    $my_comments = '';
                  }
                  $my_approver = isset($r->approver_name) ? trim((string) $r->approver_name) : '';
                  $my_has = ($my_decision !== '' || $my_comments !== '' || $my_approver !== '');
                ?>
                <?php if ($my_has): ?>
                  <div class="small">
                    <?php if ($my_approver !== ''): ?>
                    <div class="fw-semibold"><?php echo esc_view($my_approver); ?></div>
                    <?php endif; ?>
                    <?php if ($my_decision !== ''): ?>
                    <span class="badge <?php echo ($my_decision === 'rejected') ? 'bg-danger' : 'bg-success'; ?>"><?php echo esc_view(ucfirst($my_decision)); ?></span>
                    <?php endif; ?>
                    <?php if ($my_comments !== ''): ?>
                    <div class="mt-1"><?php echo esc_view($my_comments); ?></div>
                    <?php else: ?>
                    <div class="mt-1 text-muted">No comment text</div>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="text-muted small">No comments</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<?php $this->load->view('partials/footer'); ?>
