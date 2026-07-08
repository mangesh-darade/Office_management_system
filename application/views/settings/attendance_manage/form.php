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
    'check_in_location'  => '',
    'check_out_location' => '',
    'check_in_lat'  => '',
    'check_in_lng'  => '',
    'check_out_lat' => '',
    'check_out_lng' => '',
    'status'    => 'present',
    'notes'     => '',
    'user_id'   => 0,
);

if ($is_edit && $row) {
    $parsed = attendance_manage_row_display($row, $ctx);
    $display['att_date'] = $parsed['date'];
    $display['check_in'] = $parsed['check_in'];
    $display['check_out'] = $parsed['check_out'];
    $display['check_in_location'] = $parsed['check_in_location'];
    $display['check_out_location'] = $parsed['check_out_location'];
    $display['check_in_lat'] = $parsed['check_in_lat'];
    $display['check_in_lng'] = $parsed['check_in_lng'];
    $display['check_out_lat'] = $parsed['check_out_lat'];
    $display['check_out_lng'] = $parsed['check_out_lng'];
    $display['status'] = $parsed['status'] !== '' ? $parsed['status'] : 'present';
    $display['notes'] = $parsed['notes'];
    $display['user_id'] = (int) $row->user_id;
}

$show_location_fields = !empty($ctx['has_checkin_location']) || !empty($ctx['has_checkout_location']);
$show_lat_lng_fields = !empty($ctx['has_checkin_lat']) || !empty($ctx['has_checkin_lng'])
    || !empty($ctx['has_checkout_lat']) || !empty($ctx['has_checkout_lng']);

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
<style>
.att-manage-form-page { padding-bottom: 4rem; }
.att-manage-form-page .att-section-title {
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #6c757d;
  margin-bottom: 0.75rem;
  padding-bottom: 0.35rem;
  border-bottom: 1px solid #e9ecef;
}
.att-manage-form-page .att-location-block {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 0.375rem;
  padding: 0.85rem 1rem;
  height: 100%;
}
.att-manage-form-page .att-location-block .form-label {
  font-size: 0.8125rem;
}
</style>
<div class="container-fluid py-3 att-manage-form-page">
  <div class="d-flex flex-wrap align-items-start gap-2 mb-3">
    <a href="<?php echo site_url('settings/attendance-manage'); ?>" class="btn btn-outline-secondary btn-sm flex-shrink-0" title="Back to Attendance Manage">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <div class="flex-grow-1 min-w-0">
      <h1 class="h5 mb-0 fw-bold">
        <i class="bi bi-clock-history me-2 text-primary"></i><?php echo esc_view($page_title); ?>
      </h1>
      <p class="text-muted small mb-0 mt-1">Manual attendance entry — holiday punch block does not apply here.</p>
    </div>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <form method="post" action="">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>

        <div class="att-section-title">Employee &amp; date</div>
        <div class="row g-3 mb-4">
          <div class="col-lg-5">
            <label class="form-label fw-semibold">Employee</label>
            <?php if ($is_edit && $user): ?>
              <div class="border rounded px-3 py-2 bg-light">
                <div class="fw-semibold"><?php echo esc_view($user->name); ?></div>
                <?php if (!empty($user->email)): ?>
                  <div class="small text-muted"><?php echo esc_view($user->email); ?></div>
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
          <div class="col-md-4 col-lg-3">
            <label class="form-label fw-semibold">Attendance Date</label>
            <input type="date" class="form-control" name="att_date" value="<?php echo esc_view($display['att_date']); ?>" required />
          </div>
          <div class="col-md-4 col-lg-4">
            <label class="form-label fw-semibold">Status</label>
            <select class="form-select" name="status">
              <?php foreach ($status_options as $value => $label): ?>
                <option value="<?php echo esc_view($value); ?>" <?php echo ($display['status'] === $value) ? 'selected' : ''; ?>>
                  <?php echo esc_view($label); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <div class="att-section-title mb-2"><i class="bi bi-box-arrow-in-right me-1"></i>Check in</div>
            <label class="form-label fw-semibold">Time</label>
            <input type="time" class="form-control" name="check_in" value="<?php echo esc_view($display['check_in']); ?>" />
            <?php if ($show_location_fields): ?>
            <label class="form-label fw-semibold mt-3">Location</label>
            <textarea name="check_in_location" class="form-control" rows="2" maxlength="255" placeholder="Office address or site name"><?php echo esc_view($display['check_in_location']); ?></textarea>
            <?php endif; ?>
            <?php if ($show_lat_lng_fields): ?>
            <div class="row g-2 mt-2">
              <div class="col-6">
                <label class="form-label small text-muted mb-0">Latitude</label>
                <input type="text" class="form-control form-control-sm" name="check_in_lat" value="<?php echo esc_view($display['check_in_lat']); ?>" placeholder="Optional" />
              </div>
              <div class="col-6">
                <label class="form-label small text-muted mb-0">Longitude</label>
                <input type="text" class="form-control form-control-sm" name="check_in_lng" value="<?php echo esc_view($display['check_in_lng']); ?>" placeholder="Optional" />
              </div>
            </div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <div class="att-section-title mb-2"><i class="bi bi-box-arrow-right me-1"></i>Check out</div>
            <label class="form-label fw-semibold">Time</label>
            <input type="time" class="form-control" name="check_out" value="<?php echo esc_view($display['check_out']); ?>" />
            <?php if ($show_location_fields): ?>
            <label class="form-label fw-semibold mt-3">Location</label>
            <textarea name="check_out_location" class="form-control" rows="2" maxlength="255" placeholder="Office address or site name"><?php echo esc_view($display['check_out_location']); ?></textarea>
            <?php endif; ?>
            <?php if ($show_lat_lng_fields): ?>
            <div class="row g-2 mt-2">
              <div class="col-6">
                <label class="form-label small text-muted mb-0">Latitude</label>
                <input type="text" class="form-control form-control-sm" name="check_out_lat" value="<?php echo esc_view($display['check_out_lat']); ?>" placeholder="Optional" />
              </div>
              <div class="col-6">
                <label class="form-label small text-muted mb-0">Longitude</label>
                <input type="text" class="form-control form-control-sm" name="check_out_lng" value="<?php echo esc_view($display['check_out_lng']); ?>" placeholder="Optional" />
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="att-section-title">Notes</div>
        <div class="mb-4">
          <textarea name="notes" class="form-control" rows="2" placeholder="Reason for manual entry (optional)"><?php echo esc_view($display['notes']); ?></textarea>
        </div>

        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Save Attendance
          </button>
          <a href="<?php echo site_url('settings/attendance-manage'); ?>" class="btn btn-outline-secondary">
            Cancel
          </a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
