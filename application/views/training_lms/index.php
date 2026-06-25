<?php
$is_mgr = !empty($is_lms_manager);
$gating = !empty($enrollment_gating_active);
$waiting = !empty($learner_waiting_enrollment);
$this->load->view('partials/header', array('title' => 'Module — List'));
?>
<div class="container-fluid py-4">
  <nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item active" aria-current="page">Module</li>
    </ol>
  </nav>
  <h1 class="h4 mb-3 fw-bold"><i class="bi bi-journal-richtext text-primary me-2"></i>Module</h1>
  <p class="text-muted small mb-2">
    Flow: <strong>Module</strong> → <strong>Topics</strong> (each shows file assignment + assessment) → <strong>Open topic</strong> to submit or take the test.
    <a href="<?php echo site_url('training/my-training'); ?>">My training hub</a> summarizes uploads and assessments.
  </p>
  <?php if ($is_mgr): ?>
    <div class="alert alert-secondary py-2 small mb-3"><i class="bi bi-shield-check me-1"></i>Admin / LMS manager: you see <strong>all</strong> active modules regardless of enrollments.</div>
  <?php elseif ($waiting): ?>
    <div class="alert alert-warning py-2 small mb-3">You are not enrolled in any training module yet. Ask an administrator to enroll you, or use direct links they share.</div>
  <?php elseif ($gating): ?>
    <div class="alert alert-info py-2 small mb-3"><i class="bi bi-funnel me-1"></i>Only modules you are enrolled in are listed.</div>
  <?php endif; ?>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php if (empty($modules)): ?>
    <div class="alert alert-info"><?php echo $waiting ? 'No modules to show until you are enrolled.' : 'No active modules yet.'; ?></div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($modules as $m): ?>
        <div class="col-md-6 col-xl-4">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex flex-column">
              <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:0.7rem;">Module</div>
              <h2 class="h5 card-title"><?php echo esc_view($m->title); ?></h2>
              <?php if (!empty($m->description)): ?>
                <p class="card-text small text-muted flex-grow-1"><?php echo nl2br(esc_view($m->description)); ?></p>
              <?php else: ?>
                <p class="card-text small text-muted flex-grow-1">&nbsp;</p>
              <?php endif; ?>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="badge bg-secondary"><?php echo (int) $m->topic_count; ?> topic(s)</span>
                <a class="btn btn-primary btn-sm" href="<?php echo site_url('training/module/' . (int) $m->id); ?>">Open module</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php $this->load->view('partials/footer'); ?>
