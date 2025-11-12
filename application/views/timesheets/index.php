<?php $this->load->view('partials/header', ['title' => 'My Timesheet']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">My Timesheet</h1>
  <form class="d-flex gap-2" method="get">
    <label class="form-label mb-0 align-self-center">Week start</label>
    <input type="date" class="form-control" style="width: 180px" name="week" value="<?php echo htmlspecialchars($week_start); ?>" />
    <button class="btn btn-outline-secondary btn-sm">Go</button>
  </form>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card shadow-soft h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <div class="text-muted small">Week</div>
            <div class="fw-semibold"><?php echo htmlspecialchars($timesheet->week_start_date); ?> to <?php echo htmlspecialchars($timesheet->week_end_date); ?></div>
          </div>
          <div>
            <span class="badge bg-light text-dark border">Status: <?php echo htmlspecialchars($timesheet->status); ?></span>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Date</th>
                <th>Project</th>
                <th>Task</th>
                <th class="text-end">Hours</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($entries)): ?>
                <tr><td colspan="5" class="text-muted text-center">No entries yet.</td></tr>
              <?php else: foreach ($entries as $e): ?>
                <tr>
                  <td><?php echo htmlspecialchars($e->work_date); ?></td>
                  <td><?php echo (int)$e->project_id; ?></td>
                  <td><?php echo (int)$e->task_id; ?></td>
                  <td class="text-end"><?php echo htmlspecialchars(number_format((float)$e->hours, 2)); ?></td>
                  <td style="max-width: 340px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($e->description); ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <form class="mt-3 row g-2" method="post" action="<?php echo site_url('timesheets'); ?>?week=<?php echo urlencode($week_start); ?>">
          <div class="col-md-3">
            <label class="form-label">Date</label>
            <input type="date" class="form-control" name="work_date" required />
          </div>
          <div class="col-md-3">
            <label class="form-label">Project</label>
            <select class="form-select" name="project_id">
              <option value="">--</option>
              <?php foreach ($projects as $p): ?>
                <option value="<?php echo (int)$p->id; ?>"><?php echo htmlspecialchars($p->name); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Task</label>
            <select class="form-select" name="task_id">
              <option value="">--</option>
              <?php foreach ($tasks as $t): ?>
                <option value="<?php echo (int)$t->id; ?>"><?php echo htmlspecialchars($t->title); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Hours</label>
            <input type="number" step="0.25" min="0" class="form-control" name="hours" required />
          </div>
          <div class="col-md-12">
            <label class="form-label">Description</label>
            <input type="text" class="form-control" name="description" />
          </div>
          <div class="col-12 mt-2">
            <button class="btn btn-primary">Add Entry</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-soft h-100">
      <div class="card-body">
        <h2 class="h6">Submit</h2>
        <form method="post" action="<?php echo site_url('timesheets/submit'); ?>">
          <input type="hidden" name="week_start" value="<?php echo htmlspecialchars($week_start); ?>" />
          <button class="btn btn-success" <?php echo ($timesheet->status==='submitted' || $timesheet->status==='approved')?'disabled':''; ?>>Submit for Approval</button>
        </form>
        <hr/>
        <h2 class="h6">Reports</h2>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('timesheets/report'); ?>">View Monthly Report</a>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
