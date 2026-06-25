<?php $this->load->view('partials/header', ['title' => 'Settings']); ?>
<?php $can_save_settings = function_exists('has_module_access') && (has_module_access('settings') || has_module_access('system_settings') || is_admin_group()); ?>
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
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('warning')): ?>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i><?php echo esc_view($this->session->flashdata('warning')); ?>
  </div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
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
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
      <i class="bi bi-sliders me-1"></i> General & Display
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
      <i class="bi bi-shield-check me-1"></i> Security & Protection
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ai_integration" type="button" role="tab">
      <i class="bi bi-robot me-1"></i> AI Integration
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
        <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3" id="companyForm" data-validate="true">
          <input type="hidden" name="form_section" value="company">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Company Name</label>
              <input class="form-control" name="company_name" value="<?php echo esc_view(isset($settings['company_name']) ? $settings['company_name'] : ''); ?>" required />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Company Email</label>
              <input type="email" class="form-control" name="company_email" value="<?php echo esc_view(isset($settings['company_email']) ? $settings['company_email'] : ''); ?>" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Phone</label>
              <input type="tel" class="form-control" name="company_phone" value="<?php echo esc_view(isset($settings['company_phone']) ? $settings['company_phone'] : ''); ?>" pattern="[0-9+\-\s()]+" />
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
              <textarea class="form-control" name="company_address" rows="3"><?php echo esc_view(isset($settings['company_address']) ? $settings['company_address'] : ''); ?></textarea>
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
        <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3" id="attendanceForm" data-validate="true">
          <input type="hidden" name="form_section" value="attendance">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Office Start Time</label>
              <input type="time" class="form-control" name="attendance_start_time" value="<?php echo esc_view(isset($settings['attendance_start_time']) ? $settings['attendance_start_time'] : '09:30'); ?>" required />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Office End Time</label>
              <input type="time" class="form-control" name="attendance_end_time" value="<?php echo esc_view(isset($settings['attendance_end_time']) ? $settings['attendance_end_time'] : '18:30'); ?>" required />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Grace Period (minutes)</label>
              <input type="number" class="form-control" name="attendance_grace_minutes" value="<?php echo esc_view(isset($settings['attendance_grace_minutes']) ? $settings['attendance_grace_minutes'] : '15'); ?>" min="0" max="60" />
              <div class="form-text">Minutes allowed after start time</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Standard Working Hours</label>
              <input type="number" class="form-control" name="attendance_standard_working_hours" value="<?php echo esc_view(isset($settings['attendance_standard_working_hours']) ? $settings['attendance_standard_working_hours'] : (isset($settings['standard_working_hours']) ? $settings['standard_working_hours'] : '8')); ?>" step="0.5" min="1" max="24" />
              <div class="form-text">Standard working hours per day (for overtime calculation)</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Late Mark Notification</label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="attendance_late_mark_notification" value="yes" <?php echo (isset($settings['attendance_late_mark_notification']) && $settings['attendance_late_mark_notification'] === 'yes') ? 'checked' : ''; ?> id="attendance_late_mark_notification">
                <label class="form-check-label" for="attendance_late_mark_notification">
                  Send late mark email when employee checks in late
                </label>
              </div>
              <div class="form-text">Enable to send emails with late time when check-in is after start time + grace period</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Face Capture Mode</label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="attendance_auto_capture" value="yes" <?php echo (isset($settings['attendance_auto_capture']) && $settings['attendance_auto_capture'] === 'yes') ? 'checked' : ''; ?> id="attendance_auto_capture">
                <label class="form-check-label" for="attendance_auto_capture">
                  Enable auto face capture
                </label>
              </div>
              <div class="form-text">When enabled, face will be captured automatically after 3 seconds. When disabled, user must click "Capture Face" button manually.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Auto Submit After Capture</label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="attendance_auto_submit" value="yes" <?php echo (isset($settings['attendance_auto_submit']) && $settings['attendance_auto_submit'] === 'yes') ? 'checked' : ''; ?> id="attendance_auto_submit">
                <label class="form-check-label" for="attendance_auto_submit">
                  Enable auto submit after face capture
                </label>
              </div>
              <div class="form-text">When enabled, attendance form will be automatically submitted after successful face capture. When disabled, user must manually click "Mark Attendance" button.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Face Verification Required</label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="attendance_face_verification_required" value="yes" <?php echo (isset($settings['attendance_face_verification_required']) && $settings['attendance_face_verification_required'] === 'yes') ? 'checked' : ''; ?> id="attendance_face_verification_required">
                <label class="form-check-label" for="attendance_face_verification_required">
                  Require face verification for attendance
                </label>
              </div>
              <div class="form-text">When enabled, employees must verify their face before marking attendance. When disabled, attendance can be marked without face verification.</div>
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
        <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3" id="leaveForm" data-validate="true">
          <input type="hidden" name="form_section" value="leave">
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
              <input type="number" class="form-control" name="leave_max_consecutive" value="<?php echo esc_view(isset($settings['leave_max_consecutive']) ? $settings['leave_max_consecutive'] : '14'); ?>" min="1" max="365" />
              <div class="form-text">Maximum days allowed at once</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Minimum Gap Between Leaves</label>
              <input type="number" class="form-control" name="leave_min_gap" value="<?php echo esc_view(isset($settings['leave_min_gap']) ? $settings['leave_min_gap'] : '1'); ?>" min="0" max="30" />
              <div class="form-text">Days required between two leaves</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Default Annual Leave Days</label>
              <input type="number" class="form-control" step="0.5" min="0" name="leave_default_days" value="<?php echo esc_view(isset($settings['leave_default_days']) ? $settings['leave_default_days'] : '0'); ?>" />
              <div class="form-text">Leave days per year for new employees</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">HR Manager</label>
              <select class="form-select" name="leave_hr_user_id" id="leave_hr_user_id">
                <option value="">Select HR Manager</option>
                <?php if (isset($all_users) && is_array($all_users)): ?>
                  <?php foreach ($all_users as $user): ?>
                    <?php $selected = (isset($settings['leave_hr_user_id']) && $settings['leave_hr_user_id'] == $user->id) ? 'selected' : ''; ?>
                    <option value="<?php echo (int)$user->id; ?>" <?php echo $selected; ?>>
                      <?php echo esc_view(!empty($user->name) ? $user->name : $user->email); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
              <div class="form-text">HR will receive emails when leave is approved/rejected</div>
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

    <div class="card shadow-sm mt-4">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="bi bi-list-ul me-2"></i>Leave Types Management
        </h5>
      </div>
      <div class="card-body">
        <p class="text-muted mb-3">Manage leave types such as Annual Leave, Sick Leave, Casual Leave, etc. Configure annual quotas and paid/unpaid status for each type.</p>
        <a href="<?php echo site_url('settings/leave-types'); ?>" class="btn btn-primary">
          <i class="bi bi-gear me-1"></i>Manage Leave Types
        </a>
      </div>
    </div>

    <div class="card shadow-sm mt-4">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="bi bi-calendar-event me-2"></i>Holidays Management
        </h5>
      </div>
      <div class="card-body">
        <p class="text-muted mb-3">Configure company holidays. These dates will be treated as non-working days for leave validations and reports.</p>
        <a href="<?php echo site_url('settings/holidays'); ?>" class="btn btn-outline-primary">
          <i class="bi bi-gear me-1"></i>Manage Holidays
        </a>
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
        <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3" id="emailForm" data-validate="true">
          <input type="hidden" name="form_section" value="email">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">SMTP User</label>
              <input type="email" class="form-control" name="email_smtp_user" value="<?php echo esc_view(isset($settings['email_smtp_user']) ? $settings['email_smtp_user'] : ''); ?>" placeholder="email@example.com" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">SMTP Password</label>
              <div class="input-group">
                <input type="password" class="form-control" name="email_smtp_pass" value="<?php echo esc_view(isset($settings['email_smtp_pass']) ? $settings['email_smtp_pass'] : ''); ?>" id="smtpPass" />
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('smtpPass')">
                  <i class="bi bi-eye" id="smtpPassIcon"></i>
                </button>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">SMTP Host</label>
              <input class="form-control" name="email_smtp_host" value="<?php echo esc_view(isset($settings['email_smtp_host']) ? $settings['email_smtp_host'] : 'smtp.gmail.com'); ?>" required />
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
        <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3" id="notifyForm" data-validate="true">
          <input type="hidden" name="form_section" value="notify_basic">
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

    <!-- Notification Messages Configuration -->
    <div class="card shadow-sm mt-4">
      <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">
          <i class="bi bi-chat-text me-2"></i>Notification Messages Configuration
        </h5>
        <small class="opacity-75">Customize alert messages for different modules and actions</small>
      </div>
      <div class="card-body">
        <?php
        $this->load->helper('notification');
        $modules_structure = get_notification_modules_structure();
        $defaults = get_all_default_notification_messages();
        ?>
        
        <form method="post" action="<?php echo site_url('settings/update'); ?>" id="notificationMessagesForm">
          <input type="hidden" name="form_section" value="notify_messages">
          <div class="accordion" id="notificationModulesAccordion">
            <?php 
            $module_index = 0;
            foreach ($modules_structure as $module_key => $module_info): 
              $module_index++;
            ?>
              <div class="accordion-item">
                <h2 class="accordion-header" id="heading<?php echo $module_index; ?>">
                  <button class="accordion-button <?php echo $module_index === 1 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $module_index; ?>" aria-expanded="<?php echo $module_index === 1 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $module_index; ?>">
                    <i class="bi <?php echo esc_view($module_info['icon']); ?> me-2"></i>
                    <?php echo esc_view($module_info['label']); ?>
                    <span class="badge bg-secondary ms-2"><?php echo count($module_info['actions']); ?> actions</span>
                  </button>
                </h2>
                <div id="collapse<?php echo $module_index; ?>" class="accordion-collapse collapse <?php echo $module_index === 1 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $module_index; ?>" data-bs-parent="#notificationModulesAccordion">
                  <div class="accordion-body">
                    <div class="table-responsive">
                      <table class="table table-bordered table-hover">
                        <thead class="table-light">
                          <tr>
                            <th style="width: 25%;">Action</th>
                            <th style="width: 37.5%;">Success Message</th>
                            <th style="width: 37.5%;">Error Message</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($module_info['actions'] as $action): ?>
                            <?php
                            $success_key = "notification_{$module_key}_{$action}_success";
                            $error_key = "notification_{$module_key}_{$action}_error";
                            
                            $default_success = isset($defaults["{$module_key}_{$action}_success"]) ? $defaults["{$module_key}_{$action}_success"] : ucfirst($action) . ' completed successfully';
                            $default_error = isset($defaults["{$module_key}_{$action}_error"]) ? $defaults["{$module_key}_{$action}_error"] : 'Failed to ' . $action;
                            
                            $current_success = isset($settings[$success_key]) ? $settings[$success_key] : $default_success;
                            $current_error = isset($settings[$error_key]) ? $settings[$error_key] : $default_error;
                            ?>
                            <tr>
                              <td>
                                <strong><?php echo ucfirst(str_replace('_', ' ', $action)); ?></strong>
                                <button type="button" class="btn btn-sm btn-link p-0 ms-2" data-bs-toggle="tooltip" title="Reset to default" onclick="resetNotificationMessage('<?php echo $success_key; ?>', '<?php echo esc_view(addslashes($default_success)); ?>'); resetNotificationMessage('<?php echo $error_key; ?>', '<?php echo esc_view(addslashes($default_error)); ?>');">
                                  <i class="bi bi-arrow-counterclockwise text-secondary"></i>
                                </button>
                              </td>
                              <td>
                                <input type="text" class="form-control form-control-sm" 
                                       name="<?php echo esc_view($success_key); ?>" 
                                       value="<?php echo esc_view($current_success); ?>"
                                       placeholder="<?php echo esc_view($default_success); ?>"
                                       data-default="<?php echo esc_view($default_success); ?>">
                              </td>
                              <td>
                                <input type="text" class="form-control form-control-sm" 
                                       name="<?php echo esc_view($error_key); ?>" 
                                       value="<?php echo esc_view($current_error); ?>"
                                       placeholder="<?php echo esc_view($default_error); ?>"
                                       data-default="<?php echo esc_view($default_error); ?>">
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          
          <div class="alert alert-info mt-3 mb-0">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Tip:</strong> You can use placeholders in messages like <code>{name}</code> or <code>{count}</code> that will be replaced with actual values.
            <br>Leave a field empty to use the default message. Click the reset icon next to each action to restore default messages.
          </div>
          
          <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg me-1"></i> Save Notification Messages
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetAllNotificationMessages()">
              <i class="bi bi-arrow-clockwise me-1"></i> Reset All to Defaults
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- General & Display Settings Tab -->
  <div class="tab-pane fade" id="general" role="tabpanel">
    <div class="row g-4">
      <!-- Display & Format Settings -->
      <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-light border-0">
            <h6 class="mb-0 fw-bold">
              <i class="bi bi-display text-primary me-2"></i>Display & Format Settings
            </h6>
          </div>
          <div class="card-body">
            <form method="post" action="<?php echo site_url('settings/update'); ?>" id="generalDisplayForm">
              <input type="hidden" name="form_section" value="general_display">
              <div class="mb-3">
                <label class="form-label fw-semibold">Date Format</label>
                <select class="form-select" name="system_date_format">
                  <option value="Y-m-d" <?php echo (isset($settings['system_date_format']) && $settings['system_date_format'] === 'Y-m-d') || (!isset($settings['system_date_format'])) ? 'selected' : ''; ?>>YYYY-MM-DD (2024-12-31)</option>
                  <option value="d/m/Y" <?php echo (isset($settings['system_date_format']) && $settings['system_date_format'] === 'd/m/Y') ? 'selected' : ''; ?>>DD/MM/YYYY (31/12/2024)</option>
                  <option value="m/d/Y" <?php echo (isset($settings['system_date_format']) && $settings['system_date_format'] === 'm/d/Y') ? 'selected' : ''; ?>>MM/DD/YYYY (12/31/2024)</option>
                  <option value="d-m-Y" <?php echo (isset($settings['system_date_format']) && $settings['system_date_format'] === 'd-m-Y') ? 'selected' : ''; ?>>DD-MM-YYYY (31-12-2024)</option>
                  <option value="M d, Y" <?php echo (isset($settings['system_date_format']) && $settings['system_date_format'] === 'M d, Y') ? 'selected' : ''; ?>>Dec 31, 2024</option>
                  <option value="d M Y" <?php echo (isset($settings['system_date_format']) && $settings['system_date_format'] === 'd M Y') ? 'selected' : ''; ?>>31 Dec 2024</option>
                </select>
                <div class="form-text">Date format used throughout the application</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Time Format</label>
                <select class="form-select" name="system_time_format">
                  <option value="24h" <?php echo (isset($settings['system_time_format']) && $settings['system_time_format'] === '24h') || (!isset($settings['system_time_format'])) ? 'selected' : ''; ?>>24 Hour (14:30)</option>
                  <option value="12h" <?php echo (isset($settings['system_time_format']) && $settings['system_time_format'] === '12h') ? 'selected' : ''; ?>>12 Hour (2:30 PM)</option>
                </select>
                <div class="form-text">Time format used throughout the application</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Currency</label>
                <input type="text" class="form-control" name="system_currency" 
                       value="<?php echo esc_view(isset($settings['system_currency']) ? $settings['system_currency'] : 'USD'); ?>" 
                       placeholder="USD" maxlength="3" style="text-transform:uppercase;">
                <div class="form-text">ISO currency code (e.g., USD, INR, EUR, GBP)</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Currency Symbol</label>
                <input type="text" class="form-control" name="system_currency_symbol" 
                       value="<?php echo esc_view(isset($settings['system_currency_symbol']) ? $settings['system_currency_symbol'] : '$'); ?>" 
                       placeholder="$" maxlength="5">
                <div class="form-text">Symbol displayed with currency amounts</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Default Items Per Page</label>
                <select class="form-select" name="system_items_per_page">
                  <option value="10" <?php echo (isset($settings['system_items_per_page']) && $settings['system_items_per_page'] == '10') ? 'selected' : ''; ?>>10</option>
                  <option value="20" <?php echo (isset($settings['system_items_per_page']) && $settings['system_items_per_page'] == '20') || (!isset($settings['system_items_per_page'])) ? 'selected' : ''; ?>>20</option>
                  <option value="50" <?php echo (isset($settings['system_items_per_page']) && $settings['system_items_per_page'] == '50') ? 'selected' : ''; ?>>50</option>
                  <option value="100" <?php echo (isset($settings['system_items_per_page']) && $settings['system_items_per_page'] == '100') ? 'selected' : ''; ?>>100</option>
                </select>
                <div class="form-text">Default number of records per page in lists</div>
              </div>
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-check-lg me-1"></i> Save Display Settings
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Employee & HR Settings -->
      <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-light border-0">
            <h6 class="mb-0 fw-bold">
              <i class="bi bi-people text-success me-2"></i>Employee & HR Settings
            </h6>
          </div>
          <div class="card-body">
            <form method="post" action="<?php echo site_url('settings/update'); ?>" id="generalHRForm">
              <input type="hidden" name="form_section" value="general_hr">
              <div class="mb-3">
                <label class="form-label fw-semibold">Employee Code Prefix</label>
                <input type="text" class="form-control" name="system_employee_code_prefix" 
                       value="<?php echo esc_view(isset($settings['system_employee_code_prefix']) ? $settings['system_employee_code_prefix'] : 'EMP'); ?>" 
                       placeholder="EMP" maxlength="10">
                <div class="form-text">Prefix for auto-generated employee codes (e.g., EMP001, DEV001)</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Employee Code Length</label>
                <input type="number" class="form-control" name="system_employee_code_length" 
                       value="<?php echo esc_view(isset($settings['system_employee_code_length']) ? $settings['system_employee_code_length'] : '3'); ?>" 
                       min="2" max="6">
                <div class="form-text">Number of digits in employee code (e.g., 3 = EMP001, 4 = EMP0001)</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Default Probation Period (days)</label>
                <input type="number" class="form-control" name="system_probation_period_days" 
                       value="<?php echo esc_view(isset($settings['system_probation_period_days']) ? $settings['system_probation_period_days'] : '90'); ?>" 
                       min="0" max="365">
                <div class="form-text">Default probation period for new employees</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Notice Period (days)</label>
                <input type="number" class="form-control" name="system_notice_period_days" 
                       value="<?php echo esc_view(isset($settings['system_notice_period_days']) ? $settings['system_notice_period_days'] : '30'); ?>" 
                       min="0" max="180">
                <div class="form-text">Standard notice period for employee resignation</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Working Days Per Week</label>
                <input type="number" class="form-control" name="system_working_days_per_week" 
                       value="<?php echo esc_view(isset($settings['system_working_days_per_week']) ? $settings['system_working_days_per_week'] : '5'); ?>" 
                       min="1" max="7" step="0.5">
                <div class="form-text">Average working days per week for calculations</div>
              </div>
              <button type="submit" class="btn btn-success w-100">
                <i class="bi bi-check-lg me-1"></i> Save HR Settings
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- System & Maintenance Settings -->
      <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-light border-0">
            <h6 class="mb-0 fw-bold">
              <i class="bi bi-gear text-warning me-2"></i>System & Maintenance
            </h6>
          </div>
          <div class="card-body">
            <form method="post" action="<?php echo site_url('settings/update'); ?>" id="generalSystemForm">
              <input type="hidden" name="form_section" value="general_system">
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="system_maintenance_mode" value="yes" 
                         <?php echo (isset($settings['system_maintenance_mode']) && $settings['system_maintenance_mode'] === 'yes') ? 'checked' : ''; ?> 
                         id="system_maintenance_mode">
                  <label class="form-check-label" for="system_maintenance_mode">
                    Enable Maintenance Mode
                  </label>
                </div>
                <div class="form-text">When enabled, only admins can access the system</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Global Maintenance Message</label>
                <textarea class="form-control" name="system_maintenance_message" rows="3" 
                          placeholder="The system is currently under maintenance. Please try again later."><?php echo esc_view(isset($settings['system_maintenance_message']) ? $settings['system_maintenance_message'] : 'The system is currently under maintenance. Please try again later.'); ?></textarea>
                <div class="form-text">Default message shown to non-admin users when no module-specific message is set</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Module-Specific Maintenance Messages</label>
                <div class="alert alert-info d-flex align-items-start mb-3">
                  <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                  <div>
                    <small>Configure maintenance messages for specific modules. When a module is in maintenance, users will see the module-specific message instead of the global message. Leave empty to use the global message.</small>
                  </div>
                </div>
                <div class="accordion" id="moduleMaintenanceAccordion">
                  <?php
                  $modules_list = [
                    'dashboard' => 'Dashboard',
                    'employees' => 'Employees',
                    'users' => 'Users',
                    'projects' => 'Projects',
                    'tasks' => 'Tasks',
                    'attendance' => 'Attendance',
                    'leaves' => 'Leaves',
                    'departments' => 'Departments',
                    'designations' => 'Designations',
                    'clients' => 'Clients',
                    'assets' => 'Assets',
                    'announcements' => 'Announcements',
                    'chats' => 'Chats',
                    'calls' => 'Calls',
                    'timesheets' => 'Timesheets',
                    'reports' => 'Reports',
                    'settings' => 'Settings',
                    'reminders' => 'Reminders',
                    'activity' => 'Activity Log',
                    'permissions' => 'Permissions',
                    'payroll' => 'Payroll'
                  ];
                  foreach ($modules_list as $module_key => $module_label):
                    $module_maintenance_enabled_key = 'system_maintenance_module_' . $module_key;
                    $module_maintenance_message_key = 'system_maintenance_module_' . $module_key . '_message';
                    $module_enabled = isset($settings[$module_maintenance_enabled_key]) && $settings[$module_maintenance_enabled_key] === 'yes';
                    $module_message = isset($settings[$module_maintenance_message_key]) ? $settings[$module_maintenance_message_key] : '';
                  ?>
                    <div class="accordion-item">
                      <h2 class="accordion-header" id="headingMaintenance<?php echo ucfirst($module_key); ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMaintenance<?php echo ucfirst($module_key); ?>" aria-expanded="false">
                          <i class="bi bi-box me-2"></i> <?php echo esc_view($module_label); ?>
                        </button>
                      </h2>
                      <div id="collapseMaintenance<?php echo ucfirst($module_key); ?>" class="accordion-collapse collapse" aria-labelledby="headingMaintenance<?php echo ucfirst($module_key); ?>" data-bs-parent="#moduleMaintenanceAccordion">
                        <div class="accordion-body">
                          <div class="mb-3">
                            <div class="form-check form-switch">
                              <input class="form-check-input" type="checkbox" name="<?php echo $module_maintenance_enabled_key; ?>" value="yes" 
                                     <?php echo $module_enabled ? 'checked' : ''; ?> 
                                     id="<?php echo $module_maintenance_enabled_key; ?>">
                              <label class="form-check-label" for="<?php echo $module_maintenance_enabled_key; ?>">
                                Enable Maintenance for <?php echo esc_view($module_label); ?>
                              </label>
                            </div>
                            <div class="form-text">When enabled, this module will be unavailable to non-admin users</div>
                          </div>
                          <div class="mb-3">
                            <label class="form-label fw-semibold">Maintenance Message for <?php echo esc_view($module_label); ?></label>
                            <textarea class="form-control" name="<?php echo $module_maintenance_message_key; ?>" rows="2" 
                                      placeholder="The <?php echo esc_view($module_label); ?> module is currently under maintenance. Please try again later."><?php echo esc_view($module_message); ?></textarea>
                            <div class="form-text">Leave empty to use global maintenance message</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Log Retention Period (days)</label>
                <input type="number" class="form-control" name="system_log_retention_days" 
                       value="<?php echo esc_view(isset($settings['system_log_retention_days']) ? $settings['system_log_retention_days'] : '90'); ?>" 
                       min="7" max="365">
                <div class="form-text">How long to keep activity and audit logs (7-365 days)</div>
              </div>
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="system_enable_debug_mode" value="yes" 
                         <?php echo (isset($settings['system_enable_debug_mode']) && $settings['system_enable_debug_mode'] === 'yes') ? 'checked' : ''; ?> 
                         id="system_enable_debug_mode">
                  <label class="form-check-label" for="system_enable_debug_mode">
                    Enable Debug Mode
                  </label>
                </div>
                <div class="form-text">Show detailed error messages (disable in production)</div>
              </div>

              <button type="submit" class="btn btn-warning w-100">
                <i class="bi bi-check-lg me-1"></i> Save System Settings
              </button>
            </form>
          </div>
        </div>
      </div>
      <!-- Location & Office Settings (Moved into General) -->
      <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-light border-0">
            <h6 class="mb-0 fw-bold">
              <i class="bi bi-geo-alt text-info me-2"></i>Location & Office Settings
            </h6>
          </div>
          <div class="card-body">
            <form method="post" action="<?php echo site_url('settings/update'); ?>" id="generalLocationForm">
              <input type="hidden" name="form_section" value="general_location">
              <div class="mb-3">
                <label class="form-label fw-semibold">Default Office Location Name</label>
                <input type="text" class="form-control" name="system_default_office_location" 
                       value="<?php echo esc_view(isset($settings['system_default_office_location']) ? $settings['system_default_office_location'] : ''); ?>" 
                       placeholder="Head Office">
                <div class="form-text">Default location name for attendance</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Office Latitude</label>
                <input type="text" class="form-control" name="system_office_latitude" 
                       value="<?php echo esc_view(isset($settings['system_office_latitude']) ? $settings['system_office_latitude'] : ''); ?>" 
                       placeholder="28.6139">
                <div class="form-text">Office location latitude for attendance validation</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Office Longitude</label>
                <input type="text" class="form-control" name="system_office_longitude" 
                       value="<?php echo esc_view(isset($settings['system_office_longitude']) ? $settings['system_office_longitude'] : ''); ?>" 
                       placeholder="77.2090">
                <div class="form-text">Office location longitude for attendance validation</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Allowed Radius (meters)</label>
                <input type="number" class="form-control" name="system_attendance_radius_meters" 
                       value="<?php echo esc_view(isset($settings['system_attendance_radius_meters']) ? $settings['system_attendance_radius_meters'] : '100'); ?>" 
                       min="10" max="5000" step="10">
                <div class="form-text">Maximum distance from office to mark attendance (10-5000 meters)</div>
              </div>
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="system_enable_location_strict" value="yes" 
                         <?php echo (isset($settings['system_enable_location_strict']) && $settings['system_enable_location_strict'] === 'yes') ? 'checked' : ''; ?> 
                         id="system_enable_location_strict">
                  <label class="form-check-label" for="system_enable_location_strict">
                    Strict Location Validation
                  </label>
                </div>
                <div class="form-text">Enforce location check for attendance marking</div>
              </div>
              <button type="submit" class="btn btn-info w-100 text-white">
                <i class="bi bi-check-lg me-1"></i> Save Location Settings
              </button>
            </form>
          </div>
        </div>
      </div>
    </div> <!-- End Row -->

    <?php if (function_exists('has_module_access') && (has_module_access('types') || has_module_access('settings') || has_module_access('admin'))): ?>
    <div class="card shadow-sm mt-4 border-0">
      <div class="card-header bg-light border-0">
        <h6 class="mb-0 fw-bold">
          <i class="bi bi-ui-checks-grid text-primary me-2"></i>Module Type Management
        </h6>
      </div>
      <div class="card-body">
        <p class="text-muted mb-3">Configure types for My Works, Clients, Projects, Requirements, and Employees. These appear in module dropdowns, filters, and validation.</p>
        <a href="<?php echo site_url('settings/types'); ?>" class="btn btn-primary">
          <i class="bi bi-gear me-1"></i>Manage Module Types
        </a>
      </div>
    </div>
    <?php endif; ?>
  </div> <!-- End General Tab -->

  <!-- AI Integration Tab -->
  <!-- AI Integration Tab -->
  <div class="tab-pane fade" id="ai_integration" role="tabpanel">
    <div class="card shadow-sm">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="bi bi-robot me-2"></i>AI Service Configuration</h5>
        <div>
             <button type="button" class="btn btn-primary btn-sm rounded-circle shadow-sm" data-bs-toggle="modal" data-bs-target="#aiConfigModal" title="Configure AI Services">
                <i class="bi bi-plus-lg"></i>
             </button>
        </div>
      </div>
      <div class="card-body">
         <!-- Status Overview List -->
         <div class="list-group list-group-flush">
            <?php 
               $ai_services = [
                   'openai' => ['name' => 'OpenAI', 'icon' => 'bi-cpu', 'color' => 'success', 'desc' => 'Premium Provider (GPT-4o).'],
                   'gemini' => ['name' => 'Google Gemini', 'icon' => 'bi-google', 'color' => 'primary', 'desc' => 'Primary Provider for reasoning and RAG.'],
                   'openrouter' => ['name' => 'OpenRouter', 'icon' => 'bi-motherboard', 'color' => 'dark', 'desc' => 'Fallback provider for advanced models.'],
                   'huggingface' => ['name' => 'Hugging Face', 'icon' => 'bi-emoji-smile', 'color' => 'warning', 'desc' => 'Backup provider for open-source models.'],
                   'azure_speech' => ['name' => 'Azure Speech', 'icon' => 'bi-mic', 'color' => 'info', 'desc' => 'Text-to-Speech and Speech-to-Text services.']
               ];
               
               $has_active = false;
               foreach($ai_services as $key => $service):
                   $enabled_key = 'ai_' . $key . '_enabled';
                   // Strict check: defaults to false if not set
                   $is_active = isset($settings[$enabled_key]) && $settings[$enabled_key] === 'yes';
                   
                   if(!$is_active) continue;
                   $has_active = true;
            ?>
            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
               <div class="d-flex align-items-center">
                  <div class="rounded-circle bg-<?php echo $service['color']; ?> bg-opacity-10 p-2 me-3">
                     <i class="bi <?php echo $service['icon']; ?> text-<?php echo $service['color']; ?> fs-4"></i>
                  </div>
                  <div>
                     <h6 class="mb-0 fw-bold"><?php echo $service['name']; ?></h6>
                     <small class="text-muted"><?php echo $service['desc']; ?></small>
                  </div>
               </div>
               <span class="badge bg-success rounded-pill">Active</span>
            </div>
            <?php endforeach; ?>
            
            <?php if(!$has_active): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-robot display-4 d-block mb-3 opacity-25"></i>
                <p>No AI services are currently active.</p>
                <p class="small">Click the <strong>+</strong> button to configure and enable a service.</p>
            </div>
            <?php endif; ?>
         </div>
         
         <div class="mt-4 text-center">
            <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Configured services are prioritized in the order: Gemini > OpenRouter > Hugging Face.</small>
         </div>
      </div>
    </div>
  </div>

  <!-- AI Configuration Modal -->
  <div class="modal fade" id="aiConfigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Configure AI Services</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3" id="aiForm">
              <input type="hidden" name="form_section" value="ai_integration">
              
              <div class="row g-3">
                <!-- Google Gemini -->
                <div class="col-md-6">
                  <div class="card h-100 border-primary border-opacity-25">
                     <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <label class="fw-bold text-primary"><i class="bi bi-google me-1"></i> Google Gemini</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ai_gemini_enabled" value="yes" id="ai_gemini_enabled" <?php echo (isset($settings['ai_gemini_enabled']) && $settings['ai_gemini_enabled'] === 'yes') || !isset($settings['ai_gemini_enabled']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ai_gemini_enabled">Active</label>
                            </div>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Key</span>
                            <input type="password" class="form-control" name="ai_gemini_api_key" value="<?php echo esc_view(isset($settings['ai_gemini_api_key']) ? $settings['ai_gemini_api_key'] : ''); ?>" id="geminiKey">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('geminiKey')"><i class="bi bi-eye"></i></button>
                        </div>
                        <small class="text-muted d-block mt-1">Status: Primary Provider</small>
                     </div>
                  </div>
                </div>

                <!-- OpenRouter -->
                <div class="col-md-6">
                   <div class="card h-100 border-secondary border-opacity-25">
                     <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <label class="fw-bold text-dark"><i class="bi bi-cpu me-1"></i> OpenRouter</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ai_openrouter_enabled" value="yes" id="ai_openrouter_enabled" <?php echo (isset($settings['ai_openrouter_enabled']) && $settings['ai_openrouter_enabled'] === 'yes') || !isset($settings['ai_openrouter_enabled']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ai_openrouter_enabled">Active</label>
                            </div>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Key</span>
                            <input type="password" class="form-control" name="ai_openrouter_api_key" value="<?php echo esc_view(isset($settings['ai_openrouter_api_key']) ? $settings['ai_openrouter_api_key'] : ''); ?>" id="openrouterKey">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('openrouterKey')"><i class="bi bi-eye"></i></button>
                        </div>
                        <small class="text-muted d-block mt-1">Status: Fallback Provider</small>
                     </div>
                  </div>
                </div>

                <!-- Hugging Face -->
                <div class="col-md-6">
                    <div class="card h-100 border-warning border-opacity-25">
                     <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <label class="fw-bold text-warning"><i class="bi bi-emoji-smile me-1"></i> Hugging Face</label>
                             <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ai_huggingface_enabled" value="yes" id="ai_huggingface_enabled" <?php echo (isset($settings['ai_huggingface_enabled']) && $settings['ai_huggingface_enabled'] === 'yes') || !isset($settings['ai_huggingface_enabled']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ai_huggingface_enabled">Active</label>
                            </div>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Key</span>
                            <input type="password" class="form-control" name="ai_huggingface_api_key" value="<?php echo esc_view(isset($settings['ai_huggingface_api_key']) ? $settings['ai_huggingface_api_key'] : ''); ?>" id="hfKey">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('hfKey')"><i class="bi bi-eye"></i></button>
                        </div>
                        <small class="text-muted d-block mt-1">Status: Backup Provider</small>
                     </div>
                  </div>
                </div>

                <!-- Azure Speech -->
                <div class="col-md-6">
                   <div class="card h-100 border-info border-opacity-25">
                     <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <label class="fw-bold text-info"><i class="bi bi-mic me-1"></i> Azure Speech</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ai_azure_speech_enabled" value="yes" id="ai_azure_speech_enabled" <?php echo (isset($settings['ai_azure_speech_enabled']) && $settings['ai_azure_speech_enabled'] === 'yes') || !isset($settings['ai_azure_speech_enabled']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ai_azure_speech_enabled">Active</label>
                            </div>
                        </div>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text">Key</span>
                            <input type="password" class="form-control" name="ai_azure_speech_key" value="<?php echo esc_view(isset($settings['ai_azure_speech_key']) ? $settings['ai_azure_speech_key'] : ''); ?>" id="azureKey">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('azureKey')"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="input-group input-group-sm">
                             <span class="input-group-text">Region</span>
                            <input type="text" class="form-control" name="ai_azure_speech_region" value="<?php echo esc_view(isset($settings['ai_azure_speech_region']) ? $settings['ai_azure_speech_region'] : 'eastus'); ?>" placeholder="e.g. eastus">
                        </div>
                     </div>
                  </div>
                </div>

                <!-- OpenAI -->
                <div class="col-md-6">
                   <div class="card h-100 border-success border-opacity-25">
                     <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <label class="fw-bold text-success"><i class="bi bi-cpu me-1"></i> OpenAI</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ai_openai_enabled" value="yes" id="ai_openai_enabled" <?php echo (isset($settings['ai_openai_enabled']) && $settings['ai_openai_enabled'] === 'yes') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ai_openai_enabled">Active</label>
                            </div>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Key</span>
                            <input type="password" class="form-control" name="ai_openai_api_key" value="<?php echo esc_view(isset($settings['ai_openai_api_key']) ? $settings['ai_openai_api_key'] : ''); ?>" id="openaiKey" placeholder="sk-...">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('openaiKey')"><i class="bi bi-eye"></i></button>
                        </div>
                        <small class="text-muted d-block mt-1">Status: Premium Provider</small>
                     </div>
                   </div>
                </div>
              </div>

              <!-- Custom AI Providers Section -->
              <div class="col-12 mt-4">
                 <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-puzzle me-2"></i>Custom AI Providers</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCustomProviderRow()">
                        <i class="bi bi-plus-lg me-1"></i> Add Custom Service
                    </button>
                 </div>
                 
                 <div id="customAiProvidersList" class="vstack gap-2">
                    <?php 
                        $custom_providers = isset($settings['ai_custom_providers']) ? json_decode($settings['ai_custom_providers'], true) : [];
                        if (!is_array($custom_providers)) $custom_providers = [];
                        
                        foreach($custom_providers as $index => $provider): 
                    ?>
                    <div class="row g-2 align-items-center custom-provider-row" id="provider_row_<?php echo $index; ?>">
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" name="ai_custom_providers[<?php echo $index; ?>][name]" value="<?php echo esc_view(isset($provider['name']) ? $provider['name'] : ''); ?>" placeholder="Provider Name (e.g. Anthropic)" required>
                        </div>
                        <div class="col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input type="password" class="form-control" name="ai_custom_providers[<?php echo $index; ?>][key]" value="<?php echo esc_view(isset($provider['key']) ? $provider['key'] : ''); ?>" placeholder="API Key" id="customKey_<?php echo $index; ?>">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('customKey_<?php echo $index; ?>')"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-check form-switch pt-1">
                                <input class="form-check-input" type="checkbox" name="ai_custom_providers[<?php echo $index; ?>][enabled]" value="1" id="custom_enabled_<?php echo $index; ?>" <?php echo (isset($provider['enabled']) && $provider['enabled'] == '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="custom_enabled_<?php echo $index; ?>">Active</label>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeCustomProviderRow('provider_row_<?php echo $index; ?>')"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                 </div>
                 <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i> Add any other AI services you wish to integrate. These will be available for selection in supported modules.</div>
              </div>

              <div class="d-flex justify-content-end gap-2 mt-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary px-4">
                  <i class="bi bi-save me-1"></i> Save Changes
                </button>
              </div>
            </form>
        </div>
      </div>
    </div>
  </div>
  <div class="tab-pane fade" id="security" role="tabpanel">
    <div class="card shadow-sm">
       <div class="card-header bg-light">
          <h5 class="card-title mb-0"><i class="bi bi-shield-check me-2"></i>Security & Protection</h5>
       </div>
       <div class="card-body">
         <div class="accordion" id="securityAccordion">
            
            <!-- Password Policy -->
            <div class="accordion-item">
               <h2 class="accordion-header" id="headingPassword">
                 <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePassword">
                   <i class="bi bi-key me-2 text-primary"></i> Password Policy
                 </button>
               </h2>
               <div id="collapsePassword" class="accordion-collapse collapse show" data-bs-parent="#securityAccordion">
                  <div class="accordion-body">
                     <form method="post" action="<?php echo site_url('settings/update'); ?>" id="securityPasswordForm">
                        <input type="hidden" name="form_section" value="security_password">
                        <div class="row g-3">
                           <div class="col-md-3">
                              <label class="form-label fw-semibold small">Min Length</label>
                              <input type="number" class="form-control form-control-sm" name="security_min_password_length" value="<?php echo esc_view(isset($settings['security_min_password_length']) ? $settings['security_min_password_length'] : '8'); ?>" min="6" max="32">
                           </div>
                           <div class="col-md-3">
                               <label class="form-label fw-semibold small">Expiry (Days)</label>
                               <input type="number" class="form-control form-control-sm" name="security_password_expiry" value="<?php echo esc_view(isset($settings['security_password_expiry']) ? $settings['security_password_expiry'] : '90'); ?>" min="0" max="365">
                           </div>
                           <div class="col-md-6 d-flex align-items-center justify-content-between flex-wrap gap-2">
                               <div class="form-check form-switch">
                                   <input class="form-check-input" type="checkbox" name="security_require_uppercase" value="yes" id="req_upper" <?php echo (isset($settings['security_require_uppercase']) && $settings['security_require_uppercase'] === 'yes') ? 'checked' : ''; ?>>
                                   <label class="form-check-label small" for="req_upper">Uppercase</label>
                               </div>
                               <div class="form-check form-switch">
                                   <input class="form-check-input" type="checkbox" name="security_require_lowercase" value="yes" id="req_lower" <?php echo (isset($settings['security_require_lowercase']) && $settings['security_require_lowercase'] === 'yes') ? 'checked' : ''; ?>>
                                   <label class="form-check-label small" for="req_lower">Lowercase</label>
                               </div>
                               <div class="form-check form-switch">
                                   <input class="form-check-input" type="checkbox" name="security_require_number" value="yes" id="req_num" <?php echo (isset($settings['security_require_number']) && $settings['security_require_number'] === 'yes') ? 'checked' : ''; ?>>
                                   <label class="form-check-label small" for="req_num">Number</label>
                               </div>
                               <div class="form-check form-switch">
                                   <input class="form-check-input" type="checkbox" name="security_require_special" value="yes" id="req_spec" <?php echo (isset($settings['security_require_special']) && $settings['security_require_special'] === 'yes') ? 'checked' : ''; ?>>
                                   <label class="form-check-label small" for="req_spec">Special Char</label>
                               </div>
                           </div>
                           <div class="col-12 text-end">
                               <button type="submit" class="btn btn-sm btn-primary">Save Password Policy</button>
                           </div>
                        </div>
                     </form>
                  </div>
               </div>
            </div>

            <!-- Session & 2FA -->
            <div class="accordion-item">
               <h2 class="accordion-header" id="headingSession">
                 <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSession">
                   <i class="bi bi-shield-lock me-2 text-warning"></i> Session & 2FA
                 </button>
               </h2>
               <div id="collapseSession" class="accordion-collapse collapse" data-bs-parent="#securityAccordion">
                  <div class="accordion-body">
                     <div class="row g-4">
                        <!-- Session -->
                        <div class="col-md-6 border-end">
                           <h6 class="text-secondary mb-3">Session Management</h6>
                           <form method="post" action="<?php echo site_url('settings/update'); ?>" id="securitySessionForm">
                              <input type="hidden" name="form_section" value="security_session">
                              <div class="row g-2">
                                 <div class="col-md-6">
                                     <label class="form-label small fw-bold">Timeout (Min)</label>
                                     <input type="number" class="form-control form-control-sm" name="security_session_timeout" value="<?php echo esc_view(isset($settings['security_session_timeout']) ? $settings['security_session_timeout'] : '60'); ?>">
                                 </div>
                                 <div class="col-md-6">
                                     <label class="form-label small fw-bold">Lockout (Min)</label>
                                     <input type="number" class="form-control form-control-sm" name="security_lockout_duration" value="<?php echo esc_view(isset($settings['security_lockout_duration']) ? $settings['security_lockout_duration'] : '15'); ?>">
                                 </div>
                                 <div class="col-md-12">
                                     <div class="form-check form-switch mb-1">
                                         <input class="form-check-input" type="checkbox" name="security_single_session" value="yes" id="single_sess" <?php echo (isset($settings['security_single_session']) && $settings['security_single_session'] === 'yes') ? 'checked' : ''; ?>>
                                         <label class="form-check-label small" for="single_sess">Single Session per User</label>
                                     </div>
                                     <div class="form-check form-switch">
                                         <input class="form-check-input" type="checkbox" name="security_remember_me" value="yes" id="rem_me" <?php echo (isset($settings['security_remember_me']) && $settings['security_remember_me'] === 'yes') ? 'checked' : ''; ?>>
                                         <label class="form-check-label small" for="rem_me">Enable 'Remember Me'</label>
                                     </div>
                                 </div>
                                 <div class="col-12 mt-2 text-end">
                                     <button type="submit" class="btn btn-sm btn-warning text-white">Save Session</button>
                                 </div>
                              </div>
                           </form>
                        </div>
                        <!-- 2FA -->
                        <div class="col-md-6">
                           <h6 class="text-secondary mb-3">Two-Factor Auth</h6>
                           <form method="post" action="<?php echo site_url('settings/update'); ?>" id="security2FAForm">
                              <input type="hidden" name="form_section" value="security_2fa">
                              <div class="row g-2">
                                 <div class="col-md-6">
                                     <label class="form-label small fw-bold">2FA Method</label>
                                     <select class="form-select form-select-sm" name="security_2fa_method">
                                        <option value="email" <?php echo (isset($settings['security_2fa_method']) && $settings['security_2fa_method'] === 'email') ? 'selected' : ''; ?>>Email OTP</option>
                                        <option value="sms" <?php echo (isset($settings['security_2fa_method']) && $settings['security_2fa_method'] === 'sms') ? 'selected' : ''; ?>>SMS OTP</option>
                                        <option value="app" <?php echo (isset($settings['security_2fa_method']) && $settings['security_2fa_method'] === 'app') ? 'selected' : ''; ?>>Auth App</option>
                                     </select>
                                 </div>
                                 <div class="col-md-12">
                                     <div class="form-check form-switch mb-1">
                                         <input class="form-check-input" type="checkbox" name="security_enable_2fa" value="yes" id="enable_2fa" <?php echo (isset($settings['security_enable_2fa']) && $settings['security_enable_2fa'] === 'yes') ? 'checked' : ''; ?>>
                                         <label class="form-check-label small fw-bold" for="enable_2fa">Enable 2FA Globally</label>
                                     </div>
                                     <div class="form-check form-switch">
                                         <input class="form-check-input" type="checkbox" name="security_2fa_required_admin" value="yes" id="req_2fa_admin" <?php echo (isset($settings['security_2fa_required_admin']) && $settings['security_2fa_required_admin'] === 'yes') ? 'checked' : ''; ?>>
                                         <label class="form-check-label small" for="req_2fa_admin">Enforce for Admins</label>
                                     </div>
                                 </div>
                                 <div class="col-12 mt-2 text-end">
                                     <button type="submit" class="btn btn-sm btn-warning text-white">Save 2FA</button>
                                 </div>
                              </div>
                           </form>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            
            <!-- IP & Audit -->
             <div class="accordion-item">
               <h2 class="accordion-header" id="headingAudit">
                 <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAudit">
                   <i class="bi bi-globe me-2 text-danger"></i> IP Access & Audit Logs
                 </button>
               </h2>
               <div id="collapseAudit" class="accordion-collapse collapse" data-bs-parent="#securityAccordion">
                  <div class="accordion-body">
                      <div class="row g-4">
                        <!-- IP Access -->
                        <div class="col-md-6 border-end">
                             <form method="post" action="<?php echo site_url('settings/update'); ?>" id="securityIPForm">
                                <input type="hidden" name="form_section" value="security_ip">
                                <div class=" mb-2">
                                     <div class="form-check form-switch">
                                         <input class="form-check-input" type="checkbox" name="security_enable_ip_whitelist" value="yes" id="ip_whitelist" <?php echo (isset($settings['security_enable_ip_whitelist']) && $settings['security_enable_ip_whitelist'] === 'yes') ? 'checked' : ''; ?>>
                                         <label class="form-check-label small fw-bold" for="ip_whitelist">Enable IP Whitelist</label>
                                     </div>
                                </div>
                                <textarea class="form-control form-control-sm mb-2" name="security_allowed_ips" rows="3" placeholder="192.168.1.1 (One per line)"><?php echo esc_view(isset($settings['security_allowed_ips']) ? $settings['security_allowed_ips'] : ''); ?></textarea>
                                <div class="text-end">
                                     <button type="submit" class="btn btn-sm btn-danger">Save IP Rules</button>
                                </div>
                             </form>
                        </div>
                        <!-- Audit Logs -->
                        <div class="col-md-6">
                            <form method="post" action="<?php echo site_url('settings/update'); ?>" id="securityAuditForm">
                                <input type="hidden" name="form_section" value="security_audit">
                                <div class="d-flex flex-wrap gap-3 mb-2">
                                     <div class="form-check form-switch">
                                         <input class="form-check-input" type="checkbox" name="security_audit_login" value="yes" id="audit_login" <?php echo (isset($settings['security_audit_login']) && $settings['security_audit_login'] === 'yes') ? 'checked' : ''; ?>>
                                         <label class="form-check-label small" for="audit_login">Log Logins</label>
                                     </div>
                                     <div class="form-check form-switch">
                                         <input class="form-check-input" type="checkbox" name="security_audit_settings" value="yes" id="audit_settings" <?php echo (isset($settings['security_audit_settings']) && $settings['security_audit_settings'] === 'yes') ? 'checked' : ''; ?>>
                                         <label class="form-check-label small" for="audit_settings">Log Settings</label>
                                     </div>
                                     <div class="form-check form-switch">
                                         <input class="form-check-input" type="checkbox" name="security_audit_data" value="yes" id="audit_data" <?php echo (isset($settings['security_audit_data']) && $settings['security_audit_data'] === 'yes') ? 'checked' : ''; ?>>
                                         <label class="form-check-label small" for="audit_data">Log Data</label>
                                     </div>
                                </div>
                                <div class="input-group input-group-sm mb-3">
                                   <span class="input-group-text">Retention (Days)</span>
                                   <input type="number" class="form-control" name="security_log_retention" value="<?php echo esc_view(isset($settings['security_log_retention']) ? $settings['security_log_retention'] : '90'); ?>" min="30" max="365">
                                </div>
                                <div class="text-end">
                                     <button type="submit" class="btn btn-sm btn-info text-white">Save Audit</button>
                                </div>
                            </form>
                        </div>
                      </div>
                  </div>
               </div>
            </div>
         </div>
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
    var csrfMatch = document.cookie.match(/(?:^|;\s*)ci_csrf_token=([^;]*)/);
    var csrfBody = csrfMatch ? '<?php echo $this->security->get_csrf_token_name(); ?>=' + encodeURIComponent(decodeURIComponent(csrfMatch[1])) : '';
    fetch('<?php echo site_url("settings/remove-logo"); ?>', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: csrfBody
    }).then(() => {
      location.reload();
    });
  }
}

// Handle weekend checkboxes
document.addEventListener('DOMContentLoaded', function() {
  // Initialize Bootstrap tabs explicitly
  const triggerTabList = document.querySelectorAll('#settingsTabs button[data-bs-toggle="tab"]');
  if (window.bootstrap && bootstrap.Tab) {
    triggerTabList.forEach(triggerEl => {
      const tabTrigger = new bootstrap.Tab(triggerEl);
      
      triggerEl.addEventListener('click', event => {
        event.preventDefault();
        tabTrigger.show();
      });
    });
  }
  
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

// Notification Messages Functions (Global scope for inline onclick)
function resetNotificationMessage(key, defaultValue) {
  const input = document.querySelector(`input[name="${key}"]`);
  if (input) {
    input.value = defaultValue;
  }
}

function resetAllNotificationMessages() {
  if (confirm('Are you sure you want to reset all notification messages to their defaults? This will undo any customizations you have made.')) {
    const inputs = document.querySelectorAll('#notificationMessagesForm input[data-default]');
    inputs.forEach(input => {
      input.value = input.getAttribute('data-default');
    });
  }
}

// Security tab enhancements
document.addEventListener('DOMContentLoaded', function() {
  // Add smooth transitions to security cards
  const securityCards = document.querySelectorAll('#security .card');
  securityCards.forEach(card => {
    card.style.transition = 'transform 0.2s ease, box-shadow 0.2s ease';
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-2px)';
      this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
    });
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
      this.style.boxShadow = '';
    });
  });

  // Form submission handling for notification messages form
  const notificationMessagesForm = document.getElementById('notificationMessagesForm');
  if (notificationMessagesForm) {
    notificationMessagesForm.addEventListener('submit', function(e) {
      const submitBtn = this.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        const originalHTML = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
        
        // Re-enable after 2 seconds in case of error
        setTimeout(() => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalHTML;
        }, 2000);
      }
    });
  }

  // Form submission handling for security forms
  const securityForms = document.querySelectorAll('#security form');
  securityForms.forEach(form => {
    form.addEventListener('submit', function(e) {
      const submitBtn = this.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        const originalHTML = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
        
        // Re-enable after 2 seconds in case of error
        setTimeout(() => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalHTML;
        }, 2000);
      }
    });
  });

  // IP whitelist toggle
  const ipWhitelistToggle = document.getElementById('security_enable_ip_whitelist');
  const ipTextarea = document.querySelector('textarea[name="security_allowed_ips"]');
  if (ipWhitelistToggle && ipTextarea) {
    function toggleIPField() {
      ipTextarea.disabled = !ipWhitelistToggle.checked;
      ipTextarea.style.opacity = ipWhitelistToggle.checked ? '1' : '0.5';
    }
    toggleIPField();
    ipWhitelistToggle.addEventListener('change', toggleIPField);
  }

  // 2FA toggle
  const enable2FAToggle = document.getElementById('security_enable_2fa');
  const twoFAMethod = document.querySelector('select[name="security_2fa_method"]');
  const twoFARequiredAdmin = document.getElementById('security_2fa_required_admin');
  if (enable2FAToggle) {
    function toggle2FAFields() {
      const isEnabled = enable2FAToggle.checked;
      if (twoFAMethod) {
        twoFAMethod.disabled = !isEnabled;
        twoFAMethod.style.opacity = isEnabled ? '1' : '0.5';
      }
      if (twoFARequiredAdmin) {
        twoFARequiredAdmin.disabled = !isEnabled;
        twoFARequiredAdmin.style.opacity = isEnabled ? '1' : '0.5';
      }
    }
    toggle2FAFields();
    enable2FAToggle.addEventListener('change', toggle2FAFields);
  }
});

// Custom AI Providers Functions
function addCustomProviderRow() {
    const list = document.getElementById('customAiProvidersList');
    const index = new Date().getTime(); // Unique index based on timestamp
    
    const div = document.createElement('div');
    div.className = 'row g-2 align-items-center custom-provider-row';
    div.id = 'provider_row_' + index;
    
    div.innerHTML = `
        <div class="col-md-4">
            <input type="text" class="form-control form-control-sm" name="ai_custom_providers[${index}][name]" placeholder="Provider Name" required>
        </div>
        <div class="col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-key"></i></span>
                <input type="password" class="form-control" name="ai_custom_providers[${index}][key]" placeholder="API Key" id="customKey_${index}">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('customKey_${index}')"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-check form-switch pt-1">
                <input class="form-check-input" type="checkbox" name="ai_custom_providers[${index}][enabled]" value="1" id="custom_enabled_${index}" checked>
                <label class="form-check-label small" for="custom_enabled_${index}">Active</label>
            </div>
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeCustomProviderRow('provider_row_${index}')"><i class="bi bi-trash"></i></button>
        </div>
    `;
    
    list.appendChild(div);
}

function removeCustomProviderRow(id) {
    const row = document.getElementById(id);
    if (row) {
        row.remove();
    }
}
</script>

<style>
#security .card {
  border: none;
  transition: all 0.3s ease;
}

#security .card:hover {
  box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
}

#security .bg-gradient {
  border-radius: 0.5rem;
}

#security .form-check-input:checked {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

#security .alert {
  border-left: 4px solid;
  border-radius: 0.5rem;
}

#security .alert-info {
  border-left-color: #0dcaf0;
  background-color: #cff4fc;
}

#security .alert-warning {
  border-left-color: #ffc107;
  background-color: #fff3cd;
}

#security .card-header {
  background-color: #f8f9fa !important;
  border-bottom: 2px solid #e9ecef;
  padding: 1rem 1.25rem;
}

#security .card-body {
  padding: 1.5rem;
}

#security .btn {
  font-weight: 500;
  padding: 0.625rem 1.25rem;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
}

#security .btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

@media (max-width: 768px) {
  #security .col-lg-6 {
    margin-bottom: 1.5rem;
  }
}
</style>

<?php if(!$can_save_settings): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Hide all submit buttons in settings forms for users without settings permission
  var forms = document.querySelectorAll('form');
  forms.forEach(function(form) {
    var btns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    btns.forEach(function(btn) { btn.style.display = 'none'; });
    // Also disable all inputs to make it read-only
    var inputs = form.querySelectorAll('input:not([type="hidden"]), select, textarea');
    inputs.forEach(function(inp) { inp.setAttribute('disabled', 'disabled'); });
  });
  // Show a notice
  var notice = document.createElement('div');
  notice.className = 'alert alert-warning alert-dismissible fade show mb-3';
  notice.innerHTML = '<i class="bi bi-lock me-2"></i><strong>Read-only:</strong> You do not have permission to modify system settings. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
  var container = document.querySelector('.container-fluid') || document.body;
  container.insertBefore(notice, container.firstChild);
});
</script>
<?php endif; ?>
<?php $this->load->view('partials/footer'); ?>
