<?php
$is_edit = ($action === 'edit');
$page_title = $is_edit ? 'Edit Defect' : 'Log Defect';
$clients = isset($clients) ? $clients : array();
$projects = isset($projects) ? $projects : array();
$releases = isset($releases) ? $releases : array();
$tasks = isset($tasks) ? $tasks : array();
$members = isset($members) ? $members : array();
$selected_client_id = isset($selected_client_id) ? (int) $selected_client_id : 0;
$preselected_project_id = isset($preselected_project_id) ? (int) $preselected_project_id : 0;
if ($item && !empty($item->project_id) && $preselected_project_id < 1) {
    $preselected_project_id = (int) $item->project_id;
}
$back_url = $item ? site_url('defects/view/' . (int) $item->id) : site_url('defects');
$redirect_path = isset($redirect_path) ? trim((string) $redirect_path) : '';
if ($redirect_path !== '') {
    $back_url = site_url($redirect_path);
}
$this->load->view('partials/header', array('title' => $page_title, 'extra_css' => array('assets/css/defects-form.css')));
?>
<div class="oms-form-compact defect-form-page">
<div class="container-fluid py-2 py-md-3 px-2 px-md-3">

  <nav aria-label="breadcrumb" class="defect-form-crumb small mb-2 d-none d-md-block">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo $redirect_path !== '' ? site_url($redirect_path) : site_url('defects'); ?>"><?php echo $redirect_path !== '' ? 'Second Brain' : 'Defects'; ?></a></li>
      <li class="breadcrumb-item active" aria-current="page"><?php echo $is_edit ? 'Edit' : 'Log'; ?></li>
    </ol>
  </nav>

  <div class="defect-form-hero oms-form-page-head d-flex align-items-center gap-2 gap-md-3 mb-3">
    <a href="<?php echo esc_view($back_url); ?>" class="btn btn-light border defect-form-back oms-form-back" title="Back">
      <i class="bi bi-arrow-left"></i><span class="d-none d-sm-inline ms-1">Back</span>
    </a>
    <div class="defect-form-hero-icon d-none d-sm-flex" aria-hidden="true">
      <i class="bi bi-bug-fill"></i>
    </div>
    <div class="oms-form-page-titles min-w-0 flex-grow-1">
      <h1 class="defect-form-title mb-0"><?php echo esc_view($page_title); ?></h1>
      <p class="defect-form-sub text-muted mb-0 d-none d-md-block">Pick client &amp; project on the left, describe the bug on the right.</p>
    </div>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger border-0 shadow-sm"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" id="defectForm" class="defect-form" novalidate>
    <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
    <?php if ($redirect_path !== ''): ?>
      <input type="hidden" name="redirect" value="<?php echo esc_view($redirect_path); ?>">
    <?php endif; ?>

    <div class="row g-3 defect-form-split">
      <!-- Left: metadata (md-4) -->
      <div class="col-12 col-md-4 defect-form-meta">
        <aside class="defect-form-panel defect-form-panel--meta h-100">
          <header class="defect-form-panel-head">
            <span class="defect-form-panel-icon"><i class="bi bi-sliders"></i></span>
            <div>
              <h2 class="defect-form-panel-title">Details</h2>
              <p class="defect-form-panel-hint mb-0">Context, severity &amp; assignment</p>
            </div>
          </header>

          <div class="defect-form-group">
            <div class="defect-form-group-label">Context</div>
            <div class="vstack gap-2">
              <div class="defect-field">
                <label class="form-label" for="defectClientId">Client</label>
                <select name="client_id" id="defectClientId" class="form-select" <?php echo empty($clients) ? 'disabled' : ''; ?>>
                  <option value="0">— All clients —</option>
                  <?php foreach ($clients as $c): ?>
                    <option value="<?php echo (int) $c->id; ?>" <?php echo $selected_client_id === (int) $c->id ? 'selected' : ''; ?>>
                      <?php echo esc_view($c->company_name); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="defect-field">
                <label class="form-label" for="defectProjectId">Project</label>
                <select name="project_id" id="defectProjectId" class="form-select">
                  <option value="">— Select project —</option>
                  <?php foreach ($projects as $p): ?>
                    <?php
                      $pid = (int) $p->id;
                      $pcid = isset($p->client_id) ? (int) $p->client_id : 0;
                      $sel = ($preselected_project_id === $pid) || ($item && (int) $item->project_id === $pid);
                    ?>
                    <option value="<?php echo $pid; ?>"
                            data-client-id="<?php echo $pcid; ?>"
                            <?php echo $sel ? 'selected' : ''; ?>>
                      <?php echo esc_view($p->name); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="defect-field">
                <label class="form-label" for="defectReleaseId">Release <span class="text-muted fw-normal">(optional)</span></label>
                <select name="release_id" id="defectReleaseId" class="form-select">
                  <option value="">—</option>
                  <?php foreach ($releases as $rel): ?>
                    <option value="<?php echo (int) $rel->id; ?>" <?php echo ($item && (int) $item->release_id === (int) $rel->id) ? 'selected' : ''; ?>>
                      <?php echo esc_view($rel->version . ' — ' . $rel->title); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="defect-field">
                <label class="form-label" for="defectTaskId">Related task <span class="text-muted fw-normal">(optional)</span></label>
                <select name="task_id" id="defectTaskId" class="form-select">
                  <option value="">—</option>
                  <?php foreach ($tasks as $t): ?>
                    <option value="<?php echo (int) $t->id; ?>" <?php echo ($item && (int) $item->task_id === (int) $t->id) ? 'selected' : ''; ?>>
                      <?php echo esc_view($t->title); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="defect-form-group">
            <div class="defect-form-group-label">Classification</div>
            <div class="row g-2">
              <div class="col-6 defect-field">
                <label class="form-label" for="defectSeverity">Severity</label>
                <select name="severity" id="defectSeverity" class="form-select">
                  <?php foreach (array('low', 'medium', 'high', 'critical') as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo ($item && $item->severity === $s) ? 'selected' : (!$item && $s === 'medium' ? 'selected' : ''); ?>>
                      <?php echo ucfirst($s); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 defect-field">
                <label class="form-label" for="defectPriority">Priority</label>
                <select name="priority" id="defectPriority" class="form-select">
                  <?php foreach (array('low', 'medium', 'high', 'critical') as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo ($item && $item->priority === $s) ? 'selected' : (!$item && $s === 'medium' ? 'selected' : ''); ?>>
                      <?php echo ucfirst($s); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 defect-field">
                <label class="form-label" for="status">Status</label>
                <?php $this->load->view('partials/status_select', array(
                  'field_name' => 'status',
                  'module_type' => 'defects',
                  'current' => $item ? (string) $item->status : '',
                  'default_code' => 'open',
                )); ?>
              </div>
              <div class="col-12 defect-field">
                <label class="form-label" for="defectDueDate">Due date (SLA)</label>
                <input type="date" name="due_date" id="defectDueDate" class="form-control"
                       value="<?php echo ($item && !empty($item->due_date)) ? esc_view($item->due_date) : ''; ?>">
              </div>
            </div>
          </div>

          <div class="defect-form-group defect-form-group--last">
            <div class="defect-form-group-label">People &amp; files</div>
            <div class="vstack gap-2">
              <div class="defect-field">
                <label class="form-label" for="defectAssignedTo">Assign to</label>
                <select name="assigned_to" id="defectAssignedTo" class="form-select">
                  <option value="">Unassigned</option>
                  <?php foreach ($members as $m): ?>
                    <option value="<?php echo (int) $m->id; ?>" <?php echo ($item && (int) $item->assigned_to === (int) $m->id) ? 'selected' : ''; ?>>
                      <?php echo esc_view($m->name); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="defect-field defect-field--file">
                <label class="form-label" for="defectAttachments">Attachments</label>
                <label class="defect-file-drop" for="defectAttachments">
                  <i class="bi bi-cloud-arrow-up"></i>
                  <span class="defect-file-drop-title">Choose files</span>
                  <span class="defect-file-drop-hint">JPG, PNG, PDF, TXT, LOG, ZIP · max 5 MB</span>
                </label>
                <input type="file" name="attachments[]" id="defectAttachments" class="form-control defect-file-input" multiple
                       accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.log,.zip">
              </div>
            </div>
          </div>
        </aside>
      </div>

      <!-- Right: Title + Description + Steps (md-8) -->
      <div class="col-12 col-md-8 defect-form-main">
        <section class="defect-form-panel defect-form-panel--main h-100">
          <header class="defect-form-panel-head">
            <span class="defect-form-panel-icon defect-form-panel-icon--main"><i class="bi bi-card-text"></i></span>
            <div>
              <h2 class="defect-form-panel-title">Description &amp; steps</h2>
              <p class="defect-form-panel-hint mb-0">What broke, and how to reproduce it</p>
            </div>
          </header>

          <div class="vstack gap-3 defect-form-main-body">
            <div class="defect-field">
              <label class="form-label" for="defectTitle">Title</label>
              <input type="text" name="title" id="defectTitle" class="form-control defect-title-input" maxlength="255"
                     placeholder="Short summary of the bug"
                     value="<?php echo $item ? esc_view($item->title) : ''; ?>"
                     <?php echo !$is_edit ? 'autofocus' : ''; ?>>
            </div>
            <div class="defect-field">
              <label class="form-label" for="defect-description">Description</label>
              <textarea id="defect-description" name="description" class="form-control" rows="6" placeholder="Describe the issue…"><?php
                if ($item && !empty($item->description)) {
                    $allowed = '<p><br><strong><em><b><i><u><s><del><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre><span>';
                    echo strip_tags((string) $item->description, $allowed);
                }
              ?></textarea>
            </div>
            <div class="defect-field">
              <label class="form-label" for="defect-steps">Steps to reproduce</label>
              <textarea id="defect-steps" name="steps_to_reproduce" class="form-control" rows="6" placeholder="1. Go to…&#10;2. Click…&#10;3. See error…"><?php
                if ($item && !empty($item->steps_to_reproduce)) {
                    $allowed = '<p><br><strong><em><b><i><u><s><del><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre><span>';
                    echo strip_tags((string) $item->steps_to_reproduce, $allowed);
                }
              ?></textarea>
            </div>
          </div>
        </section>
      </div>
    </div>

    <div class="defect-form-actions">
      <div class="defect-form-actions-inner">
        <button type="submit" class="btn btn-primary defect-btn-save">
          <i class="bi bi-check-lg me-1"></i><?php echo $is_edit ? 'Save changes' : 'Log defect'; ?>
        </button>
        <a class="btn btn-light border" href="<?php echo esc_view($back_url); ?>">Cancel</a>
      </div>
    </div>
  </form>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function(){
  var defectEditorConfig = {
    menubar: false,
    statusbar: true,
    plugins: [
      'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
      'searchreplace', 'visualblocks', 'code', 'fullscreen',
      'insertdatetime', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | bold italic underline | bullist numlist | link | removeformat',
    branding: false,
    width: '100%',
    convert_urls: false,
    default_link_target: '_blank',
    content_style: 'body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; font-size: 14px; line-height: 1.5; }',
    formats: {
      bold: { inline: 'strong' },
      italic: { inline: 'em' },
      underline: { inline: 'u' },
      strikethrough: { inline: 'del' }
    }
  };

  var isNarrow = window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
  tinymce.init(Object.assign({}, defectEditorConfig, {
    selector: '#defect-description',
    height: isNarrow ? 180 : 260,
    placeholder: 'Describe the issue…',
    toolbar_mode: isNarrow ? 'scrolling' : 'wrap'
  }));
  tinymce.init(Object.assign({}, defectEditorConfig, {
    selector: '#defect-steps',
    height: isNarrow ? 200 : 280,
    placeholder: 'Numbered steps to reproduce the bug…',
    toolbar_mode: isNarrow ? 'scrolling' : 'wrap'
  }));

  var form = document.getElementById('defectForm');
  if (form) {
    form.addEventListener('submit', function() {
      if (window.tinymce) {
        var desc = tinymce.get('defect-description');
        var steps = tinymce.get('defect-steps');
        if (desc) { desc.save(); }
        if (steps) { steps.save(); }
      }
    });
  }

  var clientSel = document.getElementById('defectClientId');
  var projectSel = document.getElementById('defectProjectId');
  var releaseSel = document.getElementById('defectReleaseId');
  var taskSel = document.getElementById('defectTaskId');
  if (!projectSel || !releaseSel || !taskSel) { return; }

  var baseUrl = <?php echo json_encode(site_url('defects/ajax-options')); ?>;
  var currentRelease = releaseSel.value;
  var currentTask = taskSel.value;
  var allProjects = [];
  for (var i = 0; i < projectSel.options.length; i++) {
    var opt = projectSel.options[i];
    allProjects.push({
      value: opt.value,
      text: opt.textContent,
      clientId: opt.getAttribute('data-client-id') || '0',
      selected: opt.selected
    });
  }

  function fillSelect(sel, items, selected) {
    sel.innerHTML = '<option value="">—</option>';
    items.forEach(function(it) {
      var o = document.createElement('option');
      o.value = it.id;
      o.textContent = it.label;
      if (String(it.id) === String(selected)) { o.selected = true; }
      sel.appendChild(o);
    });
  }

  function filterProjects() {
    if (!clientSel) { return; }
    var cid = String(clientSel.value || '0');
    var keep = projectSel.value;
    projectSel.innerHTML = '';
    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '— Select project —';
    projectSel.appendChild(placeholder);
    allProjects.forEach(function(p) {
      if (!p.value) { return; }
      if (cid !== '0' && String(p.clientId) !== String(cid)) { return; }
      var o = document.createElement('option');
      o.value = p.value;
      o.textContent = p.text;
      o.setAttribute('data-client-id', p.clientId);
      if (String(p.value) === String(keep)) { o.selected = true; }
      projectSel.appendChild(o);
    });
    if (!projectSel.value && keep) {
      fillSelect(releaseSel, [], '');
      fillSelect(taskSel, [], '');
    }
  }

  function loadOptions() {
    var pid = projectSel.value;
    if (!pid) {
      fillSelect(releaseSel, [], '');
      fillSelect(taskSel, [], '');
      return;
    }
    fetch(baseUrl + '/' + pid, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (!res || res.status !== 'success' || !res.data) { return; }
        fillSelect(releaseSel, res.data.releases || [], currentRelease);
        fillSelect(taskSel, res.data.tasks || [], currentTask);
        currentRelease = '';
        currentTask = '';
      })
      .catch(function() { /* ignore network blips */ });
  }

  if (clientSel) {
    clientSel.addEventListener('change', function() {
      currentRelease = '';
      currentTask = '';
      filterProjects();
      loadOptions();
    });
    filterProjects();
  }

  projectSel.addEventListener('change', function() {
    currentRelease = '';
    currentTask = '';
    if (clientSel) {
      var selected = projectSel.options[projectSel.selectedIndex];
      var pcid = selected ? (selected.getAttribute('data-client-id') || '0') : '0';
      if (pcid !== '0' && String(clientSel.value) !== String(pcid)) {
        clientSel.value = pcid;
        filterProjects();
      }
    }
    loadOptions();
  });

  if (projectSel.value) { loadOptions(); }

  var fileInput = document.getElementById('defectAttachments');
  var fileTitle = document.querySelector('.defect-file-drop-title');
  if (fileInput && fileTitle) {
    fileInput.addEventListener('change', function() {
      var n = fileInput.files ? fileInput.files.length : 0;
      fileTitle.textContent = n > 0 ? (n + ' file' + (n === 1 ? '' : 's') + ' selected') : 'Choose files';
    });
  }
})();
</script>
<?php $this->load->view('partials/footer'); ?>
