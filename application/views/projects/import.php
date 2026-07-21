<?php $this->load->view('partials/header', ['title' => 'Import Projects']); ?>
  <div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0">Import Projects (CSV)</h1>
    <a class="btn btn-secondary" href="<?php echo site_url('projects'); ?>">Back</a>
  </div>

  <div class="card shadow-soft">
    <div class="card-body">
      <p class="text-muted mb-2">Upload a CSV with headers: <code>code, name, status, start_date, end_date, estimate_hours</code></p>
      <div class="mb-3">
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url('assets/samples/projects_import_sample.csv'); ?>" download>
          <i class="bi bi-download me-1"></i>Download sample file
        </a>
      </div>
      <ul class="small text-muted mb-3">
        <li><strong>name</strong> — required.</li>
        <li><strong>status</strong> — planned, active, on_hold, completed, cancelled (default planned).</li>
        <li><strong>estimate_hours</strong> — optional decimal hours (e.g. 40 or 2.5).</li>
        <li>Duplicate <strong>code</strong> values are skipped with a row error.</li>
      </ul>
      <?php $this->load->view('partials/import_errors'); ?>
      <form method="post" enctype="multipart/form-data">
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
