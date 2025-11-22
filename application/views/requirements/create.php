<?php $this->load->view('partials/header', ['title' => 'New Requirement']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">New Requirement</h1>
  <a class="btn btn-light btn-sm" href="<?php echo site_url('requirements'); ?>">Back</a>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="" class="vstack gap-3" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Client <span class="text-danger">*</span></label>
          <select name="client_id" class="form-select" required>
            <option value="">-- Select Client --</option>
            <?php if (isset($clients) && is_array($clients)) foreach ($clients as $c): ?>
              <option value="<?php echo (int)$c->id; ?>"><?php echo htmlspecialchars($c->company_name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Project</label>
          <div class="d-flex align-items-center gap-2">
            <select name="project_id" class="form-select">
              <option value="">-- None --</option>
              <?php if (isset($projects) && is_array($projects)) foreach ($projects as $p): ?>
                <option value="<?php echo (int)$p->id; ?>"><?php echo htmlspecialchars($p->name); ?></option>
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
          <select name="requirement_type" class="form-select">
            <?php $types = array('new_feature','enhancement','bug_fix','maintenance','consultation','other'); foreach ($types as $t): ?>
              <option value="<?php echo htmlspecialchars($t); ?>"><?php echo ucfirst(str_replace('_',' ',$t)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label">Description</label>
          <textarea name="description" id="description" rows="6" class="form-control"></textarea>
        </div>
        <div class="col-md-3">
          <label class="form-label">Priority</label>
          <select name="priority" class="form-select">
            <?php $priorities = array('low','medium','high','critical'); foreach ($priorities as $pr): ?>
              <option value="<?php echo htmlspecialchars($pr); ?>"><?php echo ucfirst($pr); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <?php $statuses = array('received','under_review','approved','in_progress','completed','on_hold','rejected','cancelled'); ?>
          <select name="status" class="form-select">
            <?php foreach ($statuses as $st): ?>
              <option value="<?php echo htmlspecialchars($st); ?>"><?php echo ucfirst(str_replace('_',' ',$st)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Expected Delivery</label>
          <input type="date" name="expected_delivery_date" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Received Date</label>
          <input type="date" name="received_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Budget (<?php echo 'INR'; ?>)</label>
          <input type="number" step="0.01" name="budget_estimate" class="form-control">
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
              <option value="<?php echo (int)$m->id; ?>"><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Attachments</label>
          <input type="file" name="attachments[]" class="form-control" multiple>
        </div>
      </div>
      <div>
        <button class="btn btn-primary">Create</button>
        <a class="btn btn-light" href="<?php echo site_url('requirements'); ?>">Cancel</a>
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

<?php $this->load->view('partials/footer'); ?>
<script src="https://cdn.ckeditor.com/4.21.0/standard-all/ckeditor.js"></script>
<script>
  if (window.CKEDITOR){
    CKEDITOR.replace('description', {
      extraPlugins: 'table,autogrow',
      autoGrow_minHeight: 200,
      removePlugins: 'elementspath',
      resize_enabled: true
    });
  }

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
</script>
