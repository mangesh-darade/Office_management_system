<?php $this->load->view('partials/header', ['title' => 'Expenses Report']); ?>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-receipt text-primary me-2"></i>Expenses Report</h4>
      <p class="text-muted mb-0 small">Expense claims summary and breakdown by category</p>
    </div>
    <div class="d-flex gap-2">
      <?php if(function_exists('has_module_access') && (has_module_access('expenses_export') || has_module_access('expenses_reports') || has_module_access('reports_expenses') || has_module_access('reports') || is_admin_group())): ?>
      <a href="<?php echo site_url('reports/expenses?' . http_build_query(['date_from' => $date_from, 'date_to' => $date_to, 'status' => $status, 'category' => $category, 'export' => 'csv'])); ?>"
         class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i>Export CSV</a>
      <?php endif; ?>
      <a href="<?php echo site_url('expenses'); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Expenses
      </a>
    </div>
  </div>

  <!-- Filters -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2">
      <form method="get" action="<?php echo site_url('reports/expenses'); ?>" class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
          <label class="form-label small fw-bold text-uppercase text-muted mb-1">From</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo esc_view($date_from); ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-bold text-uppercase text-muted mb-1">To</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo esc_view($date_to); ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-bold text-uppercase text-muted mb-1">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">— All —</option>
            <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'reimbursed' => 'Reimbursed'] as $val => $lbl): ?>
              <option value="<?php echo $val; ?>" <?php echo ($status === $val) ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-bold text-uppercase text-muted mb-1">Category</label>
          <select name="category" class="form-select form-select-sm">
            <option value="">— All —</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?php echo $c->id; ?>" <?php echo ($category == $c->id) ? 'selected' : ''; ?>>
                <?php echo esc_view($c->name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
          <a href="<?php echo site_url('reports/expenses'); ?>" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-primary mb-0"><?php echo $summary['count']; ?></div>
        <div class="small text-muted">Claims</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-dark mb-0"><?php echo number_format($summary['total'], 2); ?></div>
        <div class="small text-muted">Total Amount</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-success mb-0"><?php echo number_format($summary['approved'], 2); ?></div>
        <div class="small text-muted">Approved</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="h4 fw-bold text-warning mb-0"><?php echo number_format($summary['pending'], 2); ?></div>
        <div class="small text-muted">Pending</div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <!-- By Category Breakdown -->
    <?php if (!empty($by_category)): ?>
    <div class="col-12 col-md-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-transparent fw-semibold">By Category</div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            <?php arsort($by_category); ?>
            <?php foreach ($by_category as $cat => $amt): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
              <span class="small"><?php echo esc_view($cat); ?></span>
              <span class="fw-semibold"><?php echo number_format($amt, 2); ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Expenses Table -->
    <div class="col-12 <?php echo !empty($by_category) ? 'col-md-8' : ''; ?>">
      <div class="card shadow-sm border-0">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>Employee</th>
                  <th class="d-none d-md-table-cell">Category</th>
                  <th class="d-none d-sm-table-cell">Description</th>
                  <th class="text-end">Amount</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($expenses)): ?>
                  <tr><td colspan="7" class="text-center py-5 text-muted">No expenses found for this period.</td></tr>
                <?php else: ?>
                  <?php foreach ($expenses as $e): ?>
                  <?php
                    $badge_map = ['pending' => 'warning text-dark', 'approved' => 'success', 'rejected' => 'danger', 'reimbursed' => 'info'];
                    $st = isset($e->status) ? $e->status : 'pending';
                    $badge = isset($badge_map[$st]) ? $badge_map[$st] : 'secondary';
                  ?>
                  <tr>
                    <td style="white-space:nowrap;"><?php echo isset($e->expense_date) ? date('d M Y', strtotime($e->expense_date)) : '—'; ?></td>
                    <td>
                      <div class="fw-semibold small"><?php echo esc_view($e->employee_name ? $e->employee_name : '—'); ?></div>
                    </td>
                    <td class="d-none d-md-table-cell small"><?php echo esc_view($e->category_name ? $e->category_name : '—'); ?></td>
                    <td class="d-none d-sm-table-cell small text-muted"><?php echo esc_view(isset($e->description) ? substr($e->description, 0, 40) : ''); ?></td>
                    <td class="text-end fw-semibold"><?php echo number_format(isset($e->amount) ? $e->amount : 0, 2); ?></td>
                    <td><span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($st); ?></span></td>
                    <td class="text-end">
                      <a href="<?php echo site_url('expenses/view/' . $e->id); ?>" class="btn btn-sm btn-outline-primary" title="View">
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
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
