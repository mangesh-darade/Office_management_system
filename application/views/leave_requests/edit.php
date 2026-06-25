<?php $this->load->view('partials/header', ['title' => 'Edit Leave Request']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Edit Leave Request</h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('leave/team'); ?>">Back to Team Leaves</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="<?php echo site_url('leave/edit/'.(int)$leave->id); ?>">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Leave Type <span class="text-danger">*</span></label>
          <select class="form-select" name="type_id" required>
            <option value="">Select</option>
            <?php foreach ($types as $t): $tid=(int)$t->id; ?>
              <option value="<?php echo $tid; ?>" <?php echo ((int)$leave->type_id === $tid) ? 'selected' : ''; ?>>
                <?php echo esc_view($t->name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Status <span class="text-danger">*</span></label>
          <select class="form-select" name="status" required>
            <?php $statuses=['pending','lead_approved','hr_approved','approved','rejected','cancelled'];
            foreach ($statuses as $st): ?>
              <option value="<?php echo $st; ?>" <?php echo ($leave->status === $st) ? 'selected' : ''; ?>>
                <?php echo ucfirst(str_replace('_',' ', $st)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Start Date <span class="text-danger">*</span></label>
          <input type="date" class="form-control" name="start_date" value="<?php echo esc_view($leave->start_date); ?>" required />
        </div>
        <div class="col-md-6">
          <label class="form-label">End Date <span class="text-danger">*</span></label>
          <input type="date" class="form-control" name="end_date" value="<?php echo esc_view($leave->end_date); ?>" required />
        </div>
        <div class="col-md-6">
          <label class="form-label">Days</label>
          <input type="number" step="0.5" min="0.5" class="form-control" name="days" value="<?php echo esc_view($leave->days); ?>" />
          <div class="form-text">Enter number of days (e.g., 1, 1.5, 2)</div>
        </div>
        <div class="col-md-12">
          <label class="form-label">Reason</label>
          <textarea class="form-control" name="reason" rows="3"><?php echo esc_view($leave->reason); ?></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Update Leave Request</button>
          <a href="<?php echo site_url('leave/team'); ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>

