<?php $this->load->view('partials/header', ['title' => 'Send Cheer']); ?>
<div class="container-fluid py-3"><h1 class="h4 fw-bold mb-3"><i class="bi bi-cup-hot text-warning me-2"></i>Send a cheer</h1>
<div class="card shadow-soft"><div class="card-body"><form method="post"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="mb-3"><label class="form-label">Colleague</label><select name="recipient_id" class="form-select" required><option value="">Select…</option><?php foreach ($users as $u): ?><option value="<?php echo (int)$u->id; ?>"><?php echo htmlspecialchars($u->name); ?></option><?php endforeach; ?></select></div>
<div class="mb-3"><label class="form-label">Message (optional)</label><textarea name="message" class="form-control" rows="3" placeholder="Thanks for helping with…"></textarea></div>
<button class="btn btn-primary">Send cheer</button> <a href="<?php echo site_url('rewards'); ?>" class="btn btn-outline-secondary">Cancel</a>
</form></div></div></div>
<?php $this->load->view('partials/footer'); ?>
