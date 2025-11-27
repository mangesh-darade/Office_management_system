<?php $this->load->view('partials/header', ['title' => 'My Profile']); ?>
<style>
.profile-enhanced .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.profile-enhanced .profile-header { 
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
  border-radius: 12px 12px 0 0; 
  padding: 2rem; 
  position: relative; 
  overflow: hidden;
}
.profile-enhanced .profile-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  animation: float 6s ease-in-out infinite;
}
@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
}
.profile-enhanced .avatar-container { 
  position: relative; 
  width: 120px; 
  height: 120px; 
  margin: 0 auto 1rem;
}
.profile-enhanced .avatar-img { 
  width: 100%; 
  height: 100%; 
  border-radius: 50%; 
  object-fit: cover; 
  border: 4px solid rgba(255,255,255,0.9);
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
.profile-enhanced .avatar-placeholder { 
  width: 100%; 
  height: 100%; 
  border-radius: 50%; 
  background: rgba(255,255,255,0.9); 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  font-size: 3rem; 
  font-weight: 700; 
  color: #667eea;
  border: 4px solid rgba(255,255,255,0.9);
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
.profile-enhanced .profile-name { 
  color: white; 
  font-size: 1.5rem; 
  font-weight: 700; 
  margin-bottom: 0.5rem; 
  text-align: center;
}
.profile-enhanced .profile-role { 
  color: rgba(255,255,255,0.9); 
  text-align: center; 
  margin-bottom: 1rem;
}
.profile-enhanced .stat-card { 
  background: rgba(255,255,255,0.1); 
  border-radius: 8px; 
  padding: 1rem; 
  text-align: center; 
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.2);
}
.profile-enhanced .stat-value { 
  color: white; 
  font-size: 1.25rem; 
  font-weight: 700; 
  display: block;
}
.profile-enhanced .stat-label { 
  color: rgba(255,255,255,0.8); 
  font-size: 0.875rem;
}
.profile-enhanced .info-card { 
  border-radius: 12px; 
  border: 1px solid #e5e7eb; 
  padding: 1.5rem; 
  margin-bottom: 1rem;
  transition: all 0.3s ease;
}
.profile-enhanced .info-card:hover { 
  box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
  transform: translateY(-2px);
}
.profile-enhanced .info-label { 
  color: #6b7280; 
  font-size: 0.875rem; 
  font-weight: 600; 
  margin-bottom: 0.25rem;
}
.profile-enhanced .info-value { 
  color: #374151; 
  font-size: 1rem; 
  font-weight: 500;
}
.profile-enhanced .btn { border-radius: 8px; font-weight: 500; }
.profile-enhanced .quick-action-card { 
  text-align: center; 
  padding: 1.5rem; 
  border-radius: 12px; 
  border: 1px solid #e5e7eb; 
  transition: all 0.3s ease;
  cursor: pointer;
}
.profile-enhanced .quick-action-card:hover { 
  border-color: #3b82f6; 
  background: #f0f9ff; 
  transform: translateY(-2px);
}
.profile-enhanced .quick-action-icon { 
  font-size: 2rem; 
  margin-bottom: 0.5rem; 
  display: block;
}
.profile-enhanced .quick-action-title { 
  font-weight: 600; 
  color: #374151; 
  margin-bottom: 0.25rem;
}
.profile-enhanced .quick-action-desc { 
  color: #6b7280; 
  font-size: 0.875rem;
}
</style>

<div class="profile-enhanced">
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">👤 My Profile</h1>
  <div>
    <a href="<?php echo site_url('profile/edit'); ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-pencil-square me-1"></i>Edit Profile
    </a>
  </div>
</div>

<?php if($this->session->flashdata('success')): ?>
  <div class="alert alert-success fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($this->session->flashdata('success')); ?>
  </div>
<?php endif; ?>
<?php if($this->session->flashdata('error')): ?>
  <div class="alert alert-danger fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($this->session->flashdata('error')); ?>
  </div>
<?php endif; ?>

<div class="row g-4">
  <!-- Profile Header Card -->
  <div class="col-12 col-lg-4">
    <div class="card">
      <div class="profile-header">
        <div class="avatar-container">
          <?php
            $displayName = '';
            if (!empty($user) && !empty($user->name)) {
              $displayName = (string)$user->name;
            } elseif (!empty($employee) && (!empty($employee->first_name) || !empty($employee->last_name))) {
              $displayName = trim((isset($employee->first_name) ? $employee->first_name : '').' '.(isset($employee->last_name) ? $employee->last_name : ''));
            } elseif (!empty($user) && !empty($user->email)) {
              $displayName = (string)$user->email;
            } else {
              $displayName = 'User';
            }
            $initial = strtoupper(substr($displayName, 0, 1));
          ?>
          
          <?php if (!empty($user->avatar) && file_exists('./' . $user->avatar)): ?>
            <img src="<?php echo base_url($user->avatar); ?>" alt="Profile Avatar" class="avatar-img">
          <?php else: ?>
            <div class="avatar-placeholder"><?php echo htmlspecialchars($initial); ?></div>
          <?php endif; ?>
        </div>
        
        <div class="profile-name"><?php echo htmlspecialchars($displayName); ?></div>
        <div class="profile-role">
          <i class="bi bi-briefcase me-1"></i>
          <?php echo htmlspecialchars(isset($user->role) ? ucfirst($user->role) : 'Member'); ?>
        </div>
        
        <!-- Stats -->
        <div class="row g-2">
          <div class="col-4">
            <div class="stat-card">
              <span class="stat-value"><?php echo isset($employee->department) ? 'Active' : 'N/A'; ?></span>
              <span class="stat-label">Status</span>
            </div>
          </div>
          <div class="col-4">
            <div class="stat-card">
              <span class="stat-value"><?php echo date('Y'); ?></span>
              <span class="stat-label">Since</span>
            </div>
          </div>
          <div class="col-4">
            <div class="stat-card">
              <span class="stat-value"><?php echo isset($employee->department) ? 'Team' : 'Solo'; ?></span>
              <span class="stat-label">Type</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card mt-3">
      <div class="card-body">
        <h6 class="card-title mb-3">🚀 Quick Actions</h6>
        <div class="d-grid gap-2">
          <a href="<?php echo site_url('profile/edit'); ?>" class="btn btn-primary">
            <i class="bi bi-pencil-square me-2"></i>Edit Profile
          </a>
          <a href="<?php echo site_url('auth/change_password'); ?>" class="btn btn-outline-primary">
            <i class="bi bi-key me-2"></i>Change Password
          </a>
          <a href="<?php echo site_url('tasks/board'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-kanban me-2"></i>View Tasks
          </a>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Main Content -->
  <div class="col-12 col-lg-8">
    <!-- Personal Information -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-4">
          <i class="bi bi-person me-2"></i>Personal Information
        </h5>
        
        <div class="row g-3">
          <div class="col-md-6">
            <div class="info-card">
              <div class="info-label">Full Name</div>
              <div class="info-value">
                <?php 
                  $fullName = '';
                  if (!empty($employee)) {
                    $fullName = trim((isset($employee->first_name) ? $employee->first_name : '') . ' ' . (isset($employee->last_name) ? $employee->last_name : ''));
                  }
                  echo htmlspecialchars($fullName ?: $displayName);
                ?>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="info-card">
              <div class="info-label">Email Address</div>
              <div class="info-value">
                <i class="bi bi-envelope me-1"></i>
                <?php echo htmlspecialchars(isset($user->email) ? $user->email : 'Not set'); ?>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="info-card">
              <div class="info-label">Phone Number</div>
              <div class="info-value">
                <i class="bi bi-phone me-1"></i>
                <?php echo htmlspecialchars(isset($user->phone) ? $user->phone : (isset($employee->phone) ? $employee->phone : 'Not set')); ?>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="info-card">
              <div class="info-label">User Role</div>
              <div class="info-value">
                <i class="bi bi-shield me-1"></i>
                <?php echo htmlspecialchars(isset($user->role) ? ucfirst($user->role) : 'Member'); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Professional Information -->
    <?php if(!empty($employee)): ?>
    <div class="card mt-4">
      <div class="card-body">
        <h5 class="card-title mb-4">
          <i class="bi bi-briefcase me-2"></i>Professional Information
        </h5>
        
        <div class="row g-3">
          <div class="col-md-6">
            <div class="info-card">
              <div class="info-label">Department</div>
              <div class="info-value">
                <i class="bi bi-building me-1"></i>
                <?php echo htmlspecialchars(isset($employee->department) ? $employee->department : 'Not set'); ?>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="info-card">
              <div class="info-label">Designation</div>
              <div class="info-value">
                <i class="bi bi-award me-1"></i>
                <?php echo htmlspecialchars(isset($employee->designation) ? $employee->designation : 'Not set'); ?>
              </div>
            </div>
          </div>
          
          <?php if (isset($employee->address)): ?>
          <div class="col-12">
            <div class="info-card">
              <div class="info-label">Address</div>
              <div class="info-value">
                <i class="bi bi-geo-alt me-1"></i>
                <?php echo htmlspecialchars($employee->address); ?>
              </div>
            </div>
          </div>
          <?php endif; ?>
          
          <?php if (isset($employee->bio)): ?>
          <div class="col-12">
            <div class="info-card">
              <div class="info-label">Bio</div>
              <div class="info-value">
                <i class="bi bi-card-text me-1"></i>
                <?php echo htmlspecialchars($employee->bio); ?>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Links -->
    <div class="card mt-4">
      <div class="card-body">
        <h5 class="card-title mb-4">
          <i class="bi bi-lightning me-2"></i>Quick Links
        </h5>
        
        <div class="row g-3">
          <div class="col-md-4">
            <div class="quick-action-card" onclick="window.location.href='<?php echo site_url('tasks/board'); ?>'">
              <span class="quick-action-icon">📋</span>
              <div class="quick-action-title">Task Board</div>
              <div class="quick-action-desc">View and manage tasks</div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="quick-action-card" onclick="window.location.href='<?php echo site_url('attendance/create'); ?>'">
              <span class="quick-action-icon">⏰</span>
              <div class="quick-action-title">Mark Attendance</div>
              <div class="quick-action-desc">Check in/out</div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="quick-action-card" onclick="window.location.href='<?php echo site_url('reports'); ?>'">
              <span class="quick-action-icon">📊</span>
              <div class="quick-action-title">Reports</div>
              <div class="quick-action-desc">View analytics</div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="quick-action-card" onclick="window.location.href='<?php echo site_url('announcements'); ?>'">
              <span class="quick-action-icon">📢</span>
              <div class="quick-action-title">Announcements</div>
              <div class="quick-action-desc">Latest updates</div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="quick-action-card" onclick="window.location.href='<?php echo site_url('reminders/dashboard'); ?>'">
              <span class="quick-action-icon">📧</span>
              <div class="quick-action-title">Reminders</div>
              <div class="quick-action-desc">Email reminders</div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="quick-action-card" onclick="window.location.href='<?php echo site_url('chats'); ?>'">
              <span class="quick-action-icon">💬</span>
              <div class="quick-action-title">Chat</div>
              <div class="quick-action-desc">Team communication</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<?php $this->load->view('partials/footer'); ?>
