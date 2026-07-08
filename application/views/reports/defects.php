<?php $this->load->view('partials/header', ['title' => 'Defect Summary Report']); ?>
<div class="container-fluid py-3">
  <div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-bug text-primary me-2"></i>Defect Summary Report</h4>
      <p class="text-muted small mb-0">Open, overdue, severity and project-wise defect breakdown</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-sm-0">
      <button class="btn btn-outline-primary btn-sm" onclick="toggleFilters()"><i class="bi bi-funnel me-1"></i>Filters</button>
      <button class="btn btn-outline-success btn-sm" onclick="exportCSV()"><i class="bi bi-download me-1"></i>Export CSV</button>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reports'); ?>"><i class="bi bi-arrow-left me-1"></i>Reports</a>
    </div>
  </div>

  <div id="filtersPanel" class="card shadow-sm border-0 mb-3" style="display:none;">
    <div class="card-body">
      <form method="GET" class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Search</label>
          <input type="text" name="search" class="form-control form-control-sm" value="<?php echo esc_view(isset($filters['search']) ? $filters['search'] : ''); ?>" placeholder="Number, title, description">
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">All Statuses</option>
            <?php foreach ($filter_options['statuses'] as $status): ?>
            <option value="<?php echo esc_view($status); ?>" <?php echo (!empty($filters['status']) && $filters['status'] === $status) ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $status)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Severity</label>
          <select name="severity" class="form-select form-select-sm">
            <option value="">All Severities</option>
            <?php foreach ($filter_options['severities'] as $severity): ?>
            <option value="<?php echo esc_view($severity); ?>" <?php echo (!empty($filters['severity']) && $filters['severity'] === $severity) ? 'selected' : ''; ?>><?php echo ucfirst($severity); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Project</label>
          <select name="project_id" class="form-select form-select-sm">
            <option value="">All Projects</option>
            <?php foreach ($filter_options['projects'] as $project): ?>
            <option value="<?php echo (int) $project->id; ?>" <?php echo (!empty($filters['project_id']) && (int) $filters['project_id'] === (int) $project->id) ? 'selected' : ''; ?>><?php echo esc_view($project->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label d-block">Overdue</label>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="overdue" value="1" id="fltOverdue" <?php echo !empty($filters['overdue']) ? 'checked' : ''; ?>>
            <label class="form-check-label small" for="fltOverdue">Overdue only</label>
          </div>
        </div>
        <div class="col-md-1">
          <label class="form-label">&nbsp;</label>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
          </div>
        </div>
        <div class="col-12">
          <a href="<?php echo site_url('reports/defects'); ?>" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0 border-start border-primary border-4">
        <div class="card-body">
          <div class="small text-muted">Total Defects</div>
          <div class="h4 mb-0 fw-bold"><?php echo (int) $summary['total']; ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0 border-start border-warning border-4">
        <div class="card-body">
          <div class="small text-muted">Open / In Progress</div>
          <div class="h4 mb-0 fw-bold"><?php echo (int) $summary['open']; ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0 border-start border-danger border-4">
        <div class="card-body">
          <div class="small text-muted">Overdue</div>
          <div class="h4 mb-0 fw-bold"><?php echo (int) $summary['overdue']; ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0 border-start border-info border-4">
        <div class="card-body">
          <div class="small text-muted">Projects</div>
          <div class="h4 mb-0 fw-bold"><?php echo count($project_summary); ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white py-2"><h6 class="mb-0 small fw-semibold">By Status</h6></div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Status</th><th class="text-end">Count</th></tr></thead>
            <tbody>
              <?php if (empty($summary['by_status'])): ?>
              <tr><td colspan="2" class="text-muted small text-center py-3">No data</td></tr>
              <?php else: foreach ($summary['by_status'] as $st => $cnt): ?>
              <tr><td><?php echo esc_view(ucwords(str_replace('_', ' ', $st))); ?></td><td class="text-end"><?php echo (int) $cnt; ?></td></tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white py-2"><h6 class="mb-0 small fw-semibold">By Severity</h6></div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Severity</th><th class="text-end">Count</th></tr></thead>
            <tbody>
              <?php if (empty($summary['by_severity'])): ?>
              <tr><td colspan="2" class="text-muted small text-center py-3">No data</td></tr>
              <?php else: foreach ($summary['by_severity'] as $sev => $cnt): ?>
              <tr><td><?php echo esc_view(ucfirst($sev)); ?></td><td class="text-end"><?php echo (int) $cnt; ?></td></tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-2"><h6 class="mb-0 small fw-semibold">By Project</h6></div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Project</th>
            <th class="text-end">Total</th>
            <th class="text-end">Open</th>
            <th class="text-end">Overdue</th>
            <th class="text-end">Critical</th>
            <th class="text-end">High</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($project_summary)): ?>
          <tr><td colspan="6" class="text-muted small text-center py-3">No defects match the current filters.</td></tr>
          <?php else: foreach ($project_summary as $p): ?>
          <tr>
            <td><?php echo esc_view($p->project_name); ?></td>
            <td class="text-end"><?php echo (int) $p->total; ?></td>
            <td class="text-end"><?php echo (int) $p->open; ?></td>
            <td class="text-end"><?php echo (int) $p->overdue; ?></td>
            <td class="text-end"><?php echo (int) $p->critical; ?></td>
            <td class="text-end"><?php echo (int) $p->high; ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 small fw-semibold">Defect List</h6>
      <span class="text-muted small"><?php echo count($rows); ?> record(s)</span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Number</th>
            <th>Title</th>
            <th>Project</th>
            <th>Severity</th>
            <th>Status</th>
            <th>Assignee</th>
            <th>Due</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="text-muted small text-center py-3">No defects found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
          <?php $overdue = function_exists('defect_is_overdue') && defect_is_overdue($r); ?>
          <tr<?php echo $overdue ? ' class="table-warning"' : ''; ?>>
            <td><?php echo esc_view($r->defect_number); ?></td>
            <td><?php echo esc_view($r->title); ?><?php if ($overdue): ?> <span class="badge bg-danger">Overdue</span><?php endif; ?></td>
            <td><?php echo esc_view($r->project_name ?: '—'); ?></td>
            <td><?php echo esc_view($r->severity); ?></td>
            <td><?php echo esc_view(str_replace('_', ' ', $r->status)); ?></td>
            <td><?php echo esc_view($r->assignee_name ?: '—'); ?></td>
            <td><?php echo esc_view(!empty($r->due_date) ? $r->due_date : '—'); ?></td>
            <td><a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('defects/view/' . (int) $r->id); ?>">View</a></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
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
  window.location.href = '<?php echo site_url('reports/defects'); ?>?' + params.toString();
}
</script>
<?php $this->load->view('partials/footer'); ?>
