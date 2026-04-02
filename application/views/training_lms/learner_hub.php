<?php
$waiting = !empty($learner_waiting_enrollment);
$is_mgr = !empty($is_lms_manager);
$this->load->view('partials/header', array('title' => 'Training — Module · Topic · Assignment · Assessment'));
?>
<div class="container-fluid py-4">
  <nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item active" aria-current="page">Training hub</li>
    </ol>
  </nav>
  <h1 class="h4 mb-2 fw-bold"><i class="bi bi-columns-gap text-primary me-2"></i>Training hub</h1>
  <p class="text-muted small mb-2">Overview: <strong>Module</strong> → <strong>Topic</strong> → <strong>Assignment</strong> (file) &amp; <strong>Assessment</strong> (test).</p>
  <?php if ($waiting): ?>
    <div class="alert alert-warning py-2 small mb-3">No module enrollments yet — course list below stays empty until an admin enrolls you.</div>
  <?php elseif (!$is_mgr && !empty($enrollment_gating_active)): ?>
    <div class="alert alert-info py-2 small mb-3">Course list shows only modules you are enrolled in.</div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <a class="card border-0 shadow-sm text-decoration-none h-100 text-body" href="<?php echo site_url('training'); ?>">
        <div class="card-body">
          <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:0.65rem;">Module &amp; topic</div>
          <div class="fw-semibold">Open modules</div>
          <div class="small text-muted">List modules, then topics</div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a class="card border-0 shadow-sm text-decoration-none h-100 text-body" href="<?php echo site_url('training/my-submissions'); ?>">
        <div class="card-body">
          <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:0.65rem;">Assignment</div>
          <div class="fw-semibold">My submissions</div>
          <div class="small text-muted">Files uploaded for assignments</div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a class="card border-0 shadow-sm text-decoration-none h-100 text-body" href="<?php echo site_url('training-assessment/my-assignments'); ?>">
        <div class="card-body">
          <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:0.65rem;">Assessment</div>
          <div class="fw-semibold">My assessments</div>
          <div class="small text-muted">Tests and results</div>
        </div>
      </a>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white fw-semibold">Module</div>
        <div class="card-body p-0">
          <?php if (empty($modules)): ?>
            <p class="text-muted small mb-0 p-3"><?php echo $waiting ? 'No enrollments — nothing to show here yet.' : 'No modules available.'; ?></p>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($modules as $m): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span><?php echo htmlspecialchars($m->title); ?></span>
                  <a class="btn btn-sm btn-primary" href="<?php echo site_url('training/module/' . (int) $m->id); ?>">Open module</a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Assessment</div>
        <div class="card-body p-0">
          <?php if (empty($assessment_assignments)): ?>
            <p class="text-muted small mb-0 p-3">No assessment assignments.</p>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($assessment_assignments as $a): ?>
                <li class="list-group-item small">
                  <strong><?php echo htmlspecialchars($a->title); ?></strong>
                  <?php if (!empty($a->completed_at)): ?>
                    <span class="badge bg-success ms-1">Done</span>
                    <a class="btn btn-sm btn-outline-primary float-end" href="<?php echo htmlspecialchars(!empty($a->result_url) ? $a->result_url : site_url('training-assessment/result-token/' . rawurlencode($a->access_token))); ?>">Result</a>
                  <?php else: ?>
                    <a class="btn btn-sm btn-primary float-end" href="<?php echo site_url('training-assessment/take/' . rawurlencode($a->access_token)); ?>">Start</a>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Assignment — recent uploads</div>
        <div class="card-body p-0">
          <?php if (empty($my_submissions)): ?>
            <p class="text-muted small mb-0 p-3">No submissions yet.</p>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($my_submissions as $s): ?>
                <li class="list-group-item small d-flex justify-content-between align-items-start">
                  <div>
                    <div><?php echo htmlspecialchars($s->original_filename); ?></div>
                    <div class="text-muted"><?php echo htmlspecialchars($s->module_title); ?> — <?php echo htmlspecialchars($s->topic_name); ?></div>
                  </div>
                  <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('training/download/' . (int) $s->id); ?>">Get</a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
