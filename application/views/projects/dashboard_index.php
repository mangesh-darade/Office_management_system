<?php 
$embed = (bool)$this->input->get('embed');
if (!$embed):
  $this->load->view('partials/header', array(
    'title' => 'Project Dashboard',
    'extra_css' => array('assets/css/project-dashboard.css'),
  )); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php endif; ?>

<div class="container-fluid project-dash-compact project-dash-index">
<?php
$this->load->helper('status_row');
$status_labels = array();
$status_colors = array();
$default_colors = array(
    'pending'     => '#6b7280',
    'in_progress' => '#0284c7',
    'completed'   => '#16a34a',
    'blocked'     => '#dc2626',
);
foreach ($status_rows as $sr) {
    $code = (string) $sr->code;
    $status_labels[$code] = (string) $sr->name;
    $color = isset($sr->color) ? trim((string) $sr->color) : '';
    if ($color === '' && isset($default_colors[$code])) {
        $color = $default_colors[$code];
    }
    if ($color !== '') {
        $status_colors[$code] = $color;
    }
}

$total_projects = isset($total_projects_scope) ? (int) $total_projects_scope : count($project_cards);
$total_tasks = 0;
$total_requirements = 0;
if (!isset($status_totals) || !is_array($status_totals)) {
  $status_totals = array();
  foreach ($status_rows as $sr) {
    $status_totals[(string) $sr->code] = 0;
  }
  foreach ($project_cards as $card) {
    $tasks = isset($card['tasks']) ? $card['tasks'] : array();
    foreach ($tasks as $task) {
      $st = trim((string) $task->status);
      if ($st === '') {
        $st = 'pending';
      }
      if (!isset($status_totals[$st])) {
        $status_totals[$st] = 0;
      }
      $status_totals[$st]++;
    }
  }
}
foreach ($status_rows as $sr) {
  $code = (string) $sr->code;
  if (!isset($status_totals[$code])) {
    $status_totals[$code] = 0;
  }
}
foreach ($project_cards as $card) {
  $tasks = isset($card['tasks']) ? $card['tasks'] : array();
  $requirements = isset($card['requirements']) ? $card['requirements'] : array();
  $total_tasks += count($tasks);
  $total_requirements += count($requirements);
}
$total_tasks_all = 0;
foreach ($status_totals as $cnt) {
  $total_tasks_all += (int) $cnt;
}

$filter_project_id = isset($filter_project_id) ? (int) $filter_project_id : 0;
$filter_projects = isset($filter_projects) ? $filter_projects : array();
$filter_status = isset($filter_status) ? (string) $filter_status : 'all';
$filter_user_id = isset($filter_user_id) ? (int) $filter_user_id : -1;
$filter_client_id = isset($filter_client_id) ? (int) $filter_client_id : 0;
$filter_department_id = isset($filter_department_id) ? (int) $filter_department_id : -1;
$complete_view_on = !empty($complete_view_on);

$pd_url = function ($overrides = array()) use (
  $embed,
  $filter_status,
  $filter_project_id,
  $filter_user_id,
  $filter_client_id,
  $filter_department_id,
  $complete_view_on
) {
  $params = array();
  $status = array_key_exists('status', $overrides) ? $overrides['status'] : $filter_status;
  $project_id = array_key_exists('project_id', $overrides) ? (int) $overrides['project_id'] : $filter_project_id;
  $user_id = array_key_exists('user_id', $overrides) ? (int) $overrides['user_id'] : $filter_user_id;
  $client_id = array_key_exists('client_id', $overrides) ? (int) $overrides['client_id'] : $filter_client_id;
  $department_id = array_key_exists('department_id', $overrides) ? (int) $overrides['department_id'] : $filter_department_id;

  if ($status !== '' && $status !== null && $status !== 'all') {
    $params['status'] = $status;
  }
  if ($project_id > 0) {
    $params['project_id'] = $project_id;
  }
  if ($user_id >= 0) {
    $params['user_id'] = $user_id;
  }
  if ($client_id > 0) {
    $params['client_id'] = $client_id;
  }
  if ($department_id >= 0) {
    $params['department_id'] = $department_id;
  }
  if (!empty($complete_view_on)) {
    $params['complete_view'] = 1;
  }
  if ($embed) {
    $params['embed'] = 1;
    $parent_tab = trim((string) get_instance()->input->get('parent_tab'));
    if ($parent_tab !== '') {
      $params['parent_tab'] = $parent_tab;
    }
  }
  return site_url('projects/dashboard' . (!empty($params) ? ('?' . http_build_query($params)) : ''));
};
$head_actions = '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('projects') . '"><i class="bi bi-kanban me-1"></i>All Projects</a>';
if (function_exists('has_module_access') && (has_module_access('projects_matrix') || has_module_access('projects') || has_module_access('projects_list'))) {
    $head_actions .= '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('projects/matrix') . '"><i class="bi bi-grid-3x3-gap me-1"></i>Matrix</a>';
}
if (!$embed) {
    $this->load->view('partials/oms_page_head', array(
        'title'        => 'Project Dashboard',
        'icon'         => 'bi bi-speedometer2',
        'subtitle'     => 'Tasks and requirements across all projects',
        'actions_html' => $head_actions,
        'mb'           => 'mb-0',
    ));
}

$format_task_date = function ($task) {
    $due = isset($task->due_date) ? trim((string) $task->due_date) : '';
    if ($due !== '' && $due !== '0000-00-00') {
        return date('M j', strtotime($due));
    }
    $start = isset($task->start_date) ? trim((string) $task->start_date) : '';
    if ($start !== '' && $start !== '0000-00-00') {
        return date('M j', strtotime($start));
    }
    $created = isset($task->created_at) ? trim((string) $task->created_at) : '';
    if ($created !== '') {
        return date('M j', strtotime($created));
    }
    return '—';
};
$assignee_label = function ($task) {
    if (isset($task->emp_name) && trim((string) $task->emp_name) !== '') {
        return trim((string) $task->emp_name);
    }
    if (isset($task->assignee_full_name) && trim((string) $task->assignee_full_name) !== '') {
        return trim((string) $task->assignee_full_name);
    }
    if (isset($task->assignee_name) && trim((string) $task->assignee_name) !== '') {
        return trim((string) $task->assignee_name);
    }
    if (isset($task->assignee_email) && trim((string) $task->assignee_email) !== '') {
        $parts = explode('@', (string) $task->assignee_email);
        return $parts[0];
    }
    return '—';
};
?>

<?php
$show_project_toolbar = true;
?>
<?php if ($show_project_toolbar): ?>
<div class="project-dash-toolbar">
  <form method="get" action="<?php echo site_url('projects/dashboard'); ?>" class="project-dash-filter-form" id="projectDashFilterForm">
    <?php if ($embed): ?>
    <input type="hidden" name="embed" value="1">
    <?php endif; ?>
    <?php if ($this->input->get('parent_tab')): ?>
    <input type="hidden" name="parent_tab" value="<?php echo esc_view($this->input->get('parent_tab'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if (!empty($complete_view_on)): ?>
    <input type="hidden" name="complete_view" value="1">
    <?php endif; ?>

    <?php if (isset($filter_departments) && !empty($filter_departments)): ?>
    <label class="project-dash-filter-label">
      <span class="project-dash-filter-label-text">Department</span>
      <select name="department_id" class="form-select form-select-sm project-dash-filter-select">
        <option value="all"<?php echo $filter_department_id < 0 ? ' selected' : ''; ?>>All Departments</option>
        <?php foreach ($filter_departments as $fd): ?>
          <option value="<?php echo (int) $fd->id; ?>"<?php echo $filter_department_id === (int) $fd->id ? ' selected' : ''; ?>><?php echo esc_view($fd->dept_name, ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php endif; ?>

    <?php
      $filter_client_id = isset($filter_client_id) ? (int) $filter_client_id : 0;
      $filter_clients = isset($filter_clients) && is_array($filter_clients) ? $filter_clients : array();
    ?>
    <?php if (!empty($filter_clients)): ?>
    <label class="project-dash-filter-label">
      <span class="project-dash-filter-label-text">Client</span>
      <select name="client_id" class="form-select form-select-sm project-dash-filter-select">
        <option value="0"<?php echo $filter_client_id < 1 ? ' selected' : ''; ?>>All Clients</option>
        <?php foreach ($filter_clients as $fc): ?>
          <?php
            $fc_id = (int) $fc->id;
            $fc_name = isset($fc->company_name) ? trim((string) $fc->company_name) : '';
            $fc_label = $fc_name !== '' ? $fc_name : ('Client #' . $fc_id);
          ?>
          <option value="<?php echo $fc_id; ?>"<?php echo $filter_client_id === $fc_id ? ' selected' : ''; ?>><?php echo esc_view($fc_label, ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php endif; ?>

    <?php if (!empty($filter_projects)): ?>
    <label class="project-dash-filter-label">
      <span class="project-dash-filter-label-text">Project</span>
      <select name="project_id" class="form-select form-select-sm project-dash-filter-select">
        <option value="0"<?php echo $filter_project_id < 1 ? ' selected' : ''; ?>>All Projects</option>
        <?php foreach ($filter_projects as $fp): ?>
          <?php
            $fp_id = (int) $fp->id;
            $fp_name = isset($fp->name) ? trim((string) $fp->name) : '';
            $fp_label = $fp_name !== '' ? $fp_name : ('Project #' . $fp_id);
          ?>
          <option value="<?php echo $fp_id; ?>"<?php echo $filter_project_id === $fp_id ? ' selected' : ''; ?>><?php echo esc_view($fp_label, ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php endif; ?>

    <label class="project-dash-filter-label">
      <span class="project-dash-filter-label-text">Status</span>
      <select name="status" class="form-select form-select-sm project-dash-filter-select">
        <option value="all"<?php echo $filter_status === 'all' ? ' selected' : ''; ?>>All Statuses</option>
        <?php foreach ($status_rows as $sr): ?>
          <option value="<?php echo esc_view($sr->code, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filter_status === $sr->code ? ' selected' : ''; ?>><?php echo esc_view($sr->name, ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <?php if (!empty($filter_users)): ?>
    <label class="project-dash-filter-label">
      <span class="project-dash-filter-label-text">Employee</span>
      <select name="user_id" class="form-select form-select-sm project-dash-filter-select">
        <option value="all"<?php echo $filter_user_id < 0 ? ' selected' : ''; ?>>All Employees</option>
        <?php foreach ($filter_users as $fu): ?>
          <?php
            $fu_id = (int) $fu->id;
            $fu_name = isset($fu->name) ? trim((string) $fu->name) : '';
            $fu_label = $fu_name !== '' ? $fu_name : ($fu_id > 0 ? 'User #' . $fu_id : 'Unassigned');
          ?>
          <option value="<?php echo $fu_id; ?>"<?php echo $filter_user_id === $fu_id ? ' selected' : ''; ?>><?php echo esc_view($fu_label, ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php endif; ?>

    <label class="project-dash-filter-label project-dash-filter-search">
      <span class="project-dash-filter-label-text">Search</span>
      <input type="search" id="projectDashSearch" value=""
             class="form-control form-control-sm project-dash-filter-select"
             placeholder="Project or task…"
             autocomplete="off">
    </label>

    <?php if (!$embed): ?>
    <div class="project-dash-complete-toggle" id="projectDashCompleteToggleWrap">
      <div class="form-check form-switch mb-0">
        <input class="form-check-input" type="checkbox" role="switch" id="projectDashCompleteToggle"<?php echo !empty($complete_view_on) ? ' checked' : ''; ?>>
        <label class="form-check-label" for="projectDashCompleteToggle">Show completed</label>
      </div>
    </div>
    <?php endif; ?>
  </form>
</div>
<?php endif; ?>

<div class="project-dash-summary">
  <a class="project-dash-stat<?php echo ($filter_status === 'all' || $filter_status === '') ? ' is-active' : ''; ?>"
     href="<?php echo esc_view($pd_url(array('status' => 'all')), ENT_QUOTES, 'UTF-8'); ?>"
     title="Show all projects">
    <div class="project-dash-stat-label">Projects</div>
    <div class="project-dash-stat-value"><?php echo (int) $total_projects; ?></div>
  </a>
  <a class="project-dash-stat<?php echo ($filter_status === 'all' || $filter_status === '') ? ' is-active' : ''; ?>"
     href="<?php echo esc_view($pd_url(array('status' => 'all')), ENT_QUOTES, 'UTF-8'); ?>"
     title="Show all tasks">
    <div class="project-dash-stat-label">Total Tasks</div>
    <div class="project-dash-stat-value"><?php echo (int) $total_tasks_all; ?></div>
  </a>
  <div class="project-dash-stat" title="Requirements">
    <div class="project-dash-stat-label">Requirements</div>
    <div class="project-dash-stat-value"><?php echo (int) $total_requirements; ?></div>
  </div>
  <?php foreach ($status_rows as $sr): ?>
    <?php
      $code = (string) $sr->code;
      $label = isset($status_labels[$code]) ? $status_labels[$code] : $code;
      $count = isset($status_totals[$code]) ? (int) $status_totals[$code] : 0;
      $color = isset($status_colors[$code]) ? $status_colors[$code] : '#374151';
      $is_active = ($filter_status === $code);
    ?>
    <a class="project-dash-stat<?php echo $is_active ? ' is-active' : ''; ?>"
       href="<?php echo esc_view($pd_url(array('status' => $code)), ENT_QUOTES, 'UTF-8'); ?>"
       title="Show <?php echo esc_view($label, ENT_QUOTES, 'UTF-8'); ?> tasks"
       style="border-top-color:<?php echo esc_view($color, ENT_QUOTES, 'UTF-8'); ?>;">
      <div class="project-dash-stat-label"><?php echo esc_view($label); ?></div>
      <div class="project-dash-stat-value" style="color:<?php echo esc_view($color, ENT_QUOTES, 'UTF-8'); ?>;">
        <?php echo $count; ?>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<?php if (empty($project_cards)): ?>
  <div class="card project-dash-card">
    <div class="card-body text-center py-3 text-muted small">No projects available.</div>
  </div>
<?php else: ?>
  <div class="row project-dash-grid align-items-start">
    <?php foreach ($project_cards as $card): ?>
      <?php
        $p = $card['project'];
        $tasks = isset($card['tasks']) ? $card['tasks'] : array();
        $requirements = isset($card['requirements']) ? $card['requirements'] : array();
        $row_items = array();
        foreach ($tasks as $task) {
            $row_items[] = array('kind' => 'task', 'row' => $task);
        }
        foreach ($requirements as $req) {
            $row_items[] = array('kind' => 'requirement', 'row' => $req);
        }
        $item_count = count($row_items);
      ?>
      <div class="col-12 col-md-6 col-lg-4 project-dash-grid-col"
           data-project-search="<?php echo esc_view(strtolower(trim(
             (isset($p->name) ? (string) $p->name : '') . ' ' .
             (isset($p->code) ? (string) $p->code : '') . ' ' .
             (isset($p->department_name) ? (string) $p->department_name : '')
           )), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="card project-dash-card">
          <div class="card-body">
            <div class="project-dash-head">
              <div class="project-dash-head-main">
                <a href="<?php echo site_url('projects/' . (int) $p->id); ?>" class="text-decoration-none project-dash-project-name" title="<?php echo esc_view((string) $p->name, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo esc_view((string) $p->name); ?>
                </a>
                <?php if (!empty($p->department_name)): ?>
                  <div class="text-muted small mt-1" style="font-size: 0.6875rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; background: #f3f4f6; padding: 2px 6px; border-radius: 4px; color: #4b5563 !important;">
                    <i class="bi bi-building"></i>
                    <span><?php echo esc_view($p->department_name); ?></span>
                  </div>
                <?php endif; ?>
              </div>
              <span class="project-dash-count<?php echo $item_count < 1 ? ' is-zero' : ''; ?>" title="<?php echo (int) $item_count; ?> item(s)">
                <?php echo (int) $item_count; ?>
              </span>
            </div>

            <div class="project-dash-task-list">
              <?php if (empty($row_items)): ?>
                <div class="project-dash-empty">
                  <i class="bi bi-inbox"></i>
                  <span>No tasks or requirements</span>
                </div>
              <?php else: ?>
                <table class="table table-sm project-dash-task-table mb-0">
                  <thead>
                    <tr>
                      <th>Item</th>
                      <th>Assignee</th>
                      <th style="width: 55px;">Date</th>
                      <th class="text-end" style="width: 52px;">Est.hr</th>
                      <th style="width: 110px;">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($row_items as $entry): ?>
                      <?php
                        $kind = isset($entry['kind']) ? (string) $entry['kind'] : 'task';
                        $row = isset($entry['row']) ? $entry['row'] : null;
                        if (!$row) {
                            continue;
                        }
                        $task_status = trim((string) $row->status);
                        if ($task_status === '') {
                            $task_status = 'pending';
                        }
                        $task_label = isset($status_labels[$task_status]) ? $status_labels[$task_status] : ucfirst(str_replace('_', ' ', $task_status));
                        $task_date = $format_task_date($row);
                        $badge_color = isset($status_colors[$task_status]) ? $status_colors[$task_status] : '#6b7280';
                        $assignee = $assignee_label($row);
                        $item_title = isset($row->title) ? (string) $row->title : '';
                        if (!function_exists('estimate_hours_row')) {
                            $this->load->helper('estimate_hours');
                        }
                        $est_label = ($kind === 'task')
                          ? estimate_hours_row(isset($row->estimate_hours) ? $row->estimate_hours : null)
                          : '—';
                        if ($kind === 'requirement') {
                            $item_url = site_url('requirements/view/' . (int) $row->id);
                            $type_prefix = 'Req: ';
                        } else {
                            $item_url = site_url('tasks/' . (int) $row->id);
                            $type_prefix = '';
                        }
                      ?>
                      <tr class="project-dash-task-row project-dash-task-row-<?php echo esc_view($task_status); ?>"
                          data-item-search="<?php echo esc_view(strtolower($item_title), ENT_QUOTES, 'UTF-8'); ?>"
                          style="<?php echo esc_view(status_row_css_var_style($badge_color), ENT_QUOTES, 'UTF-8'); ?>">
                        <td>
                          <a href="<?php echo $item_url; ?>" class="project-dash-task-title" title="<?php echo esc_view($type_prefix . $item_title, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if ($type_prefix !== ''): ?><span class="text-muted small"><?php echo esc_view($type_prefix); ?></span><?php endif; ?>
                            <?php echo esc_view($item_title); ?>
                          </a>
                        </td>
                        <td class="project-dash-assignee" title="<?php echo esc_view($assignee, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($assignee); ?></td>
                        <td class="project-dash-date"><?php echo esc_view($task_date); ?></td>
                        <td class="text-end text-nowrap project-dash-est" title="Estimate (hrs)"><?php echo esc_view($est_label); ?></td>
                        <td>
                          <select class="form-select form-select-sm project-dash-status-select" 
                                  data-id="<?php echo (int) $row->id; ?>" 
                                  data-type="<?php echo $kind === 'requirement' ? 'requirement' : 'project_task'; ?>"
                                  data-estimate-hours="<?php echo esc_view(($kind === 'task' && isset($row->estimate_hours) && $row->estimate_hours !== null && $row->estimate_hours !== '') ? (string) $row->estimate_hours : '', ENT_QUOTES, 'UTF-8'); ?>"
                                  data-prev-status="<?php echo esc_view($task_status, ENT_QUOTES, 'UTF-8'); ?>"
                                  style="color:<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>;background-color:<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>1a;border:1px solid <?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>40;font-weight:600;font-size:0.75rem;border-radius:4px;padding:2px 20px 2px 6px; cursor:pointer;"
                                  title="<?php echo esc_view($task_label, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php foreach ($status_rows as $srow): ?>
                              <?php
                                $opt_color = isset($status_colors[$srow->code]) ? $status_colors[$srow->code] : $srow->color;
                              ?>
                              <option value="<?php echo esc_view($srow->code, ENT_QUOTES, 'UTF-8'); ?>" 
                                      data-color="<?php echo esc_view($opt_color, ENT_QUOTES, 'UTF-8'); ?>"
                                      style="color: <?php echo esc_view($opt_color, ENT_QUOTES, 'UTF-8'); ?>; font-weight: 600;"
                                      <?php echo $task_status === $srow->code ? 'selected' : ''; ?>>
                                <?php echo esc_view($srow->name, ENT_QUOTES, 'UTF-8'); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
</div>
<?php if ($show_project_toolbar): ?>
<script>
(function () {
  var form = document.getElementById('projectDashFilterForm');
  if (!form) {
    return;
  }
  var selects = form.querySelectorAll('select.project-dash-filter-select');
  selects.forEach(function (select) {
    select.addEventListener('change', function () {
      form.submit();
    });
  });

  var searchInput = document.getElementById('projectDashSearch');
  var grid = document.querySelector('.project-dash-grid');
  function applyProjectSearch() {
    if (!searchInput || !grid) {
      return;
    }
    var q = String(searchInput.value || '').trim().toLowerCase();
    grid.querySelectorAll('.project-dash-grid-col').forEach(function (col) {
      var projectHay = String(col.getAttribute('data-project-search') || '');
      var projectMatch = (q === '' || projectHay.indexOf(q) >= 0);
      var rows = col.querySelectorAll('tr[data-item-search]');
      var anyItemMatch = false;

      rows.forEach(function (row) {
        var itemHay = String(row.getAttribute('data-item-search') || '');
        var itemMatch;
        if (q === '' || projectMatch) {
          itemMatch = true;
        } else {
          itemMatch = itemHay.indexOf(q) >= 0;
        }
        row.style.display = itemMatch ? '' : 'none';
        if (itemMatch && q !== '' && !projectMatch) {
          anyItemMatch = true;
        }
      });

      var show = (q === '' || projectMatch || anyItemMatch);
      col.style.display = show ? '' : 'none';
    });
  }
  if (searchInput) {
    searchInput.addEventListener('input', applyProjectSearch);
    searchInput.addEventListener('search', applyProjectSearch);
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
      }
    });
  }

  var completeToggle = document.getElementById('projectDashCompleteToggle');
  if (completeToggle) {
    completeToggle.addEventListener('change', function () {
      var params = new URLSearchParams(window.location.search);
      if (this.checked) {
        params.set('complete_view', '1');
      } else {
        params.delete('complete_view');
      }
      window.location.search = params.toString();
    });
  }

  if (window.jQuery) {
    window.jQuery(document).on('change', '.project-dash-status-select', function() {
      var $select = window.jQuery(this);
      var newStatus = $select.val();
      var itemId = $select.data('id');
      var itemType = $select.data('type');
      var $selectedOption = $select.find('option:selected');
      var newColor = $selectedOption.data('color');

      $select.css({
        'color': newColor,
        'background-color': newColor + '1a',
        'border-color': newColor + '40'
      });
      var $row = $select.closest('tr');
      if ($row.length) {
        $row.removeClass(function (index, className) {
            return (className.match(/(^|\s)project-dash-task-row-\S+/g) || []).join(' ');
        });
        $row.addClass('project-dash-task-row-' + newStatus);
        $row[0].style.setProperty('--pd-row-status-color', newColor);
      }

      window.jQuery.ajax({
        url: '<?php echo site_url("tasks/ajax_update_item_status"); ?>',
        type: 'POST',
        data: {
          id: itemId,
          type: itemType,
          status: newStatus,
          actual_hours: $select.attr('data-actual-hours') || '',
          <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
        },
        dataType: 'json',
        success: function(res) {
          if (!res || !res.success) {
            if (res && res.need_actual_hours && window.omsActualHours) {
              window.omsActualHours.prompt({
                estimate: $select.attr('data-estimate-hours') || null
              }).then(function (hours) {
                if (hours === null) {
                  var prev = $select.attr('data-prev-status');
                  if (prev) { $select.val(prev); }
                  return;
                }
                $select.attr('data-actual-hours', String(hours));
                $select.trigger('change');
              });
              return;
            }
            alert((res && res.error) ? res.error : 'Failed to update status.');
            var prev = $select.attr('data-prev-status');
            if (prev) {
              $select.val(prev);
            }
          } else {
            $select.attr('data-prev-status', newStatus);
            $select.removeAttr('data-actual-hours');
          }
        },
        error: function() {
          alert('An error occurred. Please try again.');
        }
      });
    });
  }
})();
</script>
<?php endif; ?>
<?php if (!$embed) {
  $this->load->view('partials/footer');
} else {
  // Embed panes: ensure actual-hours script is present if parent shell missed it.
  echo '<script src="' . base_url('assets/js/actual-hours-complete.js') . '"></script>';
} ?>
