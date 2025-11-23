<?php $this->load->view('partials/header', ['title' => 'Settings']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">System Settings</h1>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<ul class="nav nav-tabs" id="settingsTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#company" type="button" role="tab">Company</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab">Attendance</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#leave" type="button" role="tab">Leave</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">Email</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notify" type="button" role="tab">Notifications</button>
  </li>
</ul>
<div class="tab-content pt-3">
  <div class="tab-pane fade show active" id="company" role="tabpanel">
    <!-- Main company settings form (no file upload here) -->
    <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Company Name</label>
          <input class="form-control" name="company_name" value="<?php echo htmlspecialchars(isset($settings['company_name']) ? $settings['company_name'] : ''); ?>" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Company Email</label>
          <input class="form-control" name="company_email" value="<?php echo htmlspecialchars(isset($settings['company_email']) ? $settings['company_email'] : ''); ?>" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Phone</label>
          <input class="form-control" name="company_phone" value="<?php echo htmlspecialchars(isset($settings['company_phone']) ? $settings['company_phone'] : ''); ?>" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Timezone</label>
          <input class="form-control" name="company_timezone" value="<?php echo htmlspecialchars(isset($settings['company_timezone']) ? $settings['company_timezone'] : 'Asia/Kolkata'); ?>" />
        </div>
        <div class="col-md-12">
          <label class="form-label">Address</label>
          <textarea class="form-control" name="company_address" rows="2"><?php echo htmlspecialchars(isset($settings['company_address']) ? $settings['company_address'] : ''); ?></textarea>
        </div>
      </div>
      <div>
        <button class="btn btn-primary">Save</button>
      </div>
    </form>

    <!-- Separate logo upload form to avoid nested forms -->
    <form id="uploadLogoForm" method="post" action="<?php echo site_url('settings/upload-logo'); ?>" enctype="multipart/form-data" class="mt-3">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Logo</label>
          <div class="d-flex align-items-center gap-3">
            <input type="file" class="form-control" name="logo" />
            <?php if (isset($settings['company_logo']) && !empty($settings['company_logo'])): ?>
              <img src="<?php echo base_url($settings['company_logo']); ?>" alt="Logo" style="height:40px" />
            <?php endif; ?>
            <button class="btn btn-outline-secondary" type="submit">Upload</button>
          </div>
        </div>
      </div>
    </form>
  </div>

  <div class="tab-pane fade" id="attendance" role="tabpanel">
    <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Office Start Time</label>
          <input class="form-control" name="attendance_start_time" value="<?php echo htmlspecialchars(isset($settings['attendance_start_time']) ? $settings['attendance_start_time'] : '09:30'); ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Office End Time</label>
          <input class="form-control" name="attendance_end_time" value="<?php echo htmlspecialchars(isset($settings['attendance_end_time']) ? $settings['attendance_end_time'] : '18:30'); ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Grace Period (minutes)</label>
          <input class="form-control" name="attendance_grace_minutes" value="<?php echo htmlspecialchars(isset($settings['attendance_grace_minutes']) ? $settings['attendance_grace_minutes'] : '15'); ?>" />
        </div>
        <div class="col-md-12">
          <label class="form-label">Weekend Days (CSV of 0-6, where 0=Sunday)</label>
          <input class="form-control" name="attendance_weekends" value="<?php echo htmlspecialchars(isset($settings['attendance_weekends']) ? $settings['attendance_weekends'] : '0,6'); ?>" />
        </div>
      </div>
      <div>
        <button class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>

  <div class="tab-pane fade" id="leave" role="tabpanel">
    <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Carry Forward (yes/no)</label>
          <input class="form-control" name="leave_carry_forward" value="<?php echo htmlspecialchars(isset($settings['leave_carry_forward']) ? $settings['leave_carry_forward'] : 'yes'); ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Max Consecutive Days</label>
          <input class="form-control" name="leave_max_consecutive" value="<?php echo htmlspecialchars(isset($settings['leave_max_consecutive']) ? $settings['leave_max_consecutive'] : '14'); ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Minimum Gap Between Leaves (days)</label>
          <input class="form-control" name="leave_min_gap" value="<?php echo htmlspecialchars(isset($settings['leave_min_gap']) ? $settings['leave_min_gap'] : '1'); ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Default Annual Leave Days</label>
          <input class="form-control" type="number" step="0.5" min="0" name="leave_default_days" value="<?php echo htmlspecialchars(isset($settings['leave_default_days']) ? $settings['leave_default_days'] : '0'); ?>" />
        </div>
      </div>
      <div>
        <button class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>

  <div class="tab-pane fade" id="email" role="tabpanel">
    <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">SMTP User</label>
          <input class="form-control" name="email_smtp_user" value="<?php echo htmlspecialchars(isset($settings['email_smtp_user']) ? $settings['email_smtp_user'] : ''); ?>" />
        </div>
        <div class="col-md-6">
          <label class="form-label">SMTP Password</label>
          <input class="form-control" name="email_smtp_pass" value="<?php echo htmlspecialchars(isset($settings['email_smtp_pass']) ? $settings['email_smtp_pass'] : ''); ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">SMTP Host</label>
          <input class="form-control" name="email_smtp_host" value="<?php echo htmlspecialchars(isset($settings['email_smtp_host']) ? $settings['email_smtp_host'] : 'smtp.gmail.com'); ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">SMTP Port</label>
          <input class="form-control" name="email_smtp_port" value="<?php echo htmlspecialchars(isset($settings['email_smtp_port']) ? $settings['email_smtp_port'] : '587'); ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">SMTP Crypto (tls/ssl)</label>
          <input class="form-control" name="email_smtp_crypto" value="<?php echo htmlspecialchars(isset($settings['email_smtp_crypto']) ? $settings['email_smtp_crypto'] : 'tls'); ?>" />
        </div>
      </div>
      <div>
        <button class="btn btn-primary">Save</button>
        <button class="btn btn-outline-secondary" type="submit" formaction="<?php echo site_url('settings/test-email'); ?>">Send Test Email</button>
      </div>
    </form>
  </div>

  <div class="tab-pane fade" id="notify" role="tabpanel">
    <form method="post" action="<?php echo site_url('settings/update'); ?>" class="vstack gap-3">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Enable In-App Notifications (yes/no)</label>
          <input class="form-control" name="notify_in_app" value="<?php echo htmlspecialchars(isset($settings['notify_in_app']) ? $settings['notify_in_app'] : 'yes'); ?>" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Enable Email Notifications (yes/no)</label>
          <input class="form-control" name="notify_email" value="<?php echo htmlspecialchars(isset($settings['notify_email']) ? $settings['notify_email'] : 'yes'); ?>" />
        </div>
      </div>
      <div>
        <button class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
