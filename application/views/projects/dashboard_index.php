<?php $this->load->view('partials/header', array(
  'title' => 'Project Dashboard',
  'extra_css' => array('assets/css/project-dashboard.css'),
)); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

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

$total_projects = count($project_cards);
$total_tasks = 0;
$total_requirements = 0;
$status_totals = array();
foreach ($status_rows as $sr) {
    $status_totals[(string) $sr->code] = 0;
}
foreach ($project_cards as $card) {
    $tasks = isset($card['tasks']) ? $card['tasks'] : array();
    $requirements = isset($card['requirements']) ? $card['requirements'] : array();
    $total_tasks += count($tasks);
    $total_requirements += count($requirements);
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

$filter_project_id = isset($filter_project_id) ? (int) $filter_project_id : 0;
$filter_projects = isset($filter_projects) ? $filter_projects : array();

$head_actions = '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('projects') . '"><i class="bi bi-kanban me-1"></i>All Projects</a>';
$this->load->view('partials/oms_page_head', array(
    'title'        => 'Project Dashboard',
    'icon'         => 'bi-columns-gap',
    'subtitle'     => '',
    'actions_html' => $head_actions,
    'mb'           => 'mb-0',
));

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

<?php if (!empty($filter_projects)): ?>
<div class="project-dash-filters">
  <form method="get" action="<?php echo site_url('projects/dashboard'); ?>" class="project-dash-filter-form" id="projectDashFilterForm">
    <label class="project-dash-filter-label me-3">
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

    <label class="project-dash-filter-label me-3">
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
  </form>
</div>
<?php endif; ?>

<div class="project-dash-summary">
  <div class="project-dash-stat">
    <div class="project-dash-stat-label">Projects</div>
    <div class="project-dash-stat-value"><?php echo (int) $total_projects; ?></div>
  </div>
  <div class="project-dash-stat">
    <div class="project-dash-stat-label">Total Tasks</div>
    <div class="project-dash-stat-value"><?php echo (int) $total_tasks; ?></div>
  </div>
  <div class="project-dash-stat">
    <div class="project-dash-stat-label">Requirements</div>
    <div class="project-dash-stat-value"><?php echo (int) $total_requirements; ?></div>
  </div>
  <?php foreach ($status_rows as $sr): ?>
    <?php
      $code = (string) $sr->code;
      $label = isset($status_labels[$code]) ? $status_labels[$code] : $code;
      $count = isset($status_totals[$code]) ? (int) $status_totals[$code] : 0;
    ?>
    <div class="project-dash-stat">
      <div class="project-dash-stat-label"><?php echo esc_view($label); ?></div>
      <div class="project-dash-stat-value" style="color:<?php echo esc_view(isset($status_colors[$code]) ? $status_colors[$code] : '#374151', ENT_QUOTES, 'UTF-8'); ?>;">
        <?php echo $count; ?>
      </div>
    </div>
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
      <div class="col-12 col-md-6 col-lg-4 project-dash-grid-col">
        <div class="card project-dash-card">
          <div class="card-body">
            <div class="project-dash-head">
              <div class="project-dash-head-main">
                <a href="<?php echo site_url('projects/' . (int) $p->id); ?>" class="text-decoration-none project-dash-project-name" title="<?php echo esc_view((string) $p->name, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo esc_view((string) $p->name); ?>
                </a>
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
                        if ($kind === 'requirement') {
                            $item_url = site_url('requirements/view/' . (int) $row->id);
                            $type_prefix = 'Req: ';
                        } else {
                            $item_url = site_url('tasks/' . (int) $row->id);
                            $type_prefix = '';
                        }
                      ?>
                      <tr class="project-dash-task-row project-dash-task-row-<?php echo esc_view($task_status); ?>"
                          style="<?php echo esc_view(status_row_css_var_style($badge_color), ENT_QUOTES, 'UTF-8'); ?>">
                        <td>
                          <a href="<?php echo $item_url; ?>" class="project-dash-task-title" title="<?php echo esc_view($type_prefix . $item_title, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if ($type_prefix !== ''): ?><span class="text-muted small"><?php echo esc_view($type_prefix); ?></span><?php endif; ?>
                            <?php echo esc_view($item_title); ?>
                          </a>
                        </td>
                        <td class="project-dash-assignee" title="<?php echo esc_view($assignee, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($assignee); ?></td>
                        <td class="project-dash-date"><?php echo esc_view($task_date); ?></td>
                        <td>
                          <select class="form-select form-select-sm project-dash-status-select" 
                                  data-id="<?php echo (int) $row->id; ?>" 
                                  data-type="<?php echo $kind === 'requirement' ? 'requirement' : 'project_task'; ?>"
                                  style="color:<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>;background-color:<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>1a;border:1px solid <?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>40;font-weight:600;font-size:0.75rem;border-radius:4px;padding:2px 20px 2px 6px; cursor:pointer;"
                                  title="<?php echo esc_view($task_label, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php foreach ($status_rows as $srow): ?>
                              <option value="<?php echo esc_view($srow->code, ENT_QUOTES, 'UTF-8'); ?>" 
                                      data-color="<?php echo esc_view(isset($status_colors[$srow->code]) ? $status_colors[$srow->code] : $srow->color, ENT_QUOTES, 'UTF-8'); ?>"
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
<?php if (!empty($filter_projects)): ?>
<script>
(function () {
  var form = document.getElementById('projectDashFilterForm');
  if (!form) {
    return;
  }
  var selects = form.querySelectorAll('select.project-dash-filter-select');
  if (selects.length === 0) {
    return;
  }
  selects.forEach(function (select) {
    select.addEventListener('change', function () {
      form.submit();
    });
  });

  $(document).on('change', '.project-dash-status-select', function() {
    var $select = $(this);
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

    $.ajax({
      url: '<?php echo site_url("tasks/ajax_update_item_status"); ?>',
      type: 'POST',
      data: {
        id: itemId,
        type: itemType,
        status: newStatus,
        <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
      },
      dataType: 'json',
      success: function(res) {
        if (!res || !res.success) {
          alert('Failed to update status.');
        }
      },
      error: function() {
        alert('An error occurred. Please try again.');
      }
    });
  });
})();
</script>
<?php endif; ?>
<?php $this->load->view('partials/footer'); ?>
