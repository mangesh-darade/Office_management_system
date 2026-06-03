<?php $this->load->view('partials/header', array('title' => 'Task Time Tracking')); ?>
<div class="container-fluid py-3">
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-stopwatch text-primary me-2"></i>Task Time Tracking</h4>
      <p class="text-muted small mb-0">
        <?php if (!empty($task)): ?>
          <?php echo htmlspecialchars($task->title); ?> (#<?php echo (int) $task->id; ?>)
        <?php else: ?>
          Task #<?php echo (int) $this->uri->segment(3); ?>
        <?php endif; ?>
      </p>
    </div>
    <a class="btn btn-outline-secondary btn-sm mt-2 mt-sm-0" href="<?php echo site_url('timesheets/analytics'); ?>"><i class="bi bi-arrow-left me-1"></i>Back to Analytics</a>
  </div>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-3">
      <form method="get" class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
          <label class="form-label small mb-0">From</label>
          <input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($start_date); ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small mb-0">To</label>
          <input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($end_date); ?>">
        </div>
        <div class="col-12 col-md-2">
          <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 datatable">
        <thead class="table-light">
          <tr>
            <th>User</th>
            <th>Project</th>
            <th>Total Hours</th>
            <th>Billable</th>
            <th>Entries</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($tracking)): ?>
            <tr><td colspan="5" class="text-center text-muted py-5">No time logged for this task in the selected period.</td></tr>
          <?php else: ?>
            <?php $sum = 0; foreach ($tracking as $r): $sum += (float) $r->total_hours; ?>
              <tr>
                <td><?php echo htmlspecialchars($r->user_email); ?></td>
                <td><?php echo htmlspecialchars($r->project_name ? $r->project_name : '—'); ?></td>
                <td class="fw-semibold"><?php echo number_format((float) $r->total_hours, 2); ?></td>
                <td><?php echo number_format((float) $r->billable_hours, 2); ?></td>
                <td><?php echo (int) $r->entry_count; ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <?php if (!empty($tracking)): ?>
        <tfoot class="table-light">
          <tr><th colspan="2">Total</th><th><?php echo number_format($sum, 2); ?></th><th colspan="2"></th></tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
