<?php $this->load->view('partials/header', ['title' => $item->defect_number]); ?>
<style>
.defect-rich-content ul, .defect-rich-content ol { padding-left: 1.25rem; margin-bottom: 0.5rem; }
.defect-rich-content p:last-child { margin-bottom: 0; }
.defect-rich-content pre { background: #f8f9fa; padding: 0.5rem; border-radius: 4px; font-size: 0.875rem; }
</style>
<div class="container-fluid py-3">
<?php
$can_edit = function_exists('has_module_access') && (has_module_access('defects_edit') || has_module_access('defects'));
$can_delete = function_exists('has_module_access') && (has_module_access('defects_delete') || has_module_access('defects'));
$actions = '';
if ($can_edit) {
    $actions .= '<a class="btn btn-primary btn-sm" href="'.site_url('defects/edit/'.$item->id).'"><i class="bi bi-pencil me-1"></i>Edit</a> ';
}
$this->load->view('partials/oms_page_head', ['title' => $item->defect_number, 'icon' => 'bi-bug', 'subtitle' => esc_view($item->title), 'actions_html' => $actions]);
?>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div><?php endif; ?>
<?php if (!empty($is_overdue)): ?><div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>This defect is overdue.</div><?php endif; ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card shadow-soft mb-3"><div class="card-body">
      <h2 class="h6 text-muted">Description</h2>
      <div class="defect-rich-content mb-0">
        <?php if (!empty($item->description)):
            $allowed = '<p><br><strong><em><b><i><u><s><del><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre><span>';
            echo strip_tags((string) $item->description, $allowed);
        else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
      </div>
    </div></div>
    <div class="card shadow-soft mb-3"><div class="card-body">
      <h2 class="h6 text-muted">Steps to reproduce</h2>
      <div class="defect-rich-content mb-0">
        <?php if (!empty($item->steps_to_reproduce)):
            $allowed = '<p><br><strong><em><b><i><u><s><del><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre><span>';
            echo strip_tags((string) $item->steps_to_reproduce, $allowed);
        else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
      </div>
    </div></div>
    <?php if (!empty($attachments)): ?>
    <div class="card shadow-soft mb-3"><div class="card-body">
      <h2 class="h6 text-muted">Attachments</h2>
      <ul class="list-unstyled mb-0">
        <?php foreach ($attachments as $a): ?>
        <li class="mb-1"><a href="<?php echo site_url('defects/attachment/'.$item->id.'/'.$a->id.'/download'); ?>"><i class="bi bi-paperclip me-1"></i><?php echo esc_view($a->original_name); ?></a> <span class="text-muted small">(<?php echo number_format((int)$a->file_size / 1024, 1); ?> KB)</span></li>
        <?php endforeach; ?>
      </ul>
    </div></div>
    <?php endif; ?>
    <div class="card shadow-soft mb-3"><div class="card-body">
      <h2 class="h6 text-muted">Comments</h2>
      <?php if (empty($comments)): ?><p class="text-muted small mb-2">No comments yet.</p><?php else: ?>
      <div class="vstack gap-2 mb-3">
        <?php foreach ($comments as $c): ?>
        <div class="border rounded p-2 small">
          <strong><?php echo esc_view($c->user_name ?: 'User'); ?></strong>
          <span class="text-muted"><?php echo esc_view($c->created_at); ?></span>
          <p class="mb-0 mt-1"><?php echo nl2br(esc_view($c->comment)); ?></p>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <form method="post" action="<?php echo site_url('defects/add-comment/'.$item->id); ?>">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
        <textarea name="comment" class="form-control mb-2" rows="2" placeholder="Add a comment…" required></textarea>
        <button type="submit" class="btn btn-sm btn-outline-primary">Post comment</button>
      </form>
    </div></div>
    <?php if (!empty($activity)): ?>
    <div class="card shadow-soft"><div class="card-body">
      <h2 class="h6 text-muted">Activity</h2>
      <ul class="list-unstyled small mb-0">
        <?php foreach ($activity as $act): ?>
        <li class="mb-1"><span class="text-muted"><?php echo esc_view($act->created_at); ?></span> — <?php echo esc_view($act->user_name ?: 'User'); ?>: <strong><?php echo esc_view($act->action); ?></strong><?php if (!empty($act->detail)): ?> — <?php echo esc_view($act->detail); ?><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    </div></div>
    <?php endif; ?>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-soft"><div class="card-body">
      <dl class="row mb-0 small">
        <dt class="col-5">Project</dt><dd class="col-7"><?php echo esc_view($item->project_name ?: '—'); ?></dd>
        <dt class="col-5">Release</dt><dd class="col-7"><?php echo $item->release_version ? esc_view($item->release_version . ' — ' . $item->release_title) : '—'; ?></dd>
        <dt class="col-5">Task</dt><dd class="col-7"><?php echo !empty($item->task_title) ? esc_view($item->task_title) : '—'; ?></dd>
        <dt class="col-5">Severity</dt><dd class="col-7"><?php echo esc_view($item->severity); ?></dd>
        <dt class="col-5">Priority</dt><dd class="col-7"><?php echo esc_view($item->priority); ?></dd>
        <dt class="col-5">Status</dt><dd class="col-7"><?php echo esc_view(str_replace('_',' ',$item->status)); ?></dd>
        <dt class="col-5">Due date</dt><dd class="col-7"><?php echo esc_view(!empty($item->due_date) ? $item->due_date : '—'); ?></dd>
        <dt class="col-5">Reporter</dt><dd class="col-7"><?php echo esc_view($item->reporter_name ?: '—'); ?></dd>
        <dt class="col-5">Assignee</dt><dd class="col-7"><?php echo esc_view($item->assignee_name ?: 'Unassigned'); ?></dd>
        <dt class="col-5">Verified by</dt><dd class="col-7"><?php echo esc_view(!empty($item->verifier_name) ? $item->verifier_name : '—'); ?></dd>
        <dt class="col-5">Resolved</dt><dd class="col-7"><?php echo esc_view($item->resolved_at ?: '—'); ?></dd>
        <dt class="col-5">Created</dt><dd class="col-7"><?php echo esc_view($item->created_at ?: '—'); ?></dd>
      </dl>
      <?php if ($can_delete): ?>
      <form method="post" action="<?php echo site_url('defects/delete/'.$item->id); ?>" class="mt-3" onsubmit="return confirm('Delete this defect?');">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
      </form>
      <?php endif; ?>
    </div></div>
  </div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
