<?php 
$embed = (bool)$this->input->get('embed');
if (!$embed) {
  $this->load->view('partials/header', array(
    'title' => 'My Work Overview', 
    'extra_css' => array('assets/css/my-works.css'),
  ));
} ?>

<?php
  $view_mode = 'overview';
  $dashboard_sections = isset($dashboard_sections) ? $dashboard_sections : array();
  $dashboard_counts = isset($dashboard_counts) ? $dashboard_counts : array('ad_hoc' => 0, 'project' => 0, 'total' => 0);
  $filterProjectId = isset($filters['project_id']) ? (int) $filters['project_id'] : 0;
  $filterAssignee = isset($filters['created_for']) ? (int) $filters['created_for'] : 0;
  $filterSearch = isset($filters['q']) ? trim((string) $filters['q']) : '';
  $filters_active = ($filterAssignee > 0 || $filterProjectId > 0 || $filterSearch !== '');
  // When filters on: show empty carts after nonempty (do not hide).
  $hide_empty_lanes = false;
  $prioritize_nonempty = $filters_active;
  $section_order = array('ad_hoc', 'project');
  if ($prioritize_nonempty && function_exists('dashboard_sort_nonempty_first')) {
    $section_order = dashboard_sort_nonempty_first(
      $section_order,
      function ($key) use ($dashboard_counts) {
        return isset($dashboard_counts[$key]) ? (int) $dashboard_counts[$key] : 0;
      }
    );
  }
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

<div class="mw-dash-page<?php echo $embed ? ' mw-dash-page--embed' : ''; ?>"
     data-lane-update-url="<?php echo esc_view(site_url('my-works/update-lane'), ENT_QUOTES, 'UTF-8'); ?>">

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 mx-3 mt-2 mb-0" role="alert">
      <?php echo esc_view((string) $this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (!$embed): ?>
  <header class="mw-dash-header">
    <div class="mw-dash-header-left d-flex align-items-start gap-2">
      <?php $this->load->view('my_works/_back_btn', array(
        'back_url' => site_url('dashboard'),
        'back_title' => 'Back to Dashboard',
      )); ?>
      <div>
      <h1 class="mw-dash-title">My Work Overview</h1>
      <p class="mw-dash-subtitle">Organize, prioritize and track your work items efficiently.</p>
      </div>
    </div>
    <div class="mw-dash-header-center">
      <form method="get" action="<?php echo site_url('my-works'); ?>" class="mw-dash-search-form">
        <input type="hidden" name="view" value="overview">
        <?php foreach ($baseQuery as $qk => $qv): ?>
          <?php if ($qk === 'q' || $qk === 'view') { continue; } ?>
          <input type="hidden" name="<?php echo esc_view($qk); ?>" value="<?php echo esc_view((string) $qv, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>
        <div class="mw-dash-search-wrap">
          <i class="bi bi-search"></i>
          <input type="search" name="q" class="mw-dash-search-input" placeholder="Search tasks, projects, clients…" value="<?php echo esc_view(isset($filters['q']) ? (string) $filters['q'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </form>
    </div>
    <div class="mw-dash-header-right">
      <?php if (!empty($can_add)): ?>
        <a class="btn btn-primary btn-sm mw-dash-create-btn" href="<?php echo site_url('my-works/create'); ?>">
          <i class="bi bi-plus-lg me-1"></i>Create Task
        </a>
        <a class="btn btn-outline-primary btn-sm mw-dash-template-btn" href="<?php echo site_url('my-works/template-tasks'); ?>">
          <i class="bi bi-collection me-1"></i>Template Task
        </a>
      <?php endif; ?>
      <div class="mw-dash-date-pill">
        <i class="bi bi-calendar3"></i>
        <span><?php echo esc_view(my_works_dashboard_week_range_label(), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    </div>
  </header>
  <?php endif; ?>

  <?php if (!$embed) {
    $this->load->view('my_works/_dash_view_tabs', array('active_tab' => 'overview'));
  } ?>

  <div class="mw-dash-filters<?php echo $embed ? ' mw-dash-filters--embed' : ''; ?>">
    <form method="get" action="<?php echo site_url('my-works'); ?>" class="mw-dash-filter-form flex-nowrap mb-0" id="mwDashFilterForm">
      <input type="hidden" name="view" value="overview">
      <?php if (!empty($filters['q'])): ?>
        <input type="hidden" name="q" value="<?php echo esc_view((string) $filters['q'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>
      <label class="mw-dash-filter-label">
        Project:
        <select name="project_id" class="form-select form-select-sm mw-dash-filter-select">
          <option value="0">All Projects</option>
          <?php foreach ((array) $projects as $p): ?>
            <option value="<?php echo (int) $p->id; ?>" <?php echo $filterProjectId === (int) $p->id ? 'selected' : ''; ?>>
              <?php echo esc_view($p->name ? (string) $p->name : ('Project #' . (int) $p->id), ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
    </form>
    <?php if ($embed): ?>
      <form method="get" action="<?php echo site_url('my-works'); ?>" class="mw-dash-search-form mb-0 mw-dash-search-form--embed">
        <input type="hidden" name="view" value="overview">
        <?php foreach ($baseQuery as $qk => $qv): ?>
          <?php if ($qk === 'q' || $qk === 'view') { continue; } ?>
          <input type="hidden" name="<?php echo esc_view($qk); ?>" value="<?php echo esc_view((string) $qv, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>
        <div class="mw-dash-search-wrap mw-dash-search-wrap--compact">
          <i class="bi bi-search"></i>
          <input type="search" name="q" class="mw-dash-search-input" placeholder="Search..." value="<?php echo esc_view(isset($filters['q']) ? (string) $filters['q'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </form>
    <?php endif; ?>
    <div class="btn-group btn-group-sm mw-dash-layout-toggle" role="group" aria-label="Layout">
      <button type="button" class="btn btn-outline-secondary" data-mw-layout="grid" title="Grid" aria-pressed="false">
        <i class="bi bi-table"></i><span class="ms-1">Grid</span>
      </button>
      <button type="button" class="btn btn-primary" data-mw-layout="card" title="Card" aria-pressed="true">
        <i class="bi bi-grid-3x3-gap"></i><span class="ms-1">Card</span>
      </button>
    </div>
  </div>

  <?php foreach ($section_order as $section_key): ?>
  <?php
    $section_title = ($section_key === 'project') ? 'Project Tasks' : 'Ad hoc tasks';
    $section_count = isset($dashboard_counts[$section_key]) ? (int) $dashboard_counts[$section_key] : 0;
  ?>
  <section class="mw-dash-section">
    <h2 class="mw-dash-section-title">
      <?php echo esc_view($section_title); ?>
      <span class="mw-dash-section-count">(Count: <?php echo $section_count; ?>)</span>
    </h2>
    <?php $this->load->view('my_works/_dashboard_lanes', array(
      'section_key' => $section_key,
      'dashboard_sections' => $dashboard_sections,
      'can_add' => !empty($can_add),
      'can_view_all' => !empty($can_view_all),
      'hide_empty_lanes' => $hide_empty_lanes,
      'prioritize_nonempty' => !empty($prioritize_nonempty),
      'disable_lane_drag' => false,
      'hide_drag_column' => false,
      'assignee_names_map' => isset($assignee_names_map) ? $assignee_names_map : array(),
      'task_assignee_names_map' => isset($task_assignee_names_map) ? $task_assignee_names_map : array(),
    )); ?>
    <?php $this->load->view('my_works/_overview_grid', array(
      'section_key' => $section_key,
      'dashboard_sections' => $dashboard_sections,
      'users' => isset($users) ? $users : array(),
      'can_add' => !empty($can_add),
    )); ?>
  </section>
  <?php endforeach; ?>

  <?php if (!empty($prioritize_nonempty) && (int) $dashboard_counts['total'] < 1): ?>
  <div class="mw-dash-empty-filter alert alert-light border text-muted mx-3">
    No work items match the selected filters.
  </div>
  <?php endif; ?>

  <footer class="mw-dash-footer-note">
    <i class="bi bi-info-circle"></i>
    <strong>Yesterday</strong> shows work due yesterday, or with no due date when the task or its activity history was last updated yesterday. <strong>Need Discussion</strong> uses status <strong>Needs Discussion</strong>. <strong>Future Pipeline</strong> shows <strong>Postponed</strong> items and any work with a <strong>due date after today</strong>. <strong>Back Log</strong> and <strong>Future Pipeline</strong> never include <strong>Closed / Complete / Completed</strong> tasks. Other columns use due date, or last updated date when no due date is set. Drag a task row between columns to reschedule or update status.
  </footer>

</div>

<?php if (!empty($can_add)): ?>
<?php
  $grid_clients = isset($clients) ? $clients : array();
  $grid_projects = isset($projects) ? $projects : array();
  $grid_self_label = trim((string) $this->session->userdata('name'));
  if ($grid_self_label === '') {
      $grid_self_label = trim((string) $this->session->userdata('email'));
  }
?>
<template id="mwOverviewInlineRowTpl">
<tr class="mw-overview-grid-inline-row">
  <td>
    <select class="form-select form-select-sm mw-overview-inline-client" aria-label="Client">
      <option value="">Client</option>
      <?php foreach ($grid_clients as $cl): ?>
        <?php if (!is_object($cl) || !isset($cl->id)) { continue; } ?>
        <option value="<?php echo (int) $cl->id; ?>"><?php echo esc_view(isset($cl->company_name) ? (string) $cl->company_name : ''); ?></option>
      <?php endforeach; ?>
    </select>
  </td>
  <td>
    <select class="form-select form-select-sm mw-overview-inline-project" aria-label="Project">
      <option value="">Project</option>
      <?php foreach ($grid_projects as $pr): ?>
        <?php if (!is_object($pr) || !isset($pr->id)) { continue; } ?>
        <option value="<?php echo (int) $pr->id; ?>" data-client-id="<?php echo isset($pr->client_id) ? (int) $pr->client_id : 0; ?>"><?php echo esc_view(isset($pr->name) ? (string) $pr->name : ''); ?></option>
      <?php endforeach; ?>
    </select>
  </td>
  <td><input type="text" class="form-control form-control-sm mw-overview-inline-title" maxlength="255" placeholder="Task title" aria-label="Task title"></td>
  <td class="text-muted text-nowrap"><?php echo esc_view($grid_self_label !== '' ? $grid_self_label : '—'); ?></td>
  <td class="text-nowrap"><span class="project-dash-date">—</span></td>
  <td class="text-muted text-nowrap">—</td>
  <td class="text-end text-nowrap">—</td>
  <td class="text-nowrap">
    <div class="d-flex align-items-center gap-1 justify-content-end">
      <span class="text-muted small">Auto-saves</span>
      <button type="button" class="btn btn-outline-secondary btn-sm mw-overview-inline-cancel" title="Cancel" aria-label="Cancel"><i class="bi bi-x-lg"></i></button>
    </div>
  </td>
</tr>
</template>
<?php endif; ?>

<script>
(function () {
  var statusUrl = <?php echo json_encode(site_url('tasks/ajax_update_item_status')); ?>;
  var csrfName = <?php echo json_encode($this->security->get_csrf_token_name()); ?>;
  var csrfHash = <?php echo json_encode($this->security->get_csrf_hash()); ?>;
  <?php
    $mw_grid_status_js = array();
    if (function_exists('my_works_status_records')) {
        foreach (my_works_status_records() as $st_row) {
            if (!is_object($st_row) || !isset($st_row->code)) {
                continue;
            }
            $mw_grid_status_js[] = array(
                'code' => (string) $st_row->code,
                'name' => isset($st_row->name) ? (string) $st_row->name : (string) $st_row->code,
                'color' => isset($st_row->color) && (string) $st_row->color !== '' ? (string) $st_row->color : '#6c757d',
            );
        }
    }
    $task_grid_status_js = array();
    if (function_exists('my_works_overview_task_status_codes')) {
        foreach (my_works_overview_task_status_codes() as $task_code) {
            $task_grid_status_js[] = array(
                'code' => (string) $task_code,
                'name' => my_works_dashboard_task_status_label($task_code),
                'color' => my_works_dashboard_task_status_hex($task_code),
            );
        }
    }
  ?>
  window.mwOverviewGridCfg = {
    csrfName: csrfName,
    csrfHash: csrfHash,
    adHocUrl: <?php echo json_encode(site_url('my-works/ajax-grid-create')); ?>,
    taskUrl: <?php echo json_encode(site_url('tasks/ajax-dashboard-create-task')); ?>,
    mwStatuses: <?php echo json_encode($mw_grid_status_js); ?>,
    taskStatuses: <?php echo json_encode($task_grid_status_js); ?>
  };

  function initOverviewGrids(page) {
    if (!window.DataTable || !page) {
      return;
    }
    var tables = page.querySelectorAll('table.mw-overview-grid-table');
    var t;
    for (t = 0; t < tables.length; t++) {
      var tbl = tables[t];
      try {
        if (DataTable.isDataTable && DataTable.isDataTable(tbl)) {
          new DataTable(tbl).columns.adjust();
          continue;
        }
        new DataTable(tbl, {
          pageLength: 10,
          lengthMenu: [10, 25, 50, 100],
          ordering: true,
          order: [],
          searching: true,
          paging: true,
          info: false,
          responsive: false,
          autoWidth: false,
          columnDefs: [{ orderable: false, targets: -1 }],
          language: {
            search: 'Search:',
            lengthMenu: '_MENU_ entries per page',
            emptyTable: 'No items',
            zeroRecords: 'No matching items'
          }
        });
      } catch (err) {}
    }
  }

  if (!window.mwOverviewGridStatusBound) {
    window.mwOverviewGridStatusBound = true;
    document.addEventListener('change', function (e) {
      var sel = e.target.closest('.mw-overview-grid-wrap .project-dash-status-select');
      if (!sel) {
        return;
      }
      var chosen = sel.options[sel.selectedIndex];
      var color = chosen ? (chosen.getAttribute('data-color') || '') : '';
      if (color) {
        sel.style.color = color;
        sel.style.backgroundColor = color + '1a';
        sel.style.borderColor = color + '40';
        var row = sel.closest('tr');
        if (row) {
          row.style.background = color + '14';
        }
      }
      var body = new URLSearchParams();
      body.set('id', sel.getAttribute('data-item-id') || '');
      body.set('type', sel.getAttribute('data-item-type') || '');
      body.set('source', sel.getAttribute('data-item-source') || '');
      body.set('status', sel.value);
      body.set('actual_hours', sel.getAttribute('data-actual-hours') || '');
      body.set(csrfName, csrfHash);
      fetch(statusUrl, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
      }).then(function (res) { return res.json(); }).then(function (data) {
        if (!data || !data.success) {
          var prev = sel.getAttribute('data-prev-status');
          if (prev) {
            sel.value = prev;
          }
          window.alert((data && data.error) ? data.error : 'Failed to update status.');
          return;
        }
        sel.setAttribute('data-prev-status', sel.value);
      }).catch(function () {
        window.alert('Failed to update status.');
      });
    });
  }

  window.mwInitOverviewGrids = initOverviewGrids;

  function destroyOverviewGrid(tbl) {
    if (!tbl || !window.DataTable || !DataTable.isDataTable || !DataTable.isDataTable(tbl)) {
      return;
    }
    try {
      new DataTable(tbl).destroy();
    } catch (err) {}
    tbl.classList.remove('dataTable');
  }
  window.mwDestroyOverviewGrid = destroyOverviewGrid;

  if (!window.mwOverviewGridInlineBound) {
    window.mwOverviewGridInlineBound = true;

    function gridEsc(text) {
      return String(text == null ? '' : text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function removeEmptyGridRow(tbl) {
      var empty = tbl.querySelector('tbody td.dt-empty, tbody td.dataTables_empty');
      if (empty && empty.parentNode && empty.parentNode.parentNode) {
        empty.parentNode.parentNode.removeChild(empty.parentNode);
      }
    }

    function reinitOverviewGrid(table) {
      var pageEl = table ? table.closest('.mw-dash-page') : null;
      if (pageEl && window.mwInitOverviewGrids) {
        window.mwInitOverviewGrids(pageEl);
      }
    }

    function filterInlineProjects(projectSel, clientId) {
      if (!projectSel) {
        return;
      }
      var cid = parseInt(clientId, 10) || 0;
      var options = projectSel.querySelectorAll('option');
      var keep = '';
      var i;
      for (i = 0; i < options.length; i++) {
        var opt = options[i];
        if (!opt.value) {
          opt.hidden = false;
          continue;
        }
        var optClient = parseInt(opt.getAttribute('data-client-id') || '0', 10) || 0;
        var show = (cid < 1) || (optClient === cid) || (optClient === 0);
        opt.hidden = !show;
        if (show && opt.value === projectSel.value) {
          keep = opt.value;
        }
      }
      projectSel.value = keep;
    }

    function formatGridCreated(data) {
      if (data.created_label) {
        return data.created_label;
      }
      var raw = data.created_at ? String(data.created_at) : '';
      var m = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
      if (!m) {
        return '—';
      }
      var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      return m[3] + ' ' + months[parseInt(m[2], 10) - 1] + ' ' + m[1];
    }

    function buildSavedGridRow(data) {
      var cfg = window.mwOverviewGridCfg || {};
      var source = data.item_source === 'tasks' ? 'tasks' : 'my_works';
      var options = source === 'tasks' ? (cfg.taskStatuses || []) : (cfg.mwStatuses || []);
      var status = data.status || '';
      var color = data.status_color || '#6c757d';
      var label = data.status_label || status || 'New';
      var optsHtml = '';
      var matched = false;
      var i;
      for (i = 0; i < options.length; i++) {
        var opt = options[i];
        if (opt.code === status) {
          matched = true;
          if (opt.color) {
            color = data.status_color || opt.color;
          }
          if (!data.status_label && opt.name) {
            label = opt.name;
          }
        }
        optsHtml += '<option value="' + gridEsc(opt.code) + '" data-color="' + gridEsc(opt.color || color) + '"' + (opt.code === status ? ' selected' : '') + '>' + gridEsc(opt.name || opt.code) + '</option>';
      }
      if (!matched) {
        optsHtml = '<option value="' + gridEsc(status) + '" data-color="' + gridEsc(color) + '" selected>' + gridEsc(label) + '</option>' + optsHtml;
      }
      var itemType = data.item_type || (source === 'tasks' ? 'project_task' : 'my_work');
      var client = data.client_name ? data.client_name : '—';
      var project = data.project_name ? data.project_name : '—';
      var creator = data.created_by_name ? data.created_by_name : '—';
      var html = '<tr style="--pd-row-status-color:' + gridEsc(color) + ';background:' + gridEsc(color) + '14;">';
      html += '<td class="text-muted">' + gridEsc(client) + '</td>';
      html += '<td class="text-muted">' + gridEsc(project) + '</td>';
      html += '<td><a href="' + gridEsc(data.url || '#') + '" class="project-dash-task-title" title="' + gridEsc(data.title || '') + '">' + gridEsc(data.title || '') + '</a></td>';
      html += '<td class="text-muted text-nowrap">' + gridEsc(creator) + '</td>';
      html += '<td class="text-nowrap"><span class="project-dash-date">—</span></td>';
      html += '<td class="text-muted text-nowrap">' + gridEsc(formatGridCreated(data)) + '</td>';
      html += '<td class="text-end text-nowrap project-dash-est">—</td>';
      html += '<td><select class="form-select form-select-sm project-dash-status-select" data-item-id="' + gridEsc(data.id) + '" data-item-type="' + gridEsc(itemType) + '" data-item-source="' + gridEsc(source) + '" data-prev-status="' + gridEsc(status) + '" data-estimate-hours="" style="color:' + gridEsc(color) + ';background-color:' + gridEsc(color) + '1a;border-color:' + gridEsc(color) + '40;" aria-label="Status">' + optsHtml + '</select></td>';
      html += '</tr>';
      return html;
    }

    function bumpGridCount(table) {
      var section = table.closest('.mw-dash-section');
      if (section) {
        var countEl = section.querySelector('.mw-dash-section-count');
        if (countEl) {
          var match = String(countEl.textContent || '').match(/(\d+)/);
          var next = (match ? parseInt(match[1], 10) : 0) + 1;
          countEl.textContent = '(Count: ' + next + ')';
        }
      }
      var wrap = table.closest('.mw-overview-grid-wrap');
      if (!wrap) {
        return;
      }
      var stat = wrap.querySelector('.mw-overview-grid-stat-value');
      if (stat) {
        stat.textContent = String((parseInt(stat.textContent, 10) || 0) + 1);
      }
    }

    function setInlineDisabled(row, disabled) {
      var fields = row.querySelectorAll('input, select, button');
      var i;
      for (i = 0; i < fields.length; i++) {
        fields[i].disabled = disabled;
      }
    }

    function saveInlineGridRow(row, opts) {
      opts = opts || {};
      if (!row || row.getAttribute('data-saving') === '1' || row.getAttribute('data-cancel') === '1') {
        return;
      }
      var titleInput = row.querySelector('.mw-overview-inline-title');
      var title = titleInput ? String(titleInput.value || '').trim() : '';
      var table = row.closest('table');
      if (!title) {
        if (opts.quiet && row.parentNode) {
          row.parentNode.removeChild(row);
          reinitOverviewGrid(table);
        } else if (titleInput) {
          titleInput.focus();
        }
        return;
      }
      var section = row.getAttribute('data-section') || 'ad_hoc';
      var projectSel = row.querySelector('.mw-overview-inline-project');
      var projectId = projectSel ? (projectSel.value || '') : '';
      if (section === 'project' && (!projectId || projectId === '0')) {
        if (!opts.quiet) {
          window.alert('Select a project.');
          if (projectSel) {
            projectSel.focus();
          }
        }
        return;
      }
      var cfg = window.mwOverviewGridCfg || {};
      var clientSel = row.querySelector('.mw-overview-inline-client');
      var body = new URLSearchParams();
      body.set('title', title);
      body.set('client_id', clientSel ? (clientSel.value || '') : '');
      body.set('project_id', projectId);
      body.set(cfg.csrfName || '', cfg.csrfHash || '');
      var url = section === 'project' ? cfg.taskUrl : cfg.adHocUrl;
      row.setAttribute('data-saving', '1');
      setInlineDisabled(row, true);
      fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
      }).then(function (res) { return res.json(); }).then(function (data) {
        if (!data || !data.success || !data.data) {
          window.alert((data && data.error) ? data.error : 'Failed to add row.');
          row.removeAttribute('data-saving');
          setInlineDisabled(row, false);
          return;
        }
        var holder = document.createElement('tbody');
        holder.innerHTML = buildSavedGridRow(data.data);
        var saved = holder.firstElementChild;
        if (saved && row.parentNode) {
          row.parentNode.replaceChild(saved, row);
        }
        if (table) {
          bumpGridCount(table);
          reinitOverviewGrid(table);
        }
      }).catch(function () {
        window.alert('Failed to add row.');
        row.removeAttribute('data-saving');
        setInlineDisabled(row, false);
      });
    }

    document.addEventListener('click', function (e) {
      var addBtn = e.target.closest('.mw-overview-grid-add');
      if (addBtn) {
        var wrap = addBtn.closest('.mw-overview-grid-wrap');
        var tpl = document.getElementById('mwOverviewInlineRowTpl');
        if (!wrap || !tpl || !tpl.content) {
          return;
        }
        var table = wrap.querySelector('table.mw-overview-grid-table');
        if (!table) {
          return;
        }
        var existing = table.querySelector('tbody .mw-overview-grid-inline-row');
        if (existing) {
          var existingTitle = existing.querySelector('.mw-overview-inline-title');
          if (existingTitle) {
            existingTitle.focus();
          }
          return;
        }
        if (window.mwDestroyOverviewGrid) {
          window.mwDestroyOverviewGrid(table);
        }
        removeEmptyGridRow(table);
        var row = tpl.content.firstElementChild.cloneNode(true);
        row.setAttribute('data-section', addBtn.getAttribute('data-section') || 'ad_hoc');
        var filterProject = document.querySelector('#mwDashFilterForm select[name="project_id"]');
        var projectSel = row.querySelector('.mw-overview-inline-project');
        if (filterProject && projectSel && filterProject.value && filterProject.value !== '0') {
          projectSel.value = filterProject.value;
        }
        var tbody = table.querySelector('tbody');
        if (!tbody) {
          return;
        }
        tbody.insertBefore(row, tbody.firstChild);
        var titleInput = row.querySelector('.mw-overview-inline-title');
        if (titleInput) {
          titleInput.focus();
        }
        return;
      }
      var cancelBtn = e.target.closest('.mw-overview-inline-cancel');
      if (!cancelBtn) {
        return;
      }
      var cancelRow = cancelBtn.closest('tr.mw-overview-grid-inline-row');
      if (!cancelRow) {
        return;
      }
      var cancelTable = cancelRow.closest('table');
      cancelRow.setAttribute('data-cancel', '1');
      if (cancelRow.parentNode) {
        cancelRow.parentNode.removeChild(cancelRow);
      }
      reinitOverviewGrid(cancelTable);
    });

    document.addEventListener('mousedown', function (e) {
      var cancelBtn = e.target.closest('.mw-overview-inline-cancel');
      if (!cancelBtn) {
        return;
      }
      var row = cancelBtn.closest('tr.mw-overview-grid-inline-row');
      if (row) {
        row.setAttribute('data-cancel', '1');
      }
    });

    document.addEventListener('change', function (e) {
      var clientSel = e.target.closest('.mw-overview-inline-client');
      if (!clientSel) {
        return;
      }
      var row = clientSel.closest('tr');
      filterInlineProjects(row ? row.querySelector('.mw-overview-inline-project') : null, clientSel.value);
    });

    document.addEventListener('keydown', function (e) {
      var input = e.target.closest('.mw-overview-inline-title');
      if (!input) {
        return;
      }
      if (e.key === 'Enter') {
        e.preventDefault();
        saveInlineGridRow(input.closest('tr'));
      } else if (e.key === 'Escape') {
        e.preventDefault();
        var row = input.closest('tr');
        if (!row) {
          return;
        }
        row.setAttribute('data-cancel', '1');
        var table = row.closest('table');
        if (row.parentNode) {
          row.parentNode.removeChild(row);
        }
        reinitOverviewGrid(table);
      }
    });

    document.addEventListener('focusout', function (e) {
      var row = e.target.closest('tr.mw-overview-grid-inline-row');
      if (!row) {
        return;
      }
      setTimeout(function () {
        if (!row.parentNode) {
          return;
        }
        if (row.getAttribute('data-cancel') === '1' || row.getAttribute('data-saving') === '1') {
          return;
        }
        if (row.contains(document.activeElement)) {
          return;
        }
        saveInlineGridRow(row, { quiet: true });
      }, 120);
    });
  }

  var page = document.querySelector('.mw-dash-page');
  var toggle = document.querySelector('.mw-dash-layout-toggle');
  if (page && toggle) {
    var storageKey = 'mw_overview_layout';
    function applyLayout(layout) {
      if (layout !== 'grid' && layout !== 'card') {
        layout = 'card';
      }
      page.setAttribute('data-layout', layout);
      var buttons = toggle.querySelectorAll('[data-mw-layout]');
      var i;
      for (i = 0; i < buttons.length; i++) {
        var on = buttons[i].getAttribute('data-mw-layout') === layout;
        buttons[i].classList.toggle('btn-primary', on);
        buttons[i].classList.toggle('btn-outline-secondary', !on);
        buttons[i].setAttribute('aria-pressed', on ? 'true' : 'false');
      }
      try {
        localStorage.setItem(storageKey, layout);
      } catch (e) {}
      if (layout === 'grid') {
        window.requestAnimationFrame(function () {
          initOverviewGrids(page);
        });
      }
    }
    var saved = 'card';
    try {
      var raw = localStorage.getItem(storageKey);
      if (raw === 'grid' || raw === 'card') {
        saved = raw;
      }
    } catch (e2) {}
    applyLayout(saved);
    toggle.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-mw-layout]');
      if (!btn) {
        return;
      }
      applyLayout(btn.getAttribute('data-mw-layout'));
    });
  }
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

<?php if (!$embed): ?>
<script>
  window.mwDashLaneUpdateUrl = <?php echo json_encode(site_url('my-works/update-lane')); ?>;
</script>
<script src="<?php echo base_url('assets/js/my-works-lane-dnd.js'); ?>"></script>
<?php endif; ?>

<?php if (!$embed) {
  $this->load->view('partials/footer');
} ?>
