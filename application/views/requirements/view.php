<?php $this->load->view('partials/header', ['title' => 'Requirement Details']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Requirement: <?php echo esc_view(isset($req->req_number)?$req->req_number:'#'.(int)$req->id); ?></h1>
  <div class="d-flex gap-2">
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('requirements/edit/'.(int)$req->id); ?>">Edit</a>
    <a class="btn btn-light btn-sm" href="<?php echo site_url('requirements'); ?>">Back</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card shadow-soft mb-3">
      <div class="card-body">
        <h5 class="mb-1"><?php echo esc_view($req->title); ?></h5>
        <div class="mb-2 text-muted small">
          <span class="me-3">Client: <?php echo esc_view(isset($req->client_name)?$req->client_name:''); ?></span>
          <span class="me-3">Status: <span class="badge bg-light text-dark border"><?php echo esc_view(isset($req->status)?$req->status:'received'); ?></span></span>
          <span class="me-3">Priority: <span class="badge bg-secondary"><?php echo esc_view(isset($req->priority)?$req->priority:'medium'); ?></span></span>
          <?php if (!empty($req->requirement_type)): ?>
          <span>Type: <span class="badge bg-info text-dark"><?php echo esc_view(function_exists('module_type_label') ? module_type_label($req->requirement_type, 'requirements') : $req->requirement_type); ?></span></span>
          <?php endif; ?>
        </div>
        <?php if (isset($req->description) && $req->description !== ''): ?>
        <div class="border rounded p-3 bg-white">
          <?php echo sanitize_html_output($req->description); ?>
        </div>
        <?php else: ?>
        <div class="text-muted small">No description provided.</div>
        <?php endif; ?>
        <div class="row mt-3 small">
          <div class="col-md-6">
            <div class="text-muted">Expected Delivery</div>
            <div><?php echo esc_view(isset($req->expected_delivery_date)?$req->expected_delivery_date:''); ?></div>
          </div>
          <div class="col-md-6">
            <div class="text-muted">Owner</div>
            <div><?php echo esc_view(isset($req->owner_name)?$req->owner_name:'Unassigned'); ?></div>
          </div>
          <?php if (!empty($req->reference_url)): ?>
          <div class="col-md-12 mt-2">
            <?php $this->load->view('partials/reference_url_display', ['reference_url' => $req->reference_url, 'wrapper_class' => 'mb-0']); ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card shadow-soft">
      <div class="card-header"><h6 class="mb-0">Attachments</h6></div>
      <div class="card-body">
        <?php if (empty($attachments)): ?>
          <div class="text-muted small">No attachments.</div>
        <?php else: ?>
        <div class="list-group">
          <?php foreach ($attachments as $a): ?>
          <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo base_url($a->file_path); ?>" download>
            <div>
              <i class="bi bi-file-earmark me-2"></i>
              <strong><?php echo esc_view(isset($a->original_name)?$a->original_name:$a->file_name); ?></strong>
              <?php if (isset($a->file_size)): ?><small class="text-muted"> (<?php echo (int)$a->file_size; ?> KB)</small><?php endif; ?>
            </div>
            <i class="bi bi-download"></i>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php
    // Get linked tasks for this requirement
    $linked_tasks = [];
    if ($this->db->table_exists('tasks') && schema_table_has_column($this->db, 'tasks', 'requirement_id')) {
        $this->db->select('t.id, t.title, t.status, t.priority, t.due_date, p.name AS project_name');
        $this->db->from('tasks t');
        $this->db->join('projects p', 'p.id = t.project_id', 'left');
        $this->db->where('t.requirement_id', (int)$req->id);
        $this->db->order_by('t.id', 'DESC');
        $linked_tasks = $this->db->get()->result();
    }
    ?>
    
    <?php if (!empty($linked_tasks)): ?>
    <div class="card shadow-soft mt-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
          <i class="bi bi-list-task me-2"></i>Linked Tasks (<?php echo count($linked_tasks); ?>)
        </h6>
        <a href="<?php echo site_url('tasks/create?requirement_id='.(int)$req->id); ?>" class="btn btn-sm btn-primary">
          <i class="bi bi-plus-lg me-1"></i>Create Task
        </a>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Task ID</th>
                <th>Title</th>
                <th>Project</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Due Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($linked_tasks as $lt): ?>
              <tr>
                <td>#<?php echo (int)$lt->id; ?></td>
                <td><?php echo esc_view($lt->title); ?></td>
                <td><?php echo esc_view(isset($lt->project_name) ? $lt->project_name : ''); ?></td>
                <td>
                  <?php 
                  $statusColors = ['pending' => 'warning', 'in_progress' => 'info', 'completed' => 'success', 'blocked' => 'danger'];
                  $statusColor = isset($statusColors[$lt->status]) ? $statusColors[$lt->status] : 'secondary';
                  ?>
                  <span class="badge bg-<?php echo $statusColor; ?>"><?php echo esc_view(ucwords(str_replace('_', ' ', $lt->status))); ?></span>
                </td>
                <td>
                  <?php 
                  $priorityColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'urgent' => 'danger'];
                  $priorityColor = isset($priorityColors[$lt->priority]) ? $priorityColors[$lt->priority] : 'secondary';
                  ?>
                  <span class="badge bg-<?php echo $priorityColor; ?>"><?php echo esc_view(ucfirst($lt->priority)); ?></span>
                </td>
                <td>
                  <?php if (isset($lt->due_date) && $lt->due_date): ?>
                    <?php 
                    $dueDate = strtotime($lt->due_date);
                    $today = strtotime('today');
                    $class = $dueDate < $today ? 'text-danger' : ($dueDate <= strtotime('+3 days') ? 'text-warning' : '');
                    ?>
                    <span class="<?php echo $class; ?>"><?php echo date('M j, Y', $dueDate); ?></span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?php echo site_url('tasks/'.(int)$lt->id); ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i>View
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="card shadow-soft mt-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
          <i class="bi bi-list-task me-2"></i>Linked Tasks
        </h6>
        <a href="<?php echo site_url('tasks/create?requirement_id='.(int)$req->id); ?>" class="btn btn-sm btn-primary">
          <i class="bi bi-plus-lg me-1"></i>Create Task from Requirement
        </a>
      </div>
      <div class="card-body">
        <div class="text-center text-muted py-4">
          <i class="bi bi-inbox" style="font-size: 3rem;"></i>
          <p class="mt-2 mb-0">No tasks linked to this requirement yet.</p>
          <p class="small">Create a task to start working on this requirement.</p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-soft mt-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Version History</h6>
        <form method="get" action="<?php echo site_url('requirements/view/'.(int)$req->id); ?>" class="d-flex align-items-center gap-2">
          <?php $curType = isset($type_filter) ? (string)$type_filter : ''; ?>
          <label class="small text-muted me-2">Filter by type</label>
          <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="" <?php echo $curType === '' ? 'selected' : ''; ?>>All</option>
            <?php if (isset($requirement_types) && is_array($requirement_types)): foreach ($requirement_types as $code => $label): ?>
              <option value="<?php echo esc_view($code); ?>" <?php echo ($curType === (string) $code) ? 'selected' : ''; ?>><?php echo esc_view($label); ?></option>
            <?php endforeach; endif; ?>
          </select>
        </form>
      </div>
      <div class="card-body">
        <?php if (!isset($versions) || empty($versions)): ?>
          <div class="text-muted small">No versions yet.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Version</th>
                <th>Title</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Created At</th>
                <th>By</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($versions as $v): ?>
              <tr>
                <td><?php echo (int)$v->version_no; ?></td>
                <td><?php echo esc_view(isset($v->title)?$v->title:''); ?></td>
                <td><?php echo esc_view(isset($v->status)?$v->status:''); ?></td>
                <td><?php echo esc_view(isset($v->priority)?$v->priority:''); ?></td>
                <td><?php echo esc_view(isset($v->created_at)?$v->created_at:''); ?></td>
                <td><?php echo esc_view(isset($v->created_by)?$v->created_by:''); ?></td>
                <td><a class="btn btn-light btn-sm" href="<?php echo site_url('requirements/version/'.(int)$v->id); ?>">View</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-soft">
      <div class="card-header"><h6 class="mb-0">Meta</h6></div>
      <div class="card-body small text-muted">
        <div>Received: <?php echo esc_view(isset($req->received_date)?$req->received_date:''); ?></div>
        <div>Created: <?php echo esc_view(isset($req->created_at)?$req->created_at:''); ?></div>
        <div>Updated: <?php echo esc_view(isset($req->updated_at)?$req->updated_at:''); ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Comments Section -->
<div class="row mt-4">
  <div class="col-12">
    <div class="card shadow-soft">
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
            <?php echo esc_view($this->session->flashdata('error')); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo esc_view($this->session->flashdata('success')); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        
        <!-- Comment Form -->
        <div class="mb-4">
          <form method="post" action="<?php echo site_url('requirements/'.(int)$req->id.'/comment'); ?>" id="commentForm">
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
  const requirementId = <?php echo (int)$req->id; ?>;

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
      const name = c.name || c.full_name || c.email || ('User #'+c.user_id);
      const item = document.createElement('div');
      item.className = 'comment-item border-bottom pb-3 mb-3';
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
              '<a href="<?php echo site_url('requirements/comment'); ?>/' + c.id + '/delete?ref=<?php echo rawurlencode(site_url('requirements/view/'.(int)$req->id)); ?>" class="link-danger small text-decoration-none" onclick="return confirm(\'Delete this comment?\')">' +
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
    fetch('<?php echo site_url('requirements'); ?>/'+requirementId+'/comments', { credentials: 'same-origin' })
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
</script>

<?php $this->load->view('partials/footer'); ?>
