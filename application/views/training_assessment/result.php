<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$isCand = empty($au->user_id);
$name = $isCand ? $au->candidate_name : (!empty($au->assignee_user_name) ? $au->assignee_user_name : 'Employee');
$this->load->view('partials/header', array(
  'title' => 'Assessment result',
  'with_sidebar' => (bool)(int)$this->session->userdata('user_id'),
));
$showRetake = !empty($show_retake);
$showCorrect = !empty($show_correct);
?>
<div class="container py-4">
  <?php if ($this->session->flashdata('ta_submit_notice')): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($this->session->flashdata('ta_submit_notice')); ?></div>
  <?php endif; ?>
  <?php if ($result): ?>
  <div class="card border-0 shadow-sm mb-4 bg-success bg-opacity-10 border-success">
    <div class="card-body text-center py-4">
      <div class="display-5 text-success mb-2" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></div>
      <h1 class="h4 mb-2">Thank you</h1>
      <p class="text-muted mb-0">Your responses have been submitted. Your score and question summary are below.</p>
    </div>
  </div>
  <?php endif; ?>
  <h2 class="h5 mb-3">Result: <?php echo htmlspecialchars($au->assessment_title); ?></h2>
  <?php if (!$result): ?>
    <div class="alert alert-warning">No result record found.</div>
  <?php else: ?>
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="text-muted small">Score</div>
            <div class="display-6 fw-bold"><?php echo htmlspecialchars(number_format((float)$result->score_percent, 1)); ?>%</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="text-muted small">Points</div>
            <div class="fs-4 fw-bold"><?php echo htmlspecialchars($result->earned_points); ?> / <?php echo htmlspecialchars($result->total_points); ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="text-muted small">Outcome</div>
            <div class="fs-4">
              <?php if ((int)$result->passed === 1): ?>
                <span class="badge bg-success">Passed</span>
              <?php else: ?>
                <span class="badge bg-danger">Failed</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="text-muted small">Candidate</div>
            <div class="small fw-semibold"><?php echo htmlspecialchars($name); ?></div>
            <?php if ($isCand && !empty($au->candidate_email)): ?>
              <div class="small text-muted"><?php echo htmlspecialchars($au->candidate_email); ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <?php if ($showRetake): ?>
      <?php echo form_open('training-assessment/retake-assessment', array('class' => 'mb-4', 'onsubmit' => 'return confirm("Start a new attempt? Your previous answers will be cleared.");')); ?>
      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
      <input type="hidden" name="access_token" value="<?php echo htmlspecialchars($au->access_token); ?>">
      <button type="submit" class="btn btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Retake assessment</button>
      <?php echo form_close(); ?>
    <?php endif; ?>

    <?php if ($result && !empty($au->id)): ?>
      <p class="mb-3">
        <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener" href="<?php echo site_url('training-assessment/certificate/' . (int) $au->id); ?>"><i class="bi bi-award me-1"></i>Print certificate</a>
      </p>
    <?php endif; ?>

    <h2 class="h6 text-muted mb-2">Question breakdown</h2>
    <?php if ($showCorrect): ?>
      <p class="small text-muted">Correct answers and feedback are shown because this assessment has &quot;training mode&quot; enabled.</p>
    <?php endif; ?>
    <?php if (empty($details)): ?>
    <p class="text-muted small">No saved answers for this attempt.</p>
    <?php else: ?>
    <div class="table-responsive card shadow-sm border-0">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Question</th>
            <th>Type</th>
            <?php if ($showCorrect): ?>
            <th>Your answer</th>
            <th>Correct / feedback</th>
            <?php endif; ?>
            <th>Points</th>
            <th>Verdict</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($details as $d): ?>
          <tr>
            <td><?php
              $sn = strip_tags($d->question_text);
              if (function_exists('mb_strimwidth')) {
                $sn = mb_strimwidth($sn, 0, 200, '…');
              } elseif (strlen($sn) > 200) {
                $sn = substr($sn, 0, 197) . '…';
              }
              echo nl2br(htmlspecialchars($sn));
            ?></td>
            <td><span class="badge bg-secondary"><?php echo htmlspecialchars(strtoupper($d->question_type)); ?></span></td>
            <?php if ($showCorrect): ?>
            <td class="small"><?php echo isset($d->ta_your_answer) ? nl2br(htmlspecialchars($d->ta_your_answer !== '' ? $d->ta_your_answer : '—')) : '—'; ?></td>
            <td class="small"><?php echo isset($d->ta_correct_summary) ? nl2br(htmlspecialchars($d->ta_correct_summary)) : '—'; ?></td>
            <?php endif; ?>
            <td><?php echo htmlspecialchars($d->points_earned); ?> / <?php echo htmlspecialchars($d->question_points); ?></td>
            <td>
              <?php if ((int)$d->is_graded_correct === 1): ?>
                <span class="text-success" aria-label="Correct"><i class="bi bi-check-circle"></i></span>
              <?php elseif ((int)$d->is_graded_correct === 0): ?>
                <span class="text-danger" aria-label="Incorrect"><i class="bi bi-x-circle"></i></span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="mt-4">
    <?php if ((int)$this->session->userdata('user_id')): ?>
      <a href="<?php echo site_url('training-assessment'); ?>" class="btn btn-outline-secondary">Dashboard</a>
    <?php endif; ?>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
