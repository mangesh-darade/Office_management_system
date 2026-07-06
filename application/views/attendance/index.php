<?php 
  // Get current user role for filtering
  $user_id = (int)$this->session->userdata('user_id');
  $role_id = (int)$this->session->userdata('role_id');
  $isAdminGroup = (function_exists('is_admin_group') && is_admin_group());
  $canViewAll = isset($can_view_all) ? $can_view_all : ($isAdminGroup || in_array($role_id, [1,2], true));
  $canAddAttendance = isset($can_add_attendance) ? $can_add_attendance : $canViewAll;
  $user_tab = (isset($user_tab) && $user_tab === 'inactive') ? 'inactive' : 'active';
  $isInactiveTab = ($user_tab === 'inactive');
  $activeTabUrl = site_url('attendance?tab=active');
  $inactiveTabUrl = site_url('attendance?tab=inactive');
  
  // Get permission flags from controller
  $canEditAttendance = isset($can_edit_attendance) ? $can_edit_attendance : false;
  $canDeleteAttendance = isset($can_delete_attendance) ? $can_delete_attendance : false;
  $currentUserId = isset($current_user_id) ? $current_user_id : $user_id;
  $isAdmin = isset($is_admin_group) ? $is_admin_group : $isAdminGroup;
  $currentRoleId = isset($current_role_id) ? $current_role_id : $role_id;
  
  $this->load->view('partials/header', array(
    'title' => 'Attendance',
    'extra_css' => array('assets/css/attendance-index.css'),
  ));
  $employee_count = isset($total_records) ? (int) $total_records : 0;
  $summary_scope = $canViewAll
    ? ($isInactiveTab ? 'inactive employees' : 'active employees')
    : ($isInactiveTab ? 'your inactive department/team' : 'your active department/team');
  $canExport = isset($can_export_attendance)
    ? (bool) $can_export_attendance
    : (function_exists('can_access_attendance_export') && can_access_attendance_export());
?>

<!-- Flash messages are handled by the global toast container in partials/header.php -->

<?php if (!$canViewAll): ?>
<div class="alert alert-info d-flex align-items-center mb-3" role="alert">
  <i class="bi bi-info-circle me-2"></i>
  <div>
    <strong>Group View:</strong> Showing attendance for your department/team only.
  </div>
</div>
<?php endif; ?>


<div class="att-page">
  <div class="att-hero">
    <div class="att-hero-top">
      <div>
        <h1 class="att-hero-title">Attendance Summary</h1>
        <p class="att-hero-subtitle">Overview for <?php echo esc_view($summary_scope); ?>.</p>
        <?php if ($employee_count > 0): ?>
          <span class="att-hero-count"><?php echo $employee_count; ?> employees</span>
        <?php endif; ?>
      </div>
      <?php if ($canAddAttendance): ?>
        <a class="btn btn-light att-hero-add" title="Mark attendance" href="<?php echo site_url('attendance/create'); ?>">
          <i class="bi bi-plus-lg"></i><span class="d-none d-lg-inline ms-1">Add</span>
        </a>
      <?php endif; ?>
    </div>
    <?php if ($canAddAttendance): ?>
      <div class="att-hero-actions-mobile">
        <a class="btn btn-light" href="<?php echo site_url('attendance/create'); ?>">
          <i class="bi bi-plus-lg me-1"></i>Mark attendance
        </a>
      </div>
    <?php endif; ?>
  </div>

  <ul class="nav nav-tabs att-status-tabs mb-3">
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

  <?php if ($canExport): ?>
  <div class="att-export-bar mb-3" id="exportActionsBar" style="display: none;">
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
  <?php endif; ?>

  <?php if ($canExport && !empty($records)): ?>
  <div class="att-mobile-toolbar d-md-none">
    <label for="selectAllMobile">
      <input type="checkbox" id="selectAllMobile" title="Select all" onchange="toggleSelectAll(this)">
      Select all
    </label>
    <span class="text-muted small"><?php echo $employee_count; ?> employees</span>
  </div>
  <?php endif; ?>

  <div class="att-mobile-list d-md-none">
    <?php if (!empty($records)) foreach ($records as $r): ?>
      <?php
        $display_name = !empty($r->user_name) ? trim($r->user_name) : '';
        $email = isset($r->email) && $r->email !== '' ? $r->email : '';
        if ($display_name === '') {
          $display_name = $email !== '' ? $email : 'Unknown';
        }
        $avatar_letter = strtoupper(substr($display_name, 0, 1));
        $last_attendance_date = isset($r->last_attendance_date) ? $r->last_attendance_date : '';
        $attendance_count = isset($r->attendance_count) ? (int) $r->attendance_count : 0;
        $name_js = esc_view($display_name);
      ?>
      <div class="att-mobile-card" data-user-id="<?php echo (int) $r->user_id; ?>" onclick="handleRowClick(event, <?php echo (int) $r->user_id; ?>, '<?php echo $name_js; ?>')">
        <div class="att-mobile-card-top<?php echo $canExport ? ' has-select' : ''; ?>">
          <?php if ($canExport): ?>
          <input type="checkbox" class="row-checkbox" value="<?php echo (int) $r->user_id; ?>" onchange="syncRowCheckbox(this); updateSelection()" onclick="event.stopPropagation();" aria-label="Select <?php echo esc_view($display_name); ?>">
          <?php endif; ?>
          <div class="att-user">
            <div class="att-user-avatar"><?php echo esc_view($avatar_letter); ?></div>
            <div class="min-width-0">
              <p class="att-user-name"><?php echo esc_view($display_name); ?></p>
              <?php if ($email !== '' && $display_name !== $email): ?>
                <p class="att-user-email"><?php echo esc_view($email); ?></p>
              <?php endif; ?>
            </div>
          </div>
          <button type="button" class="btn btn-outline-primary att-mobile-view-btn" onclick="event.stopPropagation(); showUserAttendanceDetails(<?php echo (int) $r->user_id; ?>, '<?php echo $name_js; ?>')" title="View details" aria-label="View details">
            <i class="bi bi-eye"></i>
          </button>
        </div>
        <div class="att-mobile-meta">
          <div class="att-mobile-meta-item">
            <span class="att-mobile-meta-label">Last attendance</span>
            <?php if ($last_attendance_date): ?>
              <span class="att-date-badge"><?php echo esc_view($last_attendance_date); ?></span>
            <?php else: ?>
              <span class="text-muted">None</span>
            <?php endif; ?>
          </div>
          <div class="att-mobile-meta-item">
            <span class="att-mobile-meta-label">Total records</span>
            <span class="att-count-badge"><?php echo $attendance_count; ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if (empty($records)): ?>
      <div class="att-empty">
        <i class="bi bi-calendar-x d-block"></i>
        <div class="fw-semibold">No attendance records found</div>
        <div class="small"><?php echo $isInactiveTab ? 'No inactive employees with attendance records.' : 'No active employees with attendance records.'; ?></div>
      </div>
    <?php endif; ?>
  </div>

  <div class="att-table-wrap d-none d-md-block">
    <table class="att-table" id="attendanceTable">
      <thead>
        <tr>
          <?php if ($canExport): ?>
          <th style="width: 40px;">
            <input type="checkbox" id="selectAll" title="Select all" onchange="toggleSelectAll(this)">
          </th>
          <?php endif; ?>
          <th>Employee</th>
          <th>Last attendance</th>
          <th>Total records</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($records)) foreach ($records as $r): ?>
          <?php
            $display_name = !empty($r->user_name) ? trim($r->user_name) : '';
            $email = isset($r->email) && $r->email !== '' ? $r->email : '';
            if ($display_name === '') {
              $display_name = $email !== '' ? $email : 'Unknown';
            }
            $avatar_letter = strtoupper(substr($display_name, 0, 1));
            $last_attendance_date = isset($r->last_attendance_date) ? $r->last_attendance_date : '';
            $attendance_count = isset($r->attendance_count) ? (int) $r->attendance_count : 0;
            $name_js = esc_view($display_name);
          ?>
          <tr data-user-id="<?php echo (int) $r->user_id; ?>" onclick="handleRowClick(event, <?php echo (int) $r->user_id; ?>, '<?php echo $name_js; ?>')" style="cursor: pointer;">
            <?php if ($canExport): ?>
            <td onclick="event.stopPropagation();">
              <input type="checkbox" class="row-checkbox" value="<?php echo (int) $r->user_id; ?>" onchange="syncRowCheckbox(this); updateSelection()" onclick="event.stopPropagation();">
            </td>
            <?php endif; ?>
            <td>
              <div class="att-user">
                <div class="att-user-avatar"><?php echo esc_view($avatar_letter); ?></div>
                <div class="min-width-0">
                  <p class="att-user-name"><?php echo esc_view($display_name); ?></p>
                  <?php if ($email !== '' && $display_name !== $email): ?>
                    <p class="att-user-email"><?php echo esc_view($email); ?></p>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td>
              <?php if ($last_attendance_date): ?>
                <span class="att-date-badge"><?php echo esc_view($last_attendance_date); ?></span>
              <?php else: ?>
                <span class="text-muted">&mdash;</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="att-count-badge"><?php echo $attendance_count; ?></span>
            </td>
            <td>
              <div class="att-action-btns">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); showUserAttendanceDetails(<?php echo (int) $r->user_id; ?>, '<?php echo $name_js; ?>')" title="View details">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($records)): ?>
          <tr>
            <td colspan="<?php echo $canExport ? 5 : 4; ?>" class="text-center">
              <div class="att-empty">
                <i class="bi bi-calendar-x d-block"></i>
                <div class="fw-semibold">No attendance records found</div>
                <div class="small"><?php echo $isInactiveTab ? 'No inactive employees with attendance records.' : 'No active employees with attendance records.'; ?></div>
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
  <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-md-down att-modal-dialog">
    <div class="modal-content att-modal-content">
      <div class="modal-header att-modal-header">
        <div class="min-width-0">
          <h5 class="modal-title text-truncate" id="attendanceModalLabel">Attendance Details</h5>
          <p class="att-modal-subtitle mb-0">Filter by date, month, or year</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body att-modal-body">
        <div class="att-modal-filters">
          <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
              <label class="form-label small fw-semibold mb-1" for="filterType">Filter by</label>
              <select id="filterType" class="form-select">
                <option value="month">Month</option>
                <option value="date">Date</option>
                <option value="year">Year</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label small fw-semibold mb-1" for="filterValue">Period</label>
              <input type="text" id="filterValue" class="form-control" placeholder="Select period">
            </div>
            <div class="col-12 col-md-4">
              <button type="button" class="btn btn-primary w-100 att-modal-search" onclick="loadAttendanceDetails()">
                <i class="bi bi-search me-1"></i>Search
              </button>
            </div>
          </div>
        </div>

        <div id="attendanceDetailsMobile" class="att-detail-list d-md-none">
          <div class="att-detail-loading text-center text-muted py-4">
            <div class="spinner-border spinner-border-sm" role="status"></div>
            <div class="small mt-2">Loading attendance details…</div>
          </div>
        </div>

        <div class="att-detail-table-wrap d-none d-md-block">
          <div class="table-responsive">
            <table class="table table-sm att-detail-table" id="attendanceDetailsTable">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Check in</th>
                  <th>Check out</th>
                  <th>Status</th>
                  <th>Notes</th>
                  <th>Check-in location</th>
                  <th>Check-out location</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="attendanceDetailsBody">
                <tr>
                  <td colspan="8" class="text-center">
                    <div class="spinner-border spinner-border-sm" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                    Loading attendance details…
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div id="attendancePagination" class="att-detail-pagination d-flex justify-content-between align-items-center mt-3" style="display: none;">
          <div class="text-muted">
            <small id="paginationInfo">Loading records…</small>
          </div>
          <nav>
            <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
          </nav>
        </div>
      </div>
      <div class="modal-footer att-modal-footer">
        <button type="button" class="btn btn-secondary w-100 w-md-auto" data-bs-dismiss="modal">Close</button>
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

const ATT_EMPTY_MARK = '<span class="text-muted">&mdash;</span>';

function attEscapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function attDetailLoadingHtml() {
    return `
        <div class="att-detail-loading text-center text-muted py-4">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="small mt-2">Loading attendance details…</div>
        </div>
    `;
}

function setAttendanceDetailsLoading() {
    const tbody = document.getElementById('attendanceDetailsBody');
    const mobile = document.getElementById('attendanceDetailsMobile');
    const paginationDiv = document.getElementById('attendancePagination');
    if (paginationDiv) paginationDiv.style.display = 'none';
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center">${attDetailLoadingHtml()}</td></tr>`;
    }
    if (mobile) mobile.innerHTML = attDetailLoadingHtml();
}

function setAttendanceDetailsMessage(message, isError) {
    const className = isError ? 'text-danger' : 'text-muted';
    const tbody = document.getElementById('attendanceDetailsBody');
    const mobile = document.getElementById('attendanceDetailsMobile');
    const paginationDiv = document.getElementById('attendancePagination');
    if (paginationDiv) paginationDiv.style.display = 'none';
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center ${className}">${attEscapeHtml(message)}</td></tr>`;
    }
    if (mobile) {
        mobile.innerHTML = `<div class="att-detail-empty text-center ${className} py-4">${attEscapeHtml(message)}</div>`;
    }
}

function attendanceStatusMeta(status) {
    let statusClass = 'secondary';
    switch (status) {
        case 'present': statusClass = 'success'; break;
        case 'absent': statusClass = 'danger'; break;
        case 'late': statusClass = 'warning'; break;
        case 'early_leave': statusClass = 'warning'; break;
        case 'half_day': statusClass = 'info'; break;
        default: statusClass = 'warning';
    }
    const statusText = String(status || 'incomplete').charAt(0).toUpperCase()
        + String(status || 'incomplete').slice(1).replace('_', ' ');
    return { statusClass, statusText };
}

function buildAttendanceActionButtons(record) {
    const canEdit = record.can_edit !== undefined ? record.can_edit : false;
    const canDelete = record.can_delete !== undefined ? record.can_delete : false;
    let html = '<div class="att-detail-actions-inner d-flex gap-1">';
    if (canEdit) {
        html += `<button type="button" class="btn btn-outline-primary btn-sm" onclick="editAttendance(${record.id})" title="Edit">
            <i class="bi bi-pencil"></i><span class="d-none d-sm-inline ms-1">Edit</span>
        </button>`;
    }
    if (canDelete) {
        html += `<button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteAttendance(${record.id})" title="Delete">
            <i class="bi bi-trash"></i><span class="d-none d-sm-inline ms-1">Delete</span>
        </button>`;
    }
    if (!canEdit && !canDelete) {
        html += '<span class="text-muted small align-self-center">No actions</span>';
    }
    html += '</div>';
    return html;
}

function loadAttendanceDetails(page = 1) {
    const filterType = document.getElementById('filterType').value;
    const filterValue = document.getElementById('filterValue').value;
    
    if (!filterValue) {
        return;
    }
    
    currentPage = page;
    setAttendanceDetailsLoading();
    
    // Fetch attendance data
    fetch('<?php echo site_url('attendance/get_user_monthly_attendance'); ?>', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `user_id=${encodeURIComponent(currentUserId)}&filter_type=${encodeURIComponent(filterType)}&filter_value=${encodeURIComponent(filterValue)}&page=${encodeURIComponent(currentPage)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAttendanceDetails(data.data, data.pagination);
        } else {
            setAttendanceDetailsMessage(data.message || 'Error loading attendance details', true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        setAttendanceDetailsMessage('Error loading attendance details', true);
    });
}

function displayAttendanceDetails(data, pagination) {
    const tbody = document.getElementById('attendanceDetailsBody');
    const mobile = document.getElementById('attendanceDetailsMobile');
    const paginationDiv = document.getElementById('attendancePagination');
    
    if (!data || data.length === 0) {
        setAttendanceDetailsMessage('No attendance records found for the selected period', false);
        return;
    }
    
    let tableHtml = '';
    let mobileHtml = '';
    data.forEach(record => {
        const meta = attendanceStatusMeta(record.status);
        const actionButtons = buildAttendanceActionButtons(record);
        const checkIn = record.check_in ? attEscapeHtml(record.check_in) : ATT_EMPTY_MARK;
        const checkOut = record.check_out ? attEscapeHtml(record.check_out) : ATT_EMPTY_MARK;
        const notes = record.notes ? attEscapeHtml(record.notes) : ATT_EMPTY_MARK;
        const checkinLocation = record.checkin_location ? attEscapeHtml(record.checkin_location) : ATT_EMPTY_MARK;
        const checkoutLocation = record.checkout_location ? attEscapeHtml(record.checkout_location) : ATT_EMPTY_MARK;
        const dateLabel = attEscapeHtml(record.date);

        tableHtml += `
            <tr>
                <td>${dateLabel}</td>
                <td>${checkIn}</td>
                <td>${checkOut}</td>
                <td><span class="badge bg-${meta.statusClass}">${meta.statusText}</span></td>
                <td>${notes}</td>
                <td>${checkinLocation}</td>
                <td>${checkoutLocation}</td>
                <td>${actionButtons}</td>
            </tr>
        `;

        mobileHtml += `
            <div class="att-detail-card">
                <div class="att-detail-card-head">
                    <span class="att-detail-date">${dateLabel}</span>
                    <span class="badge bg-${meta.statusClass}">${meta.statusText}</span>
                </div>
                <div class="att-detail-times">
                    <div class="att-detail-time-box">
                        <span class="lbl">Check in</span>
                        <span class="val">${record.check_in ? attEscapeHtml(record.check_in) : '&mdash;'}</span>
                    </div>
                    <div class="att-detail-time-box">
                        <span class="lbl">Check out</span>
                        <span class="val">${record.check_out ? attEscapeHtml(record.check_out) : '&mdash;'}</span>
                    </div>
                </div>
                ${record.notes ? `<div class="att-detail-field"><span class="lbl">Notes: </span>${attEscapeHtml(record.notes)}</div>` : ''}
                ${record.checkin_location ? `<div class="att-detail-field"><span class="lbl">Check-in: </span>${attEscapeHtml(record.checkin_location)}</div>` : ''}
                ${record.checkout_location ? `<div class="att-detail-field"><span class="lbl">Check-out: </span>${attEscapeHtml(record.checkout_location)}</div>` : ''}
                <div class="att-detail-actions">${actionButtons}</div>
            </div>
        `;
    });
    
    if (tbody) tbody.innerHTML = tableHtml;
    if (mobile) mobile.innerHTML = mobileHtml;
    
    if (pagination && pagination.total_pages > 1) {
        updatePaginationControls(pagination);
        if (paginationDiv) paginationDiv.style.display = 'flex';
    } else if (paginationDiv) {
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
        if (modal) modal.hide();

        // Submit via POST to satisfy the method check
        const form = document.createElement('form');
        form.method = 'post';
        form.action = '<?php echo site_url('attendance/'); ?>' + id + '/delete';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '<?php echo $this->security->get_csrf_token_name(); ?>';
        csrf.value = '<?php echo $this->security->get_csrf_hash(); ?>';
        form.appendChild(csrf);

        document.body.appendChild(form);
        form.submit();
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
  function getSelectedUserIds() {
    const ids = new Set();
    document.querySelectorAll('.row-checkbox:checked').forEach(function(cb) {
      ids.add(cb.value);
    });
    return Array.from(ids);
  }

  function syncRowCheckbox(changed) {
    if (!changed || !changed.value) return;
    document.querySelectorAll('.row-checkbox[value="' + changed.value + '"]').forEach(function(cb) {
      cb.checked = changed.checked;
    });
  }

  function setSelectAllState(checked) {
    const selectAll = document.getElementById('selectAll');
    const selectAllMobile = document.getElementById('selectAllMobile');
    if (selectAll) selectAll.checked = checked;
    if (selectAllMobile) selectAllMobile.checked = checked;
  }

  function toggleSelectAll(checkbox) {
    document.querySelectorAll('.row-checkbox').forEach(function(cb) {
      cb.checked = checkbox.checked;
    });
    setSelectAllState(checkbox.checked);
    updateSelection();
  }

  function updateSelection() {
    const selectedIds = getSelectedUserIds();
    const count = selectedIds.length;
    const exportBar = document.getElementById('exportActionsBar');
    const selectedCount = document.getElementById('selectedCount');

    if (exportBar) {
      exportBar.style.display = count > 0 ? 'block' : 'none';
    }
    if (selectedCount) {
      selectedCount.textContent = count + ' selected';
    }

    const allIds = new Set();
    document.querySelectorAll('.row-checkbox').forEach(function(cb) {
      allIds.add(cb.value);
    });
    const allSelected = allIds.size > 0 && count === allIds.size;
    setSelectAllState(allSelected);
  }

  function handleRowClick(event, userId, userName) {
    if (event.target.type === 'checkbox' || event.target.closest('.btn') || event.target.closest('.att-action-btns') || event.target.closest('.att-mobile-view-btn')) {
      return;
    }
    showUserAttendanceDetails(userId, userName);
  }

  function clearSelection() {
    document.querySelectorAll('.row-checkbox').forEach(function(cb) {
      cb.checked = false;
    });
    setSelectAllState(false);
    updateSelection();
  }

  function exportSelected(format) {
    const userIds = getSelectedUserIds();
    if (userIds.length === 0) {
      alert('Please select at least one employee to export.');
      return;
    }
    
    const userIdsStr = userIds.join(',');
    
    // Show loading message
    const exportBar = document.getElementById('exportActionsBar');
    const originalHTML = exportBar.innerHTML;
    exportBar.innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split"></i> Preparing export...</div>';
    
    // GET download — avoids CSRF 403 on POST (controller accepts GET params)
    const exportUrl = '<?php echo site_url("attendance/export"); ?>'
      + '?format=' + encodeURIComponent(format)
      + '&user_ids=' + encodeURIComponent(userIdsStr);
    window.location.href = exportUrl;
    
    // Restore original HTML after a delay
    setTimeout(() => {
      exportBar.innerHTML = originalHTML;
    }, 2000);
  }
</script>

<?php $this->load->view('partials/footer'); ?>
