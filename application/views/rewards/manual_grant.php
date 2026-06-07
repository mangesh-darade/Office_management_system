<?php $this->load->view('partials/header', ['title' => 'Manual Grant']); ?>
<div class="container-fluid py-3"><h1 class="h4 fw-bold mb-3">Manual point grant</h1>
<div class="card shadow-soft"><div class="card-body"><form method="post"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="mb-3"><label class="form-label">Employee</label><select name="user_id" class="form-select" required><?php foreach ($users as $u): ?><option value="<?php echo (int)$u->id; ?>"><?php echo htmlspecialchars($u->name); ?></option><?php endforeach; ?></select></div>
<div class="mb-3"><label class="form-label">Points</label><input type="number" step="0.01" name="points" class="form-control" required value="50"></div>
<div class="mb-3"><label class="form-label">Label</label><input name="label" class="form-control" required placeholder="Above & beyond contribution"></div>
<div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
<button class="btn btn-primary">Grant points</button> <a href="<?php echo site_url('rewards/rules'); ?>" class="btn btn-outline-secondary">Cancel</a>
</form></div></div></div>
<?php $this->load->view('partials/footer'); ?>
