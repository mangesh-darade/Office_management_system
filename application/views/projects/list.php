<?php $this->load->view('partials/header', array('title' => 'Projects', 'extra_css' => array('assets/css/projects.css'))); ?>
<div class="container-fluid py-2 proj-list-compact">
<?php $this->load->view('partials/import_errors'); ?>
<?php
$actions_html = '';
if (function_exists('has_module_access') && (has_module_access('projects_matrix') || has_module_access('projects') || has_module_access('projects_list'))) {
    $actions_html .= '<a class="btn btn-outline-secondary btn-sm" title="Portfolio Matrix" href="' . site_url('projects/matrix') . '"><i class="bi bi-grid-3x3-gap me-1"></i><span class="d-none d-sm-inline">Matrix</span></a>';
}
if (function_exists('has_module_access') && (has_module_access('projects') || has_module_access('projects_list'))) {
    $actions_html .= '<a class="btn btn-outline-secondary btn-sm" title="Project Dashboard" href="' . site_url('projects/dashboard') . '"><i class="bi bi-speedometer2 me-1"></i><span class="d-none d-sm-inline">Dashboard</span></a>';
}
if (function_exists('has_module_access') && (has_module_access('projects_add') || has_module_access('projects'))) {
    $actions_html .= '<a class="btn btn-primary btn-sm" title="Create" href="' . site_url('projects/create') . '"><i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">New Project</span></a>';
}
if (function_exists('has_module_access') && (has_module_access('projects_import') || has_module_access('projects'))) {
    $actions_html .= '<a class="btn btn-outline-secondary btn-sm" title="Import CSV" href="' . site_url('projects/import') . '"><i class="bi bi-upload me-1"></i><span class="d-none d-sm-inline">Import</span></a>';
}
if (function_exists('has_module_access') && (has_module_access('projects_import') || has_module_access('projects_list') || has_module_access('projects'))) {
    $actions_html .= '<a class="btn btn-outline-secondary btn-sm" title="Export CSV" href="' . site_url('projects/export') . '"><i class="bi bi-download me-1"></i><span class="d-none d-sm-inline">Export</span></a>';
}
$this->load->view('partials/oms_page_head', array(
    'title' => 'Projects',
    'icon' => 'bi-kanban',
    'subtitle' => 'Manage all your projects',
    'actions_html' => $actions_html,
    'mb' => 'mb-2',
));
?>

<div id="projListSaveMsg" class="proj-list-save-msg d-none" role="status" aria-live="polite"></div>

<?php if (!empty($show_department_column) && !empty($departments)): ?>
<div class="proj-list-filters">
  <label class="proj-filter-field" for="projDeptFilter">
    <span class="proj-filter-label">Department</span>
    <select id="projDeptFilter" class="form-select form-select-sm">
      <option value="">All</option>
      <?php foreach ($departments as $dept): ?>
        <?php $dept_id = isset($dept->id) ? (int) $dept->id : 0; ?>
        <?php if ($dept_id < 1) { continue; } ?>
        <option value="<?php echo esc_view(isset($dept->dept_name) ? $dept->dept_name : ''); ?>"><?php echo esc_view(isset($dept->dept_name) ? $dept->dept_name : ''); ?></option>
      <?php endforeach; ?>
    </select>
  </label>
</div>
<?php endif; ?>

<div class="card border-0 proj-table-card oms-datatable-grid">
  <div class="card-body">
    <div class="table-responsive">
      <table id="projectsTable" class="table table-hover table-sm align-middle mb-0 w-100 proj-list-table" data-order-col="1" data-order-dir="asc">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <?php if (!empty($show_department_column)): ?>
            <th class="proj-dept-col">Department</th>
            <?php endif; ?>
            <?php if (!empty($show_assignee_column)): ?>
            <th>Assigned To</th>
            <?php endif; ?>
            <th>Status</th>
            <th class="text-end">Estimate</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($projects)) foreach ($projects as $p): ?>
            <?php
              $st = isset($p->status) ? strtolower((string) $p->status) : '';
              $st_class = 'bg-light text-dark border';
              if ($st === 'in_progress' || $st === 'active') {
                  $st_class = 'bg-primary';
              } elseif ($st === 'completed' || $st === 'done' || $st === 'closed') {
                  $st_class = 'bg-success';
              } elseif ($st === 'on_hold' || $st === 'hold') {
                  $st_class = 'bg-warning text-dark';
              } elseif ($st === 'cancelled' || $st === 'canceled') {
                  $st_class = 'bg-danger';
              } elseif ($st === 'planned') {
                  $st_class = 'bg-secondary';
              }
              $st_label = $st === '' ? '—' : ucfirst(str_replace('_', ' ', $st));
              $pid = (int) $p->id;
            ?>
            <tr>
              <td class="text-muted"><?php echo $pid; ?></td>
              <td>
                <a class="proj-name-link" href="<?php echo site_url('projects/' . $pid); ?>" title="<?php echo esc_view($p->name); ?>"><?php echo esc_view($p->name); ?></a>
              </td>
              <?php if (!empty($show_department_column)): ?>
              <?php
                $dept_label = (isset($p->department_name) && $p->department_name !== '') ? $p->department_name : '—';
                $dept_id = isset($p->department_id) ? (int) $p->department_id : 0;
              ?>
              <td data-search="<?php echo esc_view($dept_label); ?>" data-order="<?php echo esc_view($dept_label); ?>">
                <?php if (!empty($can_edit_list)): ?>
                <select class="form-select form-select-sm proj-field-select" data-field="department" data-url="<?php echo site_url('projects/' . $pid . '/list-field'); ?>" data-prev="<?php echo $dept_id; ?>" aria-label="Department">
                  <option value="">—</option>
                  <?php
                    $dept_selected = false;
                    foreach ($departments as $dept):
                      $opt_id = isset($dept->id) ? (int) $dept->id : 0;
                      if ($opt_id < 1) {
                          continue;
                      }
                      $selected = ($dept_id === $opt_id);
                      if ($selected) {
                          $dept_selected = true;
                      }
                  ?>
                  <option value="<?php echo $opt_id; ?>"<?php echo $selected ? ' selected' : ''; ?>><?php echo esc_view(isset($dept->dept_name) ? $dept->dept_name : ''); ?></option>
                  <?php endforeach; ?>
                  <?php if ($dept_id > 0 && !$dept_selected): ?>
                  <option value="<?php echo $dept_id; ?>" selected><?php echo esc_view($dept_label); ?></option>
                  <?php endif; ?>
                </select>
                <?php else: ?>
                <?php echo esc_view($dept_label); ?>
                <?php endif; ?>
              </td>
              <?php endif; ?>
              <?php if (!empty($show_assignee_column)): ?>
              <?php
                $assignee_label = (isset($p->assignee_label) && $p->assignee_label !== '') ? $p->assignee_label : '—';
                $manager_id = isset($p->manager_id) ? (int) $p->manager_id : 0;
              ?>
              <td data-search="<?php echo esc_view($assignee_label); ?>" data-order="<?php echo esc_view($assignee_label); ?>">
                <?php if (!empty($can_edit_list)): ?>
                <select class="form-select form-select-sm proj-field-select" data-field="assigned" data-url="<?php echo site_url('projects/' . $pid . '/list-field'); ?>" data-prev="<?php echo $manager_id; ?>" aria-label="Assigned to">
                  <option value="">—</option>
                  <?php
                    $user_selected = false;
                    if (!empty($assignable_users)) foreach ($assignable_users as $au):
                      $opt_id = isset($au->id) ? (int) $au->id : 0;
                      if ($opt_id < 1) {
                          continue;
                      }
                      $selected = ($manager_id === $opt_id);
                      if ($selected) {
                          $user_selected = true;
                      }
                  ?>
                  <option value="<?php echo $opt_id; ?>"<?php echo $selected ? ' selected' : ''; ?>><?php echo esc_view(isset($au->label) ? $au->label : ''); ?></option>
                  <?php endforeach; ?>
                  <?php if ($manager_id > 0 && !$user_selected): ?>
                  <option value="<?php echo $manager_id; ?>" selected><?php echo esc_view($assignee_label); ?></option>
                  <?php endif; ?>
                </select>
                <?php else: ?>
                <?php echo esc_view($assignee_label); ?>
                <?php endif; ?>
              </td>
              <?php endif; ?>
              <td><span class="badge proj-badge <?php echo esc_view($st_class); ?>"><?php echo esc_view($st_label); ?></span></td>
              <td class="text-end text-nowrap" data-order="<?php echo isset($p->estimate_hours) && $p->estimate_hours !== null && $p->estimate_hours !== '' ? (float) $p->estimate_hours : -1; ?>">
                <?php echo function_exists('estimate_hours_display') ? esc_view(estimate_hours_display(isset($p->estimate_hours) ? $p->estimate_hours : null)) : '—'; ?>
              </td>
              <td class="text-end text-nowrap">
                <?php if (function_exists('has_module_access') && (has_module_access('projects_delete') || has_module_access('projects'))): ?>
                <form id="proj-del-<?php echo $pid; ?>" method="post" action="<?php echo site_url('projects/' . $pid . '/delete'); ?>" class="d-none" onsubmit="return confirm('Delete this project?');">
                  <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                </form>
                <?php endif; ?>
                <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                  <a class="btn btn-outline-secondary" title="Dashboard" href="<?php echo site_url('projects/' . $pid . '/dashboard'); ?>"><i class="bi bi-speedometer2"></i></a>
                  <a class="btn btn-outline-secondary" title="View" href="<?php echo site_url('projects/' . $pid); ?>"><i class="bi bi-eye"></i></a>
                  <?php if (function_exists('has_module_access') && (has_module_access('projects_edit') || has_module_access('projects'))): ?>
                  <a class="btn btn-outline-primary" title="Edit" href="<?php echo site_url('projects/' . $pid . '/edit'); ?>"><i class="bi bi-pencil"></i></a>
                  <?php endif; ?>
                  <?php if (function_exists('has_module_access') && (has_module_access('projects_delete') || has_module_access('projects'))): ?>
                  <button type="submit" form="proj-del-<?php echo $pid; ?>" class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php if (!empty($can_edit_list)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var table = document.getElementById('projectsTable');
  var msg = document.getElementById('projListSaveMsg');
  if (!table || table.dataset.fieldSaveBound === '1') {
    return;
  }
  table.dataset.fieldSaveBound = '1';
  var csrfName = <?php echo json_encode($this->security->get_csrf_token_name()); ?>;
  var csrfHash = <?php echo json_encode($this->security->get_csrf_hash()); ?>;
  var hideTimer = null;

  function showMsg(text, ok) {
    if (!msg) {
      return;
    }
    msg.textContent = text;
    msg.classList.remove('d-none', 'is-ok', 'is-err');
    msg.classList.add(ok ? 'is-ok' : 'is-err');
    if (hideTimer) {
      clearTimeout(hideTimer);
    }
    hideTimer = setTimeout(function () {
      msg.classList.add('d-none');
    }, 2500);
  }

  table.addEventListener('change', function (ev) {
    var select = ev.target.closest('select.proj-field-select');
    if (!select || !table.contains(select)) {
      return;
    }
    var prev = select.getAttribute('data-prev') || '';
    var body = new URLSearchParams();
    body.set(csrfName, csrfHash);
    body.set('field', select.getAttribute('data-field') || '');
    body.set('value', select.value);
    select.disabled = true;
    fetch(select.getAttribute('data-url'), {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: body.toString()
    }).then(function (res) {
      return res.json().then(function (data) {
        return { ok: res.ok, data: data };
      });
    }).then(function (result) {
      if (!result.ok || !result.data || !result.data.ok) {
        select.value = prev;
        var err = (result.data && result.data.error) ? result.data.error : 'Could not save.';
        showMsg(err, false);
        return;
      }
      select.setAttribute('data-prev', select.value);
      var opt = select.options[select.selectedIndex];
      var label = opt ? opt.text : '—';
      var td = select.closest('td');
      if (td) {
        td.setAttribute('data-search', label);
        td.setAttribute('data-order', label);
      }
      if (window.DataTable && DataTable.isDataTable(table)) {
        var api = new DataTable(table);
        if (td) {
          api.cell(td).invalidate('dom');
        }
        api.draw(false);
      }
      showMsg(result.data.message || 'Saved.', true);
    }).catch(function () {
      select.value = prev;
      showMsg('Could not save.', false);
    }).finally(function () {
      select.disabled = false;
    });
  });
});
</script>
<?php endif; ?>
<?php $this->load->view('partials/footer'); ?>
