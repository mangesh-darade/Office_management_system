<?php $this->load->view('partials/header', ['title' => 'Import Requirements']); ?>
  <div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0">Import Requirements (CSV)</h1>
    <a class="btn btn-secondary" href="<?php echo site_url('requirements'); ?>">Back</a>
  </div>

  <div class="card shadow-soft">
    <div class="card-body">
      <p class="text-muted mb-2">Upload a CSV with headers: <code>client_name, title, project_name, description, priority, status, requirement_type, received_date, expected_delivery_date</code></p>
      <div class="mb-3">
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url('assets/samples/requirements_import_sample.csv'); ?>" download>
          <i class="bi bi-download me-1"></i>Download sample file
        </a>
      </div>
      <ul class="small text-muted mb-3">
        <li><strong>client_name</strong> — required; must match Clients list (company name or code).</li>
        <li><strong>title</strong> — required.</li>
        <li><strong>project_name</strong> — optional; project name or code from Projects.</li>
        <li><strong>priority</strong> — low, medium, high, urgent (default medium).</li>
        <li><strong>status</strong> — received, under_review, approved, in_progress, completed, on_hold, rejected, cancelled (default received).</li>
      </ul>
      <?php $this->load->view('partials/import_errors'); ?>
      <?php if($this->session->flashdata('error')): ?>
        <div class="alert alert-danger py-2 mb-3"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
      <?php endif; ?>
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
