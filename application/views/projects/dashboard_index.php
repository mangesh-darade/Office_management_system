<?php $this->load->view('partials/header', array(
  'title' => 'Project Dashboard',
  'extra_css' => array('assets/css/project-dashboard.css'),
)); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="container-fluid project-dash-compact">
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

$total_projects = count($project_cards);
$total_tasks = 0;
$status_totals = array();
foreach ($status_rows as $sr) {
    $status_totals[(string) $sr->code] = 0;
}
foreach ($project_cards as $card) {
    $tasks = isset($card['tasks']) ? $card['tasks'] : array();
    $total_tasks += count($tasks);
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
?>

<div class="project-dash-summary">
  <div class="project-dash-stat">
    <div class="project-dash-stat-label">Projects</div>
    <div class="project-dash-stat-value"><?php echo (int) $total_projects; ?></div>
  </div>
  <div class="project-dash-stat">
    <div class="project-dash-stat-label">Total Tasks</div>
    <div class="project-dash-stat-value"><?php echo (int) $total_tasks; ?></div>
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

<?php if (!empty($status_rows)): ?>
  <div class="project-dash-legend">
    <span class="project-dash-legend-title">Status</span>
    <?php foreach ($status_rows as $sr): ?>
      <?php
        $code = (string) $sr->code;
        $label = isset($status_labels[$code]) ? $status_labels[$code] : $code;
        $dot_color = isset($status_colors[$code]) ? $status_colors[$code] : '#9ca3af';
      ?>
      <span class="project-dash-legend-item">
        <span class="project-dash-legend-dot" style="background:<?php echo esc_view($dot_color, ENT_QUOTES, 'UTF-8'); ?>;"></span>
        <?php echo esc_view($label); ?>
      </span>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (empty($project_cards)): ?>
  <div class="card project-dash-card">
    <div class="card-body text-center py-4 text-muted">No projects available.</div>
  </div>
<?php else: ?>
  <div class="row project-dash-grid">
    <?php foreach ($project_cards as $card): ?>
      <?php
        $p = $card['project'];
        $tasks = isset($card['tasks']) ? $card['tasks'] : array();
        $task_count = count($tasks);
      ?>
      <div class="col-sm-6 col-lg-4 col-xl-3">
        <div class="card project-dash-card">
          <div class="card-body">
            <div class="project-dash-head">
              <div class="project-dash-head-main">
                <span class="project-dash-code"><?php echo esc_view((string) $p->code); ?></span>
                <a href="<?php echo site_url('projects/' . (int) $p->id); ?>" class="text-decoration-none project-dash-project-name" title="<?php echo esc_view((string) $p->name, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo esc_view((string) $p->name); ?>
                </a>
              </div>
              <span class="project-dash-count<?php echo $task_count < 1 ? ' is-zero' : ''; ?>" title="<?php echo (int) $task_count; ?> task(s)">
                <?php echo (int) $task_count; ?>
              </span>
            </div>

            <div class="project-dash-task-list">
              <?php if (empty($tasks)): ?>
                <div class="project-dash-empty">
                  <i class="bi bi-inbox"></i>
                  <span>No tasks</span>
                </div>
              <?php else: ?>
                <table class="table table-sm project-dash-task-table mb-0">
                  <thead>
                    <tr>
                      <th>Task</th>
                      <th>Status</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($tasks as $task): ?>
                      <?php
                        $task_status = trim((string) $task->status);
                        if ($task_status === '') {
                          $task_status = 'pending';
                        }
                        $task_label = isset($status_labels[$task_status]) ? $status_labels[$task_status] : ucfirst(str_replace('_', ' ', $task_status));
                        $task_date = $format_task_date($task);
                        $badge_color = isset($status_colors[$task_status]) ? $status_colors[$task_status] : '#6b7280';
                      ?>
                      <tr>
                        <td>
                          <a href="<?php echo site_url('tasks/' . (int) $task->id); ?>" class="project-dash-task-title" title="<?php echo esc_view((string) $task->title, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo esc_view((string) $task->title); ?>
                          </a>
                        </td>
                        <td>
                          <span class="project-dash-status-badge"
                            style="color:<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>;background:<?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>1a;border:1px solid <?php echo esc_view($badge_color, ENT_QUOTES, 'UTF-8'); ?>40;"
                            title="<?php echo esc_view($task_label, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo esc_view($task_label); ?>
                          </span>
                        </td>
                        <td class="project-dash-date"><?php echo esc_view($task_date); ?></td>
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
<?php $this->load->view('partials/footer'); ?>
