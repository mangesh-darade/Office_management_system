<?php
$this->load->view('partials/header', array(
  'title' => 'Task Details',
  'extra_css' => array('assets/css/tasks.css'),
));

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

$getInitials = function ($text) {
  $text = trim((string) $text);
  if ($text === '') {
    return 'NA';
  }
  $parts = preg_split('/\s+/', $text);
  $first = strtoupper(substr($parts[0], 0, 1));
  $last = isset($parts[count($parts) - 1]) ? strtoupper(substr($parts[count($parts) - 1], 0, 1)) : '';
  return $first . ($last && $last !== $first ? $last : '');
};

$status_colors = array(
  'pending' => 'secondary',
  'in_progress' => 'info',
  'completed' => 'success',
  'blocked' => 'danger',
);
$priority_colors = array(
  'urgent' => 'dark',
  'high' => 'danger',
  'medium' => 'warning',
  'low' => 'success',
);

$task_status = isset($task->status) ? (string) $task->status : 'pending';
$status_class = isset($status_colors[$task_status]) ? $status_colors[$task_status] : 'secondary';
$task_priority = isset($task->priority) ? (string) $task->priority : '';
$priority_class = isset($priority_colors[$task_priority]) ? $priority_colors[$task_priority] : 'secondary';

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
$creatorName = $getDisplayName((object) array(
  'emp_name' => isset($task->creator_name) ? $task->creator_name : '',
  'full_name' => isset($task->creator_full_name) ? $task->creator_full_name : '',
  'name' => isset($task->creator_name) ? $task->creator_name : '',
  'email' => isset($task->creator_email) ? $task->creator_email : '',
));

$can_edit = function_exists('has_module_access') && (has_module_access('tasks_edit') || has_module_access('tasks'));
$can_delete = function_exists('has_module_access') && (has_module_access('tasks_delete') || has_module_access('tasks'));
$can_whatsapp = function_exists('has_module_access') && has_module_access('whatsapp');

$project_id = !empty($task->project_id) ? (int) $task->project_id : 0;
$project_name = isset($task->project_name) ? trim((string) $task->project_name) : '';
$back_url = $project_id > 0 ? site_url('projects/' . $project_id) : site_url('tasks');
$back_title = $project_id > 0 ? 'Back to Project' : 'Back to Tasks';
$task_title = isset($task->title) ? (string) $task->title : ('Task #' . (int) $task->id);

$due_raw = isset($task->due_date) ? trim((string) $task->due_date) : '';
$due_overdue = false;
$due_soon = false;
if ($due_raw !== '' && $due_raw !== '0000-00-00') {
  $due_ts = strtotime($due_raw);
  $due_overdue = $due_ts < strtotime('today');
  $due_soon = !$due_overdue && $due_ts <= strtotime('+3 days');
}
?>

<div class="container-fluid py-1 px-2 task-detail-page">
  <?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success py-1 px-2 mb-1 small"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger py-1 px-2 mb-1 small"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <div class="task-detail-toolbar mb-1">
    <a class="task-detail-back" href="<?php echo esc_view($back_url, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo esc_view($back_title, ENT_QUOTES, 'UTF-8'); ?>">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div class="task-detail-title-row">
      <a class="task-detail-crumb" href="<?php echo site_url('tasks'); ?>">Tasks</a>
      <?php if ($project_id > 0 && $project_name !== ''): ?>
      <span class="task-detail-sep" aria-hidden="true">/</span>
      <a class="task-detail-crumb" href="<?php echo site_url('projects/' . $project_id); ?>"><?php echo esc_view($project_name); ?></a>
      <?php endif; ?>
      <span class="task-detail-sep" aria-hidden="true">/</span>
      <h1 class="task-detail-name" title="<?php echo esc_view($task_title, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($task_title); ?></h1>
      <span class="badge bg-<?php echo esc_view($status_class); ?>"><?php echo esc_view(ucwords(str_replace('_', ' ', $task_status))); ?></span>
      <?php if ($task_priority !== ''): ?>
      <span class="badge bg-<?php echo esc_view($priority_class); ?>"><?php echo esc_view(ucfirst($task_priority)); ?></span>
      <?php endif; ?>
    </div>
    <div class="task-detail-actions">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('tasks/board'); ?>" title="Task board"><i class="bi bi-kanban"></i></a>
      <?php if ($can_whatsapp): ?>
      <button type="button" class="btn btn-outline-success btn-sm" title="Send via WhatsApp" onclick="sendTaskViaWhatsApp(<?php echo (int) $task->id; ?>)">
        <i class="bi bi-whatsapp"></i>
      </button>
      <?php endif; ?>
      <?php if ($can_edit): ?>
      <a class="btn btn-primary btn-sm" href="<?php echo site_url('tasks/' . (int) $task->id . '/edit'); ?>" title="Edit task"><i class="bi bi-pencil"></i></a>
      <?php endif; ?>
      <?php if ($can_delete): ?>
      <form method="post" action="<?php echo site_url('tasks/' . (int) $task->id . '/delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this task?');">
        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
        <button type="submit" class="btn btn-danger btn-sm" title="Delete task"><i class="bi bi-trash"></i></button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="task-detail-stats mb-1">
    <div class="task-detail-stat-card">
      <span class="task-detail-stat-label">Assignee</span>
      <strong class="task-detail-stat-value"><?php echo esc_view($assigneeName !== '' ? $assigneeName : 'Unassigned'); ?></strong>
    </div>
    <div class="task-detail-stat-card">
      <span class="task-detail-stat-label">Due</span>
      <strong class="task-detail-stat-value<?php echo $due_overdue ? ' text-danger' : ''; ?>">
        <?php
        if ($due_raw !== '' && $due_raw !== '0000-00-00') {
            echo esc_view(date('M j, Y', strtotime($due_raw)));
            if ($due_overdue) {
                echo ' <span class="badge bg-danger">Overdue</span>';
            } elseif ($due_soon) {
                echo ' <span class="badge bg-warning text-dark">Soon</span>';
            }
        } else {
            echo '—';
        }
        ?>
      </strong>
    </div>
    <div class="task-detail-stat-card">
      <span class="task-detail-stat-label">Start</span>
      <strong class="task-detail-stat-value">
        <?php echo (!empty($task->start_date) && $task->start_date !== '0000-00-00') ? esc_view(date('M j, Y', strtotime($task->start_date))) : '—'; ?>
      </strong>
    </div>
    <div class="task-detail-stat-card">
      <span class="task-detail-stat-label">Estimate (hrs)</span>
      <strong class="task-detail-stat-value">
        <?php echo function_exists('estimate_hours_display') ? esc_view(estimate_hours_display(isset($task->estimate_hours) ? $task->estimate_hours : null)) : '—'; ?>
      </strong>
    </div>
    <div class="task-detail-stat-card">
      <span class="task-detail-stat-label">Created</span>
      <strong class="task-detail-stat-value">
        <?php echo !empty($task->created_at) ? esc_view(date('M j, Y', strtotime($task->created_at))) : '—'; ?>
        <?php if ($creatorName !== ''): ?>
        <span class="task-detail-stat-sub">by <?php echo esc_view($creatorName); ?></span>
        <?php endif; ?>
      </strong>
    </div>
  </div>

  <?php if ($can_edit): ?>
  <div class="task-detail-status-bar mb-1">
    <span class="task-detail-status-label">Status</span>
    <div class="task-detail-status-actions" role="group" aria-label="Update status">
      <button type="button" class="btn btn-sm<?php echo $task_status === 'pending' ? ' active' : ''; ?>" onclick="updateTaskStatus('pending')">Pending</button>
      <button type="button" class="btn btn-sm<?php echo $task_status === 'in_progress' ? ' active' : ''; ?>" onclick="updateTaskStatus('in_progress')">In Progress</button>
      <button type="button" class="btn btn-sm<?php echo $task_status === 'completed' ? ' active' : ''; ?>" onclick="updateTaskStatus('completed')">Complete</button>
      <button type="button" class="btn btn-sm<?php echo $task_status === 'blocked' ? ' active' : ''; ?>" onclick="updateTaskStatus('blocked')">Blocked</button>
    </div>
  </div>
  <?php endif; ?>

  <div class="row g-2">
    <div class="col-lg-8">
      <div class="card task-detail-panel mb-1">
        <div class="card-header">
          <h2 class="task-detail-panel-title mb-0">Details</h2>
        </div>
        <div class="card-body">
          <?php if (!empty($task->description)): ?>
          <div class="mb-3">
            <div class="task-detail-field-label">Description</div>
            <div class="task-description">
              <?php
                $allowed = '<p><br><strong><em><b><i><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';
                echo strip_tags((string) $task->description, $allowed);
              ?>
            </div>
          </div>
          <?php else: ?>
          <p class="task-detail-empty mb-3">No description.</p>
          <?php endif; ?>

          <?php if (property_exists($task, 'attachment_path') && !empty($task->attachment_path)): ?>
          <div class="mb-3">
            <div class="task-detail-field-label">Attachment</div>
            <a href="<?php echo base_url($task->attachment_path); ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-paperclip me-1"></i>Download
            </a>
          </div>
          <?php endif; ?>

          <?php if (!empty($task->reference_url)): ?>
          <div>
            <div class="task-detail-field-label">Reference</div>
            <?php $this->load->view('partials/reference_url_display', array('reference_url' => $task->reference_url, 'wrapper_class' => 'mb-0')); ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card task-detail-panel mb-1">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h2 class="task-detail-panel-title mb-0">
            Comments
            <span class="badge text-bg-light border ms-1" id="comment-count">0</span>
          </h2>
        </div>
        <div class="card-body">
          <form method="post" action="<?php echo site_url('tasks/' . (int) $task->id . '/comment'); ?>" id="commentForm" class="task-detail-comment-form mb-3">
            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
            <textarea class="form-control form-control-sm" name="comment" rows="2" placeholder="Add a comment…" required></textarea>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <small class="text-muted">Enter to post · Shift+Enter for new line</small>
              <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i>Post</button>
            </div>
          </form>
          <div id="comments" class="task-detail-comments"></div>
          <div id="comments-empty" class="task-detail-empty text-center py-3" style="display:none">No comments yet.</div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card task-detail-panel mb-1">
        <div class="card-header">
          <h2 class="task-detail-panel-title mb-0">Info</h2>
        </div>
        <div class="card-body">
          <dl class="task-detail-dl">
            <div>
              <dt>Assignee</dt>
              <dd>
                <?php if ($assigneeName !== ''): ?>
                <span class="task-detail-avatar" aria-hidden="true"><?php echo esc_view($getInitials($assigneeName)); ?></span>
                <?php echo esc_view($assigneeName); ?>
                <?php else: ?>
                <span class="text-muted">Unassigned</span>
                <?php endif; ?>
              </dd>
            </div>
            <div>
              <dt>Status</dt>
              <dd><span class="badge bg-<?php echo esc_view($status_class); ?>"><?php echo esc_view(ucwords(str_replace('_', ' ', $task_status))); ?></span></dd>
            </div>
            <?php if ($task_priority !== ''): ?>
            <div>
              <dt>Priority</dt>
              <dd><span class="badge bg-<?php echo esc_view($priority_class); ?>"><?php echo esc_view(ucfirst($task_priority)); ?></span></dd>
            </div>
            <?php endif; ?>
            <?php if ($project_id > 0): ?>
            <div>
              <dt>Project</dt>
              <dd>
                <a href="<?php echo site_url('projects/' . $project_id); ?>" class="text-decoration-none">
                  <?php echo esc_view($project_name !== '' ? $project_name : ('Project #' . $project_id)); ?>
                </a>
              </dd>
            </div>
            <?php endif; ?>
            <?php if (!empty($task->requirement_id)): ?>
            <div>
              <dt>Requirement</dt>
              <dd>
                <a href="<?php echo site_url('requirements/view/' . (int) $task->requirement_id); ?>" class="text-decoration-none">
                  <?php echo esc_view(!empty($task->requirement_number) ? $task->requirement_number : ('REQ #' . (int) $task->requirement_id)); ?>
                </a>
              </dd>
            </div>
            <?php endif; ?>
            <div>
              <dt>Created by</dt>
              <dd><?php echo esc_view($creatorName !== '' ? $creatorName : '—'); ?></dd>
            </div>
            <?php if (!empty($task->updated_at)): ?>
            <div>
              <dt>Updated</dt>
              <dd><?php echo esc_view(date('M j, Y g:i A', strtotime($task->updated_at))); ?></dd>
            </div>
            <?php endif; ?>
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var container = document.getElementById('comments');
  var empty = document.getElementById('comments-empty');
  var commentCount = document.getElementById('comment-count');
  var taskId = <?php echo (int) $task->id; ?>;

  function timeago(iso){
    var d = new Date(String(iso).replace(' ', 'T'));
    var diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return Math.floor(diff) + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
  }

  function getInitials(text) {
    text = (text || '').trim();
    if (!text) return 'NA';
    var parts = text.split(/\s+/);
    var first = parts[0] ? parts[0].charAt(0).toUpperCase() : '';
    var last = parts.length > 1 ? parts[parts.length - 1].charAt(0).toUpperCase() : '';
    return first + (last && last !== first ? last : '');
  }

  function escapeHtml(s){
    return (s || '').replace(/[&<>"']/g, function(c){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
  }

  function render(list){
    container.innerHTML = '';
    if (!list || list.length === 0){
      empty.style.display = 'block';
      commentCount.textContent = '0';
      return;
    }
    empty.style.display = 'none';
    commentCount.textContent = list.length;
    list.forEach(function(c){
      var name = c.name || c.email || ('User #' + c.user_id);
      var item = document.createElement('div');
      item.className = 'task-detail-comment';
      item.innerHTML =
        '<div class="task-detail-avatar" aria-hidden="true">' + escapeHtml(getInitials(name)) + '</div>' +
        '<div class="task-detail-comment-body">' +
          '<div class="task-detail-comment-meta">' +
            '<strong>' + escapeHtml(name) + '</strong>' +
            '<span>' + (c.created_at ? escapeHtml(timeago(c.created_at)) : '') + '</span>' +
          '</div>' +
          '<div class="task-detail-comment-text">' + escapeHtml(c.comment || '').replace(/\n/g, '<br>') + '</div>' +
          '<a href="<?php echo site_url('tasks/comment'); ?>/' + c.id + '/delete?ref=<?php echo rawurlencode(site_url('tasks/' . (int) $task->id)); ?>" class="task-detail-comment-delete" onclick="return confirm(\'Delete this comment?\')"><i class="bi bi-trash"></i> Delete</a>' +
        '</div>';
      container.appendChild(item);
    });
  }

  function load(){
    fetch('<?php echo site_url('tasks'); ?>/' + taskId + '/comments', { credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(res){
        if (res && res.ok) render(res.comments || []);
      });
  }

  var commentForm = document.getElementById('commentForm');
  var commentTextarea = commentForm.querySelector('textarea');
  commentTextarea.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      commentForm.submit();
    }
  });

  load();
})();

function _csrfParam() {
  var m = document.cookie.match(/(?:^|;\s*)ci_csrf_token=([^;]*)/);
  return m ? '&<?php echo $this->security->get_csrf_token_name(); ?>=' + encodeURIComponent(decodeURIComponent(m[1])) : '';
}

function updateTaskStatus(status) {
  var taskId = <?php echo (int) $task->id; ?>;
  fetch('<?php echo site_url('tasks/update-status'); ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + taskId + '&status=' + status + _csrfParam(),
    credentials: 'same-origin'
  })
  .then(function(response) { return response.json(); })
  .then(function(data) {
    if (data.ok) {
      showNotification('Task status updated', 'success');
      setTimeout(function() { location.reload(); }, 700);
    } else {
      showNotification(data.error || 'Failed to update status', 'danger');
    }
  })
  .catch(function() {
    showNotification('Network error. Please try again.', 'danger');
  });
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
