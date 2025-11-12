<?php $this->load->view('partials/header', ['title' => 'Team Leaves']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Team Leave Requests</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('leave/calendar'); ?>">Calendar View</a>
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
        <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($filters['from'] ?? ''); ?>" />
      </div>
      <div class="col-md-3">
        <label class="form-label">To</label>
        <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($filters['to'] ?? ''); ?>" />
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
            <th>Employee</th>
            <th>Type</th>
            <th>Dates</th>
            <th>Days</th>
            <th>Status</th>
            <th>Reason</th>
            <th style="width:220px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center text-muted">No leave requests found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars($r->user_email ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r->type_name ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r->start_date.' to '.$r->end_date); ?></td>
              <td><?php echo htmlspecialchars((string)$r->days); ?></td>
              <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($r->status); ?></span></td>
              <td style="max-width:280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($r->reason ?? ''); ?></td>
              <td>
                <div class="d-flex gap-2">
                  <form method="post" action="<?php echo site_url('leave/approve/'.(int)$r->id); ?>" class="d-inline">
                    <input type="text" class="form-control form-control-sm" name="comments" placeholder="Comments" />
                    <button class="btn btn-success btn-sm mt-1" <?php echo ($r->status==='pending')?'':'disabled'; ?>>Approve</button>
                  </form>
                  <form method="post" action="<?php echo site_url('leave/reject/'.(int)$r->id); ?>" class="d-inline">
                    <input type="text" class="form-control form-control-sm" name="comments" placeholder="Comments" />
                    <button class="btn btn-danger btn-sm mt-1" <?php echo ($r->status==='pending')?'':'disabled'; ?>>Reject</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
