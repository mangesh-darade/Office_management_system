<?php $this->load->view('partials/header', ['title' => 'Event']); ?>
<div class="oms-form-compact">
<div class="container-fluid py-3">
<h1 class="h4 fw-bold mb-3"><?php echo $action==='edit'?'Edit':'New'; ?> Event</h1>
<div class="card shadow-soft oms-form-card"><div class="card-body"><form method="post"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" required value="<?php echo $item?esc_view($item->title):''; ?>"></div>
<div class="row g-2 oms-form-grid mb-3"><div class="col-md-6"><label class="form-label">Start</label><input type="datetime-local" name="start_at" class="form-control" required value="<?php echo $item?date('Y-m-d\TH:i', strtotime($item->start_at)):''; ?>"></div><div class="col-md-6"><label class="form-label">End</label><input type="datetime-local" name="end_at" class="form-control" value="<?php echo ($item && $item->end_at)?date('Y-m-d\TH:i', strtotime($item->end_at)):''; ?>"></div></div>
<div class="mb-3"><label class="form-label">Location</label><input name="location" class="form-control" value="<?php echo $item?esc_view($item->location):''; ?>"></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4"><?php echo $item?esc_view($item->description):''; ?></textarea></div>
<?php if ($action==='edit'): ?><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" <?php echo ($item->is_active)?'checked':''; ?>><label class="form-check-label">Active</label></div><?php endif; ?>
<button class="btn btn-primary">Save</button> <a href="<?php echo site_url('events'); ?>" class="btn btn-outline-secondary">Cancel</a>
</form></div></div></div>
</div>
<?php $this->load->view('partials/footer'); ?>
