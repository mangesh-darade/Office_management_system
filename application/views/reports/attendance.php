<?php $this->load->view('partials/header', ['title' => 'Attendance Report']); ?>

<style>
/* Compact Attendance Report Styles */
:root {
  --primary-color: #2563eb;
  --primary-dark: #1e40af;
  --success-color: #10b981;
  --warning-color: #f59e0b;
  --danger-color: #ef4444;
  --info-color: #06b6d4;
  --light-bg: #f8fafc;
  --border-color: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --radius-sm: 0.375rem;
  --radius-md: 0.5rem;
}

body {
  background: var(--light-bg);
  min-height: 100vh;
}

/* Compact Header */
.report-header {
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  padding: 0.75rem 1rem;
  margin-bottom: 0.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-left: 3px solid var(--primary-color);
}

.report-title {
  font-size: 1.5rem;
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
}

.breadcrumb-nav a {
  color: var(--primary-color);
  text-decoration: none;
}

/* Compact Stats */
.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.stat-card {
  background: white;
  border-radius: var(--radius-md);
  padding: 0.75rem;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  transition: all 0.2s;
}

.stat-card:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.stat-icon {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  margin-bottom: 0.375rem;
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.stat-icon.success { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
.stat-icon.warning { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
.stat-icon.info { background: rgba(6, 182, 212, 0.1); color: var(--info-color); }

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.stat-label {
  color: var(--text-secondary);
  font-size: 0.8rem;
  font-weight: 500;
}

/* Compact Filters */
.filter-section {
  background: white;
  border-radius: var(--radius-md);
  padding: 1rem 1.5rem;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  margin-bottom: 1rem;
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

.form-control, .form-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  background: white;
}

.form-control:focus, .form-select:focus {
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

.btn-success {
  background: var(--success-color);
  color: white;
}

.btn-danger {
  background: var(--danger-color);
  color: white;
}

.btn-outline-primary {
  background: transparent;
  color: var(--primary-color);
  border: 1px solid var(--primary-color);
}

.btn-outline-primary:hover {
  background: var(--primary-color);
  color: white;
}

/* Compact Tabs */
.tabs-section {
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  margin-bottom: 1rem;
}

.tabs-nav {
  display: flex;
  border-bottom: 1px solid var(--border-color);
}

.tab-button {
  padding: 0.75rem 1rem;
  background: none;
  border: none;
  color: var(--text-secondary);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
  border-bottom: 2px solid transparent;
  font-size: 0.9rem;
}

.tab-button:hover {
  color: var(--text-primary);
  background: var(--light-bg);
}

.tab-button.active {
  color: var(--primary-color);
  border-bottom-color: var(--primary-color);
}

/* Compact Table */
.table-section {
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.table-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
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
  gap: 0.75rem;
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
  font-size: 0.9rem;
}

.data-table th {
  background: var(--light-bg);
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--text-primary);
  border-bottom: 1px solid var(--border-color);
  white-space: nowrap;
  cursor: pointer;
  user-select: none;
  font-size: 0.85rem;
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
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--border-color);
  vertical-align: middle;
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
  font-size: 0.75rem;
}

.employee-info {
  flex: 1;
}

.employee-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
}

.employee-id {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.status-badge.present {
  background: rgba(16, 185, 129, 0.1);
  color: var(--success-color);
}

.status-badge.absent {
  background: rgba(239, 68, 68, 0.1);
  color: var(--danger-color);
}

.status-badge.half_day {
  background: rgba(245, 158, 11, 0.1);
  color: var(--warning-color);
}

.status-badge.work_from_home {
  background: rgba(6, 182, 212, 0.1);
  color: var(--info-color);
}

.count-cell {
  text-align: center;
  font-weight: 600;
  color: var(--text-primary);
}

/* Pagination Section */
.pagination-section {
  padding: 0.5rem 1rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.pagination-info {
  color: var(--text-secondary);
  font-size: 0.75rem;
}

.pagination-controls {
  display: flex;
  gap: 0.25rem;
  align-items: center;
  flex-wrap: wrap;
}

.rows-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-right: auto;
}

.rows-select {
  padding: 0.25rem 0.5rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  background: white;
  min-width: 60px;
}

.pagination-btn {
  padding: 0.25rem 0.375rem;
  border: 1px solid var(--border-color);
  background: white;
  color: var(--text-primary);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.7rem;
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
  font-size: 2rem;
  margin-bottom: 0.5rem;
  opacity: 0.5;
}

.empty-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: var(--text-primary);
}

.empty-description {
  margin-bottom: 1rem;
  font-size: 0.85rem;
}

/* Responsive */
@media (max-width: 768px) {
  .report-header {
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
    padding: 1rem;
  }
  
  .report-title {
    font-size: 1.25rem;
  }
  
  .stats-row {
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
  }
  
  .filter-form {
    flex-direction: column;
    align-items: stretch;
  }
  
  .table-header {
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }
  
  .table-actions {
    justify-content: space-between;
  }
  
  .search-input {
    width: 100%;
  }
  
  .data-table {
    font-size: 0.85rem;
  }
  
  .data-table th,
  .data-table td {
    padding: 0.5rem;
  }
  
  .employee-avatar {
    width: 28px;
    height: 28px;
    font-size: 0.7rem;
  }
}
</style>

<!-- Compact Header -->
<div class="report-header">
  <div>
    <div class="breadcrumb-nav">
      <a href="<?php echo site_url('reports'); ?>">Reports</a>
      <span>/</span>
      <span>Attendance</span>
    </div>
    <h1 class="report-title">
      <i class="bi bi-calendar-check"></i>
      Attendance Report
    </h1>
  </div>
</div>

<!-- Compact Stats -->
<div class="stats-row">
  <div class="stat-card">
    <div class="stat-icon">
      <i class="bi bi-calendar-range"></i>
    </div>
    <div class="stat-value"><?php echo htmlspecialchars($start_date); ?></div>
    <div class="stat-label">Start Date</div>
  </div>
  
  <div class="stat-card">
    <div class="stat-icon">
      <i class="bi bi-calendar-event"></i>
    </div>
    <div class="stat-value"><?php echo htmlspecialchars($end_date); ?></div>
    <div class="stat-label">End Date</div>
  </div>
  
  <div class="stat-card">
    <div class="stat-icon success">
      <i class="bi bi-people"></i>
    </div>
    <div class="stat-value"><?php 
      $total = 0;
      $dataVar = $period;
      foreach ($$dataVar as $row) { $total += $row->cnt; }
      echo number_format($total); 
    ?></div>
    <div class="stat-label">Total Records</div>
  </div>
  
  <div class="stat-card">
    <div class="stat-icon warning">
      <i class="bi bi-clock-history"></i>
    </div>
    <div class="stat-value"><?php 
      $lateTotal = 0;
      $lateVar = $period . 'Late';
      foreach ($$lateVar as $row) { $lateTotal += $row->late_cnt; }
      echo number_format($lateTotal); 
    ?></div>
    <div class="stat-label">Late Marks</div>
  </div>
  
  <div class="stat-card">
    <div class="stat-icon info">
      <i class="bi bi-person-badge"></i>
    </div>
    <div class="stat-value"><?php 
      $employees = [];
      $dataVar = $period;
      foreach ($$dataVar as $row) { $employees[$row->uid] = true; }
      echo count($employees); 
    ?></div>
    <div class="stat-label">Employees</div>
  </div>
</div>

<!-- Compact Filters -->
<div class="filter-section">
  <form method="get" class="filter-form">
    <input type="hidden" name="period" value="<?php echo htmlspecialchars($period); ?>">
    
    <div class="form-group">
      <label class="form-label">Start Date</label>
      <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="form-control">
    </div>
    
    <div class="form-group">
      <label class="form-label">End Date</label>
      <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="form-control">
    </div>
    
    <?php if (!empty($departments)): ?>
    <div class="form-group">
      <label class="form-label">Department</label>
      <select name="department_id" class="form-select">
        <option value="all">All Departments</option>
        <?php foreach ($departments as $dept): ?>
          <option value="<?php echo $dept->id; ?>" <?php echo ($selected_department == $dept->id) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($dept->name); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    
    <div class="form-group">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-funnel-fill"></i>
        Apply
      </button>
    </div>
    
    <div class="form-group">
      <a href="<?php echo site_url('reports/attendance?period=' . $period . '&export=csv&start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date) . ($selected_department ? '&department_id=' . $selected_department : '')); ?>" class="btn btn-success">
        <i class="bi bi-file-earmark-csv"></i>
        CSV
      </a>
    </div>
    
    <div class="form-group">
      <a href="<?php echo site_url('reports/attendance?period=' . $period . '&export=pdf&start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date) . ($selected_department ? '&department_id=' . $selected_department : '')); ?>" class="btn btn-danger">
        <i class="bi bi-file-earmark-pdf"></i>
        PDF
      </a>
    </div>
  </form>
</div>

<!-- Compact Tabs -->
<div class="tabs-section">
  <div class="tabs-nav">
    <button class="tab-button <?php echo ($period==='daily')?'active':''; ?>" onclick="switchTab('daily')">
      <i class="bi bi-calendar-day"></i>
      Daily
    </button>
    <button class="tab-button <?php echo ($period==='weekly')?'active':''; ?>" onclick="switchTab('weekly')">
      <i class="bi bi-calendar-week"></i>
      Weekly
    </button>
    <button class="tab-button <?php echo ($period==='monthly')?'active':''; ?>" onclick="switchTab('monthly')">
      <i class="bi bi-calendar-month"></i>
      Monthly
    </button>
  </div>
</div>

<!-- Daily Table -->
<div id="daily-content" class="table-section" style="<?php echo ($period!=='daily')?'display:none;': ''; ?>">
  <div class="table-header">
    <h3 class="table-title">
      <i class="bi bi-calendar-day"></i>
      Daily Attendance
    </h3>
    <div class="table-actions">
      <div class="search-box">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search..." id="daily-search">
      </div>
      <button class="btn btn-outline-primary" onclick="resetTable('daily')">
        <i class="bi bi-arrow-clockwise"></i>
        Reset
      </button>
    </div>
  </div>
  
  <div class="table-wrapper">
    <table class="data-table" id="daily-table">
      <thead>
        <tr>
          <th onclick="sortTable('daily', 0)">
            Employee <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable('daily', 1)">
            Date <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable('daily', 2)">
            Status <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable('daily', 3)">
            Count <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($daily)): ?>
          <tr>
            <td colspan="4">
              <div class="empty-state">
                <div class="empty-icon">
                  <i class="bi bi-calendar-x"></i>
                </div>
                <div class="empty-title">No Data Found</div>
                <div class="empty-description">
                  No attendance data for selected criteria.
                </div>
                <button class="btn btn-primary" onclick="clearFilters()">
                  <i class="bi bi-funnel-fill"></i>
                  Clear Filters
                </button>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($daily as $index => $r): ?>
            <tr data-searchable="<?php echo strtolower(htmlspecialchars(isset($r->name)?$r->name:'') . ' ' . htmlspecialchars(isset($r->bucket)?$r->bucket:'') . ' ' . htmlspecialchars(isset($r->status)?$r->status:'')); ?>" data-index="daily-<?php echo $index; ?>">
              <td>
                <div class="employee-cell">
                  <div class="employee-avatar">
                    <?php echo strtoupper(substr(htmlspecialchars(isset($r->name)?$r->name:'Unknown'), 0, 1)); ?>
                  </div>
                  <div class="employee-info">
                    <div class="employee-name"><?php echo htmlspecialchars(isset($r->name)?$r->name:'Unknown'); ?></div>
                    <div class="employee-id">ID: <?php echo $r->uid; ?></div>
                  </div>
                </div>
              </td>
              <td><?php echo htmlspecialchars(isset($r->bucket)?$r->bucket:''); ?></td>
              <td>
                <?php 
                  $status = strtolower(isset($r->status)?$r->status:'');
                  $statusClass = '';
                  $statusIcon = '';
                  switch($status) {
                    case 'present': 
                      $statusClass = 'present'; 
                      $statusIcon = 'bi-check-circle';
                      break;
                    case 'absent': 
                      $statusClass = 'absent'; 
                      $statusIcon = 'bi-x-circle';
                      break;
                    case 'half_day': 
                      $statusClass = 'half_day'; 
                      $statusIcon = 'bi-clock';
                      break;
                    case 'work_from_home': 
                      $statusClass = 'work_from_home'; 
                      $statusIcon = 'bi-house';
                      break;
                    default: 
                      $statusClass = ''; 
                      $statusIcon = 'bi-question-circle';
                  }
                ?>
                <span class="status-badge <?php echo $statusClass; ?>">
                  <i class="bi <?php echo $statusIcon; ?>"></i>
                  <?php echo htmlspecialchars(ucfirst(isset($r->status)?$r->status:'Unknown')); ?>
                </span>
              </td>
              <td class="count-cell"><?php echo (int)$r->cnt; ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <?php if (!empty($daily)): ?>
  <div class="pagination-section">
    <div class="pagination-info">
      Showing <strong id="daily-showing-count"><?php echo min(20, count($daily)); ?></strong> of <strong><?php echo count($daily); ?></strong> records
    </div>
    <div class="pagination-controls" id="daily-pagination-controls">
      <!-- Pagination will be generated by JavaScript -->
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Weekly Table -->
<div id="weekly-content" class="table-section" style="<?php echo ($period!=='weekly')?'display:none;': ''; ?>">
  <div class="table-header">
    <h3 class="table-title">
      <i class="bi bi-calendar-week"></i>
      Weekly Attendance
    </h3>
    <div class="table-actions">
      <div class="search-box">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search..." id="weekly-search">
      </div>
      <button class="btn btn-outline-primary" onclick="resetTable('weekly')">
        <i class="bi bi-arrow-clockwise"></i>
        Reset
      </button>
    </div>
  </div>
  
  <div class="table-wrapper">
    <table class="data-table" id="weekly-table">
      <thead>
        <tr>
          <th onclick="sortTable('weekly', 0)">
            Employee <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable('weekly', 1)">
            Week <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable('weekly', 2)">
            Status <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable('weekly', 3)">
            Count <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($weekly)): ?>
          <tr>
            <td colspan="4">
              <div class="empty-state">
                <div class="empty-icon">
                  <i class="bi bi-calendar-x"></i>
                </div>
                <div class="empty-title">No Data Found</div>
                <div class="empty-description">
                  No weekly attendance data for selected criteria.
                </div>
                <button class="btn btn-primary" onclick="clearFilters()">
                  <i class="bi bi-funnel-fill"></i>
                  Clear Filters
                </button>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($weekly as $index => $r): ?>
            <tr data-searchable="<?php echo strtolower(htmlspecialchars(isset($r->name)?$r->name:'') . ' ' . htmlspecialchars(isset($r->bucket)?$r->bucket:'') . ' ' . htmlspecialchars(isset($r->status)?$r->status:'')); ?>" data-index="weekly-<?php echo $index; ?>">
              <td>
                <div class="employee-cell">
                  <div class="employee-avatar">
                    <?php echo strtoupper(substr(htmlspecialchars(isset($r->name)?$r->name:'Unknown'), 0, 1)); ?>
                  </div>
                  <div class="employee-info">
                    <div class="employee-name"><?php echo htmlspecialchars(isset($r->name)?$r->name:'Unknown'); ?></div>
                    <div class="employee-id">ID: <?php echo $r->uid; ?></div>
                  </div>
                </div>
              </td>
              <td><?php echo htmlspecialchars(isset($r->bucket)?$r->bucket:''); ?></td>
              <td>
                <?php 
                  $status = strtolower(isset($r->status)?$r->status:'');
                  $statusClass = '';
                  $statusIcon = '';
                  switch($status) {
                    case 'present': 
                      $statusClass = 'present'; 
                      $statusIcon = 'bi-check-circle';
                      break;
                    case 'absent': 
                      $statusClass = 'absent'; 
                      $statusIcon = 'bi-x-circle';
                      break;
                    case 'half_day': 
                      $statusClass = 'half_day'; 
                      $statusIcon = 'bi-clock';
                      break;
                    case 'work_from_home': 
                      $statusClass = 'work_from_home'; 
                      $statusIcon = 'bi-house';
                      break;
                    default: 
                      $statusClass = ''; 
                      $statusIcon = 'bi-question-circle';
                  }
                ?>
                <span class="status-badge <?php echo $statusClass; ?>">
                  <i class="bi <?php echo $statusIcon; ?>"></i>
                  <?php echo htmlspecialchars(ucfirst(isset($r->status)?$r->status:'Unknown')); ?>
                </span>
              </td>
              <td class="count-cell"><?php echo (int)$r->cnt; ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <?php if (!empty($weekly)): ?>
  <div class="pagination-section">
    <div class="pagination-info">
      Showing <strong id="weekly-showing-count"><?php echo min(20, count($weekly)); ?></strong> of <strong><?php echo count($weekly); ?></strong> records
    </div>
    <div class="pagination-controls" id="weekly-pagination-controls">
      <!-- Pagination will be generated by JavaScript -->
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Monthly Table -->
<div id="monthly-content" class="table-section" style="<?php echo ($period!=='monthly')?'display:none;': ''; ?>">
  <div class="table-header">
    <h3 class="table-title">
      <i class="bi bi-calendar-month"></i>
      Monthly Attendance
    </h3>
    <div class="table-actions">
      <div class="search-box">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search..." id="monthly-search">
      </div>
      <button class="btn btn-outline-primary" onclick="resetTable('monthly')">
        <i class="bi bi-arrow-clockwise"></i>
        Reset
      </button>
    </div>
  </div>
  
  <div class="table-wrapper">
    <table class="data-table" id="monthly-table">
      <thead>
        <tr>
          <th onclick="sortTable('monthly', 0)">
            Employee <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable('monthly', 1)">
            Month <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable('monthly', 2)">
            Status <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
          <th onclick="sortTable('monthly', 3)">
            Count <i class="bi bi-arrow-down-up sort-icon"></i>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($monthly)): ?>
          <tr>
            <td colspan="4">
              <div class="empty-state">
                <div class="empty-icon">
                  <i class="bi bi-calendar-x"></i>
                </div>
                <div class="empty-title">No Data Found</div>
                <div class="empty-description">
                  No monthly attendance data for selected criteria.
                </div>
                <button class="btn btn-primary" onclick="clearFilters()">
                  <i class="bi bi-funnel-fill"></i>
                  Clear Filters
                </button>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($monthly as $index => $r): ?>
            <tr data-searchable="<?php echo strtolower(htmlspecialchars(isset($r->name)?$r->name:'') . ' ' . htmlspecialchars(isset($r->bucket)?$r->bucket:'') . ' ' . htmlspecialchars(isset($r->status)?$r->status:'')); ?>" data-index="monthly-<?php echo $index; ?>">
              <td>
                <div class="employee-cell">
                  <div class="employee-avatar">
                    <?php echo strtoupper(substr(htmlspecialchars(isset($r->name)?$r->name:'Unknown'), 0, 1)); ?>
                  </div>
                  <div class="employee-info">
                    <div class="employee-name"><?php echo htmlspecialchars(isset($r->name)?$r->name:'Unknown'); ?></div>
                    <div class="employee-id">ID: <?php echo $r->uid; ?></div>
                  </div>
                </div>
              </td>
              <td><?php echo htmlspecialchars(isset($r->bucket)?$r->bucket:''); ?></td>
              <td>
                <?php 
                  $status = strtolower(isset($r->status)?$r->status:'');
                  $statusClass = '';
                  $statusIcon = '';
                  switch($status) {
                    case 'present': 
                      $statusClass = 'present'; 
                      $statusIcon = 'bi-check-circle';
                      break;
                    case 'absent': 
                      $statusClass = 'absent'; 
                      $statusIcon = 'bi-x-circle';
                      break;
                    case 'half_day': 
                      $statusClass = 'half_day'; 
                      $statusIcon = 'bi-clock';
                      break;
                    case 'work_from_home': 
                      $statusClass = 'work_from_home'; 
                      $statusIcon = 'bi-house';
                      break;
                    default: 
                      $statusClass = ''; 
                      $statusIcon = 'bi-question-circle';
                  }
                ?>
                <span class="status-badge <?php echo $statusClass; ?>">
                  <i class="bi <?php echo $statusIcon; ?>"></i>
                  <?php echo htmlspecialchars(ucfirst(isset($r->status)?$r->status:'Unknown')); ?>
                </span>
              </td>
              <td class="count-cell"><?php echo (int)$r->cnt; ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <?php if (!empty($monthly)): ?>
  <div class="pagination-section">
    <div class="pagination-info">
      Showing <strong id="monthly-showing-count"><?php echo min(20, count($monthly)); ?></strong> of <strong><?php echo count($monthly); ?></strong> records
    </div>
    <div class="pagination-controls" id="monthly-pagination-controls">
      <!-- Pagination will be generated by JavaScript -->
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
// Compact JavaScript for Attendance Report with Pagination

// Pagination settings for each table
var paginationData = {
  daily: { itemsPerPage: 20, currentPage: 1, allRows: [], filteredRows: [] },
  weekly: { itemsPerPage: 20, currentPage: 1, allRows: [], filteredRows: [] },
  monthly: { itemsPerPage: 20, currentPage: 1, allRows: [], filteredRows: [] }
};

// Initialize pagination on page load
document.addEventListener('DOMContentLoaded', function() {
  initializePagination('daily');
  initializePagination('weekly');
  initializePagination('monthly');
});

function initializePagination(tablePrefix) {
  var table = document.getElementById(tablePrefix + '-table');
  if (table) {
    var tbody = table.getElementsByTagName('tbody')[0];
    var rows = Array.from(tbody.getElementsByTagName('tr'));
    
    paginationData[tablePrefix].allRows = rows;
    paginationData[tablePrefix].filteredRows = [...rows];
    paginationData[tablePrefix].currentPage = 1;
    
    updatePaginationControls(tablePrefix);
    updateDisplay(tablePrefix);
  }
}

function changeRowsPerPage(tablePrefix) {
  var select = document.getElementById(tablePrefix + '-rows-per-page');
  if (select) {
    paginationData[tablePrefix].itemsPerPage = parseInt(select.value);
    paginationData[tablePrefix].currentPage = 1;
    updateDisplay(tablePrefix);
  }
}

function goToPage(tablePrefix, page) {
  var totalPages = Math.ceil(paginationData[tablePrefix].filteredRows.length / paginationData[tablePrefix].itemsPerPage);
  if (page < 1 || page > totalPages) return;
  
  paginationData[tablePrefix].currentPage = page;
  updateDisplay(tablePrefix);
}

function updatePaginationControls(tablePrefix) {
  var data = paginationData[tablePrefix];
  var totalPages = Math.ceil(data.filteredRows.length / data.itemsPerPage);
  var controlsContainer = document.getElementById(tablePrefix + '-pagination-controls');
  
  if (!controlsContainer) return;
  
  var paginationHTML = '';
  
  // Add rows selector
  paginationHTML += `
    <div class="rows-selector">
      <label style="margin: 0; font-size: 0.75rem;">Rows:</label>
      <select class="rows-select" id="${tablePrefix}-rows-per-page" onchange="changeRowsPerPage('${tablePrefix}')">
        <option value="10" ${data.itemsPerPage === 10 ? 'selected' : ''}>10</option>
        <option value="20" ${data.itemsPerPage === 20 ? 'selected' : ''}>20</option>
        <option value="50" ${data.itemsPerPage === 50 ? 'selected' : ''}>50</option>
        <option value="100" ${data.itemsPerPage === 100 ? 'selected' : ''}>100</option>
      </select>
    </div>
  `;
  
  // Previous button
  paginationHTML += `<button class="pagination-btn" onclick="goToPage('${tablePrefix}', ${data.currentPage - 1})" ${data.currentPage === 1 ? 'disabled' : ''}>
    <i class="bi bi-chevron-left"></i>
  </button>`;
  
  // Page numbers
  var startPage = Math.max(1, data.currentPage - 2);
  var endPage = Math.min(totalPages, startPage + 4);
  
  if (startPage > 1) {
    paginationHTML += `<button class="pagination-btn" onclick="goToPage('${tablePrefix}', 1)">1</button>`;
    if (startPage > 2) {
      paginationHTML += `<span class="pagination-btn" disabled>...</span>`;
    }
  }
  
  for (var i = startPage; i <= endPage; i++) {
    paginationHTML += `<button class="pagination-btn ${i === data.currentPage ? 'active' : ''}" onclick="goToPage('${tablePrefix}', ${i})">${i}</button>`;
  }
  
  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      paginationHTML += `<span class="pagination-btn" disabled>...</span>`;
    }
    paginationHTML += `<button class="pagination-btn" onclick="goToPage('${tablePrefix}', ${totalPages})">${totalPages}</button>`;
  }
  
  // Next button
  paginationHTML += `<button class="pagination-btn" onclick="goToPage('${tablePrefix}', ${data.currentPage + 1})" ${data.currentPage === totalPages ? 'disabled' : ''}>
    <i class="bi bi-chevron-right"></i>
  </button>`;
  
  controlsContainer.innerHTML = paginationHTML;
}

function updateDisplay(tablePrefix) {
  var data = paginationData[tablePrefix];
  var startIndex = (data.currentPage - 1) * data.itemsPerPage;
  var endIndex = startIndex + data.itemsPerPage;
  
  // Hide all rows
  data.allRows.forEach(row => row.style.display = 'none');
  
  // Show rows for current page
  for (var i = startIndex; i < endIndex && i < data.filteredRows.length; i++) {
    data.filteredRows[i].style.display = '';
  }
  
  // Update pagination info
  var showingCount = Math.min(endIndex, data.filteredRows.length) - startIndex;
  var infoElement = document.getElementById(tablePrefix + '-showing-count');
  if (infoElement) {
    infoElement.textContent = showingCount;
  }
  
  // Update pagination controls
  updatePaginationControls(tablePrefix);
}

function switchTab(period) {
  document.querySelectorAll('[id$="-content"]').forEach(content => {
    content.style.display = 'none';
  });
  
  document.querySelectorAll('.tab-button').forEach(tab => {
    tab.classList.remove('active');
  });
  
  document.getElementById(period + '-content').style.display = 'block';
  event.target.closest('.tab-button').classList.add('active');
  
  var url = new URL(window.location);
  url.searchParams.set('period', period);
  window.history.pushState({}, '', url);
}

document.querySelectorAll('.search-input').forEach(input => {
  input.addEventListener('input', function() {
    var tablePrefix = this.id.replace('-search', '');
    var searchTerm = this.value.toLowerCase();
    var data = paginationData[tablePrefix];
    
    if (searchTerm === '') {
      data.filteredRows = [...data.allRows];
    } else {
      data.filteredRows = data.allRows.filter(row => {
        var searchableText = row.getAttribute('data-searchable');
        return searchableText && searchableText.includes(searchTerm);
      });
    }
    
    data.currentPage = 1;
    updateDisplay(tablePrefix);
  });
});

function sortTable(tablePrefix, columnIndex) {
  var table = document.getElementById(tablePrefix + '-table');
  if (!table) return;
  
  var tbody = table.getElementsByTagName('tbody')[0];
  var rows = Array.from(tbody.getElementsByTagName('tr'));
  var data = paginationData[tablePrefix];
  
  // Toggle sort direction
  var isAscending = !table.getAttribute('data-sort-asc-' + columnIndex);
  table.setAttribute('data-sort-asc-' + columnIndex, isAscending);
  
  // Sort rows
  rows.sort(function(a, b) {
    var aValue = a.getElementsByTagName('td')[columnIndex].textContent.trim();
    var bValue = b.getElementsByTagName('td')[columnIndex].textContent.trim();
    
    if (isAscending) {
      return aValue.localeCompare(bValue);
    } else {
      return bValue.localeCompare(aValue);
    }
  });
  
  // Update DOM and pagination data
  rows.forEach(row => tbody.appendChild(row));
  data.allRows = rows;
  data.filteredRows = [...rows];
  
  updateDisplay(tablePrefix);
}

function resetTable(tablePrefix) {
  var searchInput = document.getElementById(tablePrefix + '-search');
  if (searchInput) {
    searchInput.value = '';
    searchInput.dispatchEvent(new Event('input'));
  }
}

function clearFilters() {
  window.location.href = '<?php echo site_url('reports/attendance'); ?>';
}
</script>
        <?php $this->load->view('partials/footer'); ?>
