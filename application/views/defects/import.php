<?php $this->load->view('partials/header', ['title' => 'Import Defects']); ?>
  <div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0">Import Defects (CSV)</h1>
    <a class="btn btn-secondary" href="<?php echo site_url('defects'); ?>">Back</a>
  </div>

  <div class="card shadow-soft">
    <div class="card-body">
      <p class="text-muted mb-2">Upload a CSV with headers: <code>project_name, title, description, steps_to_reproduce, severity, priority, status, assigned_to, due_date</code></p>
      <div class="mb-3">
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url('assets/samples/defects_import_sample.csv'); ?>" download>
          <i class="bi bi-download me-1"></i>Download sample file
        </a>
      </div>
      <ul class="small text-muted mb-3">
        <li><strong>project_name</strong> — exact project name or code from Projects list (column <code>project</code> also works).</li>
        <li><strong>title</strong> — required; rows without a title are skipped.</li>
        <li><strong>description</strong> / <strong>steps_to_reproduce</strong> — optional text.</li>
        <li><strong>severity</strong> / <strong>priority</strong> — low, medium, high, critical (default medium).</li>
        <li><strong>status</strong> — open, in_progress, fixed, verified, closed, rejected (default open).</li>
        <li><strong>assigned_to</strong> — user ID; leave blank for unassigned.</li>
        <li><strong>due_date</strong> — optional SLA date (YYYY-MM-DD).</li>
      </ul>
      <?php $this->load->view('partials/import_errors'); ?>
      <?php if($this->session->flashdata('error')): ?>
        <div class="alert alert-danger py-2 mb-3"><?php echo esc_view($this->session->flashdata('error')); ?></div>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
        <div class="row g-2 align-items-center">
          <div class="col-12 col-sm-8">
            <input type="file" name="file" accept=".csv" class="form-control" required>
          </div>
          <div class="col-12 col-sm-auto">
            <button class="btn btn-primary" type="submit">Upload & Import</button>
          </div>
        </div>
      </form>
    </div>
  </div>
<?php $this->load->view('partials/footer'); ?>
