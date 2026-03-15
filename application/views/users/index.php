<?php $this->load->view('partials/header', array('title' => (isset($title) ? $title : 'Users'), 'active' => 'users')); ?>
<div class="container-fluid p-0">
  <!-- Header Section -->
  <div class="row g-2 mb-3">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0 fw-bold">Users Management</h5>
          <p class="text-muted mb-0 small">Manage system users and their permissions</p>
        </div>
        <?php if (function_exists('has_module_access') && has_module_access('users_add')): ?>
        <a href="<?php echo site_url('users/create'); ?>" class="btn btn-primary">
          <i class="bi bi-person-plus-fill me-2"></i>Add New User
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Search and Filters -->
  <div class="row g-2 mb-3">
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
          <form method="get" action="<?php echo site_url('users'); ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
              <label class="form-label fw-semibold text-dark mb-1">Search Users</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0">
                  <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" name="q" value="<?php echo htmlspecialchars(isset($q) ? $q : ''); ?>" 
                       class="form-control border-start-0" placeholder="Search by name, email...">
              </div>
            </div>
            <div class="col-md-auto">
              <button class="btn btn-primary px-4" type="submit">
                <i class="bi bi-search me-2"></i>Search
              </button>
              <?php if (isset($q) && $q !== ''): ?>
              <a href="<?php echo site_url('users'); ?>" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-x-lg me-1"></i>Clear
              </a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Users Table -->
  <div class="row g-3">
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
              <i class="bi bi-people-fill me-2 text-primary"></i>
              All Users
              <?php if (!empty($rows)): ?>
              <span class="badge bg-primary ms-2"><?php echo count($rows); ?></span>
              <?php endif; ?>
            </h6>
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="card-body p-0">
          <?php if (!empty($rows)): ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable" data-order='[[0,"asc"]]'>
              <thead class="table-light">
                <tr>
                  <th class="border-0 text-muted fw-semibold" style="width:70px;">#</th>
                  <th class="border-0 text-muted fw-semibold">User</th>
                  <th class="border-0 text-muted fw-semibold">Contact</th>
                  <th class="border-0 text-muted fw-semibold" style="width:120px;">Role</th>
                  <th class="border-0 text-muted fw-semibold" style="width:120px;">Status</th>
                  <th class="border-0 text-muted fw-semibold" style="width:140px;">Face Registered</th>
                  <th class="border-0 text-muted fw-semibold" style="width:160px;">Last Login</th>
                  <th class="border-0 text-muted fw-semibold text-center" style="width:160px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; foreach ($rows as $r): ?>
                <tr class="user-row">
                  <td class="ps-4">
                    <?php echo $i++; ?>
                  </td>
                  <td class="ps-4">
                    <div class="d-flex align-items-center">
                      <div class="avatar-wrapper me-3">
                        <?php if (!empty($r->avatar)): ?>
                          <img src="<?php echo base_url('uploads/avatars/'.htmlspecialchars($r->avatar)); ?>" 
                               class="rounded-circle avatar-md border border-2 border-light-subtle" 
                               alt="<?php echo htmlspecialchars(isset($r->name) ? $r->name : 'User'); ?>">
                        <?php else: ?>
                          <div class="avatar-md rounded-circle bg-primary bg-gradient d-flex align-items-center justify-content-center text-white fw-bold">
                            <?php echo strtoupper(substr(isset($r->name) ? $r->name : 'U', 0, 2)); ?>
                          </div>
                        <?php endif; ?>
                      </div>
                      <div>
                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars(isset($r->name) ? $r->name : 'Unknown'); ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="contact-info">
                      <div class="d-flex align-items-center mb-1">
                        <i class="bi bi-envelope-fill text-muted me-2 small"></i>
                        <span class="small"><?php echo htmlspecialchars(isset($r->email) ? $r->email : ''); ?></span>
                      </div>
                      <?php if (!empty($r->phone)): ?>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-telephone-fill text-muted me-2 small"></i>
                        <span class="small"><?php echo htmlspecialchars($r->phone); ?></span>
                      </div>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <?php
                      // Use the same roles array as the edit form for consistency
                      $roleOptions = isset($roles) && is_array($roles) && !empty($roles)
                        ? $roles
                        : [1 => 'Admin', 2 => 'Manager', 3 => 'Lead', 4 => 'Staff'];
                      
                      $roleLabel = '';
                      $roleIcon = '';
                      $roleColor = '';
                      $rid = isset($r->role_id) ? (int)$r->role_id : null;
                      
                      // Get role name from roles array
                      if ($rid && isset($roleOptions[$rid])) {
                        $roleName = $roleOptions[$rid];
                        $roleLabel = strtolower(trim($roleName));
                      } else if (isset($r->role) && $r->role !== '') { 
                        $roleLabel = strtolower(trim($r->role)); 
                      } else { 
                        $roleLabel = 'staff'; 
                      }
                      
                      // Map role labels to icons and colors
                      switch($roleLabel) {
                        case 'admin':
                          $roleIcon = 'bi-shield-fill';
                          $roleColor = 'danger';
                          break;
                        case 'manager':
                        case 'hr':
                          $roleIcon = 'bi-people-fill';
                          $roleColor = 'info';
                          break;
                        case 'lead':
                          $roleIcon = 'bi-star-fill';
                          $roleColor = 'warning';
                          break;
                        default:
                          $roleIcon = 'bi-person-fill';
                          $roleColor = 'secondary';
                      }
                      
                      // Display the role name from roles array (same as edit form)
                      $displayName = $rid && isset($roleOptions[$rid]) ? $roleOptions[$rid] : ucfirst($roleLabel);
                    ?>
                    <span class="badge bg-<?php echo $roleColor; ?> bg-opacity-10 text-<?php echo $roleColor; ?> border border-<?php echo $roleColor; ?> bg-opacity-25">
                      <i class="bi <?php echo $roleIcon; ?> me-1"></i>
                      <?php echo htmlspecialchars($displayName); ?>
                    </span>
                  </td>
                  <td>
                    <?php
                      $st = isset($r->status) ? $r->status : 0;
                      $is_active = false;
                      if (is_numeric($st)) { $is_active = ((int)$st) === 1; }
                      else if (is_string($st)) { $is_active = in_array(strtolower(trim($st)), ['active','enabled','true','yes'], true); }
                    ?>
                    <?php if ($is_active): ?>
                      <span class="badge bg-success bg-opacity-10 text-success border border-success bg-opacity-25">
                        <i class="bi bi-circle-fill me-1" style="font-size: 6px;"></i>
                        Active
                      </span>
                    <?php else: ?>
                      <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary bg-opacity-25">
                        <i class="bi bi-circle me-1" style="font-size: 6px;"></i>
                        Inactive
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php
                      $faceRegistered = isset($r->face_registered) ? $r->face_registered : false;
                      $faceDate = isset($r->face_registered_date) ? $r->face_registered_date : null;
                    ?>
                    <?php if ($faceRegistered): ?>
                      <span class="badge bg-success" title="<?php echo $faceDate ? 'Registered on: ' . htmlspecialchars($faceDate) : 'Face registered'; ?>">
                        <i class="bi bi-check-circle-fill"></i> Yes
                      </span>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark" title="Face not registered">
                        <i class="bi bi-x-circle-fill"></i> No
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($r->last_login_at)): ?>
                      <div class="small">
                        <div class="text-muted"><?php echo date('M d, Y', strtotime($r->last_login_at)); ?></div>
                        <div class="text-muted"><?php echo date('h:i A', strtotime($r->last_login_at)); ?></div>
                      </div>
                    <?php else: ?>
                      <span class="text-muted small">Never</span>
                    <?php endif; ?>
                  </td>
                  <td class="pe-4">
                    <div class="d-flex justify-content-center gap-1">
                      <?php if(function_exists('has_module_access') && (has_module_access('users_edit') || has_module_access('users'))): ?>
                      <a href="<?php echo site_url('users/edit/'.(int)$r->id); ?>" 
                         class="btn btn-sm btn-outline-primary btn-icon" 
                         data-bs-toggle="tooltip" title="Edit User">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <?php endif; ?>
                      <a href="<?php echo site_url('users/view/'.(int)$r->id); ?>" 
                         class="btn btn-sm btn-outline-info btn-icon" 
                         data-bs-toggle="tooltip" title="View Details">
                        <i class="bi bi-eye"></i>
                      </a>
                      <?php if (function_exists('has_module_access') && has_module_access('users_delete')): ?>
                      <form method="post" action="<?php echo site_url('users/destroy/'.(int)$r->id); ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" data-bs-toggle="tooltip" title="Delete User">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="text-center py-5">
            <div class="mb-4">
              <i class="bi bi-people text-muted" style="font-size: 4rem;"></i>
            </div>
            <h6 class="text-muted mb-2">No Users Found</h6>
            <p class="text-muted small mb-4">
              <?php if (isset($q) && $q !== ''): ?>
                No users match your search criteria. Try adjusting your search terms.
              <?php else: ?>
                No users have been added to the system yet.
              <?php endif; ?>
            </p>
            <?php if (function_exists('has_module_access') && has_module_access('users_add')): ?>
            <a href="<?php echo site_url('users/create'); ?>" class="btn btn-primary">
              <i class="bi bi-person-plus-fill me-2"></i>Add Your First User
            </a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.avatar-md {
  width: 36px;
  height: 36px;
  object-fit: cover;
}

.avatar-wrapper {
  position: relative;
}

.user-row {
  transition: all 0.2s ease;
}

.user-row:hover {
  background-color: #f8f9fa;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.btn-icon {
  width: 28px;
  height: 28px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.btn-icon:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.contact-info {
  line-height: 1.2;
}

.table th {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 0.5rem 0.75rem;
}

.table td {
  padding: 0.5rem 0.75rem;
  vertical-align: middle;
}

.badge {
  font-weight: 500;
  font-size: 0.7rem;
  padding: 0.25rem 0.5rem;
}

.card-header {
  padding: 0.75rem 1rem;
}

.card-body {
  padding: 0.75rem;
}

@media (max-width: 768px) {
  .contact-info {
    font-size: 0.75rem;
  }
  
  .btn-icon {
    width: 24px;
    height: 24px;
  }
  
  .avatar-md {
    width: 32px;
    height: 32px;
  }
  
  .table th,
  .table td {
    padding: 0.4rem 0.5rem;
  }
}

@media print {
  .btn, .btn-icon {
    display: none !important;
  }
  
  .user-row:hover {
    background-color: transparent !important;
    transform: none !important;
    box-shadow: none !important;
  }
}
</style>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
  
  // Add row click functionality
  document.querySelectorAll('.user-row').forEach(function(row) {
    row.style.cursor = 'pointer';
    row.addEventListener('click', function(e) {
      // Don't trigger if clicking on buttons or links
      if (e.target.closest('.btn') || e.target.closest('a')) {
        return;
      }
      
      // Get the view link
      var viewLink = row.querySelector('a[href*="/view/"]');
      if (viewLink) {
        window.location.href = viewLink.href;
      }
    });
  });
  
  // Search functionality enhancement
  var searchInput = document.querySelector('input[name="q"]');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      var value = this.value.toLowerCase();
      var rows = document.querySelectorAll('.user-row');
      
      rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        if (text.includes(value)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  }
});
</script>

<?php $this->load->view('partials/footer'); ?>
