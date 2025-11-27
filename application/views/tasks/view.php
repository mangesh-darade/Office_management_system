<?php $this->load->view('partials/header', ['title' => 'Task Details']); ?>

<?php
// Helper function to get display name
$getDisplayName = function($user) {
  $name = '';
  if (isset($user->emp_name) && trim((string)$user->emp_name) !== '') { $name = $user->emp_name; }
  else if (isset($user->full_name) && trim((string)$user->full_name) !== '') { $name = $user->full_name; }
  else if (isset($user->name) && trim((string)$user->name) !== '') { $name = $user->name; }
  else if (isset($user->email)) { $name = $user->email; }
  return trim((string)$name);
};

$getInitials = function($text) {
  $text = trim((string)$text);
  if ($text === '') return 'NA';
  $parts = preg_split('/\s+/', $text);
  $first = strtoupper(substr($parts[0],0,1));
  $last = isset($parts[count($parts)-1]) ? strtoupper(substr($parts[count($parts)-1],0,1)) : '';
  return $first.($last && $last!==$first ? $last : '');
};

$getStatusColor = function($status) {
  $colors = [
    'pending' => 'warning',
    'in_progress' => 'info', 
    'completed' => 'success',
    'blocked' => 'danger'
  ];
  return isset($colors[$status]) ? $colors[$status] : 'secondary';
};

$getPriorityColor = function($priority) {
  $colors = [
    'high' => 'danger',
    'medium' => 'warning',
    'low' => 'success'
  ];
  return isset($colors[$priority]) ? $colors[$priority] : 'secondary';
};

$assigneeName = $getDisplayName($task);
$creatorName = $getDisplayName((object)[
  'emp_name' => isset($task->creator_name) ? $task->creator_name : '',
  'full_name' => isset($task->creator_full_name) ? $task->creator_full_name : '', 
  'name' => isset($task->creator_name) ? $task->creator_name : '',
  'email' => isset($task->creator_email) ? $task->creator_email : ''
]);
?>

<!-- Header Section -->
<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <div class="d-flex align-items-center gap-3 mb-2">
      <h1 class="h2 mb-0">Task #<?php echo (int)$task->id; ?></h1>
      <span class="badge bg-<?php echo $getStatusColor($task->status); ?> fs-6"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $task->status))); ?></span>
      <?php if (isset($task->priority) && $task->priority): ?>
        <span class="badge bg-<?php echo $getPriorityColor($task->priority); ?> fs-6">Priority: <?php echo ucfirst($task->priority); ?></span>
      <?php endif; ?>
    </div>
    <p class="text-muted mb-0">
      Created <?php echo isset($task->created_at) ? date('M j, Y', strtotime($task->created_at)) : ''; ?>
      <?php if ($creatorName): ?>by <?php echo htmlspecialchars($creatorName); ?><?php endif; ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <div class="dropdown">
      <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
        <i class="bi bi-gear me-1"></i> Actions
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?php echo site_url('tasks/'.$task->id.'/edit'); ?>">
          <i class="bi bi-pencil me-2"></i>Edit Task
        </a></li>
        <li><a class="dropdown-item" href="<?php echo site_url('tasks/board'); ?>">
          <i class="bi bi-kanban me-2"></i>View Board
        </a></li>
        <li><a class="dropdown-item" href="<?php echo site_url('tasks'); ?>">
          <i class="bi bi-list me-2"></i>List View
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="<?php echo site_url('tasks/'.$task->id.'/delete'); ?>" onclick="return confirm('Are you sure you want to delete this task?')">
          <i class="bi bi-trash me-2"></i>Delete Task
        </a></li>
      </ul>
    </div>
  </div>
</div>

<!-- Quick Actions Bar -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h5 class="mb-1"><?php echo htmlspecialchars($task->title); ?></h5>
        <?php if (!empty($task->project_name)): ?>
          <div class="d-flex align-items-center gap-2 text-muted">
            <i class="bi bi-folder"></i>
            <span>Project: <?php echo htmlspecialchars($task->project_name); ?></span>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-md-4 text-end">
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="updateTaskStatus('pending')">
            <i class="bi bi-clock me-1"></i>Pending
          </button>
          <button type="button" class="btn btn-outline-info btn-sm" onclick="updateTaskStatus('in_progress')">
            <i class="bi bi-play me-1"></i>In Progress
          </button>
          <button type="button" class="btn btn-outline-success btn-sm" onclick="updateTaskStatus('completed')">
            <i class="bi bi-check me-1"></i>Complete
          </button>
          <button type="button" class="btn btn-outline-danger btn-sm" onclick="updateTaskStatus('blocked')">
            <i class="bi bi-exclamation-triangle me-1"></i>Block
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="row">
  <!-- Task Details Column -->
  <div class="col-lg-8">
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="bi bi-info-circle me-2"></i>Task Details
        </h5>
      </div>
      <div class="card-body">
        <?php if (!empty($task->description)): ?>
          <div class="mb-4">
            <h6 class="text-muted mb-2">Description</h6>
            <div class="task-description">
              <?php 
                $allowed = '<p><br><strong><em><b><i><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre>'; 
                $desc = isset($task->description) ? strip_tags($task->description, $allowed) : '';
                echo $desc; 
              ?>
            </div>
          </div>
        <?php endif; ?>
        
        <?php if (property_exists($task, 'attachment_path') && !empty($task->attachment_path)): ?>
          <div class="mb-4">
            <h6 class="text-muted mb-2">Attachment</h6>
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-paperclip text-muted"></i>
              <a href="<?php echo base_url($task->attachment_path); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-download me-1"></i>Download Attachment
              </a>
            </div>
          </div>
        <?php endif; ?>
        
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="text-muted small">Assigned To</label>
              <div class="d-flex align-items-center gap-2">
                <?php if ($assigneeName): ?>
                  <div class="avatar avatar-bg" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.75rem; font-weight: 600; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff;">
                    <?php echo htmlspecialchars($getInitials($assigneeName)); ?>
                  </div>
                  <span><?php echo htmlspecialchars($assigneeName); ?></span>
                <?php else: ?>
                  <span class="text-muted">Unassigned</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="text-muted small">Status</label>
              <div>
                <span class="badge bg-<?php echo $getStatusColor($task->status); ?>"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $task->status))); ?></span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="text-muted small">Created</label>
              <div><?php echo isset($task->created_at) ? date('M j, Y g:i A', strtotime($task->created_at)) : ''; ?></div>
            </div>
          </div>
          <?php if (isset($task->updated_at) && $task->updated_at): ?>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="text-muted small">Last Updated</label>
                <div><?php echo date('M j, Y g:i A', strtotime($task->updated_at)); ?></div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Sidebar Column -->
  <div class="col-lg-4">
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="bi bi-info-square me-2"></i>Quick Info
        </h5>
      </div>
      <div class="card-body">
        <div class="vstack gap-3">
          <div>
            <label class="text-muted small">Task ID</label>
            <div class="fw-mono">#<?php echo (int)$task->id; ?></div>
          </div>
          
          <?php if (!empty($task->project_name)): ?>
            <div>
              <label class="text-muted small">Project</label>
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-folder text-primary"></i>
                <span><?php echo htmlspecialchars($task->project_name); ?></span>
              </div>
            </div>
          <?php endif; ?>
          
          <?php if (isset($task->priority) && $task->priority): ?>
            <div>
              <label class="text-muted small">Priority</label>
              <div>
                <span class="badge bg-<?php echo $getPriorityColor($task->priority); ?>"><?php echo ucfirst($task->priority); ?></span>
              </div>
            </div>
          <?php endif; ?>
          
          <div>
            <label class="text-muted small">Created By</label>
            <div class="d-flex align-items-center gap-2">
              <?php if ($creatorName): ?>
                <div class="avatar avatar-bg" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.7rem; font-weight: 600; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff;">
                  <?php echo htmlspecialchars($getInitials($creatorName)); ?>
                </div>
                <span><?php echo htmlspecialchars($creatorName); ?></span>
              <?php else: ?>
                <span class="text-muted">Unknown</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Comments Section -->
<div class="row mt-4">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="bi bi-chat-dots me-2"></i>Comments
          <span class="badge bg-secondary ms-2" id="comment-count">0</span>
        </h5>
      </div>
      <div class="card-body">
        <!-- Flash Messages -->
        <?php if ($this->session->flashdata('error')): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($this->session->flashdata('error')); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($this->session->flashdata('success')); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        
        <!-- Comment Form -->
        <div class="mb-4">
          <form method="post" action="<?php echo site_url('tasks/'.(int)$task->id.'/comment'); ?>" id="commentForm">
            <div class="mb-3">
              <textarea class="form-control" name="comment" rows="3" placeholder="Add a comment..." required></textarea>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted">Press Enter to submit, Shift+Enter for new line</small>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-send me-1"></i>Post Comment
              </button>
            </div>
          </form>
        </div>
        
        <!-- Comments List -->
        <div id="comments" class="vstack gap-3"></div>
        <div id="comments-empty" class="text-center text-muted py-4" style="display:none">
          <i class="bi bi-chat-dots" style="font-size: 3rem;"></i>
          <p class="mt-2">No comments yet. Be the first to comment!</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const container = document.getElementById('comments');
  const empty = document.getElementById('comments-empty');
  const commentCount = document.getElementById('comment-count');
  const taskId = <?php echo (int)$task->id; ?>;

  function timeago(iso){
    const d = new Date(iso.replace(' ', 'T'));
    const diff = (Date.now() - d.getTime())/1000;
    if (diff < 60) return Math.floor(diff)+'s ago';
    if (diff < 3600) return Math.floor(diff/60)+'m ago';
    if (diff < 86400) return Math.floor(diff/3600)+'h ago';
    return Math.floor(diff/86400)+'d ago';
  }

  function getInitials(text) {
    text = text || '';
    if (!text) return 'NA';
    const parts = text.trim().split(/\s+/);
    const first = parts[0] ? parts[0].charAt(0).toUpperCase() : '';
    const last = parts.length > 1 ? parts[parts.length - 1].charAt(0).toUpperCase() : '';
    return first + (last && last !== first ? last : '');
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
      const name = c.name || c.email || ('User #'+c.user_id);
      const item = document.createElement('div');
      item.className = 'comment-item border-bottom pb-3';
      item.innerHTML = 
        '<div class="d-flex gap-3 align-items-start">' +
          '<div class="avatar avatar-bg" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.875rem; font-weight: 600; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; flex-shrink: 0;">' +
            getInitials(name) +
          '</div>' +
          '<div class="flex-grow-1">' +
            '<div class="d-flex justify-content-between align-items-center mb-2">' +
              '<div class="fw-semibold">' + escapeHtml(name) + '</div>' +
              '<div class="text-muted small">' + (c.created_at ? escapeHtml(timeago(c.created_at)) : '') + '</div>' +
            '</div>' +
            '<div class="comment-content mb-2">' + escapeHtml(c.comment || '').replace(/\n/g, '<br>') + '</div>' +
            '<div class="comment-actions">' +
              '<a href="<?php echo site_url('tasks/comment'); ?>/' + c.id + '/delete?ref=<?php echo rawurlencode(site_url('tasks/'.(int)$task->id)); ?>" class="link-danger small text-decoration-none" onclick="return confirm(\'Delete this comment?\')">' +
                '<i class="bi bi-trash me-1"></i>Delete' +
              '</a>' +
            '</div>' +
          '</div>' +
        '</div>';
      container.appendChild(item);
    });
  }

  function escapeHtml(s){
    return (s||'').replace(/[&<>"']/g, function(c){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c];
    });
  }

  function load(){
    fetch('<?php echo site_url('tasks'); ?>/'+taskId+'/comments', { credentials: 'same-origin' })
      .then(function(r) { return r.json(); }).then(function(res){ 
        if (res && res.ok) render(res.comments||[]); 
      });
  }

  // Handle form submission with Enter key
  const commentForm = document.getElementById('commentForm');
  const commentTextarea = commentForm.querySelector('textarea');
  
  commentTextarea.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      commentForm.submit();
    }
  });

  load();
})();

// Task status update function
function updateTaskStatus(status) {
  const taskId = <?php echo (int)$task->id; ?>;
  
  fetch('<?php echo site_url('tasks/update-status'); ?>', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'id=' + taskId + '&status=' + status,
    credentials: 'same-origin'
  })
  .then(response => response.json())
  .then(data => {
    if (data.ok) {
      // Show success notification
      showNotification('Task status updated successfully', 'success');
      // Reload page to reflect changes
      setTimeout(function() { location.reload(); }, 1000);
    } else {
      showNotification(data.error || 'Failed to update status', 'error');
    }
  })
  .catch(error => {
    showNotification('Network error. Please try again.', 'error');
  });
}

function showNotification(message, type) {
  type = type || 'info';
  const alertDiv = document.createElement('div');
  alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed';
  alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
  alertDiv.innerHTML = 
    message +
    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
  document.body.appendChild(alertDiv);
  
  setTimeout(function() {
    alertDiv.remove();
  }, 5000);
}
</script>

<?php $this->load->view('partials/footer'); ?>
