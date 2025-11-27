<?php $this->load->view('partials/header', ['title' => 'Edit Profile']); ?>
<style>
.profile-edit .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.profile-edit .form-label { font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
.profile-edit .form-control, .profile-edit .form-select { border-radius: 8px; border: 1px solid #d1d5db; }
.profile-edit .form-control:focus, .profile-edit .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.profile-edit .btn { border-radius: 8px; font-weight: 500; }
.profile-edit .avatar-upload { 
  border: 2px dashed #d1d5db; 
  border-radius: 12px; 
  padding: 2rem; 
  text-align: center; 
  cursor: pointer; 
  transition: all 0.3s ease;
  background: #f9fafb;
}
.profile-edit .avatar-upload:hover { 
  border-color: #3b82f6; 
  background: #f0f9ff;
}
.profile-edit .avatar-preview { 
  width: 120px; 
  height: 120px; 
  border-radius: 50%; 
  margin: 0 auto 1rem; 
  overflow: hidden; 
  border: 4px solid #e5e7eb;
}
.profile-edit .avatar-preview img { 
  width: 100%; 
  height: 100%; 
  object-fit: cover;
}
.profile-edit .avatar-placeholder { 
  width: 100%; 
  height: 100%; 
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  color: white; 
  font-size: 3rem; 
  font-weight: 700;
}
.profile-edit .section-title { 
  font-weight: 700; 
  color: #1f2937; 
  margin-bottom: 1.5rem; 
  padding-bottom: 0.5rem; 
  border-bottom: 2px solid #e5e7eb;
}
.profile-edit .info-card { 
  background: #f8fafc; 
  border-radius: 8px; 
  padding: 1rem; 
  margin-bottom: 1rem;
}
.profile-edit .preview-section { 
  background: #f0f9ff; 
  border-radius: 8px; 
  padding: 1rem; 
  border: 1px solid #bfdbfe;
}
</style>

<div class="profile-edit">
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">✏️ Edit Profile</h1>
  <a class="btn btn-secondary btn-sm" href="<?php echo site_url('profile'); ?>">
    <i class="bi bi-arrow-left me-1"></i>Back to Profile
  </a>
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

<form method="post" enctype="multipart/form-data" action="<?php echo site_url('profile/edit'); ?>">
  <!-- Avatar Upload Section -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="section-title">📷 Profile Picture</h5>
      
      <div class="row align-items-center">
        <div class="col-md-4">
          <div class="avatar-upload" onclick="document.getElementById('avatarInput').click()">
            <div class="avatar-preview">
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
                <img src="<?php echo base_url($user->avatar); ?>" alt="Profile Avatar" id="avatarPreview">
              <?php else: ?>
                <div class="avatar-placeholder"><?php echo htmlspecialchars($initial); ?></div>
              <?php endif; ?>
            </div>
            <div>
              <i class="bi bi-camera" style="font-size: 2rem; color: #6b7280;"></i>
              <div class="mt-2">
                <strong>Click to upload</strong>
                <div class="text-muted small">JPG, PNG, GIF (Max 2MB)</div>
              </div>
            </div>
          </div>
          <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display: none;" onchange="previewAvatar(event)">
          
          <?php if (!empty($user->avatar)): ?>
            <div class="mt-3">
              <a href="<?php echo site_url('profile/remove_avatar'); ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove current avatar?')">
                <i class="bi bi-trash me-1"></i>Remove Avatar
              </a>
            </div>
          <?php endif; ?>
        </div>
        
        <div class="col-md-8">
          <div class="info-card">
            <h6 class="mb-2">💡 Avatar Guidelines</h6>
            <ul class="mb-0 text-muted small">
              <li>Use a professional headshot for best results</li>
              <li>Recommended size: 400x400 pixels</li>
              <li>File size should be less than 2MB</li>
              <li>Supported formats: JPG, PNG, GIF</li>
              <li>Your avatar will be displayed across the system</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Personal Information -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="section-title">👤 Personal Information</h5>
      
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Display Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars(isset($user->name) ? $user->name : ''); ?>" required placeholder="Enter your display name">
        </div>
        
        <div class="col-md-6">
          <label class="form-label">Email Address <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars(isset($user->email) ? $user->email : ''); ?>" required placeholder="your.email@example.com">
        </div>
        
        <div class="col-md-6">
          <label class="form-label">Phone Number</label>
          <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars(isset($user->phone) ? $user->phone : (isset($employee->phone) ? $employee->phone : '')); ?>" placeholder="+1 (555) 123-4567">
        </div>
        
        <div class="col-md-6">
          <label class="form-label">Current Password</label>
          <input type="password" name="current_password" class="form-control" placeholder="Enter current password to change">
          <div class="form-text">Leave blank to keep current password</div>
        </div>
        
        <div class="col-md-6">
          <label class="form-label">New Password</label>
          <input type="password" name="password" class="form-control" id="password" placeholder="Enter new password">
        </div>
        
        <div class="col-md-6">
          <label class="form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" id="confirm_password" placeholder="Confirm new password">
        </div>
      </div>
    </div>
  </div>

  <!-- Professional Information -->
  <?php if(!empty($employee)): ?>
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="section-title">💼 Professional Information</h5>
      
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">First Name</label>
          <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars(isset($employee->first_name) ? $employee->first_name : ''); ?>" placeholder="Enter first name">
        </div>
        
        <div class="col-md-6">
          <label class="form-label">Last Name</label>
          <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars(isset($employee->last_name) ? $employee->last_name : ''); ?>" placeholder="Enter last name">
        </div>
        
        <div class="col-md-6">
          <label class="form-label">Department</label>
          <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars(isset($employee->department) ? $employee->department : ''); ?>" placeholder="e.g., Engineering, Marketing">
        </div>
        
        <div class="col-md-6">
          <label class="form-label">Designation</label>
          <input type="text" name="designation" class="form-control" value="<?php echo htmlspecialchars(isset($employee->designation) ? $employee->designation : ''); ?>" placeholder="e.g., Senior Developer">
        </div>
        
        <div class="col-12">
          <label class="form-label">Address</label>
          <textarea name="address" class="form-control" rows="2" placeholder="Enter your address"><?php echo htmlspecialchars(isset($employee->address) ? $employee->address : ''); ?></textarea>
        </div>
        
        <div class="col-12">
          <label class="form-label">Bio</label>
          <textarea name="bio" class="form-control" rows="3" placeholder="Tell us about yourself..."><?php echo htmlspecialchars(isset($employee->bio) ? $employee->bio : ''); ?></textarea>
          <div class="form-text">Brief description about yourself (visible to team members)</div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Actions -->
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>Save Changes
          </button>
          <a href="<?php echo site_url('profile'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-2"></i>Cancel
          </a>
        </div>
        
        <div class="text-muted small">
          <i class="bi bi-info-circle me-1"></i>
          Last updated: <?php echo isset($user->updated_at) ? date('M j, Y H:i', strtotime($user->updated_at)) : 'Never'; ?>
        </div>
      </div>
    </div>
  </div>
</form>
</div>

<script>
function previewAvatar(event) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const preview = document.getElementById('avatarPreview');
      if (preview) {
        preview.src = e.target.result;
      } else {
        // Create preview if it doesn't exist
        const container = document.querySelector('.avatar-preview');
        container.innerHTML = `<img src="${e.target.result}" alt="Profile Avatar" id="avatarPreview">`;
      }
    };
    reader.readAsDataURL(file);
  }
}

// Password validation
document.addEventListener('DOMContentLoaded', function() {
  const password = document.getElementById('password');
  const confirmPassword = document.getElementById('confirm_password');
  const form = document.querySelector('form');
  
  if (form) {
    form.addEventListener('submit', function(e) {
      // Check if passwords match when new password is entered
      if (password.value && password.value !== confirmPassword.value) {
        e.preventDefault();
        alert('New passwords do not match!');
        return false;
      }
      
      // Check if current password is provided when changing password
      if (password.value && !document.querySelector('input[name="current_password"]').value) {
        e.preventDefault();
        alert('Please enter your current password to change your password!');
        return false;
      }
    });
  }
});
</script>

<?php $this->load->view('partials/footer'); ?>
