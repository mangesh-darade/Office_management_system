<?php $this->load->view('partials/header', ['title' => 'Success Screen Settings']); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-2">
      <i class="bi bi-check-circle-fill me-2"></i>Success Screen Settings
    </h1>
    <p class="text-muted mb-0">Configure the success screen shown after user login</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?php echo site_url('system-settings'); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-gear me-1"></i>General Settings
    </a>
    <a href="<?php echo site_url('system-settings/permissions'); ?>" class="btn btn-outline-primary">
      <i class="bi bi-shield-lock me-1"></i>Permissions
    </a>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header bg-light">
        <h5 class="mb-0">
          <i class="bi bi-palette me-2"></i>Success Screen Configuration
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('system-settings/update_success_screen'); ?>">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Show Success Screen</label>
                <div class="form-check form-switch">
                  <input type="hidden" name="settings[show_success_screen]" value="0">
                  <input type="checkbox" 
                         class="form-check-input" 
                         name="settings[show_success_screen]" 
                         value="1" 
                         <?php echo (isset($ui_settings['show_success_screen']) ? $ui_settings['show_success_screen'] : '0') == '1' ? 'checked' : ''; ?>>
                  <label class="form-check-label">
                    Enable success screen after login
                  </label>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Display Duration (seconds)</label>
                <input type="number" 
                       class="form-control" 
                       name="settings[success_screen_duration]" 
                       value="<?php echo esc_view(isset($ui_settings['success_screen_duration']) ? $ui_settings['success_screen_duration'] : '3'); ?>"
                       min="1" 
                       max="10">
                <div class="form-text">How long to show the success screen before redirecting</div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Available Modules</label>
                <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                  <?php foreach ($all_modules as $key => $name): ?>
                    <div class="form-check mb-2">
                      <input type="checkbox" 
                             class="form-check-input" 
                             id="module_<?php echo $key; ?>" 
                             name="success_modules[]" 
                             value="<?php echo $key; ?>"
                             <?php echo in_array($key, $enabled_modules) ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="module_<?php echo $key; ?>">
                        <i class="bi bi-<?php echo get_module_icon($key); ?> me-1"></i>
                        <?php echo esc_view($name); ?>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="form-text">Select modules to display on the success screen</div>
              </div>
            </div>
          </div>
          
          <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save me-1"></i>Save Settings
            </button>
            <a href="<?php echo site_url('system-settings'); ?>" class="btn btn-outline-secondary">
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
          <i class="bi bi-info-circle me-2"></i>Success Screen Preview
        </h6>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <h6>How it works:</h6>
          <ol class="mb-0">
            <li>User successfully logs in to the system</li>
            <li>Success screen is displayed for the configured duration</li>
            <li>Only enabled and accessible modules are shown</li>
            <li>User can click any module to navigate directly</li>
            <li>After timeout, user is redirected to dashboard</li>
          </ol>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <h6>Current Configuration:</h6>
            <ul class="list-unstyled">
              <li><strong>Status:</strong> <?php echo (isset($ui_settings['show_success_screen']) ? $ui_settings['show_success_screen'] : '0') == '1' ? 'Enabled' : 'Disabled'; ?></li>
              <li><strong>Duration:</strong> <?php echo isset($ui_settings['success_screen_duration']) ? $ui_settings['success_screen_duration'] : '3'; ?> seconds</li>
              <li><strong>Modules:</strong> <?php echo count($enabled_modules); ?> selected</li>
            </ul>
          </div>
          <div class="col-md-6">
            <h6>Selected Modules:</h6>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($enabled_modules as $module): ?>
                <?php if (isset($all_modules[$module])): ?>
                  <span class="badge bg-primary">
                    <i class="bi bi-<?php echo get_module_icon($module); ?> me-1"></i>
                    <?php echo esc_view($all_modules[$module]); ?>
                  </span>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
function get_module_icon($module) {
    $icons = [
        'dashboard' => 'grid-3x3-gap-fill',
        'tasks' => 'list-check',
        'projects' => 'folder',
        'employees' => 'people',
        'attendance' => 'clock',
        'leave_requests' => 'calendar-check',
        'announcements' => 'megaphone',
        'reports' => 'graph-up',
        'timesheets' => 'clock-history',
        'payroll' => 'currency-dollar'
    ];
    return isset($icons[$module]) ? $icons[$module] : 'grid';
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        // Collect selected modules
        const checkboxes = document.querySelectorAll('input[name="success_modules[]"]:checked');
        const modules = Array.from(checkboxes).map(cb => cb.value);
        
        // Update hidden field with comma-separated values
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'settings[success_screen_modules]';
        hiddenField.value = modules.join(',');
        form.appendChild(hiddenField);
    });
});
</script>

<?php $this->load->view('partials/footer'); ?>
