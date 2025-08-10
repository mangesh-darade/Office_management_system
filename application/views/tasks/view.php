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
<?php $this->load->view('partials/footer'); ?>
