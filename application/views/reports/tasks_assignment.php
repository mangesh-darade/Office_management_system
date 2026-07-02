<?php $this->load->view('partials/header', ['title' => 'Task Assignment Report']); ?>
  <div class="container-fluid py-3">
  <div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-people text-primary me-2"></i>Task Assignment Report</h4>
      <p class="text-muted small mb-0">View workload distribution and task completion per employee</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-sm-0">
      <button class="btn btn-outline-primary btn-sm" onclick="toggleFilters()"><i class="bi bi-funnel me-1"></i>Filters</button>
      <button class="btn btn-outline-success btn-sm" onclick="exportCSV()"><i class="bi bi-download me-1"></i>Export CSV</button>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reports'); ?>"><i class="bi bi-arrow-left me-1"></i>Reports</a>
    </div>
  </div>

  <!-- Filters Panel -->
  <div id="filtersPanel" class="card shadow-sm border-0 mb-3" style="display:none;">
    <div class="card-body">
      <form method="GET" class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Search</label>
          <input type="text" name="search" class="form-control form-control-sm" 
                 value="<?php echo esc_view(isset($filters['search']) ? $filters['search'] : ''); ?>" placeholder="Task title or description">
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
          <label class="form-label">Project</label>
          <select name="project_id" class="form-select form-select-sm">
            <option value="">All Projects</option>
            <?php foreach($filter_options['projects'] as $project): ?>
              <option value="<?php echo $project->id; ?>" <?php echo (isset($filters['project_id']) && $filters['project_id'] == $project->id) ? 'selected' : ''; ?>><?php echo esc_view($project->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Date From</label>
          <input type="date" name="date_from" class="form-control form-control-sm" 
                 value="<?php echo esc_view(isset($filters['date_from']) ? $filters['date_from'] : ''); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Date To</label>
          <input type="date" name="date_to" class="form-control form-control-sm" 
                 value="<?php echo esc_view(isset($filters['date_to']) ? $filters['date_to'] : ''); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">&nbsp;</label>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <a href="<?php echo site_url('reports/tasks-assignment'); ?>" class="btn btn-outline-secondary btn-sm">Clear</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Summary Statistics -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0 border-start border-primary border-4">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="small text-muted">Total Employees</div>
              <div class="h4 mb-0 fw-bold"><?php echo count($rows); ?></div>
            </div>
            <div class="text-primary"><i class="bi bi-people fs-4"></i></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0 border-start border-success border-4">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="small text-muted">Total Tasks</div>
              <div class="h4 mb-0 fw-bold"><?php 
                $total_tasks = array_sum(array_map(function($r) { return $r->total; }, $rows));
                echo $total_tasks; 
              ?></div>
            </div>
            <div class="text-success"><i class="bi bi-check2-square fs-4"></i></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0 border-start border-warning border-4">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="small text-muted">In Progress</div>
              <div class="h4 mb-0 fw-bold"><?php 
                $total_in_progress = array_sum(array_map(function($r) { return $r->counts['in_progress']; }, $rows));
                echo $total_in_progress; 
              ?></div>
            </div>
            <div class="text-warning"><i class="bi bi-clock-history fs-4"></i></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0 border-start border-info border-4">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="small text-muted">Avg Completion</div>
              <div class="h4 mb-0 fw-bold"><?php 
                $avg_completion = count($rows) > 0 ? round(array_sum(array_map(function($r) { return $r->completion_percentage; }, $rows)) / count($rows), 1) : 0;
                echo $avg_completion . '%'; 
              ?></div>
            </div>
            <div class="text-info"><i class="bi bi-graph-up-arrow fs-4"></i></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <?php if (empty($rows)): ?>
        <div class="empty-state py-5">
          <div class="empty-icon mx-auto"><i class="bi bi-people"></i></div>
          <h6 class="fw-semibold">No task data found</h6>
          <p class="text-muted small mb-0">Try adjusting your filters or assign tasks to employees</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="tasksAssignmentTable" data-dt-manual="1">
            <thead class="table-light">
              <tr>
                <th style="width:20%">Employee</th>
                <th style="width:35%">Tasks</th>
                <th class="text-center" style="width:8%">Pending</th>
                <th class="text-center" style="width:8%">In Progress</th>
                <th class="text-center" style="width:8%">Completed</th>
                <th class="text-center" style="width:8%">Blocked</th>
                <th class="text-center" style="width:6%">Total</th>
                <th style="width:7%">Progress</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?php echo esc_view($r->name); ?></div>
                    <div class="small text-muted">ID: <?php echo (int)$r->user_id; ?></div>
                  </td>
                  <td>
                    <?php if (!empty($r->tasks)): ?>
                      <div class="task-list">
                        <?php foreach (array_slice($r->tasks, 0, 3) as $task): ?>
                          <div class="small mb-1">
                            <span class="badge bg-light text-dark me-1"><?php echo esc_view(ucfirst(str_replace('_', ' ', $task->status))); ?></span>
                            <span class="text-truncate" title="<?php echo esc_view($task->title); ?>"><?php echo esc_view($task->title); ?></span>
                          </div>
                        <?php endforeach; ?>
                        <?php if (count($r->tasks) > 3): ?>
                          <div class="small text-muted">+<?php echo count($r->tasks) - 3; ?> more tasks</div>
                        <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <span class="text-muted small"><i class="bi bi-dash-circle me-1"></i>No tasks</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center"><span class="badge bg-secondary"><?php echo (int)$r->counts['pending']; ?></span></td>
                  <td class="text-center"><span class="badge bg-info text-dark"><?php echo (int)$r->counts['in_progress']; ?></span></td>
                  <td class="text-center"><span class="badge bg-success"><?php echo (int)$r->counts['completed']; ?></span></td>
                  <td class="text-center"><span class="badge bg-danger"><?php echo (int)$r->counts['blocked']; ?></span></td>
                  <td class="text-center fw-bold"><?php echo (int)$r->total; ?></td>
                  <td>
                    <?php if ($r->total > 0): ?>
                      <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $r->completion_percentage; ?>%" 
                             title="Completed: <?php echo $r->counts['completed']; ?>/<?php echo $r->total; ?>">
                          <?php echo $r->completion_percentage; ?>%
                        </div>
                      </div>
                    <?php else: ?>
                      <span class="text-muted">--</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
  function toggleFilters() {
    var panel = document.getElementById('filtersPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
  }
  
  function exportCSV() {
    var params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '<?php echo site_url("reports/tasks-assignment"); ?>?' + params.toString();
  }
  
  $(document).ready(function() {
    if ($.fn.DataTable) {
      $('#tasksAssignmentTable').DataTable({
        ordering: true,
        order: [[0, 'asc']],
        paging: false,
        searching: false,
        lengthChange: false,
        info: false,
        responsive: true
      });
    }
  });
  </script>
  </div><!-- .container-fluid -->
<?php $this->load->view('partials/footer'); ?>
