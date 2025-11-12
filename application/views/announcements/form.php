<?php $this->load->view('partials/header', ['title' => (($action === 'edit') ? 'Edit' : 'Create').' Announcement']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?php echo ($action==='edit'?'Edit':'Create'); ?> Announcement</h1>
  <a class="btn btn-secondary btn-sm" href="<?php echo site_url('announcements'); ?>">Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-soft">
  <div class="card-body">
    <form method="post">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">Title</label>
          <input class="form-control" name="title" value="<?php echo htmlspecialchars(isset($row->title)?$row->title:''); ?>" required />
        </div>
        <div class="col-md-4">
          <label class="form-label">Priority</label>
          <?php $priority = isset($row->priority)?$row->priority:'medium'; ?>
          <select class="form-select" name="priority">
            <option value="low" <?php echo $priority==='low'?'selected':''; ?>>Low</option>
            <option value="medium" <?php echo $priority==='medium'?'selected':''; ?>>Medium</option>
            <option value="high" <?php echo $priority==='high'?'selected':''; ?>>High</option>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label">Content</label>
          <textarea class="form-control" name="content" rows="6" required><?php echo htmlspecialchars(isset($row->content)?$row->content:''); ?></textarea>
        </div>
        <div class="col-md-4">
          <label class="form-label">Target Roles (all/admin/manager/lead/staff)</label>
          <input class="form-control" name="target_roles" value="<?php echo htmlspecialchars(isset($row->target_roles)?$row->target_roles:'all'); ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Start Date</label>
          <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars(isset($row->start_date)?$row->start_date:''); ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">End Date</label>
          <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars(isset($row->end_date)?$row->end_date:''); ?>" />
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <?php $status = isset($row->status)?$row->status:'draft'; ?>
          <select class="form-select" name="status">
            <option value="draft" <?php echo $status==='draft'?'selected':''; ?>>Draft</option>
            <option value="published" <?php echo $status==='published'?'selected':''; ?>>Published</option>
            <option value="archived" <?php echo $status==='archived'?'selected':''; ?>>Archived</option>
          </select>
        </div>
      </div>
      <div class="mt-3">
        <button class="btn btn-primary"><?php echo ($action==='edit'?'Update':'Create'); ?></button>
      </div>
    </form>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
