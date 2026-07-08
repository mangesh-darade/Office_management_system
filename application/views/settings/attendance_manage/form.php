<?php
$is_edit = isset($action) && $action === 'edit';
$page_title = $is_edit ? 'Edit Attendance' : 'Add Attendance';
$row = isset($row) ? $row : null;
$user = isset($user) ? $user : null;
$ctx = isset($ctx) ? $ctx : array();
$display = array(
    'att_date'  => date('Y-m-d'),
    'check_in'  => '',
    'check_out' => '',
    'status'    => 'present',
    'notes'     => '',
    'user_id'   => 0,
);

if ($is_edit && $row) {
    $parsed = attendance_manage_row_display($row, $ctx);
    $display['att_date'] = $parsed['date'];
    $display['check_in'] = $parsed['check_in'];
    $display['check_out'] = $parsed['check_out'];
    $display['status'] = $parsed['status'] !== '' ? $parsed['status'] : 'present';
    $display['notes'] = $parsed['notes'];
    $display['user_id'] = (int) $row->user_id;
}

$status_options = array(
    'present'        => 'Present',
    'absent'         => 'Absent',
    'late'           => 'Late',
    'half_day'       => 'Half Day',
    'early_leave'    => 'Early Leave',
    'work_from_home' => 'Work From Home',
    'holiday'        => 'Holiday',
    'on_leave'       => 'On Leave',
);
?>
<?php $this->load->view('partials/header', ['title' => $page_title]); ?>
<div class="oms-form-compact">
<div class="oms-form-page-head d-flex justify-content-between align-items-center mb-2">
  <h1 class="h3 mb-0">
    <i class="bi bi-clock-history me-2"></i><?php echo esc_view($page_title); ?>
  </h1>
  <a href="<?php echo site_url('settings/attendance-manage'); ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Back to Attendance Manage
  </a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="alert alert-info small">
  Use this screen to add or correct attendance for a specific employee. Holiday punch block does not apply here.
  For holiday work, set the correct date and check-in / check-out times below.
</div>

<div class="card shadow-sm oms-form-card">
  <div class="card-header bg-light">
    <h5 class="card-title mb-0"><i class="bi bi-pencil-square me-2"></i><?php echo esc_view($page_title); ?></h5>
  </div>
  <div class="card-body">
    <form method="post" action="">
      <div class="row g-2 oms-form-grid">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Employee</label>
          <?php if ($is_edit && $user): ?>
            <div class="form-control-plaintext border rounded px-3 py-2 bg-light">
              <?php echo esc_view($user->name); ?>
              <?php if (!empty($user->email)): ?>
                <span class="text-muted small">(<?php echo esc_view($user->email); ?>)</span>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <select name="user_id" class="form-select" required>
              <option value="">Select employee</option>
              <?php if (!empty($users)): foreach ($users as $u): ?>
                <option value="<?php echo (int) $u->id; ?>" <?php echo ((int) $display['user_id'] === (int) $u->id) ? 'selected' : ''; ?>>
                  <?php echo esc_view($u->name); ?><?php echo !empty($u->email) ? ' (' . esc_view($u->email) . ')' : ''; ?>
                </option>
              <?php endforeach; endif; ?>
            </select>
          <?php endif; ?>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Attendance Date</label>
          <input type="date" class="form-control" name="att_date" value="<?php echo esc_view($display['att_date']); ?>" required />
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Status</label>
          <select class="form-select" name="status">
            <?php foreach ($status_options as $value => $label): ?>
              <option value="<?php echo esc_view($value); ?>" <?php echo ($display['status'] === $value) ? 'selected' : ''; ?>>
                <?php echo esc_view($label); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Check In</label>
          <input type="time" class="form-control" name="check_in" value="<?php echo esc_view($display['check_in']); ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Check Out</label>
          <input type="time" class="form-control" name="check_out" value="<?php echo esc_view($display['check_out']); ?>" />
        </div>
        <div class="col-md-12">
          <label class="form-label fw-semibold">Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Reason for manual entry (optional)"><?php echo esc_view($display['notes']); ?></textarea>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i> Save Attendance
        </button>
        <a href="<?php echo site_url('settings/attendance-manage'); ?>" class="btn btn-outline-secondary">
          <i class="bi bi-x-lg me-1"></i> Cancel
        </a>
      </div>
    </form>
  </div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
