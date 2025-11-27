<?php 
  // Get current user role for filtering
  $user_id = (int)$this->session->userdata('user_id');
  $role_id = (int)$this->session->userdata('role_id');
  $isAdminGroup = (function_exists('is_admin_group') && is_admin_group());
  $canViewAll = isset($can_view_all) ? $can_view_all : ($isAdminGroup || in_array($role_id, [1,2], true));
  $canAddAttendance = isset($can_add_attendance) ? $can_add_attendance : $canViewAll;
  
  $this->load->view('partials/header', ['title' => 'Attendance']); 
?>

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

/* Mobile Responsive */
@media (max-width: 768px) {
  .attendance-container {
    padding: 0.25rem;
  }
  .attendance-header {
    padding: 0.75rem;
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
  }
  .attendance-table {
    min-width: 550px;
  }
  .attendance-table thead th {
    padding: 0.5rem 0.25rem;
    font-size: 0.625rem;
  }
  .attendance-table tbody td {
    padding: 0.375rem 0.25rem;
    font-size: 0.75rem;
  }
  .user-cell {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.125rem;
  }
  .user-avatar {
    width: 28px;
    height: 28px;
    font-size: 0.625rem;
  }
  .user-name {
    font-size: 0.625rem;
  }
  .user-email {
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
        <h1 class="attendance-title">Attendance Records</h1>
        <p class="attendance-subtitle">
          <?php 
            if (isset($show_all) && $show_all) {
              echo 'Showing all attendance records';
            } else {
              echo 'Showing today\'s attendance records';
            }
          ?>
          <?php if (isset($total_records)): ?>
            <span class="ms-2">(<?php echo $total_records; ?> records)</span>
          <?php endif; ?>
        </p>
      </div>
      <div class="d-flex gap-2">
        <?php if (isset($show_all) && $show_all): ?>
          <a class="btn btn-light btn-sm" title="Show Today" href="<?php echo site_url('attendance'); ?>">
            <i class="bi bi-calendar-day me-1"></i> Today
          </a>
        <?php else: ?>
          <a class="btn btn-light btn-sm" title="Show All" href="<?php echo site_url('attendance?all=1'); ?>">
            <i class="bi bi-calendar-range me-1"></i> All
          </a>
        <?php endif; ?>
        <?php if ($canAddAttendance): ?>
          <a class="btn btn-light btn-sm" title="Add Attendance" href="<?php echo site_url('attendance/create'); ?>">
            <i class="bi bi-plus-lg"></i> Add
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Collapsible Filters -->
  <div class="filter-toggle" id="filterToggle">
    <div class="filter-toggle-header" onclick="toggleFilters()">
      <h3 class="filter-toggle-title">
        <i class="bi bi-funnel"></i>
        Filters
      </h3>
      <i class="bi bi-chevron-down filter-toggle-arrow"></i>
    </div>
    <div class="filter-content" id="filterContent">
      <div class="filter-row">
        <div>
          <label class="form-label">Date Range</label>
          <div class="input-group">
            <input type="date" id="startDate" class="form-control">
            <span class="input-group-text">to</span>
            <input type="date" id="endDate" class="form-control">
          </div>
        </div>
        
        <?php if ($canViewAll): ?>
        <div>
          <label class="form-label">Employee</label>
          <select id="userFilter" class="form-select">
            <option value="">All Employees</option>
            <?php
              $users = [];
              if (!empty($records)) {
                foreach ($records as $r) {
                  $name = '';
                  if (!empty($r->first_name) || !empty($r->last_name)) {
                    $name = trim((isset($r->first_name) ? $r->first_name : '').' '.(isset($r->last_name) ? $r->last_name : ''));
                  }
                  if ($name === '') { $name = isset($r->email) && $r->email !== '' ? $r->email : 'Unknown'; }
                  $users[$r->user_id] = $name;
                }
              }
              foreach ($users as $uid => $name) {
                echo '<option value="'.$uid.'">'.htmlspecialchars($name).'</option>';
              }
            ?>
          </select>
        </div>
        <?php endif; ?>
        
        <div>
          <label class="form-label">Status</label>
          <select id="statusFilter" class="form-select">
            <option value="">All Status</option>
            <option value="present">Present</option>
            <option value="absent">Absent</option>
            <option value="incomplete">Incomplete</option>
          </select>
        </div>
      </div>
      
      <div class="filter-actions">
        <button class="btn btn-primary" onclick="applyFilters()">
          <i class="bi bi-check-circle me-1"></i>Apply
        </button>
        <button class="btn btn-outline-secondary" onclick="clearFilters()">
          <i class="bi bi-x-circle me-1"></i>Clear
        </button>
      </div>
    </div>
  </div>

  <!-- Attendance Table -->
  <div class="attendance-table-container">
    <table class="attendance-table" id="attendanceTable">
      <thead>
        <tr>
          <th>Employee</th>
          <th>Date</th>
          <th>Check In</th>
          <th>Check Out</th>
          <th>Status</th>
          <th>Notes</th>
          <th>Location</th>
          <?php if ($canViewAll): ?>
            <th>Actions</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if(!empty($records)) foreach($records as $r): ?>
          <?php 
            $name = '';
            if (!empty($r->first_name) || !empty($r->last_name)) {
              $name = trim((isset($r->first_name) ? $r->first_name : '').' '.(isset($r->last_name) ? $r->last_name : ''));
            }
            if ($name === '') { $name = isset($r->email) && $r->email !== '' ? $r->email : 'Unknown'; }
            
            // Schema-aware fields
            $d = isset($r->att_date) ? $r->att_date : (isset($r->date) ? $r->date : '');
            $cin = isset($r->punch_in) ? $r->punch_in : (isset($r->check_in) ? $r->check_in : '');
            $cout = isset($r->punch_out) ? $r->punch_out : (isset($r->check_out) ? $r->check_out : '');
            if ($cin === '00:00:00' || $cin === '0000-00-00 00:00:00') { $cin = ''; }
            if ($cout === '00:00:00' || $cout === '0000-00-00 00:00:00') { $cout = ''; }
            
            // Pretty display just time portion if datetime
            $cin_disp = $cin;
            $cout_disp = $cout;
            if ($cin_disp && strpos($cin_disp, ' ') !== false) { $cin_disp = trim(explode(' ', $cin_disp)[1]); }
            if ($cout_disp && strpos($cout_disp, ' ') !== false) { $cout_disp = trim(explode(' ', $cout_disp)[1]); }
            
            $notes = isset($r->notes) ? $r->notes : '';
            $file = isset($r->attachment_path) ? $r->attachment_path : '';
            
            // Location schema-aware fields
            $lat = '';
            $lng = '';
            if (isset($r->latitude)) { $lat = $r->latitude; }
            elseif (isset($r->lat)) { $lat = $r->lat; }
            elseif (isset($r->geo_lat)) { $lat = $r->geo_lat; }
            if (isset($r->longitude)) { $lng = $r->longitude; }
            elseif (isset($r->lng)) { $lng = $r->lng; }
            elseif (isset($r->geo_lng)) { $lng = $r->geo_lng; }
            $ip = isset($r->ip_address) ? $r->ip_address : '';
            $loc = isset($r->location_name) ? $r->location_name : '';
            
            // Determine status
            $status = 'incomplete';
            if ($cin && $cout) {
              $status = 'present';
            } elseif ($cin && !$cout) {
              $status = 'incomplete';
            } else {
              $status = 'absent';
            }
            
            // Check if record is from today
            $isToday = ($d === date('Y-m-d'));
          ?>
          <tr data-user-id="<?php echo $r->user_id; ?>" data-date="<?php echo htmlspecialchars($d); ?>" data-status="<?php echo $status; ?>">
            <td>
              <div class="user-cell">
                <div class="user-avatar">
                  <?php echo strtoupper(substr($name, 0, 1)); ?>
                </div>
                <div class="user-details">
                  <p class="user-name"><?php echo htmlspecialchars($name); ?></p>
                  <?php if (isset($r->email) && $r->email !== '' && $name !== $r->email): ?>
                    <p class="user-email"><?php echo htmlspecialchars($r->email); ?></p>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td><?php echo htmlspecialchars($d); ?></td>
            <td>
              <?php if ($cin_disp): ?>
                <span class="time-badge"><?php echo htmlspecialchars($cin_disp); ?></span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($cout_disp): ?>
                <span class="time-badge checkout"><?php echo htmlspecialchars($cout_disp); ?></span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="status-badge <?php echo $status; ?>">
                <?php 
                  switch($status) {
                    case 'present': echo 'Present'; break;
                    case 'absent': echo 'Absent'; break;
                    case 'incomplete': echo 'Incomplete'; break;
                  }
                ?>
              </span>
            </td>
            <td class="notes-cell">
              <?php if($notes): ?>
                <p class="notes-text" title="<?php echo htmlspecialchars($notes); ?>">
                  <?php echo htmlspecialchars(substr($notes, 0, 20)) . (strlen($notes) > 20 ? '...' : ''); ?>
                </p>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="location-info">
              <?php if($loc): ?>
                <p class="location-name" title="<?php echo htmlspecialchars($loc); ?>">
                  <?php echo htmlspecialchars(substr($loc, 0, 25)) . (strlen($loc) > 25 ? '...' : ''); ?>
                </p>
                <?php if($lat && $lng): ?>
                  <p class="location-coords">
                    <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($lat); ?>, <?php echo htmlspecialchars($lng); ?>
                  </p>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <?php if ($canViewAll): ?>
              <td>
                <div class="action-buttons">
                  <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('attendance/'.$r->id.'/edit'); ?>" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <?php if(!empty($file)): ?>
                    <a class="btn btn-sm btn-outline-success" href="<?php echo base_url($file); ?>" target="_blank" title="Download">
                      <i class="bi bi-download"></i>
                    </a>
                  <?php endif; ?>
                  <button class="btn btn-sm btn-outline-danger" onclick="deleteAttendance(<?php echo $r->id; ?>)" title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        
        <?php if(empty($records)): ?>
          <tr>
            <td colspan="<?php echo $canViewAll ? '8' : '7'; ?>" class="text-center">
              <div class="empty-state">
                <i class="bi bi-calendar-x empty-state-icon"></i>
                <div class="empty-state-title">No attendance records found</div>
                <div class="empty-state-text">Try adjusting your filters or add a new attendance record</div>
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

<script>
function toggleFilters() {
  const toggle = document.getElementById('filterToggle');
  const content = document.getElementById('filterContent');
  const arrow = toggle.querySelector('.filter-toggle-arrow');
  
  toggle.classList.toggle('collapsed');
  if (toggle.classList.contains('collapsed')) {
    content.style.display = 'none';
  } else {
    content.style.display = 'block';
  }
}

function applyFilters() {
  const startDate = document.getElementById('startDate').value;
  const endDate = document.getElementById('endDate').value;
  const userFilter = document.getElementById('userFilter').value;
  const statusFilter = document.getElementById('statusFilter').value;
  
  const rows = document.querySelectorAll('#attendanceTable tbody tr[data-user-id]');
  let visibleCount = 0;
  
  rows.forEach(row => {
    let show = true;
    
    // Date filter
    if (startDate && row.dataset.date) {
      show = show && (row.dataset.date >= startDate);
    }
    if (endDate && row.dataset.date) {
      show = show && (row.dataset.date <= endDate);
    }
    
    // User filter
    if (userFilter) {
      show = show && (row.dataset.userId == userFilter);
    }
    
    // Status filter
    if (statusFilter) {
      show = show && (row.dataset.status == statusFilter);
    }
    
    row.style.display = show ? '' : 'none';
    if (show) visibleCount++;
  });
  
  // Show/hide empty state
  const tbody = document.querySelector('#attendanceTable tbody');
  let emptyRow = tbody.querySelector('.empty-state');
  
  if (visibleCount === 0 && !emptyRow) {
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
      <td colspan="<?php echo $canViewAll ? '8' : '7'; ?>" class="text-center">
        <div class="empty-state">
          <i class="bi bi-funnel empty-state-icon"></i>
          <div class="empty-state-title">No records match your filters</div>
          <div class="empty-state-text">Try adjusting your filter criteria</div>
        </div>
      </td>
    `;
    tbody.appendChild(newRow);
  } else if (visibleCount > 0 && emptyRow) {
    emptyRow.parentElement.remove();
  }
}

function clearFilters() {
  document.getElementById('startDate').value = '';
  document.getElementById('endDate').value = '';
  document.getElementById('userFilter').value = '';
  document.getElementById('statusFilter').value = '';
  
  // Reload page to reset filters and show today's records by default
  window.location.href = '<?php echo site_url('attendance'); ?>';
}

function deleteAttendance(id) {
  if (confirm('Are you sure you want to delete this attendance record?')) {
    window.location.href = '<?php echo site_url('attendance/'); ?>' + id + '/delete';
  }
}

// Initialize: set filters and collapse them
document.addEventListener('DOMContentLoaded', function() {
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('endDate').value = today;
  document.getElementById('startDate').value = today;
  
  // Collapse filters by default
  toggleFilters();
});
</script>

<?php $this->load->view('partials/footer'); ?>
