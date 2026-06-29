<?php $this->load->view('partials/header', ['title' => 'Article']); ?>
<div class="oms-form-compact">
<div class="container-fluid py-3">
<h1 class="h4 fw-bold mb-3"><?php echo $action==='edit'?'Edit':'New'; ?> Article</h1>
<div class="card shadow-soft oms-form-card"><div class="card-body"><form method="post"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" required value="<?php echo $item?esc_view($item->title):''; ?>"></div>
<div class="mb-3"><label class="form-label">Summary</label><input name="summary" class="form-control" value="<?php echo $item?esc_view($item->summary):''; ?>"></div>
<div class="row g-2 oms-form-grid mb-3"><div class="col-md-4"><label class="form-label">Category</label><input name="category" class="form-control" value="<?php echo $item?esc_view($item->category):''; ?>"></div><div class="col-md-4"><label class="form-label">Tags</label><input name="tags" class="form-control" value="<?php echo $item?esc_view($item->tags):''; ?>"></div><div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach (['draft','published','archived'] as $s): ?><option value="<?php echo $s; ?>" <?php echo ($item && $item->status===$s)?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?></select></div></div>
<div class="mb-3"><label class="form-label">Body</label><textarea name="body" class="form-control" rows="10" required><?php echo $item?esc_view($item->body):''; ?></textarea></div>
<button class="btn btn-primary">Save</button> <a href="<?php echo site_url('knowledge-base'); ?>" class="btn btn-outline-secondary">Cancel</a>
</form></div></div></div>
</div>
<?php $this->load->view('partials/footer'); ?>
