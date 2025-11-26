<?php $this->load->view('partials/header', ['title' => 'Reports Dashboard']); ?>
  <style>
    .hero-report {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .hero-blob {
      width: 150px;
      height: 150px;
      background: rgba(255,255,255,0.1);
      border-radius: 50%;
      position: absolute;
      top: -50px;
      right: -50px;
      animation: float 6s ease-in-out infinite;
    }
    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-20px); }
    }
    .icon-circle {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      transition: all 0.3s ease;
    }
    .icon-circle:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .card {
      border: none;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      overflow: hidden;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    .card-body {
      padding: 1rem;
    }
    .metric-card {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      border-radius: 12px;
      padding: 1rem;
      margin-bottom: 1rem;
      border-left: 4px solid #667eea;
    }
    .metric-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: #2d3748;
    }
    .metric-label {
      font-size: 0.75rem;
      color: #718096;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .btn-light {
      border-radius: 8px;
      font-weight: 500;
      padding: 0.5rem 1rem;
      transition: all 0.3s ease;
    }
    .btn-light:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .chart-container {
      position: relative;
      height: 180px;
    }
    .badge-new {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      color: white;
      padding: 0.25rem 0.5rem;
      border-radius: 12px;
      font-size: 0.7rem;
      font-weight: 600;
      margin-left: 0.5rem;
    }
  </style>
  <section class="hero-report mb-3 text-white rounded-3 p-4 p-md-5 position-relative overflow-hidden">
    <div class="row align-items-center">
      <div class="col-12">
        <div class="mb-2">
          <span class="badge bg-white bg-opacity-20 text-white px-3 py-1 rounded-pill">
            <i class="bi bi-graph-up-arrow me-2"></i>Analytics Dashboard
          </span>
        </div>
        <h1 class="h3 fw-bold mb-3">Reports Dashboard</h1>
        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-light btn-sm" href="<?php echo site_url('reports/requirements'); ?>">
            <i class="bi bi-clipboard-data me-2"></i>Requirements
          </a>
          <a class="btn btn-outline-light btn-sm" href="<?php echo site_url('reports/projects-status'); ?>">
            <i class="bi bi-diagram-3 me-2"></i>Projects
            <span class="badge-new">NEW</span>
          </a>
          <a class="btn btn-outline-light btn-sm" href="<?php echo site_url('reports/leaves'); ?>">
            <i class="bi bi-calendar-check me-2"></i>Leaves
          </a>
          <a class="btn btn-outline-light btn-sm" href="<?php echo site_url('reports/tasks-assignment'); ?>">
            <i class="bi bi-people me-2"></i>Team
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Quick Stats -->
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-3">
      <div class="metric-card">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="metric-label">Active Projects</div>
            <div class="metric-value">
              <?php 
                $active_projects = 0;
                if (!empty($projects_progress)) {
                  foreach ($projects_progress as $p) {
                    if (in_array($p->status, ['in_progress', 'planning'])) {
                      $active_projects += (int)$p->cnt;
                    }
                  }
                }
                echo $active_projects;
              ?>
            </div>
          </div>
          <div class="icon-circle bg-indigo text-white">
            <i class="bi bi-diagram-3"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="metric-card">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="metric-label">Pending Tasks</div>
            <div class="metric-value">
              <?php 
                $pending_tasks = 0;
                if (!empty($task_status)) {
                  foreach ($task_status as $t) {
                    if (in_array($t->status, ['pending', 'todo'])) {
                      $pending_tasks += (int)$t->cnt;
                    }
                  }
                }
                echo $pending_tasks;
              ?>
            </div>
          </div>
          <div class="icon-circle bg-amber text-dark">
            <i class="bi bi-clock"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="metric-card">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="metric-label">Team Members</div>
            <div class="metric-value">
              <?php 
                $team_size = count($task_by_assignee);
                echo $team_size > 0 ? $team_size : '—';
              ?>
            </div>
          </div>
          <div class="icon-circle bg-teal text-white">
            <i class="bi bi-people"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="metric-card">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="metric-label">Leave Requests</div>
            <div class="metric-value">
              <?php 
                $total_leaves = 0;
                if (!empty($leaves_by_status)) {
                  foreach ($leaves_by_status as $l) {
                    $total_leaves += (int)$l->cnt;
                  }
                }
                echo $total_leaves;
              ?>
            </div>
          </div>
          <div class="icon-circle bg-purple text-white">
            <i class="bi bi-calendar-check"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="row g-3 align-items-stretch">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center">
              <div class="icon-circle bg-indigo text-white me-2">
                <i class="bi bi-kanban"></i>
              </div>
              <div>
                <h5 class="card-title mb-0">Task Status</h5>
              </div>
            </div>
            <a class="btn btn-light btn-sm" href="<?php echo site_url('reports/tasks-assignment'); ?>">
              <i class="bi bi-arrow-right me-1"></i>View
            </a>
          </div>
          <div class="chart-container">
            <canvas id="taskStatusChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center">
              <div class="icon-circle bg-teal text-white me-2">
                <i class="bi bi-diagram-3"></i>
              </div>
              <div>
                <h5 class="card-title mb-0">Projects</h5>
              </div>
            </div>
            <a class="btn btn-light btn-sm" href="<?php echo site_url('reports/projects-status'); ?>">
              <i class="bi bi-arrow-right me-1"></i>View
            </a>
          </div>
          <div class="chart-container">
            <canvas id="projectStatusChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center">
              <div class="icon-circle bg-amber text-dark me-2">
                <i class="bi bi-calendar-week"></i>
              </div>
              <div>
                <h5 class="card-title mb-0">Leaves</h5>
              </div>
            </div>
            <a class="btn btn-light btn-sm" href="<?php echo site_url('reports/leaves'); ?>">
              <i class="bi bi-arrow-right me-1"></i>View
            </a>
          </div>
          <div class="row g-2 align-items-center">
            <div class="col-12 col-md-6 border-md-end">
              <div class="chart-container" style="height: 140px;">
                <canvas id="leavesStatusChart"></canvas>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="chart-container" style="height: 140px;">
                <canvas id="leavesMonthlyChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center">
              <div class="icon-circle bg-purple text-white me-2">
                <i class="bi bi-people"></i>
              </div>
              <div>
                <h5 class="card-title mb-0">Team</h5>
              </div>
            </div>
            <a class="btn btn-light btn-sm" href="<?php echo site_url('reports/tasks-assignment'); ?>">
              <i class="bi bi-arrow-right me-1"></i>View
            </a>
          </div>
          <div class="chart-container">
            <canvas id="taskAssigneeChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center">
              <div class="icon-circle bg-blue text-white me-2">
                <i class="bi bi-graph-up"></i>
              </div>
              <div>
                <h5 class="card-title mb-0">Attendance</h5>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <form method="get" class="d-flex align-items-center gap-1">
                <select name="att_days" class="form-select form-select-sm" onchange="this.form.submit()">
                  <?php $attDays = isset($attendance_days) ? (int)$attendance_days : 14; ?>
                  <option value="7"  <?php echo $attDays===7  ? 'selected' : ''; ?>>7</option>
                  <option value="14" <?php echo $attDays===14 ? 'selected' : ''; ?>>14</option>
                  <option value="30" <?php echo $attDays===30 ? 'selected' : ''; ?>>30</option>
                </select>
              </form>
              <a class="btn btn-light btn-sm" href="<?php echo site_url('reports/attendance'); ?>">
                <i class="bi bi-arrow-right me-1"></i>Full
              </a>
            </div>
          </div>

          <div class="row g-2">
            <div class="col-12 col-md-8">
              <div class="chart-container" style="height: 100px;">
                <canvas id="attendanceRecentChart"></canvas>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="chart-container" style="height: 100px;">
                <canvas id="attendanceLateChart"></canvas>
              </div>
            </div>
          </div>
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

      const palette = {
        primary: ['#4f46e5', '#7c3aed', '#2563eb', '#1d4ed8', '#4338ca'],
        success: ['#10b981', '#059669', '#047857', '#065f46', '#064e3b'],
        warning: ['#f59e0b', '#d97706', '#b45309', '#92400e', '#78350f'],
        danger: ['#ef4444', '#dc2626', '#b91c1c', '#991b1b', '#7f1d1d'],
        info: ['#06b6d4', '#0891b2', '#0e7490', '#155e75', '#164e63'],
        purple: ['#8b5cf6', '#7c3aed', '#6d28d9', '#5b21b6', '#4c1d95']
      };
      
      const getChartColors = (type = 'primary') => palette[type] || palette.primary;

      if (document.getElementById('taskStatusChart') && ts.labels.length) {
        new Chart(document.getElementById('taskStatusChart'), {
          type: 'doughnut',
          data: { 
            labels: ts.labels, 
            datasets: [{ 
              data: ts.values, 
              backgroundColor: getChartColors('primary'),
              borderWidth: 2,
              borderColor: '#fff'
            }] 
          },
          options: { 
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
              legend: { 
                position: 'bottom',
                labels: {
                  padding: 15,
                  font: { size: 12 }
                }
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                  }
                }
              }
            }
          }
        });
      }

      if (document.getElementById('projectStatusChart') && ps.labels.length) {
        new Chart(document.getElementById('projectStatusChart'), {
          type: 'bar',
          data: { 
            labels: ps.labels, 
            datasets: [{ 
              label: 'Projects', 
              data: ps.values, 
              backgroundColor: getChartColors('success')[0],
              borderRadius: 8,
              borderWidth: 0
            }] 
          },
          options: { 
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }, 
            scales: { 
              y: { 
                beginAtZero: true,
                grid: {
                  display: true,
                  drawBorder: false
                }
              },
              x: {
                grid: {
                  display: false
                }
              }
            }
          }
        });
      }

      const leavesStatusEl = document.getElementById('leavesStatusChart');
      const leavesMonthlyEl = document.getElementById('leavesMonthlyChart');

      const ls = toChartData(leavesByStatus, 'status', 'cnt');
      if (leavesStatusEl && ls.labels.length) {
        new Chart(leavesStatusEl, {
          type: 'doughnut',
          data: { 
            labels: ls.labels, 
            datasets: [{ 
              data: ls.values, 
              backgroundColor: getChartColors('warning'),
              borderWidth: 2,
              borderColor: '#fff'
            }] 
          },
          options: { 
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
              legend: { 
                position: 'bottom',
                labels: {
                  padding: 10,
                  font: { size: 11 }
                }
              }
            }
          }
        });
      }

      if (leavesMonthlyEl && lm.labels.length) {
        new Chart(leavesMonthlyEl, {
          type: 'line',
          data: { 
            labels: lm.labels, 
            datasets: [{ 
              label: 'Leave Days', 
              data: lm.values, 
              borderColor: getChartColors('info')[0], 
              backgroundColor: getChartColors('info')[0] + '20', 
              fill: true, 
              tension: 0.4,
              borderWidth: 3,
              pointRadius: 4,
              pointBackgroundColor: getChartColors('info')[0]
            }] 
          },
          options: { 
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
              legend: { 
                display: false
              }
            }, 
            scales: { 
              y: { 
                beginAtZero: true,
                grid: {
                  display: true,
                  drawBorder: false
                }
              },
              x: {
                grid: {
                  display: false
                }
              }
            }
          }
        });
      }

      // Top assignees chart
      const ta = toChartData(byAssignee, 'label', 'cnt');
      if (document.getElementById('taskAssigneeChart')) {
        new Chart(document.getElementById('taskAssigneeChart'), {
          type: 'bar',
          data: { 
            labels: ta.labels,
            datasets: [{ 
              label: 'Tasks', 
              data: ta.values, 
              backgroundColor: getChartColors('purple')[0],
              borderRadius: 6,
              borderWidth: 0
            }]
          },
          options: { 
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    return context.parsed.y + ' tasks assigned';
                  }
                }
              }
            }, 
            scales: { 
              y: { 
                beginAtZero: true,
                grid: {
                  display: true,
                  drawBorder: false
                }
              },
              x: {
                grid: {
                  display: false
                }
              }
            }
          }
        });
      }

      // Attendance recent chart
      if (document.getElementById('attendanceRecentChart')) {
        new Chart(document.getElementById('attendanceRecentChart'), {
          type: 'line',
          data: { 
            labels: attendanceRecent.map(r => r.d), 
            datasets: [{ 
              label: 'Attendance', 
              data: attendanceRecent.map(r => parseInt(r.cnt||0,10)), 
              borderColor: getChartColors('success')[0], 
              backgroundColor: getChartColors('success')[0] + '20', 
              fill: true, 
              tension: 0.4,
              borderWidth: 3,
              pointRadius: 3,
              pointBackgroundColor: getChartColors('success')[0]
            }] 
          },
          options: { 
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
              legend: { display: false }
            }, 
            scales: { 
              y: { 
                beginAtZero: true,
                grid: {
                  display: true,
                  drawBorder: false
                }
              },
              x: {
                grid: {
                  display: false
                }
              }
            }
          }
        });
      }

      // Attendance late chart (top late marks)
      const lateData = toChartData(attendanceLateTop, 'name', 'late_days');
      if (document.getElementById('attendanceLateChart') && lateData.labels.length) {
        new Chart(document.getElementById('attendanceLateChart'), {
          type: 'bar',
          data: {
            labels: lateData.labels,
            datasets: [{ 
              label: 'Late Days', 
              data: lateData.values, 
              backgroundColor: getChartColors('danger')[0],
              borderRadius: 4,
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    return context.parsed.y + ' late days';
                  }
                }
              }
            },
            scales: { 
              y: { 
                beginAtZero: true, 
                ticks: { precision: 0 },
                grid: {
                  display: true,
                  drawBorder: false
                }
              },
              x: {
                grid: {
                  display: false
                }
              }
            }
          }
        });
      }
    })();
  </script>
<?php $this->load->view('partials/footer'); ?>
