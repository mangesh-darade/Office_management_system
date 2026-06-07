<?php $this->load->view('partials/header', ['title' => 'Office Closing Checklist']); ?>
<div class="container-fluid py-3">
<h1 class="h4 fw-bold mb-3">Office closing checklist</h1>
<p class="text-muted">Submit once per day after completing the closing checklist (+30 points).</p>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div><?php endif; ?>
<div class="card shadow-soft"><div class="card-body">
<form method="post"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="mb-3"><label class="form-label">Date</label><input type="date" name="checklist_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
<div class="mb-3"><label class="form-label">Notes (optional)</label><textarea name="notes" class="form-control" rows="3" placeholder="Lights off, doors locked, etc."></textarea></div>
<button class="btn btn-primary">Submit checklist</button> <a class="btn btn-outline-secondary" href="<?php echo site_url('rewards'); ?>">Cancel</a>
</form></div></div></div>
<?php $this->load->view('partials/footer'); ?>
