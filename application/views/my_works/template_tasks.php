<?php
$clients = isset($clients) ? $clients : array();
$projects = isset($projects) ? $projects : array();
$projects_have_client = !empty($projects_have_client);
$users = isset($users) ? $users : array();
$teams = isset($teams) ? $teams : array();
$template_json = isset($template_json) ? $template_json : array();
$can_import_templates = !empty($can_import_templates);
$can_export_templates = !empty($can_export_templates);
$import_errors = $this->session->flashdata('import_errors');

$this->load->view('partials/header', array(
  'title' => 'Create Template Task',
  'extra_css' => array('assets/css/my-works.css', 'assets/css/tasks.css', 'assets/css/defects-form.css'),
));
?>
<div class="oms-form-compact defect-form-page">
<div class="container-fluid py-2 py-md-3 px-2 px-md-3 mw-page">

  <nav aria-label="breadcrumb" class="defect-form-crumb small mb-2 d-none d-md-block">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('my-works'); ?>">Second Brain</a></li>
      <li class="breadcrumb-item active" aria-current="page">Template Task</li>
    </ol>
  </nav>

  <div class="defect-form-hero oms-form-page-head d-flex align-items-center gap-2 gap-md-3 mb-3 flex-wrap">
    <a href="<?php echo site_url('my-works'); ?>" class="btn btn-light border defect-form-back oms-form-back" title="Back to Second Brain">
      <i class="bi bi-arrow-left"></i><span class="d-none d-sm-inline ms-1">Back</span>
    </a>
    <div class="defect-form-hero-icon d-none d-sm-flex" aria-hidden="true">
      <i class="bi bi-collection"></i>
    </div>
    <div class="oms-form-page-titles min-w-0 flex-grow-1">
      <h1 class="defect-form-title mb-0">Create Template Task</h1>
      <p class="defect-form-sub text-muted mb-0 d-none d-md-block">Pick project &amp; template on the left, describe the task on the right.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap ms-sm-auto">
      <?php if ($can_import_templates): ?>
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mwTemplateImportModal">
          <i class="bi bi-upload me-1"></i>Import Task
        </button>
      <?php endif; ?>
      <?php if ($can_export_templates): ?>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('my-works/template-tasks/export'); ?>">
          <i class="bi bi-download me-1"></i>Export Catalog
        </a>
      <?php endif; ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('tasks'); ?>">
        <i class="bi bi-list-task me-1"></i>Tasks
      </a>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm py-2" role="alert">
      <?php echo esc_view((string) $this->session->flashdata('success')); ?>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm py-2" role="alert">
      <?php echo esc_view((string) $this->session->flashdata('error')); ?>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (!empty($import_errors) && is_array($import_errors)): ?>
    <div class="alert alert-warning border-0 shadow-sm py-2 small">
      <div class="fw-semibold mb-1">Import notes</div>
      <ul class="mb-0 ps-3">
        <?php foreach ($import_errors as $err_line): ?>
          <li><?php echo esc_view((string) $err_line); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="<?php echo site_url('my-works/template-tasks'); ?>" id="mw-template-task-form" class="defect-form" enctype="multipart/form-data">
    <?php $this->load->view('my_works/_csrf'); ?>

    <div class="row g-3 defect-form-split">
      <div class="col-12 col-md-4 defect-form-meta">
        <aside class="defect-form-panel defect-form-panel--meta h-100">
          <header class="defect-form-panel-head">
            <span class="defect-form-panel-icon"><i class="bi bi-sliders"></i></span>
            <div>
              <h2 class="defect-form-panel-title">Details</h2>
              <p class="defect-form-panel-hint mb-0">Project, template &amp; assignment</p>
            </div>
          </header>

          <div class="defect-form-group">
            <div class="defect-form-group-label">Context</div>
            <div class="vstack gap-2">
              <div class="defect-field">
                <label class="form-label" for="mw-tt-client">Client</label>
                <?php if (!empty($clients)): ?>
                  <select name="client_id" id="mw-tt-client" class="form-select">
                    <option value="">-- Optional --</option>
                    <?php foreach ($clients as $client): ?>
                      <option value="<?php echo (int) $client->id; ?>">
                        <?php echo esc_view((string) $client->company_name); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                <?php else: ?>
                  <select id="mw-tt-client" class="form-select" disabled>
                    <option>No clients in system</option>
                  </select>
                  <input type="hidden" name="client_id" value="0">
                <?php endif; ?>
              </div>
              <div class="defect-field">
                <label class="form-label" for="mw-tt-project">Project <span class="text-danger">*</span></label>
                <?php if (!empty($projects)): ?>
                  <select name="project_id" id="mw-tt-project" class="form-select" required>
                    <option value="">-- Select project --</option>
                    <?php foreach ($projects as $p): ?>
                      <option value="<?php echo (int) $p->id; ?>"
                        data-client-id="<?php echo ($projects_have_client && isset($p->client_id)) ? (int) $p->client_id : 0; ?>">
                        <?php echo esc_view($p->name ? (string) $p->name : ('Project #' . (int) $p->id)); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <?php if ($projects_have_client && !empty($clients)): ?>
                    <div class="form-text">Filters by client when selected</div>
                  <?php endif; ?>
                <?php else: ?>
                  <select id="mw-tt-project" class="form-select" disabled required>
                    <option>No projects in system</option>
                  </select>
                  <input type="hidden" name="project_id" value="0">
                <?php endif; ?>
              </div>
              <div class="defect-field">
                <label class="form-label">Priority</label>
                <select name="priority" class="form-select">
                  <option value="low">Low</option>
                  <option value="medium" selected>Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>
          </div>

          <div class="defect-form-group">
            <div class="defect-form-group-label">Template</div>
            <div class="vstack gap-2">
              <div class="defect-field">
                <label class="form-label" for="mw-tt-team">Team <span class="text-danger">*</span></label>
                <select name="team" id="mw-tt-team" class="form-select" required>
                  <option value="">-- Select team --</option>
                  <?php foreach ($teams as $team): ?>
                    <option value="<?php echo esc_view($team); ?>">
                      <?php echo esc_view($team); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="defect-field">
                <label class="form-label" for="mw-tt-type">Type <span class="text-danger">*</span></label>
                <select name="template_type" id="mw-tt-type" class="form-select" required disabled>
                  <option value="">-- Select type --</option>
                </select>
              </div>
              <div class="defect-field">
                <label class="form-label" for="mw-tt-task">Task <span class="text-danger">*</span></label>
                <select name="template_id" id="mw-tt-task" class="form-select" required disabled>
                  <option value="">-- Select task --</option>
                </select>
              </div>
            </div>
          </div>

          <div class="defect-form-group">
            <div class="defect-form-group-label">Schedule &amp; people</div>
            <div class="vstack gap-2">
              <div class="defect-field">
                <label class="form-label" for="mw-tt-assign">Assigned To <span class="text-danger">*</span></label>
                <select name="assigned_to[]" id="mw-tt-assign" class="form-select oms-select2-multi" multiple style="width: 100%;" required>
                  <?php
                    $current_user_id = isset($current_user_id) ? (int) $current_user_id : 0;
                    foreach ($users as $u):
                      if (isset($u->emp_name) && trim((string) $u->emp_name) !== '') {
                        $label = trim((string) $u->emp_name);
                      } else {
                        $label = !empty($u->full_name) ? $u->full_name : (!empty($u->name) ? $u->name : $u->email);
                      }
                      $label = trim((string) $label);
                      if ($label === '') {
                        $label = !empty($u->email) ? (string) $u->email : ('User #' . (int) $u->id);
                      }
                      $uid = (int) $u->id;
                  ?>
                    <option value="<?php echo $uid; ?>" <?php echo $uid === $current_user_id ? 'selected' : ''; ?>><?php echo esc_view($label); ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text">Type to search. Click × on a tag to remove that user. First selected is primary.</div>
              </div>
              <div class="defect-field">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control">
              </div>
              <div class="defect-field">
                <label class="form-label" for="mw-tt-estimate-hours">Estimate (hrs)</label>
                <input type="number" name="estimate_hours" id="mw-tt-estimate-hours" class="form-control" min="0" max="9" step="1" placeholder="e.g. 2">
                <div class="form-text">Optional. Single digit 0–9. Auto-filled from catalog when you pick a task; you can override.</div>
              </div>
              <div class="defect-field">
                <label class="form-label" for="mw-tt-attachment">Attachment</label>
                <?php $this->load->view('my_works/_attachment_field', array(
                  'input_id' => 'mw-tt-attachment',
                  'input_name' => 'attachments[]',
                )); ?>
              </div>
            </div>
          </div>

          <input type="hidden" name="status" value="pending">
        </aside>
      </div>

      <div class="col-12 col-md-8 defect-form-main">
        <section class="defect-form-panel defect-form-panel--main h-100">
          <header class="defect-form-panel-head">
            <span class="defect-form-panel-icon defect-form-panel-icon--main"><i class="bi bi-card-text"></i></span>
            <div>
              <h2 class="defect-form-panel-title">Description</h2>
              <p class="defect-form-panel-hint mb-0">Optional details for the new task</p>
            </div>
          </header>
          <div class="vstack gap-3 defect-form-main-body">
            <div class="defect-field">
              <label class="form-label" for="mw-tt-description">Description</label>
              <textarea id="mw-tt-description" name="description" rows="6" class="form-control" placeholder="Optional description for the new task"></textarea>
              <div class="form-text">Rich text — bold, italic, colors, lists, and links supported.</div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <div class="defect-form-actions">
      <div class="defect-form-actions-inner">
        <button class="btn btn-primary defect-btn-save" type="submit">
          <i class="bi bi-check-lg me-1"></i>Create Task
        </button>
        <a class="btn btn-light border" href="<?php echo site_url('tasks'); ?>">Cancel</a>
      </div>
    </div>
  </form>

<script>
(function () {
  var templates = <?php echo json_encode($template_json, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

  var teamSel = document.getElementById('mw-tt-team');
  var typeSel = document.getElementById('mw-tt-type');
  var taskSel = document.getElementById('mw-tt-task');
  var estInput = document.getElementById('mw-tt-estimate-hours');

  function clearSelect(sel, placeholder, disabled) {
    if (!sel) {
      return;
    }
    sel.innerHTML = '';
    var opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder;
    sel.appendChild(opt);
    sel.disabled = !!disabled;
  }

  function typesForTeam(team) {
    var seen = {};
    var out = [];
    templates.forEach(function (t) {
      if (t.team !== team || !t.template_type || seen[t.template_type]) {
        return;
      }
      seen[t.template_type] = true;
      out.push(t.template_type);
    });
    out.sort(function (a, b) {
      return String(a).localeCompare(String(b));
    });
    return out;
  }

  function tasksForTeamType(team, type) {
    return templates.filter(function (t) {
      return t.team === team && t.template_type === type;
    }).sort(function (a, b) {
      return String(a.title).localeCompare(String(b.title));
    });
  }

  function fillTypes() {
    if (!typeSel || !teamSel) {
      return;
    }
    var team = teamSel.value;
    clearSelect(typeSel, '-- Select type --', team === '');
    clearSelect(taskSel, '-- Select task --', true);
    if (team === '') {
      return;
    }
    typesForTeam(team).forEach(function (type) {
      var opt = document.createElement('option');
      opt.value = type;
      opt.textContent = type;
      typeSel.appendChild(opt);
    });
    if (typeSel.options.length === 2) {
      typeSel.selectedIndex = 1;
      fillTasks();
    }
  }

  function fillTasks() {
    if (!taskSel || !teamSel || !typeSel) {
      return;
    }
    var team = teamSel.value;
    var type = typeSel.value;
    clearSelect(taskSel, '-- Select task --', team === '' || type === '');
    if (estInput) {
      estInput.value = '';
    }
    if (team === '' || type === '') {
      return;
    }
    tasksForTeamType(team, type).forEach(function (t) {
      var opt = document.createElement('option');
      opt.value = String(t.id);
      opt.textContent = t.estimate_hours
        ? (t.title + ' (' + t.estimate_hours + ' hrs)')
        : t.title;
      if (t.estimate_hours) {
        opt.setAttribute('data-estimate', String(t.estimate_hours));
      }
      taskSel.appendChild(opt);
    });
    if (taskSel.options.length === 2) {
      taskSel.selectedIndex = 1;
      syncEstimateFromTask();
    }
  }

  function syncEstimateFromTask() {
    if (!taskSel || !estInput) {
      return;
    }
    var opt = taskSel.options[taskSel.selectedIndex];
    if (!opt || !opt.value) {
      estInput.value = '';
      return;
    }
    var est = opt.getAttribute('data-estimate') || '';
    estInput.value = est;
  }

  if (teamSel) {
    teamSel.addEventListener('change', fillTypes);
  }
  if (typeSel) {
    typeSel.addEventListener('change', fillTasks);
  }
  if (taskSel) {
    taskSel.addEventListener('change', syncEstimateFromTask);
  }

  if (teamSel && teamSel.options.length === 2) {
    teamSel.selectedIndex = 1;
    fillTypes();
  }

  var clientSel = document.getElementById('mw-tt-client');
  var projectSel = document.getElementById('mw-tt-project');
  if (clientSel && projectSel) {
    var allProjectOptions = [];
    for (var i = 0; i < projectSel.options.length; i++) {
      var opt = projectSel.options[i];
      allProjectOptions.push({
        value: opt.value,
        text: opt.textContent,
        clientId: opt.getAttribute('data-client-id') || '0'
      });
    }
    function filterProjects() {
      var clientId = clientSel.value || '';
      var current = projectSel.value;
      projectSel.innerHTML = '';
      allProjectOptions.forEach(function (item) {
        if (item.value === '') {
          var blank = document.createElement('option');
          blank.value = '';
          blank.textContent = item.text;
          projectSel.appendChild(blank);
          return;
        }
        if (clientId !== '' && item.clientId !== '0' && item.clientId !== clientId) {
          return;
        }
        var o = document.createElement('option');
        o.value = item.value;
        o.textContent = item.text;
        o.setAttribute('data-client-id', item.clientId);
        projectSel.appendChild(o);
      });
      if (current) {
        projectSel.value = current;
        if (projectSel.value !== current) {
          projectSel.value = '';
        }
      }
    }
    clientSel.addEventListener('change', filterProjects);
  }
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
  if (!document.getElementById('mw-tt-description')) {
    return;
  }
  tinymce.init({
    selector: '#mw-tt-description',
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
    height: 280,
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
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }'
  });
  var form = document.getElementById('mw-template-task-form');
  if (form) {
    form.addEventListener('submit', function () {
      if (window.tinymce && tinymce.get('mw-tt-description')) {
        tinymce.get('mw-tt-description').save();
      }
    });
  }
})();
</script>

<script src="<?php echo base_url('assets/js/my-works-attachment.js'); ?>"></script>

<?php if ($can_import_templates): ?>
<div class="modal fade" id="mwTemplateImportModal" tabindex="-1" aria-labelledby="mwTemplateImportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered mw-tt-import-dialog">
    <div class="modal-content mw-tt-import-content">
      <form method="post" action="<?php echo site_url('my-works/template-tasks/import'); ?>" enctype="multipart/form-data" id="mw-tt-import-form">
        <?php $this->load->view('my_works/_csrf'); ?>
        <div class="modal-header mw-tt-import-header border-0">
          <div class="mw-tt-import-title-wrap">
            <span class="mw-tt-import-icon" aria-hidden="true"><i class="bi bi-file-earmark-spreadsheet"></i></span>
            <div>
              <h2 class="modal-title h6 mb-0" id="mwTemplateImportModalLabel">Import Template Tasks</h2>
              <p class="mw-tt-import-subtitle mb-0">Add catalog rows from CSV</p>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body mw-tt-import-body pt-0">
          <div class="mw-tt-import-cols" aria-label="Required CSV columns">
            <span class="mw-tt-import-col-chip">team</span>
            <span class="mw-tt-import-col-chip">template_type</span>
            <span class="mw-tt-import-col-chip">title</span>
            <span class="mw-tt-import-col-chip">estimate_hours</span>
            <span class="mw-tt-import-col-chip mw-tt-import-col-chip--opt">sort_order</span>
            <span class="mw-tt-import-col-chip mw-tt-import-col-chip--opt">is_active</span>
          </div>
          <p class="mw-tt-import-hint mb-3">
            Duplicates (same team + type + title) are skipped. Max 500 rows per file.
          </p>

          <a class="mw-tt-import-sample" href="<?php echo site_url('my-works/template-tasks/sample-csv'); ?>">
            <span class="mw-tt-import-sample-icon" aria-hidden="true"><i class="bi bi-filetype-csv"></i></span>
            <span class="mw-tt-import-sample-text">
              <strong>Download sample CSV</strong>
              <span>Ready-to-edit format with example rows</span>
            </span>
            <i class="bi bi-download mw-tt-import-sample-dl" aria-hidden="true"></i>
          </a>

          <label class="mw-tt-import-drop" for="mw-tt-import-file" id="mw-tt-import-drop">
            <input type="file" name="file" id="mw-tt-import-file" class="mw-tt-import-file-input" accept=".csv,text/csv" required>
            <span class="mw-tt-import-drop-icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></span>
            <span class="mw-tt-import-drop-title">Drop CSV here or <em>browse</em></span>
            <span class="mw-tt-import-drop-meta" id="mw-tt-import-file-label">No file chosen · .csv only</span>
          </label>
        </div>
        <div class="modal-footer mw-tt-import-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="mw-tt-import-submit" disabled>
            <i class="bi bi-upload me-1"></i>Import
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
(function () {
  var input = document.getElementById('mw-tt-import-file');
  var drop = document.getElementById('mw-tt-import-drop');
  var label = document.getElementById('mw-tt-import-file-label');
  var submit = document.getElementById('mw-tt-import-submit');
  if (!input || !drop || !label || !submit) {
    return;
  }

  function setFile(file) {
    if (!file) {
      label.textContent = 'No file chosen · .csv only';
      submit.disabled = true;
      drop.classList.remove('is-filled');
      return;
    }
    label.textContent = file.name;
    submit.disabled = false;
    drop.classList.add('is-filled');
  }

  input.addEventListener('change', function () {
    setFile(input.files && input.files[0] ? input.files[0] : null);
  });

  ['dragenter', 'dragover'].forEach(function (evt) {
    drop.addEventListener(evt, function (e) {
      e.preventDefault();
      e.stopPropagation();
      drop.classList.add('is-dragover');
    });
  });
  ['dragleave', 'drop'].forEach(function (evt) {
    drop.addEventListener(evt, function (e) {
      e.preventDefault();
      e.stopPropagation();
      drop.classList.remove('is-dragover');
    });
  });
  drop.addEventListener('drop', function (e) {
    var files = e.dataTransfer && e.dataTransfer.files;
    if (!files || !files.length) {
      return;
    }
    var file = files[0];
    var name = String(file.name || '').toLowerCase();
    if (name.indexOf('.csv') === -1) {
      setFile(null);
      input.value = '';
      label.textContent = 'Please choose a .csv file';
      return;
    }
    try {
      var dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;
    } catch (err) {
      /* older browsers: click browse instead */
    }
    setFile(file);
  });
})();
</script>
<?php endif; ?>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
<?php
  $this->load->view('partials/oms_select2_multi', array(
    'oms_select2_selectors' => array('#mw-tt-assign'),
    'oms_select2_placeholder' => 'Select assignee(s)…',
  ));
?>

