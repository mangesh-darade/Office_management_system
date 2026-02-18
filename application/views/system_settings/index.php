<?php $this->load->view('partials/header', ['title' => 'System Settings']); ?>

<div class="container-fluid py-3">
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4">
  <div>
    <h1 class="h4 mb-1 fw-bold">
      <i class="bi bi-gear-fill text-primary me-2"></i>System Settings
    </h1>
    <p class="text-muted mb-0 small">Configure system-wide settings and preferences</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?php echo site_url('system-settings/permissions'); ?>" class="btn btn-outline-primary">
      <i class="bi bi-shield-lock me-1"></i>Permissions
    </a>
    <a href="<?php echo site_url('system-settings/user-access'); ?>" class="btn btn-outline-info">
      <i class="bi bi-people me-1"></i>User Access
    </a>
    <a href="<?php echo site_url('system-settings/success-screen'); ?>" class="btn btn-outline-success">
      <i class="bi bi-check-circle me-1"></i>Success Screen
    </a>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <form method="post" action="<?php echo site_url('system-settings/update_settings'); ?>">
      <?php foreach ($settings_by_category as $category => $settings): ?>
        <div class="card mb-4">
          <div class="card-header bg-light">
            <h5 class="mb-0">
              <i class="bi bi-<?php echo get_category_icon($category); ?> me-2"></i>
              <?php echo ucfirst(str_replace('_', ' ', $category)); ?> Settings
            </h5>
          </div>
          <div class="card-body">
            <div class="row">
              <?php foreach ($settings as $setting): ?>
                <div class="col-md-6 mb-3">
                  <label class="form-label">
                    <?php echo htmlspecialchars($setting->description); ?>
                    <?php if (!$setting->is_public): ?>
                      <span class="badge bg-warning ms-1">Admin Only</span>
                    <?php endif; ?>
                  </label>
                  
                  <?php if ($setting->setting_type === 'boolean'): ?>
                    <div class="form-check form-switch">
                      <input type="hidden" name="settings[<?php echo $setting->setting_key; ?>]" value="0">
                      <input type="checkbox" 
                             class="form-check-input" 
                             name="settings[<?php echo $setting->setting_key; ?>]" 
                             value="1" 
                             <?php echo $setting->setting_value == '1' ? 'checked' : ''; ?>>
                      <label class="form-check-label">
                        <?php echo $setting->setting_value == '1' ? 'Enabled' : 'Disabled'; ?>
                      </label>
                    </div>
                    
                  <?php elseif ($setting->setting_type === 'select'): ?>
                    <select class="form-select" name="settings[<?php echo $setting->setting_key; ?>]">
                      <?php if ($setting->setting_key === 'default_timezone'): ?>
                        <?php $timezones = ['Asia/Kolkata', 'America/New_York', 'Europe/London', 'Australia/Sydney']; ?>
                        <?php foreach ($timezones as $tz): ?>
                          <option value="<?php echo $tz; ?>" <?php echo $setting->setting_value === $tz ? 'selected' : ''; ?>>
                            <?php echo $tz; ?>
                          </option>
                        <?php endforeach; ?>
                      <?php elseif ($setting->setting_key === 'date_format'): ?>
                        <option value="Y-m-d" <?php echo $setting->setting_value === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                        <option value="d/m/Y" <?php echo $setting->setting_value === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                        <option value="m/d/Y" <?php echo $setting->setting_value === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                      <?php elseif ($setting->setting_key === 'time_format'): ?>
                        <option value="24h" <?php echo $setting->setting_value === '24h' ? 'selected' : ''; ?>>24 Hour</option>
                        <option value="12h" <?php echo $setting->setting_value === '12h' ? 'selected' : ''; ?>>12 Hour</option>
                      <?php endif; ?>
                    </select>
                    
                  <?php elseif ($setting->setting_type === 'number'): ?>
                    <input type="number" 
                           class="form-control" 
                           name="settings[<?php echo $setting->setting_key; ?>]" 
                           value="<?php echo htmlspecialchars($setting->setting_value); ?>"
                           min="0">
                    
                  <?php elseif ($setting->setting_type === 'file'): ?>
                    <input type="file" 
                           class="form-control" 
                           name="settings[<?php echo $setting->setting_key; ?>]"
                           accept="image/*">
                    <?php if ($setting->setting_value): ?>
                      <div class="mt-2">
                        <small class="text-muted">Current: <?php echo htmlspecialchars($setting->setting_value); ?></small>
                        <br>
                        <img src="<?php echo base_url($setting->setting_value); ?>" alt="Logo" style="max-height: 50px;">
                      </div>
                    <?php endif; ?>
                    
                  <?php else: ?>
                    <input type="text" 
                           class="form-control" 
                           name="settings[<?php echo $setting->setting_key; ?>]" 
                           value="<?php echo htmlspecialchars($setting->setting_value); ?>"
                           placeholder="<?php echo htmlspecialchars($setting->description); ?>">
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      
      <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save me-1"></i>Save Settings
        </button>
        <a href="<?php echo site_url('dashboard'); ?>" class="btn btn-outline-secondary">
          <i class="bi bi-x-lg me-1"></i>Cancel
        </a>
      </div>
    </form>
  </div>
</div>

<?php
function get_category_icon($category) {
    $icons = [
        'general' => 'gear',
        'ui' => 'palette',
        'modules' => 'grid-3x3-gap',
        'security' => 'shield-lock',
        'email' => 'envelope',
        'notifications' => 'bell'
    ];
    return isset($icons[$category]) ? $icons[$category] : 'gear';
}
?>
</div>

<?php $this->load->view('partials/footer'); ?>
