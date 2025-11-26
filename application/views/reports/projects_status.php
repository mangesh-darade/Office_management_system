<?php $this->load->view('partials/header', ['title' => 'Projects Status Report']); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Projects Status Report</h1>
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
          <label class="form-label">Search</label>
          <input type="text" name="search" class="form-control form-control-sm" 
                 value="<?php echo htmlspecialchars(isset($filters['search']) ? $filters['search'] : ''); ?>" placeholder="Project name">
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
          <label class="form-label">Client</label>
          <select name="client_id" class="form-select form-select-sm">
            <option value="">All Clients</option>
            <?php foreach($filter_options['clients'] as $client): ?>
              <option value="<?php echo $client->id; ?>" <?php echo (isset($filters['client_id']) && $filters['client_id'] == $client->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($client->company_name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Manager</label>
          <select name="project_manager_id" class="form-select form-select-sm">
            <option value="">All Managers</option>
            <?php foreach($filter_options['managers'] as $manager): ?>
              <option value="<?php echo $manager->id; ?>" <?php echo (isset($filters['project_manager_id']) && $filters['project_manager_id'] == $manager->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($manager->email); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Start From</label>
          <input type="date" name="date_from" class="form-control form-control-sm" 
                 value="<?php echo htmlspecialchars(isset($filters['date_from']) ? $filters['date_from'] : ''); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">End To</label>
          <input type="date" name="date_to" class="form-control form-control-sm" 
                 value="<?php echo htmlspecialchars(isset($filters['date_to']) ? $filters['date_to'] : ''); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">&nbsp;</label>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <a href="<?php echo site_url('reports/projects-status'); ?>" class="btn btn-outline-secondary btn-sm">Clear</a>
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
              <div class="small text-muted">Total Projects</div>
              <div class="h4 mb-0"><?php echo count($project_details); ?></div>
            </div>
            <div class="text-primary">
              <svg width="24" height="24" fill="currentColor"><use xlink:href="#folder"/></svg>
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
              <div class="small text-muted">Completed Projects</div>
              <div class="h4 mb-0"><?php 
                $completed = array_filter($project_details, function($p) { return $p->project_status === 'completed'; });
                echo count($completed); 
              ?></div>
            </div>
            <div class="text-success">
              <svg width="24" height="24" fill="currentColor"><use xlink:href="#check-circle"/></svg>
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
              <div class="small text-muted">In Progress</div>
              <div class="h4 mb-0"><?php 
                $in_progress = array_filter($project_details, function($p) { return $p->project_status === 'in_progress'; });
                echo count($in_progress); 
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
      <div class="card shadow-soft border-start border-danger border-4">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="small text-muted">Overdue</div>
              <div class="h4 mb-0"><?php 
                $overdue = array_filter($project_details, function($p) { return isset($p->is_overdue) && $p->is_overdue; });
                echo count($overdue); 
              ?></div>
            </div>
            <div class="text-danger">
              <svg width="24" height="24" fill="currentColor"><use xlink:href="#exclamation-triangle"/></svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Status Breakdown -->
    <div class="col-md-4 mb-3">
      <div class="card shadow-soft">
        <div class="card-body">
          <h5 class="card-title mb-2">Status Breakdown</h5>
          <?php if (empty($rows)): ?>
            <div class="text-muted">No project data available.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead>
                  <tr>
                    <th>Status</th>
                    <th class="text-center">Projects</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rows as $r): ?>
                    <tr>
                      <td><?php $this->load->view('partials/status_badge', ['status' => $r->status]); ?></td>
                      <td class="text-center fw-semibold"><?php echo (int)$r->cnt; ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Project Details -->
    <div class="col-md-8 mb-3">
      <div class="card shadow-soft">
        <div class="card-body">
          <h5 class="card-title mb-2">Project Portfolio</h5>
          <?php if (empty($project_details)): ?>
            <div class="text-muted">No project data available.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle" id="projectsTable">
                <thead>
                  <tr>
                    <th>Project</th>
                    <th>Client</th>
                    <th>Manager</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>Timeline</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($project_details as $p): ?>
                    <tr>
                      <td>
                        <div class="fw-semibold"><?php echo htmlspecialchars($p->project_name); ?></div>
                        <div class="small text-muted">ID: <?php echo (int)$p->project_id; ?></div>
                        <?php if (isset($p->budget) && $p->budget): ?>
                          <div class="small text-success">₹<?php echo number_format($p->budget, 2); ?></div>
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars($p->client_name); ?></td>
                      <td>
                        <div class="small"><?php echo htmlspecialchars($p->manager_name); ?></div>
                      </td>
                      <td>
                        <?php if ($p->total_tasks > 0): ?>
                          <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $p->completion_percentage; ?>%" 
                                 title="<?php echo $p->completed_tasks; ?>/<?php echo $p->total_tasks; ?> tasks completed">
                              <?php echo $p->completion_percentage; ?>%
                            </div>
                          </div>
                          <div class="small text-muted mt-1"><?php echo $p->completed_tasks; ?>/<?php echo $p->total_tasks; ?> tasks</div>
                        <?php else: ?>
                          <span class="text-muted">No tasks</span>
                        <?php endif; ?>
                      </td>
                      <td><?php $this->load->view('partials/status_badge', ['status' => $p->project_status]); ?></td>
                      <td>
                        <?php if (isset($p->end_date) && $p->end_date): ?>
                          <div class="small <?php echo (isset($p->is_overdue) && $p->is_overdue) ? 'text-danger' : 'text-dark'; ?>">
                            <?php echo date('M d', strtotime($p->end_date)); ?>
                          </div>
                          <?php if (isset($p->days_remaining) && $p->days_remaining !== null): ?>
                            <div class="small <?php echo (isset($p->is_overdue) && $p->is_overdue) ? 'text-danger' : 'text-muted'; ?>">
                              <?php if (isset($p->is_overdue) && $p->is_overdue): ?>
                                ⚠️ <?php echo abs($p->days_remaining); ?> days overdue
                              <?php elseif ($p->days_remaining == 0): ?>
                                📅 Due today
                              <?php else: ?>
                                📅 <?php echo $p->days_remaining; ?> days left
                              <?php endif; ?>
                            </div>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="text-muted">No deadline</span>
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
    window.location.href = '<?php echo site_url("reports/projects-status"); ?>?' + params.toString();
  }
  
  // Initialize DataTable for better UX
  $(document).ready(function() {
    $('#projectsTable').DataTable({
      pageLength: 25,
      order: [[0, 'asc']], // Sort by project name
      responsive: true,
      language: {
        search: 'Search projects:',
        lengthMenu: 'Show _MENU_ projects per page'
      }
    });
  });
  </script>
<?php $this->load->view('partials/footer'); ?>
