<?php $this->load->view('partials/header', ['title' => 'Import Projects']); ?>
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0">Import Projects (CSV)</h1>
    <a class="btn btn-secondary" href="<?php echo site_url('projects'); ?>">Back</a>
  </div>

  <div class="card shadow-soft">
    <div class="card-body">
      <p class="text-muted mb-2">Upload a CSV with headers: <code>code, name, status, start_date, end_date</code></p>
      <div class="mb-3">
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url('assets/samples/projects_import_sample.csv'); ?>" download>
          Download sample file
        </a>
      </div>
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
