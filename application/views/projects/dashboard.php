<?php $this->load->view('partials/header', [
  'title' => 'Project Dashboard — ' . (isset($project->name) ? $project->name : ''),
  'extra_css' => ['assets/css/tasks.css'],
]); ?>
<div class="container-fluid py-3 task-board-page project-dashboard-page">
<?php
$status_badges = array(
    'pending'     => 'secondary',
    'in_progress' => 'info',
    'completed'   => 'success',
    'blocked'     => 'danger',
);
$status_labels = array();
foreach ($status_rows as $sr) {
    $status_labels[(string) $sr->code] = (string) $sr->name;
}
$can_add_task = function_exists('has_module_access') && (has_module_access('tasks_add') || has_module_access('tasks'));
$can_edit_task = function_exists('has_module_access') && (has_module_access('tasks_edit') || has_module_access('tasks'));

$head_actions = '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('projects/dashboard') . '"><i class="bi bi-speedometer2 me-1"></i>All Projects</a>';
$head_actions .= '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('projects/' . (int) $project->id) . '"><i class="bi bi-eye me-1"></i>Details</a>';
if ($can_add_task) {
    $head_actions .= '<a class="btn btn-primary btn-sm" href="' . site_url('tasks/create?project_id=' . (int) $project->id) . '"><i class="bi bi-plus-lg me-1"></i>New Task</a>';
}
?>

<!-- Summary cards above project name -->
<div class="row g-3 mb-3">
  <?php foreach ($status_rows as $sr): ?>
    <?php
      $code = (string) $sr->code;
      $count = isset($stats[$code]) ? (int) $stats[$code] : 0;
      $badge = isset($status_badges[$code]) ? $status_badges[$code] : 'secondary';
      $label = isset($status_labels[$code]) ? $status_labels[$code] : $code;
    ?>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body py-3">
          <div class="text-muted small text-uppercase mb-1"><?php echo esc_view($label); ?></div>
          <div class="d-flex align-items-center justify-content-between">
            <span class="h4 mb-0 fw-bold"><?php echo $count; ?></span>
            <span class="badge bg-<?php echo esc_view($badge); ?> rounded-pill"><?php echo esc_view($label); ?></span>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="col-6 col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body py-3">
        <div class="text-muted small text-uppercase mb-1">Progress</div>
        <div class="d-flex align-items-center justify-content-between">
          <span class="h4 mb-0 fw-bold"><?php echo (int) $progress; ?>%</span>
          <span class="badge bg-success rounded-pill">Completed</span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$this->load->view('partials/oms_page_head', array(
    'title'        => (string) $project->name,
    'icon'         => 'bi-columns-gap',
    'subtitle'     => (string) $project->code . ' — tasks by status',
    'actions_html' => $head_actions,
));
?>

<div class="card shadow-soft task-board-toolbar mb-3">
  <div class="card-body py-2 px-3">
    <div class="task-board-toolbar-inner">
      <div class="task-board-search-wrap ms-auto">
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
            return 'NA';
        }
        $parts = preg_split('/\s+/', $text);
        $first = strtoupper(substr($parts[0], 0, 1));
        $last = isset($parts[count($parts) - 1]) ? strtoupper(substr($parts[count($parts) - 1], 0, 1)) : '';
        return $first . ($last && $last !== $first ? $last : '');
    };
  ?>
  <div class="kanban-scroll-wrap">
    <div class="row g-3 kanban-columns flex-nowrap flex-lg-wrap">
      <?php foreach ($columns as $status => $items): ?>
        <?php
          $label = isset($status_labels[$status]) ? $status_labels[$status] : ucfirst(str_replace('_', ' ', $status));
          $badge = isset($status_badges[$status]) ? $status_badges[$status] : 'secondary';
        ?>
        <div class="col kanban-col col-10 col-sm-8 col-md-6 col-lg-3">
          <div class="card shadow-sm kanban-column-card fade-in" data-column-status="<?php echo esc_view($status); ?>">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 kanban-column-header">
              <div class="d-flex align-items-center min-w-0">
                <div class="status-indicator status-<?php echo esc_view($status); ?> me-2 flex-shrink-0"></div>
                <h6 class="mb-0 fw-semibold text-truncate">
                  <?php echo esc_view($label); ?>
                  <span class="badge bg-<?php echo esc_view($badge); ?> ms-2" id="count-<?php echo esc_view($status); ?>"><?php echo count($items); ?></span>
                </h6>
              </div>
              <button class="btn btn-sm btn-outline-secondary kanban-col-toggle flex-shrink-0" type="button"
                onclick="toggleKanbanColumn('<?php echo esc_view($status); ?>', event)"
                title="Collapse column"
                aria-label="Toggle column <?php echo esc_view($label); ?>">
                <i class="bi bi-chevron-up kanban-col-toggle-icon"></i>
              </button>
            </div>
            <div class="card-body p-2">
              <div class="kanban-column" data-status="<?php echo esc_view($status); ?>" ondragover="event.preventDefault();" ondrop="handleDrop(event, this)">
                <?php if (empty($items)): ?>
                  <div class="d-flex flex-column align-items-center justify-content-center empty-hint xsmall empty-hint-placeholder">
                    <i class="bi bi-inbox text-muted mb-2" style="font-size: 2rem;"></i>
                    <span class="text-muted">No tasks</span>
                  </div>
                <?php endif; ?>
                <?php foreach ($items as $t): ?>
                  <?php
                    $assignee = $assigneeName($t);
                    $init = $initials($assignee);
                    $priority = isset($t->priority) ? $t->priority : 'medium';
                    $created_date = isset($t->created_at) ? date('M j', strtotime($t->created_at)) : '';
                  ?>
                  <div class="kanban-card" draggable="true" ondragstart="handleDragStart(event)" data-id="<?php echo (int) $t->id; ?>" data-status="<?php echo esc_view($status); ?>" data-priority="<?php echo esc_view($priority); ?>" data-assignee="<?php echo esc_view($assignee); ?>" data-title="<?php echo esc_view($t->title); ?>">
                    <div class="kanban-card-header">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                          <span class="priority-indicator priority-<?php echo esc_view($priority); ?>" title="Priority: <?php echo esc_view(ucfirst($priority)); ?>"></span>
                          <span class="task-id text-muted small fw-mono">#<?php echo (int) $t->id; ?></span>
                        </div>
                        <div class="kanban-card-actions">
                          <a class="btn btn-sm btn-light border" href="<?php echo site_url('tasks/' . (int) $t->id); ?>" title="View details">
                            <i class="bi bi-box-arrow-up-right"></i>
                          </a>
                          <?php if ($can_edit_task): ?>
                          <a class="btn btn-sm btn-primary" href="<?php echo site_url('tasks/' . (int) $t->id . '/edit'); ?>" title="Edit task">
                            <i class="bi bi-pencil"></i>
                          </a>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <div class="kanban-card-body">
                      <h6 class="task-title mb-2"><?php echo esc_view($t->title); ?></h6>
                      <?php if (!empty($t->description)): ?>
                        <div class="task-description mb-2">
                          <?php
                            $desc = trim(strip_tags((string) $t->description));
                            if ($desc !== '') {
                              echo esc_view(strlen($desc) > 80 ? substr($desc, 0, 80) . '...' : $desc);
                            }
                          ?>
                        </div>
                      <?php endif; ?>
                      <span class="badge bg-<?php echo esc_view($badge); ?>"><?php echo esc_view($label); ?></span>
                    </div>
                    <div class="kanban-card-footer">
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center flex-wrap gap-1">
                          <?php if ($created_date): ?>
                            <span class="date-chip text-muted">
                              <i class="bi bi-calendar3 me-1"></i><?php echo esc_view($created_date); ?>
                            </span>
                          <?php endif; ?>
                        </div>
                        <div class="avatar avatar-bg" title="<?php echo esc_view($assignee !== '' ? $assignee : 'Unassigned'); ?>">
                          <?php echo esc_view($init); ?>
                        </div>
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
    draggedId = e.target && e.target.dataset ? e.target.dataset.id : null;
    e.target.style.opacity = '0.5';
    e.dataTransfer.effectAllowed = 'move';
  };

  document.addEventListener('dragend', function (e) {
    if (e.target.classList && e.target.classList.contains('kanban-card')) {
      e.target.style.opacity = '';
    }
  });

  window.handleDrop = async function (e, column) {
    e.preventDefault();
    var status = column.getAttribute('data-status');
    if (!draggedId || !status) {
      return;
    }
    var card = document.querySelector('.kanban-card[data-id="' + draggedId + '"]');
    if (card) {
      card.style.opacity = '0.7';
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
    var btn = card.querySelector('.kanban-col-toggle');
    var icon = card.querySelector('.kanban-col-toggle-icon');
    if (btn && icon) {
      var collapsed = card.classList.contains('collapsed');
      icon.className = collapsed ? 'bi bi-chevron-down kanban-col-toggle-icon' : 'bi bi-chevron-up kanban-col-toggle-icon';
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
