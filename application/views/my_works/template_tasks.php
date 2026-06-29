<?php $this->load->view('partials/header', array('title' => 'Template Task', 'extra_css' => array('assets/css/my-works.css', 'assets/css/tasks.css'))); ?>

<?php
  $clients = isset($clients) ? $clients : array();
  $users = isset($users) ? $users : array();
  $teams = isset($teams) ? $teams : array();
  $statuses = isset($statuses) ? $statuses : array();
  $projects_have_client = !empty($projects_have_client);
  $template_json = isset($template_json) ? $template_json : array();
  $projects_json = isset($projects_json) ? $projects_json : array();
?>

<div class="container-fluid py-3 mw-page">
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3 flex-wrap">
    <div>
      <h1 class="h4 mb-1 fw-bold">Create Task from Template</h1>
      <p class="text-muted small mb-0">Select client, task type, and template tasks — saved to the Tasks module.</p>
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

  <div class="card shadow-sm border-0">
    <div class="card-body p-3 p-md-4">
      <form method="post" action="<?php echo site_url('my-works/template-tasks'); ?>" id="mw-template-task-form">
        <?php $this->load->view('my_works/_csrf'); ?>

        <div class="row g-3">
          <?php if ($projects_have_client && !empty($clients)): ?>
            <div class="col-md-6">
              <label class="form-label" for="mw-tt-client">
                <i class="bi bi-building me-1"></i>Client <span class="text-danger">*</span>
              </label>
              <select name="client_id" id="mw-tt-client" class="form-select" required>
                <option value="">-- Select client --</option>
                <?php foreach ($clients as $client): ?>
                  <option value="<?php echo (int) $client->id; ?>">
                    <?php echo esc_view((string) $client->company_name, ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php else: ?>
            <input type="hidden" name="client_id" value="0">
          <?php endif; ?>

          <div class="col-md-6">
            <label class="form-label" for="mw-tt-project">
              <i class="bi bi-folder me-1"></i>Project <span class="text-danger">*</span>
            </label>
            <select name="project_id" id="mw-tt-project" class="form-select" required>
              <option value="">-- Select project --</option>
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
              <i class="bi bi-tags me-1"></i>Task type <span class="text-danger">*</span>
            </label>
            <select name="template_type" id="mw-tt-type" class="form-select" required disabled>
              <option value="">-- Select task type --</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="mw-tt-task">
              <i class="bi bi-check2-square me-1"></i>Template task(s) <span class="text-danger">*</span>
            </label>
            <select name="template_ids[]" id="mw-tt-task" class="form-select" multiple required disabled size="6">
            </select>
            <div class="form-text">Hold Ctrl (Windows) or Cmd (Mac) to select multiple tasks.</div>
          </div>

          <div class="col-12">
            <label class="form-label" for="mw-tt-description">
              <i class="bi bi-file-text me-1"></i>Description
            </label>
            <textarea id="mw-tt-description" name="description" rows="6" class="form-control" placeholder="Optional description applied to all selected tasks"></textarea>
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
            <label class="form-label"><i class="bi bi-arrow-right-circle me-1"></i>Status</label>
            <select name="status" class="form-select">
              <?php if (!empty($statuses)): ?>
                <?php foreach ($statuses as $st): ?>
                  <option value="<?php echo esc_view((string) $st->code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) $st->code === 'pending' ? 'selected' : ''; ?>>
                    <?php echo esc_view((string) $st->name, ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="pending" selected>Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="blocked">Blocked</option>
              <?php endif; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label"><i class="bi bi-calendar-event me-1"></i>Start date</label>
            <input type="date" name="start_date" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label"><i class="bi bi-calendar-check me-1"></i>Due date</label>
            <input type="date" name="due_date" class="form-control">
          </div>
        </div>

        <div class="mt-4 d-flex gap-2 flex-wrap">
          <button class="btn btn-primary" type="submit">
            <i class="bi bi-check-lg me-1"></i>Create Task(s)
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
  var hasClient = <?php echo $projects_have_client ? 'true' : 'false'; ?>;

  var clientSel = document.getElementById('mw-tt-client');
  var projectSel = document.getElementById('mw-tt-project');
  var teamSel = document.getElementById('mw-tt-team');
  var typeSel = document.getElementById('mw-tt-type');
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

  function typesForTeam(team) {
    var map = {};
    templates.forEach(function (t) {
      if (t.team === team && t.template_type) {
        map[t.template_type] = true;
      }
    });
    return Object.keys(map).sort();
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
    clearSelect(typeSel, '-- Select task type --');
    typeSel.disabled = team === '';
    if (team === '') {
      fillTasks();
      return;
    }
    typesForTeam(team).forEach(function (type) {
      var opt = document.createElement('option');
      opt.value = type;
      opt.textContent = type;
      typeSel.appendChild(opt);
    });
    fillTasks();
  }

  function fillTasks() {
    if (!taskSel || !teamSel || !typeSel) {
      return;
    }
    var team = teamSel.value;
    var type = typeSel.value;
    taskSel.innerHTML = '';
    taskSel.disabled = team === '' || type === '';
    if (team === '' || type === '') {
      return;
    }
    tasksForTeamType(team, type).forEach(function (t) {
      var opt = document.createElement('option');
      opt.value = String(t.id);
      opt.textContent = t.title;
      taskSel.appendChild(opt);
    });
  }

  if (hasClient && clientSel) {
    clientSel.addEventListener('change', fillProjects);
  }
  if (teamSel) {
    teamSel.addEventListener('change', fillTypes);
  }
  if (typeSel) {
    typeSel.addEventListener('change', fillTasks);
  }

  fillProjects();
  if (teamSel && teamSel.options.length === 2) {
    teamSel.selectedIndex = 1;
    fillTypes();
    if (typeSel && typeSel.options.length === 2) {
      typeSel.selectedIndex = 1;
      fillTasks();
    }
  }
})();
</script>

<?php $this->load->view('partials/footer'); ?>
