<?php $this->load->view('partials/header', ['title' => 'Email Notification Settings']); ?>

<div class="container-fluid py-3">
<div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4">
  <div>
    <h1 class="h4 mb-1 fw-bold">
      <i class="bi bi-envelope-gear text-primary me-2"></i>Email Notification Settings
    </h1>
    <p class="text-muted mb-0 small">Configure email notifications for all system modules</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?php echo site_url('email-settings/user-preferences'); ?>" class="btn btn-outline-primary">
      <i class="bi bi-person-gear me-1"></i>User Preferences
    </a>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header bg-light">
        <h5 class="mb-0">
          <i class="bi bi-gear me-2"></i>Global Email Settings
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('email-settings/update'); ?>">
          <?php foreach ($grouped_settings as $module => $settings): ?>
            <div class="module-section mb-4">
              <div class="d-flex align-items-center mb-3">
                <h6 class="mb-0 me-2"><?php echo htmlspecialchars($modules[$module]['name']); ?></h6>
                <span class="badge bg-secondary"><?php echo htmlspecialchars($modules[$module]['description']); ?></span>
              </div>
              
              <div class="table-responsive">
                <table class="table table-sm table-hover">
                  <thead>
                    <tr>
                      <th width="40%">Event</th>
                      <th width="20%">Enabled</th>
                      <th width="25%">Recipient Type</th>
                      <th width="15%">Test</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($settings as $setting): ?>
                      <tr>
                        <td>
                          <div class="fw-semibold"><?php echo htmlspecialchars($modules[$module]['events'][$setting->event_type]); ?></div>
                          <small class="text-muted"><?php echo htmlspecialchars($setting->event_type); ?></small>
                        </td>
                        <td>
                          <div class="form-check form-switch">
                            <input type="hidden" name="settings[<?php echo $setting->id; ?>][is_enabled]" value="0">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   name="settings[<?php echo $setting->id; ?>][is_enabled]" 
                                   value="1" 
                                   <?php echo $setting->is_enabled ? 'checked' : ''; ?>>
                          </div>
                        </td>
                        <td>
                          <select class="form-select form-select-sm" name="settings[<?php echo $setting->id; ?>][recipient_type]">
                            <option value="assignee" <?php echo $setting->recipient_type === 'assignee' ? 'selected' : ''; ?>>Assignee</option>
                            <option value="self" <?php echo $setting->recipient_type === 'self' ? 'selected' : ''; ?>>Self</option>
                            <option value="admin" <?php echo $setting->recipient_type === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="manager" <?php echo $setting->recipient_type === 'manager' ? 'selected' : ''; ?>>Manager</option>
                            <option value="members" <?php echo $setting->recipient_type === 'members' ? 'selected' : ''; ?>>All Members</option>
                            <option value="target_roles" <?php echo $setting->recipient_type === 'target_roles' ? 'selected' : ''; ?>>Target Roles</option>
                            <option value="custom" <?php echo $setting->recipient_type === 'custom' ? 'selected' : ''; ?>>Custom</option>
                          </select>
                        </td>
                        <td>
                          <a href="<?php echo site_url('email_settings/edit_template/'.$setting->id); ?>" 
                             class="btn btn-sm btn-outline-primary" 
                             title="Edit Template">
                            <i class="bi bi-pencil-square"></i>
                          </a>
                          <button type="button" 
                                  class="btn btn-sm btn-outline-info test-email-btn"
                                  data-module="<?php echo htmlspecialchars($module); ?>"
                                  data-event="<?php echo htmlspecialchars($setting->event_type); ?>">
                            <i class="bi bi-send"></i>
                          </button>
                        </td>
                      </tr>
                      
                      <?php if ($setting->recipient_type === 'custom'): ?>
                        <tr>
                          <td colspan="4">
                            <div class="mt-2">
                              <label class="form-label small">Custom Recipients (comma-separated emails):</label>
                              <input type="text" 
                                     class="form-control form-control-sm" 
                                     name="settings[<?php echo $setting->id; ?>][custom_recipients]" 
                                     value="<?php echo htmlspecialchars($setting->custom_recipients); ?>"
                                     placeholder="email1@example.com, email2@example.com">
                            </div>
                          </td>
                        </tr>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </tbody>
                </table>
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
              <i class="bi bi-save me-1"></i>Save Settings
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

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Send Test Email</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="testEmailForm">
          <div class="mb-3">
            <label class="form-label">Test Email Address:</label>
            <input type="email" class="form-control" name="test_email" required 
                   placeholder="test@example.com">
          </div>
          <input type="hidden" name="module" id="testModule">
          <input type="hidden" name="event_type" id="testEventType">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="sendTestEmail">Send Test Email</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle test email buttons
    document.querySelectorAll('.test-email-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const module = this.dataset.module;
            const event = this.dataset.event;
            
            document.getElementById('testModule').value = module;
            document.getElementById('testEventType').value = event;
            
            const modal = new bootstrap.Modal(document.getElementById('testEmailModal'));
            modal.show();
        });
    });
    
    // Handle recipient type change
    document.querySelectorAll('select[name*="[recipient_type]"]').forEach(select => {
        select.addEventListener('change', function() {
            const row = this.closest('tr');
            const customRow = row.nextElementSibling;
            
            if (customRow && customRow.querySelector('input[name*="[custom_recipients]"]')) {
                if (this.value === 'custom') {
                    customRow.style.display = '';
                } else {
                    customRow.style.display = 'none';
                }
            }
        });
    });
    
    // Send test email
    document.getElementById('sendTestEmail').addEventListener('click', function() {
        const form = document.getElementById('testEmailForm');
        const formData = new FormData(form);
        
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending...';
        
        fetch('<?php echo site_url('email-settings/test_email'); ?>', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Test email sent successfully!');
                bootstrap.Modal.getInstance(document.getElementById('testEmailModal')).hide();
                form.reset();
            } else {
                alert('Failed to send test email: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error sending test email: ' + error.message);
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = 'Send Test Email';
        });
    });
});
</script>
</div>

<?php $this->load->view('partials/footer'); ?>
