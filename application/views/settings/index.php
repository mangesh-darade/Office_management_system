<?php $this->load->view('partials/header', ['title' => 'Settings']); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">
    <i class="bi bi-gear-fill me-2"></i>System Settings
  </h1>
  <div>
    <button class="btn btn-outline-secondary btn-sm" onclick="resetAllForms()">
      <i class="bi bi-arrow-clockwise"></i> Reset Changes
    </button>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<ul class="nav nav-tabs" id="settingsTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#company" type="button" role="tab">
      <i class="bi bi-building me-1"></i> Company
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab">
      <i class="bi bi-clock-history me-1"></i> Attendance
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#leave" type="button" role="tab">
      <i class="bi bi-calendar-x me-1"></i> Leave
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
      <i class="bi bi-envelope me-1"></i> Email
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notify" type="button" role="tab">
      <i class="bi bi-bell me-1"></i> Notifications
    </button>
  </li>
</ul>
<div class="tab-content pt-3">
  <div class="tab-pane fade show active" id="company" role="tabpanel">
    <div class="card shadow-sm">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="bi bi-building me-2"></i>Company Information
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3" id="companyForm">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Company Name</label>
              <input class="form-control" name="company_name" value="<?php echo htmlspecialchars(isset($settings['company_name']) ? $settings['company_name'] : ''); ?>" required />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Company Email</label>
              <input type="email" class="form-control" name="company_email" value="<?php echo htmlspecialchars(isset($settings['company_email']) ? $settings['company_email'] : ''); ?>" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Phone</label>
              <input type="tel" class="form-control" name="company_phone" value="<?php echo htmlspecialchars(isset($settings['company_phone']) ? $settings['company_phone'] : ''); ?>" pattern="[0-9+\-\s()]+" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Timezone</label>
              <select class="form-select" name="company_timezone">
                <?php
                $timezones = [
                  'Asia/Kolkata' => 'India Standard Time (IST)',
                  'Asia/Dubai' => 'Gulf Standard Time (GST)',
                  'Europe/London' => 'Greenwich Mean Time (GMT)',
                  'America/New_York' => 'Eastern Time (ET)',
                  'America/Los_Angeles' => 'Pacific Time (PT)',
                  'Australia/Sydney' => 'Australian Eastern Time (AET)'
                ];
                $current_tz = isset($settings['company_timezone']) ? $settings['company_timezone'] : 'Asia/Kolkata';
                foreach ($timezones as $tz => $label) {
                  $selected = $tz === $current_tz ? 'selected' : '';
                  echo "<option value=\"$tz\" $selected>$label</option>";
                }
                ?>
              </select>
            </div>
            <div class="col-md-12">
              <label class="form-label fw-semibold">Address</label>
              <textarea class="form-control" name="company_address" rows="3"><?php echo htmlspecialchars(isset($settings['company_address']) ? $settings['company_address'] : ''); ?></textarea>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg me-1"></i> Save Changes
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetForm('companyForm')">
              <i class="bi bi-arrow-clockwise me-1"></i> Reset
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm mt-4">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="bi bi-image me-2"></i>Company Logo
        </h5>
      </div>
      <div class="card-body">
        <form id="uploadLogoForm" method="post" action="<?php echo site_url('settings/upload-logo'); ?>" enctype="multipart/form-data">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Logo Upload</label>
              <div class="d-flex align-items-center gap-3">
                <input type="file" class="form-control" name="logo" accept="image/*" />
                <?php if (isset($settings['company_logo']) && !empty($settings['company_logo'])): ?>
                  <div class="d-flex align-items-center gap-2">
                    <img src="<?php echo base_url($settings['company_logo']); ?>" alt="Logo" class="img-thumbnail" style="height:50px" />
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLogo()">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                <?php endif; ?>
              </div>
              <div class="form-text">Allowed formats: JPG, PNG, GIF. Max size: 2MB</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">&nbsp;</label>
              <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-upload me-1"></i> Upload Logo
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="attendance" role="tabpanel">
    <div class="card shadow-sm">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="bi bi-clock-history me-2"></i>Attendance Settings
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3" id="attendanceForm">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Office Start Time</label>
              <input type="time" class="form-control" name="attendance_start_time" value="<?php echo htmlspecialchars(isset($settings['attendance_start_time']) ? $settings['attendance_start_time'] : '09:30'); ?>" required />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Office End Time</label>
              <input type="time" class="form-control" name="attendance_end_time" value="<?php echo htmlspecialchars(isset($settings['attendance_end_time']) ? $settings['attendance_end_time'] : '18:30'); ?>" required />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Grace Period (minutes)</label>
              <input type="number" class="form-control" name="attendance_grace_minutes" value="<?php echo htmlspecialchars(isset($settings['attendance_grace_minutes']) ? $settings['attendance_grace_minutes'] : '15'); ?>" min="0" max="60" />
              <div class="form-text">Minutes allowed after start time</div>
            </div>
            <div class="col-md-12">
              <label class="form-label fw-semibold">Weekend Days</label>
              <div class="row g-2">
                <?php
                $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $weekend_values = isset($settings['attendance_weekends']) ? explode(',', $settings['attendance_weekends']) : ['0', '6'];
                foreach ($weekdays as $index => $day) {
                  $checked = in_array((string)$index, $weekend_values) ? 'checked' : '';
                  echo "
                  <div class='col-auto'>
                    <div class='form-check'>
                      <input class='form-check-input' type='checkbox' name='attendance_weekends[]' value='$index' $checked id='weekend_$index'>
                      <label class='form-check-label' for='weekend_$index'>$day</label>
                    </div>
                  </div>";
                }
                ?>
              </div>
              <div class="form-text">Select weekend days</div>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg me-1"></i> Save Changes
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetForm('attendanceForm')">
              <i class="bi bi-arrow-clockwise me-1"></i> Reset
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="leave" role="tabpanel">
    <div class="card shadow-sm">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="bi bi-calendar-x me-2"></i>Leave Policy Settings
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3" id="leaveForm">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Carry Forward Leave</label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="leave_carry_forward" value="yes" <?php echo (isset($settings['leave_carry_forward']) && $settings['leave_carry_forward'] === 'yes') ? 'checked' : ''; ?> id="leave_carry_forward">
                <label class="form-check-label" for="leave_carry_forward">
                  Enable carry forward of unused leave
                </label>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Max Consecutive Days</label>
              <input type="number" class="form-control" name="leave_max_consecutive" value="<?php echo htmlspecialchars(isset($settings['leave_max_consecutive']) ? $settings['leave_max_consecutive'] : '14'); ?>" min="1" max="365" />
              <div class="form-text">Maximum days allowed at once</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Minimum Gap Between Leaves</label>
              <input type="number" class="form-control" name="leave_min_gap" value="<?php echo htmlspecialchars(isset($settings['leave_min_gap']) ? $settings['leave_min_gap'] : '1'); ?>" min="0" max="30" />
              <div class="form-text">Days required between two leaves</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Default Annual Leave Days</label>
              <input type="number" class="form-control" step="0.5" min="0" name="leave_default_days" value="<?php echo htmlspecialchars(isset($settings['leave_default_days']) ? $settings['leave_default_days'] : '0'); ?>" />
              <div class="form-text">Leave days per year for new employees</div>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg me-1"></i> Save Changes
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetForm('leaveForm')">
              <i class="bi bi-arrow-clockwise me-1"></i> Reset
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="email" role="tabpanel">
    <div class="card shadow-sm">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="bi bi-envelope me-2"></i>Email Configuration
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3" id="emailForm">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">SMTP User</label>
              <input type="email" class="form-control" name="email_smtp_user" value="<?php echo htmlspecialchars(isset($settings['email_smtp_user']) ? $settings['email_smtp_user'] : ''); ?>" placeholder="email@example.com" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">SMTP Password</label>
              <div class="input-group">
                <input type="password" class="form-control" name="email_smtp_pass" value="<?php echo htmlspecialchars(isset($settings['email_smtp_pass']) ? $settings['email_smtp_pass'] : ''); ?>" id="smtpPass" />
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('smtpPass')">
                  <i class="bi bi-eye" id="smtpPassIcon"></i>
                </button>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">SMTP Host</label>
              <input class="form-control" name="email_smtp_host" value="<?php echo htmlspecialchars(isset($settings['email_smtp_host']) ? $settings['email_smtp_host'] : 'smtp.gmail.com'); ?>" required />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">SMTP Port</label>
              <select class="form-select" name="email_smtp_port">
                <option value="587" <?php echo (isset($settings['email_smtp_port']) && $settings['email_smtp_port'] === '587') ? 'selected' : ''; ?>>587 (TLS)</option>
                <option value="465" <?php echo (isset($settings['email_smtp_port']) && $settings['email_smtp_port'] === '465') ? 'selected' : ''; ?>>465 (SSL)</option>
                <option value="25" <?php echo (isset($settings['email_smtp_port']) && $settings['email_smtp_port'] === '25') ? 'selected' : ''; ?>>25 (None)</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">SMTP Encryption</label>
              <select class="form-select" name="email_smtp_crypto">
                <option value="tls" <?php echo (isset($settings['email_smtp_crypto']) && $settings['email_smtp_crypto'] === 'tls') ? 'selected' : ''; ?>>TLS</option>
                <option value="ssl" <?php echo (isset($settings['email_smtp_crypto']) && $settings['email_smtp_crypto'] === 'ssl') ? 'selected' : ''; ?>>SSL</option>
                <option value="" <?php echo (isset($settings['email_smtp_crypto']) && $settings['email_smtp_crypto'] === '') ? 'selected' : ''; ?>>None</option>
              </select>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg me-1"></i> Save Changes
            </button>
            <button type="submit" class="btn btn-outline-info" formaction="<?php echo site_url('settings/test-email'); ?>">
              <i class="bi bi-send me-1"></i> Send Test Email
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetForm('emailForm')">
              <i class="bi bi-arrow-clockwise me-1"></i> Reset
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="notify" role="tabpanel">
    <div class="card shadow-sm">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="bi bi-bell me-2"></i>Notification Preferences
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3" id="notifyForm">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">In-App Notifications</label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="notify_in_app" value="yes" <?php echo (isset($settings['notify_in_app']) && $settings['notify_in_app'] === 'yes') ? 'checked' : ''; ?> id="notify_in_app">
                <label class="form-check-label" for="notify_in_app">
                  Show notifications within the application
                </label>
              </div>
              <div class="form-text">Display real-time notifications to users</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Email Notifications</label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="notify_email" value="yes" <?php echo (isset($settings['notify_email']) && $settings['notify_email'] === 'yes') ? 'checked' : ''; ?> id="notify_email">
                <label class="form-check-label" for="notify_email">
                  Send notifications via email
                </label>
              </div>
              <div class="form-text">Send important updates via email</div>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg me-1"></i> Save Changes
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetForm('notifyForm')">
              <i class="bi bi-arrow-clockwise me-1"></i> Reset
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function resetForm(formId) {
  if (confirm('Are you sure you want to reset all changes in this tab?')) {
    document.getElementById(formId).reset();
  }
}

function resetAllForms() {
  if (confirm('Are you sure you want to reset all changes across all tabs?')) {
    document.querySelectorAll('form').forEach(form => form.reset());
  }
}

function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  const icon = document.getElementById(inputId + 'Icon');
  
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'bi bi-eye';
  }
}

function removeLogo() {
  if (confirm('Are you sure you want to remove the company logo?')) {
    fetch('<?php echo site_url("settings/remove-logo"); ?>', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).then(() => {
      location.reload();
    });
  }
}

// Handle weekend checkboxes
document.addEventListener('DOMContentLoaded', function() {
  const weekendCheckboxes = document.querySelectorAll('input[name="attendance_weekends[]"]');
  weekendCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      const checked = document.querySelectorAll('input[name="attendance_weekends[]"]:checked');
      if (checked.length === 7) {
        alert('Warning: You have selected all days as weekends. Please uncheck at least one day.');
      }
    });
  });
  
  // Handle leave carry forward toggle
  const carryForward = document.getElementById('leave_carry_forward');
  if (carryForward) {
    carryForward.addEventListener('change', function() {
      if (!this.checked) {
        if (!confirm('Disabling carry forward will delete all unused leave balances. Continue?')) {
          this.checked = true;
        }
      }
    });
  }
});
</script>

<?php $this->load->view('partials/footer'); ?>
