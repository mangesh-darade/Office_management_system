<?php $this->load->view('partials/header', array('title' => 'Template Task', 'extra_css' => array('assets/css/my-works.css', 'assets/css/tasks.css'))); ?>
<div class="oms-form-compact">

<?php
  $clients = isset($clients) ? $clients : array();
  $users = isset($users) ? $users : array();
  $teams = isset($teams) ? $teams : array();
  $statuses = isset($statuses) ? $statuses : array();
  $projects_have_client = !empty($projects_have_client);
  $template_json = isset($template_json) ? $template_json : array();
  $projects_json = isset($projects_json) ? $projects_json : array();
?>

<div class="container-fluid py-2 mw-page">
  <div class="oms-form-page-head d-flex justify-content-between align-items-start gap-2 mb-2 flex-wrap">
    <div>
      <h1 class="h5 mb-0 fw-semibold">Create Task from Template</h1>
      <p class="text-muted small mb-0">Select client, team, and one template task — saved to the Tasks module.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('my-works'); ?>">
        <i class="bi bi-arrow-left me-1"></i>Back to My Works
      </a>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('tasks'); ?>">
        <i class="bi bi-list-task me-1"></i>Tasks List
      </a>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
      <?php echo esc_view((string) $this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
      <?php echo esc_view((string) $this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm border-0 oms-form-card">
    <div class="card-body">
      <form method="post" action="<?php echo site_url('my-works/template-tasks'); ?>" id="mw-template-task-form">
        <?php $this->load->view('my_works/_csrf'); ?>

        <div class="row g-2 oms-form-grid">
          <div class="col-md-6">
            <label class="form-label" for="mw-tt-client">
              <i class="bi bi-building me-1"></i>Client <?php if ($projects_have_client): ?><span class="text-danger">*</span><?php endif; ?>
            </label>
            <?php if (!empty($clients)): ?>
              <select name="client_id" id="mw-tt-client" class="form-select" <?php echo $projects_have_client ? 'required' : ''; ?>>
                <option value=""><?php echo $projects_have_client ? '-- Select client --' : '-- All clients / optional --'; ?></option>
                <?php foreach ($clients as $client): ?>
                  <option value="<?php echo (int) $client->id; ?>">
                    <?php echo esc_view((string) $client->company_name, ENT_QUOTES, 'UTF-8'); ?>
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

          <div class="col-md-6">
            <label class="form-label" for="mw-tt-project">
              <i class="bi bi-folder me-1"></i>Project <span class="text-danger">*</span>
            </label>
            <select name="project_id" id="mw-tt-project" class="form-select" required>
              <option value="">-- Select project --</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="mw-tt-team">
              <i class="bi bi-people me-1"></i>Team <span class="text-danger">*</span>
            </label>
            <select name="team" id="mw-tt-team" class="form-select" required>
              <option value="">-- Select team --</option>
              <?php foreach ($teams as $team): ?>
                <option value="<?php echo esc_view($team, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo esc_view($team, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="mw-tt-task">
              <i class="bi bi-check2-square me-1"></i>Template task <span class="text-danger">*</span>
            </label>
            <select name="template_id" id="mw-tt-task" class="form-select" required disabled>
              <option value="">-- Select template task --</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Assign to</label>
            <select name="assigned_to" class="form-select">
              <option value="">-- Unassigned --</option>
              <?php foreach ($users as $u): ?>
                <?php
                  if (isset($u->emp_name) && trim((string) $u->emp_name) !== '') {
                    $label = trim((string) $u->emp_name);
                  } else {
                    $label = !empty($u->full_name) ? $u->full_name : (!empty($u->name) ? $u->name : $u->email);
                  }
                  $label = trim((string) $label);
                  $label = $label !== '' ? $label . ' (' . $u->email . ')' : $u->email;
                ?>
                <option value="<?php echo (int) $u->id; ?>"><?php echo esc_view($label, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label"><i class="bi bi-flag me-1"></i>Priority</label>
            <select name="priority" class="form-select">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="mw-tt-status"><i class="bi bi-arrow-right-circle me-1"></i>Status</label>
            <select name="status" id="mw-tt-status" class="form-select" <?php echo empty($statuses) ? 'disabled' : ''; ?>>
              <?php if (!empty($statuses)): ?>
                <?php foreach ($statuses as $st): ?>
                  <option value="<?php echo esc_view((string) $st->code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) $st->code === 'pending' ? 'selected' : ''; ?>>
                    <?php echo esc_view((string) $st->name, ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="">— No task statuses configured —</option>
              <?php endif; ?>
            </select>
            <div class="form-text">From <a href="<?php echo site_url('statuses?type=tasks'); ?>">Statuses (Tasks)</a></div>
          </div>

          <div class="col-md-6">
            <label class="form-label"><i class="bi bi-calendar-event me-1"></i>Start date</label>
            <input type="date" name="start_date" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label"><i class="bi bi-calendar-check me-1"></i>Due date</label>
            <input type="date" name="due_date" class="form-control">
          </div>

          <div class="col-12">
            <label class="form-label" for="mw-tt-description">
              <i class="bi bi-file-text me-1"></i>Description
            </label>
            <textarea id="mw-tt-description" name="description" rows="6" class="form-control" placeholder="Optional description for the new task"></textarea>
            <div class="form-text">Rich text — bold, italic, colors, lists, and links supported.</div>
          </div>
        </div>

        <div class="oms-form-actions">
          <button class="btn btn-primary" type="submit">
            <i class="bi bi-check-lg me-1"></i>Create Task
          </button>
          <a class="btn btn-light" href="<?php echo site_url('my-works'); ?>">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  var templates = <?php echo json_encode($template_json, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  var projects = <?php echo json_encode($projects_json, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  var hasClient = <?php echo ($projects_have_client && !empty($clients)) ? 'true' : 'false'; ?>;
  var hasClientSelect = <?php echo !empty($clients) ? 'true' : 'false'; ?>;

  var clientSel = document.getElementById('mw-tt-client');
  var projectSel = document.getElementById('mw-tt-project');
  var teamSel = document.getElementById('mw-tt-team');
  var taskSel = document.getElementById('mw-tt-task');

  function clearSelect(sel, placeholder) {
    if (!sel) {
      return;
    }
    sel.innerHTML = '';
    var opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder;
    sel.appendChild(opt);
  }

  function fillProjects() {
    if (!projectSel) {
      return;
    }
    projectSel.innerHTML = '<option value="">-- Select project --</option>';
    projects.forEach(function (p) {
      if (hasClient) {
        var selectedClient = parseInt(clientSel ? clientSel.value : '0', 10) || 0;
        if (selectedClient < 1) {
          return;
        }
        if (parseInt(p.client_id, 10) !== selectedClient) {
          return;
        }
      }
      var opt = document.createElement('option');
      opt.value = String(p.id);
      opt.textContent = p.name || ('Project #' + p.id);
      projectSel.appendChild(opt);
    });
  }

  function tasksForTeam(team) {
    return templates.filter(function (t) {
      return t.team === team;
    }).sort(function (a, b) {
      return String(a.title).localeCompare(String(b.title));
    });
  }

  function fillTasks() {
    if (!taskSel || !teamSel) {
      return;
    }
    var team = teamSel.value;
    clearSelect(taskSel, '-- Select template task --');
    taskSel.disabled = team === '';
    if (team === '') {
      return;
    }
    tasksForTeam(team).forEach(function (t) {
      var opt = document.createElement('option');
      opt.value = String(t.id);
      opt.textContent = t.title;
      taskSel.appendChild(opt);
    });
  }

  if (hasClientSelect && clientSel) {
    clientSel.addEventListener('change', fillProjects);
  }
  if (teamSel) {
    teamSel.addEventListener('change', fillTasks);
  }

  fillProjects();
  if (teamSel && teamSel.options.length === 2) {
    teamSel.selectedIndex = 1;
    fillTasks();
    if (taskSel && taskSel.options.length === 2) {
      taskSel.selectedIndex = 1;
    }
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

</div>
<?php $this->load->view('partials/footer'); ?>
