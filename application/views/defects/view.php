<?php $this->load->view('partials/header', ['title' => $item->defect_number]); ?>
<div class="container-fluid py-3">
<?php
$can_edit = function_exists('has_module_access') && (has_module_access('defects_edit') || has_module_access('defects'));
$can_delete = function_exists('has_module_access') && (has_module_access('defects_delete') || has_module_access('defects'));
$actions = '';
if ($can_edit) {
    $actions .= '<a class="btn btn-primary btn-sm" href="'.site_url('defects/edit/'.$item->id).'"><i class="bi bi-pencil me-1"></i>Edit</a> ';
}
$this->load->view('partials/oms_page_head', ['title' => $item->defect_number, 'icon' => 'bi-bug', 'subtitle' => htmlspecialchars($item->title), 'actions_html' => $actions]);
?>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card shadow-soft mb-3"><div class="card-body">
      <h2 class="h6 text-muted">Description</h2>
      <p class="mb-0"><?php echo nl2br(htmlspecialchars($item->description ?: '—')); ?></p>
    </div></div>
    <div class="card shadow-soft"><div class="card-body">
      <h2 class="h6 text-muted">Steps to reproduce</h2>
      <p class="mb-0"><?php echo nl2br(htmlspecialchars($item->steps_to_reproduce ?: '—')); ?></p>
    </div></div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-soft"><div class="card-body">
      <dl class="row mb-0 small">
        <dt class="col-5">Project</dt><dd class="col-7"><?php echo htmlspecialchars($item->project_name ?: '—'); ?></dd>
        <dt class="col-5">Release</dt><dd class="col-7"><?php echo $item->release_version ? htmlspecialchars($item->release_version . ' — ' . $item->release_title) : '—'; ?></dd>
        <dt class="col-5">Severity</dt><dd class="col-7"><?php echo htmlspecialchars($item->severity); ?></dd>
        <dt class="col-5">Priority</dt><dd class="col-7"><?php echo htmlspecialchars($item->priority); ?></dd>
        <dt class="col-5">Status</dt><dd class="col-7"><?php echo htmlspecialchars(str_replace('_',' ',$item->status)); ?></dd>
        <dt class="col-5">Reporter</dt><dd class="col-7"><?php echo htmlspecialchars($item->reporter_name ?: '—'); ?></dd>
        <dt class="col-5">Assignee</dt><dd class="col-7"><?php echo htmlspecialchars($item->assignee_name ?: 'Unassigned'); ?></dd>
        <dt class="col-5">Resolved</dt><dd class="col-7"><?php echo htmlspecialchars($item->resolved_at ?: '—'); ?></dd>
        <dt class="col-5">Created</dt><dd class="col-7"><?php echo htmlspecialchars($item->created_at ?: '—'); ?></dd>
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
