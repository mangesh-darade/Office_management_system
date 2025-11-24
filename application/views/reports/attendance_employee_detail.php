<?php $this->load->view('partials/header', ['title' => 'Employee Attendance Detail']); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Attendance - <?php echo htmlspecialchars($name); ?></h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reports/attendance-employee?month='.urlencode($month)); ?>">Back to Summary</a>
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
      <?php if (empty($days)): ?>
        <div class="text-muted">No records for this month.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th style="width:20%">Date</th>
                <th style="width:40%">Attendance Status</th>
                <th style="width:40%">Leave</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($days as $d): ?>
                <tr>
                  <td><?php echo htmlspecialchars($d->date); ?></td>
                  <td><?php echo htmlspecialchars($d->status); ?></td>
                  <td><?php echo htmlspecialchars($d->leave); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php $this->load->view('partials/footer'); ?>
