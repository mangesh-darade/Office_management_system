<?php $this->load->view('partials/header', array('title' => (isset($title) ? $title : 'View User'), 'active' => 'users')); ?>
<div class="container-fluid p-0">
  <!-- Header Section -->
  <div class="row g-2 mb-3">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0 fw-bold">User Profile</h5>
          <p class="text-muted mb-0 small">View user details and information</p>
        </div>
        <div class="d-flex gap-2">
          <a href="<?php echo site_url('users'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Users
          </a>
          <?php if (function_exists('has_module_access') && (has_module_access('users_edit') || has_module_access('users'))): ?>
          <a href="<?php echo site_url('users/edit/'.(int)$user->id); ?>" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i>Edit User
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- User Profile Cards -->
  <div class="row g-3">
    <!-- Main Profile Card -->
    <div class="col-md-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-person-circle me-2 text-primary"></i>
            User Information
          </h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <!-- Avatar Section -->
            <div class="col-md-3 text-center">
              <div class="avatar-wrapper mb-3">
                <?php if (!empty($user->avatar)): ?>
                  <img src="<?php echo base_url('uploads/avatars/'.htmlspecialchars($user->avatar)); ?>" 
                       class="rounded-circle avatar-lg border border-3 border-light-subtle" 
                       alt="<?php echo htmlspecialchars($user->name); ?>">
                <?php else: ?>
                  <div class="avatar-lg rounded-circle bg-primary bg-gradient d-flex align-items-center justify-content-center text-white fw-bold">
                    <?php echo htmlspecialchars(strtoupper(substr(isset($user->name) ? $user->name : '', 0, 2)), ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="user-status">
                <?php
                  $is_active = false;
                  if (is_numeric($user->status)) { $is_active = ((int)$user->status) === 1; }
                  else if (is_string($user->status)) { $is_active = in_array(strtolower(trim($user->status)), ['active','enabled','true','yes'], true); }
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
              </div>
            </div>
            
            <!-- User Details -->
            <div class="col-md-9">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label text-muted small">Full Name</label>
                  <p class="fw-semibold text-dark"><?php echo htmlspecialchars($user->name); ?></p>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-muted small">User ID</label>
                  <p class="fw-semibold text-dark">#<?php echo (int)$user->id; ?></p>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-muted small">Email Address</label>
                  <p class="fw-semibold text-dark">
                    <i class="bi bi-envelope-fill text-muted me-2"></i>
                    <?php echo htmlspecialchars($user->email); ?>
                  </p>
                </div>
                <?php if (!empty($user->phone)): ?>
                <div class="col-md-6">
                  <label class="form-label text-muted small">Phone Number</label>
                  <p class="fw-semibold text-dark">
                    <i class="bi bi-telephone-fill text-muted me-2"></i>
                    <?php echo htmlspecialchars($user->phone); ?>
                  </p>
                </div>
                <?php endif; ?>
                <div class="col-md-6">
                  <label class="form-label text-muted small">Role</label>
                  <p class="fw-semibold text-dark">
                    <?php
                      // Use the same roles array as the edit form for consistency
                      $roleOptions = isset($roles) && is_array($roles) && !empty($roles)
                        ? $roles
                        : [1 => 'Admin', 2 => 'Manager', 3 => 'Lead', 4 => 'Staff'];
                      
                      $roleLabel = '';
                      $roleIcon = '';
                      $roleColor = '';
                      $rid = isset($user->role_id) ? (int)$user->role_id : null;
                      
                      // Get role name from roles array
                      if ($rid && isset($roleOptions[$rid])) {
                        $roleName = $roleOptions[$rid];
                        $roleLabel = strtolower(trim($roleName));
                      } else if (isset($user->role) && $user->role !== '') { 
                        $roleLabel = strtolower(trim($user->role)); 
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
                  </p>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-muted small">Email Verification</label>
                  <p class="fw-semibold text-dark">
                    <?php if (!empty($user->is_verified) && $user->is_verified): ?>
                      <span class="badge bg-success bg-opacity-10 text-success border border-success bg-opacity-25">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Verified
                      </span>
                    <?php else: ?>
                      <span class="badge bg-warning bg-opacity-10 text-warning border border-warning bg-opacity-25">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        Not Verified
                      </span>
                    <?php endif; ?>
                  </p>
                </div>
                <?php if (!empty($user->created_at)): ?>
                <div class="col-md-6">
                  <label class="form-label text-muted small">Account Created</label>
                  <p class="fw-semibold text-dark">
                    <i class="bi bi-calendar-plus text-muted me-2"></i>
                    <?php echo date('M d, Y h:i A', strtotime($user->created_at)); ?>
                  </p>
                </div>
                <?php endif; ?>
                <?php if (!empty($user->last_login_at)): ?>
                <div class="col-md-6">
                  <label class="form-label text-muted small">Last Login</label>
                  <p class="fw-semibold text-dark">
                    <i class="bi bi-clock-history text-muted me-2"></i>
                    <?php echo date('M d, Y h:i A', strtotime($user->last_login_at)); ?>
                  </p>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Side Cards -->
    <div class="col-md-4">
      <!-- Employee Information -->
      <?php if (isset($employee) && $employee): ?>
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-briefcase me-2 text-primary"></i>
            Employee Information
          </h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label text-muted small">Employee Code</label>
              <p class="fw-semibold text-dark"><?php echo htmlspecialchars($employee->emp_code); ?></p>
            </div>
            <?php if (!empty($employee->department)): ?>
            <div class="col-12">
              <label class="form-label text-muted small">Department</label>
              <p class="fw-semibold text-dark"><?php echo htmlspecialchars($employee->department); ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($employee->designation)): ?>
            <div class="col-12">
              <label class="form-label text-muted small">Designation</label>
              <p class="fw-semibold text-dark"><?php echo htmlspecialchars($employee->designation); ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($employee->join_date)): ?>
            <div class="col-12">
              <label class="form-label text-muted small">Join Date</label>
              <p class="fw-semibold text-dark"><?php echo date('M d, Y', strtotime($employee->join_date)); ?></p>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Face Recognition -->
      <?php if (isset($face) && $face): ?>
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-camera-fill me-2 text-primary"></i>
            Face Recognition
          </h6>
        </div>
        <div class="card-body text-center">
          <?php if (!empty($face->image_path)): ?>
            <?php 
            // Check if image_path is a base64 data URL or a regular path
            $image_src = $face->image_path;
            if (strpos($image_src, 'data:image') === 0) {
                // Base64 data URL
                $img_tag = '<img src="' . htmlspecialchars($image_src) . '" 
                           class="rounded border border-2 border-light-subtle mb-3" 
                           alt="Face Recognition" style="max-width: 150px;">';
            } else {
                // Regular file path — escape to prevent XSS
                $img_tag = '<img src="' . htmlspecialchars(base_url($image_src), ENT_QUOTES, 'UTF-8') . '" 
                           class="rounded border border-2 border-light-subtle mb-3" 
                           alt="Face Recognition" style="max-width: 150px;">';
            }
            echo $img_tag;
            ?>
          <?php else: ?>
            <div class="avatar-lg rounded-circle bg-light bg-gradient d-flex align-items-center justify-content-center text-muted mb-3 mx-auto" style="width: 150px; height: 150px;">
              <i class="bi bi-person-fill" style="font-size: 3rem;"></i>
            </div>
          <?php endif; ?>
          <p class="text-muted small mb-1">
            <i class="bi bi-check-circle-fill text-success me-1"></i>
            Face data registered
          </p>
          <p class="text-muted small mb-0">Registered: <?php echo date('M d, Y h:i A', strtotime($face->created_at)); ?></p>
        </div>
      </div>
      <?php else: ?>
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-camera-fill me-2 text-primary"></i>
            Face Recognition
          </h6>
        </div>
        <div class="card-body text-center">
          <div class="avatar-lg rounded-circle bg-light bg-gradient d-flex align-items-center justify-content-center text-muted mb-3 mx-auto" style="width: 150px; height: 150px;">
            <i class="bi bi-person-fill" style="font-size: 3rem;"></i>
          </div>
          <p class="text-muted small mb-0">
            <i class="bi bi-x-circle-fill text-warning me-1"></i>
            No face data registered
          </p>
        </div>
      </div>
      <?php endif; ?>

      <!-- Quick Actions -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-lightning-fill me-2 text-primary"></i>
            Quick Actions
          </h6>
        </div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <?php if (function_exists('has_module_access') && (has_module_access('users_edit') || has_module_access('users'))): ?>
            <a href="<?php echo site_url('users/edit/'.(int)$user->id); ?>" class="btn btn-outline-primary">
              <i class="bi bi-pencil me-2"></i>Edit Profile
            </a>
            <?php endif; ?>
            <a href="mailto:<?php echo htmlspecialchars($user->email); ?>" class="btn btn-outline-info">
              <i class="bi bi-envelope me-2"></i>Send Email
            </a>
            <?php if (isset($employee) && $employee): ?>
            <a href="<?php echo site_url('employees/view/'.(int)$employee->id); ?>" class="btn btn-outline-secondary">
              <i class="bi bi-briefcase me-2"></i>View Employee Profile
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.avatar-lg {
  width: 120px;
  height: 120px;
  object-fit: cover;
}

.avatar-wrapper {
  position: relative;
}

.card-header {
  padding: 0.75rem 1rem;
}

.card-body {
  padding: 0.75rem;
}

.form-label {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.badge {
  font-weight: 500;
  font-size: 0.7rem;
  padding: 0.25rem 0.5rem;
}

@media (max-width: 768px) {
  .avatar-lg {
    width: 100px;
    height: 100px;
  }
}
</style>

<?php $this->load->view('partials/footer'); ?>
