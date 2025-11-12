<?php $this->load->view('partials/header', ['title' => 'Timesheet Report']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Monthly Hours Report</h1>
  <form class="d-flex gap-2" method="get">
    <label class="form-label mb-0 align-self-center">Year</label>
    <input type="number" class="form-control" style="width: 120px" name="year" value="<?php echo (int)$year; ?>" />
    <label class="form-label mb-0 align-self-center">Month</label>
    <input type="number" class="form-control" style="width: 100px" name="month" value="<?php echo (int)$month; ?>" />
    <button class="btn btn-outline-secondary btn-sm">Go</button>
    <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('timesheets'); ?>">Back to Timesheet</a>
  </form>
</div>
<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>User</th>
            <th class="text-end">Hours</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="2" class="text-center text-muted">No data for selected month.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars($r->email); ?></td>
              <td class="text-end"><?php echo htmlspecialchars(number_format((float)$r->hours, 2)); ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
