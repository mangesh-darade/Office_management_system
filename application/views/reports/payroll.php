<?php $this->load->view('partials/header', ['title' => 'Payroll Report']); ?>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-cash-coin text-primary me-2"></i>Payroll Report</h4>
      <p class="text-muted mb-0 small">Monthly payroll summary and breakdown</p>
    </div>
    <div class="d-flex gap-2">
      <?php if(function_exists('has_module_access') && (has_module_access('payroll_manage') || has_module_access('reports_payroll') || has_module_access('reports') || is_admin_group())): ?>
      <a href="<?php echo site_url('reports/payroll?' . http_build_query(['month' => $month, 'department' => $department, 'export' => 'csv'])); ?>"
         class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i>Export CSV</a>
      <?php endif; ?>
      <a href="<?php echo site_url('payroll/payslips'); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Payroll
      </a>
    </div>
  </div>

  <!-- Filters -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2">
      <form method="get" action="<?php echo site_url('reports/payroll'); ?>" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label small fw-bold text-uppercase text-muted mb-1">Month</label>
          <input type="month" name="month" class="form-control form-control-sm" value="<?php echo htmlspecialchars($month); ?>">
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
        <div class="col-auto">
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
          <a href="<?php echo site_url('reports/payroll'); ?>" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-primary mb-0"><?php echo $summary['count']; ?></div>
        <div class="small text-muted">Employees</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-success mb-0"><?php echo number_format($summary['total_gross'], 2); ?></div>
        <div class="small text-muted">Total Gross</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-warning mb-0"><?php echo number_format($summary['total_deductions'], 2); ?></div>
        <div class="small text-muted">Total Deductions</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-info mb-0"><?php echo number_format($summary['total_net'], 2); ?></div>
        <div class="small text-muted">Total Net Pay</div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Employee</th>
              <th class="d-none d-md-table-cell">Department</th>
              <th class="d-none d-sm-table-cell">Pay Period</th>
              <th class="text-end">Gross</th>
              <th class="text-end d-none d-md-table-cell">Deductions</th>
              <th class="text-end">Net Pay</th>
              <th class="d-none d-md-table-cell">Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($payslips)): ?>
              <tr><td colspan="8" class="text-center py-5 text-muted">No payroll data found for this period.</td></tr>
            <?php else: ?>
              <?php foreach ($payslips as $p): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?php echo htmlspecialchars($p->employee_name ? $p->employee_name : '—'); ?></div>
                  <small class="text-muted"><?php echo htmlspecialchars($p->employee_email ? $p->employee_email : ''); ?></small>
                </td>
                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars(isset($p->department) ? $p->department : '—'); ?></td>
                <td class="d-none d-sm-table-cell"><?php echo htmlspecialchars(isset($p->pay_period) ? $p->pay_period : $month); ?></td>
                <td class="text-end"><?php echo number_format(isset($p->gross_salary) ? $p->gross_salary : 0, 2); ?></td>
                <td class="text-end d-none d-md-table-cell text-danger"><?php echo number_format(isset($p->total_deductions) ? $p->total_deductions : 0, 2); ?></td>
                <td class="text-end fw-bold text-success"><?php echo number_format(isset($p->net_salary) ? $p->net_salary : 0, 2); ?></td>
                <td class="d-none d-md-table-cell">
                  <?php $st = isset($p->status) ? $p->status : 'draft'; ?>
                  <span class="badge bg-<?php echo $st === 'paid' ? 'success' : ($st === 'approved' ? 'info' : 'secondary'); ?>">
                    <?php echo ucfirst($st); ?>
                  </span>
                </td>
                <td class="text-end">
                  <a href="<?php echo site_url('payroll/view/' . $p->id); ?>" class="btn btn-sm btn-outline-primary" title="View Payslip">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <?php if (!empty($payslips)): ?>
          <tfoot class="table-light fw-bold">
            <tr>
              <td colspan="3" class="d-none d-sm-table-cell">Total (<?php echo $summary['count']; ?> employees)</td>
              <td colspan="3" class="d-sm-none">Total</td>
              <td class="text-end"><?php echo number_format($summary['total_gross'], 2); ?></td>
              <td class="text-end d-none d-md-table-cell text-danger"><?php echo number_format($summary['total_deductions'], 2); ?></td>
              <td class="text-end text-success"><?php echo number_format($summary['total_net'], 2); ?></td>
              <td class="d-none d-md-table-cell"></td>
              <td></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
