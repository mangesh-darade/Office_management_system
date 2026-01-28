<?php $this->load->view('partials/header', ['title' => 'Employee Attendance']); ?>

<style>
/* Compact Employee Attendance Report Styles */
:root {
  --primary-color: #2563eb;
  --success-color: #10b981;
  --warning-color: #f59e0b;
  --danger-color: #ef4444;
  --info-color: #06b6d4;
  --light-bg: #f8fafc;
  --border-color: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --radius-sm: 0.375rem;
  --radius-md: 0.5rem;
}

body {
  background: #f8fafc;
  min-height: 100vh;
}

/* Compact Header */
.report-header {
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  padding: 0.75rem 1rem;
  margin-bottom: 0.75rem;
  border-left: 3px solid var(--primary-color);
}

.report-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.breadcrumb-nav {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-secondary);
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
}

.breadcrumb-nav a {
  color: var(--primary-color);
  text-decoration: none;
}

.breadcrumb-nav a:hover {
  color: var(--primary-dark);
}

/* Compact Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.stat-card {
  background: white;
  border-radius: var(--radius-md);
  padding: 0.5rem;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  transition: all 0.2s;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--primary-color);
}

.stat-card.success::before { background: var(--success-color); }
.stat-card.warning::before { background: var(--warning-color); }
.stat-card.danger::before { background: var(--danger-color); }
.stat-card.info::before { background: var(--info-color); }

.stat-icon {
  width: 28px;
  height: 28px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  margin-bottom: 0.5rem;
  background: var(--light-bg);
}

.stat-icon.primary { background: rgba(37, 99, 235, 0.1); color: var(--primary-color); }
.stat-icon.success { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
.stat-icon.warning { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
.stat-icon.danger { background: rgba(239, 68, 68, 0.1); color: var(--danger-color); }
.stat-icon.info { background: rgba(6, 182, 212, 0.1); color: var(--info-color); }

.stat-value {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.125rem;
}

.stat-label {
  color: var(--text-secondary);
  font-size: 0.7rem;
  font-weight: 500;
}

/* Compact Filter Section */
.filter-section {
  background: white;
  border-radius: var(--radius-md);
  padding: 0.5rem;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  margin-bottom: 0.5rem;
}

.filter-form {
  display: flex;
  gap: 1rem;
  align-items: end;
  flex-wrap: wrap;
}

.form-group {
  display: flex;
  flex-direction: column;
  min-width: 150px;
}

.form-label {
  font-weight: 500;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
  font-size: 0.85rem;
}

.form-control {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  background: white;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: var(--radius-sm);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.85rem;
}

.btn-primary {
  background: var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background: var(--primary-dark);
}

.btn-outline-secondary {
  background: transparent;
  color: var(--text-secondary);
  border: 1px solid var(--text-secondary);
}

.btn-outline-secondary:hover {
  background: var(--text-secondary);
  color: white;
}

.btn-sm {
  padding: 0.4rem 0.75rem;
  font-size: 0.8rem;
}

.btn-success {
  background: var(--success-color);
  color: white;
}

.btn-success:hover {
  background: #059669;
}

.btn-danger {
  background: var(--danger-color);
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
}

/* Compact Table Section */
.table-section {
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.table-header {
  padding: 0.5rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.table-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.table-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.search-box {
  position: relative;
}

.search-input {
  padding: 0.375rem 0.75rem 0.375rem 2rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
  width: 200px;
}

.search-icon {
  position: absolute;
  left: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  font-size: 0.85rem;
}

.table-wrapper {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: var(--light-bg);
  padding: 0.5rem 0.75rem;
  text-align: left;
  font-weight: 600;
  color: var(--text-primary);
  border-bottom: 1px solid var(--border-color);
  white-space: nowrap;
  cursor: pointer;
  user-select: none;
  font-size: 0.8rem;
  height: 45px;
}

.data-table th:hover {
  background: #f1f5f9;
}

.sort-icon {
  margin-left: 0.25rem;
  opacity: 0.5;
  font-size: 0.7rem;
}

.data-table td {
  padding: 0.375rem 0.5rem;
  border-bottom: 1px solid var(--border-color);
  vertical-align: middle;
  font-size: 0.8rem;
  height: 40px;
}

.data-table tr:hover {
  background: var(--light-bg);
}

.employee-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.employee-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--primary-color);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.7rem;
}

.employee-info {
  flex: 1;
}

.employee-name {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.125rem;
  font-size: 0.85rem;
}

.employee-id {
  font-size: 0.7rem;
  color: var(--text-secondary);
}

.status-cell {
  text-align: center;
  font-weight: 600;
  font-size: 0.8rem;
}

.status-cell.present { color: var(--success-color); }
.status-cell.half { color: var(--warning-color); }
.status-cell.wfh { color: var(--info-color); }
.status-cell.absent { color: var(--danger-color); }
.status-cell.leave { color: var(--text-secondary); }

.progress-bar {
  width: 40px;
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
  margin: 0.25rem auto 0;
}

.progress-fill {
  height: 100%;
  background: var(--success-color);
  transition: width 0.3s ease;
}

.progress-fill.half { background: var(--warning-color); }
.progress-fill.wfh { background: var(--info-color); }
.progress-fill.absent { background: var(--danger-color); }
.progress-fill.leave { background: var(--text-secondary); }

/* Compact Pagination */
.pagination-section {
  padding: 0.5rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.pagination-info {
  color: var(--text-secondary);
  font-size: 0.8rem;
}

.pagination-controls {
  display: flex;
  gap: 0.25rem;
  align-items: center;
}

.pagination-btn {
  padding: 0.25rem 0.375rem;
  border: 1px solid var(--border-color);
  background: white;
  color: var(--text-primary);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.75rem;
}

.pagination-btn:hover:not(:disabled) {
  background: var(--light-bg);
  border-color: var(--primary-color);
}

.pagination-btn.active {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Compact Empty State */
.empty-state {
  text-align: center;
  padding: 1.5rem 0.75rem;
  color: var(--text-secondary);
}

.empty-icon {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
  opacity: 0.5;
}

.empty-title {
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: var(--text-primary);
}

.empty-description {
  margin-bottom: 0.75rem;
  font-size: 0.8rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .report-header {
    padding: 1rem;
  }
  
  .report-title {
    font-size: 1.25rem;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .filter-form {
    flex-direction: column;
    align-items: stretch;
  }
  
  .table-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .table-actions {
    justify-content: space-between;
  }
  
  .search-input {
    width: 100%;
  }
  
  .data-table th,
  .data-table td {
    padding: 0.5rem 0.375rem;
    font-size: 0.8rem;
  }
  
  .employee-avatar {
    width: 28px;
    height: 28px;
    font-size: 0.7rem;
  }
  
  .export-actions-bar {
    gap: 0.4rem !important;
    padding: 0.5rem 0.75rem !important;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  
  .export-actions-bar label {
    font-size: 0.8rem;
  }
  
  .export-text-full {
    display: none !important;
  }
  
  .export-text-short {
    display: inline !important;
  }
}
</style>

<!-- Compact Header -->
<div class="report-header">
  <div class="breadcrumb-nav">
    <a href="<?php echo site_url('reports'); ?>">Reports</a>
    <span>/</span>
    <span>Employee Attendance</span>
  </div>
  
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
    <h1 class="report-title" style="margin: 0; flex: 1;">
      <i class="bi bi-people-fill"></i>
      Employee Attendance
      <?php if (isset($period) && $period === 'monthly'): ?>
        <span style="font-size: 0.85rem; font-weight: 500; color: var(--text-secondary); margin-left: 0.75rem;">
          <?php 
            if (isset($month) && $month) {
              $monthDate = DateTime::createFromFormat('Y-m', $month);
              if ($monthDate) {
                echo $monthDate->format('F Y');
              } else {
                echo htmlspecialchars($month);
              }
            } else {
              $currentMonth = date('Y-m');
              $monthDate = DateTime::createFromFormat('Y-m', $currentMonth);
              echo $monthDate->format('F Y');
            }
          ?>
        </span>
      <?php endif; ?>
    </h1>
    <a href="<?php echo site_url('reports'); ?>" class="btn btn-outline-secondary" style="white-space: nowrap;">
      <i class="bi bi-arrow-left"></i>
      Back
    </a>
  </div>
</div>

<!-- Compact Statistics Cards -->
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
    foreach ($rows as $r) { $totalLateHours += isset($r->late_hours) ? (float)$r->late_hours : 0; }
    if ($totalLateHours > 0):
  ?>
  <div class="stat-card" style="background: linear-gradient(135deg, #fee2e2 0%, #f87171 100%);">
    <div class="stat-icon" style="background: rgba(248, 113, 113, 0.2); color: #dc2626;">
      <i class="bi bi-clock-history"></i>
    </div>
    <div class="stat-value"><?php echo number_format($totalLateHours, 1); ?>h</div>
    <div class="stat-label">Total Late Hours</div>
  </div>
  <?php endif; ?>
  
  <?php 
    $totalExtraHours = 0;
    foreach ($rows as $r) { $totalExtraHours += isset($r->extra_hours) ? (float)$r->extra_hours : 0; }
    if ($totalExtraHours > 0):
  ?>
  <div class="stat-card" style="background: linear-gradient(135deg, #d1fae5 0%, #34d399 100%);">
    <div class="stat-icon" style="background: rgba(52, 211, 153, 0.2); color: #059669;">
      <i class="bi bi-hourglass-split"></i>
    </div>
    <div class="stat-value"><?php echo number_format($totalExtraHours, 1); ?>h</div>
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

<!-- Compact Filter Section -->
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
  <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-secondary);">
    <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
      <div><strong>Period:</strong> <?php echo htmlspecialchars($from); ?> to <?php echo htmlspecialchars($to); ?></div>
      <?php if (isset($total_working_days)): ?>
        <div><strong>Working Days:</strong> <?php echo $total_working_days; ?></div>
      <?php endif; ?>
      <?php if (isset($office_start_time)): ?>
        <div><strong>Office Start:</strong> <?php echo htmlspecialchars($office_start_time); ?></div>
      <?php endif; ?>
      <?php if (isset($grace_minutes)): ?>
        <div><strong>Grace Period:</strong> <?php echo $grace_minutes; ?> minutes</div>
      <?php endif; ?>
      <?php if (isset($standard_working_hours)): ?>
        <div><strong>Standard Hours:</strong> <?php echo $standard_working_hours; ?>h/day</div>
      <?php endif; ?>
      <?php if (isset($holidays) && !empty($holidays)): ?>
        <div style="flex-basis: 100%; margin-top: 0.25rem;">
          <strong style="color: var(--primary-color);">Holidays:</strong> 
          <?php 
            $hNames = [];
            foreach($holidays as $h) {
                $hNames[] = htmlspecialchars($h->name) . ' <span style="color: var(--text-secondary); font-size: 0.8rem;">(' . date('M j', strtotime($h->holiday_date)) . ')</span>';
            }
            echo implode(', ', $hNames);
          ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Compact Table Section -->
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
  <div class="export-actions-bar" style="background: white; padding: 0.75rem 1rem; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: nowrap; overflow-x: auto;">
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
              <div class="empty-state">
                <div class="empty-icon">
                  <i class="bi bi-calendar-x"></i>
                </div>
                <div class="empty-title">No Data Found</div>
                <div class="empty-description">
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
            // Only show row if at least one value is greater than 0
            $hasData = (float)$r->present_days > 0 || 
                       (float)$r->absent_days > 0 || 
                       (float)$r->wfh_days > 0 || 
                       (isset($r->on_time_days) && (float)$r->on_time_days > 0) || 
                       (float)$r->late_days > 0 || 
                       (float)$r->leave_days > 0 || 
                       (isset($r->late_hours) && (float)$r->late_hours > 0) || 
                       (isset($r->extra_hours) && (float)$r->extra_hours > 0);
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
              <td class="status-cell" style="color: var(--success-color);">
                <div><?php echo isset($r->on_time_days) ? htmlspecialchars($r->on_time_days) : '0'; ?></div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, (isset($r->on_time_days) ? (float)$r->on_time_days : 0) / 30 * 100); ?>%; background: var(--success-color);"></div>
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
                <div><?php echo isset($r->late_hours) ? htmlspecialchars($r->late_hours) : '0'; ?>h</div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, (isset($r->late_hours) ? (float)$r->late_hours : 0) * 10); ?>%; background: #d97706;"></div>
                </div>
              </td>
              <td class="status-cell" style="color: var(--success-color);">
                <div><?php echo isset($r->extra_hours) ? htmlspecialchars($r->extra_hours) : '0'; ?>h</div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min(100, (isset($r->extra_hours) ? (float)$r->extra_hours : 0) * 5); ?>%; background: var(--success-color);"></div>
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
      <!-- Pagination will be generated by JavaScript -->
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
// Compact Employee Attendance JavaScript

// Pagination settings
const ITEMS_PER_PAGE = 15;
let currentPage = 1;
let allRows = [];
let filteredRows = [];
let sortColumn = -1;
let sortDirection = 'asc';

// Export functionality
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
  
  // Update select all checkbox state
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
  
  // Show loading indicator
  const btn = event ? (window.event ? window.event.target.closest('button') : null) : null;
  let originalText = '';
  if (btn) {
    originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Exporting...';
  }
  
  // Build URL
  const baseUrl = '<?php echo site_url("reports/export-attendance-employee"); ?>';
  const params = new URLSearchParams();
  params.append('export', format);
  params.append('user_ids', userIds.join(','));
  params.append('period', period);
  if (month) params.append('month', month);
  if (date) params.append('date', date);
  
  const url = baseUrl + '?' + params.toString();
  
  // Use window.location for file downloads (more reliable)
  window.location.href = url;
  
  // Reset button after 3 seconds
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  allRows = Array.from(document.querySelectorAll('#employee-tbody tr[data-index]'));
  filteredRows = [...allRows];
  initializePagination();
  updateDisplay();
});

// Search functionality
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

// Sort functionality
function sortTable(columnIndex) {
  // Skip sorting for checkbox column (index 0)
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
      case 1: // Employee name
        aValue = a.querySelector('.employee-name').textContent.trim();
        bValue = b.querySelector('.employee-name').textContent.trim();
        break;
      case 2: // Present days
        aValue = parseFloat(a.cells[2].textContent.trim()) || 0;
        bValue = parseFloat(b.cells[2].textContent.trim()) || 0;
        break;
      case 3: // WFH days
        aValue = parseFloat(a.cells[3].textContent.trim()) || 0;
        bValue = parseFloat(b.cells[3].textContent.trim()) || 0;
        break;
      case 4: // Absent days
        aValue = parseFloat(a.cells[4].textContent.trim()) || 0;
        bValue = parseFloat(b.cells[4].textContent.trim()) || 0;
        break;
      case 5: // On Time days
        aValue = parseFloat(a.cells[5].textContent.trim()) || 0;
        bValue = parseFloat(b.cells[5].textContent.trim()) || 0;
        break;
      case 6: // Late days
        aValue = parseFloat(a.cells[6].textContent.trim()) || 0;
        bValue = parseFloat(b.cells[6].textContent.trim()) || 0;
        break;
      case 7: // Leave days
        aValue = parseFloat(a.cells[7].textContent.trim()) || 0;
        bValue = parseFloat(b.cells[7].textContent.trim()) || 0;
        break;
      case 8: // Late hours
        aValue = parseFloat(a.cells[8].textContent.replace('h', '').trim()) || 0;
        bValue = parseFloat(b.cells[8].textContent.replace('h', '').trim()) || 0;
        break;
      case 9: // Extra hours
        aValue = parseFloat(a.cells[9].textContent.replace('h', '').trim()) || 0;
        bValue = parseFloat(b.cells[9].textContent.replace('h', '').trim()) || 0;
        break;
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

// Pagination functionality
function initializePagination() {
  updatePaginationControls();
}

function updatePaginationControls() {
  const totalPages = Math.ceil(filteredRows.length / ITEMS_PER_PAGE);
  const controlsContainer = document.getElementById('pagination-controls');
  
  let paginationHTML = '';
  
  // Previous button
  paginationHTML += `<button class="pagination-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
    <i class="bi bi-chevron-left"></i>
  </button>`;
  
  // Page numbers
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
  
  // Next button
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
  
  // Hide all rows
  allRows.forEach(row => row.style.display = 'none');
  
  // Show rows for current page
  for (let i = startIndex; i < endIndex && i < filteredRows.length; i++) {
    filteredRows[i].style.display = '';
  }
  
  // Update pagination info
  const showingCount = Math.min(endIndex, filteredRows.length) - startIndex;
  document.getElementById('showing-count').textContent = showingCount;
  
  // Update pagination controls
  updatePaginationControls();
}

// Utility functions
function resetSearch() {
  document.getElementById('employee-search').value = '';
  document.getElementById('employee-search').dispatchEvent(new Event('input'));
}

function clearMonthFilter() {
  window.location.href = '<?php echo site_url('reports/attendance-employee'); ?>';
}

// Update period filters based on selection
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
