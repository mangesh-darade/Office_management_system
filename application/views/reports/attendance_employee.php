<?php $this->load->view('partials/header', ['title' => 'Employee Attendance']); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Employee Attendance</h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reports'); ?>">Back to Reports</a>
  </div>
  <div class="card shadow-soft mb-3">
    <div class="card-body">
      <form class="row g-3 align-items-end" method="get">
        <div class="col-sm-4 col-md-3 col-lg-2">
          <label class="form-label">Month</label>
          <input type="month" name="month" value="<?php echo htmlspecialchars($month); ?>" class="form-control">
        </div>
        <div class="col-auto">
          <button class="btn btn-primary" type="submit">Filter</button>
        </div>
      </form>
    </div>
  </div>
  <div class="card shadow-soft">
    <div class="card-body">
      <?php if (empty($rows)): ?>
        <div class="text-muted">No attendance data for this month.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Employee</th>
                <th class="text-center">Present Days</th>
                <th class="text-center">Half Days</th>
                <th class="text-center">WFH Days</th>
                <th class="text-center">Absent Days</th>
                <th class="text-center">Leave Days</th>
                <th class="text-end">Details</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r->name); ?></td>
                  <td class="text-center"><?php echo htmlspecialchars($r->present_days); ?></td>
                  <td class="text-center"><?php echo htmlspecialchars($r->half_days); ?></td>
                  <td class="text-center"><?php echo htmlspecialchars($r->wfh_days); ?></td>
                  <td class="text-center"><?php echo htmlspecialchars($r->absent_days); ?></td>
                  <td class="text-center"><?php echo htmlspecialchars($r->leave_days); ?></td>
                  <td class="text-end">
                    <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('reports/attendance-employee/'.$r->user_id.'?month='.urlencode($month)); ?>">View</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php $this->load->view('partials/footer'); ?>
