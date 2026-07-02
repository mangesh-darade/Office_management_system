<?php $this->load->view('partials/header', [
  'title' => 'Task Board',
  'extra_css' => ['assets/css/tasks.css'],
]); ?>
<div class="container-fluid py-2 task-board-page task-board-pro task-board-compact">
<?php
$can_add = function_exists('has_module_access') && (has_module_access('tasks_add') || has_module_access('tasks'));
$can_edit = function_exists('has_module_access') && (has_module_access('tasks_edit') || has_module_access('tasks'));
$can_delete = function_exists('has_module_access') && (has_module_access('tasks_delete') || has_module_access('tasks'));

$head_actions = '';
if (function_exists('has_module_access') && (has_module_access('tasks') || has_module_access('tasks_list'))) {
  $head_actions .= '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('tasks/my-dashboard') . '"><i class="bi bi-people me-1"></i><span class="d-none d-md-inline">Team Dashboard</span></a>';
}
$head_actions .= '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('tasks') . '"><i class="bi bi-list me-1"></i><span class="d-none d-md-inline">List</span></a>';
if ($can_add) {
  $head_actions .= '<a class="btn btn-primary btn-sm" href="' . site_url('tasks/create') . '"><i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">New </span>Task</a>';
}

$has_filters = (isset($filter_project_id) && $filter_project_id !== '')
  || (isset($filter_assigned_to) && $filter_assigned_to !== '')
  || (isset($filter_priority) && $filter_priority !== '');

$labels = array(
  'pending' => 'Pending',
  'in_progress' => 'In Progress',
  'completed' => 'Completed',
  'blocked' => 'Blocked',
);
$status_meta = array(
  'pending' => array('badge' => 'secondary', 'color' => '#6b7280', 'icon' => 'bi-hourglass-split'),
  'in_progress' => array('badge' => 'info', 'color' => '#0284c7', 'icon' => 'bi-play-circle'),
  'completed' => array('badge' => 'success', 'color' => '#16a34a', 'icon' => 'bi-check-circle'),
  'blocked' => array('badge' => 'danger', 'color' => '#dc2626', 'icon' => 'bi-slash-circle'),
);
$priority_meta = array(
  'urgent' => array('label' => 'Urgent', 'class' => 'tb-priority-urgent'),
  'high' => array('label' => 'High', 'class' => 'tb-priority-high'),
  'medium' => array('label' => 'Medium', 'class' => 'tb-priority-medium'),
  'low' => array('label' => 'Low', 'class' => 'tb-priority-low'),
);

$total_tasks = isset($total_tasks) ? (int) $total_tasks : 0;
$board_stats = isset($board_stats) ? $board_stats : array();
$board_progress = isset($board_progress) ? (int) $board_progress : 0;
?>

<div class="task-board-summary mb-2">
  <div class="task-board-summary-card">
    <span class="task-board-summary-label">Total Tasks</span>
    <strong class="task-board-summary-value"><?php echo $total_tasks; ?></strong>
  </div>
  <?php foreach ($labels as $code => $label): ?>
    <?php $count = isset($board_stats[$code]) ? (int) $board_stats[$code] : 0; ?>
    <div class="task-board-summary-card" style="--tb-accent:<?php echo esc_view($status_meta[$code]['color'], ENT_QUOTES, 'UTF-8'); ?>;">
      <span class="task-board-summary-label"><?php echo esc_view($label); ?></span>
      <strong class="task-board-summary-value"><?php echo $count; ?></strong>
    </div>
  <?php endforeach; ?>
  <div class="task-board-summary-card task-board-summary-progress">
    <span class="task-board-summary-label">Completed</span>
    <strong class="task-board-summary-value"><?php echo $board_progress; ?>%</strong>
  </div>
</div>

<?php
$this->load->view('partials/oms_page_head', array(
  'title' => 'Task Board',
  'icon' => 'bi-kanban',
  'subtitle' => 'Drag cards to change status',
  'actions_html' => $head_actions,
  'mb' => 'mb-2',
));
?>

<div class="card shadow-soft task-board-toolbar mb-2">
  <div class="card-body py-2 px-3">
    <div class="task-board-toolbar-inner">
      <button class="btn btn-outline-secondary btn-sm d-md-none task-board-filters-toggle<?php echo $has_filters ? '' : ' collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#taskBoardFilters" aria-expanded="<?php echo $has_filters ? 'true' : 'false'; ?>" aria-controls="taskBoardFilters">
        <i class="bi bi-funnel me-1"></i>Filters<?php if ($has_filters): ?><span class="badge bg-primary ms-1">On</span><?php endif; ?>
      </button>

      <div class="collapse<?php echo $has_filters ? ' show' : ''; ?> d-md-block task-board-filters-wrap" id="taskBoardFilters">
        <form method="get" class="task-board-filters oms-filter-row">
          <?php if (!empty($projects)): ?>
          <select name="project_id" class="form-select form-select-sm" aria-label="Filter by project" onchange="this.form.submit()">
            <option value="">All Projects</option>
            <?php foreach ($projects as $p): ?>
              <option value="<?php echo (int) $p->id; ?>" <?php echo (isset($filter_project_id) && $filter_project_id == $p->id) ? 'selected' : ''; ?>><?php echo esc_view($p->name); ?></option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>

          <?php if (!empty($assignees)): ?>
          <select name="assigned_to" class="form-select form-select-sm" aria-label="Filter by assignee" onchange="this.form.submit()">
            <option value="">All Assignees</option>
            <?php foreach ($assignees as $u): ?>
              <option value="<?php echo (int) $u->id; ?>" <?php echo (isset($filter_assigned_to) && $filter_assigned_to == $u->id) ? 'selected' : ''; ?>><?php echo esc_view($u->name ?: $u->email); ?></option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>

          <select name="priority" class="form-select form-select-sm" aria-label="Filter by priority" onchange="this.form.submit()">
            <option value="">All Priorities</option>
            <?php foreach (array('low', 'medium', 'high', 'urgent') as $pr): ?>
              <option value="<?php echo $pr; ?>" <?php echo (isset($filter_priority) && $filter_priority === $pr) ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option>
            <?php endforeach; ?>
          </select>

          <?php if ($has_filters): ?>
          <a href="<?php echo site_url('tasks/board'); ?>" class="btn btn-outline-secondary btn-sm task-board-clear-filters" title="Clear filters"><i class="bi bi-x-circle"></i><span class="d-none d-lg-inline ms-1">Clear</span></a>
          <?php endif; ?>
        </form>
      </div>

      <div class="task-board-search-wrap">
        <div class="input-group input-group-sm task-board-search">
          <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
          <input type="text" class="form-control" id="searchTasks" placeholder="Search title, project, assignee…" aria-label="Quick search tasks">
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
      $ts = strtotime($due);
      if ($ts === false) {
        return array('label' => '', 'overdue' => false);
      }
      $overdue = ($due < date('Y-m-d') && (!isset($task->status) || $task->status !== 'completed'));
      return array('label' => date('M j', $ts), 'overdue' => $overdue);
    };
  ?>
  <div class="kanban-scroll-wrap">
    <div class="row g-2 kanban-columns flex-nowrap flex-lg-wrap">
      <?php foreach ($columns as $status => $items): ?>
        <?php
          $meta = isset($status_meta[$status]) ? $status_meta[$status] : $status_meta['pending'];
          $label = isset($labels[$status]) ? $labels[$status] : ucfirst(str_replace('_', ' ', $status));
        ?>
        <div class="col kanban-col col-10 col-sm-8 col-md-6 col-lg-3">
          <div class="card shadow-sm kanban-column-card fade-in tb-column-card tb-column-<?php echo esc_view($status); ?>" data-column-status="<?php echo esc_view($status); ?>" style="--tb-col-accent:<?php echo esc_view($meta['color'], ENT_QUOTES, 'UTF-8'); ?>;">
            <div class="card-header kanban-column-header tb-column-header">
              <div class="d-flex align-items-center min-w-0 flex-grow-1">
                <i class="bi <?php echo esc_view($meta['icon']); ?> tb-column-icon me-2"></i>
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
            <div class="card-body p-1 px-2 pb-2">
              <div class="kanban-column tb-drop-zone" data-status="<?php echo esc_view($status); ?>" ondragover="event.preventDefault();" ondrop="handleDrop(event, this)">
                <?php if (empty($items)): ?>
                  <div class="tb-empty-column">
                    <i class="bi bi-inbox"></i>
                    <span>Empty</span>
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
                  <div class="kanban-card tb-task-card" draggable="true" ondragstart="handleDragStart(event)" data-id="<?php echo (int) $t->id; ?>" data-status="<?php echo esc_view($status); ?>" data-priority="<?php echo esc_view($priority); ?>" data-project="<?php echo esc_view(isset($t->project_name) ? $t->project_name : ''); ?>" data-assignee="<?php echo esc_view($assignee); ?>" data-title="<?php echo esc_view($t->title); ?>">
                    <div class="tb-task-card-top">
                      <div class="tb-task-card-meta">
                        <span class="tb-task-id">#<?php echo (int) $t->id; ?></span>
                        <span class="tb-priority-pill <?php echo esc_view($pr['class']); ?>"><?php echo esc_view($pr['label']); ?></span>
                      </div>
                      <div class="kanban-card-actions tb-task-card-actions">
                        <button class="btn btn-sm btn-light border-0" type="button" onclick="showTaskPreview(<?php echo (int) $t->id; ?>)" title="Quick preview">
                          <i class="bi bi-eye"></i>
                        </button>
                        <a class="btn btn-sm btn-light border-0" href="<?php echo site_url('tasks/' . (int) $t->id); ?>" title="Open task">
                          <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <?php if ($can_edit): ?>
                        <a class="btn btn-sm btn-light border-0" href="<?php echo site_url('tasks/' . (int) $t->id . '/edit'); ?>" title="Edit">
                          <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($can_delete): ?>
                        <div class="dropdown">
                          <button class="btn btn-sm btn-light border-0" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" title="More">
                            <i class="bi bi-three-dots-vertical"></i>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                              <form method="post" action="<?php echo site_url('tasks/' . (int) $t->id . '/delete'); ?>" class="m-0" onsubmit="return confirm('Delete this task?');">
                                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                                <button type="submit" class="dropdown-item text-danger">
                                  <i class="bi bi-trash me-2"></i>Delete
                                </button>
                              </form>
                            </li>
                          </ul>
                        </div>
                        <?php endif; ?>
                      </div>
                    </div>
                    <a class="tb-task-title" href="<?php echo site_url('tasks/' . (int) $t->id); ?>"><?php echo esc_view($t->title); ?></a>
                    <div class="tb-task-card-footer">
                      <div class="tb-task-chips">
                        <?php if (!empty($t->project_name)): ?>
                          <span class="tb-chip tb-chip-project" title="<?php echo esc_view($t->project_name, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi bi-folder2-open"></i>
                            <span><?php echo esc_view(mb_strlen($t->project_name) > 14 ? mb_substr($t->project_name, 0, 14) . '…' : $t->project_name); ?></span>
                          </span>
                        <?php endif; ?>
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

<div class="modal fade" id="taskPreviewModal" tabindex="-1" aria-labelledby="taskPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title" id="taskPreviewModalLabel"><i class="bi bi-eye me-2"></i>Task Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="taskPreviewContent">
        <div class="text-center py-4">
          <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
          <p class="mt-2 text-muted mb-0">Loading task details…</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="editTaskBtn"><i class="bi bi-pencil me-1"></i>Edit</button>
        <button type="button" class="btn btn-outline-primary" id="viewTaskBtn"><i class="bi bi-box-arrow-up-right me-1"></i>Full Details</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var draggedId = null;
  var currentPreviewTask = null;
  var statusCodes = <?php echo json_encode(array_keys($labels), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

  window.showTaskPreview = async function (taskId) {
    currentPreviewTask = taskId;
    var modal = new bootstrap.Modal(document.getElementById('taskPreviewModal'));
    var content = document.getElementById('taskPreviewContent');
    content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted mb-0">Loading task details…</p></div>';
    modal.show();
    try {
      var res = await fetch('<?php echo site_url('tasks/'); ?>' + taskId + '/preview', {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'text/html' }
      });
      if (!res.ok) {
        throw new Error('HTTP ' + res.status);
      }
      content.innerHTML = await res.text();
      document.getElementById('editTaskBtn').onclick = function () {
        window.location.href = '<?php echo site_url('tasks/'); ?>' + taskId + '/edit';
      };
      document.getElementById('viewTaskBtn').onclick = function () {
        window.location.href = '<?php echo site_url('tasks/'); ?>' + taskId;
      };
    } catch (error) {
      content.innerHTML = '<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Failed to load task details. Please try again.</div>';
    }
  };

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
        showNotification('Task moved to ' + status.replace('_', ' '), 'success');
      } else {
        showNotification((json && json.error) ? json.error : 'Could not update status', 'danger');
      }
    } catch (err) {
      showNotification('Network error. Please try again.', 'danger');
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

  function showNotification(message, type) {
    var alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed shadow-sm';
    alertDiv.style.cssText = 'top: 1.25rem; right: 1.25rem; z-index: 9999; min-width: 280px; max-width: 90vw;';
    alertDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    document.body.appendChild(alertDiv);
    setTimeout(function () {
      if (alertDiv.parentNode) {
        alertDiv.remove();
      }
    }, 4500);
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
        var project = (card.dataset.project || '').toLowerCase();
        var assignee = (card.dataset.assignee || '').toLowerCase();
        card.style.display = (searchTerm === '' || title.indexOf(searchTerm) !== -1 || project.indexOf(searchTerm) !== -1 || assignee.indexOf(searchTerm) !== -1) ? '' : 'none';
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
