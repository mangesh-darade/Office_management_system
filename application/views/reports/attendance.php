<?php $this->load->view('partials/header', ['title' => 'Attendance Report']); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Attendance Report</h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reports'); ?>">Back to Reports</a>
  </div>

  <!-- Nav tabs -->
  <ul class="nav nav-tabs mb-3" id="attendance-tabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo ($period==='daily')?'active':''; ?>" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily" type="button" role="tab" aria-controls="daily" aria-selected="<?php echo ($period==='daily')?'true':'false'; ?>">Daily</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo ($period==='weekly')?'active':''; ?>" id="weekly-tab" data-bs-toggle="tab" data-bs-target="#weekly" type="button" role="tab" aria-controls="weekly" aria-selected="<?php echo ($period==='weekly')?'true':'false'; ?>">Weekly</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo ($period==='monthly')?'active':''; ?>" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button" role="tab" aria-controls="monthly" aria-selected="<?php echo ($period==='monthly')?'true':'false'; ?>">Monthly</button>
    </li>
  </ul>

  <!-- Tab panes -->
  <div class="tab-content" id="attendance-tab-content">
    <!-- Daily -->
    <div class="tab-pane fade <?php echo ($period==='daily')?'show active':''; ?>" id="daily" role="tabpanel" aria-labelledby="daily-tab">
      <div class="card shadow-soft">
        <div class="card-body">
          <?php if (empty($daily)): ?>
            <div class="text-muted">No daily attendance data.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-center">Count</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($daily as $r): ?>
                    <tr>
                      <td><?php echo htmlspecialchars(isset($r->name)?$r->name:''); ?></td>
                      <td><?php echo htmlspecialchars(isset($r->bucket)?$r->bucket:''); ?></td>
                      <td><?php echo htmlspecialchars(isset($r->status)?$r->status:''); ?></td>
                      <td class="text-center"><?php echo (int)$r->cnt; ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <!-- Weekly -->
    <div class="tab-pane fade <?php echo ($period==='weekly')?'show active':''; ?>" id="weekly" role="tabpanel" aria-labelledby="weekly-tab">
      <div class="card shadow-soft">
        <div class="card-body">
          <?php if (empty($weekly)): ?>
            <div class="text-muted">No weekly attendance data.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Week</th>
                    <th>Status</th>
                    <th class="text-center">Count</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($weekly as $r): ?>
                    <tr>
                      <td><?php echo htmlspecialchars(isset($r->name)?$r->name:''); ?></td>
                      <td><?php echo htmlspecialchars(isset($r->bucket)?$r->bucket:''); ?></td>
                      <td><?php echo htmlspecialchars(isset($r->status)?$r->status:''); ?></td>
                      <td class="text-center"><?php echo (int)$r->cnt; ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <!-- Monthly -->
    <div class="tab-pane fade <?php echo ($period==='monthly')?'show active':''; ?>" id="monthly" role="tabpanel" aria-labelledby="monthly-tab">
      <div class="card shadow-soft">
        <div class="card-body">
          <?php if (empty($monthly)): ?>
            <div class="text-muted">No monthly attendance data.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Month</th>
                    <th>Status</th>
                    <th class="text-center">Count</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($monthly as $r): ?>
                    <tr>
                      <td><?php echo htmlspecialchars(isset($r->name)?$r->name:''); ?></td>
                      <td><?php echo htmlspecialchars(isset($r->bucket)?$r->bucket:''); ?></td>
                      <td><?php echo htmlspecialchars(isset($r->status)?$r->status:''); ?></td>
                      <td class="text-center"><?php echo (int)$r->cnt; ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <script>
    // Handle tab clicks to update URL
    document.querySelectorAll('#attendance-tabs .nav-link').forEach(function(tab) {
      tab.addEventListener('click', function() {
        var target = this.getAttribute('data-bs-target');
        var period = target.substring(1); // remove #
        var url = new URL(window.location);
        url.searchParams.set('period', period);
        window.history.pushState({}, '', url);
      });
    });
  </script>
<?php $this->load->view('partials/footer'); ?>
