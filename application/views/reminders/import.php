<?php $this->load->view('partials/header', ['title' => 'Import Reminders from CSV']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Import Reminders from CSV</h1>
  <a class="btn btn-light btn-sm" href="<?php echo site_url('reminders'); ?>">Back</a>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<div class="card shadow-soft">
  <div class="card-body">
    <p class="small text-muted mb-3">
      Upload a CSV file with one email per row. Optional <code>name</code> column is used for the {name} placeholder in templates.
    </p>
    <div class="mb-3">
      <a href="<?php echo site_url('reminders/import-sample'); ?>" class="btn btn-outline-secondary btn-sm">Download Sample CSV</a>
    </div>
    <form method="post" action="<?php echo site_url('reminders/import'); ?>" enctype="multipart/form-data" class="vstack gap-3">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">CSV File</label>
          <input type="file" name="csv_file" accept=".csv" class="form-control" required>
          <div class="form-text">Expected columns (header row): <code>email</code> (required), <code>name</code> (optional).</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Template</label>
          <select name="tpl_code" class="form-select" required>
            <option value="">-- Select Template --</option>
            <option value="daily_morning">Morning Template</option>
            <option value="daily_night">Night Template</option>
            <option value="bulk_manual">Bulk Mail Template</option>
          </select>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">From Email (optional)</label>
          <input type="email" name="from_email" class="form-control" placeholder="e.g. you@domain.com">
        </div>
        <div class="col-md-4">
          <label class="form-label">From Name (optional)</label>
          <input type="text" name="from_name" class="form-control" placeholder="Your name">
        </div>
      </div>
      <div>
        <button class="btn btn-primary" type="submit">Queue Reminders from CSV</button>
        <a class="btn btn-light" href="<?php echo site_url('reminders'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
