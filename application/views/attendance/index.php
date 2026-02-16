<?php 
  // Get current user role for filtering
  $user_id = (int)$this->session->userdata('user_id');
  $role_id = (int)$this->session->userdata('role_id');
  $isAdminGroup = (function_exists('is_admin_group') && is_admin_group());
  $canViewAll = isset($can_view_all) ? $can_view_all : ($isAdminGroup || in_array($role_id, [1,2], true));
  $canAddAttendance = isset($can_add_attendance) ? $can_add_attendance : $canViewAll;
  
  // Get permission flags from controller
  $canEditAttendance = isset($can_edit_attendance) ? $can_edit_attendance : false;
  $canDeleteAttendance = isset($can_delete_attendance) ? $can_delete_attendance : false;
  $currentUserId = isset($current_user_id) ? $current_user_id : $user_id;
  $isAdmin = isset($is_admin_group) ? $is_admin_group : $isAdminGroup;
  $currentRoleId = isset($current_role_id) ? $current_role_id : $role_id;
  
  $this->load->view('partials/header', ['title' => 'Attendance']); 
?>

<!-- Toast Container for Flash Messages -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
  <?php if($this->session->flashdata('success')): ?>
    <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
      <div class="d-flex">
        <div class="toast-body">
          <i class="bi bi-check-circle-fill me-2"></i>
          <strong>Success!</strong> <?php echo htmlspecialchars($this->session->flashdata('success')); ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  <?php endif; ?>
  <?php if($this->session->flashdata('error')): ?>
    <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
      <div class="d-flex">
        <div class="toast-body">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>Error:</strong> <?php echo htmlspecialchars($this->session->flashdata('error')); ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // Wait for Bootstrap to be fully loaded
  function showToastNotifications() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Toast) {
      // Wait a bit more if Bootstrap isn't loaded yet
      setTimeout(showToastNotifications, 100);
      return;
    }
    
    // Find toast container - use the specific one for this page
    var toastContainer = document.querySelector('.toast-container.position-fixed');
    if (!toastContainer) {
      toastContainer = document.querySelector('.toast-container');
    }
    
    if (toastContainer) {
      // Auto-show toast notifications on page load
      var toastElements = toastContainer.querySelectorAll('.toast');
      toastElements.forEach(function(toastEl) {
        try {
          var delay = toastEl.getAttribute('data-bs-delay') ? parseInt(toastEl.getAttribute('data-bs-delay')) : 3000;
          var toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: delay
          });
          toast.show();
          
          // Remove toast element after it's hidden
          toastEl.addEventListener('hidden.bs.toast', function() {
            toastEl.remove();
          });
        } catch(e) {
          console.error('Error showing toast:', e);
        }
      });
    }
  }
  
  // Try to show toasts immediately, then retry if Bootstrap isn't ready
  showToastNotifications();
  
  // Also try after a short delay to ensure Bootstrap is loaded
  setTimeout(showToastNotifications, 200);
});
</script>

<?php if (!$canViewAll): ?>
<div class="alert alert-info d-flex align-items-center mb-3" role="alert">
  <i class="bi bi-info-circle me-2"></i>
  <div>
    <strong>Group View:</strong> Showing attendance for your department/team only.
  </div>
</div>
<?php endif; ?>

<style>
.attendance-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0.5rem;
}
.attendance-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem;
  border-radius: 12px;
  margin-bottom: 1rem;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}
.attendance-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0;
}
.attendance-subtitle {
  opacity: 0.9;
  margin: 0.25rem 0 0 0;
  font-size: 0.75rem;
}
.filter-toggle {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 0.75rem;
  margin-bottom: 0.75rem;
  cursor: pointer;
  transition: all 0.3s ease;
}
.filter-toggle:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.filter-toggle-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.filter-toggle-title {
  font-weight: 600;
  color: #374151;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}
.filter-toggle-arrow {
  transition: transform 0.3s ease;
  color: #6b7280;
}
.filter-toggle.collapsed .filter-toggle-arrow {
  transform: rotate(-90deg);
}
.filter-content {
  padding-top: 0.75rem;
  display: grid;
  gap: 0.75rem;
}
.filter-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.75rem;
}
.filter-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.attendance-table-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.attendance-table {
  margin: 0;
  border-collapse: collapse;
  width: 100%;
}
.attendance-table thead {
  background: #f8f9fa;
}
.attendance-table thead th {
  padding: 0.75rem 0.5rem;
  text-align: left;
  font-weight: 600;
  color: #495057;
  font-size: 0.75rem;
  border-bottom: 2px solid #e9ecef;
  white-space: nowrap;
}
.attendance-table tbody td {
  padding: 0.5rem;
  border-bottom: 1px solid #f1f3f4;
  vertical-align: middle;
  font-size: 0.875rem;
}
.attendance-table tbody tr:hover {
  background: #f8f9fa;
}
.attendance-table tbody tr:last-child td {
  border-bottom: none;
}
.user-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.75rem;
  flex-shrink: 0;
}
.user-details {
  min-width: 0;
}
.user-name {
  font-weight: 600;
  color: #1f2937;
  margin: 0;
  font-size: 0.75rem;
}
.user-email {
  color: #6b7280;
  font-size: 0.625rem;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.time-badge {
  background: #e3f2fd;
  color: #1976d2;
  padding: 0.125rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  display: inline-block;
}
.time-badge.checkout {
  background: #f3e5f5;
  color: #7b1fa2;
}
.status-badge {
  padding: 0.125rem 0.5rem;
  border-radius: 12px;
  font-size: 0.625rem;
  font-weight: 600;
  text-transform: uppercase;
}
.status-badge.present {
  background: #d4edda;
  color: #155724;
}
.status-badge.absent {
  background: #f8d7da;
  color: #721c24;
}
.status-badge.incomplete {
  background: #fff3cd;
  color: #856404;
}
.status-badge.late {
  background: #fff3cd;
  color: #856404;
}
.status-badge.early_leave {
  background: #f8d7da;
  color: #721c24;
}
.location-info {
  max-width: 150px;
}
.location-name {
  font-size: 0.75rem;
  color: #374151;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.location-coords {
  font-size: 0.625rem;
  color: #6b7280;
  margin: 0;
}
.notes-cell {
  max-width: 120px;
}
.notes-text {
  font-size: 0.75rem;
  color: #374151;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin: 0;
}
.action-buttons {
  display: flex;
  gap: 0.25rem;
}
.empty-state {
  text-align: center;
  padding: 2rem 0.5rem;
  color: #6b7280;
}
.empty-state-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  color: #d1d5db;
}
.empty-state-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
}
.empty-state-text {
  font-size: 0.75rem;
  color: #9ca3af;
}

/* Export Actions Bar */
.export-actions-bar {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 0.75rem 1rem;
}

/* Checkbox Styles */
.row-checkbox {
  cursor: pointer;
  width: 18px;
  height: 18px;
}

#selectAll {
  cursor: pointer;
  width: 18px;
  height: 18px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .attendance-container {
    padding: 0.25rem;
  }
  .attendance-header {
    padding: 0.75rem;
    text-align: center;
    flex-direction: column;
    gap: 0.5rem;
  }
  .attendance-header > div {
    width: 100%;
    text-align: center;
  }
  .attendance-title {
    font-size: 1.125rem;
  }
  .attendance-subtitle {
    font-size: 0.625rem;
  }
  .filter-toggle {
    padding: 0.5rem;
    margin-bottom: 0.5rem;
  }
  .filter-row {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
  .filter-actions {
    justify-content: center;
    gap: 0.25rem;
  }
  .attendance-table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .attendance-table {
    min-width: 100%;
    display: block;
  }
  .attendance-table thead,
  .attendance-table tbody,
  .attendance-table tr {
    display: block;
  }
  .attendance-table thead {
    display: none;
  }
  .attendance-table tbody tr {
    margin-bottom: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.75rem;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .attendance-table tbody td {
    display: block;
    padding: 0.5rem 0;
    border: none;
    text-align: left;
    font-size: 0.875rem;
  }
  .attendance-table tbody td:before {
    content: attr(data-label);
    font-weight: 600;
    color: #6b7280;
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.75rem;
  }
  .attendance-table tbody td[data-label=""]:before {
    content: "";
    display: none;
  }
  .export-actions-bar {
    flex-direction: column;
    align-items: stretch !important;
  }
  .export-actions-bar > div {
    flex-wrap: wrap;
    justify-content: center;
  }
  .export-actions-bar button {
    flex: 1;
    min-width: 120px;
  }
  .user-cell {
    flex-direction: row;
    align-items: center;
    gap: 0.5rem;
  }
  .user-avatar {
    width: 40px;
    height: 40px;
    font-size: 0.875rem;
  }
  .user-name {
    font-size: 0.875rem;
  }
  .user-email {
    font-size: 0.625rem;
  }
  .time-badge,
  .status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
  .action-buttons {
    justify-content: flex-start;
  }
  .modal-dialog {
    max-width: 95% !important;
    margin: 0.5rem;
  }
  .modal-body .row {
    margin: 0;
  }
  .modal-body .col-md-4 {
    padding: 0.25rem;
    margin-bottom: 0.5rem;
  }
}
    font-size: 0.5rem;
  }
  .time-badge,
  .status-badge {
    font-size: 0.625rem;
    padding: 0.125rem 0.375rem;
  }
  .location-info {
    max-width: 100px;
  }
  .location-name {
    font-size: 0.625rem;
  }
  .location-coords {
    font-size: 0.5rem;
  }
  .notes-cell {
    max-width: 80px;
  }
  .notes-text {
    font-size: 0.625rem;
  }
  .action-buttons .btn {
    padding: 0.125rem 0.25rem;
    font-size: 0.75rem;
  }
}

@media (max-width: 480px) {
  .attendance-table {
    min-width: 450px;
  }
  .attendance-table thead th,
  .attendance-table tbody td {
    padding: 0.375rem 0.125rem;
    font-size: 0.625rem;
  }
  .time-badge,
  .status-badge {
    font-size: 0.5rem;
    padding: 0.0625rem 0.25rem;
  }
}
</style>

<div class="attendance-container">
  <!-- Header -->
  <div class="attendance-header">
    <div class="d-flex justify-content-between align-items-center w-100">
      <div>
        <h1 class="attendance-title">Attendance Summary</h1>
        <p class="attendance-subtitle">
          Showing attendance summary for all employees
          <?php if (isset($total_records)): ?>
            <span class="ms-2">(<?php echo $total_records; ?> employees)</span>
          <?php endif; ?>
        </p>
      </div>
      <div class="d-flex gap-2">
        <?php if ($canAddAttendance): ?>
          <a class="btn btn-light btn-sm" title="Add Attendance" href="<?php echo site_url('attendance/create'); ?>">
            <i class="bi bi-plus-lg"></i> Add
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Export Actions Bar -->
  <div class="export-actions-bar mb-3" id="exportActionsBar" style="display: none;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <span class="text-muted small" id="selectedCount">0 selected</span>
      <div class="d-flex align-items-center gap-2">
        <button type="button"
                class="btn btn-sm btn-success d-flex justify-content-center align-items-center"
                onclick="exportSelected('excel')"
                title="Export selected to Excel">
          <i class="bi bi-file-earmark-excel"></i>
        </button>
        <button type="button"
                class="btn btn-sm btn-danger d-flex justify-content-center align-items-center"
                onclick="exportSelected('pdf')"
                title="Export selected to PDF">
          <i class="bi bi-file-earmark-pdf"></i>
        </button>
        <button type="button"
                class="btn btn-sm btn-outline-secondary d-flex justify-content-center align-items-center"
                onclick="clearSelection()"
                title="Clear selection">
          <i class="bi bi-x-circle"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Attendance Table -->
  <div class="attendance-table-container">
    <table class="attendance-table" id="attendanceTable">
      <thead>
        <tr>
          <th style="width: 40px;">
            <input type="checkbox" id="selectAll" title="Select All" onchange="toggleSelectAll(this)">
          </th>
          <th>Employee</th>
          <th>Last Attendance</th>
          <th>Total Records</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if(!empty($records)) foreach($records as $r): ?>
          <?php 
            // Get name and email ONLY from users table
            $display_name = '';
            if (!empty($r->user_name)) {
              $display_name = trim($r->user_name);
            }
            
            // Get email from users table
            $email = isset($r->email) && $r->email !== '' ? $r->email : '';
            
            // If no name, use email as fallback, or 'Unknown'
            if (empty($display_name)) {
              $display_name = !empty($email) ? $email : 'Unknown';
            }
            
            // For avatar, use first letter of display name
            $avatar_letter = strtoupper(substr($display_name, 0, 1));
            
            $last_attendance_date = isset($r->last_attendance_date) ? $r->last_attendance_date : '';
            $attendance_count = isset($r->attendance_count) ? $r->attendance_count : 0;
          ?>
          <tr data-user-id="<?php echo $r->user_id; ?>" onclick="handleRowClick(event, <?php echo $r->user_id; ?>, '<?php echo htmlspecialchars($display_name); ?>')" style="cursor: pointer;">
            <td data-label="Select" onclick="event.stopPropagation();">
              <input type="checkbox" class="row-checkbox" value="<?php echo $r->user_id; ?>" onchange="updateSelection()" onclick="event.stopPropagation();">
            </td>
            <td data-label="Employee">
              <div class="user-cell">
                <div class="user-avatar">
                  <?php echo htmlspecialchars($avatar_letter); ?>
                </div>
                <div class="user-details">
                  <p class="user-name"><?php echo htmlspecialchars($display_name); ?></p>
                  <?php if (!empty($email) && $display_name !== $email): ?>
                    <p class="user-email"><?php echo htmlspecialchars($email); ?></p>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td data-label="Last Attendance">
              <?php if ($last_attendance_date): ?>
                <span class="time-badge"><?php echo htmlspecialchars($last_attendance_date); ?></span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td data-label="Total Records">
              <span class="status-badge present"><?php echo $attendance_count; ?></span>
            </td>
            <td data-label="Actions">
              <div class="action-buttons">
                <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); showUserAttendanceDetails(<?php echo $r->user_id; ?>, '<?php echo htmlspecialchars($display_name); ?>')" title="View Details">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        
        <?php if(empty($records)): ?>
          <tr>
            <td colspan="5" class="text-center">
              <div class="empty-state">
                <i class="bi bi-calendar-x empty-state-icon"></i>
                <div class="empty-state-title">No attendance records found</div>
                <div class="empty-state-text">Start by adding attendance records</div>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <!-- Pagination -->
  <?php if (isset($pagination_links) && $total_records > $per_page): ?>
    <div class="pagination-info text-center mb-2">
      <small class="text-muted">
        Showing <?php echo isset($current_page) ? $current_page : 1; ?> to <?php echo ceil($total_records / $per_page); ?> of <?php echo $total_records; ?> records
      </small>
    </div>
    <?php echo $pagination_links; ?>
  <?php endif; ?>
</div>

<!-- Attendance Details Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" style="max-width: 85%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="attendanceModalLabel">Attendance Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Filter Type</label>
            <select id="filterType" class="form-select">
              <option value="month">By Month</option>
              <option value="date">By Date</option>
              <option value="year">By Year</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Select Date/Month/Year</label>
            <input type="text" id="filterValue" class="form-control" placeholder="Select...">
          </div>
          <div class="col-md-4">
            <label class="form-label">&nbsp;</label><br>
            <button type="button" class="btn btn-primary" onclick="loadAttendanceDetails()">
              <i class="bi bi-search"></i> Search
            </button>
          </div>
        </div>
        
        <div class="table-responsive">
          <table class="table table-sm" id="attendanceDetailsTable">
            <thead>
              <tr>
                <th>Date</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Check-In Location</th>
                <th>Check-Out Location</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="attendanceDetailsBody">
              <tr>
                <td colspan="7" class="text-center">
                  <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  Loading attendance details...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination Controls -->
        <div id="attendancePagination" class="d-flex justify-content-between align-items-center mt-3" style="display: none;">
          <div class="text-muted">
            <small id="paginationInfo">Showing 0 of 0 records</small>
          </div>
          <nav>
            <ul class="pagination pagination-sm mb-0" id="paginationLinks">
              <!-- Pagination links will be inserted here -->
            </ul>
          </nav>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
let currentUserId = null;
let currentUserName = null;
let currentPage = 1;
// Permission flags from PHP
const canEditAttendance = <?php echo $canEditAttendance ? 'true' : 'false'; ?>;
const canDeleteAttendance = <?php echo $canDeleteAttendance ? 'true' : 'false'; ?>;
const currentUserID = <?php echo $currentUserId; ?>;
const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;

function showUserAttendanceDetails(userId, userName) {
    currentUserId = userId;
    currentUserName = userName;
    currentPage = 1; // Reset to first page when opening new user
    
    // Update modal title
    document.getElementById('attendanceModalLabel').textContent = `Attendance Details - ${userName}`;
    
    // Set default filter to current year
    const today = new Date();
    const currentYear = today.getFullYear().toString();
    const filterType = document.getElementById('filterType');
    const filterValue = document.getElementById('filterValue');
    
    filterType.value = 'year';
    filterValue.type = 'number';
    filterValue.min = '2020';
    filterValue.max = today.getFullYear().toString();
    filterValue.value = currentYear;
    filterValue.placeholder = 'Enter year (e.g., 2024)';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('attendanceModal'));
    modal.show();
    
    // Load attendance details
    loadAttendanceDetails();
}

function loadAttendanceDetails(page = 1) {
    const filterType = document.getElementById('filterType').value;
    const filterValue = document.getElementById('filterValue').value;
    
    if (!filterValue) {
        // Don't show alert, just return silently
        return;
    }
    
    currentPage = page;
    
    const tbody = document.getElementById('attendanceDetailsBody');
    const paginationDiv = document.getElementById('attendancePagination');
    
    // Hide pagination during loading
    paginationDiv.style.display = 'none';
    
    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                Loading attendance details...
            </td>
        </tr>
    `;
    
    // Fetch attendance data
    fetch('<?php echo site_url('attendance/get_user_monthly_attendance'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `user_id=${currentUserId}&filter_type=${filterType}&filter_value=${filterValue}&page=${currentPage}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAttendanceDetails(data.data, data.pagination);
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-danger">
                        ${data.message || 'Error loading attendance details'}
                    </td>
                </tr>
            `;
            paginationDiv.style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-danger">
                    Error loading attendance details
                </td>
            </tr>
        `;
        paginationDiv.style.display = 'none';
    });
}

function displayAttendanceDetails(data, pagination) {
    const tbody = document.getElementById('attendanceDetailsBody');
    const paginationDiv = document.getElementById('attendancePagination');
    
    if (data.length === 0) {
        tbody.innerHTML = `
              <tr>
                <td colspan="8" class="text-center text-muted">
                    No attendance records found for the selected period
                </td>
              </tr>
        `;
        paginationDiv.style.display = 'none';
        return;
    }
    
    let html = '';
    data.forEach(record => {
        let statusClass = 'secondary';
        switch(record.status) {
            case 'present': statusClass = 'success'; break;
            case 'absent': statusClass = 'danger'; break;
            case 'late': statusClass = 'warning'; break;
            case 'early_leave': statusClass = 'warning'; break; // or danger/info
            case 'half_day': statusClass = 'info'; break;
            default: statusClass = 'warning'; // incomplete
        }
        
        let statusText = record.status.charAt(0).toUpperCase() + record.status.slice(1).replace('_', ' ');
        
        // Check permissions for this record
        const canEdit = record.can_edit !== undefined ? record.can_edit : false;
        const canDelete = record.can_delete !== undefined ? record.can_delete : false;
        
        // Build action buttons HTML
        let actionButtons = '<div class="btn-group btn-group-sm" role="group">';
        if (canEdit) {
            actionButtons += `<button class="btn btn-outline-primary btn-sm" onclick="editAttendance(${record.id})" title="Edit">
                <i class="bi bi-pencil"></i>
            </button>`;
        }
        if (canDelete) {
            actionButtons += `<button class="btn btn-outline-danger btn-sm" onclick="deleteAttendance(${record.id})" title="Delete">
                <i class="bi bi-trash"></i>
            </button>`;
        }
        if (!canEdit && !canDelete) {
            actionButtons += '<span class="text-muted small">No actions</span>';
        }
        actionButtons += '</div>';
        
        html += `
            <tr>
                <td>${record.date}</td>
                <td>${record.check_in || '<span class="text-muted">—</span>'}</td>
                <td>${record.check_out || '<span class="text-muted">—</span>'}</td>
                <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                <td>${record.notes || '<span class="text-muted">—</span>'}</td>
                <td>${record.checkin_location || '<span class="text-muted">—</span>'}</td>
                <td>${record.checkout_location || '<span class="text-muted">—</span>'}</td>
                <td>${actionButtons}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Update pagination
    if (pagination.total_pages > 1) {
        updatePaginationControls(pagination);
        paginationDiv.style.display = 'flex';
    } else {
        paginationDiv.style.display = 'none';
    }
}

function updatePaginationControls(pagination) {
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationLinks = document.getElementById('paginationLinks');
    
    // Update info text
    const startRecord = (pagination.current_page - 1) * pagination.per_page + 1;
    const endRecord = Math.min(pagination.current_page * pagination.per_page, pagination.total_records);
    paginationInfo.textContent = `Showing ${startRecord}-${endRecord} of ${pagination.total_records} records`;
    
    // Generate pagination links
    let linksHtml = '';
    
    // Previous button
    if (pagination.has_prev) {
        linksHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadAttendanceDetails(${pagination.current_page - 1}); return false;">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
        `;
    } else {
        linksHtml += `
            <li class="page-item disabled">
                <span class="page-link"><i class="bi bi-chevron-left"></i></span>
            </li>
        `;
    }
    
    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, pagination.current_page - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(pagination.total_pages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    if (startPage > 1) {
        linksHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadAttendanceDetails(1); return false;">1</a>
            </li>
        `;
        if (startPage > 2) {
            linksHtml += `
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
            `;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === pagination.current_page ? 'active' : '';
        linksHtml += `
            <li class="page-item ${activeClass}">
                <a class="page-link" href="#" onclick="loadAttendanceDetails(${i}); return false;">${i}</a>
            </li>
        `;
    }
    
    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            linksHtml += `
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
            `;
        }
        linksHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadAttendanceDetails(${pagination.total_pages}); return false;">${pagination.total_pages}</a>
            </li>
        `;
    }
    
    // Next button
    if (pagination.has_next) {
        linksHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadAttendanceDetails(${pagination.current_page + 1}); return false;">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        `;
    } else {
        linksHtml += `
            <li class="page-item disabled">
                <span class="page-link"><i class="bi bi-chevron-right"></i></span>
            </li>
        `;
    }
    
    paginationLinks.innerHTML = linksHtml;
}

function editAttendance(id) {
    // Close the modal first
    const modal = bootstrap.Modal.getInstance(document.getElementById('attendanceModal'));
    modal.hide();
    
    // Redirect to edit page
    window.location.href = '<?php echo site_url('attendance/'); ?>' + id + '/edit';
}

function deleteAttendance(id) {
    if (confirm('Are you sure you want to delete this attendance record?')) {
        // Close the modal first
        const modal = bootstrap.Modal.getInstance(document.getElementById('attendanceModal'));
        modal.hide();
        
        // Redirect to delete
        window.location.href = '<?php echo site_url('attendance/'); ?>' + id + '/delete';
    }
}

// Initialize filter value input based on filter type
document.addEventListener('DOMContentLoaded', function() {
    const filterType = document.getElementById('filterType');
    const filterValue = document.getElementById('filterValue');
    
    function updateFilterInput() {
        const type = filterType.value;
        const today = new Date();
        
        switch(type) {
            case 'date':
                filterValue.type = 'date';
                if (!filterValue.value) {
                    filterValue.value = today.toISOString().split('T')[0];
                }
                break;
            case 'month':
                filterValue.type = 'month';
                if (!filterValue.value) {
                    filterValue.value = today.toISOString().slice(0, 7);
                }
                break;
            case 'year':
                filterValue.type = 'number';
                filterValue.min = '2020';
                filterValue.max = today.getFullYear().toString();
                if (!filterValue.value) {
                    filterValue.value = today.getFullYear().toString();
                }
                filterValue.placeholder = 'Enter year (e.g., 2024)';
                break;
        }
        
        // Reset to first page when filter type changes
        currentPage = 1;
    }
    
    filterType.addEventListener('change', updateFilterInput);
      // Don't call updateFilterInput() on load since modal will set its own values
  });

  // Checkbox Selection Functions
  function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => {
      cb.checked = checkbox.checked;
    });
    updateSelection();
  }

  function updateSelection() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const count = checkboxes.length;
    const exportBar = document.getElementById('exportActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    
    if (count > 0) {
      exportBar.style.display = 'block';
      selectedCount.textContent = count + ' selected';
    } else {
      exportBar.style.display = 'none';
    }
    
    // Update select all checkbox
    const selectAll = document.getElementById('selectAll');
    const allCheckboxes = document.querySelectorAll('.row-checkbox');
    selectAll.checked = allCheckboxes.length > 0 && checkboxes.length === allCheckboxes.length;
  }

  function handleRowClick(event, userId, userName) {
    // Don't trigger if clicking on checkbox or button
    if (event.target.type === 'checkbox' || event.target.closest('.btn') || event.target.closest('.action-buttons')) {
      return;
    }
    showUserAttendanceDetails(userId, userName);
  }

  function clearSelection() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => {
      cb.checked = false;
    });
    document.getElementById('selectAll').checked = false;
    updateSelection();
  }

  function exportSelected(format) {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
      alert('Please select at least one employee to export.');
      return;
    }
    
    const userIds = Array.from(checkboxes).map(cb => cb.value);
    const userIdsStr = userIds.join(',');
    
    // Show loading message
    const exportBar = document.getElementById('exportActionsBar');
    const originalHTML = exportBar.innerHTML;
    exportBar.innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split"></i> Preparing export...</div>';
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo site_url("attendance/export"); ?>';
    
    const formatInput = document.createElement('input');
    formatInput.type = 'hidden';
    formatInput.name = 'format';
    formatInput.value = format;
    form.appendChild(formatInput);
    
    const userIdsInput = document.createElement('input');
    userIdsInput.type = 'hidden';
    userIdsInput.name = 'user_ids';
    userIdsInput.value = userIdsStr;
    form.appendChild(userIdsInput);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    // Restore original HTML after a delay
    setTimeout(() => {
      exportBar.innerHTML = originalHTML;
    }, 2000);
  }
</script>

<?php $this->load->view('partials/footer'); ?>
