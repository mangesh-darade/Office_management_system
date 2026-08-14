<?php
$templates = isset($templates) ? $templates : array();
$creds = isset($creds) ? $creds : array();
$last_synced = isset($last_synced) ? $last_synced : null;
$config_configured = !empty($config_configured);
$can_sync = $config_configured && !empty($creds['access_token']);
$this->load->view('partials/header', array('title' => 'WhatsApp Templates', 'active' => 'whatsapp-templates'));
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h1 class="h3 mb-0"><i class="bi bi-file-earmark-text me-2"></i>WhatsApp Templates</h1>
    <?php if ($last_synced): ?>
      <div class="small text-muted">Last synced <?php echo esc_view(date('d M Y H:i', strtotime($last_synced))); ?></div>
    <?php endif; ?>
  </div>
  <div class="d-flex gap-2">
    <?php echo form_open('whatsapp/test-connection'); ?>
      <input type="hidden" name="back" value="templates">
      <button type="submit" class="btn btn-outline-secondary btn-sm" <?php echo $can_sync ? '' : 'disabled'; ?> title="Check token, WABA, and Phone Number ID">
        <i class="bi bi-plug me-1"></i>Test connection
      </button>
    <?php echo form_close(); ?>
    <?php echo form_open('whatsapp/sync-templates'); ?>
      <button type="submit" class="btn btn-primary btn-sm" <?php echo $can_sync ? '' : 'disabled'; ?> title="Pull templates from WhatsApp Manager">
        <i class="bi bi-arrow-repeat me-1"></i>Sync from Meta
      </button>
    <?php echo form_close(); ?>
    <a href="<?php echo site_url('whatsapp'); ?>" class="btn btn-outline-success btn-sm">
      <i class="bi bi-whatsapp me-1"></i>Inbox
    </a>
  </div>
</div>

<?php
$flash_success = $this->session->flashdata('success');
$flash_error = $this->session->flashdata('error');
$flash_warning = $this->session->flashdata('warning');
?>
<?php if ($flash_success): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-1"></i><?php echo esc_view($flash_success); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($flash_warning): ?>
  <div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-1"></i><?php echo esc_view($flash_warning); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($flash_error): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i><?php echo esc_view($flash_error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if (!$config_configured || empty($creds['access_token'])): ?>
  <div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-1"></i>
    Add Phone Number ID and access token in
    <a href="<?php echo site_url('api-integrations'); ?>">API Integrations</a>
    to sync Meta templates. WABA ID is filled automatically when the token can access the WhatsApp account.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<div id="waTplPageAlert" class="mb-2" aria-live="polite"></div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <h2 class="h6 mb-3">Add template name</h2>
    <p class="small text-muted">If Sync cannot list Meta templates, add the <strong>exact</strong> name from WhatsApp Manager (e.g. <code>hello_world</code>). Send uses this name on Graph.</p>
    <?php echo form_open('whatsapp/add-template', array('class' => 'row g-2 align-items-end')); ?>
      <div class="col-md-4">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" placeholder="hello_world" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Language</label>
        <input type="text" name="language" class="form-control" value="en_US" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Category</label>
        <input type="text" name="category" class="form-control" placeholder="UTILITY">
      </div>
      <div class="col-md-3">
        <label class="form-label">Preview (optional)</label>
        <input type="text" name="body" class="form-control" placeholder="Body text">
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn btn-outline-primary w-100" title="Save"><i class="bi bi-plus-lg"></i></button>
      </div>
    <?php echo form_close(); ?>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <?php if (empty($templates)): ?>
      <p class="text-muted mb-0">No templates cached. Create them in WhatsApp Manager, then click <strong>Sync from Meta</strong>.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Name</th>
              <th>Language</th>
              <th>Category</th>
              <th>Status</th>
              <th>Preview</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($templates as $tpl): ?>
              <?php
                $st = strtoupper($tpl['status']);
                $badge = 'secondary';
                if ($st === 'APPROVED') {
                    $badge = 'success';
                } elseif ($st === 'PENDING' || $st === 'PENDING_REVIEW') {
                    $badge = 'warning';
                } elseif ($st === 'REJECTED' || $st === 'DISABLED' || $st === 'PAUSED') {
                    $badge = 'danger';
                }
              ?>
              <tr>
                <td><code><?php echo esc_view($tpl['name']); ?></code></td>
                <td><?php echo esc_view($tpl['language']); ?></td>
                <td><?php echo esc_view($tpl['category']); ?></td>
                <td><span class="badge bg-<?php echo $badge; ?>"><?php echo esc_view($st); ?></span></td>
                <td class="small text-muted" style="max-width:280px;"><?php echo esc_view(mb_substr($tpl['body'], 0, 120)); ?></td>
                <td class="text-end text-nowrap">
                  <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                    <?php if ($st === 'APPROVED'): ?>
                      <button type="button" class="btn btn-outline-success wa-send-tpl"
                              title="Send"
                              data-bs-toggle="modal" data-bs-target="#waSendTplModal"
                              data-name="<?php echo esc_view($tpl['name']); ?>"
                              data-lang="<?php echo esc_view($tpl['language']); ?>"
                              data-vars="<?php echo (int) whatsapp_template_placeholder_count(isset($tpl['body']) ? $tpl['body'] : ''); ?>"
                              data-body="<?php echo esc_view(isset($tpl['body']) ? $tpl['body'] : ''); ?>">
                        <i class="bi bi-send"></i>
                      </button>
                    <?php endif; ?>
                    <a href="<?php echo site_url('whatsapp'); ?>" class="btn btn-outline-secondary" title="Open inbox">
                      <i class="bi bi-inbox"></i>
                    </a>
                    <?php echo form_open('whatsapp/delete-template', array('class' => 'd-inline')); ?>
                      <input type="hidden" name="id" value="<?php echo (int) $tpl['id']; ?>">
                      <button type="submit" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Remove this template from the local cache?');">
                        <i class="bi bi-trash"></i>
                      </button>
                    <?php echo form_close(); ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="modal fade" id="waSendTplModal" tabindex="-1" aria-labelledby="waSendTplLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <?php echo form_open('whatsapp/send-template', array('id' => 'waSendTplForm')); ?>
        <div class="modal-header">
          <h5 class="modal-title" id="waSendTplLabel">Send template</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="waSendTplAlert" class="mb-2"></div>
          <input type="hidden" name="template_name" id="waTplName">
          <input type="hidden" name="language" id="waTplLang">
          <p class="small text-muted">Template: <strong id="waTplNameLabel"></strong></p>
          <label class="form-label">Phone</label>
          <input type="text" name="phone" id="waTplPhone" class="form-control mb-3" placeholder="9198xxxxxxxx" required>
          <div id="waTplVarsWrap" class="d-none">
            <label class="form-label">Template variables</label>
            <div id="waTplVars"></div>
            <div class="form-text">Fill values for {{1}}, {{2}}, … in the Meta template body.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Send</button>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>

<script>
function waFillTplVars(container, count) {
  if (!container) { return; }
  container.innerHTML = '';
  count = parseInt(count, 10) || 0;
  var wrap = document.getElementById('waTplVarsWrap');
  if (count < 1) {
    if (wrap) { wrap.classList.add('d-none'); }
    return;
  }
  if (wrap) { wrap.classList.remove('d-none'); }
  for (var i = 1; i <= count; i++) {
    var input = document.createElement('input');
    input.type = 'text';
    input.name = 'template_var[]';
    input.className = 'form-control form-control-sm mb-1';
    input.placeholder = '{{' + i + '}}';
    input.required = true;
    container.appendChild(input);
  }
}
function waPageAlert(type, message, targetId) {
  var box = document.getElementById(targetId || 'waTplPageAlert');
  if (!box) { window.alert(message); return; }
  var cls = type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger');
  var icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
  box.innerHTML = '<div class="alert ' + cls + ' alert-dismissible fade show" role="alert">'
    + '<i class="bi ' + icon + ' me-1"></i><span></span>'
    + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
  box.querySelector('span').textContent = message || '';
}
document.querySelectorAll('.wa-send-tpl').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.getElementById('waTplName').value = btn.getAttribute('data-name') || '';
    document.getElementById('waTplLang').value = btn.getAttribute('data-lang') || 'en_US';
    document.getElementById('waTplNameLabel').textContent = btn.getAttribute('data-name') || '';
    waFillTplVars(document.getElementById('waTplVars'), btn.getAttribute('data-vars') || 0);
    var alertBox = document.getElementById('waSendTplAlert');
    if (alertBox) { alertBox.innerHTML = ''; }
  });
});
var sendForm = document.getElementById('waSendTplForm');
if (sendForm) {
  sendForm.addEventListener('submit', function(e) {
    var phone = document.getElementById('waTplPhone');
    var name = document.getElementById('waTplName');
    if (!phone || !(phone.value || '').trim()) {
      e.preventDefault();
      waPageAlert('warning', 'Enter a phone number with country code.', 'waSendTplAlert');
      return false;
    }
    if (!name || !(name.value || '').trim()) {
      e.preventDefault();
      waPageAlert('warning', 'Template name is missing.', 'waSendTplAlert');
      return false;
    }
    var vars = sendForm.querySelectorAll('#waTplVars input[name="template_var[]"]');
    for (var i = 0; i < vars.length; i++) {
      if (!(vars[i].value || '').trim()) {
        e.preventDefault();
        waPageAlert('warning', 'Fill all template variables ({{' + (i + 1) + '}}).', 'waSendTplAlert');
        vars[i].focus();
        return false;
      }
    }
    return true;
  });
}
var addForm = document.querySelector('form[action*="add-template"]');
if (addForm) {
  addForm.addEventListener('submit', function(e) {
    var nameInput = addForm.querySelector('input[name="name"]');
    var name = nameInput ? (nameInput.value || '').trim().toLowerCase() : '';
    if (!/^[a-z0-9_]+$/.test(name)) {
      e.preventDefault();
      waPageAlert('warning', 'Template name must be lowercase letters, numbers, and underscores only (e.g. hello_world).');
      if (nameInput) { nameInput.focus(); }
      return false;
    }
    return true;
  });
}
</script>

<?php $this->load->view('partials/footer'); ?>
