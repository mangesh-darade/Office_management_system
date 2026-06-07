<?php $this->load->view('partials/header', array('title' => 'Timesheet Analytics')); ?>
<div class="container-fluid py-3">
  <div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-graph-up text-primary me-2"></i>Timesheet Analytics</h4>
      <p class="text-muted small mb-0">Project, user, and task hours for the selected period</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-sm-0">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('timesheets/report'); ?>"><i class="bi bi-bar-chart-line me-1"></i>Monthly Report</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('timesheets'); ?>"><i class="bi bi-arrow-left me-1"></i>My Timesheet</a>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-3">
      <form method="get" class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
          <label class="form-label small mb-0">From</label>
          <input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($start_date); ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-0">To</label>
          <input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($end_date); ?>">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small mb-0">Project</label>
          <select name="project_id" class="form-select form-select-sm">
            <option value="">All projects</option>
            <?php foreach ($projects as $p): ?>
              <option value="<?php echo (int) $p->id; ?>" <?php echo (int) $selected_project === (int) $p->id ? 'selected' : ''; ?>><?php echo htmlspecialchars($p->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small mb-0">User</label>
          <select name="user_id" class="form-select form-select-sm">
            <option value="">All users</option>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo (int) $u->id; ?>" <?php echo (int) $selected_user === (int) $u->id ? 'selected' : ''; ?>><?php echo htmlspecialchars($u->email); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-2">
          <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white fw-semibold">By Project</div>
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0 datatable">
            <thead class="table-light"><tr><th>Project</th><th>Hours</th><th>Billable</th><th>Users</th></tr></thead>
            <tbody>
              <?php if (!empty($project_analytics)): foreach ($project_analytics as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r->project_name ? $r->project_name : ('#' . $r->project_id)); ?></td>
                  <td><?php echo number_format((float) $r->total_hours, 2); ?></td>
                  <td><?php echo number_format((float) $r->billable_hours, 2); ?></td>
                  <td><?php echo (int) $r->user_count; ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white fw-semibold">By User</div>
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0 datatable">
            <thead class="table-light"><tr><th>User</th><th>Hours</th><th>Projects</th><th>Entries</th></tr></thead>
            <tbody>
              <?php if (!empty($user_analytics)): foreach ($user_analytics as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r->user_email); ?></td>
                  <td><?php echo number_format((float) $r->total_hours, 2); ?></td>
                  <td><?php echo (int) $r->project_count; ?></td>
                  <td><?php echo (int) $r->entry_count; ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-semibold">Task Time Tracking</div>
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0 datatable">
            <thead class="table-light"><tr><th>Task</th><th>Project</th><th>User</th><th>Hours</th><th></th></tr></thead>
            <tbody>
              <?php if (!empty($task_tracking)): foreach ($task_tracking as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r->task_title ? $r->task_title : ('Task #' . $r->task_id)); ?></td>
                  <td><?php echo htmlspecialchars($r->project_name ? $r->project_name : '—'); ?></td>
                  <td><?php echo htmlspecialchars($r->user_email); ?></td>
                  <td><?php echo number_format((float) $r->total_hours, 2); ?></td>
                  <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('timesheets/task-tracking/' . (int) $r->task_id . '?start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date)); ?>">Detail</a></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
