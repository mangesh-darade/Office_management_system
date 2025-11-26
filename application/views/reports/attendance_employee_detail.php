<?php $this->load->view('partials/header', ['title' => 'Employee Attendance Detail']); ?>

<style>
/* Compact Employee Attendance Detail Styles */
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
  padding: 1rem 1.5rem;
  margin-bottom: 1rem;
  border-left: 3px solid var(--primary-color);
}

.employee-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.employee-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.employee-avatar-large {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--primary-color);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1.2rem;
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
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.stat-card {
  background: white;
  border-radius: var(--radius-md);
  padding: 0.75rem;
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
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
  background: var(--light-bg);
}

.stat-icon.primary { background: rgba(37, 99, 235, 0.1); color: var(--primary-color); }
.stat-icon.success { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
.stat-icon.warning { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
.stat-icon.danger { background: rgba(239, 68, 68, 0.1); color: var(--danger-color); }
.stat-icon.info { background: rgba(6, 182, 212, 0.1); color: var(--info-color); }

.stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.125rem;
}

.stat-label {
  color: var(--text-secondary);
  font-size: 0.75rem;
  font-weight: 500;
}

/* Compact Filter Section */
.filter-section {
  background: white;
  border-radius: var(--radius-md);
  padding: 1rem;
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

/* Compact Table Section */
.table-section {
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.table-header {
  padding: 1rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
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
  padding: 0.75rem;
  text-align: left;
  font-weight: 600;
  color: var(--text-primary);
  border-bottom: 1px solid var(--border-color);
  white-space: nowrap;
  font-size: 0.85rem;
}

.data-table td {
  padding: 0.375rem 0.75rem;
  border-bottom: 1px solid var(--border-color);
  vertical-align: middle;
  height: 35px;
  font-size: 0.85rem;
}

.data-table tr:hover {
  background: var(--light-bg);
}

/* Status Badges */
.status-badge {
  padding: 0.125rem 0.375rem;
  border-radius: 9999px;
  font-size: 0.7rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.125rem;
}

.status-badge.present {
  background: rgba(16, 185, 129, 0.1);
  color: var(--success-color);
}

.status-badge.half_day {
  background: rgba(245, 158, 11, 0.1);
  color: var(--warning-color);
}

.status-badge.work_from_home {
  background: rgba(6, 182, 212, 0.1);
  color: var(--info-color);
}

.status-badge.absent {
  background: rgba(239, 68, 68, 0.1);
  color: var(--danger-color);
}

.status-badge.late {
  background: rgba(245, 158, 11, 0.1);
  color: var(--warning-color);
}

.status-badge.ontime {
  background: rgba(16, 185, 129, 0.1);
  color: var(--success-color);
}

.status-badge.leave {
  background: rgba(107, 114, 128, 0.1);
  color: var(--text-secondary);
}

.date-cell {
  font-weight: 600;
  color: var(--text-primary);
}

/* Compact Pagination */
.pagination-section {
  padding: 1rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.pagination-info {
  color: var(--text-secondary);
  font-size: 0.85rem;
}

/* Pagination Controls */
.pagination-controls {
  display: flex;
  gap: 0.5rem;
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
  font-size: 0.8rem;
  background: white;
  min-width: 60px;
}

.pagination-btn {
  padding: 0.375rem 0.5rem;
  border: 1px solid var(--border-color);
  background: white;
  color: var(--text-primary);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.8rem;
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
  padding: 2rem 1rem;
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

/* Responsive Design */
@media (max-width: 768px) {
  .report-header {
    padding: 1rem;
  }
  
  .employee-header {
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }
  
  .employee-title {
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
}
</style>

<!-- Compact Header -->
<div class="report-header">
  <div class="breadcrumb-nav">
    <a href="<?php echo site_url('reports'); ?>">Reports</a>
    <span>/</span>
    <a href="<?php echo site_url('reports/attendance-employee?month='.urlencode($month)); ?>">Employee Attendance</a>
    <span>/</span>
    <span>Details</span>
  </div>
  
  <div class="employee-header">
    <h1 class="employee-title">
      <div class="employee-avatar-large">
        <?php echo strtoupper(substr(htmlspecialchars($name), 0, 1)); ?>
      </div>
      <?php echo htmlspecialchars($name); ?>
    </h1>
    
    <a href="<?php echo site_url('reports/attendance-employee?month='.urlencode($month)); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i>
      Back
    </a>
  </div>
</div>


<!-- Compact Statistics Cards -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon primary">
      <i class="bi bi-calendar-month"></i>
    </div>
    <div class="stat-value"><?php echo htmlspecialchars($month); ?></div>
    <div class="stat-label">Month</div>
  </div>
  
  <div class="stat-card success">
    <div class="stat-icon success">
      <i class="bi bi-check-circle"></i>
    </div>
    <div class="stat-value"><?php 
      $presentCount = 0;
      foreach ($days as $d) { if (strtolower($d->status) === 'present') $presentCount++; }
      echo $presentCount;
    ?></div>
    <div class="stat-label">Present</div>
  </div>
  
  <div class="stat-card warning">
    <div class="stat-icon warning">
      <i class="bi bi-clock"></i>
    </div>
    <div class="stat-value"><?php 
      $lateCount = 0;
      foreach ($days as $d) { if (isset($d->late) && strpos(strtolower($d->late), 'late') === 0) $lateCount++; }
      echo $lateCount;
    ?></div>
    <div class="stat-label">Late</div>
  </div>
  
  <div class="stat-card info">
    <div class="stat-icon info">
      <i class="bi bi-house"></i>
    </div>
    <div class="stat-value"><?php 
      $wfhCount = 0;
      foreach ($days as $d) { if (strtolower($d->status) === 'work from home') $wfhCount++; }
      echo $wfhCount;
    ?></div>
    <div class="stat-label">WFH</div>
  </div>
  
  <div class="stat-card danger">
    <div class="stat-icon danger">
      <i class="bi bi-x-circle"></i>
    </div>
    <div class="stat-value"><?php 
      $absentCount = 0;
      foreach ($days as $d) { if (strtolower($d->status) === 'absent') $absentCount++; }
      echo $absentCount;
    ?></div>
    <div class="stat-label">Absent</div>
  </div>
  
  <div class="stat-card">
    <div class="stat-icon">
      <i class="bi bi-calendar-check"></i>
    </div>
    <div class="stat-value"><?php echo count($days); ?></div>
    <div class="stat-label">Total</div>
  </div>
</div>

<!-- Compact Filter Section -->
<div class="filter-section">
  <form method="get" class="filter-form">
    <div class="form-group">
      <label class="form-label">Month</label>
      <input type="month" name="month" value="<?php echo htmlspecialchars($month); ?>" class="form-control">
    </div>
    
    <div class="form-group">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-funnel-fill"></i>
        Filter
      </button>
    </div>
    
    <div class="form-group">
      <a href="<?php echo site_url('reports/attendance-employee?month='.urlencode($month)); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
        Back
      </a>
    </div>
  </form>
</div>

<!-- Compact Table Section -->
<div class="table-section">
  <div class="table-header">
    <h3 class="table-title">
      <i class="bi bi-calendar-week"></i>
      Daily Details
    </h3>
    <div class="table-actions">
      <div class="search-box">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search..." id="detail-search">
      </div>
      <button class="btn btn-outline-primary" onclick="resetSearch()">
        <i class="bi bi-arrow-clockwise"></i>
        Reset
      </button>
    </div>
  </div>
  
  <div class="table-wrapper">
    <table class="data-table" id="detail-table">
      <thead>
        <tr>
          <th style="width: 22%">Date</th>
          <th style="width: 33%">Status</th>
          <th style="width: 25%">Late/On Time</th>
          <th style="width: 20%">Leave</th>
        </tr>
      </thead>
      <tbody id="detail-tbody">
        <?php if (!empty($days)): ?>
          <?php foreach ($days as $index => $d): ?>
            <tr data-searchable="<?php echo strtolower(htmlspecialchars($d->date . ' ' . $d->status . ' ' . (isset($d->late) ? $d->late : '') . ' ' . $d->leave)); ?>" data-index="<?php echo $index; ?>">
              <td class="date-cell"><?php echo htmlspecialchars($d->date); ?></td>
              <td>
                <?php 
                  $status = strtolower(trim($d->status));
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
                    case 'work from home': 
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
                  <?php echo htmlspecialchars($d->status); ?>
                </span>
              </td>
              <td>
                <?php 
                  $lateText = isset($d->late) ? strtolower(trim($d->late)) : '';
                  if ($lateText === '' || $lateText === '—') {
                    echo '<span class="status-badge">—</span>';
                  } elseif (strpos($lateText, 'late') === 0) {
                    echo '<span class="status-badge late"><i class="bi bi-exclamation-triangle"></i>' . htmlspecialchars($d->late) . '</span>';
                  } else {
                    echo '<span class="status-badge ontime"><i class="bi bi-check-circle"></i>' . htmlspecialchars($d->late) . '</span>';
                  }
                ?>
              </td>
              <td>
                <?php 
                  $leaveText = strtolower(trim($d->leave));
                  if ($leaveText === '' || $leaveText === '—') {
                    echo '<span class="status-badge">—</span>';
                  } else {
                    echo '<span class="status-badge leave"><i class="bi bi-calendar-x"></i>' . htmlspecialchars($d->leave) . '</span>';
                  }
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <?php if (!empty($days)): ?>
  <div class="pagination-section">
    <div class="pagination-info">
      Showing <strong id="showing-count"><?php echo min(20, count($days)); ?></strong> of <strong><?php echo count($days); ?></strong> days
    </div>
    <div class="pagination-controls" id="pagination-controls">
      <!-- Pagination will be generated by JavaScript -->
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
// Compact Employee Attendance Detail JavaScript

// Pagination settings
let ITEMS_PER_PAGE = 20;
let currentPage = 1;
let allRows = [];
let filteredRows = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  allRows = Array.from(document.querySelectorAll('#detail-tbody tr[data-index]'));
  filteredRows = [...allRows];
  initializePagination();
  updateDisplay();
});

// Change rows per page
function changeRowsPerPage() {
  const select = document.getElementById('rows-per-page');
  ITEMS_PER_PAGE = parseInt(select.value);
  currentPage = 1;
  updateDisplay();
}

// Search functionality
document.getElementById('detail-search').addEventListener('input', function() {
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

// Pagination functionality
function initializePagination() {
  updatePaginationControls();
}

function updatePaginationControls() {
  const totalPages = Math.ceil(filteredRows.length / ITEMS_PER_PAGE);
  const controlsContainer = document.getElementById('pagination-controls');
  
  let paginationHTML = '';
  
  // Add rows selector
  paginationHTML += `
    <div class="rows-selector">
      <label class="form-label" style="margin: 0; font-size: 0.8rem;">Rows:</label>
      <select class="rows-select" id="rows-per-page" onchange="changeRowsPerPage()">
        <option value="10" ${ITEMS_PER_PAGE === 10 ? 'selected' : ''}>10</option>
        <option value="20" ${ITEMS_PER_PAGE === 20 ? 'selected' : ''}>20</option>
        <option value="50" ${ITEMS_PER_PAGE === 50 ? 'selected' : ''}>50</option>
        <option value="100" ${ITEMS_PER_PAGE === 100 ? 'selected' : ''}>100</option>
      </select>
    </div>
  `;
  
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
  document.getElementById('detail-search').value = '';
  document.getElementById('detail-search').dispatchEvent(new Event('input'));
}

function clearMonthFilter() {
  window.location.href = '<?php echo site_url('reports/attendance-employee'); ?>';
}
</script>

<?php $this->load->view('partials/footer'); ?>
