<?php $this->load->view('partials/header', array('title' => 'LMS — Office CSV feeds')); ?>
<div class="container-fluid py-4">
  <h1 class="h4 mb-3 fw-bold"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Office data feeds (CSV)</h1>
  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <p class="text-muted small mb-4">
    Download flat files aligned with training catalog, assignment definitions, and submission tracking.
    For an on-screen table (Topic, assignment, submitter, attachment, score, status), use
    <a href="<?php echo site_url('training-lms-admin/assignment-submissions'); ?>">All assignment submissions</a>.
    Use UTF-8 in Excel if prompted. Assessment question bank and per-answer attempt export are under
    <a href="<?php echo site_url('training-assessment/report'); ?>">Training assessment report</a>.
  </p>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <h2 class="h6 card-title">Module &amp; topic catalog</h2>
          <p class="small text-muted mb-3">Module Name, Topic, Details, Prerequisites, Duration, flags, linked assessment.</p>
          <a class="btn btn-primary btn-sm" href="<?php echo site_url('training-lms-admin/office-feed/export/catalog'); ?>"><i class="bi bi-download me-1"></i>Download CSV</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <h2 class="h6 card-title">Assignment definitions</h2>
          <p class="small text-muted mb-3">Module Name, Topic, Assignment Name, Assignment Details, Max submissions (0 = unlimited per learner).</p>
          <a class="btn btn-primary btn-sm mb-2" href="<?php echo site_url('training-lms-admin/office-feed/export/assignments'); ?>"><i class="bi bi-download me-1"></i>Download CSV</a>
          <hr class="my-3">
          <h3 class="h6">Import assignments</h3>
          <p class="small text-muted mb-2">Updates existing topics only (matches module + topic name). Turns on &ldquo;Has file assignment&rdquo; and creates or updates the assignment row.</p>
          <?php echo form_open_multipart('training-lms-admin/office-feed/import-assignments'); ?>
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <div class="mb-2">
            <input type="file" name="csv_file" class="form-control form-control-sm" accept=".csv,text/csv" required>
          </div>
          <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-upload me-1"></i>Upload CSV</button>
          <?php echo form_close(); ?>
          <p class="small text-muted mt-2 mb-0">Sample: <code>samples/lms_assignments_import_sample.csv</code></p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <h2 class="h6 card-title">Assignment submissions</h2>
          <p class="small text-muted mb-3">Submitted by, date, attachment name, score, assessed by, status.</p>
          <a class="btn btn-primary btn-sm" href="<?php echo site_url('training-lms-admin/office-feed/export/submissions'); ?>"><i class="bi bi-download me-1"></i>Download CSV</a>
        </div>
      </div>
    </div>
  </div>

  <p class="mt-4 mb-0"><a href="<?php echo site_url('training-lms-admin'); ?>">&larr; LMS admin</a></p>
</div>
<?php $this->load->view('partials/footer'); ?>
