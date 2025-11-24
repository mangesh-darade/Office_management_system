<?php $this->load->view('partials/header', ['title' => 'Reports']); ?>
  <section class="hero-report mb-4 text-white rounded-3 p-4 p-md-5 position-relative overflow-hidden">
    <div class="row align-items-center">
      <div class="col-12 col-lg-8">
        <h1 class="display-6 fw-bold mb-2">Insights & Analytics</h1>
        <p class="mb-3 lead">Track tasks, project progress, and leave trends — all in one colorful dashboard.</p>
        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-primary btn-sm" href="<?php echo site_url('reports/requirements'); ?>"><i class="bi bi-list-check me-1"></i> Requirements Report</a>
          <a class="btn btn-light btn-sm" href="<?php echo site_url('reports/export'); ?>"><i class="bi bi-download me-1"></i> Export Tasks CSV</a>
          <a class="btn btn-outline-light btn-sm" href="<?php echo site_url('leaves/export'); ?>"><i class="bi bi-download me-1"></i> Export Leaves CSV</a>
        </div>
      </div>
      <div class="col-12 col-lg-4 d-none d-lg-block text-end">
        <div class="hero-blob"></div>
      </div>
    </div>
  </section>

  <div class="row g-3 align-items-stretch">
    <div class="col-12 col-lg-6">
      <div class="card h-100 shadow-soft border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center">
              <div class="icon-circle bg-indigo text-white me-2"><i class="bi bi-kanban"></i></div>
              <h5 class="card-title mb-0">Task Status Distribution</h5>
            </div>
            <a class="btn btn-light btn-sm" href="<?php echo site_url('reports/tasks-assignment'); ?>">Open report</a>
          </div>
          <canvas id="taskStatusChart" height="220"></canvas>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100 shadow-soft border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center">
              <div class="icon-circle bg-teal text-white me-2"><i class="bi bi-diagram-3"></i></div>
              <h5 class="card-title mb-0">Projects by Status</h5>
            </div>
            <a class="btn btn-light btn-sm" href="<?php echo site_url('reports/projects-status'); ?>">Open report</a>
          </div>
          <canvas id="projectStatusChart" height="220"></canvas>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100 shadow-soft border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center">
              <div class="icon-circle bg-amber text-dark me-2"><i class="bi bi-calendar-week"></i></div>
              <h5 class="card-title mb-0">Leaves Overview</h5>
            </div>
            <a class="btn btn-light btn-sm" href="<?php echo site_url('reports/leaves'); ?>">Open report</a>
          </div>
          <div class="row g-3 align-items-center">
            <div class="col-12 col-md-6 border-md-end">
              <h6 class="small text-muted mb-1">By Status</h6>
              <canvas id="leavesStatusChart" height="150"></canvas>
              <?php if(empty($leaves_by_status)): ?>
                <div class="small text-muted mt-2">No leave data available yet.</div>
              <?php endif; ?>
            </div>
            <div class="col-12 col-md-6">
              <h6 class="small text-muted mb-1">Last 6 Months</h6>
              <canvas id="leavesMonthlyChart" height="150"></canvas>
              <?php if(empty($leaves_monthly)): ?>
                <div class="small text-muted mt-2">No monthly data yet.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100 shadow-soft border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center">
              <div class="icon-circle bg-purple text-white me-2"><i class="bi bi-people"></i></div>
              <h5 class="card-title mb-0">Top Assignees (Tasks)</h5>
            </div>
            <a class="btn btn-light btn-sm" href="<?php echo site_url('reports/tasks-assignment'); ?>">Open report</a>
          </div>
          <canvas id="taskAssigneeChart" height="180"></canvas>
          <?php if(empty($task_by_assignee)): ?>
            <div class="small text-muted mt-2">No task data available.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-12">
      <div class="card h-100 shadow-soft border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center">
              <div class="icon-circle bg-blue text-white me-2"><i class="bi bi-graph-up"></i></div>
              <h5 class="card-title mb-0">Attendance & Late Marks</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
              <form method="get" class="d-flex align-items-center gap-1">
                <span class="small text-muted">Days:</span>
                <select name="att_days" class="form-select form-select-sm" onchange="this.form.submit()">
                  <?php $attDays = isset($attendance_days) ? (int)$attendance_days : 14; ?>
                  <option value="7"  <?php echo $attDays===7  ? 'selected' : ''; ?>>7</option>
                  <option value="14" <?php echo $attDays===14 ? 'selected' : ''; ?>>14</option>
                  <option value="30" <?php echo $attDays===30 ? 'selected' : ''; ?>>30</option>
                </select>
              </form>
              <a class="btn btn-light btn-sm" href="<?php echo site_url('reports/attendance'); ?>">Open report</a>
            </div>
          </div>

          <h6 class="small text-muted mb-1">
            Attendance (Last <?php echo isset($attendance_days) ? (int)$attendance_days : 14; ?> days<?php if (!empty($attendance_recent_from) && !empty($attendance_recent_to)): ?>:
              <?php echo htmlspecialchars($attendance_recent_from.' to '.$attendance_recent_to); ?>
            <?php endif; ?>)
          </h6>
          <canvas id="attendanceRecentChart" height="110"></canvas>

          <hr class="my-3" />
          <h6 class="small text-muted mb-1">Late Marks (Last 30 days)</h6>
          <canvas id="attendanceLateChart" height="110"></canvas>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    (function(){
      const taskStatus = <?php echo json_encode(isset($task_status) ? $task_status : []); ?>;
      const projStatus = <?php echo json_encode(isset($projects_progress) ? $projects_progress : []); ?>;
      const leavesMonthly = <?php echo json_encode(isset($leaves_monthly) ? $leaves_monthly : []); ?>;
      const leavesByStatus = <?php echo json_encode(isset($leaves_by_status) ? $leaves_by_status : []); ?>;
      const byAssignee = <?php echo json_encode(isset($task_by_assignee) ? $task_by_assignee : []); ?>;
      const attendanceRecent = <?php echo json_encode(isset($attendance_recent) ? $attendance_recent : []); ?>;
      const attendanceLateTop = <?php echo json_encode(isset($attendance_late_top) ? $attendance_late_top : []); ?>;

      const toChartData = (rows, labelKey, valueKey) => ({
        labels: rows.map(r => r[labelKey]),
        values: rows.map(r => parseFloat(r[valueKey] || 0))
      });

      const ts = toChartData(taskStatus, 'status', 'cnt');
      const ps = toChartData(projStatus, 'status', 'cnt');
      const leavesValueKey = (leavesMonthly && leavesMonthly.length && Object.prototype.hasOwnProperty.call(leavesMonthly[0], 'total_days')) ? 'total_days' : 'cnt';
      const lm = toChartData(leavesMonthly, 'ym', leavesValueKey);

      const palette = ['#4f46e5','#06b6d4','#10b981','#f59e0b','#ef4444','#8b5cf6','#22c55e','#3b82f6'];

      if (document.getElementById('taskStatusChart') && ts.labels.length) {
        new Chart(document.getElementById('taskStatusChart'), {
          type: 'doughnut',
          data: { labels: ts.labels, datasets: [{ data: ts.values, backgroundColor: palette }] },
          options: { plugins: { legend: { position: 'bottom' } } }
        });
      }

      if (document.getElementById('projectStatusChart') && ps.labels.length) {
        new Chart(document.getElementById('projectStatusChart'), {
          type: 'bar',
          data: { labels: ps.labels, datasets: [{ label: 'Projects', data: ps.values, backgroundColor: palette[0] }] },
          options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
      }

      const leavesStatusEl = document.getElementById('leavesStatusChart');
      const leavesMonthlyEl = document.getElementById('leavesMonthlyChart');

      const ls = toChartData(leavesByStatus, 'status', 'cnt');
      if (leavesStatusEl && ls.labels.length) {
        new Chart(leavesStatusEl, {
          type: 'doughnut',
          data: { labels: ls.labels, datasets: [{ data: ls.values, backgroundColor: palette }] },
          options: { plugins: { legend: { position: 'bottom' } } }
        });
      }

      if (leavesMonthlyEl && lm.labels.length) {
        new Chart(leavesMonthlyEl, {
          type: 'line',
          data: { labels: lm.labels, datasets: [{ label: 'Leaves', data: lm.values, borderColor: palette[1], backgroundColor: palette[1]+'33', fill: true, tension: .3 }] },
          options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
        });
      }

      // Top assignees chart
      const ta = toChartData(byAssignee, 'label', 'cnt');
      if (document.getElementById('taskAssigneeChart')) {
        new Chart(document.getElementById('taskAssigneeChart'), {
          type: 'bar',
          data: { 
            labels: ta.labels,
            datasets: [{ label: 'Tasks', data: ta.values, backgroundColor: palette[6] }]
          },
          options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
      }

      // Attendance recent chart
      if (document.getElementById('attendanceRecentChart')) {
        new Chart(document.getElementById('attendanceRecentChart'), {
          type: 'line',
          data: { labels: attendanceRecent.map(r => r.d), datasets: [{ label: 'Entries', data: attendanceRecent.map(r => parseInt(r.cnt||0,10)), borderColor: palette[7], backgroundColor: palette[7]+'33', fill: true, tension: .3 }] },
          options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
        });
      }

      // Attendance late chart (top late marks)
      const lateData = toChartData(attendanceLateTop, 'name', 'late_days');
      if (document.getElementById('attendanceLateChart') && lateData.labels.length) {
        new Chart(document.getElementById('attendanceLateChart'), {
          type: 'bar',
          data: {
            labels: lateData.labels,
            datasets: [{ label: 'Late Days', data: lateData.values, backgroundColor: palette[4] }]
          },
          options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
          }
        });
      }
    })();
  </script>
<?php $this->load->view('partials/footer'); ?>
