<?php $this->load->view('partials/header', array('title' => 'Assessment — My tests')); ?>
<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('training'); ?>">Module</a></li>
      <li class="breadcrumb-item active" aria-current="page">Assessment</li>
    </ol>
  </nav>
  <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:0.72rem;">Assessment</div>
  <h1 class="h4 mb-3">My tests</h1>
  <p class="text-muted small">Open each assessment below. You must be logged in for employee assignments.</p>
  <div class="list-group shadow-sm">
    <?php if (empty($assignments)): ?>
      <div class="list-group-item text-muted">No assignments found.</div>
    <?php else: ?>
      <?php foreach ($assignments as $a): ?>
        <?php
          $max = (int)$a->max_attempts;
          $used = (int)$a->attempts_used;
          $attemptsLabel = $max === 0 ? 'Attempts: unlimited' : ('Attempts used: ' . $used . ' / ' . $max);
          $remaining = $max === 0 ? null : max(0, $max - $used);
        ?>
        <div class="list-group-item d-flex flex-column flex-lg-row justify-content-between align-items-start gap-2">
          <div class="flex-grow-1">
            <strong><?php echo htmlspecialchars($a->title); ?></strong>
            <div class="small text-muted">Assigned <?php echo htmlspecialchars($a->assigned_at); ?></div>
            <div class="small mt-1">
              <span class="text-muted"><?php echo htmlspecialchars($attemptsLabel); ?></span>
              <?php if ($remaining !== null && $a->completed_at && (int)$a->allow_retake === 1): ?>
                <?php if ($remaining > 0): ?>
                  <span class="badge bg-info text-dark ms-1"><?php echo (int)$remaining; ?> left</span>
                <?php else: ?>
                  <span class="badge bg-secondary ms-1">No retakes left</span>
                <?php endif; ?>
              <?php endif; ?>
            </div>
            <?php if ($a->completed_at && $a->score_percent !== null && $a->score_percent !== ''): ?>
              <div class="mt-2">
                <span class="badge bg-<?php echo (isset($a->passed) && (int)$a->passed === 1) ? 'success' : 'danger'; ?>">
                  Score <?php echo htmlspecialchars(number_format((float)$a->score_percent, 1)); ?>%
                </span>
              </div>
            <?php endif; ?>
          </div>
          <div class="text-lg-end d-flex flex-wrap gap-2 align-items-center">
            <?php if ($a->completed_at): ?>
              <span class="badge bg-success">Completed</span>
              <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars(!empty($a->result_url) ? $a->result_url : site_url('training-assessment/result-token/' . rawurlencode($a->access_token))); ?>">Result</a>
            <?php else: ?>
              <span class="badge bg-warning text-dark">Not submitted</span>
              <a class="btn btn-sm btn-primary" href="<?php echo site_url('training-assessment/take/' . rawurlencode($a->access_token)); ?>">Start / continue</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
