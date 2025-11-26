<?php $this->load->view('partials/header', ['title' => 'Leaves Report']); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Leaves Report</h1>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-primary btn-sm" onclick="toggleFilters()">🔍 Filters</button>
      <button class="btn btn-outline-success btn-sm" onclick="exportCSV()">📥 Export CSV</button>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reports'); ?>">Back to Reports</a>
    </div>
  </div>

  <!-- Filters Panel -->
  <div id="filtersPanel" class="card shadow-soft mb-3" style="display:none;">
    <div class="card-body">
      <form method="GET" class="row g-3">
        <div class="col-md-2">
          <label class="form-label">Employee</label>
          <select name="user_id" class="form-select form-select-sm">
            <option value="">All Employees</option>
            <?php foreach($filter_options['users'] as $user): ?>
              <option value="<?php echo $user->id; ?>" <?php echo (isset($filters['user_id']) && $filters['user_id'] == $user->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($user->email); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">All Statuses</option>
            <?php foreach($filter_options['statuses'] as $status): ?>
              <option value="<?php echo $status; ?>" <?php echo (isset($filters['status']) && $filters['status'] === $status) ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $status)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Leave Type</label>
          <select name="leave_type" class="form-select form-select-sm">
            <option value="">All Types</option>
            <?php if (!empty($filter_options['leave_types'])): ?>
              <?php foreach($filter_options['leave_types'] as $type): ?>
                <option value="<?php echo $type; ?>" <?php echo (isset($filters['leave_type']) && $filters['leave_type'] === $type) ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $type))); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Date From</label>
          <input type="date" name="date_from" class="form-control form-control-sm" 
                 value="<?php echo htmlspecialchars(isset($filters['date_from']) ? $filters['date_from'] : ''); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Date To</label>
          <input type="date" name="date_to" class="form-control form-control-sm" 
                 value="<?php echo htmlspecialchars(isset($filters['date_to']) ? $filters['date_to'] : ''); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">&nbsp;</label>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <a href="<?php echo site_url('reports/leaves'); ?>" class="btn btn-outline-secondary btn-sm">Clear</a>
          </div>
        </div>
      </form>
    </div>
  </div>
  <!-- Summary Statistics -->
  <div class="row mb-3">
    <div class="col-md-3">
      <div class="card shadow-soft border-start border-primary border-4">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="small text-muted">Total Requests</div>
              <div class="h4 mb-0"><?php 
                $total_requests = array_sum(array_map(function($r) { return (int)$r->cnt; }, $by_status));
                echo $total_requests; 
              ?></div>
            </div>
            <div class="text-primary">
              <svg width="24" height="24" fill="currentColor"><use xlink:href="#document-text"/></svg>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-soft border-start border-success border-4">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="small text-muted">Total Days</div>
              <div class="h4 mb-0"><?php 
                $total_days = array_sum(array_map(function($r) { return isset($r->total_days) ? (float)$r->total_days : 0; }, $by_status));
                echo number_format($total_days, 1); 
              ?></div>
            </div>
            <div class="text-success">
              <svg width="24" height="24" fill="currentColor"><use xlink:href="#calendar"/></svg>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-soft border-start border-warning border-4">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="small text-muted">Pending</div>
              <div class="h4 mb-0"><?php 
                $pending = array_filter($by_status, function($r) { return $r->status === 'pending'; });
                $pending_count = array_sum(array_map(function($r) { return (int)$r->cnt; }, $pending));
                echo $pending_count; 
              ?></div>
            </div>
            <div class="text-warning">
              <svg width="24" height="24" fill="currentColor"><use xlink:href="#clock"/></svg>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-soft border-start border-info border-4">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="small text-muted">Avg Days/Request</div>
              <div class="h4 mb-0"><?php 
                $avg_days = $total_requests > 0 ? round($total_days / $total_requests, 1) : 0;
                echo $avg_days; 
              ?></div>
            </div>
            <div class="text-info">
              <svg width="24" height="24" fill="currentColor"><use xlink:href="#bar-chart"/></svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Status Breakdown -->
    <div class="col-md-6 mb-3">
      <div class="card shadow-soft">
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
                      <td><?php $this->load->view('partials/status_badge', ['status' => $r->status]); ?></td>
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
    </div>

    <!-- Employee Breakdown -->
    <div class="col-md-6 mb-3">
      <div class="card shadow-soft">
        <div class="card-body">
          <h5 class="card-title mb-2">Top Employees by Leave Days</h5>
          <?php if (empty($by_employee)): ?>
            <div class="text-muted">No employee data available.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th class="text-center">Requests</th>
                    <th class="text-center">Days</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($by_employee as $r): ?>
                    <tr>
                      <td>
                        <div class="fw-semibold"><?php echo htmlspecialchars($r->name); ?></div>
                        <div class="small text-muted">ID: <?php echo (int)$r->user_id; ?></div>
                      </td>
                      <td class="text-center"><?php echo (int)$r->cnt; ?></td>
                      <td class="text-center fw-semibold"><?php echo (float)$r->total_days; ?></td>
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
  <!-- Monthly Trends -->
  <div class="row">
    <div class="col-md-6 mb-3">
      <div class="card shadow-soft">
        <div class="card-body">
          <h5 class="card-title mb-2">Monthly Trends</h5>
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
    </div>

    <!-- Recent Leaves -->
    <div class="col-md-6 mb-3">
      <div class="card shadow-soft">
        <div class="card-body">
          <h5 class="card-title mb-2">Recent Leave Requests</h5>
          <?php if (empty($recent_leaves)): ?>
            <div class="text-muted">No recent leaves.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle" id="recentLeavesTable">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th class="text-center">Days</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recent_leaves as $r): ?>
                    <tr>
                      <td>
                        <div class="fw-semibold small"><?php echo htmlspecialchars($r->user_name); ?></div>
                        <div class="small text-muted"><?php echo date('M d', strtotime($r->start_date)); ?> - <?php echo date('M d', strtotime($r->end_date)); ?></div>
                      </td>
                      <td>
                        <div class="small"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $r->leave_type))); ?></div>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-light text-dark"><?php echo (float)$r->days; ?></span>
                      </td>
                      <td><?php $this->load->view('partials/status_badge', ['status' => $r->status]); ?></td>
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
  function toggleFilters() {
    const panel = document.getElementById('filtersPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
  }
  
  function exportCSV() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '<?php echo site_url("reports/leaves"); ?>?' + params.toString();
  }
  
  // Initialize DataTable for recent leaves
  $(document).ready(function() {
    $('#recentLeavesTable').DataTable({
      pageLength: 5,
      ordering: false,
      searching: false,
      paging: false,
      info: false
    });
  });
  </script>
<?php $this->load->view('partials/footer'); ?>
