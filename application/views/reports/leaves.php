<?php $this->load->view('partials/header', ['title' => 'Leaves Report']); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Leaves Report</h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reports'); ?>">Back to Reports</a>
  </div>
  <div class="card shadow-soft mb-3">
    <div class="card-body">
      <h5 class="card-title mb-2">Leaves by Status</h5>
      <?php if (empty($by_status)): ?>
        <div class="text-muted">No leave data available.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Status</th>
                <th class="text-center">Requests</th>
                <th class="text-center">Days</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($by_status as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r->status); ?></td>
                  <td class="text-center"><?php echo (int)$r->cnt; ?></td>
                  <td class="text-center">
                    <?php
                      if (isset($r->total_days)) {
                        $daysVal = (float)$r->total_days;
                        $daysText = (fmod($daysVal, 1.0) === 0.0)
                          ? (string)(int)$daysVal
                          : rtrim(rtrim(number_format($daysVal, 2, '.', ''), '0'), '.');
                        echo htmlspecialchars($daysText);
                      } else {
                        echo '-';
                      }
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="card shadow-soft">
    <div class="card-body">
      <h5 class="card-title mb-2">Leaves - Last 6 Months</h5>
      <?php if (empty($monthly)): ?>
        <div class="text-muted">No monthly data.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Month</th>
                <th class="text-center">Days</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($monthly as $m): ?>
                <tr>
                  <td><?php echo htmlspecialchars($m->ym); ?></td>
                  <td class="text-center">
                    <?php
                      $val = null;
                      if (isset($m->total_days)) {
                        $val = (float)$m->total_days;
                      } elseif (isset($m->cnt)) {
                        $val = (float)$m->cnt;
                      }
                      if ($val !== null) {
                        $text = (fmod($val, 1.0) === 0.0)
                          ? (string)(int)$val
                          : rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');
                        echo htmlspecialchars($text);
                      } else {
                        echo '-';
                      }
                    ?>
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
