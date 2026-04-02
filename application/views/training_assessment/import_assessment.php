<?php $this->load->view('partials/header', array('title' => 'Import assessment from CSV')); ?>
<div class="container py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1"><i class="bi bi-file-earmark-arrow-up me-2"></i>Import assessment (CSV)</h1>
      <p class="text-muted small mb-0">Create one assessment and all questions in a single upload.</p>
    </div>
    <a href="<?php echo site_url('training-assessment'); ?>" class="btn btn-outline-secondary btn-sm">Back to dashboard</a>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white fw-semibold">1. Download sample</div>
        <div class="card-body">
          <p class="small text-muted">Use the same column headers. The first data row must include <strong>assessment_title</strong> and assessment settings plus the first question. Further rows can leave assessment columns blank and only fill question fields.</p>
          <p class="small mb-2"><strong>Question types:</strong> <code>mcq</code>, <code>text</code>, <code>coding</code></p>
          <p class="small mb-2"><strong>MCQ:</strong> fill <code>opt1</code>…<code>opt4</code> (at least two); <code>correct_option</code> = 1 for first option, 2 for second, etc.</p>
          <p class="small mb-3"><strong>Coding:</strong> <code>coding_language</code> = php or js; <code>coding_expected_output</code> for auto-scoring (trimmed match).</p>
          <?php if (!empty($sample_exists)): ?>
            <a class="btn btn-outline-primary" href="<?php echo site_url('training-assessment/import/sample'); ?>">
              <i class="bi bi-download me-1"></i>Download sample CSV
            </a>
          <?php else: ?>
            <div class="alert alert-warning small mb-0">Sample file missing at <code>samples/training_assessment_import_sample.csv</code>. You can still upload your own CSV with the documented columns.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white fw-semibold">2. Upload CSV</div>
        <div class="card-body">
          <?php echo form_open_multipart('training-assessment/import/process'); ?>
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <div class="mb-3">
            <label class="form-label">CSV file (UTF-8, max 2 MB)</label>
            <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload me-1"></i>Import to database</button>
          <?php echo form_close(); ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white small fw-semibold">Expected columns (header row)</div>
    <div class="card-body small font-monospace text-break">
      assessment_title, assessment_description, time_limit_minutes, passing_marks, randomize_questions, shuffle_options, max_attempts, allow_retake, status, question_type, question_text, points, coding_language, model_answer, coding_expected_output, sort_order, opt1, opt2, opt3, opt4, correct_option
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
