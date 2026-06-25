<?php $this->load->view('partials/header', ['title' => 'My Email Preferences']); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-2">
      <i class="bi bi-person-gear me-2"></i>My Email Preferences
    </h1>
    <p class="text-muted mb-0">Choose which email notifications you want to receive</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?php echo site_url('email-settings'); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-gear me-1"></i>Global Settings
    </a>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header bg-light">
        <h5 class="mb-0">
          <i class="bi bi-person-check me-2"></i>Personal Email Preferences
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('email-settings/user-preferences'); ?>">
          <?php foreach ($grouped_settings as $module => $settings): ?>
            <div class="module-section mb-4">
              <div class="d-flex align-items-center mb-3">
                <h6 class="mb-0 me-2"><?php echo esc_view($modules[$module]['name']); ?></h6>
                <span class="badge bg-secondary"><?php echo esc_view($modules[$module]['description']); ?></span>
              </div>
              
              <div class="row">
                <?php foreach ($settings as $setting): ?>
                  <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card card-body h-100">
                      <div class="form-check form-switch">
                        <input type="checkbox" 
                               class="form-check-input" 
                               name="preferences[<?php echo esc_view($module); ?>][<?php echo esc_view($setting->event_type); ?>]" 
                               value="1" 
                               id="pref_<?php echo $setting->id; ?>"
                               <?php echo (isset($user_preferences[$module][$setting->event_type]) && $user_preferences[$module][$setting->event_type]) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="pref_<?php echo $setting->id; ?>">
                          <div class="fw-semibold"><?php echo esc_view($modules[$module]['events'][$setting->event_type]); ?></div>
                          <small class="text-muted"><?php echo esc_view($setting->event_type); ?></small>
                        </label>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            
            <?php 
            // PHP 5.6+ compatible way to get last array key
            $module_keys = array_keys($grouped_settings);
            $last_module = end($module_keys);
            if ($module !== $last_module): 
            ?>
              <hr class="my-4">
            <?php endif; ?>
          <?php endforeach; ?>
          
          <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save me-1"></i>Save Preferences
            </button>
            <a href="<?php echo site_url('dashboard'); ?>" class="btn btn-outline-secondary">
              <i class="bi bi-x-lg me-1"></i>Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header bg-light">
        <h6 class="mb-0">
          <i class="bi bi-info-circle me-2"></i>Email Notification Guide
        </h6>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <h6>Module Descriptions:</h6>
            <ul class="small">
              <li><strong>Tasks:</strong> Receive notifications for task assignments, updates, and status changes</li>
              <li><strong>Projects:</strong> Get notified about project updates and member changes</li>
              <li><strong>Leave Requests:</strong> Notifications for leave applications and approvals</li>
              <li><strong>Attendance:</strong> Daily attendance summaries and absence notifications</li>
            </ul>
          </div>
          <div class="col-md-6">
            <h6>Notification Types:</h6>
            <ul class="small">
              <li><strong>Created:</strong> When a new item is created</li>
              <li><strong>Updated:</strong> When an item is modified</li>
              <li><strong>Status Changed:</strong> When status changes</li>
              <li><strong>Daily/Weekly Summary:</strong> Regular summary emails</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
