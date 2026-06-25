<?php
$is_mgr = !empty($is_lms_manager);
$gating = !empty($enrollment_gating_active);
$this->load->view('partials/header', array('title' => 'Module — ' . $module->title));
?>
<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('training'); ?>">Module</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?php echo esc_view($module->title); ?></li>
    </ol>
  </nav>

  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:0.72rem;">Module</div>
      <h1 class="h4 mb-1"><?php echo esc_view($module->title); ?></h1>
      <p class="text-muted small mb-0">
        <?php if ($is_mgr): ?>
          <span class="badge bg-secondary me-1">Full catalogue</span> You see every topic, assignment, and assessment link (admin / LMS manager).
        <?php else: ?>
          Topics assigned with this module<?php echo $gating ? ' for your enrollment' : ''; ?>. Open a topic to upload files or take a linked test.
        <?php endif; ?>
      </p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('training'); ?>">All modules</a>
  </div>

  <?php if (!empty($module->description)): ?>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body small"><?php echo nl2br(esc_view($module->description)); ?></div>
    </div>
  <?php endif; ?>

  <h2 class="h6 text-uppercase text-muted mb-3">Topic</h2>

  <?php if (empty($topics)): ?>
    <div class="alert alert-info">No topics in this module yet.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($topics as $t): ?>
        <?php
        $has_a = (int) $t->has_assignment === 1;
        $has_q = (int) $t->has_assessment === 1;
        $aname = isset($t->assignment_display_name) ? trim((string) $t->assignment_display_name) : '';
        $atitle = isset($t->assessment_display_title) ? trim((string) $t->assessment_display_title) : '';
        $assign_line = '—';
        if ($has_a) {
            $assign_line = $aname !== '' ? esc_view($aname) : '<span class="text-warning">Assignment not configured</span>';
        }
        $assess_line = '—';
        if ($has_q) {
            $assess_line = $atitle !== '' ? esc_view($atitle) : '<span class="text-muted">Linked assessment</span>';
        }
        $done = !empty($t->learner_topic_done);
        ?>
        <div class="col-12">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                  <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:0.65rem;">Topic</div>
                  <h3 class="h6 mb-1"><?php echo esc_view($t->name); ?></h3>
                  <div class="small text-muted">
                    <i class="bi bi-clock me-1"></i><?php echo esc_view(number_format((float) $t->duration_hours, 1)); ?> h
                    <?php if (!empty($t->prerequisite_topic_id) && (int) $t->prerequisite_topic_id > 0): ?>
                      <span class="ms-2"><i class="bi bi-lock me-1"></i>Prerequisite topic required</span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <?php if ($done): ?>
                    <span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i>Topic complete</span>
                  <?php endif; ?>
                  <a class="btn btn-primary btn-sm" href="<?php echo site_url('training/topic/' . (int) $t->id); ?>">Open topic</a>
                </div>
              </div>
              <?php if (!empty($t->description)): ?>
                <p class="small text-muted mb-3"><?php echo nl2br(esc_view($t->description)); ?></p>
              <?php endif; ?>
              <div class="row g-2 small">
                <div class="col-md-6">
                  <div class="border rounded p-2 h-100 bg-light bg-opacity-50">
                    <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:0.7rem;">Assignment</div>
                    <div><?php echo $has_a ? $assign_line : '—'; ?></div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="border rounded p-2 h-100 bg-light bg-opacity-50">
                    <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:0.7rem;">Assessment</div>
                    <div><?php echo $has_q ? $assess_line : '—'; ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php $this->load->view('partials/footer'); ?>
