<?php
$page_title = isset($page_title) ? (string) $page_title : 'Team Dashboard';
$group_mode = isset($group_mode) ? (string) $group_mode : 'employee';
$group_cards = isset($group_cards) ? $group_cards : array();
$is_admin_view = !empty($is_admin_view);
$type_counts = isset($type_counts) ? $type_counts : array(
    'total'        => isset($task_total) ? (int) $task_total : 0,
    'project_task' => 0,
    'my_work'      => 0,
    'ad_hoc'       => 0,
    'requirement'  => 0,
);
$my_works_status_rows = isset($my_works_status_rows) ? $my_works_status_rows : array();
$requirement_status_rows = isset($requirement_status_rows) ? $requirement_status_rows : array();
$team_dash_status_options = isset($team_dash_status_options) ? $team_dash_status_options : array(
    'task'        => $status_rows,
    'my_work'     => $my_works_status_rows,
    'requirement' => $requirement_status_rows,
);
$complete_view_on = !empty($complete_view_on);
$empty_message = 'No tasks, requirements, Second Brain items, or ad hoc items found for your team.';

$embed = isset($embed) ? (bool)$embed : (isset($_GET['embed']) ? (bool)$_GET['embed'] : (bool)$this->input->get('embed'));
if (!$embed):
  $this->load->view('partials/header', array(
    'title' => $page_title,
    'extra_css' => array('assets/css/project-dashboard.css'),
    'embed' => $embed,
  )); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php endif; ?>

<style>
.project-dash-section {
    border-bottom: 1px dashed #cbd5e1;
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
}
.project-dash-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}
.project-dash-section-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #64748b;
    margin-bottom: 0.35rem;
    padding-left: 0.35rem;
}
#unifiedEmployeeTasksTable {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}
#unifiedEmployeeTasksTable thead th {
    background-color: #f8fafc;
    color: #475569;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    border-bottom: 1px solid #e2e8f0;
    padding: 6px 10px;
}
#unifiedEmployeeTasksTable tbody td {
    padding: 6px 10px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    font-size: 0.8125rem;
}
#unifiedEmployeeTasksTable tbody tr:last-child td {
    border-bottom: none;
}
#unifiedEmployeeTasksTable tbody tr:hover {
    background-color: #f8fafc !important;
}
.employee-dash-badge {
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.68rem !important;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 2px 6px;
}
.team-dash-focus-table-wrap {
    padding: 0.5rem !important;
    margin-top: 0.35rem !important;
    border-radius: 8px !important;
}
</style>

<?php
$filter_user_id = isset($filter_user_id) ? (int) $filter_user_id : -1;
$filter_users = isset($filter_users) ? $filter_users : array();
$focus_raw = $this->input->get('focus');
$is_user_focused = ($filter_user_id > 0 && in_array((string) $focus_raw, array('1', 'true'), true));
?>

<div class="container-fluid project-dash-compact team-dash-index<?php echo $is_user_focused ? ' is-user-filtered' : ''; ?>">
<?php
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

$total_groups = count($group_cards);
$total_items = isset($type_counts['total']) ? (int) $type_counts['total'] : 0;

$subtitle = isset($subtitle) ? (string) $subtitle : 'Employee tasks, requirements, Second Brain, and ad hoc items';

$head_actions = '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('tasks/board') . '"><i class="bi bi-kanban me-1"></i>Task Board</a>';
$head_actions .= '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('tasks') . '"><i class="bi bi-list me-1"></i>All Tasks</a>';
if (function_exists('has_module_access') && (has_module_access('my_works') || has_module_access('my_works_list'))) {
    $head_actions .= '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('my-works?view=overview') . '"><i class="oms-icon-brain me-1" aria-hidden="true"></i>Second Brain</a>';
}
if (function_exists('has_module_access') && (has_module_access('projects') || has_module_access('projects_list'))) {
    $head_actions .= '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('projects/dashboard') . '"><i class="bi bi-speedometer2 me-1"></i>Project Dashboard</a>';
}

if (!$embed) {
    $this->load->view('partials/oms_page_head', array(
        'title'        => $page_title,
        'icon'         => 'bi-people',
        'subtitle'     => $subtitle,
        'actions_html' => $head_actions,
        'mb'           => 'mb-0',
    ));
}
?>

<?php if (!$is_user_focused): ?>
  <?php if (!$embed || !empty($filter_users)): ?>
  <div class="project-dash-filters<?php echo !$embed ? ' project-dash-filters--with-toggle' : ''; ?>">
    <?php if (!$embed): ?>
    <div class="project-dash-complete-toggle" id="teamDashCompleteToggleWrap">
      <div class="form-check form-switch mb-0">
        <input class="form-check-input" type="checkbox" role="switch" id="teamDashCompleteToggle"<?php echo $complete_view_on ? ' checked' : ''; ?>>
        <label class="form-check-label" for="teamDashCompleteToggle">Completed</label>
      </div>
    </div>
    <?php endif; ?>
  <?php if (!empty($filter_users)): ?>
  <div class="project-dash-filter-fields">
    <form method="get" action="<?php echo site_url('tasks/my-dashboard'); ?>" class="project-dash-filter-form" id="teamDashFilterForm">
      <?php if ($embed): ?>
      <input type="hidden" name="embed" value="1">
      <?php endif; ?>
      <?php if (!empty($complete_view_on)): ?>
      <input type="hidden" name="complete_view" value="1">
      <?php endif; ?>
      <?php if ($this->input->get('parent_tab')): ?>
      <input type="hidden" name="parent_tab" value="<?php echo esc_view($this->input->get('parent_tab'), ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>
      <?php if (!empty($filter_projects)): ?>
      <label class="project-dash-filter-label">
        <span class="project-dash-filter-label-text">Project</span>
        <select name="project_id" class="form-select form-select-sm project-dash-filter-select">
          <option value="all"<?php echo $filter_project_id < 0 ? ' selected' : ''; ?>>All Projects</option>
          <?php foreach ($filter_projects as $fp): ?>
            <option value="<?php echo (int)$fp->id; ?>"<?php echo $filter_project_id === (int)$fp->id ? ' selected' : ''; ?>><?php echo esc_view($fp->name, ENT_QUOTES, 'UTF-8'); ?></option>
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

      <label class="project-dash-filter-label project-dash-filter-search">
        <span class="project-dash-filter-label-text">Search</span>
        <input type="search" id="teamDashSearch" value=""
               class="form-control form-control-sm project-dash-filter-select"
               placeholder="Employee or task…"
               autocomplete="off"
               aria-label="Search employees or tasks">
      </label>
    </form>
  </div>
  <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="project-dash-summary">
    <div class="project-dash-stat">
      <div class="project-dash-stat-label">Employees</div>
      <div class="project-dash-stat-value"><?php echo (int) $total_groups; ?></div>
    </div>
    <div class="project-dash-stat">
      <div class="project-dash-stat-label">Total Items</div>
      <div class="project-dash-stat-value"><?php echo (int) $total_items; ?></div>
    </div>
    <div class="project-dash-stat">
      <div class="project-dash-stat-label">Project Tasks</div>
      <div class="project-dash-stat-value"><?php echo (int) (isset($type_counts['project_task']) ? $type_counts['project_task'] : 0); ?></div>
    </div>
    <div class="project-dash-stat">
      <div class="project-dash-stat-label">Requirements</div>
      <div class="project-dash-stat-value"><?php echo (int) (isset($type_counts['requirement']) ? $type_counts['requirement'] : 0); ?></div>
    </div>
    <div class="project-dash-stat">
      <div class="project-dash-stat-label">Second Brain</div>
      <div class="project-dash-stat-value"><?php echo (int) (isset($type_counts['my_work']) ? $type_counts['my_work'] : 0); ?></div>
    </div>
    <div class="project-dash-stat">
      <div class="project-dash-stat-label">Ad Hoc</div>
      <div class="project-dash-stat-value"><?php echo (int) (isset($type_counts['ad_hoc']) ? $type_counts['ad_hoc'] : 0); ?></div>
    </div>
  </div>
<?php else: ?>
  <header class="mw-focus-screen-head">
    <?php 
      $backParams = $_GET;
      unset($backParams['user_id'], $backParams['focus']);
      $parentTab = isset($backParams['parent_tab']) ? trim((string) $backParams['parent_tab']) : '';
      if ($embed) {
          $backParams['embed'] = 1;
          $backUrl = site_url('tasks/my-dashboard') . (empty($backParams) ? '' : '?' . http_build_query($backParams));
      } elseif ($parentTab !== '') {
          unset($backParams['parent_tab']);
          $backParams['tab'] = $parentTab;
          $backUrl = site_url('my-works') . '?' . http_build_query($backParams);
      } else {
          $backUrl = site_url('tasks/my-dashboard') . (empty($backParams) ? '' : '?' . http_build_query($backParams));
      }
    ?>
    <a class="btn btn-outline-secondary btn-sm mw-focus-back-btn" href="<?php echo esc_view($backUrl); ?>" title="Back to Team Dashboard">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <?php 
      // Use display_name from controller (set based on filtered user_id), fallback to first card entity name
      $display_name_header = isset($display_name) && !empty($display_name) ? $display_name : '';
      if (empty($display_name_header) && !empty($group_cards[0]['entity']->name)) {
          $display_name_header = $group_cards[0]['entity']->name;
      }
      if (empty($display_name_header)) $display_name_header = 'Employee';
      $focus_item_count = isset($task_total) ? (int) $task_total : 0;
      if (!empty($group_cards[0]['items']) && is_array($group_cards[0]['items'])) {
          $focus_item_count = count($group_cards[0]['items']);
      }
    ?>
    <h1 class="mw-focus-screen-title"><?php echo esc_view($display_name_header); ?></h1>
    <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2 align-self-center"><?php echo (int) $focus_item_count; ?> item<?php echo (int) $focus_item_count === 1 ? '' : 's'; ?></span>
    <?php if (!$embed): ?>
    <div class="project-dash-complete-toggle ms-auto" id="teamDashCompleteToggleWrap">
      <div class="form-check form-switch mb-0">
        <input class="form-check-input" type="checkbox" role="switch" id="teamDashCompleteToggle"<?php echo $complete_view_on ? ' checked' : ''; ?>>
        <label class="form-check-label" for="teamDashCompleteToggle">Completed</label>
      </div>
    </div>
    <?php endif; ?>
  </header>
<?php endif; ?>


<?php if (empty($group_cards)): ?>
  <div class="card project-dash-card">
    <div class="card-body text-center py-4 text-muted">
      <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;"></i>
      <?php echo esc_view($empty_message); ?>
    </div>
  </div>
<?php else: ?>
  <?php
  $render_team_dash_items_table = function (array $section_items, array $options = array()) use ($team_dash_status_options, $complete_view_on) {
      if (empty($section_items)) {
          echo '<div class="project-dash-section-empty"><span>No items</span></div>';
          return;
      }

      $table_id = isset($options['table_id']) ? (string) $options['table_id'] : '';
      $table_class = isset($options['table_class']) ? (string) $options['table_class'] : 'table table-sm project-dash-task-table mb-0';
      $is_full = !empty($options['full_width']);
      $show_act = !empty($complete_view_on);

      echo '<table' . ($table_id !== '' ? ' id="' . esc_view($table_id, ENT_QUOTES, 'UTF-8') . '"' : '') . ' class="' . esc_view($table_class, ENT_QUOTES, 'UTF-8') . '">';
      echo '<thead><tr>';
      echo '<th>Task</th>';
      echo '<th>Date</th>';
      echo '<th class="text-end">Est</th>';
      if ($show_act) {
          echo '<th class="text-end">Act</th>';
      }
      echo '<th>Status</th>';
      echo '</tr></thead><tbody>';

      if (!function_exists('estimate_hours_row')) {
          $this->load->helper('estimate_hours');
      }

      foreach ($section_items as $item) {
          $item_status = isset($item['status']) ? (string) $item['status'] : 'pending';
          $item_label = isset($item['status_label']) ? (string) $item['status_label'] : ucfirst(str_replace('_', ' ', $item_status));
          $item_date = isset($item['date']) ? (string) $item['date'] : '—';
          if ($item_date !== '' && $item_date !== '—') {
              $parsed = strtotime($item_date);
              if ($parsed) {
                  $item_date = date('d M', $parsed);
              }
          }
          $badge_color = isset($item['status_color']) ? (string) $item['status_color'] : '#6b7280';
          $item_title = isset($item['title']) ? (string) $item['title'] : '';
          $item_url = isset($item['url']) ? (string) $item['url'] : '#';
          $item_detail = isset($item['detail']) ? trim((string) $item['detail']) : '';
          $item_est = estimate_hours_row(isset($item['estimate_hours']) ? $item['estimate_hours'] : null);
          $item_act = $show_act
              ? estimate_hours_row(isset($item['actual_hours']) ? $item['actual_hours'] : null)
              : '';
          $status_scope = isset($item['status_scope']) ? (string) $item['status_scope'] : 'task';
          $item_status_options = isset($team_dash_status_options[$status_scope]) ? $team_dash_status_options[$status_scope] : (isset($team_dash_status_options['task']) ? $team_dash_status_options['task'] : array());
          $has_status_match = false;
          foreach ($item_status_options as $sr) {
              if (isset($sr->code) && (string) $sr->code === $item_status) {
                  $has_status_match = true;
                  break;
              }
          }
          $row_bg = $badge_color . ($is_full ? '08' : '14');
          ?>
          <tr class="project-dash-task-row project-dash-task-row-<?php echo esc_view($item_status); ?>" style="--pd-row-status-color:<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>;background:<?php echo esc_view($row_bg, ENT_QUOTES, 'UTF-8'); ?>;">
            <td>
              <a href="<?php echo esc_view($item_url, ENT_QUOTES, 'UTF-8'); ?>" class="project-dash-task-title" title="<?php echo esc_view($item_title, ENT_QUOTES, 'UTF-8'); ?><?php echo $item_detail !== '' ? ' — ' . esc_view($item_detail, ENT_QUOTES, 'UTF-8') : ''; ?>">
                <?php echo esc_view($item_title); ?>
              </a>
            </td>
            <td>
              <span class="project-dash-date" title="<?php echo esc_view($item_date, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($item_date); ?></span>
            </td>
            <td class="text-end text-nowrap project-dash-est" title="Estimate (hrs)"><?php echo esc_view($item_est); ?></td>
            <?php if ($show_act): ?>
            <td class="text-end text-nowrap project-dash-act" title="Actual (hrs)"><?php echo esc_view($item_act); ?></td>
            <?php endif; ?>
            <td>
              <select class="form-select form-select-sm project-dash-status-select"
                      data-item-id="<?php echo (int) $item['id']; ?>"
                      data-item-type="<?php echo esc_view($item['item_type'], ENT_QUOTES, 'UTF-8'); ?>"
                      data-item-source="<?php echo esc_view(isset($item['item_source']) ? (string) $item['item_source'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                      data-estimate-hours="<?php echo esc_view(isset($item['estimate_hours']) && $item['estimate_hours'] !== null && $item['estimate_hours'] !== '' ? (string) $item['estimate_hours'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                      data-actual-hours="<?php echo esc_view(isset($item['actual_hours']) && $item['actual_hours'] !== null && $item['actual_hours'] !== '' ? (string) $item['actual_hours'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                      data-prev-status="<?php echo esc_view($item_status, ENT_QUOTES, 'UTF-8'); ?>"
                      style="color:<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>;background-color:<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>1a;border-color:<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>40;"
                      title="<?php echo esc_view($item_label, ENT_QUOTES, 'UTF-8'); ?>"
                      aria-label="Status for <?php echo esc_view($item_title, ENT_QUOTES, 'UTF-8'); ?>">
                <?php if (!$has_status_match && $item_label !== ''): ?>
                  <option value="<?php echo esc_view($item_status, ENT_QUOTES, 'UTF-8'); ?>" selected data-color="<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo esc_view($item_label); ?>
                  </option>
                <?php endif; ?>
                <?php foreach ($item_status_options as $sr): ?>
                  <option value="<?php echo esc_view($sr->code, ENT_QUOTES, 'UTF-8'); ?>"
                          data-color="<?php echo esc_view($sr->color, ENT_QUOTES, 'UTF-8'); ?>"
                          <?php echo $sr->code === $item_status ? 'selected' : ''; ?>>
                    <?php echo esc_view($sr->name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <?php
      }
      echo '</tbody></table>';
  };
  ?>
  <?php if ($is_user_focused && !empty($group_cards)): ?>
    <?php
      $card = $group_cards[0];
      $items = isset($card['items']) ? $card['items'] : array();
      $focus_item_count = count($items);
    ?>
    <div class="table-responsive bg-white border shadow-sm team-dash-focus-table-wrap">
      <?php if (empty($items)): ?>
        <div class="text-center py-3 text-muted small">
          <i class="bi bi-inbox d-block mb-1" style="font-size:1.5rem;"></i>
          No items found for this employee.
        </div>
      <?php else: ?>
        <?php $render_team_dash_items_table($items, array(
          'table_id'    => 'unifiedEmployeeTasksTable',
          'table_class' => 'table table-hover table-striped datatable sortable-table align-middle mb-0',
          'full_width'  => true,
        )); ?>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="row project-dash-grid align-items-start">
      <?php foreach ($group_cards as $card): ?>
        <?php
          $entity = isset($card['entity']) ? $card['entity'] : null;
          $items = isset($card['items']) ? $card['items'] : array();
          $item_count = count($items);
          $entity_name = ($entity && isset($entity->name)) ? (string) $entity->name : '';
          $grid_col_class = $is_user_focused ? 'col-12 team-dash-grid-col-full' : 'col-sm-6 col-lg-4 col-xl-3 team-dash-grid-col';
          $team_search_bits = array(strtolower($entity_name));
          foreach ($items as $it) {
              if (!empty($it['title'])) {
                  $team_search_bits[] = strtolower((string) $it['title']);
              }
          }
          $team_search_hay = implode(' ', $team_search_bits);
        ?>
        <div class="<?php echo esc_view($grid_col_class); ?>" data-team-search="<?php echo esc_view($team_search_hay, ENT_QUOTES, 'UTF-8'); ?>">
          <div class="card project-dash-card">
            <div class="card-body">
              <div class="project-dash-head">
                  <?php if ($entity && isset($entity->id)): ?>
                    <?php 
                      $paramName = ($group_mode === 'employee') ? 'user_id' : 'project_id';
                      $linkParams = $_GET;
                      unset($linkParams['embed'], $linkParams['focus']);
                      $linkExtra = array($paramName => (int) $entity->id, 'focus' => 1);
                      if ($embed) {
                          $linkParams['embed'] = 1;
                      }
                      if ($complete_view_on) {
                          $linkParams['complete_view'] = 1;
                      } else {
                          unset($linkParams['complete_view']);
                      }
                      if ($this->input->get('parent_tab')) {
                          $linkParams['parent_tab'] = (string) $this->input->get('parent_tab');
                      }
                      $targetUrl = site_url('tasks/my-dashboard') . '?' . http_build_query(array_merge($linkParams, $linkExtra)); 
                    ?>
                    <a href="<?php echo esc_view($targetUrl); ?>" class="project-dash-head-link text-decoration-none d-flex align-items-start justify-content-between w-100" title="View all items for <?php echo esc_view($entity_name, ENT_QUOTES, 'UTF-8'); ?>">
                      <div class="project-dash-head-main">
                        <span class="project-dash-project-name text-primary fw-semibold"><?php echo esc_view($entity_name); ?></span>
                      </div>
                      <span class="project-dash-count<?php echo $item_count < 1 ? ' is-zero' : ''; ?>" title="<?php echo (int) $item_count; ?> item(s)">
                        <?php echo (int) $item_count; ?>
                      </span>
                    </a>
                  <?php else: ?>
                    <div class="project-dash-head-main">
                      <span class="project-dash-project-name text-body" title="<?php echo esc_view($entity_name, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($entity_name); ?></span>
                    </div>
                    <span class="project-dash-count<?php echo $item_count < 1 ? ' is-zero' : ''; ?>" title="<?php echo (int) $item_count; ?> item(s)">
                      <?php echo (int) $item_count; ?>
                    </span>
                  <?php endif; ?>
              </div>

              <?php if (empty($items)): ?>
                <div class="project-dash-task-list">
                  <div class="project-dash-empty">
                    <i class="bi bi-inbox"></i>
                    <span>No items</span>
                  </div>
                </div>
              <?php else: ?>
                <div class="project-dash-task-list project-dash-task-list-section">
                  <?php $render_team_dash_items_table($items); ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
</div>
<script>
(function () {
  var completeToggle = document.getElementById('teamDashCompleteToggle');
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

  var form = document.getElementById('teamDashFilterForm');
  if (form) {
    var selects = form.querySelectorAll('select.project-dash-filter-select');
    selects.forEach(function (select) {
      select.addEventListener('change', function () {
        form.submit();
      });
    });
  }

  var teamSearch = document.getElementById('teamDashSearch');
  var teamGrid = document.querySelector('.team-dash-index .project-dash-grid');
  function applyTeamSearch() {
    if (!teamSearch || !teamGrid) {
      return;
    }
    var q = String(teamSearch.value || '').trim().toLowerCase();
    teamGrid.querySelectorAll('[data-team-search]').forEach(function (col) {
      var hay = String(col.getAttribute('data-team-search') || '');
      col.style.display = (q === '' || hay.indexOf(q) >= 0) ? '' : 'none';
    });
  }
  if (teamSearch) {
    teamSearch.addEventListener('input', applyTeamSearch);
    teamSearch.addEventListener('search', applyTeamSearch);
    teamSearch.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
      }
    });
  }

  $(document).on('change', '.project-dash-status-select', function() {
      var $select = $(this);
      var itemId = $select.data('item-id');
      var itemType = $select.data('item-type');
      var itemSource = $select.data('item-source') || '';
      var newStatus = $select.val();
      var $selectedOption = $select.find('option:selected');
      var newColor = $selectedOption.data('color');
      
      $select.css({
          'color': newColor,
          'background-color': newColor + '1a',
          'border-color': newColor + '40'
      });
      $select.closest('tr').css({
          '--pd-row-status-color': newColor,
          'background-color': newColor + '14'
      });
      
      $.ajax({
          url: '<?php echo site_url('tasks/ajax_update_item_status'); ?>',
          type: 'POST',
          dataType: 'json',
          data: {
              id: itemId,
              type: itemType,
              source: itemSource,
              status: newStatus,
              actual_hours: $select.attr('data-actual-hours') || '',
              <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
          },
          success: function(response) {
              if (!response.success) {
                  if (response.need_actual_hours && window.omsActualHours) {
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
                  alert(response.error || 'Failed to update status.');
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
              alert('An error occurred while updating the status.');
          }
      });
  });
})();
</script>
<?php if (!$embed) {
  $this->load->view('partials/footer');
} else {
  echo '<script src="' . base_url('assets/js/actual-hours-complete.js') . '"></script>';
} ?>
