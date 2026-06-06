<?php $this->load->view('partials/header', ['title' => 'Timesheet Report']); ?>
<?php
  $monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
  ];
  $monthLabel = isset($monthNames[(int)$month]) ? $monthNames[(int)$month] : '';
  $totalAll = 0;
  if (!empty($rows)) { foreach ($rows as $r) { $totalAll += (float)$r->hours; } }
?>
<div class="container-fluid py-3">
  <div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-bar-chart-line text-primary me-2"></i>Monthly Hours Report</h4>
      <p class="text-muted small mb-0"><?php echo htmlspecialchars($monthLabel . ' ' . $year); ?> &mdash; Hours logged by each team member</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm mt-2 mt-sm-0" href="<?php echo site_url('timesheets'); ?>"><i class="bi bi-arrow-left me-1"></i>Back to Timesheet</a>
  </div>

  <!-- Filter -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2">
      <form class="d-flex flex-wrap gap-2 align-items-end" method="get">
        <div>
          <label class="form-label small fw-semibold mb-1">Year</label>
          <input type="number" class="form-control form-control-sm" style="width:100px" name="year" value="<?php echo (int)$year; ?>" />
        </div>
        <div>
          <label class="form-label small fw-semibold mb-1">Month</label>
          <select class="form-select form-select-sm" style="width:140px" name="month">
            <?php for ($i = 1; $i <= 12; $i++): ?>
              <option value="<?php echo $i; ?>" <?php echo ($i == (int)$month) ? 'selected' : ''; ?>><?php echo $monthNames[$i]; ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div>
          <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Summary -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body py-3 text-center">
          <div class="text-muted small text-uppercase fw-semibold">Team Members</div>
          <div class="fw-bold fs-5 mt-1"><?php echo count($rows); ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body py-3 text-center">
          <div class="text-muted small text-uppercase fw-semibold">Total Hours</div>
          <div class="fw-bold fs-5 mt-1 text-primary"><?php echo number_format($totalAll, 2); ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body py-3 text-center">
          <div class="text-muted small text-uppercase fw-semibold">Avg / Person</div>
          <div class="fw-bold fs-5 mt-1"><?php echo count($rows) > 0 ? number_format($totalAll / count($rows), 2) : '0.00'; ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body py-3 text-center">
          <div class="text-muted small text-uppercase fw-semibold">Month</div>
          <div class="fw-bold mt-1"><?php echo htmlspecialchars($monthLabel . ' ' . $year); ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th style="width: 40px;">#</th>
              <th>User</th>
              <th class="text-end">Hours</th>
              <th style="width: 40%;">Distribution</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="4">
                <div class="empty-state py-4">
                  <div class="empty-icon mx-auto"><i class="bi bi-bar-chart"></i></div>
                  <h6 class="fw-semibold">No data for this month</h6>
                  <p class="text-muted small mb-0">No timesheet entries found for <?php echo htmlspecialchars($monthLabel . ' ' . $year); ?></p>
                </div>
              </td></tr>
            <?php else: ?>
              <?php $rank = 0; foreach ($rows as $r): $rank++;
                $pct = $totalAll > 0 ? ((float)$r->hours / $totalAll) * 100 : 0;
              ?>
              <tr>
                <td class="text-muted small"><?php echo $rank; ?></td>
                <td class="fw-semibold"><?php echo htmlspecialchars($r->email); ?></td>
                <td class="text-end fw-bold"><?php echo number_format((float)$r->hours, 2); ?></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height: 8px;">
                      <div class="progress-bar bg-primary" style="width: <?php echo $pct; ?>%"></div>
                    </div>
                    <small class="text-muted" style="min-width: 36px;"><?php echo number_format($pct, 0); ?>%</small>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <tr class="table-warning">
                <td></td>
                <td class="fw-bold">Total</td>
                <td class="text-end fw-bold text-primary"><?php echo number_format($totalAll, 2); ?></td>
                <td></td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
