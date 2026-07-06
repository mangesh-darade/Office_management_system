<?php $this->load->view('partials/header', ['title' => 'Employee Attendance']); ?>
<?php
$progressBase = (isset($total_working_days) && (int) $total_working_days > 0)
    ? (int) $total_working_days
    : 1;
$visibleCount = isset($rows) ? count($rows) : 0;
$scopeCount = isset($total_employees_in_scope) ? (int) $total_employees_in_scope : $visibleCount;
$employee_tab = (isset($employee_tab) && $employee_tab === 'inactive') ? 'inactive' : 'active';
$tabQuery = array();
if (isset($period)) {
    $tabQuery['period'] = $period;
}
if (!empty($month)) {
    $tabQuery['month'] = $month;
}
if (!empty($date)) {
    $tabQuery['date'] = $date;
}
$activeTabUrl = site_url('reports/attendance-employee') . '?' . http_build_query(array_merge($tabQuery, array('tab' => 'active')));
$inactiveTabUrl = site_url('reports/attendance-employee') . '?' . http_build_query(array_merge($tabQuery, array('tab' => 'inactive')));
$isInactiveTab = ($employee_tab === 'inactive');
?>

<style>
.att-emp-report {
  --att-emp-primary: #2563eb;
  --att-emp-success: #10b981;
  --att-emp-warning: #f59e0b;
  --att-emp-danger: #ef4444;
  --att-emp-info: #06b6d4;
  --att-emp-light-bg: #f8fafc;
  --att-emp-border: #e2e8f0;
  --att-emp-text: #1e293b;
  --att-emp-text-sec: #64748b;
  --att-emp-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --att-emp-radius-sm: 0.375rem;
  --att-emp-radius-md: 0.5rem;
}

.att-emp-report .stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.att-emp-report .stat-card {
  background: white;
  border-radius: var(--att-emp-radius-md);
  padding: 0.5rem;
  box-shadow: var(--att-emp-shadow);
  border: 1px solid var(--att-emp-border);
  transition: all 0.2s;
  position: relative;
  overflow: hidden;
}

.att-emp-report .stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--att-emp-primary);
}

.att-emp-report .stat-card.success::before { background: var(--att-emp-success); }
.att-emp-report .stat-card.warning::before { background: var(--att-emp-warning); }
.att-emp-report .stat-card.danger::before { background: var(--att-emp-danger); }
.att-emp-report .stat-card.info::before { background: var(--att-emp-info); }

.att-emp-report .stat-icon {
  width: 28px;
  height: 28px;
  border-radius: var(--att-emp-radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  margin-bottom: 0.5rem;
  background: var(--att-emp-light-bg);
}

.att-emp-report .stat-icon.primary { background: rgba(37, 99, 235, 0.1); color: var(--att-emp-primary); }
.att-emp-report .stat-icon.success { background: rgba(16, 185, 129, 0.1); color: var(--att-emp-success); }
.att-emp-report .stat-icon.warning { background: rgba(245, 158, 11, 0.1); color: var(--att-emp-warning); }
.att-emp-report .stat-icon.danger { background: rgba(239, 68, 68, 0.1); color: var(--att-emp-danger); }
.att-emp-report .stat-icon.info { background: rgba(6, 182, 212, 0.1); color: var(--att-emp-info); }

.att-emp-report .stat-value {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--att-emp-text);
  margin-bottom: 0.125rem;
}

.att-emp-report .stat-label {
  color: var(--att-emp-text-sec);
  font-size: 0.7rem;
  font-weight: 500;
}

.att-emp-report .filter-section {
  background: white;
  border-radius: var(--att-emp-radius-md);
  padding: 0.5rem;
  box-shadow: var(--att-emp-shadow);
  border: 1px solid var(--att-emp-border);
  margin-bottom: 0.5rem;
}

.att-emp-report .filter-form {
  display: flex;
  gap: 1rem;
  align-items: end;
  flex-wrap: wrap;
}

.att-emp-report .form-group {
  display: flex;
  flex-direction: column;
  min-width: 150px;
}

.att-emp-report .table-section {
  background: white;
  border-radius: var(--att-emp-radius-md);
  box-shadow: var(--att-emp-shadow);
  border: 1px solid var(--att-emp-border);
  overflow: hidden;
}

.att-emp-report .table-header {
  padding: 0.5rem;
  border-bottom: 1px solid var(--att-emp-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.att-emp-report .table-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--att-emp-text);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.att-emp-report .table-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.att-emp-report .search-box {
  position: relative;
}

.att-emp-report .search-input {
  padding: 0.375rem 0.75rem 0.375rem 2rem;
  border: 1px solid var(--att-emp-border);
  border-radius: var(--att-emp-radius-sm);
  font-size: 0.85rem;
  width: 200px;
}

.att-emp-report .search-icon {
  position: absolute;
  left: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--att-emp-text-sec);
  font-size: 0.85rem;
}

.att-emp-report .table-wrapper {
  overflow-x: auto;
}

.att-emp-report .data-table {
  width: 100%;
  border-collapse: collapse;
}

.att-emp-report .data-table th {
  background: var(--att-emp-light-bg);
  padding: 0.5rem 0.75rem;
  text-align: left;
  font-weight: 600;
  color: var(--att-emp-text);
  border-bottom: 1px solid var(--att-emp-border);
  white-space: nowrap;
  cursor: pointer;
  user-select: none;
  font-size: 0.8rem;
  height: 45px;
}

.att-emp-report .data-table th:hover {
  background: #f1f5f9;
}

.att-emp-report .sort-icon {
  margin-left: 0.25rem;
  opacity: 0.5;
  font-size: 0.7rem;
}

.att-emp-report .data-table td {
  padding: 0.375rem 0.5rem;
  border-bottom: 1px solid var(--att-emp-border);
  vertical-align: middle;
  font-size: 0.8rem;
  height: 40px;
}

.att-emp-report .data-table tr:hover {
  background: var(--att-emp-light-bg);
}

.att-emp-report .employee-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.att-emp-report .employee-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--att-emp-primary);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.7rem;
}

.att-emp-report .employee-info {
  flex: 1;
}

.att-emp-report .employee-name {
  font-weight: 600;
  color: var(--att-emp-text);
  margin-bottom: 0.125rem;
  font-size: 0.85rem;
}

.att-emp-report .employee-id {
  font-size: 0.7rem;
  color: var(--att-emp-text-sec);
}

.att-emp-report .status-cell {
  text-align: center;
  font-weight: 600;
  font-size: 0.8rem;
}

.att-emp-report .status-cell.present { color: var(--att-emp-success); }
.att-emp-report .status-cell.half { color: var(--att-emp-warning); }
.att-emp-report .status-cell.wfh { color: var(--att-emp-info); }
.att-emp-report .status-cell.absent { color: var(--att-emp-danger); }
.att-emp-report .status-cell.leave { color: var(--att-emp-text-sec); }

.att-emp-report .progress-bar {
  width: 40px;
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
  margin: 0.25rem auto 0;
}

.att-emp-report .progress-fill {
  height: 100%;
  background: var(--att-emp-success);
  transition: width 0.3s ease;
}

.att-emp-report .progress-fill.half { background: var(--att-emp-warning); }
.att-emp-report .progress-fill.wfh { background: var(--att-emp-info); }
.att-emp-report .progress-fill.absent { background: var(--att-emp-danger); }
.att-emp-report .progress-fill.leave { background: var(--att-emp-text-sec); }

.att-emp-report .pagination-section {
  padding: 0.5rem;
  border-top: 1px solid var(--att-emp-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.att-emp-report .pagination-info {
  color: var(--att-emp-text-sec);
  font-size: 0.8rem;
}

.att-emp-report .pagination-controls {
  display: flex;
  gap: 0.25rem;
  align-items: center;
}

.att-emp-report .pagination-btn {
  padding: 0.25rem 0.375rem;
  border: 1px solid var(--att-emp-border);
  background: white;
  color: var(--att-emp-text);
  border-radius: var(--att-emp-radius-sm);
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.75rem;
}

.att-emp-report .pagination-btn:hover:not(:disabled) {
  background: var(--att-emp-light-bg);
  border-color: var(--att-emp-primary);
}

.att-emp-report .pagination-btn.active {
  background: var(--att-emp-primary);
  color: white;
  border-color: var(--att-emp-primary);
}

.att-emp-report .pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.att-emp-report .att-emp-empty-state {
  text-align: center;
  padding: 1.5rem 0.75rem;
  color: var(--att-emp-text-sec);
}

.att-emp-report .att-emp-empty-icon {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
  opacity: 0.5;
}

.att-emp-report .att-emp-empty-title {
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: var(--att-emp-text);
}

.att-emp-report .att-emp-empty-desc {
  margin-bottom: 0.75rem;
  font-size: 0.8rem;
}

@media (max-width: 768px) {
  .att-emp-report .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .att-emp-report .filter-form {
    flex-direction: column;
    align-items: stretch;
  }
  
  .att-emp-report .table-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .att-emp-report .table-actions {
    justify-content: space-between;
  }
  
  .att-emp-report .search-input {
    width: 100%;
  }
  
  .att-emp-report .data-table th,
  .att-emp-report .data-table td {
    padding: 0.5rem 0.375rem;
    font-size: 0.8rem;
  }
  
  .att-emp-report .employee-avatar {
    width: 28px;
    height: 28px;
    font-size: 0.7rem;
  }
  
  .att-emp-report .export-actions-bar {
    gap: 0.4rem !important;
    padding: 0.5rem 0.75rem !important;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  
  .att-emp-report .export-actions-bar label {
    font-size: 0.8rem;
  }
  
  .att-emp-report .export-text-full {
    display: none !important;
  }
  
  .att-emp-report .export-text-short {
    display: inline !important;
  }
}
</style>

<div class="container-fluid py-3">
<div class="att-emp-report">
<div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
  <div>
    <h4 class="mb-1 fw-bold"><i class="bi bi-person-badge text-primary me-2"></i>Employee Attendance</h4>
    <p class="text-muted small mb-0">
      <?php if ($isInactiveTab): ?>
        Inactive employee attendance history for the selected period
      <?php else: ?>
        Active employee attendance records and analytics
      <?php endif; ?>
    </p>
  </div>
  <div class="d-flex gap-2 mt-2 mt-sm-0">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reports/attendance'); ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>
</div>

<ul class="nav nav-tabs mb-3" id="employee-attendance-tabs">
  <li class="nav-item">
    <a class="nav-link <?php echo !$isInactiveTab ? 'active' : ''; ?>" href="<?php echo $activeTabUrl; ?>">
      <i class="bi bi-person-check me-1"></i>Active Employees
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $isInactiveTab ? 'active' : ''; ?>" href="<?php echo $inactiveTabUrl; ?>">
      <i class="bi bi-person-x me-1"></i>Inactive Employees
    </a>
  </li>
</ul>

<!-- Statistics Cards -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">
      <i class="bi bi-people"></i>
    </div>
    <div class="stat-value"><?php echo $visibleCount; ?></div>
    <div class="stat-label"><?php echo $isInactiveTab ? 'Inactive Employees' : 'Employees With Data'; ?></div>
  </div>
  <?php if ($scopeCount > $visibleCount && !$isInactiveTab): ?>
  <div class="stat-card">
    <div class="stat-icon">
      <i class="bi bi-person-lines-fill"></i>
    </div>
    <div class="stat-value"><?php echo $scopeCount; ?></div>
    <div class="stat-label">Active In Scope</div>
  </div>
  <?php elseif ($isInactiveTab && $scopeCount > 0): ?>
  <div class="stat-card">
    <div class="stat-icon">
      <i class="bi bi-person-lines-fill"></i>
    </div>
    <div class="stat-value"><?php echo $scopeCount; ?></div>
    <div class="stat-label">Inactive In Scope</div>
  </div>
  <?php endif; ?>
  
  <?php 
    $totalPresent = 0;
    foreach ($rows as $r) { $totalPresent += (float)$r->present_days; }
    if ($totalPresent > 0):
  ?>
  <div class="stat-card success">
    <div class="stat-icon success">
      <i class="bi bi-check-circle"></i>
    </div>
    <div class="stat-value"><?php echo number_format($totalPresent, 1); ?></div>
    <div class="stat-label">Total Present</div>
  </div>
  <?php endif; ?>
  
  <?php 
    $totalAbsent = 0;
    foreach ($rows as $r) { $totalAbsent += (float)$r->absent_days; }
    if ($totalAbsent > 0):
  ?>
  <div class="stat-card danger">
    <div class="stat-icon danger">
      <i class="bi bi-x-circle"></i>
    </div>
    <div class="stat-value"><?php echo number_format($totalAbsent, 1); ?></div>
    <div class="stat-label">Total Absent</div>
  </div>
  <?php endif; ?>
  
  <?php 
    $totalWfh = 0;
    foreach ($rows as $r) { $totalWfh += (float)$r->wfh_days; }
    if ($totalWfh > 0):
  ?>
  <div class="stat-card info">
    <div class="stat-icon info">
      <i class="bi bi-house"></i>
    </div>
    <div class="stat-value"><?php echo number_format($totalWfh, 1); ?></div>
    <div class="stat-label">Total WFH</div>
  </div>
  <?php endif; ?>
  
  <?php 
    $totalHalf = 0;
    foreach ($rows as $r) { $totalHalf += (float) $r->half_days; }
    if ($totalHalf > 0):
  ?>
  <div class="stat-card warning">
    <div class="stat-icon warning">
      <i class="bi bi-pie-chart"></i>
    </div>
    <div class="stat-value"><?php echo number_format($totalHalf, 1); ?></div>
    <div class="stat-label">Total Half Days</div>
  </div>
  <?php endif; ?>
  
  <?php 
    $totalLeaveStat = 0;
    foreach ($rows as $r) { $totalLeaveStat += (float) $r->leave_days; }
    if ($totalLeaveStat > 0):
  ?>
  <div class="stat-card info">
    <div class="stat-icon info">
      <i class="bi bi-calendar-minus"></i>
    </div>
    <div class="stat-value"><?php echo number_format($totalLeaveStat, 1); ?></div>
    <div class="stat-label">Total Leave</div>
  </div>
  <?php endif; ?>
  
  <?php 
    $totalHolidayDays = 0;
    foreach ($rows as $r) { $totalHolidayDays += isset($r->holiday_days) ? (float) $r->holiday_days : 0; }
    if ($totalHolidayDays > 0):
  ?>
  <div class="stat-card" style="background: linear-gradient(135deg, #ede9fe 0%, #c4b5fd 100%);">
    <div class="stat-icon" style="background: rgba(139, 92, 246, 0.2); color: #7c3aed;">
      <i class="bi bi-calendar-heart"></i>
    </div>
    <div class="stat-value"><?php echo number_format($totalHolidayDays, 1); ?></div>
    <div class="stat-label">Holiday Days (Recorded)</div>
  </div>
  <?php endif; ?>
  
  <?php 
    $totalOnTime = 0;
    foreach ($rows as $r) { $totalOnTime += isset($r->on_time_days) ? (float)$r->on_time_days : 0; }
    if ($totalOnTime > 0):
  ?>
  <div class="stat-card success">
    <div class="stat-icon success">
      <i class="bi bi-check-circle-fill"></i>
    </div>
    <div class="stat-value"><?php echo number_format($totalOnTime, 1); ?></div>
    <div class="stat-label">Total On Time</div>
  </div>
  <?php endif; ?>
  
  <?php 
    $totalLate = 0;
    foreach ($rows as $r) { $totalLate += (float)$r->late_days; }
    if ($totalLate > 0):
  ?>
  <div class="stat-card" style="background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%);">
    <div class="stat-icon" style="background: rgba(251, 191, 36, 0.2); color: #d97706;">
      <i class="bi bi-exclamation-triangle"></i>
    </div>
    <div class="stat-value"><?php echo number_format($totalLate, 1); ?></div>
    <div class="stat-label">Total Late Days</div>
  </div>
  <?php endif; ?>
  
  <?php 
    $totalLateHours = 0;
    foreach ($rows as $r) { $totalLateHours += isset($r->late_hours_decimal) ? (float)$r->late_hours_decimal : 0; }
    if ($totalLateHours > 0):
  ?>
  <div class="stat-card" style="background: linear-gradient(135deg, #fee2e2 0%, #f87171 100%);">
    <div class="stat-icon" style="background: rgba(248, 113, 113, 0.2); color: #dc2626;">
      <i class="bi bi-clock-history"></i>
    </div>
    <div class="stat-value"><?php echo attendance_report_format_hours_hhmm($totalLateHours); ?></div>
    <div class="stat-label">Total Late</div>
  </div>
  <?php endif; ?>
  
  <?php 
    $totalExtraHours = 0;
    foreach ($rows as $r) { $totalExtraHours += isset($r->extra_hours_decimal) ? (float)$r->extra_hours_decimal : 0; }
    if ($totalExtraHours > 0):
  ?>
  <div class="stat-card" style="background: linear-gradient(135deg, #d1fae5 0%, #34d399 100%);">
    <div class="stat-icon" style="background: rgba(52, 211, 153, 0.2); color: #059669;">
      <i class="bi bi-hourglass-split"></i>
    </div>
    <div class="stat-value"><?php echo attendance_report_format_hours_hhmm($totalExtraHours); ?></div>
    <div class="stat-label">Total Work</div>
  </div>
  <?php endif; ?>

  <?php
    $totalNetHours = 0;
    foreach ($rows as $r) {
        $totalNetHours += isset($r->net_hours_decimal)
            ? (float) $r->net_hours_decimal
            : attendance_report_compute_net_hours(
                isset($r->extra_hours_decimal) ? (float) $r->extra_hours_decimal : 0,
                isset($r->late_hours_decimal) ? (float) $r->late_hours_decimal : 0
            );
    }
    $netStatClass = $totalNetHours < 0 ? 'danger' : ($totalNetHours > 0 ? 'success' : '');
    $netStatBg = $totalNetHours < 0
        ? 'background: linear-gradient(135deg, #fee2e2 0%, #f87171 100%);'
        : ($totalNetHours > 0
            ? 'background: linear-gradient(135deg, #d1fae5 0%, #34d399 100%);'
            : 'background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);');
    $netStatIconColor = $totalNetHours < 0 ? '#dc2626' : ($totalNetHours > 0 ? '#059669' : '#6b7280');
  ?>
  <div class="stat-card" style="<?php echo $netStatBg; ?>">
    <div class="stat-icon" style="background: rgba(0,0,0,0.08); color: <?php echo $netStatIconColor; ?>;">
      <i class="bi bi-<?php echo $totalNetHours < 0 ? 'dash-circle' : ($totalNetHours > 0 ? 'plus-circle' : 'circle'); ?>"></i>
    </div>
    <div class="stat-value" style="<?php echo $totalNetHours < 0 ? 'color:#dc2626;' : ($totalNetHours > 0 ? 'color:#059669;' : ''); ?>">
      <?php echo attendance_report_format_hours_hhmm_signed($totalNetHours); ?>
    </div>
    <div class="stat-label">Total (Work − Late)</div>
  </div>

  
  <div class="stat-card" style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);">
    <div class="stat-icon" style="background: rgba(99, 102, 241, 0.2); color: #4338ca;">
      <i class="bi bi-calendar-event"></i>
    </div>
    <div class="stat-value"><?php echo isset($holidays) ? count($holidays) : 0; ?></div>
    <div class="stat-label">Holidays in Month</div>
  </div>
</div>

<!-- Filter Section -->
<div class="filter-section">
  <form method="get" class="filter-form">
    <input type="hidden" name="tab" value="<?php echo esc_view($employee_tab); ?>">
    <div class="form-group">
      <label class="form-label">Period</label>
      <select name="period" class="form-control" id="period-select" onchange="updatePeriodFilters()">
        <option value="daily" <?php echo (isset($period) && $period === 'daily') ? 'selected' : ''; ?>>Daily</option>
        <option value="weekly" <?php echo (isset($period) && $period === 'weekly') ? 'selected' : ''; ?>>Weekly</option>
        <option value="monthly" <?php echo (!isset($period) || $period === 'monthly') ? 'selected' : ''; ?>>Monthly</option>
      </select>
    </div>
    
    <div class="form-group" id="date-filter-group" style="display: <?php echo (isset($period) && $period !== 'monthly') ? 'flex' : 'none'; ?>;">
      <label class="form-label" id="date-label"><?php echo (isset($period) && $period === 'daily') ? 'Date' : 'Week Start Date'; ?></label>
      <input type="date" name="date" value="<?php echo isset($date) ? esc_view($date) : date('Y-m-d'); ?>" class="form-control">
    </div>
    
    <div class="form-group" id="month-filter-group" style="display: <?php echo (!isset($period) || $period === 'monthly') ? 'flex' : 'none'; ?>;">
      <label class="form-label">Month</label>
      <input type="month" name="month" value="<?php echo isset($month) ? esc_view($month) : date('Y-m'); ?>" class="form-control">
    </div>
    
    <div class="form-group">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-funnel-fill"></i>
        Filter
      </button>
    </div>
  </form>
  
  <?php if (isset($from) && isset($to)): ?>
  <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--att-emp-border); font-size: 0.85rem; color: var(--att-emp-text-sec);">
    <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
      <div><strong>Period:</strong> <?php echo esc_view($from); ?> to <?php echo esc_view($to); ?></div>
      <?php if (isset($total_working_days)): ?>
        <div><strong>Working Days:</strong> <?php echo $total_working_days; ?></div>
      <?php endif; ?>
      <?php if (isset($office_start_time)): ?>
        <div><strong>Office Start:</strong> <?php echo esc_view($office_start_time); ?></div>
      <?php endif; ?>
      <?php if (isset($office_end_time)): ?>
        <div><strong>Office End:</strong> <?php echo esc_view($office_end_time); ?></div>
      <?php endif; ?>
      <?php if (isset($grace_minutes)): ?>
        <div><strong>Grace Period:</strong> <?php echo $grace_minutes; ?> minutes</div>
      <?php endif; ?>
      <?php if (isset($office_start_time) && isset($office_end_time) && isset($grace_minutes)): ?>
        <div><strong>On Time Rule:</strong> Check-in by <?php echo esc_view(date('H:i', strtotime($office_start_time) + ((int)$grace_minutes * 60))); ?> and check-out from <?php echo esc_view($office_end_time); ?></div>
      <?php endif; ?>
      <?php if (isset($standard_working_hours)): ?>
        <div><strong>Standard Hours:</strong> <?php echo $standard_working_hours; ?>h/day</div>
      <?php endif; ?>
      <div><strong>Total:</strong> Work − Late in HH:MM (negative = time deficit, shown in red)</div>
      <div><strong>Late Rule:</strong> Per-employee shift when assigned, otherwise global office settings above</div>
      <?php if (isset($holidays) && !empty($holidays)): ?>
        <div style="flex-basis: 100%; margin-top: 0.25rem;">
          <strong style="color: var(--att-emp-primary);">Holidays:</strong> 
          <?php 
            $hNames = [];
            foreach($holidays as $h) {
                $hNames[] = esc_view($h->name) . ' <span style="color: var(--att-emp-text-sec); font-size: 0.8rem;">(' . date('M j', strtotime($h->holiday_date)) . ')</span>';
            }
            echo implode(', ', $hNames);
          ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Table Section -->
<div class="table-section">
  <div class="table-header">
    <h3 class="table-title">
      <i class="bi bi-table"></i>
      Employee Summary
    </h3>
    <div class="table-actions">
      <div class="search-box">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search..." id="employee-search">
      </div>
      <button class="btn btn-outline-primary" onclick="resetSearch()">
        <i class="bi bi-arrow-clockwise"></i>
        Reset
      </button>
    </div>
  </div>
  
  <!-- Export Actions Bar -->
  <div class="export-actions-bar" style="background: white; padding: 0.75rem 1rem; border-radius: var(--att-emp-radius-md); box-shadow: var(--att-emp-shadow); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: nowrap; overflow-x: auto;">
    <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
      <input type="checkbox" id="select-all" onchange="toggleSelectAll()" style="width: 18px; height: 18px; cursor: pointer;">
      <label for="select-all" style="margin: 0; cursor: pointer; font-weight: 500; white-space: nowrap;">Select All</label>
    </div>
    <div style="flex: 1; min-width: 0.5rem;"></div>
    <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
      <button class="btn btn-success btn-sm" onclick="exportSelected('excel')" id="export-excel-btn" disabled style="white-space: nowrap; font-size: 0.8rem; padding: 0.4rem 0.6rem;">
        <i class="bi bi-file-earmark-excel"></i> <span class="export-text-full">Export Excel</span><span class="export-text-short" style="display: none;">Excel</span>
      </button>
      <button class="btn btn-danger btn-sm" onclick="exportSelected('pdf')" id="export-pdf-btn" disabled style="white-space: nowrap; font-size: 0.8rem; padding: 0.4rem 0.6rem;">
        <i class="bi bi-file-earmark-pdf"></i> <span class="export-text-full">Export PDF</span><span class="export-text-short" style="display: none;">PDF</span>
      </button>
    </div>
  </div>
  
  <div class="table-wrapper">
    <table class="data-table" id="employee-table">
      <thead>
        <tr>
          <th style="width: 40px;">
            <input type="checkbox" id="select-all-header" onchange="toggleSelectAll()" style="width: 18px; height: 18px; cursor: pointer;">
          </th>
          <th onclick="sortTable(1)">
            Employee <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(2)" class="text-center">
            Present <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(3)" class="text-center">
            Half Day <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(4)" class="text-center">
            WFH <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(5)" class="text-center">
            Absent <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(6)" class="text-center">
            On Time <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(7)" class="text-center">
            Late <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(8)" class="text-center">
            Leave <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(9)" class="text-center">
            Holiday <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(10)" class="text-center">
            Late <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(11)" class="text-center">
            Work <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(12)" class="text-center">
            Total <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody id="employee-tbody">
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="14">
              <div class="att-emp-empty-state">
                <div class="att-emp-empty-icon">
                  <i class="bi bi-calendar-x"></i>
                </div>
                <div class="att-emp-empty-title">No Data Found</div>
                <div class="att-emp-empty-desc">
                  <?php if ($isInactiveTab): ?>
                    No inactive employees found for the selected period.
                  <?php else: ?>
                    No attendance activity for active employees in the selected period.
                  <?php endif; ?>
                </div>
                <button class="btn btn-primary" onclick="clearMonthFilter()">
                  <i class="bi bi-funnel-fill"></i>
                  Clear Filter
                </button>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $index => $r): ?>
            <tr data-searchable="<?php echo strtolower(esc_view($r->name)); ?>" data-index="<?php echo $index; ?>" data-user-id="<?php echo $r->user_id; ?>"
                data-name="<?php echo esc_view($r->name); ?>"
                data-present="<?php echo (float) $r->present_days; ?>"
                data-half="<?php echo (float) $r->half_days; ?>"
                data-wfh="<?php echo (float) $r->wfh_days; ?>"
                data-absent="<?php echo (float) $r->absent_days; ?>"
                data-on-time="<?php echo isset($r->on_time_days) ? (float) $r->on_time_days : 0; ?>"
                data-late="<?php echo (float) $r->late_days; ?>"
                data-leave="<?php echo (float) $r->leave_days; ?>"
                data-holiday="<?php echo isset($r->holiday_days) ? (float) $r->holiday_days : 0; ?>"
                data-late-hours="<?php echo isset($r->late_hours_decimal) ? (float) $r->late_hours_decimal : 0; ?>"
                data-extra-hours="<?php echo isset($r->extra_hours_decimal) ? (float) $r->extra_hours_decimal : 0; ?>"
                data-net-hours="<?php echo isset($r->net_hours_decimal) ? (float) $r->net_hours_decimal : attendance_report_compute_net_hours(isset($r->extra_hours_decimal) ? (float) $r->extra_hours_decimal : 0, isset($r->late_hours_decimal) ? (float) $r->late_hours_decimal : 0); ?>"
                data-present-display="<?php echo esc_view($r->present_days); ?>"
                data-half-display="<?php echo esc_view($r->half_days); ?>"
                data-wfh-display="<?php echo esc_view($r->wfh_days); ?>"
                data-absent-display="<?php echo esc_view($r->absent_days); ?>"
                data-on-time-display="<?php echo isset($r->on_time_days) ? esc_view($r->on_time_days) : '0'; ?>"
                data-late-display="<?php echo esc_view($r->late_days); ?>"
                data-leave-display="<?php echo esc_view($r->leave_days); ?>"
                data-holiday-display="<?php echo isset($r->holiday_days) ? esc_view($r->holiday_days) : '0'; ?>"
                data-late-hours-display="<?php echo isset($r->late_hours) ? esc_view($r->late_hours) : '00:00'; ?>"
                data-extra-hours-display="<?php echo isset($r->extra_hours) ? esc_view($r->extra_hours) : '00:00'; ?>"
                data-net-hours-display="<?php echo isset($r->net_hours) ? esc_view($r->net_hours) : attendance_report_format_hours_hhmm_signed(0); ?>">
              <td style="text-align: center;">
                <input type="checkbox" class="employee-checkbox" value="<?php echo $r->user_id; ?>" onchange="updateExportButtons()" style="width: 18px; height: 18px; cursor: pointer;">
              </td>
              <td>
                <div class="employee-cell">
                  <div class="employee-avatar">
                    <?php echo strtoupper(substr(esc_view($r->name), 0, 1)); ?>
                  </div>
                  <div class="employee-info">
                    <div class="employee-name"><?php echo esc_view($r->name); ?></div>
                    <div class="employee-id">ID: <?php echo $r->user_id; ?></div>
                  </div>
                </div>
              </td>
              <td class="status-cell present">
                <div><?php echo esc_view($r->present_days); ?></div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, ((float) $r->present_days / $progressBase) * 100); ?>%"></div>
                </div>
              </td>
              <td class="status-cell" style="color: #d97706;">
                <div><?php echo esc_view($r->half_days); ?></div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, ((float) $r->half_days / $progressBase) * 100); ?>%; background: #d97706;"></div>
                </div>
              </td>
              <td class="status-cell wfh">
                <div><?php echo esc_view($r->wfh_days); ?></div>
                <div class="progress-bar">
                  <div class="progress-fill wfh" style="width: <?php echo min(100, ((float) $r->wfh_days / $progressBase) * 100); ?>%"></div>
                </div>
              </td>
              <td class="status-cell absent">
                <div><?php echo esc_view($r->absent_days); ?></div>
                <div class="progress-bar">
                  <div class="progress-fill absent" style="width: <?php echo min(100, ((float) $r->absent_days / $progressBase) * 100); ?>%"></div>
                </div>
              </td>
              <td class="status-cell" style="color: var(--att-emp-success);">
                <div><?php echo isset($r->on_time_days) ? esc_view($r->on_time_days) : '0'; ?></div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, (isset($r->on_time_days) ? (float) $r->on_time_days : 0) / $progressBase * 100); ?>%; background: var(--att-emp-success);"></div>
                </div>
              </td>
              <td class="status-cell" style="color: #d97706;">
                <div><?php echo esc_view($r->late_days); ?></div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, ((float) $r->late_days / $progressBase) * 100); ?>%; background: #d97706;"></div>
                </div>
              </td>
              <td class="status-cell leave">
                <div><?php echo esc_view($r->leave_days); ?></div>
                <div class="progress-bar">
                  <div class="progress-fill leave" style="width: <?php echo min(100, ((float) $r->leave_days / $progressBase) * 100); ?>%"></div>
                </div>
              </td>
              <td class="status-cell" style="color: #7c3aed;">
                <div><?php echo isset($r->holiday_days) ? esc_view($r->holiday_days) : '0'; ?></div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, (isset($r->holiday_days) ? (float) $r->holiday_days : 0) / $progressBase * 100); ?>%; background: #7c3aed;"></div>
                </div>
              </td>
              <td class="status-cell" style="color: #d97706;">
                <div><?php echo isset($r->late_hours) ? esc_view($r->late_hours) : '00:00'; ?></div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, (isset($r->late_hours_decimal) ? (float) $r->late_hours_decimal : 0) * 10); ?>%; background: #d97706;"></div>
                </div>
              </td>
              <td class="status-cell" style="color: var(--att-emp-success);">
                <div><?php echo isset($r->extra_hours) ? esc_view($r->extra_hours) : '00:00'; ?></div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, (isset($r->extra_hours_decimal) ? (float) $r->extra_hours_decimal : 0) * 5); ?>%; background: var(--att-emp-success);"></div>
                </div>
              </td>
              <?php
                $netDecimal = isset($r->net_hours_decimal)
                    ? (float) $r->net_hours_decimal
                    : attendance_report_compute_net_hours(
                        isset($r->extra_hours_decimal) ? (float) $r->extra_hours_decimal : 0,
                        isset($r->late_hours_decimal) ? (float) $r->late_hours_decimal : 0
                    );
                $netColor = $netDecimal < 0 ? '#dc2626' : ($netDecimal > 0 ? '#059669' : 'var(--att-emp-text-sec)');
                $netDisplay = isset($r->net_hours) ? $r->net_hours : attendance_report_format_hours_hhmm_signed($netDecimal);
              ?>
              <td class="status-cell" style="color: <?php echo $netColor; ?>; font-weight: <?php echo $netDecimal !== 0.0 ? '600' : '400'; ?>;">
                <div><?php echo esc_view($netDisplay); ?></div>
                <?php if ($netDecimal < 0): ?>
                <div class="small" style="color:#dc2626;">time deficit</div>
                <?php elseif ($netDecimal > 0): ?>
                <div class="small" style="color:#059669;">surplus</div>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div style="display: flex; gap: 0.25rem; justify-content: flex-end;">
                  <?php 
                    $viewParams = array('tab' => $employee_tab);
                    if (isset($period)) $viewParams['period'] = $period;
                    if (isset($month)) $viewParams['month'] = $month;
                    if (isset($date)) $viewParams['date'] = $date;
                    $viewUrl = site_url('reports/attendance-employee/'.$r->user_id.'?'.http_build_query($viewParams));
                  ?>
                  <a class="btn btn-outline-primary btn-sm" href="<?php echo $viewUrl; ?>" title="View Details">
                    <i class="bi bi-eye"></i>
                  </a>
                  <button class="btn btn-outline-success btn-sm" onclick="exportSingle(<?php echo $r->user_id; ?>, 'excel')" title="Export Excel">
                    <i class="bi bi-file-earmark-excel"></i>
                  </button>
                  <button class="btn btn-outline-danger btn-sm" onclick="exportSingle(<?php echo $r->user_id; ?>, 'pdf')" title="Export PDF">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <?php if (!empty($rows)): ?>
  <div class="pagination-section">
    <div class="pagination-info">
      Showing <strong id="showing-count"><?php echo $visibleCount; ?></strong> of <strong id="total-count"><?php echo $visibleCount; ?></strong>
    </div>
    <div class="pagination-controls" id="pagination-controls">
    </div>
  </div>
  <?php endif; ?>
</div>

</div><!-- .att-emp-report -->
</div><!-- .container-fluid -->

<script>
const ITEMS_PER_PAGE = 15;
let currentPage = 1;
let allRows = [];
let filteredRows = [];
let sortColumn = 7;
let sortDirection = 'desc';

function toggleSelectAll() {
  const selectAll = document.getElementById('select-all');
  const selectAllHeader = document.getElementById('select-all-header');
  const checkboxes = document.querySelectorAll('.employee-checkbox');
  const isChecked = selectAll.checked;
  
  checkboxes.forEach(cb => {
    cb.checked = isChecked;
  });
  
  if (selectAllHeader) {
    selectAllHeader.checked = isChecked;
  }
  
  updateExportButtons();
}

function updateExportButtons() {
  const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
  const exportExcelBtn = document.getElementById('export-excel-btn');
  const exportPdfBtn = document.getElementById('export-pdf-btn');
  const hasSelection = checkboxes.length > 0;
  
  if (exportExcelBtn) exportExcelBtn.disabled = !hasSelection;
  if (exportPdfBtn) exportPdfBtn.disabled = !hasSelection;
  
  const allCheckboxes = document.querySelectorAll('.employee-checkbox');
  const selectAll = document.getElementById('select-all');
  const selectAllHeader = document.getElementById('select-all-header');
  
  if (selectAll) {
    selectAll.checked = allCheckboxes.length > 0 && checkboxes.length === allCheckboxes.length;
  }
  if (selectAllHeader) {
    selectAllHeader.checked = allCheckboxes.length > 0 && checkboxes.length === allCheckboxes.length;
  }
}

function exportSelected(format) {
  const userIds = getOrderedExportUserIds();
  if (userIds.length === 0) {
    alert('Please select at least one employee to export.');
    return;
  }
  exportAttendance(userIds, format);
}

function exportSingle(userId, format) {
  exportAttendance([String(userId)], format);
}

function getOrderedExportUserIds() {
  const checked = new Set(
    Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(function(cb) {
      return cb.value;
    })
  );
  if (checked.size === 0) {
    return [];
  }
  return filteredRows
    .map(function(row) { return row.getAttribute('data-user-id'); })
    .filter(function(id) { return id && checked.has(id); });
}

function getGridExportRows(userIds) {
  const idSet = new Set(userIds.map(String));

  return filteredRows
    .filter(function(row) {
      return idSet.has(row.getAttribute('data-user-id'));
    })
    .map(function(row) {
      return {
        user_id: row.getAttribute('data-user-id'),
        name: row.getAttribute('data-name') || '',
        present: row.getAttribute('data-present-display') || '0',
        half_day: row.getAttribute('data-half-display') || '0',
        wfh: row.getAttribute('data-wfh-display') || '0',
        absent: row.getAttribute('data-absent-display') || '0',
        on_time: row.getAttribute('data-on-time-display') || '0',
        late: row.getAttribute('data-late-display') || '0',
        leave: row.getAttribute('data-leave-display') || '0',
        holiday: row.getAttribute('data-holiday-display') || '0',
        late_hours: row.getAttribute('data-late-hours-display') || '00:00',
        extra_hours: row.getAttribute('data-extra-hours-display') || '00:00',
        net_hours: row.getAttribute('data-net-hours-display') || '00:00',
        late_hours_decimal: row.getAttribute('data-late-hours') || '0',
        extra_hours_decimal: row.getAttribute('data-extra-hours') || '0',
        net_hours_decimal: row.getAttribute('data-net-hours') || '0'
      };
    });
}

function exportAttendance(userIds, format) {
  if (!userIds || userIds.length === 0) {
    alert('Please select at least one employee to export.');
    return;
  }

  const gridRows = getGridExportRows(userIds);
  if (!gridRows.length) {
    alert('No grid rows found for export.');
    return;
  }

  const period = getUrlParameter('period') || 'monthly';
  const month = getUrlParameter('month') || '';
  const date = getUrlParameter('date') || '';

  const btn = (typeof event !== 'undefined' && event && event.target)
    ? event.target.closest('button')
    : null;
  let originalText = '';
  if (btn) {
    originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Exporting...';
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '<?php echo site_url("reports/export-attendance-employee"); ?>';
  form.style.display = 'none';

  const fields = {
    export: format,
    period: period,
    grid_rows: JSON.stringify(gridRows),
    sort_column: String(sortColumn),
    sort_direction: sortDirection,
    office_start: '<?php echo isset($office_start_time) ? esc_view($office_start_time) : ''; ?>',
    office_end: '<?php echo isset($office_end_time) ? esc_view($office_end_time) : ''; ?>',
    grace_minutes: '<?php echo isset($grace_minutes) ? (int) $grace_minutes : ''; ?>',
    standard_hours: '<?php echo isset($standard_working_hours) ? esc_view($standard_working_hours) : ''; ?>'
  };
  if (month) {
    fields.month = month;
  }
  if (date) {
    fields.date = date;
  }
  if (typeof window.csrfTokenName !== 'undefined' && typeof window.getCsrfToken === 'function') {
    fields[window.csrfTokenName] = window.getCsrfToken();
  }

  Object.keys(fields).forEach(function(key) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = key;
    input.value = fields[key];
    form.appendChild(input);
  });

  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);

  if (btn) {
    setTimeout(function() {
      btn.disabled = false;
      btn.innerHTML = originalText;
    }, 3000);
  }
}

function getUrlParameter(name) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(name);
}

document.addEventListener('DOMContentLoaded', function() {
  allRows = Array.from(document.querySelectorAll('#employee-tbody tr[data-index]'));
  filteredRows = allRows.slice();
  if (filteredRows.length > 0) {
    applyTableSort();
  } else {
    initializePagination();
    updateDisplay();
  }
});

document.getElementById('employee-search').addEventListener('input', function() {
  const searchTerm = this.value.toLowerCase();
  
  if (searchTerm === '') {
    filteredRows = allRows.slice();
  } else {
    filteredRows = allRows.filter(function(row) {
      const searchableText = row.getAttribute('data-searchable');
      return searchableText && searchableText.includes(searchTerm);
    });
  }
  
  currentPage = 1;
  if (filteredRows.length > 0 && sortColumn > 0) {
    applyTableSort();
  } else {
    updateDisplay();
  }
});

function sortTable(columnIndex) {
  if (columnIndex === 0) {
    return;
  }
  
  if (sortColumn === columnIndex) {
    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
  } else {
    sortColumn = columnIndex;
    sortDirection = 'asc';
  }
  
  applyTableSort();
}

function applyTableSort() {
  if (sortColumn <= 0 || filteredRows.length === 0) {
    updateSortIcons(sortColumn);
    updateDisplay();
    return;
  }

  const columnIndex = sortColumn;
  const dir = sortDirection === 'asc' ? 1 : -1;
  
  filteredRows.sort(function(a, b) {
    let aValue;
    let bValue;
    
    switch (columnIndex) {
      case 1:
        aValue = a.querySelector('.employee-name').textContent.trim().toLowerCase();
        bValue = b.querySelector('.employee-name').textContent.trim().toLowerCase();
        break;
      case 2:
        aValue = parseFloat(a.getAttribute('data-present')) || 0;
        bValue = parseFloat(b.getAttribute('data-present')) || 0;
        break;
      case 3:
        aValue = parseFloat(a.getAttribute('data-half')) || 0;
        bValue = parseFloat(b.getAttribute('data-half')) || 0;
        break;
      case 4:
        aValue = parseFloat(a.getAttribute('data-wfh')) || 0;
        bValue = parseFloat(b.getAttribute('data-wfh')) || 0;
        break;
      case 5:
        aValue = parseFloat(a.getAttribute('data-absent')) || 0;
        bValue = parseFloat(b.getAttribute('data-absent')) || 0;
        break;
      case 6:
        aValue = parseFloat(a.getAttribute('data-on-time')) || 0;
        bValue = parseFloat(b.getAttribute('data-on-time')) || 0;
        break;
      case 7:
        aValue = parseFloat(a.getAttribute('data-late')) || 0;
        bValue = parseFloat(b.getAttribute('data-late')) || 0;
        break;
      case 8:
        aValue = parseFloat(a.getAttribute('data-leave')) || 0;
        bValue = parseFloat(b.getAttribute('data-leave')) || 0;
        break;
      case 9:
        aValue = parseFloat(a.getAttribute('data-holiday')) || 0;
        bValue = parseFloat(b.getAttribute('data-holiday')) || 0;
        break;
      case 10:
        aValue = parseFloat(a.getAttribute('data-late-hours')) || 0;
        bValue = parseFloat(b.getAttribute('data-late-hours')) || 0;
        break;
      case 11:
        aValue = parseFloat(a.getAttribute('data-extra-hours')) || 0;
        bValue = parseFloat(b.getAttribute('data-extra-hours')) || 0;
        break;
      case 12:
        aValue = parseFloat(a.getAttribute('data-net-hours')) || 0;
        bValue = parseFloat(b.getAttribute('data-net-hours')) || 0;
        break;
      default:
        return 0;
    }
    
    if (aValue !== bValue) {
      if (typeof aValue === 'string') {
        if (aValue > bValue) return dir;
        if (aValue < bValue) return -dir;
      } else {
        if (aValue > bValue) return dir;
        if (aValue < bValue) return -dir;
      }
    }

    const aName = a.querySelector('.employee-name').textContent.trim().toLowerCase();
    const bName = b.querySelector('.employee-name').textContent.trim().toLowerCase();
    if (aName > bName) return 1;
    if (aName < bName) return -1;
    return 0;
  });
  
  updateSortIcons(columnIndex);
  currentPage = 1;
  updateDisplay();
}

function updateSortIcons(activeColumn) {
  const headers = document.querySelectorAll('.data-table th[onclick]');
  headers.forEach(function(header) {
    const icon = header.querySelector('.sort-icon');
    if (!icon) {
      return;
    }
    const match = header.getAttribute('onclick').match(/sortTable\((\d+)\)/);
    const col = match ? parseInt(match[1], 10) : -1;
    if (col === activeColumn) {
      icon.className = sortDirection === 'asc' ? 'bi bi-arrow-up sort-icon' : 'bi bi-arrow-down sort-icon';
    } else {
      icon.className = 'bi bi-arrow-down-up sort-icon';
    }
  });
}

function syncTableRowOrder() {
  const tbody = document.getElementById('employee-tbody');
  if (!tbody) {
    return;
  }

  filteredRows.forEach(function(row) {
    tbody.appendChild(row);
  });

  allRows.forEach(function(row) {
    if (filteredRows.indexOf(row) === -1) {
      tbody.appendChild(row);
    }
  });
}

function initializePagination() {
  updatePaginationControls();
}

function updatePaginationControls() {
  const totalPages = Math.ceil(filteredRows.length / ITEMS_PER_PAGE);
  const controlsContainer = document.getElementById('pagination-controls');
  
  let paginationHTML = '';
  
  paginationHTML += `<button class="pagination-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
    <i class="bi bi-chevron-left"></i>
  </button>`;
  
  const startPage = Math.max(1, currentPage - 2);
  const endPage = Math.min(totalPages, startPage + 4);
  
  if (startPage > 1) {
    paginationHTML += `<button class="pagination-btn" onclick="goToPage(1)">1</button>`;
    if (startPage > 2) {
      paginationHTML += `<span class="pagination-btn" disabled>...</span>`;
    }
  }
  
  for (let i = startPage; i <= endPage; i++) {
    paginationHTML += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
  }
  
  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      paginationHTML += `<span class="pagination-btn" disabled>...</span>`;
    }
    paginationHTML += `<button class="pagination-btn" onclick="goToPage(${totalPages})">${totalPages}</button>`;
  }
  
  paginationHTML += `<button class="pagination-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
    <i class="bi bi-chevron-right"></i>
  </button>`;
  
  controlsContainer.innerHTML = paginationHTML;
}

function goToPage(page) {
  const totalPages = Math.ceil(filteredRows.length / ITEMS_PER_PAGE);
  if (page < 1 || page > totalPages) return;
  
  currentPage = page;
  updateDisplay();
}

function updateDisplay() {
  const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
  const endIndex = startIndex + ITEMS_PER_PAGE;

  syncTableRowOrder();

  allRows.forEach(function(row) { row.style.display = 'none'; });
  
  for (let i = startIndex; i < endIndex && i < filteredRows.length; i++) {
    filteredRows[i].style.display = '';
  }
  
  const visibleOnPage = Math.min(endIndex, filteredRows.length) - startIndex;
  const showingEl = document.getElementById('showing-count');
  const totalEl = document.getElementById('total-count');
  if (showingEl) {
    showingEl.textContent = Math.max(0, visibleOnPage);
  }
  if (totalEl) {
    totalEl.textContent = filteredRows.length;
  }
  
  updatePaginationControls();
}

function resetSearch() {
  document.getElementById('employee-search').value = '';
  document.getElementById('employee-search').dispatchEvent(new Event('input'));
}

function clearMonthFilter() {
  const tab = getUrlParameter('tab') || 'active';
  window.location.href = '<?php echo site_url('reports/attendance-employee'); ?>?tab=' + encodeURIComponent(tab);
}

function updatePeriodFilters() {
  const periodSelect = document.getElementById('period-select');
  const period = periodSelect.value;
  const dateGroup = document.getElementById('date-filter-group');
  const monthGroup = document.getElementById('month-filter-group');
  const dateLabel = document.getElementById('date-label');
  
  if (period === 'monthly') {
    dateGroup.style.display = 'none';
    monthGroup.style.display = 'flex';
  } else {
    dateGroup.style.display = 'flex';
    monthGroup.style.display = 'none';
    if (period === 'daily') {
      dateLabel.textContent = 'Date';
    } else {
      dateLabel.textContent = 'Week Start Date';
    }
  }
}
</script>

<?php $this->load->view('partials/footer'); ?>
