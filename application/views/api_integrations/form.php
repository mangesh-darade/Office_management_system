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
  <div class="d-flex gap-2">
    <?php if ($is_edit && $integration && $integration->service_type === 'whatsapp'): ?>
      <?php echo form_open('api-integrations/test-whatsapp'); ?>
        <input type="hidden" name="id" value="<?php echo (int) $integration->id; ?>">
        <button type="submit" class="btn btn-outline-secondary">
          <i class="bi bi-plug me-1"></i>Test WhatsApp
        </button>
      <?php echo form_close(); ?>
    <?php endif; ?>
    <a href="<?php echo site_url('api-integrations'); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
  </div>
</div>

<?php
$flash_error = $this->session->flashdata('error');
$flash_success = $this->session->flashdata('success');
$flash_warning = $this->session->flashdata('warning');
?>
<?php if ($flash_error): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i><?php echo esc_view($flash_error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($flash_warning): ?>
  <div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-1"></i><?php echo esc_view($flash_warning); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($flash_success): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-1"></i><?php echo esc_view($flash_success); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="card shadow-sm oms-form-card">
  <div class="card-body">
    <?php echo form_open('api-integrations/' . ($is_edit ? 'update/' . $integration->id : 'store'), array('id' => 'apiIntegrationForm', 'data-validate' => 'true')); ?>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Service Type <span class="text-danger">*</span></label>
          <select name="service_type" id="service_type" class="form-select" required>
            <option value="">-- Select Service Type --</option>
            <option value="sendgrid" <?php echo ($integration && $integration->service_type === 'sendgrid') ? 'selected' : ''; ?>>SendGrid (Email API)</option>
            <option value="whatsapp" <?php echo ($integration && $integration->service_type === 'whatsapp') ? 'selected' : ''; ?>>WhatsApp (Meta Cloud API)</option>
            <option value="smtp" <?php echo ($integration && $integration->service_type === 'smtp') ? 'selected' : ''; ?>>SMTP (Email)</option>
            <option value="jitsi" <?php echo ($integration && $integration->service_type === 'jitsi') ? 'selected' : ''; ?>>Jitsi Meet (Video Meetings)</option>
          </select>
        </div>
        
        <div class="col-md-6 mb-3">
          <label class="form-label">Service Name <span class="text-danger">*</span></label>
          <input type="text" name="service_name" class="form-control" 
                 value="<?php echo $integration ? esc_view($integration->service_name) : ''; ?>" 
                 placeholder="e.g., Production SendGrid, Meta WhatsApp" required>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Account ID / API Key <span class="text-danger">*</span></label>
          <input type="text" name="account_id" id="account_id" class="form-control" 
                 value="<?php echo $integration ? esc_view($integration->account_id) : ''; ?>" 
                 placeholder="Account SID, API Key, etc." required>
          <small class="form-text text-muted" id="account_id_hint">For SendGrid: API Key | For WhatsApp: Phone Number ID</small>
        </div>
        
        <div class="col-md-6 mb-3">
          <label class="form-label">Auth Token / API Secret <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="password" name="auth_token" id="auth_token" class="form-control"
                   value="<?php echo $is_edit ? '' : ''; ?>"
                   placeholder="<?php echo $is_edit ? 'Leave blank to keep current token' : 'Auth Token, API Secret, Password'; ?>"
                   <?php echo $is_edit ? '' : 'required'; ?>
                   data-min-length="<?php echo $is_edit ? '0' : '1'; ?>" data-max-length="65535" autocomplete="new-password">
            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('auth_token')">
              <i class="bi bi-eye" id="auth_token_icon"></i>
            </button>
          </div>
          <small class="form-text text-muted" id="auth_token_hint">Paste one token only. On edit, leave blank to keep the current token.</small>
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
            <input type="text" name="jitsi_app_id" id="jitsi_app_id" class="form-control"
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

      <!-- WhatsApp-specific fields (Meta Cloud API) -->
      <div id="whatsapp_fields" style="display: none;">
        <div class="alert alert-info small">
          Webhook URL for Meta App Dashboard:
          <code><?php echo site_url('whatsapp/webhook'); ?></code>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">WABA ID</label>
            <input type="text" name="content_sid" class="form-control"
                   value="<?php echo $integration ? esc_view($integration->content_sid) : ''; ?>"
                   placeholder="WhatsApp Business Account ID">
            <small class="form-text text-muted">Required to list approved templates in the inbox.</small>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Display phone</label>
            <input type="text" name="from_number" class="form-control"
                   value="<?php echo $integration ? esc_view($integration->from_number) : ''; ?>"
                   placeholder="9198xxxxxxxx">
            <small class="form-text text-muted">Business number in digits (optional).</small>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Default template name</label>
            <input type="text" name="from_name_wa" class="form-control"
                   value="<?php echo ($integration && $integration->service_type === 'whatsapp') ? esc_view($integration->from_name) : ''; ?>"
                   placeholder="hello_world">
            <small class="form-text text-muted">Used for first contact when no template is selected.</small>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Webhook verify token</label>
            <input type="text" name="webhook_verify_token" class="form-control"
                   value="<?php echo ($integration && !empty($integration->webhook_verify_token)) ? esc_view($integration->webhook_verify_token) : ''; ?>"
                   placeholder="Any secret you set in Meta webhook config">
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 mb-3">
            <label class="form-label">App Secret</label>
            <input type="password" name="app_secret" class="form-control" value="" autocomplete="new-password"
                   placeholder="<?php echo ($integration && !empty($integration->app_secret)) ? 'Leave blank to keep current' : 'Meta app secret (HMAC)'; ?>">
            <small class="form-text text-muted">Required to verify inbound webhooks. Leave blank on edit to keep the current secret.</small>
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
    <?php echo form_close(); ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const isEdit = <?php echo $is_edit ? 'true' : 'false'; ?>;
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
      accountIdField.placeholder = 'Phone Number ID';
      authTokenField.required = !isEdit;
      authTokenField.placeholder = isEdit ? 'Leave blank to keep current token' : 'System user access token';
      var idHint = document.getElementById('account_id_hint');
      var tokHint = document.getElementById('auth_token_hint');
      if (idHint) idHint.textContent = 'Meta WhatsApp Phone Number ID';
      if (tokHint) tokHint.textContent = 'Paste one System User token only. Leave blank on edit to keep the current token.';
    } else if (type === 'smtp') {
      accountIdField.placeholder = 'smtp.gmail.com or server address';
      authTokenField.required = !isEdit;
    } else if (type === 'jitsi') {
      accountIdField.placeholder = 'meet.jit.si or jitsi.yourcompany.com';
      authTokenField.required = false;
      authTokenField.placeholder = 'JWT secret (optional for meet.jit.si)';
    }
  }
  
  serviceType.addEventListener('change', toggleFields);
  toggleFields();
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

