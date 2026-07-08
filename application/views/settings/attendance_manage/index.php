<?php $this->load->view('partials/header', ['title' => 'Attendance Manage']); ?>
<div class="container-fluid py-3" style="padding-bottom: 4rem;">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div class="d-flex align-items-start gap-2 flex-grow-1 min-w-0">
      <a href="<?php echo site_url('settings'); ?>" class="btn btn-outline-secondary btn-sm flex-shrink-0 mt-0" title="Back to Settings">
        <i class="bi bi-arrow-left me-1"></i>Back
      </a>
      <div class="min-w-0">
        <h1 class="h5 mb-0 fw-bold">
          <i class="bi bi-clock-history me-2 text-primary"></i>Attendance Manage
        </h1>
        <p class="text-muted small mb-0 mt-1">Add or correct employee attendance records (admin only).</p>
      </div>
    </div>
    <?php if (!empty($can_add)): ?>
    <a href="<?php echo site_url('settings/attendance-manage/create'); ?>" class="btn btn-primary btn-sm flex-shrink-0">
      <i class="bi bi-plus-lg me-1"></i>Add Attendance
    </a>
    <?php endif; ?>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-3">
      <form method="get" action="<?php echo site_url('settings/attendance-manage'); ?>" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label small fw-semibold mb-1">Employee</label>
          <select name="user_id" class="form-select form-select-sm">
            <option value="">All employees</option>
            <?php if (!empty($users)): foreach ($users as $u): ?>
              <option value="<?php echo (int) $u->id; ?>" <?php echo (!empty($filters['user_id']) && (int) $filters['user_id'] === (int) $u->id) ? 'selected' : ''; ?>>
                <?php echo esc_view($u->name); ?><?php echo !empty($u->email) ? ' (' . esc_view($u->email) . ')' : ''; ?>
              </option>
            <?php endforeach; endif; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold mb-1">From Date</label>
          <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo esc_view(isset($filters['from_date']) ? $filters['from_date'] : ''); ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold mb-1">To Date</label>
          <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo esc_view(isset($filters['to_date']) ? $filters['to_date'] : ''); ?>" />
        </div>
        <div class="col-md-2 d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
          <a href="<?php echo site_url('settings/attendance-manage'); ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-header bg-white py-2 border-bottom">
      <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-list-ul me-2"></i>Attendance Records</h2>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Employee</th>
            <th>Date</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th style="min-width: 140px;">Check-in Location</th>
            <th style="min-width: 140px;">Check-out Location</th>
            <th>Status</th>
            <th>Notes</th>
            <th class="text-nowrap" style="width: 72px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $row):
              $display = attendance_manage_row_display($row, $ctx);
              $cin_loc = $display['check_in_location'];
              $cout_loc = $display['check_out_location'];
            ?>
              <tr>
                <td>
                  <div class="fw-semibold small"><?php echo esc_view(isset($row->user_name) ? $row->user_name : ('User #' . (int) $row->user_id)); ?></div>
                  <?php if (!empty($row->user_email)): ?>
                    <div class="text-muted" style="font-size: 0.75rem;"><?php echo esc_view($row->user_email); ?></div>
                  <?php endif; ?>
                </td>
                <td class="text-nowrap small"><?php echo esc_view($display['date']); ?></td>
                <td class="text-nowrap small"><?php echo esc_view($display['check_in'] !== '' ? $display['check_in'] : '—'); ?></td>
                <td class="text-nowrap small"><?php echo esc_view($display['check_out'] !== '' ? $display['check_out'] : '—'); ?></td>
                <td class="small text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($cin_loc, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo esc_view($cin_loc !== '' ? $cin_loc : '—'); ?>
                </td>
                <td class="small text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($cout_loc, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo esc_view($cout_loc !== '' ? $cout_loc : '—'); ?>
                </td>
                <td class="small"><?php echo esc_view($display['status'] !== '' ? ucfirst(str_replace('_', ' ', $display['status'])) : '—'); ?></td>
                <td class="small text-truncate" style="max-width: 120px;" title="<?php echo htmlspecialchars($display['notes'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo esc_view($display['notes'] !== '' ? $display['notes'] : '—'); ?>
                </td>
                <td>
                  <a href="<?php echo site_url('settings/attendance-manage/' . (int) $row->id . '/edit'); ?>" class="btn btn-sm btn-outline-primary py-0 px-2" title="Edit">
                    <i class="bi bi-pencil-square"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" class="text-center text-muted py-4">No attendance records found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
