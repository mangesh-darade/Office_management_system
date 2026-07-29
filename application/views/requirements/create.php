<?php $this->load->view('partials/header', ['title' => 'New Requirement']); ?>
<?php
$redirect_path = isset($redirect_path) ? trim((string) $redirect_path) : '';
if ($redirect_path === '' || strpos($redirect_path, '://') !== false) {
    $redirect_path = '';
}
$this->load->helper('my_works');
$back_url = $redirect_path !== '' ? my_works_safe_redirect($redirect_path, 'my-works') : site_url('requirements');
$back_title = $redirect_path !== '' ? 'Back to Second Brain' : 'Back to Requirements';
?>
<div class="oms-form-compact">
<div class="oms-form-page-head mw-page-head-with-back d-flex align-items-center gap-2 mb-2">
  <?php $this->load->view('my_works/_back_btn', array(
    'back_url' => $back_url,
    'back_title' => $back_title,
  )); ?>
  <h1 class="h4 mb-0">New Requirement</h1>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<div class="card shadow-soft oms-form-card">
  <div class="card-body">
    <form method="post" action="" class="vstack gap-3" enctype="multipart/form-data" data-validate="true">
      <?php if ($redirect_path !== ''): ?>
      <input type="hidden" name="redirect" value="<?php echo esc_view($redirect_path, ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>
      <div class="row g-2 oms-form-grid">
        <div class="col-md-6">
          <label class="form-label">Client <span class="text-danger">*</span></label>
          <select name="client_id" class="form-select" required>
            <option value="">-- Select Client --</option>
            <?php if (isset($clients) && is_array($clients)) foreach ($clients as $c): ?>
              <option value="<?php echo (int)$c->id; ?>"><?php echo esc_view($c->company_name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Project</label>
          <div class="d-flex align-items-center gap-2">
            <select name="project_id" class="form-select">
              <option value="">-- None --</option>
              <?php if (isset($projects) && is_array($projects)) foreach ($projects as $p): ?>
                <option value="<?php echo (int)$p->id; ?>"><?php echo esc_view($p->name); ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddProject" title="Add Project">
              <i class="bi bi-plus-lg"></i>
            </button>
          </div>
        </div>
        <div class="col-md-8">
          <label class="form-label">Title <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Type</label>
          <?php $this->load->view('partials/module_type_select', array(
            'field_name' => 'requirement_type',
            'options' => isset($requirement_types) ? $requirement_types : array(),
            'current' => 'new_feature',
            'required' => true,
            'placeholder' => '— Select type —',
          )); ?>
        </div>
        <div class="col-md-12">
          <label class="form-label">Description</label>
          <textarea name="description" id="description" rows="6" class="form-control"></textarea>
        </div>
        <div class="col-md-12">
          <label class="form-label"><i class="bi bi-link-45deg me-1"></i>URL / Link</label>
          <input type="url" name="reference_url" class="form-control" placeholder="https://example.com/requirement-spec">
          <div class="form-text">Optional. Paste any web link related to this requirement.</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Priority</label>
          <select name="priority" class="form-select">
            <?php $priorities = array('low','medium','high','critical'); foreach ($priorities as $pr): ?>
              <option value="<?php echo esc_view($pr); ?>"><?php echo ucfirst($pr); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <?php 
            if (isset($statuses) && is_array($statuses) && !empty($statuses)): 
              foreach ($statuses as $st): 
                $selected = ($st->code === 'received') ? 'selected' : '';
            ?>
              <option value="<?php echo esc_view($st->code); ?>" <?php echo $selected; ?>><?php echo esc_view($st->name); ?></option>
            <?php 
              endforeach; 
            else: 
              // Fallback to hardcoded statuses if database is not available
              $fallback_statuses = ['received','under_review','approved','in_progress','completed','on_hold','rejected','cancelled'];
              foreach ($fallback_statuses as $st):
            ?>
              <option value="<?php echo esc_view($st); ?>"><?php echo ucfirst(str_replace('_',' ',$st)); ?></option>
            <?php 
              endforeach; 
            endif; 
            ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Received Date</label>
          <input type="date" name="received_date" id="received_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Expected Delivery</label>
          <input type="date" name="expected_delivery_date" id="expected_delivery_date" class="form-control">
          <div class="form-text text-danger" id="date-error" style="display: none;">
            <small>Expected delivery date must be on or after received date</small>
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Owner <span class="text-danger">*</span></label>
          <select name="owner_id" class="form-select" required>
            <option value="">-- Select Owner --</option>
            <?php if (isset($members) && is_array($members)) foreach ($members as $m): ?>
              <?php $label = '';
                if (isset($m->full_label) && $m->full_label!=='') { $label = $m->full_label; }
                else if (isset($m->full_name) && $m->full_name!=='') { $label = $m->full_name; }
                else if (isset($m->name) && $m->name!=='') { $label = $m->name; }
                else if (isset($m->email)) { $label = $m->email; }
              ?>
              <option value="<?php echo (int)$m->id; ?>"><?php echo esc_view($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Assigned To</label>
          <select name="assigned_to[]" id="req-assigned-to-select" class="form-select oms-select2-multi" multiple style="width: 100%;">
            <?php if (isset($members) && is_array($members)) foreach ($members as $m): ?>
              <?php $label = '';
                if (isset($m->full_label) && $m->full_label!=='') { $label = $m->full_label; }
                else if (isset($m->full_name) && $m->full_name!=='') { $label = $m->full_name; }
                else if (isset($m->name) && $m->name!=='') { $label = $m->name; }
                else if (isset($m->email)) { $label = $m->email; }
              ?>
              <option value="<?php echo (int)$m->id; ?>"><?php echo esc_view($label); ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Type to search. Click × on a tag to remove that user. First selected is primary.</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Attachments</label>
          <input type="file" name="attachments[]" class="form-control" multiple>
        </div>
      </div>
      <div>
        <button class="btn btn-primary">Create</button>
        <a class="btn btn-light" href="<?php echo esc_view($back_url, ENT_QUOTES, 'UTF-8'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Project</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <iframe id="projectModalFrame" src="" style="border:0;width:100%;height:500px;"></iframe>
      </div>
    </div>
  </div>
</div>

</div>
<?php $this->load->view('partials/footer'); ?>
<?php
  $this->load->view('partials/oms_select2_multi', array(
    'oms_select2_selectors' => array('#req-assigned-to-select'),
    'oms_select2_placeholder' => 'Select assignee(s)…',
  ));
?>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  // Initialize TinyMCE for description field
  tinymce.init({
    selector: '#description',
    menubar: 'edit view insert format tools',
    statusbar: true,
    plugins: [
      'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
      'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
      'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount',
      'textcolor', 'colorpicker', 'fontselect', 'fontsizeselect'
    ],
    toolbar: 'undo redo | formatselect | ' +
      'bold italic underline strikethrough | forecolor backcolor | ' +
      'alignleft aligncenter alignright alignjustify | ' +
      'bullist numlist outdent indent | ' +
      'removeformat | link image | code | fullscreen | help',
    branding: false,
    height: 400,
    width: '100%',
    convert_urls: false,
    default_link_target: '_blank',
    font_formats: 'Arial=arial,helvetica,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Tahoma=tahoma,arial,helvetica,sans-serif; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva',
    fontsize_formats: '8pt 10pt 12pt 14pt 16pt 18pt 24pt 36pt 48pt',
    formats: {
      bold: { inline: 'strong', classes: 'fw-bold' },
      italic: { inline: 'em', classes: 'fst-italic' },
      underline: { inline: 'u', classes: 'text-decoration-underline' },
      strikethrough: { inline: 'del' }
    },
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
    setup: function(editor) {
      editor.on('init', function() {
        editor.execCommand('FontName', false, 'Arial');
        editor.execCommand('FontSize', false, '14pt');
      });
    }
  });
  
  // Ensure content sync on submit
  document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form');
    if (form) {
      form.addEventListener('submit', function() {
        if (tinymce.get('description')) {
          tinymce.get('description').save();
        }
      });
    }
  });

  function closeProjectModal(){
    var frame = document.getElementById('projectModalFrame');
    if (frame){ frame.src = 'about:blank'; }
    var modalEl = document.getElementById('projectModal');
    if (modalEl){
      if (window.bootstrap && window.bootstrap.Modal){
        var m = window.bootstrap.Modal.getOrCreateInstance(modalEl);
        m.hide();
      } else {
        modalEl.style.display = 'none';
      }
    }
  }
  window.closeProjectModal = closeProjectModal;

  // Called from embedded project create popup when a project is created
  window.onProjectCreated = function(id, name){
    var select = document.querySelector('select[name="project_id"]');
    if (!select) return;
    var val = String(id || '');
    if (!val) return;
    var opt = null;
    for (var i = 0; i < select.options.length; i++){
      if (select.options[i].value === val){ opt = select.options[i]; break; }
    }
    if (!opt){
      opt = document.createElement('option');
      opt.value = val;
      opt.textContent = name || ('Project #' + val);
      select.appendChild(opt);
    }
    select.value = val;

    closeProjectModal();
  };

  (function(){
    var btn = document.getElementById('btnAddProject');
    if (!btn) return;
    btn.addEventListener('click', function(ev){
      ev.preventDefault();
      var frame = document.getElementById('projectModalFrame');
      if (frame) {
        frame.src = '<?php echo site_url('projects/create'); ?>?embed=1';
      }
      var modalEl = document.getElementById('projectModal');
      if (modalEl) {
        if (window.bootstrap && window.bootstrap.Modal) {
          var m = window.bootstrap.Modal.getOrCreateInstance(modalEl);
          m.show();
        } else {
          modalEl.style.display = 'block';
        }
      } else {
        window.open('<?php echo site_url('projects/create'); ?>','_blank','width=900,height=600');
      }
    });
  })();

  // Date validation: Expected delivery date must be >= Received date
  document.addEventListener('DOMContentLoaded', function() {
    var receivedDateInput = document.getElementById('received_date');
    var expectedDateInput = document.getElementById('expected_delivery_date');
    var dateError = document.getElementById('date-error');
    var form = document.querySelector('form');
    
    function validateDates() {
      if (receivedDateInput && receivedDateInput.value && expectedDateInput && expectedDateInput.value) {
        var receivedDate = new Date(receivedDateInput.value);
        var expectedDate = new Date(expectedDateInput.value);
        
        if (expectedDate < receivedDate) {
          if (dateError) dateError.style.display = 'block';
          if (expectedDateInput) expectedDateInput.classList.add('is-invalid');
          return false;
        } else {
          if (dateError) dateError.style.display = 'none';
          if (expectedDateInput) expectedDateInput.classList.remove('is-invalid');
          return true;
        }
      }
      if (dateError) dateError.style.display = 'none';
      if (expectedDateInput) expectedDateInput.classList.remove('is-invalid');
      return true;
    }
    
    if (receivedDateInput) receivedDateInput.addEventListener('change', validateDates);
    if (expectedDateInput) expectedDateInput.addEventListener('change', validateDates);
    
    if (form) {
      form.addEventListener('submit', function(e) {
        if (!validateDates()) {
          e.preventDefault();
          return false;
        }
      });
    }
  });
</script>
