<?php
$this->load->view('partials/header', array('title' => 'Import CSV — Training & Assessment'));
$ready = isset($ready) ? (bool) $ready : false;
?>
<div class="container-fluid py-4">
  <nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('training-assessment'); ?>">Dashboard</a></li>
      <li class="breadcrumb-item active">Import CSV</li>
    </ol>
  </nav>

  <div class="mb-4">
    <h1 class="h4 mb-1 fw-bold"><i class="bi bi-file-earmark-spreadsheet text-primary me-2"></i>Import training data</h1>
    <p class="text-muted mb-0">Row 1 = sections · Row 2 = column names · <strong>Sample data starts row 3</strong> (one item per row).</p>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php if (!$ready): ?>
    <div class="alert alert-warning">Database tables not installed. Run training SQL scripts first.</div>
  <?php else: ?>

  <div class="row g-3 mb-4">
    <div class="col-md-5">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <h2 class="h6 fw-semibold">1. Download sample</h2>
          <p class="small text-muted">Row 1 + Row 2 = do not delete. Edit sample data from row 3.</p>
          <a class="btn btn-primary btn-sm" href="<?php echo site_url('training/import/sample/all'); ?>">
            <i class="bi bi-download me-1"></i>Download sample CSV
          </a>
        </div>
      </div>
    </div>
    <div class="col-md-7">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <h2 class="h6 fw-semibold">2. Upload</h2>
          <?php echo form_open_multipart('training/import/process'); ?>
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <input type="hidden" name="import_type" value="all">
          <input type="file" name="csv_file" class="form-control mb-2" accept=".csv" required>
          <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-upload me-1"></i>Import</button>
          <?php echo form_close(); ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-semibold">Horizontal column blocks</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm mb-0 small">
          <thead class="table-light">
            <tr><th>Block</th><th>Columns (row 2)</th><th>When to fill</th></tr>
          </thead>
          <tbody>
            <tr><td><span class="badge bg-primary">TRAINING</span></td><td>Name · Description</td><td>One row per training program</td></tr>
            <tr><td><span class="badge bg-info text-dark">TOPIC</span></td><td>Training · Name · Description · Test</td><td>One row per topic (link test name if needed)</td></tr>
            <tr><td><span class="badge bg-warning text-dark">TEST</span></td><td>Name · Description · Minutes · Pass%</td><td>One row per assessment / quiz</td></tr>
            <tr><td><span class="badge bg-success">QUESTION</span></td><td>Test · Question · A · B · C · D · Correct</td><td>One row per MCQ question</td></tr>
            <tr><td><span class="badge bg-secondary">ASSIGNMENT</span></td><td>Training · Topic · Title · Description</td><td>One row per file assignment</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-white fw-semibold small">How it looks in Excel</div>
    <div class="card-body small">
      <p class="text-muted mb-2"><strong>Row 1</strong> = TRAINING · TOPIC · TEST · QUESTION · ASSIGNMENT &nbsp;|&nbsp; <strong>Row 2</strong> = Name, Description, … &nbsp;|&nbsp; <strong>Row 3+</strong> = sample data (fill one block per row only)</p>
      <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0 font-monospace" style="font-size:11px">
          <tr class="table-light fw-semibold text-center">
            <td colspan="2">TRAINING</td><td></td>
            <td colspan="4">TOPIC</td>
            <td colspan="4">TEST</td>
            <td colspan="7">QUESTION</td>
            <td colspan="4">ASSIGNMENT</td>
          </tr>
          <tr class="table-secondary">
            <td>Name</td><td>Description</td><td></td>
            <td>Training</td><td>Name</td><td>Description</td><td>Test</td>
            <td>Name</td><td>Description</td><td>Minutes</td><td>Pass%</td>
            <td>Test</td><td>Question</td><td>A</td><td>B</td><td>C</td><td>D</td><td>Correct</td>
            <td>Training</td><td>Topic</td><td>Title</td><td>Description</td>
          </tr>
          <tr>
            <td>Onboarding</td><td>New employee program</td><td colspan="20"></td>
          </tr>
          <tr>
            <td colspan="3"></td>
            <td>Onboarding</td><td>Welcome</td><td>Orientation</td><td></td>
            <td colspan="14"></td>
          </tr>
          <tr>
            <td colspan="7"></td>
            <td>Safety Quiz</td><td>Safety check</td><td>30</td><td>60</td>
            <td colspan="10"></td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <?php endif; ?>
</div>
<?php $this->load->view('partials/footer'); ?>
