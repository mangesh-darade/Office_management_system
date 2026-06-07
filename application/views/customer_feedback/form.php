<?php $this->load->view('partials/header', ['title' => 'Log Feedback']); ?>
<div class="container-fluid py-3">
<h1 class="h4 fw-bold mb-3">Log Customer Feedback</h1>
<div class="card shadow-soft"><div class="card-body"><form method="post"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="row g-3 mb-3"><div class="col-md-6"><label class="form-label">Client</label><select name="client_id" class="form-select"><option value="">—</option><?php foreach ($clients as $c): ?><option value="<?php echo (int)$c->id; ?>"><?php echo htmlspecialchars($c->company_name); ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Project</label><select name="project_id" class="form-select"><option value="">—</option><?php foreach ($projects as $p): ?><option value="<?php echo (int)$p->id; ?>"><?php echo htmlspecialchars($p->name); ?></option><?php endforeach; ?></select></div></div>
<div class="mb-3"><label class="form-label">Customer name</label><input name="customer_name" class="form-control"></div>
<div class="mb-3"><label class="form-label">Rating (1–5)</label><input type="number" min="1" max="5" name="rating" class="form-control" value="5" required></div>
<div class="mb-3"><label class="form-label">Feedback</label><textarea name="feedback_text" class="form-control" rows="5" required></textarea></div>
<button class="btn btn-primary">Save</button> <a href="<?php echo site_url('customer-feedback'); ?>" class="btn btn-outline-secondary">Cancel</a>
</form></div></div></div>
<?php $this->load->view('partials/footer'); ?>
