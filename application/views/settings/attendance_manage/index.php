<?php $this->load->view('partials/header', ['title' => 'Attendance Manage']); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">
    <i class="bi bi-clock-history me-2"></i>Attendance Manage
  </h1>
  <div class="d-flex gap-2">
    <a href="<?php echo site_url('settings'); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left"></i> Back to Settings
    </a>
    <?php if (!empty($can_add)): ?>
    <a href="<?php echo site_url('settings/attendance-manage/create'); ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Add Attendance
    </a>
    <?php endif; ?>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form method="get" action="<?php echo site_url('settings/attendance-manage'); ?>" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Employee</label>
        <select name="user_id" class="form-select">
          <option value="">All employees</option>
          <?php if (!empty($users)): foreach ($users as $u): ?>
            <option value="<?php echo (int) $u->id; ?>" <?php echo (!empty($filters['user_id']) && (int) $filters['user_id'] === (int) $u->id) ? 'selected' : ''; ?>>
              <?php echo esc_view($u->name); ?><?php echo !empty($u->email) ? ' (' . esc_view($u->email) . ')' : ''; ?>
            </option>
          <?php endforeach; endif; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">From Date</label>
        <input type="date" name="from_date" class="form-control" value="<?php echo esc_view(isset($filters['from_date']) ? $filters['from_date'] : ''); ?>" />
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">To Date</label>
        <input type="date" name="to_date" class="form-control" value="<?php echo esc_view(isset($filters['to_date']) ? $filters['to_date'] : ''); ?>" />
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary w-100">Filter</button>
        <a href="<?php echo site_url('settings/attendance-manage'); ?>" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header bg-light">
    <h5 class="card-title mb-0"><i class="bi bi-list-ul me-2"></i>Attendance Records</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>Employee</th>
            <th>Date</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Status</th>
            <th>Notes</th>
            <th style="width: 90px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $row):
              $display = attendance_manage_row_display($row, $ctx);
            ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?php echo esc_view(isset($row->user_name) ? $row->user_name : ('User #' . (int) $row->user_id)); ?></div>
                  <?php if (!empty($row->user_email)): ?>
                    <div class="small text-muted"><?php echo esc_view($row->user_email); ?></div>
                  <?php endif; ?>
                </td>
                <td><?php echo esc_view($display['date']); ?></td>
                <td><?php echo esc_view($display['check_in'] !== '' ? $display['check_in'] : '—'); ?></td>
                <td><?php echo esc_view($display['check_out'] !== '' ? $display['check_out'] : '—'); ?></td>
                <td><?php echo esc_view($display['status'] !== '' ? ucfirst(str_replace('_', ' ', $display['status'])) : '—'); ?></td>
                <td class="small"><?php echo esc_view($display['notes'] !== '' ? $display['notes'] : '—'); ?></td>
                <td>
                  <a href="<?php echo site_url('settings/attendance-manage/' . (int) $row->id . '/edit'); ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil-square"></i> Edit
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">No attendance records found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
