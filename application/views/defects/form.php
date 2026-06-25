<?php $this->load->view('partials/header', ['title' => ($action==='edit'?'Edit':'Log').' Defect']); ?>
<div class="container-fluid py-3">
<h1 class="h4 fw-bold mb-3"><?php echo $action==='edit'?'Edit':'Log'; ?> Defect</h1>
<div class="card shadow-soft"><div class="card-body">
<form method="post"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="row g-3">
  <div class="col-md-4"><label class="form-label">Project</label><select name="project_id" class="form-select" required><?php foreach ($projects as $p): ?><option value="<?php echo (int)$p->id; ?>" <?php echo ($item && (int)$item->project_id===(int)$p->id)?'selected':''; ?>><?php echo esc_view($p->name); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-4"><label class="form-label">Release (optional)</label><select name="release_id" class="form-select"><option value="">—</option><?php foreach ($releases as $rel): ?><option value="<?php echo (int)$rel->id; ?>" <?php echo ($item && (int)$item->release_id===(int)$rel->id)?'selected':''; ?>><?php echo esc_view($rel->version . ' — ' . $rel->title); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-4"><label class="form-label">Related task (optional)</label><select name="task_id" class="form-select"><option value="">—</option><?php foreach ($tasks as $t): ?><option value="<?php echo (int)$t->id; ?>" <?php echo ($item && (int)$item->task_id===(int)$t->id)?'selected':''; ?>><?php echo esc_view($t->title); ?></option><?php endforeach; ?></select></div>
  <div class="col-12"><label class="form-label">Title</label><input name="title" class="form-control" required value="<?php echo $item?esc_view($item->title):''; ?>"></div>
  <div class="col-md-3"><label class="form-label">Severity</label><select name="severity" class="form-select"><?php foreach (['low','medium','high','critical'] as $s): ?><option value="<?php echo $s; ?>" <?php echo ($item && $item->severity===$s)?'selected':(!$item && $s==='medium'?'selected':''); ?>><?php echo $s; ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><label class="form-label">Priority</label><select name="priority" class="form-select"><?php foreach (['low','medium','high','critical'] as $s): ?><option value="<?php echo $s; ?>" <?php echo ($item && $item->priority===$s)?'selected':(!$item && $s==='medium'?'selected':''); ?>><?php echo $s; ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach (['open','in_progress','fixed','verified','closed','rejected'] as $s): ?><option value="<?php echo $s; ?>" <?php echo ($item && $item->status===$s)?'selected':(!$item && $s==='open'?'selected':''); ?>><?php echo str_replace('_',' ',$s); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><label class="form-label">Assign to</label><select name="assigned_to" class="form-select"><option value="">Unassigned</option><?php foreach ($members as $m): ?><option value="<?php echo (int)$m->id; ?>" <?php echo ($item && (int)$item->assigned_to===(int)$m->id)?'selected':''; ?>><?php echo esc_view($m->name); ?></option><?php endforeach; ?></select></div>
  <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?php echo $item?esc_view($item->description):''; ?></textarea></div>
  <div class="col-12"><label class="form-label">Steps to reproduce</label><textarea name="steps_to_reproduce" class="form-control" rows="4"><?php echo $item?esc_view($item->steps_to_reproduce):''; ?></textarea></div>
</div>
<div class="mt-3"><button class="btn btn-primary">Save</button> <a class="btn btn-outline-secondary" href="<?php echo site_url($item ? 'defects/view/'.$item->id : 'defects'); ?>">Cancel</a></div>
</form></div></div></div>
<?php $this->load->view('partials/footer'); ?>
