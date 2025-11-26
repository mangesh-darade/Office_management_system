<?php $this->load->view('partials/header', ['title' => 'Requirements Report']); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Requirements Report</h1>
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
        <div class="col-md-3">
          <label class="form-label">Search</label>
          <input type="text" name="search" class="form-control form-control-sm" 
                 value="<?php echo htmlspecialchars(isset($filters['search']) ? $filters['search'] : ''); ?>" placeholder="Title or Req #">
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
          <label class="form-label">Priority</label>
          <select name="priority" class="form-select form-select-sm">
            <option value="">All Priorities</option>
            <?php foreach($filter_options['priorities'] as $priority): ?>
              <option value="<?php echo $priority; ?>" <?php echo (isset($filters['priority']) && $filters['priority'] === $priority) ? 'selected' : ''; ?>><?php echo ucfirst($priority); ?></option>
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
          <label class="form-label">Project</label>
          <select name="project_id" class="form-select form-select-sm">
            <option value="">All Projects</option>
            <?php foreach($filter_options['projects'] as $project): ?>
              <option value="<?php echo $project->id; ?>" <?php echo (isset($filters['project_id']) && $filters['project_id'] == $project->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($project->name); ?></option>
            <?php endforeach; ?>
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
            <a href="<?php echo site_url('reports/requirements'); ?>" class="btn btn-outline-secondary btn-sm">Clear</a>
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
              <div class="small text-muted">Total Requirements</div>
              <div class="h4 mb-0"><?php echo count($rows); ?></div>
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
              <div class="small text-muted">Completed Tasks</div>
              <div class="h4 mb-0"><?php 
                $total_completed = array_sum(array_map(function($r) { return $r->counts['completed']; }, $rows));
                echo $total_completed; 
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
                $total_in_progress = array_sum(array_map(function($r) { return $r->counts['in_progress']; }, $rows));
                echo $total_in_progress; 
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
                $overdue = array_filter($rows, function($r) { 
                  return $r->expected_delivery_date && strtotime($r->expected_delivery_date) < strtotime('today'); 
                });
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

  <div class="card shadow-soft">
    <div class="card-body">
      <?php if (empty($rows)): ?>
        <div class="text-muted">No requirements found.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle" id="requirementsTable">
            <thead>
              <tr>
                <th style="width:3%">#</th>
                <th style="width:18%">Requirement</th>
                <th style="width:12%">Client</th>
                <th style="width:12%">Project</th>
                <th style="width:10%">Owner</th>
                <th style="width:8%">Priority</th>
                <th style="width:8%">Status</th>
                <th style="width:15%">Progress</th>
                <th class="text-center" style="width:6%">Tasks</th>
                <th style="width:8%">Delivery</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><span class="badge bg-secondary"><?php echo (int)$r->id; ?></span></td>
                  <td>
                    <div class="fw-semibold text-truncate" title="<?php echo htmlspecialchars($r->title); ?>"><?php echo htmlspecialchars($r->title); ?></div>
                    <div class="small text-muted"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $r->requirement_type))); ?></div>
                    <?php if ($r->budget_estimate): ?>
                      <div class="small text-success">₹<?php echo number_format($r->budget_estimate, 2); ?></div>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($r->client_name ?: '—'); ?></td>
                  <td><?php echo htmlspecialchars($r->project_name ?: '—'); ?></td>
                  <td><?php echo htmlspecialchars($r->owner ?: '—'); ?></td>
                  <td><?php $this->load->view('partials/priority_badge', ['priority' => $r->priority]); ?></td>
                  <td><?php $this->load->view('partials/status_badge', ['status' => $r->req_status]); ?></td>
                  <td>
                    <?php if ($r->total > 0): ?>
                      <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $r->completion_percentage; ?>%" 
                             title="Completed: <?php echo $r->counts['completed']; ?>/<?php echo $r->total; ?>">
                          <?php echo $r->completion_percentage; ?>%
                        </div>
                      </div>
                      <div class="small text-muted mt-1">
                        <span class="badge bg-secondary me-1"><?php echo (int)$r->counts['pending']; ?></span>
                        <span class="badge bg-info text-dark me-1"><?php echo (int)$r->counts['in_progress']; ?></span>
                        <span class="badge bg-success me-1"><?php echo (int)$r->counts['completed']; ?></span>
                        <span class="badge bg-danger"><?php echo (int)$r->counts['blocked']; ?></span>
                      </div>
                    <?php else: ?>
                      <span class="text-muted">No tasks</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center fw-semibold"><?php echo (int)$r->total; ?></td>
                  <td>
                    <?php if ($r->expected_delivery_date): ?>
                      <div class="small <?php echo (strtotime($r->expected_delivery_date) < strtotime('today')) ? 'text-danger' : 'text-dark'; ?>">
                        <?php echo date('M d', strtotime($r->expected_delivery_date)); ?>
                        <?php if (strtotime($r->expected_delivery_date) < strtotime('today')): ?>
                          <div class="text-danger">⚠️ Overdue</div>
                        <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <span class="text-muted">—</span>
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
    const panel = document.getElementById('filtersPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
  }
  
  function exportCSV() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '<?php echo site_url("reports/requirements"); ?>?' + params.toString();
  }
  
  // Initialize DataTable for better UX
  $(document).ready(function() {
    $('#requirementsTable').DataTable({
      pageLength: 25,
      order: [[0, 'desc']],
      responsive: true,
      language: {
        search: 'Search requirements:',
        lengthMenu: 'Show _MENU_ requirements per page'
      }
    });
  });
  </script>
<?php $this->load->view('partials/footer'); ?>
