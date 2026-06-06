<?php $this->load->view('partials/header', ['title' => 'Employee Attendance']); ?>

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
    <p class="text-muted small mb-0">Individual employee attendance records and analytics</p>
  </div>
  <div class="d-flex gap-2 mt-2 mt-sm-0">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reports/attendance'); ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">
      <i class="bi bi-people"></i>
    </div>
    <div class="stat-value"><?php echo count($rows); ?></div>
    <div class="stat-label">Total Employees</div>
  </div>
  
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
    <div class="stat-label">Total Late Hours</div>
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
    <div class="stat-label">Total Extra Hours</div>
  </div>
  <?php endif; ?>

  
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
      <input type="date" name="date" value="<?php echo isset($date) ? htmlspecialchars($date) : date('Y-m-d'); ?>" class="form-control">
    </div>
    
    <div class="form-group" id="month-filter-group" style="display: <?php echo (!isset($period) || $period === 'monthly') ? 'flex' : 'none'; ?>;">
      <label class="form-label">Month</label>
      <input type="month" name="month" value="<?php echo isset($month) ? htmlspecialchars($month) : date('Y-m'); ?>" class="form-control">
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
      <div><strong>Period:</strong> <?php echo htmlspecialchars($from); ?> to <?php echo htmlspecialchars($to); ?></div>
      <?php if (isset($total_working_days)): ?>
        <div><strong>Working Days:</strong> <?php echo $total_working_days; ?></div>
      <?php endif; ?>
      <?php if (isset($office_start_time)): ?>
        <div><strong>Office Start:</strong> <?php echo htmlspecialchars($office_start_time); ?></div>
      <?php endif; ?>
      <?php if (isset($office_end_time)): ?>
        <div><strong>Office End:</strong> <?php echo htmlspecialchars($office_end_time); ?></div>
      <?php endif; ?>
      <?php if (isset($grace_minutes)): ?>
        <div><strong>Grace Period:</strong> <?php echo $grace_minutes; ?> minutes</div>
      <?php endif; ?>
      <?php if (isset($office_start_time) && isset($office_end_time) && isset($grace_minutes)): ?>
        <div><strong>On Time Rule:</strong> Check-in by <?php echo htmlspecialchars(date('H:i', strtotime($office_start_time) + ((int)$grace_minutes * 60))); ?> and check-out from <?php echo htmlspecialchars($office_end_time); ?></div>
      <?php endif; ?>
      <?php if (isset($standard_working_hours)): ?>
        <div><strong>Standard Hours:</strong> <?php echo $standard_working_hours; ?>h/day</div>
      <?php endif; ?>
      <?php if (isset($holidays) && !empty($holidays)): ?>
        <div style="flex-basis: 100%; margin-top: 0.25rem;">
          <strong style="color: var(--att-emp-primary);">Holidays:</strong> 
          <?php 
            $hNames = [];
            foreach($holidays as $h) {
                $hNames[] = htmlspecialchars($h->name) . ' <span style="color: var(--att-emp-text-sec); font-size: 0.8rem;">(' . date('M j', strtotime($h->holiday_date)) . ')</span>';
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
          <th onclick="sortTable(1)" class="text-center">
            Present <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(2)" class="text-center">
            WFH <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(3)" class="text-center">
            Absent <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(4)" class="text-center">
            On Time <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(5)" class="text-center">
            Late <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(6)" class="text-center">
            Leave <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(7)" class="text-center">
            Late Hours <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable(8)" class="text-center">
            Extra Hours <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody id="employee-tbody">
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="12">
              <div class="att-emp-empty-state">
                <div class="att-emp-empty-icon">
                  <i class="bi bi-calendar-x"></i>
                </div>
                <div class="att-emp-empty-title">No Data Found</div>
                <div class="att-emp-empty-desc">
                  No attendance data for selected month.
                </div>
                <button class="btn btn-primary" onclick="clearMonthFilter()">
                  <i class="bi bi-funnel-fill"></i>
                  Clear Filter
                </button>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $index => $r): 
            $hasData = (float)$r->present_days > 0 || 
                       (float)$r->absent_days > 0 || 
                       (float)$r->wfh_days > 0 || 
                       (isset($r->on_time_days) && (float)$r->on_time_days > 0) || 
                       (float)$r->late_days > 0 || 
                       (float)$r->leave_days > 0 || 
                       (isset($r->late_hours_decimal) && (float)$r->late_hours_decimal > 0) || 
                       (isset($r->extra_hours_decimal) && (float)$r->extra_hours_decimal > 0);
            if (!$hasData) continue;
          ?>
            <tr data-searchable="<?php echo strtolower(htmlspecialchars($r->name)); ?>" data-index="<?php echo $index; ?>" data-user-id="<?php echo $r->user_id; ?>">
              <td style="text-align: center;">
                <input type="checkbox" class="employee-checkbox" value="<?php echo $r->user_id; ?>" onchange="updateExportButtons()" style="width: 18px; height: 18px; cursor: pointer;">
              </td>
              <td>
                <div class="employee-cell">
                  <div class="employee-avatar">
                    <?php echo strtoupper(substr(htmlspecialchars($r->name), 0, 1)); ?>
                  </div>
                  <div class="employee-info">
                    <div class="employee-name"><?php echo htmlspecialchars($r->name); ?></div>
                    <div class="employee-id">ID: <?php echo $r->user_id; ?></div>
                  </div>
                </div>
              </td>
              <td class="status-cell present">
                <div><?php echo htmlspecialchars($r->present_days); ?></div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, ($r->present_days / 30) * 100); ?>%"></div>
                </div>
              </td>
              <td class="status-cell wfh">
                <div><?php echo htmlspecialchars($r->wfh_days); ?></div>
                <div class="progress-bar">
                  <div class="progress-fill wfh" style="width: <?php echo min(100, ($r->wfh_days / 30) * 100); ?>%"></div>
                </div>
              </td>
              <td class="status-cell absent">
                <div><?php echo htmlspecialchars($r->absent_days); ?></div>
                <div class="progress-bar">
                  <div class="progress-fill absent" style="width: <?php echo min(100, ($r->absent_days / 30) * 100); ?>%"></div>
                </div>
              </td>
              <td class="status-cell" style="color: var(--att-emp-success);">
                <div><?php echo isset($r->on_time_days) ? htmlspecialchars($r->on_time_days) : '0'; ?></div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, (isset($r->on_time_days) ? (float)$r->on_time_days : 0) / 30 * 100); ?>%; background: var(--att-emp-success);"></div>
                </div>
              </td>
              <td class="status-cell" style="color: #d97706;">
                <div><?php echo htmlspecialchars($r->late_days); ?></div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, ($r->late_days / 30) * 100); ?>%; background: #d97706;"></div>
                </div>
              </td>
              <td class="status-cell leave">
                <div><?php echo htmlspecialchars($r->leave_days); ?></div>
                <div class="progress-bar">
                  <div class="progress-fill leave" style="width: <?php echo min(100, ($r->leave_days / 30) * 100); ?>%"></div>
                </div>
              </td>
              <td class="status-cell" style="color: #d97706;">
                <div><?php echo isset($r->late_hours) ? htmlspecialchars($r->late_hours) : '00:00'; ?></div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, (isset($r->late_hours_decimal) ? (float)$r->late_hours_decimal : 0) * 10); ?>%; background: #d97706;"></div>
                </div>
              </td>
              <td class="status-cell" style="color: var(--att-emp-success);">
                <div><?php echo isset($r->extra_hours) ? htmlspecialchars($r->extra_hours) : '00:00'; ?></div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, (isset($r->extra_hours_decimal) ? (float)$r->extra_hours_decimal : 0) * 5); ?>%; background: var(--att-emp-success);"></div>
                </div>
              </td>
              <td class="text-end">
                <div style="display: flex; gap: 0.25rem; justify-content: flex-end;">
                  <?php 
                    $viewParams = [];
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
      Showing <strong id="showing-count"><?php echo count($rows); ?></strong> of <strong><?php echo count($rows); ?></strong>
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
let sortColumn = -1;
let sortDirection = 'asc';

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
  const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
  const userIds = Array.from(checkboxes).map(cb => cb.value);
  
  if (userIds.length === 0) {
    alert('Please select at least one employee to export.');
    return;
  }
  
  exportAttendance(userIds, format);
}

function exportSingle(userId, format) {
  exportAttendance([userId], format);
}

function exportAttendance(userIds, format) {
  if (!userIds || userIds.length === 0) {
    alert('Please select at least one employee to export.');
    return;
  }
  
  const period = getUrlParameter('period') || 'monthly';
  const month = getUrlParameter('month') || '';
  const date = getUrlParameter('date') || '';
  
  const btn = event ? (window.event ? window.event.target.closest('button') : null) : null;
  let originalText = '';
  if (btn) {
    originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Exporting...';
  }
  
  const baseUrl = '<?php echo site_url("reports/export-attendance-employee"); ?>';
  const params = new URLSearchParams();
  params.append('export', format);
  params.append('user_ids', userIds.join(','));
  params.append('period', period);
  if (month) params.append('month', month);
  if (date) params.append('date', date);
  
  const url = baseUrl + '?' + params.toString();
  
  window.location.href = url;
  
  if (btn) {
    setTimeout(() => {
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
  filteredRows = [...allRows];
  initializePagination();
  updateDisplay();
});

document.getElementById('employee-search').addEventListener('input', function() {
  const searchTerm = this.value.toLowerCase();
  
  if (searchTerm === '') {
    filteredRows = [...allRows];
  } else {
    filteredRows = allRows.filter(row => {
      const searchableText = row.getAttribute('data-searchable');
      return searchableText && searchableText.includes(searchTerm);
    });
  }
  
  currentPage = 1;
  updateDisplay();
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
  
  filteredRows.sort((a, b) => {
    let aValue, bValue;
    
    switch(columnIndex) {
      case 1:
        aValue = a.querySelector('.employee-name').textContent.trim();
        bValue = b.querySelector('.employee-name').textContent.trim();
        break;
      case 2:
        aValue = parseFloat(a.cells[2].textContent.trim()) || 0;
        bValue = parseFloat(b.cells[2].textContent.trim()) || 0;
        break;
      case 3:
        aValue = parseFloat(a.cells[3].textContent.trim()) || 0;
        bValue = parseFloat(b.cells[3].textContent.trim()) || 0;
        break;
      case 4:
        aValue = parseFloat(a.cells[4].textContent.trim()) || 0;
        bValue = parseFloat(b.cells[4].textContent.trim()) || 0;
        break;
      case 5:
        aValue = parseFloat(a.cells[5].textContent.trim()) || 0;
        bValue = parseFloat(b.cells[5].textContent.trim()) || 0;
        break;
      case 6:
        aValue = parseFloat(a.cells[6].textContent.trim()) || 0;
        bValue = parseFloat(b.cells[6].textContent.trim()) || 0;
        break;
      case 7:
        aValue = parseFloat(a.cells[7].textContent.trim()) || 0;
        bValue = parseFloat(b.cells[7].textContent.trim()) || 0;
        break;
      case 8: {
        const parseHhMm = (text) => {
          const parts = text.trim().split(':');
          if (parts.length !== 2) return 0;
          return (parseInt(parts[0], 10) || 0) * 60 + (parseInt(parts[1], 10) || 0);
        };
        aValue = parseHhMm(a.cells[8].textContent);
        bValue = parseHhMm(b.cells[8].textContent);
        break;
      }
      case 9: {
        const parseHhMm = (text) => {
          const parts = text.trim().split(':');
          if (parts.length !== 2) return 0;
          return (parseInt(parts[0], 10) || 0) * 60 + (parseInt(parts[1], 10) || 0);
        };
        aValue = parseHhMm(a.cells[9].textContent);
        bValue = parseHhMm(b.cells[9].textContent);
        break;
      }
    }
    
    if (sortDirection === 'asc') {
      return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
    } else {
      return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
    }
  });
  
  updateSortIcons(columnIndex);
  currentPage = 1;
  updateDisplay();
}

function updateSortIcons(activeColumn) {
  const headers = document.querySelectorAll('.data-table th');
  headers.forEach((header, index) => {
    const icon = header.querySelector('.sort-icon');
    if (icon) {
      if (index === activeColumn) {
        icon.className = sortDirection === 'asc' ? 'bi bi-arrow-up sort-icon' : 'bi bi-arrow-down sort-icon';
      } else {
        icon.className = 'bi bi-arrow-down-up sort-icon';
      }
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
  
  allRows.forEach(row => row.style.display = 'none');
  
  for (let i = startIndex; i < endIndex && i < filteredRows.length; i++) {
    filteredRows[i].style.display = '';
  }
  
  const showingCount = Math.min(endIndex, filteredRows.length) - startIndex;
  document.getElementById('showing-count').textContent = showingCount;
  
  updatePaginationControls();
}

function resetSearch() {
  document.getElementById('employee-search').value = '';
  document.getElementById('employee-search').dispatchEvent(new Event('input'));
}

function clearMonthFilter() {
  window.location.href = '<?php echo site_url('reports/attendance-employee'); ?>';
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
