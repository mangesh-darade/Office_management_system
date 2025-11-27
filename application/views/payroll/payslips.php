<?php $this->load->view('partials/header', ['title' => 'Payroll Management']); ?>

<div class="container-fluid p-4">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h2 mb-1">
        <i class="bi bi-wallet2 text-primary me-2"></i>Payroll Management
      </h1>
      <p class="text-muted mb-0">Manage employee payslips and salary structures</p>
    </div>
    <div class="d-flex gap-2">
      <div class="btn-group" role="group">
        <a href="<?php echo site_url('payroll/structures'); ?>" class="btn btn-outline-primary">
          <i class="bi bi-diagram-3 me-1"></i>Salary Structures
        </a>
        <a href="<?php echo site_url('payroll/generate'); ?>" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i>Generate Payslip
        </a>
      </div>
    </div>
  </div>

  <!-- Flash Messages -->
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>
      <?php echo htmlspecialchars($this->session->flashdata('error')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-2"></i>
      <?php echo htmlspecialchars($this->session->flashdata('success')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Statistics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm hover-lift">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-2">Total Payslips</h6>
              <h3 class="mb-0" id="totalPayslips"><?php echo count($rows); ?></h3>
            </div>
            <div class="ms-3">
              <div class="bg-primary bg-opacity-10 rounded p-3">
                <i class="bi bi-file-earmark-text text-primary fs-4"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm hover-lift">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-2">Current Month</h6>
              <h3 class="mb-0" id="currentMonthCount">0</h3>
            </div>
            <div class="ms-3">
              <div class="bg-success bg-opacity-10 rounded p-3">
                <i class="bi bi-calendar-check text-success fs-4"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm hover-lift">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-2">Total Payroll</h6>
              <h3 class="mb-0" id="totalPayroll">₹0</h3>
            </div>
            <div class="ms-3">
              <div class="bg-info bg-opacity-10 rounded p-3">
                <i class="bi bi-currency-rupee text-info fs-4"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm hover-lift">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-2">Avg. Salary</h6>
              <h3 class="mb-0" id="avgSalary">₹0</h3>
            </div>
            <div class="ms-3">
              <div class="bg-warning bg-opacity-10 rounded p-3">
                <i class="bi bi-graph-up text-warning fs-4"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Unified Filter Section -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filter Payslips</h6>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">
          <i class="bi bi-arrow-clockwise me-1"></i>Reset All
        </button>
      </div>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label small text-muted">Period</label>
          <select name="period" id="periodFilter" class="form-select">
            <option value="">All Periods</option>
            <?php 
            $periods = [];
            foreach($rows as $r) $periods[] = $r->period;
            $periods = array_unique($periods);
            rsort($periods);
            foreach($periods as $period): 
              $monthName = date('F Y', strtotime($period.'-01'));
            ?>
              <option value="<?php echo $period; ?>" <?php echo (isset($filters['period']) && $filters['period']==$period)?'selected':''; ?>>
                <?php echo $monthName; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small text-muted">Employee</label>
          <select name="user_id" id="userFilter" class="form-select">
            <option value="">All Employees</option>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo (int)$u['id']; ?>" <?php echo (!empty($filters['user_id']) && (int)$filters['user_id']===(int)$u['id'])?'selected':''; ?>>
                <?php echo htmlspecialchars($u['label']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small text-muted">Search</label>
          <input type="text" id="searchFilter" class="form-control" placeholder="Name...">
        </div>
        <div class="col-md-2">
          <label class="form-label small text-muted">Status</label>
          <select id="statusFilter" class="form-select">
            <option value="">All Status</option>
            <option value="recent">Recent (30 days)</option>
            <option value="current">Current Month</option>
            <option value="previous">Previous Month</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small text-muted">Department</label>
          <select name="department" id="departmentFilter" class="form-select">
            <option value="">All Departments</option>
            <option value="IT">IT</option>
            <option value="HR">HR</option>
            <option value="Finance">Finance</option>
            <option value="Sales">Sales</option>
          </select>
        </div>
      </div>
      <div class="row g-3 mt-2">
        <div class="col-md-3">
          <label class="form-label small text-muted">Date Range</label>
          <div class="input-group input-group-sm">
            <input type="month" name="date_from" id="dateFromFilter" class="form-control">
            <span class="input-group-text">to</span>
            <input type="month" name="date_to" id="dateToFilter" class="form-control">
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label small text-muted">Salary Range</label>
          <div class="input-group input-group-sm">
            <input type="number" name="salary_min" id="salaryMinFilter" class="form-control" placeholder="Min">
            <span class="input-group-text">to</span>
            <input type="number" name="salary_max" id="salaryMaxFilter" class="form-control" placeholder="Max">
          </div>
        </div>
        <div class="col-md-2">
          <label class="form-label small text-muted">Payment Mode</label>
          <select name="pay_mode" id="payModeFilter" class="form-select">
            <option value="">All Modes</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cash">Cash</option>
            <option value="Cheque">Cheque</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small text-muted">Actions</label>
          <div class="btn-group w-100" role="group">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportPayslips('csv')">
              <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
            </button>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportPayslips('pdf')">
              <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
            </button>
            <button type="button" class="btn btn-sm btn-outline-info" onclick="applyFilters()">
              <i class="bi bi-check-circle me-1"></i>Apply Filters
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Payslips Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
      <div class="d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="bi bi-table me-2"></i>Payslips Register</h5>
        <div class="d-flex align-items-center gap-2">
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="selectAllPayslips">
            <label class="form-check-label" for="selectAllPayslips">Select All</label>
          </div>
          <button type="button" class="btn btn-sm btn-primary" onclick="sendSelectedEmails()" id="sendEmailBtn" disabled>
            <i class="bi bi-envelope me-1"></i>Send Email (<span id="selectedCount">0</span>)
          </button>
        </div>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="payslipsTable">
          <thead class="table-light">
            <tr>
              <th width="40px">
                <input type="checkbox" class="form-check-input" id="masterCheckbox">
              </th>
              <th width="60px">#</th>
              <th>Employee</th>
              <th width="120px">Period</th>
              <th width="100px" class="text-end">Gross</th>
              <th width="100px" class="text-end">Net</th>
              <th width="120px">Status</th>
              <th width="140px">Generated</th>
              <th width="120px" class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody id="payslipsTableBody">
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="9" class="text-center py-5">
                  <div class="text-muted">
                    <i class="bi bi-inbox fs-1 mb-3 d-block"></i>
                    <h5>No payslips found</h5>
                    <p>Generate your first payslip to get started</p>
                    <a href="<?php echo site_url('payroll/generate'); ?>" class="btn btn-primary">
                      <i class="bi bi-plus-circle me-1"></i>Generate Payslip
                    </a>
                  </div>
                </td>
              </tr>
            <?php else: $i=1; foreach ($rows as $r): ?>
              <tr data-id="<?php echo (int)$r->id; ?>" data-period="<?php echo htmlspecialchars($r->period); ?>" data-user="<?php echo htmlspecialchars($r->name); ?>">
                <td>
                  <input type="checkbox" class="form-check-input payslip-checkbox" name="ids[]" value="<?php echo (int)$r->id; ?>">
                </td>
                <td><?php echo $i++; ?></td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2">
                      <span class="text-primary fw-bold">
                        <?php echo strtoupper(substr(isset($r->name)?$r->name:'User', 0, 2)); ?>
                      </span>
                    </div>
                    <div>
                      <div class="fw-medium"><?php echo htmlspecialchars(isset($r->name)?$r->name:'Unknown'); ?></div>
                      <div class="text-muted small"><?php echo htmlspecialchars(isset($r->email)?$r->email:''); ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge bg-light text-dark">
                    <?php 
                    $periodParts = explode('-', $r->period);
                    echo date('M Y', mktime(0, 0, 0, $periodParts[1], 1, $periodParts[0])); 
                    ?>
                  </span>
                </td>
                <td class="text-end">
                  <span class="fw-medium text-success">₹<?php echo number_format((float)$r->gross,2); ?></span>
                </td>
                <td class="text-end">
                  <span class="fw-medium text-primary">₹<?php echo number_format((float)$r->net,2); ?></span>
                </td>
                <td>
                  <?php 
                  $generatedDate = isset($r->generated_at) ? strtotime($r->generated_at) : 0;
                  $daysAgo = $generatedDate ? floor((time() - $generatedDate) / (60 * 60 * 24)) : null;
                  if ($daysAgo !== null && $daysAgo <= 7) {
                    echo '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Recent</span>';
                  } elseif ($daysAgo !== null && $daysAgo <= 30) {
                    echo '<span class="badge bg-info"><i class="bi bi-clock me-1"></i>This Month</span>';
                  } else {
                    echo '<span class="badge bg-secondary"><i class="bi bi-calendar me-1"></i>Older</span>';
                  }
                  ?>
                </td>
                <td>
                  <div class="small">
                    <?php if (isset($r->generated_at) && $r->generated_at): ?>
                      <div><?php echo date('M j, Y', strtotime($r->generated_at)); ?></div>
                      <div class="text-muted"><?php echo date('h:i A', strtotime($r->generated_at)); ?></div>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm" role="group">
                    <a href="<?php echo site_url('payroll/view/'.(int)$r->id); ?>" class="btn btn-outline-primary" title="View Payslip">
                      <i class="bi bi-eye"></i>
                    </a>
                    <button type="button" class="btn btn-outline-success" onclick="sendSingleEmail(<?php echo (int)$r->id; ?>)" title="Email Payslip">
                      <i class="bi bi-envelope"></i>
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="downloadPayslip(<?php echo (int)$r->id; ?>)" title="Download PDF">
                      <i class="bi bi-download"></i>
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="duplicatePayslip(<?php echo (int)$r->id; ?>)" title="Duplicate">
                      <i class="bi bi-files"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
// Enhanced Payslips Management Script
document.addEventListener('DOMContentLoaded', function() {
    // Initialize statistics
    updateStatistics();
    
    // Setup event listeners
    setupEventListeners();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Check mobile view
    checkMobileView();
    
    // Add resize listener
    window.addEventListener('resize', checkMobileView);
});

// Check mobile view and adjust UI
function checkMobileView() {
    const isMobile = window.innerWidth < 768;
    const container = document.querySelector('.container-fluid');
    const statsCards = document.querySelectorAll('.col-md-3');
    const filterRow = document.querySelector('.row.g-2.mt-2');
    
    if (isMobile) {
        container.classList.remove('p-4');
        container.classList.add('px-3', 'py-2');
        
        // Make stats cards full width on mobile
        statsCards.forEach(card => {
            card.classList.remove('col-md-3');
            card.classList.add('col-6');
        });
        
        // Adjust filter layout for mobile
        if (filterRow) {
            filterRow.classList.remove('g-2');
            filterRow.classList.add('g-1');
        }
    } else {
        container.classList.remove('px-3', 'py-2');
        container.classList.add('p-4');
        
        // Restore desktop layout
        statsCards.forEach(card => {
            card.classList.remove('col-6');
            card.classList.add('col-md-3');
        });
        
        if (filterRow) {
            filterRow.classList.remove('g-1');
            filterRow.classList.add('g-2');
        }
    }
}

// Update statistics cards
function updateStatistics() {
    const rows = document.querySelectorAll('#payslipsTableBody tr[data-id]');
    const currentMonth = new Date().toISOString().slice(0, 7);
    let currentMonthCount = 0;
    let totalPayroll = 0;
    let avgSalary = 0;
    
    rows.forEach(row => {
        const period = row.dataset.period;
        const netAmount = parseFloat(row.querySelector('td:nth-child(6) span').textContent.replace('₹', '').replace(/,/g, ''));
        
        if (period === currentMonth) {
            currentMonthCount++;
        }
        
        totalPayroll += netAmount;
    });
    
    avgSalary = rows.length > 0 ? totalPayroll / rows.length : 0;
    
    // Update DOM with animation
    animateValue('totalPayslips', 0, rows.length, 1000);
    animateValue('currentMonthCount', 0, currentMonthCount, 1000);
    animateValue('totalPayroll', 0, totalPayroll, 1000, true);
    animateValue('avgSalary', 0, avgSalary, 1000, true);
}

// Animate counter values
function animateValue(id, start, end, duration, isCurrency = false) {
    const element = document.getElementById(id);
    if (!element) return;
    
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        
        if (isCurrency) {
            element.textContent = '₹' + numberFormat(current);
        } else {
            element.textContent = Math.round(current);
        }
    }, 16);
}

// Format numbers with commas
function numberFormat(num) {
    return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Setup event listeners
function setupEventListeners() {
    // Master checkbox
    const masterCheckbox = document.getElementById('selectAll');
    const individualCheckboxes = document.querySelectorAll('.payslip-checkbox');
    
    masterCheckbox.addEventListener('change', function() {
        individualCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedCount();
        updateSelectAllCheckbox();
    });
    
    // Individual checkboxes
    individualCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();
            updateSelectAllCheckbox();
        });
    });
    
    // Filter listeners
    document.getElementById('periodFilter').addEventListener('change', applyFilters);
    document.getElementById('userFilter').addEventListener('change', applyFilters);
    document.getElementById('searchFilter').addEventListener('input', debounce(applyFilters, 300));
    document.getElementById('statusFilter').addEventListener('change', applyFilters);
    document.getElementById('departmentFilter').addEventListener('change', applyFilters);
    document.getElementById('dateFromFilter').addEventListener('change', applyFilters);
    document.getElementById('dateToFilter').addEventListener('change', applyFilters);
    document.getElementById('salaryMinFilter').addEventListener('input', debounce(applyFilters, 500));
    document.getElementById('salaryMaxFilter').addEventListener('input', debounce(applyFilters, 500));
    document.getElementById('payModeFilter').addEventListener('change', applyFilters);
    
    // Mobile touch interactions
    setupMobileInteractions();
}

// Setup mobile interactions
function setupMobileInteractions() {
    const isMobile = window.innerWidth < 768;
    if (!isMobile) return;
    
    // Add touch feedback to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
        btn.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.95)';
        });
        btn.addEventListener('touchend', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Make table horizontally scrollable on mobile
    const tableContainer = document.querySelector('.table-responsive');
    if (tableContainer) {
        tableContainer.style.webkitOverflowScrolling = 'touch';
    }
}

// Update selected count
function updateSelectedCount() {
    const selected = document.querySelectorAll('.payslip-checkbox:checked').length;
    const countElement = document.getElementById('selectedCount');
    const sendButton = document.getElementById('sendEmailBtn');
    
    countElement.textContent = selected;
    sendButton.disabled = selected === 0;
}

// Apply filters
function applyFilters() {
    const period = document.getElementById('periodFilter').value;
    const user = document.getElementById('userFilter').value;
    const search = document.getElementById('searchFilter').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const department = document.getElementById('departmentFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    const dateTo = document.getElementById('dateToFilter').value;
    const salaryMin = document.getElementById('salaryMinFilter').value;
    const salaryMax = document.getElementById('salaryMaxFilter').value;
    const payMode = document.getElementById('payModeFilter').value;
    
    const rows = document.querySelectorAll('#payslipsTableBody tr[data-id]');
    let visibleCount = 0;
    
    rows.forEach(row => {
        let show = true;
        
        // Period filter
        if (period && row.dataset.period !== period) {
            show = false;
        }
        
        // User filter
        if (user && !row.dataset.user.toLowerCase().includes(user.toLowerCase())) {
            show = false;
        }
        
        // Search filter
        if (search && !row.dataset.user.toLowerCase().includes(search)) {
            show = false;
        }
        
        // Status filter
        if (status) {
            const generatedDate = row.querySelector('td:nth-child(8) div').textContent;
            const daysAgo = calculateDaysAgo(generatedDate);
            
            if (status === 'recent' && daysAgo > 30) show = false;
            if (status === 'current' && daysAgo > 31) show = false;
            if (status === 'previous' && (daysAgo < 31 || daysAgo > 62)) show = false;
        }
        
        // Date range filter
        if (dateFrom && row.dataset.period < dateFrom) {
            show = false;
        }
        if (dateTo && row.dataset.period > dateTo) {
            show = false;
        }
        
        // Salary range filter
        const netAmount = parseFloat(row.querySelector('td:nth-child(6) span').textContent.replace('₹', '').replace(/,/g, ''));
        if (salaryMin && netAmount < parseFloat(salaryMin)) {
            show = false;
        }
        if (salaryMax && netAmount > parseFloat(salaryMax)) {
            show = false;
        }
        
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });
    
    // Update statistics for filtered results
    updateFilteredStats(visibleCount);
}

// Calculate days ago
function calculateDaysAgo(dateText) {
    if (!dateText || dateText === '-') return Infinity;
    
    const date = new Date(dateText);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

// Update filtered statistics
function updateFilteredStats(visibleCount) {
    if (visibleCount === 0) {
        // Show no results message
        const tbody = document.getElementById('payslipsTableBody');
        if (!tbody.querySelector('.no-results')) {
            const noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results';
            noResultsRow.innerHTML = `
                <td colspan="9" class="text-center py-5">
                    <div class="text-muted">
                        <i class="bi bi-search fs-1 mb-3 d-block"></i>
                        <h5>No payslips found</h5>
                        <p>Try adjusting your filters</p>
                    </div>
                </td>
            `;
            tbody.appendChild(noResultsRow);
        }
    } else {
        // Remove no results message if exists
        const noResults = document.querySelector('.no-results');
        if (noResults) noResults.remove();
    }
}

// Reset filters
function resetFilters() {
    document.getElementById('periodFilter').value = '';
    document.getElementById('userFilter').value = '';
    document.getElementById('searchFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('departmentFilter').value = '';
    document.getElementById('dateFromFilter').value = '';
    document.getElementById('dateToFilter').value = '';
    document.getElementById('salaryMinFilter').value = '';
    document.getElementById('salaryMaxFilter').value = '';
    document.getElementById('payModeFilter').value = '';
    applyFilters();
}

// Send selected emails
function sendSelectedEmails() {
    const selected = document.querySelectorAll('.payslip-checkbox:checked');
    const ids = Array.from(selected).map(cb => cb.value);
    
    if (ids.length === 0) {
        showToast('Please select at least one payslip', 'warning');
        return;
    }
    
    // Show loading state
    const btn = document.getElementById('sendEmailBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Sending...';
    btn.disabled = true;
    
    // Show loading toast
    showToast('Sending ' + ids.length + ' payslip emails...', 'info');
    
    // Create form data
    const formData = new FormData();
    ids.forEach(id => {
        formData.append('ids[]', id);
    });
    
    // Send via AJAX for better UX
    fetch('<?php echo site_url('payroll/send_payslips'); ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Reset button
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        if (data.success) {
            showToast(data.message || 'Payslip emails sent successfully!', 'success');
            // Clear selection after successful send
            document.querySelectorAll('.payslip-checkbox:checked').forEach(cb => cb.checked = false);
            updateSelectAllCheckbox();
        } else {
            showToast('Failed to send payslip emails: ' + (data.message || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Reset button
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        // Fallback to form submission if AJAX fails
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo site_url('payroll/send_payslips'); ?>';
        
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    });
}

// Send single email
function sendSingleEmail(id) {
    // Show loading toast
    showToast('Sending payslip email...', 'info');
    
    // Create form data
    const formData = new FormData();
    formData.append('ids[]', id);
    
    // Send via AJAX for better UX
    fetch('<?php echo site_url('payroll/send_payslips'); ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Payslip email sent successfully!', 'success');
        } else {
            showToast('Failed to send payslip email: ' + (data.message || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Fallback to form submission if AJAX fails
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo site_url('payroll/send_payslips'); ?>';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
        
        document.body.appendChild(form);
        form.submit();
    });
}

// Download payslip PDF
function downloadPayslip(id) {
    showToast('Preparing payslip PDF for download...', 'info');
    
    // Create a temporary link to check if the URL is accessible
    const downloadUrl = '<?php echo site_url('payroll/view/'); ?>' + id + '?download=1';
    
    // Check if the download URL exists first
    fetch(downloadUrl, { method: 'HEAD' })
        .then(response => {
            if (response.ok) {
                // URL is accessible, proceed with download
                const downloadWindow = window.open(downloadUrl, '_blank');
                
                // Show success message after a short delay
                setTimeout(() => {
                    showToast('Payslip PDF download started successfully', 'success');
                }, 1000);
                
                // Focus on the download window
                if (downloadWindow) {
                    downloadWindow.focus();
                }
            } else {
                throw new Error('Payslip not found or not accessible');
            }
        })
        .catch(error => {
            console.error('Download error:', error);
            showToast('Failed to download payslip. Please try again.', 'danger');
        });
}

// Duplicate payslip
function duplicatePayslip(id) {
    if (confirm('Are you sure you want to duplicate this payslip?')) {
        // This would need to be implemented in the controller
        showToast('Duplicate functionality coming soon', 'info');
    }
}

// Export payslips
function exportPayslips(format) {
    const selected = document.querySelectorAll('.payslip-checkbox:checked');
    const ids = Array.from(selected).map(cb => cb.value);
    
    if (ids.length === 0) {
        showToast('Please select payslips to export', 'warning');
        return;
    }
    
    // Create export URL
    const params = new URLSearchParams({
        format: format,
        ids: ids.join(',')
    });
    
    window.open('<?php echo site_url('payroll/export'); ?>?' + params.toString(), '_blank');
}

// Update select all checkbox state
function updateSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.payslip-checkbox');
    const checkedBoxes = document.querySelectorAll('.payslip-checkbox:checked');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = checkboxes.length > 0 && checkboxes.length === checkedBoxes.length;
        selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
    }
}

// Initialize tooltips
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Show toast notification
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : type === 'danger' ? 'danger' : 'info'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

// Create toast container
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1080';
    document.body.appendChild(container);
    return container;
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>

<style>
/* Enhanced Mobile Responsiveness */
@media (max-width: 768px) {
    .container-fluid {
        padding: 0.5rem !important;
    }
    
    .card-body {
        padding: 1rem !important;
    }
    
    .btn-group-sm .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .avatar-sm {
        width: 32px !important;
        height: 32px !important;
        font-size: 0.75rem !important;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
    
    .d-flex.gap-2 .btn-group {
        width: 100%;
    }
    
    .d-flex.gap-2 .btn-group .btn {
        flex: 1;
    }
}

/* Enhanced Animations */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.btn {
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}

/* Loading States */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Enhanced Accessibility */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    white-space: nowrap;
    border: 0;
}

.btn:focus-visible {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

.form-control:focus-visible {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

/* Print Styles */
@media print {
    .no-print {
        display: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
    
    .table {
        font-size: 0.8rem;
    }
}
</style>
<?php $this->load->view('partials/footer'); ?>
