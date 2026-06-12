<?php $this->load->view('partials/header', array('title' => 'My Work Overview', 'extra_css' => array('assets/css/my-works.css'))); ?>

<?php
  $view_mode = 'overview';
  $dashboard_sections = isset($dashboard_sections) ? $dashboard_sections : array();
  $dashboard_counts = isset($dashboard_counts) ? $dashboard_counts : array('ad_hoc' => 0, 'project' => 0, 'total' => 0);
  $filterProjectId = isset($filters['project_id']) ? (int) $filters['project_id'] : 0;
  $filterAssignee = isset($filters['created_for']) ? (int) $filters['created_for'] : 0;
  $baseQuery = array('view' => 'overview');
  foreach ($filters as $k => $v) {
    if ($k === 'current_user_id') {
      continue;
    }
    if ($v !== '' && $v !== 0 && $v !== '0') {
      $baseQuery[$k] = $v;
    }
  }
?>

<div class="mw-dash-page">

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 mx-3 mt-2 mb-0" role="alert">
      <?php echo htmlspecialchars((string) $this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <header class="mw-dash-header">
    <div class="mw-dash-header-left">
      <h1 class="mw-dash-title">My Work Overview</h1>
      <p class="mw-dash-subtitle">Organize, prioritize and track your work items efficiently.</p>
    </div>
    <div class="mw-dash-header-center">
      <form method="get" action="<?php echo site_url('my-works'); ?>" class="mw-dash-search-form">
        <input type="hidden" name="view" value="overview">
        <?php foreach ($baseQuery as $qk => $qv): ?>
          <?php if ($qk === 'q' || $qk === 'view') { continue; } ?>
          <input type="hidden" name="<?php echo htmlspecialchars($qk, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars((string) $qv, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>
        <div class="mw-dash-search-wrap">
          <i class="bi bi-search"></i>
          <input type="search" name="q" class="mw-dash-search-input" placeholder="Search tasks, projects, clients…" value="<?php echo htmlspecialchars(isset($filters['q']) ? (string) $filters['q'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </form>
    </div>
    <div class="mw-dash-header-right">
      <?php if (!empty($can_add)): ?>
        <a class="btn btn-primary btn-sm mw-dash-create-btn" href="<?php echo site_url('my-works/create'); ?>">
          <i class="bi bi-plus-lg me-1"></i>Create Task
        </a>
      <?php endif; ?>
      <div class="mw-dash-date-pill">
        <i class="bi bi-calendar3"></i>
        <span><?php echo htmlspecialchars(my_works_dashboard_week_range_label(), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    </div>
  </header>

  <div class="mw-dash-view-tabs">
    <a class="mw-dash-view-tab active" href="<?php echo site_url('my-works?view=overview'); ?>">Overview</a>
    <a class="mw-dash-view-tab" href="<?php echo site_url('my-works?view=list'); ?>">List</a>
    <a class="mw-dash-view-tab" href="<?php echo site_url('my-works?view=board'); ?>">Board</a>
    <a class="mw-dash-view-tab" href="<?php echo site_url('my-works?view=matrix'); ?>">Matrix</a>
  </div>

  <div class="mw-dash-filters">
    <form method="get" action="<?php echo site_url('my-works'); ?>" class="mw-dash-filter-form" id="mwDashFilterForm">
      <input type="hidden" name="view" value="overview">
      <?php if (!empty($filters['q'])): ?>
        <input type="hidden" name="q" value="<?php echo htmlspecialchars((string) $filters['q'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>
      <label class="mw-dash-filter-label">
        Assigned To:
        <select name="created_for" class="form-select form-select-sm mw-dash-filter-select" <?php echo empty($can_filter_users) ? 'disabled' : ''; ?>>
          <option value="0">All Users</option>
          <?php foreach ((array) $users as $u): ?>
            <option value="<?php echo (int) $u->id; ?>" <?php echo $filterAssignee === (int) $u->id ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars(my_works_user_label($u->name, $u->email, $u->id), ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="mw-dash-filter-label">
        Project:
        <select name="project_id" class="form-select form-select-sm mw-dash-filter-select">
          <option value="0">All Projects</option>
          <?php foreach ((array) $projects as $p): ?>
            <option value="<?php echo (int) $p->id; ?>" <?php echo $filterProjectId === (int) $p->id ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($p->name ? (string) $p->name : ('Project #' . (int) $p->id), ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
    </form>
    <div class="mw-dash-legend">
      <?php $this->load->helper('my_works_status'); ?>
      <?php foreach (my_works_status_records() as $st): ?>
        <span class="mw-dash-legend-item">
          <span class="mw-dash-status-dot mw-dash-dot-<?php echo htmlspecialchars(my_works_status_dashboard_dot_class($st->code), ENT_QUOTES, 'UTF-8'); ?>" style="background-color:<?php echo htmlspecialchars(my_works_status_hex_color($st->code), ENT_QUOTES, 'UTF-8'); ?>;"></span>
          <?php echo htmlspecialchars((string) $st->name, ENT_QUOTES, 'UTF-8'); ?>
        </span>
      <?php endforeach; ?>
      <?php if (function_exists('has_module_access') && has_module_access('statuses')): ?>
        <a class="mw-dash-legend-manage small" href="<?php echo site_url('statuses?type=my_works'); ?>">Manage statuses</a>
      <?php endif; ?>
    </div>
  </div>

  <section class="mw-dash-section">
    <h2 class="mw-dash-section-title">
      Ad hoc tasks
      <span class="mw-dash-section-count">(Count: <?php echo (int) $dashboard_counts['ad_hoc']; ?>)</span>
    </h2>
    <?php $this->load->view('my_works/_dashboard_lanes', array(
      'section_key' => 'ad_hoc',
      'dashboard_sections' => $dashboard_sections,
      'can_add' => !empty($can_add),
      'can_view_all' => !empty($can_view_all),
    )); ?>
  </section>

  <section class="mw-dash-section">
    <h2 class="mw-dash-section-title">
      Project Tasks
      <span class="mw-dash-section-count">(Count: <?php echo (int) $dashboard_counts['project']; ?>)</span>
    </h2>
    <?php $this->load->view('my_works/_dashboard_lanes', array(
      'section_key' => 'project',
      'dashboard_sections' => $dashboard_sections,
      'can_add' => !empty($can_add),
      'can_view_all' => !empty($can_view_all),
    )); ?>
  </section>

  <footer class="mw-dash-footer-note">
    <i class="bi bi-info-circle"></i>
    <strong>Need Discussion</strong> uses status <strong>Needs Discussion</strong>. <strong>Future Pipeline</strong> shows <strong>Postponed</strong> items and any work with a <strong>due date after today</strong>. Other columns use due date, or last updated date when no due date is set. Drag a task row between columns to reschedule or update status.
  </footer>

</div>

<script>
(function () {
  var form = document.getElementById('mwDashFilterForm');
  if (!form) { return; }
  var selects = form.querySelectorAll('select');
  for (var i = 0; i < selects.length; i++) {
    selects[i].addEventListener('change', function () {
      form.submit();
    });
  }
  var searchForm = document.querySelector('.mw-dash-search-form');
  if (searchForm) {
    var si = searchForm.querySelector('.mw-dash-search-input');
    if (si) {
      var timer = null;
      si.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
          searchForm.submit();
        }, 500);
      });
    }
  }
})();
</script>

<script>
(function () {
  if (window.mwDashLaneDnDInited) { return; }
  window.mwDashLaneDnDInited = true;

  var csrfName = <?php echo json_encode($this->security->get_csrf_token_name()); ?>;
  var csrfHash = <?php echo json_encode($this->security->get_csrf_hash()); ?>;
  var updateUrl = <?php echo json_encode(site_url('my-works/update-lane')); ?>;
  var dragRow = null;
  var dragFromBody = null;

  function laneCountEl(section, lane) {
    var laneEl = document.querySelector('.mw-dash-lane[data-section="' + section + '"][data-lane="' + lane + '"]');
    return laneEl ? laneEl.querySelector('.mw-dash-lane-count') : null;
  }

  function refreshLaneCount(section, lane) {
    var countEl = laneCountEl(section, lane);
    var body = document.querySelector('.mw-dash-lane-body[data-section="' + section + '"][data-lane="' + lane + '"]');
    if (!countEl || !body) { return; }
    var n = body.querySelectorAll('.mw-dash-task-row').length;
    countEl.textContent = String(n);
  }

  function ensureEmptyRow(body) {
    if (!body.querySelector('.mw-dash-task-row')) {
      var tr = document.createElement('tr');
      tr.className = 'mw-dash-lane-empty-row';
      tr.innerHTML = '<td colspan="4" class="mw-dash-lane-empty">No tasks</td>';
      body.appendChild(tr);
    }
  }

  function removeEmptyRow(body) {
    var empty = body.querySelector('.mw-dash-lane-empty-row');
    if (empty) { empty.remove(); }
  }

  document.querySelectorAll('.mw-dash-lane-body').forEach(function (body) {
    body.addEventListener('dragover', function (e) {
      if (!dragRow) { return; }
      e.preventDefault();
      body.classList.add('mw-dash-drop-target');
    });
    body.addEventListener('dragleave', function (e) {
      if (e.currentTarget === body && !body.contains(e.relatedTarget)) {
        body.classList.remove('mw-dash-drop-target');
      }
    });
    body.addEventListener('drop', function (e) {
      e.preventDefault();
      body.classList.remove('mw-dash-drop-target');
      if (!dragRow || !dragFromBody) { return; }

      var newLane = body.getAttribute('data-lane');
      var newSection = body.getAttribute('data-section');
      var oldLane = dragRow.getAttribute('data-lane');
      var oldSection = dragRow.getAttribute('data-section');
      var id = dragRow.getAttribute('data-id');

      if (!newLane || !newSection || !id || newLane === oldLane) {
        dragRow = null;
        dragFromBody = null;
        return;
      }
      if (newSection !== oldSection) {
        dragRow = null;
        dragFromBody = null;
        return;
      }

      removeEmptyRow(body);
      body.appendChild(dragRow);
      dragRow.setAttribute('data-lane', newLane);
      ensureEmptyRow(dragFromBody);
      refreshLaneCount(oldSection, oldLane);
      refreshLaneCount(newSection, newLane);

      var payload = new URLSearchParams();
      payload.append('id', id);
      payload.append('lane', newLane);
      payload.append(csrfName, csrfHash);

      fetch(updateUrl, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: payload.toString()
      }).then(function (r) { return r.json(); }).then(function (data) {
        if (!data || !data.ok || (data.computed_lane && data.computed_lane !== newLane)) {
          window.location.reload();
        }
      }).catch(function () {
        window.location.reload();
      });

      dragRow = null;
      dragFromBody = null;
    });
  });

  document.querySelectorAll('.mw-dash-task-row-draggable').forEach(function (row) {
    row.addEventListener('dragstart', function (e) {
      dragRow = row;
      dragFromBody = row.parentElement;
      row.classList.add('mw-dash-dragging');
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', row.getAttribute('data-id') || '');
      }
    });
    row.addEventListener('dragend', function () {
      row.classList.remove('mw-dash-dragging');
      dragRow = null;
      dragFromBody = null;
      document.querySelectorAll('.mw-dash-drop-target').forEach(function (el) {
        el.classList.remove('mw-dash-drop-target');
      });
    });
    var link = row.querySelector('.mw-dash-task-link');
    if (link) {
      link.addEventListener('click', function (e) {
        if (row.classList.contains('mw-dash-dragging')) {
          e.preventDefault();
        }
      });
    }
  });
})();
</script>

<?php $this->load->view('partials/footer'); ?>
