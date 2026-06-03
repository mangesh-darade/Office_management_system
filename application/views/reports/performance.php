<?php $this->load->view('partials/header', ['title' => 'Performance Report']); ?>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-award text-primary me-2"></i>Performance Report</h4>
      <p class="text-muted mb-0 small">Appraisal summary by period, department, and status</p>
    </div>
    <div class="d-flex gap-2">
      <?php if(function_exists('has_module_access') && (has_module_access('performance_export') || has_module_access('reports_performance') || has_module_access('reports') || (function_exists('is_admin_group') && is_admin_group()))): ?>
      <a href="<?php echo site_url('reports/performance?' . http_build_query(['period' => $period, 'status' => $status, 'department' => $department, 'export' => 'csv'])); ?>"
         class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i>Export CSV</a>
      <?php endif; ?>
      <a href="<?php echo site_url('performance'); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Appraisals
      </a>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2">
      <form method="get" action="<?php echo site_url('reports/performance'); ?>" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label small fw-bold text-uppercase text-muted mb-1">Period</label>
          <select name="period" class="form-select form-select-sm">
            <option value="">— All Periods —</option>
            <?php foreach ($periods as $p): ?>
              <option value="<?php echo htmlspecialchars($p); ?>" <?php echo ($period === $p) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($p); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small fw-bold text-uppercase text-muted mb-1">Department</label>
          <select name="department" class="form-select form-select-sm">
            <option value="">— All Departments —</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?php echo htmlspecialchars($d); ?>" <?php echo ($department === $d) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($d); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-2">
          <label class="form-label small fw-bold text-uppercase text-muted mb-1">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">— All —</option>
            <?php foreach (array('draft' => 'Draft', 'submitted' => 'Submitted', 'approved' => 'Approved') as $val => $lbl): ?>
              <option value="<?php echo $val; ?>" <?php echo ($status === $val) ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
          <a href="<?php echo site_url('reports/performance'); ?>" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-md-2">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-primary mb-0"><?php echo (int) $summary['count']; ?></div>
        <div class="small text-muted">Appraisals</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-success mb-0"><?php echo number_format($summary['avg_kpi'], 2); ?></div>
        <div class="small text-muted">Avg KPI</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-info mb-0"><?php echo number_format($summary['avg_rating'], 1); ?></div>
        <div class="small text-muted">Avg Rating</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-secondary mb-0"><?php echo (int) $summary['draft']; ?></div>
        <div class="small text-muted">Draft</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-warning mb-0"><?php echo (int) $summary['submitted']; ?></div>
        <div class="small text-muted">Submitted</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-success mb-0"><?php echo (int) $summary['approved']; ?></div>
        <div class="small text-muted">Approved</div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 datatable">
          <thead class="table-light">
            <tr>
              <th>Employee</th>
              <th class="d-none d-md-table-cell">Department</th>
              <th>Period</th>
              <th class="text-end">KPI</th>
              <th class="text-end d-none d-sm-table-cell">Rating</th>
              <th class="d-none d-md-table-cell">Manager</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($appraisals)): ?>
              <tr><td colspan="8" class="text-center py-5 text-muted">No appraisal data found for the selected filters.</td></tr>
            <?php else: ?>
              <?php foreach ($appraisals as $a): ?>
              <?php
                $emp_name = trim((isset($a->first_name) ? $a->first_name : '') . ' ' . (isset($a->last_name) ? $a->last_name : ''));
                if ($emp_name === '') { $emp_name = '—'; }
                $st = isset($a->status) ? $a->status : 'draft';
              ?>
              <tr>
                <td class="fw-semibold"><?php echo htmlspecialchars($emp_name); ?></td>
                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars(isset($a->department) ? $a->department : '—'); ?></td>
                <td><?php echo htmlspecialchars(isset($a->period) ? $a->period : '—'); ?></td>
                <td class="text-end"><?php echo isset($a->kpi_score) ? number_format((float) $a->kpi_score, 2) : '—'; ?></td>
                <td class="text-end d-none d-sm-table-cell"><?php echo isset($a->rating) && $a->rating !== null ? (int) $a->rating : '—'; ?></td>
                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars(isset($a->manager_name) ? $a->manager_name : '—'); ?></td>
                <td>
                  <span class="badge bg-<?php echo $st === 'approved' ? 'success' : ($st === 'submitted' ? 'info' : 'secondary'); ?>">
                    <?php echo ucfirst($st); ?>
                  </span>
                </td>
                <td class="text-end">
                  <a href="<?php echo site_url('performance/view/' . (int) $a->id); ?>" class="btn btn-sm btn-outline-primary" title="View">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
