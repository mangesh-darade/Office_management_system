<?php
$task = isset($task) ? $task : null;
if (!$task) {
    show_404();
}
$history = isset($history) ? $history : array();
$can_edit = function_exists('has_module_access') && (has_module_access('tasks_edit') || has_module_access('tasks'));
$can_delete = function_exists('has_module_access') && (has_module_access('tasks_delete') || has_module_access('tasks'));
$can_note = function_exists('has_module_access') && (has_module_access('tasks_view') || has_module_access('tasks_list') || has_module_access('tasks'));
$can_whatsapp = function_exists('has_module_access') && has_module_access('whatsapp');

$getDisplayName = function ($user) {
    $name = '';
    if (isset($user->emp_name) && trim((string) $user->emp_name) !== '') {
        $name = $user->emp_name;
    } elseif (isset($user->full_name) && trim((string) $user->full_name) !== '') {
        $name = $user->full_name;
    } elseif (isset($user->name) && trim((string) $user->name) !== '') {
        $name = $user->name;
    } elseif (isset($user->email)) {
        $name = $user->email;
    }
    return trim((string) $name);
};

$action_label = function ($action) {
    $map = array(
        'created' => 'Created',
        'updated' => 'Updated',
        'status_changed' => 'Status',
        'status' => 'Status',
        'assigned' => 'Reassigned',
        'reassigned' => 'Reassigned',
        'attachment_added' => 'Attachment',
        'attachment' => 'Attachment',
        'note' => 'Note',
        'comment' => 'Note',
        'commented' => 'Note',
    );
    $key = strtolower((string) $action);
    return isset($map[$key]) ? $map[$key] : ucfirst(str_replace('_', ' ', (string) $action));
};

$status_pill = function ($s) {
    $s = strtolower((string) $s);
    if ($s === 'pending') {
        return 'defect-pill defect-pill--muted';
    }
    if ($s === 'in_progress') {
        return 'defect-pill defect-pill--progress';
    }
    if ($s === 'completed') {
        return 'defect-pill defect-pill--fixed';
    }
    if ($s === 'blocked') {
        return 'defect-pill defect-pill--critical';
    }
    return 'defect-pill defect-pill--muted';
};

$priority_pill = function ($p) {
    $p = strtolower((string) $p);
    if ($p === 'urgent' || $p === 'high') {
        return 'defect-pill defect-pill--high';
    }
    if ($p === 'medium') {
        return 'defect-pill defect-pill--medium';
    }
    if ($p === 'low') {
        return 'defect-pill defect-pill--low';
    }
    return 'defect-pill defect-pill--muted';
};

$assigneeName = $getDisplayName($task);
if (isset($assignee_names) && is_array($assignee_names) && !empty($assignee_names)) {
    $parts = array();
    $seen = array();
    if (trim((string) $assigneeName) !== '') {
        $parts[] = trim((string) $assigneeName);
        $seen[strtolower(trim((string) $assigneeName))] = true;
    }
    foreach ($assignee_names as $n) {
        $n = trim((string) $n);
        if ($n === '' || isset($seen[strtolower($n)])) {
            continue;
        }
        $seen[strtolower($n)] = true;
        $parts[] = $n;
    }
    if (!empty($parts)) {
        $assigneeName = implode(', ', $parts);
    }
}
if ($assigneeName === '') {
    $assigneeName = 'Unassigned';
}

$creatorName = $getDisplayName((object) array(
    'emp_name' => isset($task->creator_name) ? $task->creator_name : '',
    'full_name' => isset($task->creator_full_name) ? $task->creator_full_name : '',
    'name' => isset($task->creator_name) ? $task->creator_name : '',
    'email' => isset($task->creator_email) ? $task->creator_email : '',
));

$project_id = !empty($task->project_id) ? (int) $task->project_id : 0;
$project_name = isset($task->project_name) ? trim((string) $task->project_name) : '';
$back_url = $project_id > 0 ? site_url('projects/' . $project_id) : site_url('tasks');
$task_title = isset($task->title) ? (string) $task->title : ('Task #' . (int) $task->id);
$task_status = isset($task->status) ? (string) $task->status : 'pending';
$task_priority = isset($task->priority) ? (string) $task->priority : '';
$due_raw = isset($task->due_date) ? trim((string) $task->due_date) : '';
$due_overdue = false;
if ($due_raw !== '' && $due_raw !== '0000-00-00') {
    $due_overdue = strtotime($due_raw) < strtotime('today');
}

$this->load->view('partials/header', array(
    'title' => 'Task #' . (int) $task->id,
    'extra_css' => array('assets/css/defects-form.css', 'assets/css/tasks.css'),
));
?>
<script>document.body.classList.add('defect-view-active');</script>

<div class="defect-view-simple">
  <div class="defect-view-toolbar mb-3">
    <a href="<?php echo esc_view($back_url); ?>" class="btn btn-sm btn-outline-secondary defect-view-back" title="Back">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div class="defect-view-heading min-w-0">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="h5 mb-0 text-truncate" title="<?php echo esc_view($task_title); ?>">
          <?php echo esc_view($task_title !== '' ? $task_title : 'Untitled task'); ?>
        </h1>
        <span class="<?php echo esc_view($status_pill($task_status)); ?>"><?php echo esc_view(ucwords(str_replace('_', ' ', $task_status))); ?></span>
        <?php if ($task_priority !== ''): ?>
          <span class="<?php echo esc_view($priority_pill($task_priority)); ?>"><?php echo esc_view(ucfirst($task_priority)); ?></span>
        <?php endif; ?>
        <?php if ($due_overdue): ?>
          <span class="badge text-bg-danger">Overdue</span>
        <?php endif; ?>
      </div>
      <div class="small text-muted mt-1">
        <span class="font-monospace fw-semibold text-body">#<?php echo (int) $task->id; ?></span>
        <?php if ($project_id > 0 && $project_name !== ''): ?>
          <span class="mx-1">·</span>
          <a class="text-decoration-none" href="<?php echo site_url('projects/' . $project_id); ?>"><?php echo esc_view($project_name); ?></a>
        <?php endif; ?>
      </div>
    </div>
    <div class="defect-view-actions d-flex align-items-center gap-1 flex-shrink-0">
      <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('tasks'); ?>" title="List"><i class="bi bi-list-ul"></i></a>
      <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('tasks/board'); ?>" title="Board"><i class="bi bi-kanban"></i></a>
      <?php if ($can_whatsapp): ?>
        <button type="button" class="btn btn-sm btn-outline-success" title="Send via WhatsApp" onclick="sendTaskViaWhatsApp(<?php echo (int) $task->id; ?>)">
          <i class="bi bi-whatsapp"></i>
        </button>
      <?php endif; ?>
      <?php if ($can_edit): ?>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('tasks/' . (int) $task->id . '/edit'); ?>" title="Edit"><i class="bi bi-pencil"></i></a>
      <?php endif; ?>
      <?php if ($can_delete): ?>
        <form method="post" action="<?php echo site_url('tasks/' . (int) $task->id . '/delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this task?');">
          <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
          <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 mb-2"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 mb-2"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php if ($can_edit): ?>
  <div class="card mb-2">
    <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center gap-2">
      <span class="small text-muted fw-semibold me-1">Status</span>
      <div class="btn-group btn-group-sm" role="group" aria-label="Update status">
        <button type="button" class="btn btn-outline-secondary<?php echo $task_status === 'pending' ? ' active' : ''; ?>" onclick="updateTaskStatus('pending')">Pending</button>
        <button type="button" class="btn btn-outline-secondary<?php echo $task_status === 'in_progress' ? ' active' : ''; ?>" onclick="updateTaskStatus('in_progress')">In Progress</button>
        <button type="button" class="btn btn-outline-secondary<?php echo $task_status === 'completed' ? ' active' : ''; ?>" onclick="updateTaskStatus('completed')">Complete</button>
        <button type="button" class="btn btn-outline-secondary<?php echo $task_status === 'blocked' ? ' active' : ''; ?>" onclick="updateTaskStatus('blocked')">Blocked</button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="row g-2">
    <div class="col-12 col-lg-8">
      <div class="card mb-2">
        <div class="card-body py-2 px-3">
          <div class="small text-muted fw-semibold mb-1">Description</div>
          <div class="defect-rich-content small mb-0">
            <?php if (!empty($task->description)): ?>
              <?php
                $allowed = '<p><br><strong><em><b><i><u><s><del><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre><span>';
                echo strip_tags((string) $task->description, $allowed);
              ?>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </div>
          <?php if (!empty($task->reference_url)): ?>
            <div class="mt-2 pt-2 border-top">
              <?php $this->load->view('partials/reference_url_display', array('reference_url' => $task->reference_url, 'wrapper_class' => 'mb-0')); ?>
            </div>
          <?php endif; ?>
          <?php if (property_exists($task, 'attachment_path') && !empty($task->attachment_path)): ?>
            <div class="mt-2 pt-2 border-top small">
              <a href="<?php echo base_url($task->attachment_path); ?>" target="_blank" rel="noopener">
                <i class="bi bi-paperclip me-1"></i>Download attachment
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mb-2" id="history">
        <div class="card-body py-2 px-3">
          <?php if ($can_note): ?>
            <div class="small text-muted fw-semibold mb-1">Save note</div>
            <form method="post" action="<?php echo site_url('tasks/add-comment/' . (int) $task->id); ?>" class="mb-3">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <textarea name="note" id="taskHistoryNote" class="form-control form-control-sm mb-2" rows="2" placeholder="Add a note to history…"></textarea>
              <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Save note</button>
            </form>
          <?php endif; ?>

          <div class="d-flex align-items-center justify-content-between mb-1">
            <div class="small text-muted fw-semibold">History</div>
            <span class="text-muted small"><?php echo count($history); ?></span>
          </div>

          <?php if (empty($history)): ?>
            <p class="text-muted small mb-0">No history yet.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0 align-middle defect-history-grid">
                <thead class="table-light">
                  <tr>
                    <th class="text-start" style="width:9.5rem;">Date</th>
                    <th>Comments</th>
                    <th style="width:9rem;">Added By</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($history as $h): ?>
                    <?php
                      $who = ($h->user_name !== '') ? $h->user_name : 'System';
                      $changed = trim((string) $h->detail);
                      if ($changed === '') {
                          $changed = $action_label($h->action);
                      } elseif (strtolower((string) $h->action) !== 'note' && strtolower((string) $h->action) !== 'comment' && strtolower((string) $h->action) !== 'commented') {
                          if (strpos($changed, ':') === false && strpos($changed, '→') === false) {
                              $changed = $action_label($h->action) . ': ' . $changed;
                          }
                      }
                      $parts = preg_split('/\s*;\s*/', $changed);
                    ?>
                    <tr>
                      <td class="small text-nowrap text-muted text-start"><?php echo esc_view($h->created_at); ?></td>
                      <td class="small">
                        <?php if (count($parts) > 1): ?>
                          <ul class="mb-0 ps-3 defect-history-changes">
                            <?php foreach ($parts as $part): ?>
                              <?php if (trim($part) === '') { continue; } ?>
                              <li><?php echo esc_view(trim($part)); ?></li>
                            <?php endforeach; ?>
                          </ul>
                        <?php else: ?>
                          <?php echo nl2br(esc_view($changed)); ?>
                        <?php endif; ?>
                      </td>
                      <td class="small fw-semibold"><?php echo esc_view($who); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card mb-2">
        <div class="card-body py-2 px-3">
          <div class="small text-muted fw-semibold mb-2">Details</div>
          <table class="table table-sm mb-0 defect-simple-meta">
            <tbody>
              <tr><th>Project</th><td><?php
                if ($project_id > 0) {
                    echo '<a href="' . site_url('projects/' . $project_id) . '" class="text-decoration-none">' . esc_view($project_name !== '' ? $project_name : ('#' . $project_id)) . '</a>';
                } else {
                    echo '—';
                }
              ?></td></tr>
              <?php if (!empty($task->requirement_id)): ?>
              <tr><th>Requirement</th><td>
                <a href="<?php echo site_url('requirements/view/' . (int) $task->requirement_id); ?>" class="text-decoration-none">
                  <?php echo esc_view(!empty($task->requirement_number) ? $task->requirement_number : ('REQ #' . (int) $task->requirement_id)); ?>
                </a>
              </td></tr>
              <?php endif; ?>
              <tr><th>Status</th><td><?php echo esc_view(ucwords(str_replace('_', ' ', $task_status))); ?></td></tr>
              <tr><th>Priority</th><td><?php echo esc_view($task_priority !== '' ? ucfirst($task_priority) : '—'); ?></td></tr>
              <tr><th>Assignee</th><td><?php echo esc_view($assigneeName); ?></td></tr>
              <tr><th>Due</th><td><?php echo ($due_raw !== '' && $due_raw !== '0000-00-00') ? esc_view($due_raw) : '—'; ?></td></tr>
              <tr><th>Start</th><td><?php echo (!empty($task->start_date) && $task->start_date !== '0000-00-00') ? esc_view($task->start_date) : '—'; ?></td></tr>
              <tr><th>Estimate</th><td><?php echo function_exists('estimate_hours_display') ? esc_view(estimate_hours_display(isset($task->estimate_hours) ? $task->estimate_hours : null)) : '—'; ?></td></tr>
              <tr><th>Actual</th><td><?php echo function_exists('actual_hours_display') ? esc_view(actual_hours_display(isset($task->actual_hours) ? $task->actual_hours : null)) : '—'; ?></td></tr>
              <tr><th>Created by</th><td><?php echo esc_view($creatorName !== '' ? $creatorName : '—'); ?></td></tr>
              <tr><th>Created</th><td><?php echo !empty($task->created_at) ? esc_view($task->created_at) : '—'; ?></td></tr>
              <tr><th>Updated</th><td><?php echo !empty($task->updated_at) ? esc_view($task->updated_at) : '—'; ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function _csrfParam() {
  var m = document.cookie.match(/(?:^|;\s*)ci_csrf_token=([^;]*)/);
  return m ? '&<?php echo $this->security->get_csrf_token_name(); ?>=' + encodeURIComponent(decodeURIComponent(m[1])) : '';
}

function updateTaskStatus(status) {
  var taskId = <?php echo (int) $task->id; ?>;
  var estimate = <?php echo json_encode(isset($task->estimate_hours) ? (string) $task->estimate_hours : ''); ?>;
  var prevStatus = <?php echo json_encode(isset($task->status) ? (string) $task->status : ''); ?>;

  function doUpdate(actualHours) {
    var body = 'id=' + taskId + '&status=' + encodeURIComponent(status) + _csrfParam();
    if (actualHours != null && actualHours !== undefined) {
      body += '&actual_hours=' + encodeURIComponent(String(actualHours));
    }
    fetch('<?php echo site_url('tasks/update-status'); ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body,
      credentials: 'same-origin'
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
      if (data.ok) {
        showNotification('Task status updated', 'success');
        setTimeout(function() { location.reload(); }, 700);
      } else if (data.need_actual_hours && window.omsActualHours) {
        window.omsActualHours.prompt({ estimate: estimate }).then(function (hours) {
          if (hours === null) { return; }
          doUpdate(hours);
        });
      } else {
        showNotification(data.error || 'Failed to update status', 'danger');
      }
    })
    .catch(function() {
      showNotification('Network error. Please try again.', 'danger');
    });
  }

  if (window.omsActualHours && window.omsActualHours.isCompleteStatus(status)
      && !window.omsActualHours.isCompleteStatus(prevStatus)) {
    window.omsActualHours.prompt({ estimate: estimate }).then(function (hours) {
      if (hours === null) { return; }
      doUpdate(hours);
    });
    return;
  }
  doUpdate(undefined);
}

function showNotification(message, type) {
  type = type || 'info';
  var alertDiv = document.createElement('div');
  alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed';
  alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 260px;';
  alertDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
  document.body.appendChild(alertDiv);
  setTimeout(function() { alertDiv.remove(); }, 4000);
}

function sendTaskViaWhatsApp(taskId) {
  if (!confirm('Send this task notification via WhatsApp to the assigned employee?')) {
    return;
  }
  fetch('<?php echo site_url('whatsapp/send-task'); ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'task_id=' + taskId + _csrfParam(),
    credentials: 'same-origin'
  })
  .then(function(response) { return response.json(); })
  .then(function(data) {
    if (data.success) {
      showNotification(data.message, 'success');
    } else {
      showNotification(data.message || 'Failed', 'danger');
    }
  })
  .catch(function() {
    showNotification('Network error. Please try again.', 'danger');
  });
}
</script>

<?php $this->load->view('partials/footer'); ?>
