<?php $this->load->view('partials/header', ['title' => ($action==='edit'?'Edit':'New').' Release']); ?>
<div class="container-fluid py-3">
<h1 class="h4 fw-bold mb-3"><?php echo $action==='edit'?'Edit':'New'; ?> Release</h1>
<div class="card shadow-soft"><div class="card-body">
<form method="post"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="row g-3">
  <div class="col-md-4"><label class="form-label">Project</label><select name="project_id" class="form-select" required><?php foreach ($projects as $p): ?><option value="<?php echo (int)$p->id; ?>" <?php echo ($item && (int)$item->project_id===(int)$p->id)?'selected':''; ?>><?php echo htmlspecialchars($p->name); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><label class="form-label">Version</label><input name="version" class="form-control" required value="<?php echo $item?htmlspecialchars($item->version):''; ?>"></div>
  <div class="col-md-3"><label class="form-label">Planned date</label><input type="date" name="planned_date" class="form-control" value="<?php echo $item?htmlspecialchars($item->planned_date):''; ?>"></div>
  <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach (['planned','in_progress','released','cancelled'] as $s): ?><option value="<?php echo $s; ?>" <?php echo ($item && $item->status===$s)?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?></select></div>
  <div class="col-12"><label class="form-label">Title</label><input name="title" class="form-control" required value="<?php echo $item?htmlspecialchars($item->title):''; ?>"></div>
  <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4"><?php echo $item?htmlspecialchars($item->description):''; ?></textarea></div>
</div>
<div class="mt-3"><button class="btn btn-primary">Save</button> <a class="btn btn-outline-secondary" href="<?php echo site_url('releases'); ?>">Cancel</a></div>
</form></div></div></div>
<?php $this->load->view('partials/footer'); ?>
