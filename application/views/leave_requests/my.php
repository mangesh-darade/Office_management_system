<?php $this->load->view('partials/header', ['title' => 'My Leaves']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">My Leave Requests</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('leave/apply'); ?>">Apply Leave</a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
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
        <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars(isset($filters['start_date']) ? $filters['start_date'] : ''); ?>" />
      </div>
      <div class="col-md-3">
        <label class="form-label">To</label>
        <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars(isset($filters['end_date']) ? $filters['end_date'] : ''); ?>" />
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
              <td><?php echo htmlspecialchars(isset($r->type_name) ? $r->type_name : ''); ?></td>
              <td>
                <?php
                  $sd = isset($r->start_date) ? (string)$r->start_date : '';
                  $ed = isset($r->end_date) ? (string)$r->end_date : '';
                  if ($sd !== '' && $sd === $ed) {
                    echo htmlspecialchars($sd);
                  } else {
                    echo htmlspecialchars($sd.' to '.$ed);
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
                  echo htmlspecialchars($daysText);
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
                <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ', $r->status))); ?></span>
              </td>
              <td><?php echo htmlspecialchars(isset($r->created_at) ? $r->created_at : ''); ?></td>
              <td style="max-width: 340px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php echo htmlspecialchars(isset($r->reason) ? $r->reason : ''); ?>
              </td>
              <td style="max-width: 300px;">
                <?php if (!empty($r->comments)): ?>
                  <div class="small">
                    <strong><?php echo htmlspecialchars(isset($r->approver_name) ? $r->approver_name : 'Manager'); ?>:</strong>
                    <span class="text-<?php echo (isset($r->decision) && $r->decision === 'approved') ? 'success' : 'danger'; ?>">
                      <?php echo htmlspecialchars($r->comments); ?>
                    </span>
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

<?php $this->load->view('partials/footer'); ?>
