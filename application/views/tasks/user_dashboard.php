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
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
}
.project-dash-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}
.project-dash-section-title {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 0.5rem;
    padding-left: 0.5rem;
}
#unifiedEmployeeTasksTable {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}
#unifiedEmployeeTasksTable thead th {
    background-color: #f8fafc;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e2e8f0;
    padding: 10px 14px;
}
#unifiedEmployeeTasksTable tbody td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
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
    font-size: 0.72rem !important;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 4px 8px;
}
</style>

<?php
$filter_user_id = isset($filter_user_id) ? (int) $filter_user_id : -1;
$filter_users = isset($filter_users) ? $filter_users : array();
$is_user_filtered = ($filter_user_id >= 0);
?>

<div class="container-fluid project-dash-compact team-dash-index<?php echo $is_user_filtered ? ' is-user-filtered' : ''; ?>">
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

<?php if (!$is_user_filtered): ?>
  <?php if (!empty($filter_users)): ?>
  <div class="project-dash-filters">
    <form method="get" action="<?php echo site_url('tasks/my-dashboard'); ?>" class="project-dash-filter-form" id="teamDashFilterForm">
      <?php if (!empty($filter_projects)): ?>
      <label class="project-dash-filter-label me-3">
        <span class="project-dash-filter-label-text">Project</span>
        <select name="project_id" class="form-select form-select-sm project-dash-filter-select">
          <option value="all"<?php echo $filter_project_id < 0 ? ' selected' : ''; ?>>All Projects</option>
          <?php foreach ($filter_projects as $fp): ?>
            <option value="<?php echo (int)$fp->id; ?>"<?php echo $filter_project_id === (int)$fp->id ? ' selected' : ''; ?>><?php echo esc_view($fp->name, ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php endif; ?>

      <label class="project-dash-filter-label me-3">
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
    </form>
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
      unset($backParams['user_id']);
      if (isset($_GET['tab'])) {
          $backUrl = site_url('my-works') . (empty($backParams) ? '' : '?' . http_build_query($backParams));
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
    ?>
    <h1 class="mw-focus-screen-title"><?php echo esc_view($display_name_header); ?></h1>
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
  $render_team_dash_items_table = function (array $section_items, array $options = array()) use ($status_rows) {
      if (empty($section_items)) {
          echo '<div class="project-dash-section-empty"><span>No items</span></div>';
          return;
      }

      $table_id = isset($options['table_id']) ? (string) $options['table_id'] : '';
      $table_class = isset($options['table_class']) ? (string) $options['table_class'] : 'table table-sm project-dash-task-table mb-0';
      $is_full = !empty($options['full_width']);

      echo '<table' . ($table_id !== '' ? ' id="' . esc_view($table_id, ENT_QUOTES, 'UTF-8') . '"' : '') . ' class="' . esc_view($table_class, ENT_QUOTES, 'UTF-8') . '">';
      echo '<thead><tr>';
      echo '<th>Task</th>';
      echo '<th style="width:' . ($is_full ? '100px' : '55px') . ';">Date</th>';
      echo '<th style="width:' . ($is_full ? '140px' : '110px') . ';">Status</th>';
      echo '</tr></thead><tbody>';

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
          $row_bg = $badge_color . ($is_full ? '08' : '14');
          $detail_max = $is_full ? '30rem' : '9rem';
          ?>
          <tr class="project-dash-task-row project-dash-task-row-<?php echo esc_view($item_status); ?>" style="--pd-row-status-color:<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>;background:<?php echo esc_view($row_bg, ENT_QUOTES, 'UTF-8'); ?>;">
            <td>
              <?php if ($is_full): ?>
              <div class="fw-semibold">
              <?php endif; ?>
              <a href="<?php echo esc_view($item_url, ENT_QUOTES, 'UTF-8'); ?>" class="project-dash-task-title<?php echo $is_full ? ' text-decoration-none text-dark' : ''; ?>" title="<?php echo esc_view($item_title, ENT_QUOTES, 'UTF-8'); ?><?php echo $item_detail !== '' ? ' — ' . esc_view($item_detail, ENT_QUOTES, 'UTF-8') : ''; ?>">
                <?php echo esc_view($item_title); ?>
              </a>
              <?php if ($is_full): ?>
              </div>
              <?php endif; ?>
              <?php if ($item_detail !== ''): ?>
                <div class="small text-muted text-truncate" style="max-width:<?php echo esc_view($detail_max, ENT_QUOTES, 'UTF-8'); ?>;" title="<?php echo esc_view($item_detail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($item_detail); ?></div>
              <?php endif; ?>
            </td>
            <td>
              <span class="project-dash-date<?php echo $is_full ? ' text-muted font-monospace small' : ''; ?>" title="<?php echo esc_view($item_date, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($item_date); ?></span>
            </td>
            <td>
              <select class="form-select form-select-sm project-dash-status-select<?php echo $is_full ? ' font-semibold small' : ''; ?>"
                      data-item-id="<?php echo (int) $item['id']; ?>"
                      data-item-type="<?php echo esc_view($item['item_type'], ENT_QUOTES, 'UTF-8'); ?>"
                      data-item-source="<?php echo esc_view(isset($item['item_source']) ? (string) $item['item_source'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                      style="color:<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>;background-color:<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>1a;border:1px solid <?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>40;font-weight:600;font-size:0.75rem;border-radius:4px;padding:2px 20px 2px 6px; cursor:pointer;"
                      title="<?php echo esc_view($item_label, ENT_QUOTES, 'UTF-8'); ?>">
                <?php foreach ($status_rows as $sr): ?>
                  <option value="<?php echo esc_view($sr->code, ENT_QUOTES, 'UTF-8'); ?>"
                          data-color="<?php echo esc_view($sr->color, ENT_QUOTES, 'UTF-8'); ?>"
                          style="color: <?php echo esc_view($sr->color, ENT_QUOTES, 'UTF-8'); ?>; font-weight: 600;"
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
  <?php if ($is_user_filtered && !empty($group_cards)): ?>
    <?php
      $card = $group_cards[0];
      $items = isset($card['items']) ? $card['items'] : array();
    ?>
    <div class="table-responsive bg-white rounded-3 border p-3 mt-2 shadow-sm">
      <?php if (empty($items)): ?>
        <div class="text-center py-4 text-muted">
          <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;"></i>
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
          $grid_col_class = $is_user_filtered ? 'col-12 team-dash-grid-col-full' : 'col-sm-6 col-lg-4 col-xl-3 team-dash-grid-col';
        ?>
        <div class="<?php echo esc_view($grid_col_class); ?>">
          <div class="card project-dash-card">
            <div class="card-body">
              <div class="project-dash-head">
                <div class="project-dash-head-main">
                  <?php if ($entity && isset($entity->id)): ?>
                    <?php 
                      $paramName = ($group_mode === 'employee') ? 'user_id' : 'project_id';
                      $linkParams = $_GET;
                      unset($linkParams['embed']);
                      $targetUrl = site_url('tasks/my-dashboard') . '?' . http_build_query(array_merge($linkParams, array($paramName => (int) $entity->id))); 
                    ?>
                    <a href="<?php echo esc_view($targetUrl); ?>" class="project-dash-project-name text-primary fw-semibold text-decoration-none" title="Filter by <?php echo esc_view($entity_name, ENT_QUOTES, 'UTF-8'); ?>">
                      <?php echo esc_view($entity_name); ?>
                    </a>
                  <?php else: ?>
                    <span class="project-dash-project-name text-body" title="<?php echo esc_view($entity_name, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($entity_name); ?></span>
                  <?php endif; ?>
                </div>
                <span class="project-dash-count<?php echo $item_count < 1 ? ' is-zero' : ''; ?>" title="<?php echo (int) $item_count; ?> item(s)">
                  <?php echo (int) $item_count; ?>
                </span>
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
<?php if (!empty($filter_users)): ?>
<script>
(function () {
  var form = document.getElementById('teamDashFilterForm');
  if (!form) {
    return;
  }
  var selects = form.querySelectorAll('select.project-dash-filter-select');
  if (selects.length === 0) {
    return;
  }
  selects.forEach(function (select) {
    select.addEventListener('change', function () {
      $('#teamDashFilterForm').submit();
    });
  });

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
              <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
          },
          success: function(response) {
              if (!response.success) {
                  alert(response.error || 'Failed to update status.');
              }
          },
          error: function() {
              alert('An error occurred while updating the status.');
          }
      });
  });
})();
</script>
<?php endif; ?>
<?php if (!$embed) {
  $this->load->view('partials/footer');
} ?>
