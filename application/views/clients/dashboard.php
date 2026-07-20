<?php
$embed = !empty($embed);
$client_cards = isset($client_cards) && is_array($client_cards) ? $client_cards : array();
$total_projects = isset($total_projects) ? (int) $total_projects : 0;
$total_tasks = isset($total_tasks) ? (int) $total_tasks : 0;

$assignee_label = function ($task) {
    if (isset($task->assignee_name) && trim((string) $task->assignee_name) !== '') {
        return trim((string) $task->assignee_name);
    }
    if (isset($task->assignee_email) && trim((string) $task->assignee_email) !== '') {
        $parts = explode('@', (string) $task->assignee_email);
        return $parts[0];
    }
    return '—';
};

$status_badge = function ($status) {
    $st = trim((string) $status);
    if ($st === '') {
        $st = 'pending';
    }
    $colors = array(
        'pending'     => 'secondary',
        'in_progress' => 'info',
        'completed'   => 'success',
        'blocked'     => 'danger',
        'open'        => 'secondary',
        'done'        => 'success',
    );
    $color = isset($colors[$st]) ? $colors[$st] : 'secondary';
    $label = ucfirst(str_replace('_', ' ', $st));
    return '<span class="badge text-bg-' . $color . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
};

if (!$embed) {
    $this->load->view('partials/header', array(
        'title' => 'Clients Dashboard',
        'extra_css' => array('assets/css/clients.css', 'assets/css/project-dashboard.css'),
    ));
}
?>

<div class="container-fluid project-dash-compact client-dash-page">
  <?php if (!$embed): ?>
  <?php
  $this->load->view('partials/oms_page_head', array(
      'title'        => 'Clients',
      'icon'         => 'bi bi-building',
      'subtitle'     => 'Alphabetical client cards · click a project for tasks',
      'actions_html' => '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('clients') . '"><i class="bi bi-list me-1"></i>All Clients</a>',
      'mb'           => 'mb-0',
  ));
  ?>
  <?php endif; ?>

  <div class="client-dash-summary">
    <div class="project-dash-stat">
      <div class="project-dash-stat-label">Clients</div>
      <div class="project-dash-stat-value"><?php echo count($client_cards); ?></div>
    </div>
    <div class="project-dash-stat">
      <div class="project-dash-stat-label">Projects</div>
      <div class="project-dash-stat-value"><?php echo (int) $total_projects; ?></div>
    </div>
    <div class="project-dash-stat">
      <div class="project-dash-stat-label">Tasks</div>
      <div class="project-dash-stat-value"><?php echo (int) $total_tasks; ?></div>
    </div>
  </div>

  <?php if (empty($client_cards)): ?>
  <div class="card project-dash-card">
    <div class="card-body text-center py-3 text-muted small">No clients found.</div>
  </div>
  <?php else: ?>
  <div class="row project-dash-grid align-items-start client-dash-grid">
    <?php foreach ($client_cards as $card): ?>
      <?php
        $c = $card['client'];
        $cid = (int) $c->id;
        $name = trim((string) (isset($c->company_name) ? $c->company_name : ''));
        if ($name === '') {
            $name = 'Client #' . $cid;
        }
        $projects = isset($card['projects']) ? $card['projects'] : array();
        $pc = (int) $card['project_count'];
        $tc = (int) $card['task_count'];
      ?>
      <div class="col-12 col-md-6 col-lg-4 project-dash-grid-col">
        <div class="card project-dash-card client-dash-card">
          <div class="card-body">
            <div class="client-dash-card-head">
              <div class="client-dash-card-title">
                <i class="bi bi-building"></i>
                <a href="<?php echo site_url('clients/view/' . $cid); ?>" class="client-dash-client-name" title="<?php echo esc_view($name, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo esc_view($name); ?>
                </a>
              </div>
              <div class="client-dash-card-counts">
                <span title="Projects"><?php echo $pc; ?>p</span>
                <span title="Tasks"><?php echo $tc; ?>t</span>
              </div>
            </div>

            <?php if (empty($projects)): ?>
            <div class="project-dash-empty">
              <i class="bi bi-inbox"></i>
              <span>No projects</span>
            </div>
            <?php else: ?>
            <ul class="client-dash-project-list">
              <?php foreach ($projects as $section): ?>
                <?php
                  $project = $section['project'];
                  $tasks = isset($section['tasks']) ? $section['tasks'] : array();
                  $pid = (int) $project->id;
                  $pname = trim((string) (isset($project->name) ? $project->name : ''));
                  if ($pname === '') {
                      $pname = 'Project #' . $pid;
                  }
                  $task_n = count($tasks);
                ?>
                <li class="client-dash-project-item" data-project-id="<?php echo $pid; ?>">
                  <div class="client-dash-project-row" role="button" tabindex="0" aria-expanded="false">
                    <span class="client-dash-project-row-main">
                      <i class="bi bi-chevron-right client-dash-chevron" aria-hidden="true"></i>
                      <span class="client-dash-project-label"><?php echo esc_view($pname); ?></span>
                    </span>
                    <span class="client-dash-project-meta">
                      <span class="project-dash-count<?php echo $task_n < 1 ? ' is-zero' : ''; ?>"><?php echo $task_n; ?></span>
                      <a href="<?php echo site_url('projects/' . $pid); ?>" class="client-dash-project-open" title="Open project">
                        <i class="bi bi-box-arrow-up-right"></i>
                      </a>
                    </span>
                  </div>
                  <div class="client-dash-tasks" hidden>
                    <?php if ($task_n < 1): ?>
                    <div class="client-dash-tasks-empty">No tasks</div>
                    <?php else: ?>
                    <table class="table table-sm project-dash-task-table mb-0">
                      <thead>
                        <tr>
                          <th>Task</th>
                          <th>Assignee</th>
                          <th style="width:58px;">Due</th>
                          <th style="width:90px;">Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($tasks as $task): ?>
                          <?php
                            $due = isset($task->due_date) ? trim((string) $task->due_date) : '';
                            $due_label = ($due !== '' && $due !== '0000-00-00') ? date('M j', strtotime($due)) : '—';
                            $assignee = $assignee_label($task);
                            $title = isset($task->title) ? (string) $task->title : '';
                          ?>
                          <tr class="project-dash-task-row">
                            <td>
                              <a href="<?php echo site_url('tasks/' . (int) $task->id); ?>" class="project-dash-task-title" title="<?php echo esc_view($title, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo esc_view($title); ?>
                              </a>
                            </td>
                            <td class="project-dash-assignee" title="<?php echo esc_view($assignee, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($assignee); ?></td>
                            <td class="project-dash-date"><?php echo esc_view($due_label); ?></td>
                            <td><?php echo $status_badge(isset($task->status) ? $task->status : ''); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                    <?php endif; ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
(function () {
  var root = document.querySelector('.client-dash-page');
  if (!root) {
    return;
  }
  root.addEventListener('click', function (e) {
    if (e.target.closest('.client-dash-project-open')) {
      return;
    }
    var row = e.target.closest('.client-dash-project-row');
    if (!row || !root.contains(row)) {
      return;
    }
    e.preventDefault();
    toggleProjectRow(row);
  });
  root.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') {
      return;
    }
    var row = e.target.closest('.client-dash-project-row');
    if (!row || !root.contains(row)) {
      return;
    }
    e.preventDefault();
    toggleProjectRow(row);
  });
  function toggleProjectRow(row) {
    var item = row.closest('.client-dash-project-item');
    if (!item) {
      return;
    }
    var panel = item.querySelector('.client-dash-tasks');
    var open = row.getAttribute('aria-expanded') === 'true';
    row.setAttribute('aria-expanded', open ? 'false' : 'true');
    item.classList.toggle('is-open', !open);
    if (panel) {
      panel.hidden = open;
    }
  }
})();
</script>

<?php if (!$embed) {
    $this->load->view('partials/footer');
} ?>
