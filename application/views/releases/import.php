<?php $this->load->view('partials/header', ['title' => 'Import Releases']); ?>
  <div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0">Import Releases (CSV)</h1>
    <a class="btn btn-secondary" href="<?php echo site_url('releases'); ?>">Back</a>
  </div>

  <div class="card shadow-soft">
    <div class="card-body">
      <p class="text-muted mb-2">Upload a CSV with headers: <code>project_name, version, title, description, status, planned_date</code></p>
      <div class="mb-3">
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url('assets/samples/releases_import_sample.csv'); ?>" download>
          <i class="bi bi-download me-1"></i>Download sample file
        </a>
      </div>
      <ul class="small text-muted mb-3">
        <li><strong>project_name</strong> — exact project name or code (column <code>project</code> also works).</li>
        <li><strong>version</strong> / <strong>title</strong> — required.</li>
        <li><strong>status</strong> — planned, in_progress, released, cancelled (default planned).</li>
        <li><strong>planned_date</strong> — optional (YYYY-MM-DD).</li>
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
