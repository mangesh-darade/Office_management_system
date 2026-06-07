<?php $this->load->view('partials/header', ['title' => 'Submit Certification']); ?>
<div class="container-fluid py-3">
<h1 class="h4 fw-bold mb-3">Submit Certification</h1>
<div class="card shadow-soft"><div class="card-body"><form method="post"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="mb-3"><label class="form-label">Certification name</label><input name="cert_name" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Issuer</label><input name="issuer" class="form-control"></div>
<div class="row g-3 mb-3"><div class="col-md-6"><label class="form-label">Certified on</label><input type="date" name="certified_on" class="form-control"></div><div class="col-md-6"><label class="form-label">Expires on</label><input type="date" name="expires_on" class="form-control"></div></div>
<div class="mb-3"><label class="form-label">Credential ID</label><input name="credential_id" class="form-control"></div>
<div class="mb-3"><label class="form-label">Evidence URL</label><input name="evidence_url" class="form-control" placeholder="Link to certificate PDF or badge"></div>
<button class="btn btn-primary">Submit for approval</button> <a href="<?php echo site_url('certifications'); ?>" class="btn btn-outline-secondary">Cancel</a>
</form></div></div></div>
<?php $this->load->view('partials/footer'); ?>
