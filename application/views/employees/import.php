<?php $this->load->view('partials/header', ['title' => 'Import Employees']); ?>
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0">Import Employees (CSV)</h1>
    <a class="btn btn-secondary" href="<?php echo site_url('employees'); ?>">Back</a>
  </div>

  <div class="card shadow-soft">
    <div class="card-body">
      <p class="text-muted">Upload a CSV with headers: <code>emp_code, first_name, last_name, email, department, designation, phone, join_date</code></p>
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
