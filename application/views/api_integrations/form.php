<?php 
$is_edit = (isset($action) && $action === 'edit');
$integration = isset($integration) ? $integration : null;
$this->load->view('partials/header', ['title' => $is_edit ? 'Edit API Integration' : 'Add API Integration']); 
?>
<div class="oms-form-compact">

<div class="oms-form-page-head d-flex justify-content-between align-items-center mb-2">
  <h1 class="h3 mb-0">
    <i class="bi bi-<?php echo $is_edit ? 'pencil' : 'plus-circle'; ?> me-2"></i>
    <?php echo $is_edit ? 'Edit' : 'Add'; ?> API Integration
  </h1>
  <a href="<?php echo site_url('api-integrations'); ?>" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Back
  </a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>

<div class="card shadow-sm oms-form-card">
  <div class="card-body">
    <form method="post" action="<?php echo site_url('api-integrations/' . ($is_edit ? 'update/' . $integration->id : 'store')); ?>" data-validate="true" id="apiIntegrationForm">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Service Type <span class="text-danger">*</span></label>
          <select name="service_type" id="service_type" class="form-select" required>
            <option value="">-- Select Service Type --</option>
            <option value="sendgrid" <?php echo ($integration && $integration->service_type === 'sendgrid') ? 'selected' : ''; ?>>SendGrid (Email API)</option>
            <option value="whatsapp" <?php echo ($integration && $integration->service_type === 'whatsapp') ? 'selected' : ''; ?>>WhatsApp (Twilio)</option>
            <option value="smtp" <?php echo ($integration && $integration->service_type === 'smtp') ? 'selected' : ''; ?>>SMTP (Email)</option>
            <option value="jitsi" <?php echo ($integration && $integration->service_type === 'jitsi') ? 'selected' : ''; ?>>Jitsi Meet (Video Meetings)</option>
          </select>
        </div>
        
        <div class="col-md-6 mb-3">
          <label class="form-label">Service Name <span class="text-danger">*</span></label>
          <input type="text" name="service_name" class="form-control" 
                 value="<?php echo $integration ? esc_view($integration->service_name) : ''; ?>" 
                 placeholder="e.g., Production SendGrid, Twilio WhatsApp" required>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Account ID / API Key <span class="text-danger">*</span></label>
          <input type="text" name="account_id" id="account_id" class="form-control" 
                 value="<?php echo $integration ? esc_view($integration->account_id) : ''; ?>" 
                 placeholder="Account SID, API Key, etc." required>
          <small class="form-text text-muted">For SendGrid: API Key | For WhatsApp: Account SID</small>
        </div>
        
        <div class="col-md-6 mb-3">
          <label class="form-label">Auth Token / API Secret <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="password" name="auth_token" id="auth_token" class="form-control" 
                   value="<?php echo $integration ? esc_view($integration->auth_token) : ''; ?>" 
                   placeholder="Auth Token, API Secret, Password" required>
            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('auth_token')">
              <i class="bi bi-eye" id="auth_token_icon"></i>
            </button>
          </div>
          <small class="form-text text-muted">For SendGrid: Not needed | For WhatsApp: Auth Token</small>
        </div>
      </div>
      
      <!-- Email-specific fields -->
      <div id="email_fields" style="display: none;">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">From Email</label>
            <input type="email" name="from_email" class="form-control" 
                   value="<?php echo $integration ? esc_view($integration->from_email) : ''; ?>" 
                   placeholder="noreply@example.com">
            <small class="form-text text-muted">Verified sender email address</small>
          </div>
          
          <div class="col-md-6 mb-3">
            <label class="form-label">From Name</label>
            <input type="text" name="from_name" class="form-control" 
                   value="<?php echo $integration ? esc_view($integration->from_name) : ''; ?>" 
                   placeholder="Company Name">
          </div>
        </div>
      </div>
      
      <!-- Jitsi-specific fields -->
      <div id="jitsi_fields" style="display: none;">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">JWT App ID (Issuer)</label>
            <input type="text" name="from_name" id="jitsi_app_id" class="form-control"
                   value="<?php echo $integration ? esc_view($integration->from_name) : ''; ?>"
                   placeholder="my_jitsi_app">
            <small class="form-text text-muted">Required only when JWT auth is enabled on your Jitsi server</small>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Extra config (JSON)</label>
            <input type="text" name="jitsi_notes_json" id="jitsi_notes_json" class="form-control"
                   value="<?php
                     if ($integration && $integration->service_type === 'jitsi' && $integration->notes) {
                       echo esc_view($integration->notes);
                     }
                   ?>"
                   placeholder='{"app_id":"my_jitsi_app"}'>
            <small class="form-text text-muted">Optional JSON; app_id fallback if From Name is empty</small>
          </div>
        </div>
        <div class="alert alert-info small mb-3">
          <strong>Field mapping:</strong> Account ID = Jitsi Meet <em>server</em> domain only (e.g. <code>meet.jit.si</code> or <code>meet.elintpos.in</code>).
          Do <strong>not</strong> use your OMS portal URL (<code>internalportal.elintpos.in</code>).
          Auth Token = JWT secret (leave blank for public <code>meet.jit.si</code>).
        </div>
      </div>

      <!-- WhatsApp-specific fields -->
      <div id="whatsapp_fields" style="display: none;">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">From Number</label>
            <input type="text" name="from_number" class="form-control" 
                   value="<?php echo $integration ? esc_view($integration->from_number) : ''; ?>" 
                   placeholder="whatsapp:+14155238886">
            <small class="form-text text-muted">Twilio WhatsApp number (e.g., whatsapp:+14155238886)</small>
          </div>
          
          <div class="col-md-6 mb-3">
            <label class="form-label">Content SID (Template)</label>
            <input type="text" name="content_sid" class="form-control" 
                   value="<?php echo $integration ? esc_view($integration->content_sid) : ''; ?>" 
                   placeholder="HXb5b62575e6e4ff6129ad7c8efe1f983e">
            <small class="form-text text-muted">Optional: Twilio Content Template SID</small>
          </div>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-12 mb-3">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="3" 
                    placeholder="Additional notes or description"><?php echo $integration ? esc_view($integration->notes) : ''; ?></textarea>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                   <?php echo (!$integration || $integration->is_active) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_active">Active</label>
          </div>
        </div>
        
        <div class="col-md-6 mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_default" id="is_default" 
                   <?php echo ($integration && $integration->is_default) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_default">Set as Default</label>
            <small class="form-text text-muted d-block">Only one default per service type</small>
          </div>
        </div>
      </div>
      
      <div class="d-flex justify-content-end gap-2">
        <a href="<?php echo site_url('api-integrations'); ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-circle me-1"></i><?php echo $is_edit ? 'Update' : 'Create'; ?>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const serviceType = document.getElementById('service_type');
  const emailFields = document.getElementById('email_fields');
  const whatsappFields = document.getElementById('whatsapp_fields');
  const jitsiFields = document.getElementById('jitsi_fields');
  const authTokenField = document.getElementById('auth_token');
  const accountIdField = document.getElementById('account_id');
  
  function toggleFields() {
    const type = serviceType.value;
    emailFields.style.display = (type === 'sendgrid' || type === 'smtp') ? 'block' : 'none';
    whatsappFields.style.display = (type === 'whatsapp') ? 'block' : 'none';
    if (jitsiFields) {
      jitsiFields.style.display = (type === 'jitsi') ? 'block' : 'none';
    }
    
    if (type === 'sendgrid') {
      accountIdField.placeholder = 'SG.xxxxxxxxxxxxxxxxxxxxx';
      authTokenField.required = false;
    } else if (type === 'whatsapp') {
      accountIdField.placeholder = 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
      authTokenField.required = true;
    } else if (type === 'smtp') {
      accountIdField.placeholder = 'smtp.gmail.com or server address';
      authTokenField.required = true;
    } else if (type === 'jitsi') {
      accountIdField.placeholder = 'meet.jit.si or jitsi.yourcompany.com';
      authTokenField.required = false;
      authTokenField.placeholder = 'JWT secret (optional for meet.jit.si)';
    }
  }
  
  serviceType.addEventListener('change', toggleFields);
  toggleFields();

  var apiForm = document.getElementById('apiIntegrationForm');
  if (apiForm) {
    apiForm.addEventListener('submit', function(){
      [emailFields, whatsappFields, jitsiFields].forEach(function(wrapper){
        if (!wrapper) return;
        var hidden = wrapper.style.display === 'none';
        wrapper.querySelectorAll('input, select, textarea').forEach(function(el){
          if (hidden) {
            el.disabled = true;
          }
        });
      });
    });
  }
});

function togglePassword(fieldId) {
  const field = document.getElementById(fieldId);
  const icon = document.getElementById(fieldId + '_icon');
  if (field.type === 'password') {
    field.type = 'text';
    icon.classList.remove('bi-eye');
    icon.classList.add('bi-eye-slash');
  } else {
    field.type = 'password';
    icon.classList.remove('bi-eye-slash');
    icon.classList.add('bi-eye');
  }
}
</script>

</div>
<?php $this->load->view('partials/footer'); ?>

