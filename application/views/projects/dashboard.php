<?php $this->load->view('partials/header', array(
  'title' => 'Project Dashboard — ' . (isset($project->name) ? $project->name : ''),
  'extra_css' => array('assets/css/project-dashboard.css', 'assets/css/tasks.css'),
)); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="container-fluid project-dash-compact project-single-dash task-board-pro">
<?php
$status_labels = array();
$status_colors = array();
$default_colors = array(
    'pending'     => '#6b7280',
    'in_progress' => '#0284c7',
    'completed'   => '#16a34a',
    'blocked'     => '#dc2626',
);
$status_meta = array(
    'pending' => array('icon' => 'bi-hourglass-split'),
    'in_progress' => array('icon' => 'bi-play-circle'),
    'completed' => array('icon' => 'bi-check-circle'),
    'blocked' => array('icon' => 'bi-slash-circle'),
);
$priority_meta = array(
    'urgent' => array('label' => 'Urgent', 'class' => 'tb-priority-urgent'),
    'high' => array('label' => 'High', 'class' => 'tb-priority-high'),
    'medium' => array('label' => 'Medium', 'class' => 'tb-priority-medium'),
    'low' => array('label' => 'Low', 'class' => 'tb-priority-low'),
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

$total_tasks = 0;
foreach ($stats as $cnt) {
    $total_tasks += (int) $cnt;
}

$can_add_task = function_exists('has_module_access') && (has_module_access('tasks_add') || has_module_access('tasks'));
$can_edit_task = function_exists('has_module_access') && (has_module_access('tasks_edit') || has_module_access('tasks'));

$head_actions = '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('projects/dashboard') . '"><i class="bi bi-grid me-1"></i>All Projects</a>';
$head_actions .= '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('projects/' . (int) $project->id) . '"><i class="bi bi-eye me-1"></i>Details</a>';
if ($can_add_task) {
    $head_actions .= '<a class="btn btn-primary btn-sm" href="' . site_url('tasks/create?project_id=' . (int) $project->id) . '"><i class="bi bi-plus-lg me-1"></i>New Task</a>';
}

$project_code = isset($project->code) ? trim((string) $project->code) : '';
$subtitle_parts = array();
if ($project_code !== '') {
    $subtitle_parts[] = $project_code;
}
$subtitle_parts[] = 'tasks by status';
$subtitle = implode(' · ', $subtitle_parts);

$this->load->view('partials/oms_page_head', array(
    'title'        => (string) $project->name,
    'icon'         => 'bi-columns-gap',
    'subtitle'     => $subtitle,
    'actions_html' => $head_actions,
    'mb'           => 'mb-2',
));
?>

<div class="project-dash-summary project-single-dash-summary">
  <div class="project-dash-stat">
    <div class="project-dash-stat-label">Total Tasks</div>
    <div class="project-dash-stat-value"><?php echo (int) $total_tasks; ?></div>
  </div>
  <?php foreach ($status_rows as $sr): ?>
    <?php
      $code = (string) $sr->code;
      $label = isset($status_labels[$code]) ? $status_labels[$code] : $code;
      $count = isset($stats[$code]) ? (int) $stats[$code] : 0;
      $color = isset($status_colors[$code]) ? $status_colors[$code] : '#374151';
    ?>
    <div class="project-dash-stat" style="border-top-color:<?php echo esc_view($color, ENT_QUOTES, 'UTF-8'); ?>;">
      <div class="project-dash-stat-label"><?php echo esc_view($label); ?></div>
      <div class="project-dash-stat-value" style="color:<?php echo esc_view($color, ENT_QUOTES, 'UTF-8'); ?>;"><?php echo $count; ?></div>
    </div>
  <?php endforeach; ?>
  <div class="project-dash-stat project-dash-stat-progress">
    <div class="project-dash-stat-label">Progress</div>
    <div class="project-dash-stat-value"><?php echo (int) $progress; ?>%</div>
  </div>
</div>

<?php if (!empty($status_rows)): ?>
<div class="project-dash-legend mb-2">
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

<div class="card shadow-soft task-board-toolbar mb-3">
  <div class="card-body py-2 px-3">
    <div class="task-board-toolbar-inner">
      <span class="project-single-dash-hint text-muted small d-none d-md-inline"><i class="bi bi-arrows-move me-1"></i>Drag cards to change status</span>
      <div class="task-board-search-wrap ms-md-auto">
        <div class="input-group input-group-sm task-board-search">
          <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
          <input type="text" class="form-control" id="searchTasks" placeholder="Search tasks…" aria-label="Search tasks">
          <button class="btn btn-outline-secondary" type="button" id="clearSearch" aria-label="Clear search">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="kanban board-responsive">
  <?php
    $assigneeName = function ($t) {
        $name = '';
        if (isset($t->emp_name) && trim((string) $t->emp_name) !== '') {
            $name = $t->emp_name;
        } else if (isset($t->full_name) && trim((string) $t->full_name) !== '') {
            $name = $t->full_name;
        } else if (isset($t->name) && trim((string) $t->name) !== '') {
            $name = $t->name;
        } else if (isset($t->assignee_email)) {
            $name = $t->assignee_email;
        }
        return trim((string) $name);
    };
    $initials = function ($text) {
        $text = trim((string) $text);
        if ($text === '') {
            return '?';
        }
        $parts = preg_split('/\s+/', $text);
        $first = strtoupper(substr($parts[0], 0, 1));
        $last = isset($parts[count($parts) - 1]) ? strtoupper(substr($parts[count($parts) - 1], 0, 1)) : '';
        return $first . ($last && $last !== $first ? $last : '');
    };
    $formatDue = function ($task) {
        $due = isset($task->due_date) ? trim((string) $task->due_date) : '';
        if ($due === '' || $due === '0000-00-00') {
            return array('label' => '', 'overdue' => false);
        }
        $overdue = ($due < date('Y-m-d') && (!isset($task->status) || $task->status !== 'completed'));
        return array('label' => date('M j', strtotime($due)), 'overdue' => $overdue);
    };
  ?>
  <div class="kanban-scroll-wrap">
    <div class="row g-2 kanban-columns flex-nowrap flex-lg-wrap">
      <?php foreach ($columns as $status => $items): ?>
        <?php
          $label = isset($status_labels[$status]) ? $status_labels[$status] : ucfirst(str_replace('_', ' ', $status));
          $color = isset($status_colors[$status]) ? $status_colors[$status] : '#94a3b8';
          $icon = isset($status_meta[$status]['icon']) ? $status_meta[$status]['icon'] : 'bi-circle';
        ?>
        <div class="col kanban-col col-10 col-sm-8 col-md-6 col-lg-3">
          <div class="card shadow-sm kanban-column-card fade-in tb-column-card" data-column-status="<?php echo esc_view($status); ?>" style="--tb-col-accent:<?php echo esc_view($color, ENT_QUOTES, 'UTF-8'); ?>;">
            <div class="card-header kanban-column-header tb-column-header">
              <div class="d-flex align-items-center min-w-0 flex-grow-1">
                <i class="bi <?php echo esc_view($icon); ?> tb-column-icon me-2"></i>
                <h6 class="mb-0 fw-semibold text-truncate"><?php echo esc_view($label); ?></h6>
                <span class="badge rounded-pill tb-column-count ms-2" id="count-<?php echo esc_view($status); ?>"><?php echo count($items); ?></span>
              </div>
              <button class="btn btn-sm btn-light border kanban-col-toggle flex-shrink-0" type="button"
                onclick="toggleKanbanColumn('<?php echo esc_view($status); ?>', event)"
                title="Collapse column"
                aria-label="Toggle column <?php echo esc_view($label); ?>">
                <i class="bi bi-chevron-up kanban-col-toggle-icon"></i>
              </button>
            </div>
            <div class="card-body p-2">
              <div class="kanban-column tb-drop-zone" data-status="<?php echo esc_view($status); ?>" ondragover="event.preventDefault();" ondrop="handleDrop(event, this)">
                <?php if (empty($items)): ?>
                  <div class="tb-empty-column">
                    <i class="bi bi-inbox"></i>
                    <span>No tasks</span>
                    <small>Drop here</small>
                  </div>
                <?php endif; ?>
                <?php foreach ($items as $t): ?>
                  <?php
                    $assignee = $assigneeName($t);
                    $init = $initials($assignee);
                    $priority = isset($t->priority) ? $t->priority : 'medium';
                    $pr = isset($priority_meta[$priority]) ? $priority_meta[$priority] : $priority_meta['medium'];
                    $due = $formatDue($t);
                  ?>
                  <div class="kanban-card tb-task-card" draggable="true" ondragstart="handleDragStart(event)" data-id="<?php echo (int) $t->id; ?>" data-status="<?php echo esc_view($status); ?>" data-priority="<?php echo esc_view($priority); ?>" data-assignee="<?php echo esc_view($assignee); ?>" data-title="<?php echo esc_view($t->title); ?>">
                    <div class="tb-task-card-top">
                      <div class="tb-task-card-meta">
                        <span class="tb-task-id">#<?php echo (int) $t->id; ?></span>
                        <span class="tb-priority-pill <?php echo esc_view($pr['class']); ?>"><?php echo esc_view($pr['label']); ?></span>
                      </div>
                      <div class="kanban-card-actions tb-task-card-actions">
                        <a class="btn btn-sm btn-light border-0" href="<?php echo site_url('tasks/' . (int) $t->id); ?>" title="Open task">
                          <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <?php if ($can_edit_task): ?>
                        <a class="btn btn-sm btn-light border-0" href="<?php echo site_url('tasks/' . (int) $t->id . '/edit'); ?>" title="Edit">
                          <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                      </div>
                    </div>
                    <a class="tb-task-title" href="<?php echo site_url('tasks/' . (int) $t->id); ?>"><?php echo esc_view($t->title); ?></a>
                    <?php if (!empty($t->description)): ?>
                      <?php
                        $desc = trim(strip_tags((string) $t->description));
                        if ($desc !== '') {
                          echo '<p class="tb-task-desc">' . esc_view(strlen($desc) > 90 ? substr($desc, 0, 90) . '…' : $desc) . '</p>';
                        }
                      ?>
                    <?php endif; ?>
                    <div class="tb-task-card-footer">
                      <div class="tb-task-chips">
                        <?php if ($due['label'] !== ''): ?>
                          <span class="tb-chip tb-chip-due<?php echo $due['overdue'] ? ' is-overdue' : ''; ?>">
                            <i class="bi bi-calendar-event"></i>
                            <?php echo esc_view($due['label']); ?>
                          </span>
                        <?php endif; ?>
                      </div>
                      <div class="avatar avatar-bg tb-task-avatar" title="<?php echo esc_view($assignee !== '' ? $assignee : 'Unassigned'); ?>">
                        <?php echo esc_view($init); ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
(function () {
  var statusCodes = <?php echo json_encode(array_keys($columns), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  var draggedId = null;

  window.handleDragStart = function (e) {
    var card = e.target.closest('.kanban-card');
    if (!card) {
      return;
    }
    draggedId = card.dataset.id || null;
    card.classList.add('dragging');
    card.style.opacity = '0.55';
    e.dataTransfer.effectAllowed = 'move';
  };

  document.addEventListener('dragend', function (e) {
    var card = e.target.closest('.kanban-card');
    if (!card) {
      return;
    }
    card.classList.remove('dragging');
    card.style.opacity = '';
  });

  window.handleDrop = async function (e, column) {
    e.preventDefault();
    var status = column.getAttribute('data-status');
    if (!draggedId || !status) {
      return;
    }
    var card = document.querySelector('.kanban-card[data-id="' + draggedId + '"]');
    if (card) {
      card.style.pointerEvents = 'none';
    }
    try {
      var form = new FormData();
      form.append('id', draggedId);
      form.append('status', status);
      form.append('<?php echo $this->security->get_csrf_token_name(); ?>', '<?php echo $this->security->get_csrf_hash(); ?>');
      var res = await fetch('<?php echo site_url('tasks/update-status'); ?>', {
        method: 'POST',
        body: form,
        credentials: 'same-origin'
      });
      var json = await res.json();
      if (json && json.ok) {
        if (card) {
          card.dataset.status = status;
          column.prepend(card);
          var empty = column.querySelector('.tb-empty-column');
          if (empty) {
            empty.remove();
          }
        }
        updateColumnCounts();
      }
    } catch (err) {
    } finally {
      if (card) {
        card.style.opacity = '';
        card.style.pointerEvents = '';
      }
    }
  };

  function updateColumnCounts() {
    statusCodes.forEach(function (status) {
      var column = document.querySelector('.kanban-column[data-status="' + status + '"]');
      if (!column) {
        return;
      }
      var visibleCount = 0;
      column.querySelectorAll('.kanban-card').forEach(function (card) {
        if (card.style.display !== 'none') {
          visibleCount++;
        }
      });
      var badge = document.getElementById('count-' + status);
      if (badge) {
        badge.textContent = visibleCount;
      }
    });
  }

  window.toggleKanbanColumn = function (status, e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    var card = document.querySelector('.kanban-column-card[data-column-status="' + status + '"]');
    if (!card) {
      return;
    }
    card.classList.toggle('collapsed');
    var icon = card.querySelector('.kanban-col-toggle-icon');
    if (icon) {
      icon.className = card.classList.contains('collapsed') ? 'bi bi-chevron-down kanban-col-toggle-icon' : 'bi bi-chevron-up kanban-col-toggle-icon';
    }
  };

  var searchInput = document.getElementById('searchTasks');
  var clearBtn = document.getElementById('clearSearch');
  if (searchInput && clearBtn) {
    searchInput.addEventListener('input', function () {
      var searchTerm = this.value.toLowerCase().trim();
      document.querySelectorAll('.kanban-card').forEach(function (card) {
        var title = (card.dataset.title || '').toLowerCase();
        var assignee = (card.dataset.assignee || '').toLowerCase();
        card.style.display = (searchTerm === '' || title.indexOf(searchTerm) !== -1 || assignee.indexOf(searchTerm) !== -1) ? '' : 'none';
      });
      updateColumnCounts();
    });
    clearBtn.addEventListener('click', function () {
      searchInput.value = '';
      document.querySelectorAll('.kanban-card').forEach(function (card) {
        card.style.display = '';
      });
      updateColumnCounts();
    });
  }

  document.addEventListener('DOMContentLoaded', updateColumnCounts);
})();
</script>
</div>
<?php $this->load->view('partials/footer'); ?>
