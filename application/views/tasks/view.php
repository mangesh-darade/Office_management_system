<?php $this->load->view('partials/header', ['title' => 'Task Details']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Task #<?php echo (int)$task->id; ?></h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('tasks'); ?>">Back</a>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('tasks/'.$task->id.'/edit'); ?>">Edit</a>
    <a class="btn btn-danger btn-sm" onclick="return confirm('Delete this task?')" href="<?php echo site_url('tasks/'.$task->id.'/delete'); ?>">Delete</a>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><div class="text-muted small">Project ID</div><div class="fw-semibold"><?php echo (int)$task->project_id; ?></div></div>
      <div class="col-md-9"><div class="text-muted small">Title</div><div class="fw-semibold"><?php echo htmlspecialchars($task->title); ?></div></div>
      <div class="col-12">
        <div class="text-muted small">Description</div>
        <div>
          <?php 
            $allowed = '<p><br><strong><em><b><i><ul><ol><li><a>'; 
            $desc = isset($task->description) ? strip_tags($task->description, $allowed) : '';
            echo $desc; 
          ?>
        </div>
      </div>
      <?php if (property_exists($task, 'attachment_path') && !empty($task->attachment_path)): ?>
      <div class="col-12">
        <div class="text-muted small">Attachment</div>
        <div>
          <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url($task->attachment_path); ?>" target="_blank" rel="noopener">Download Attachment</a>
        </div>
      </div>
      <?php endif; ?>
      <div class="col-md-3"><div class="text-muted small">Assigned To</div><div class="fw-semibold"><?php echo (int)$task->assigned_to; ?></div></div>
      <div class="col-md-3"><div class="text-muted small">Status</div><div><span class="badge bg-info text-dark"><?php echo htmlspecialchars($task->status); ?></span></div></div>
    </div>
  </div>
</div>

<div class="mt-4">
  <h2 class="h5 mb-3">Comments</h2>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <div class="card shadow-soft mb-3">
    <div class="card-body">
      <form method="post" action="<?php echo site_url('tasks/'.(int)$task->id.'/comment'); ?>" class="d-flex gap-2">
        <textarea class="form-control" name="comment" rows="2" placeholder="Write a comment..." required></textarea>
        <button class="btn btn-primary">Post</button>
      </form>
    </div>
  </div>

  <div class="card shadow-soft">
    <div class="card-body">
      <div id="comments" class="vstack gap-3"></div>
      <div id="comments-empty" class="text-muted" style="display:none">No comments yet.</div>
    </div>
  </div>
</div>

<script>
(function(){
  const container = document.getElementById('comments');
  const empty = document.getElementById('comments-empty');
  const taskId = <?php echo (int)$task->id; ?>;

  function timeago(iso){
    const d = new Date(iso.replace(' ', 'T'));
    const diff = (Date.now() - d.getTime())/1000;
    if (diff < 60) return Math.floor(diff)+'s ago';
    if (diff < 3600) return Math.floor(diff/60)+'m ago';
    if (diff < 86400) return Math.floor(diff/3600)+'h ago';
    return Math.floor(diff/86400)+'d ago';
  }

  function render(list){
    container.innerHTML = '';
    if (!list || list.length === 0){ empty.style.display='block'; return; }
    empty.style.display='none';
    list.forEach(function(c){
      const name = c.name || c.email || ('User #'+c.user_id);
      const item = document.createElement('div');
      item.className = 'd-flex gap-3 align-items-start border-bottom pb-2';
      item.innerHTML = `
        <div class="rounded-circle bg-light border" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;">👤</div>
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between">
            <div><strong>${escapeHtml(name)}</strong></div>
            <div class="text-muted small">${c.created_at ? escapeHtml(timeago(c.created_at)) : ''}</div>
          </div>
          <div class="mt-1">${escapeHtml(c.comment || '')}</div>
          <div class="mt-1">
            <a href="<?php echo site_url('tasks/comment'); ?>/${c.id}/delete?ref=<?php echo rawurlencode(site_url('tasks/'.(int)$task->id)); ?>" class="link-danger small" onclick="return confirm('Delete this comment?')">Delete</a>
          </div>
        </div>`;
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
      .then(r=>r.json()).then(function(res){ if (res && res.ok) render(res.comments||[]); });
  }

  load();
})();
</script>

<?php $this->load->view('partials/footer'); ?>
