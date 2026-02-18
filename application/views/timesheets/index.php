<?php $this->load->view('partials/header', ['title' => 'My Timesheet']); ?>
<?php
  $week_end = date('Y-m-d', strtotime($week_start.' +6 days'));
  $prev_week = date('Y-m-d', strtotime($week_start.' -7 days'));
  $next_week = date('Y-m-d', strtotime($week_start.' +7 days'));
  $is_locked = in_array($timesheet->status, ['submitted', 'approved'], true);

  $status_map = [
    'draft'     => ['bg-secondary',  'bi-pencil'],
    'submitted' => ['bg-warning text-dark', 'bi-clock-history'],
    'approved'  => ['bg-success',    'bi-check-circle'],
    'rejected'  => ['bg-danger',     'bi-x-circle'],
  ];
  $s = isset($status_map[$timesheet->status]) ? $status_map[$timesheet->status] : ['bg-secondary','bi-pencil'];

  $total_hours = 0;
  $billable_hours = 0;
  if (!empty($entries)) {
    foreach ($entries as $e) {
      $total_hours += (float)$e->hours;
      if (isset($e->billable) && $e->billable) { $billable_hours += (float)$e->hours; }
    }
  }
?>
<div class="container-fluid py-3">

<!-- Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
  <div>
    <h4 class="mb-1 fw-bold"><i class="bi bi-calendar3 text-primary me-2"></i>My Timesheet</h4>
    <p class="text-muted small mb-0">Log and manage your weekly work hours</p>
  </div>
  <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('timesheets?week='.$prev_week); ?>" title="Previous week"><i class="bi bi-chevron-left"></i></a>
    <form class="d-flex gap-1 align-items-center" method="get">
      <input type="date" class="form-control form-control-sm" style="min-width:140px" name="week" value="<?php echo htmlspecialchars($week_start); ?>" />
      <button class="btn btn-primary btn-sm"><i class="bi bi-arrow-right"></i></button>
    </form>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('timesheets?week='.$next_week); ?>" title="Next week"><i class="bi bi-chevron-right"></i></a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="card shadow-sm border-0">
      <div class="card-body py-3 text-center">
        <div class="text-muted small text-uppercase fw-semibold">Week</div>
        <div class="fw-bold mt-1"><?php echo date('M d', strtotime($week_start)); ?> &ndash; <?php echo date('M d', strtotime($week_end)); ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card shadow-sm border-0">
      <div class="card-body py-3 text-center">
        <div class="text-muted small text-uppercase fw-semibold">Status</div>
        <div class="mt-1"><span class="badge <?php echo $s[0]; ?>"><i class="bi <?php echo $s[1]; ?> me-1"></i><?php echo ucfirst(htmlspecialchars($timesheet->status)); ?></span></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card shadow-sm border-0">
      <div class="card-body py-3 text-center">
        <div class="text-muted small text-uppercase fw-semibold">Total Hours</div>
        <div class="fw-bold fs-5 mt-1 text-primary"><?php echo number_format($total_hours, 2); ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card shadow-sm border-0">
      <div class="card-body py-3 text-center">
        <div class="text-muted small text-uppercase fw-semibold">Billable</div>
        <div class="fw-bold fs-5 mt-1 text-success"><?php echo number_format($billable_hours, 2); ?></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Entries Table -->
  <div class="col-lg-8">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-list-check me-1"></i>Time Entries</span>
        <span class="text-muted small"><?php echo count($entries); ?> entries</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Project</th>
                <th>Task</th>
                <th class="text-end">Hours</th>
                <th>Billable</th>
                <th>Description</th>
                <?php if (!$is_locked): ?><th class="text-end">Action</th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($entries)): ?>
                <tr><td colspan="<?php echo $is_locked ? 6 : 7; ?>">
                  <div class="empty-state py-4">
                    <div class="empty-icon mx-auto"><i class="bi bi-clock"></i></div>
                    <h6 class="fw-semibold">No entries yet</h6>
                    <p class="text-muted small mb-0">Add your first time entry below</p>
                  </div>
                </td></tr>
              <?php else: ?>
                <?php
                  $day_totals = [];
                  foreach ($entries as $e) {
                    $d = $e->work_date;
                    if (!isset($day_totals[$d])) $day_totals[$d] = 0;
                    $day_totals[$d] += (float)$e->hours;
                  }
                  $prev_date = '';
                ?>
                <?php foreach ($entries as $e):
                  $proj_name = isset($e->project_name) && $e->project_name ? $e->project_name : ((int)$e->project_id ? '#'.(int)$e->project_id : '-');
                  $task_name = isset($e->task_title) && $e->task_title ? $e->task_title : ((int)$e->task_id ? '#'.(int)$e->task_id : '-');
                  $is_billable = isset($e->billable) && $e->billable;
                  $day_label = date('D, M d', strtotime($e->work_date));
                  $show_date_separator = ($e->work_date !== $prev_date);
                  $prev_date = $e->work_date;
                ?>
                  <?php if ($show_date_separator && isset($day_totals[$e->work_date])): ?>
                  <tr class="table-light">
                    <td colspan="<?php echo $is_locked ? 6 : 7; ?>" class="py-1">
                      <small class="fw-semibold text-muted"><?php echo $day_label; ?></small>
                      <small class="float-end text-primary fw-semibold"><?php echo number_format($day_totals[$e->work_date], 2); ?>h</small>
                    </td>
                  </tr>
                  <?php endif; ?>
                  <tr>
                    <td class="text-nowrap small"><?php echo htmlspecialchars($e->work_date); ?></td>
                    <td class="small"><?php echo htmlspecialchars($proj_name); ?></td>
                    <td class="small" style="max-width:140px;" title="<?php echo htmlspecialchars($task_name); ?>">
                      <span class="d-inline-block text-truncate" style="max-width:140px;"><?php echo htmlspecialchars($task_name); ?></span>
                    </td>
                    <td class="text-end fw-semibold"><?php echo number_format((float)$e->hours, 2); ?></td>
                    <td>
                      <?php if ($is_billable): ?>
                        <span class="badge bg-success-subtle text-success">Yes</span>
                      <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary">No</span>
                      <?php endif; ?>
                    </td>
                    <td style="max-width:200px;">
                      <span class="d-inline-block text-truncate small" style="max-width:200px;" title="<?php echo htmlspecialchars($e->description); ?>"><?php echo htmlspecialchars($e->description); ?></span>
                    </td>
                    <?php if (!$is_locked): ?>
                    <td class="text-end">
                      <form action="<?php echo site_url('timesheets/delete_entry/'.(int)$e->id); ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this entry?');">
                        <input type="hidden" name="week_start" value="<?php echo htmlspecialchars($week_start); ?>" />
                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1" title="Delete"><i class="bi bi-trash3"></i></button>
                      </form>
                    </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
                <!-- Total row -->
                <tr class="table-warning">
                  <td colspan="3" class="fw-bold text-end">Total</td>
                  <td class="text-end fw-bold text-primary"><?php echo number_format($total_hours, 2); ?></td>
                  <td colspan="<?php echo $is_locked ? 2 : 3; ?>"></td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Right sidebar: Add Entry + Submit -->
  <div class="col-lg-4">
    <!-- Add Entry Form -->
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-plus-circle me-1"></i>Add Entry</div>
      <div class="card-body">
        <?php if ($is_locked): ?>
          <div class="alert alert-info small mb-0"><i class="bi bi-lock me-1"></i>This timesheet is <strong><?php echo htmlspecialchars($timesheet->status); ?></strong>. You cannot add or modify entries.</div>
        <?php else: ?>
        <form method="post" action="<?php echo site_url('timesheets'); ?>?week=<?php echo urlencode($week_start); ?>">
          <div class="mb-2">
            <label class="form-label small fw-semibold">Date</label>
            <input type="date" class="form-control form-control-sm" name="work_date" required
                   min="<?php echo htmlspecialchars($week_start); ?>"
                   max="<?php echo htmlspecialchars($week_end); ?>"
                   value="<?php echo htmlspecialchars(date('Y-m-d')); ?>" />
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">Project</label>
            <select class="form-select form-select-sm" name="project_id" id="project_id">
              <option value="">-- Select Project --</option>
              <?php foreach ($projects as $p): ?>
                <option value="<?php echo (int)$p->id; ?>"><?php echo htmlspecialchars($p->name); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">Task</label>
            <select class="form-select form-select-sm" name="task_id" id="task_id">
              <option value="">-- Select Project First --</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">Hours</label>
            <input type="number" step="0.25" min="0.25" max="24" class="form-control form-control-sm" name="hours" placeholder="e.g. 2.5" required />
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">Description</label>
            <textarea class="form-control form-control-sm" name="description" rows="2" placeholder="What did you work on?"></textarea>
          </div>
          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="billable" value="1" id="billableCheck" checked />
              <label class="form-check-label small" for="billableCheck">Billable hours</label>
            </div>
          </div>
          <button class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg me-1"></i>Add Entry</button>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Submit / Actions -->
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-send me-1"></i>Actions</div>
      <div class="card-body">
        <?php if ($timesheet->status === 'draft'): ?>
          <p class="text-muted small mb-2">When all entries are logged, submit your timesheet for manager approval.</p>
          <form method="post" action="<?php echo site_url('timesheets/submit'); ?>">
            <input type="hidden" name="week_start" value="<?php echo htmlspecialchars($week_start); ?>" />
            <button class="btn btn-success btn-sm w-100" <?php echo empty($entries) ? 'disabled' : ''; ?>><i class="bi bi-check2-circle me-1"></i>Submit for Approval</button>
          </form>
        <?php elseif ($timesheet->status === 'submitted'): ?>
          <div class="alert alert-warning small mb-0"><i class="bi bi-hourglass-split me-1"></i>Waiting for manager approval.</div>
        <?php elseif ($timesheet->status === 'approved'): ?>
          <div class="alert alert-success small mb-0"><i class="bi bi-check-circle me-1"></i>This timesheet has been approved.
            <?php if (!empty($timesheet->comments)): ?>
              <hr class="my-1"><strong>Comments:</strong> <?php echo htmlspecialchars($timesheet->comments); ?>
            <?php endif; ?>
          </div>
        <?php elseif ($timesheet->status === 'rejected'): ?>
          <div class="alert alert-danger small mb-2"><i class="bi bi-x-circle me-1"></i>This timesheet was rejected.
            <?php if (!empty($timesheet->comments)): ?>
              <hr class="my-1"><strong>Reason:</strong> <?php echo htmlspecialchars($timesheet->comments); ?>
            <?php endif; ?>
          </div>
          <p class="text-muted small mb-2">Make changes and resubmit.</p>
          <form method="post" action="<?php echo site_url('timesheets/submit'); ?>">
            <input type="hidden" name="week_start" value="<?php echo htmlspecialchars($week_start); ?>" />
            <button class="btn btn-success btn-sm w-100"><i class="bi bi-arrow-repeat me-1"></i>Resubmit</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick links -->
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-bar-chart me-1"></i>Reports</div>
      <div class="card-body">
        <a class="btn btn-outline-secondary btn-sm w-100" href="<?php echo site_url('timesheets/report'); ?>"><i class="bi bi-file-earmark-bar-graph me-1"></i>Monthly Hours Report</a>
      </div>
    </div>
  </div>
</div>

</div><!-- .container-fluid -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    var projectSelect = document.getElementById('project_id');
    var taskSelect = document.getElementById('task_id');
    if (!projectSelect || !taskSelect) return;

    projectSelect.addEventListener('change', function() {
        var projectId = this.value;
        taskSelect.innerHTML = '<option value="">Loading...</option>';
        taskSelect.disabled = true;

        if (!projectId) {
            taskSelect.innerHTML = '<option value="">-- Select Project First --</option>';
            taskSelect.disabled = false;
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('GET', '<?php echo site_url("tasks/get_by_project/"); ?>' + projectId, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                taskSelect.disabled = false;
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        var options = '<option value="">-- Select Task --</option>';
                        if (data && data.length > 0) {
                            for (var i = 0; i < data.length; i++) {
                                options += '<option value="' + data[i].id + '">' + data[i].title + '</option>';
                            }
                        } else {
                            options = '<option value="">No tasks found for this project</option>';
                        }
                        taskSelect.innerHTML = options;
                    } catch(e) {
                        taskSelect.innerHTML = '<option value="">Error loading tasks</option>';
                    }
                } else {
                    taskSelect.innerHTML = '<option value="">Error loading tasks</option>';
                }
            }
        };
        xhr.send();
    });
});
</script>

<?php $this->load->view('partials/footer'); ?>
