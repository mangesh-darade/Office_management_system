<?php $this->load->view('partials/header', array('title' => 'Create Template Task', 'extra_css' => array('assets/css/my-works.css', 'assets/css/tasks.css'))); ?>
<div class="oms-form-compact">

<?php
  $clients = isset($clients) ? $clients : array();
  $users = isset($users) ? $users : array();
  $teams = isset($teams) ? $teams : array();
  $statuses = isset($statuses) ? $statuses : array();
  $template_json = isset($template_json) ? $template_json : array();
?>

<div class="container-fluid py-2 mw-page">
  <div class="oms-form-page-head mw-page-head-with-back d-flex align-items-start gap-2 mb-2 flex-wrap">
    <?php $this->load->view('my_works/_back_btn', array(
      'back_url' => site_url('my-works'),
      'back_title' => 'Back to Second Brain',
    )); ?>
    <div class="min-w-0 flex-grow-1">
      <h1 class="h5 mb-0 fw-semibold">Create Template Task</h1>
      <p class="text-muted small mb-0">Select client, team, type, and one task — saved to My Works.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap ms-sm-auto">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('my-works'); ?>">
        <i class="bi bi-list-task me-1"></i>My Works
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
      <form method="post" action="<?php echo site_url('my-works/template-tasks'); ?>" id="mw-template-task-form" enctype="multipart/form-data">
        <?php $this->load->view('my_works/_csrf'); ?>

        <div class="row g-2 oms-form-grid">
          <div class="col-md-4">
            <label class="form-label" for="mw-tt-client">
              <i class="bi bi-building me-1"></i>Client
            </label>
            <?php if (!empty($clients)): ?>
              <select name="client_id" id="mw-tt-client" class="form-select">
                <option value="">-- Optional --</option>
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
            <label class="form-label" for="mw-tt-assign">Assigned To <span class="text-danger">*</span></label>
            <select name="created_for" id="mw-tt-assign" class="form-select" required>
              <?php
                $current_user_id = isset($current_user_id) ? (int) $current_user_id : 0;
                foreach ($users as $u):
                  if (isset($u->emp_name) && trim((string) $u->emp_name) !== '') {
                    $label = trim((string) $u->emp_name);
                  } else {
                    $label = !empty($u->full_name) ? $u->full_name : (!empty($u->name) ? $u->name : $u->email);
                  }
                  $label = trim((string) $label);
                  $label = $label !== '' ? $label . ' (' . $u->email . ')' : $u->email;
                  $uid = (int) $u->id;
              ?>
                <option value="<?php echo $uid; ?>" <?php echo $uid === $current_user_id ? 'selected' : ''; ?>><?php echo esc_view($label, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
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

          <div class="col-md-4">
            <label class="form-label" for="mw-tt-type">
              <i class="bi bi-tags me-1"></i>Type <span class="text-danger">*</span>
            </label>
            <select name="template_type" id="mw-tt-type" class="form-select" required disabled>
              <option value="">-- Select type --</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="mw-tt-task">
              <i class="bi bi-check2-square me-1"></i>Task <span class="text-danger">*</span>
            </label>
            <select name="template_id" id="mw-tt-task" class="form-select" required disabled>
              <option value="">-- Select task --</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label"><i class="bi bi-calendar-check me-1"></i>Due Date</label>
            <input type="date" name="due_date" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label" for="mw-tt-attachment">
              <i class="bi bi-paperclip me-1"></i>Attachment
            </label>
            <?php $this->load->view('my_works/_attachment_field', array(
              'input_id' => 'mw-tt-attachment',
              'input_name' => 'attachments[]',
            )); ?>
          </div>

          <?php $default_status = isset($default_status) ? (string) $default_status : 'new'; ?>
          <input type="hidden" name="status" value="<?php echo esc_view($default_status, ENT_QUOTES, 'UTF-8'); ?>">

          <div class="col-12">
            <label class="form-label" for="mw-tt-description">
              <i class="bi bi-file-text me-1"></i>Description
            </label>
            <textarea id="mw-tt-description" name="description" rows="6" class="form-control" placeholder="Optional description for the new work item"></textarea>
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

  var teamSel = document.getElementById('mw-tt-team');
  var typeSel = document.getElementById('mw-tt-type');
  var taskSel = document.getElementById('mw-tt-task');

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
    if (team === '' || type === '') {
      return;
    }
    tasksForTeamType(team, type).forEach(function (t) {
      var opt = document.createElement('option');
      opt.value = String(t.id);
      opt.textContent = t.title;
      taskSel.appendChild(opt);
    });
    if (taskSel.options.length === 2) {
      taskSel.selectedIndex = 1;
    }
  }

  if (teamSel) {
    teamSel.addEventListener('change', fillTypes);
  }
  if (typeSel) {
    typeSel.addEventListener('change', fillTasks);
  }

  if (teamSel && teamSel.options.length === 2) {
    teamSel.selectedIndex = 1;
    fillTypes();
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

</div>
<?php $this->load->view('partials/footer'); ?>
