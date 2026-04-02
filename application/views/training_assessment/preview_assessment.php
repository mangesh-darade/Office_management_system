<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('partials/header', array('title' => 'Preview — ' . $assessment->title));
?>
<div class="container py-4">
  <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-eye me-2"></i><strong>Candidate preview</strong> — read-only. No timer or scoring.</span>
    <a class="btn btn-sm btn-outline-dark" href="<?php echo site_url('training-assessment/questions/' . (int)$assessment->id); ?>">Back to questions</a>
  </div>
  <h1 class="h4 mb-3"><?php echo htmlspecialchars($assessment->title); ?></h1>
  <?php if (!empty($assessment->description)): ?>
    <p class="text-muted"><?php echo nl2br(htmlspecialchars($assessment->description)); ?></p>
  <?php endif; ?>

  <?php if (empty($questions)): ?>
    <p class="text-muted">No questions in this assessment.</p>
  <?php else: ?>
    <?php foreach ($questions as $i => $q): ?>
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2 small text-muted">Question <?php echo (int)$i + 1; ?> · <?php echo htmlspecialchars(strtoupper($q->question_type)); ?> · <?php echo htmlspecialchars(number_format((float)$q->points, 2)); ?> pts</div>
        <div class="card-body">
          <p class="mb-3"><?php echo nl2br(htmlspecialchars($q->question_text)); ?></p>
          <?php if ($q->question_type === 'mcq'): ?>
            <?php $opts = isset($options_by_qid[(int)$q->id]) ? $options_by_qid[(int)$q->id] : array(); ?>
            <ul class="list-group list-group-flush border rounded">
              <?php foreach ($opts as $o): ?>
                <li class="list-group-item d-flex align-items-center gap-2">
                  <input class="form-check-input flex-shrink-0" type="radio" disabled aria-hidden="true">
                  <span><?php echo htmlspecialchars($o->option_text); ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php elseif ($q->question_type === 'text'): ?>
            <div class="border rounded p-3 bg-light text-muted small">Text answer area (preview)</div>
          <?php else: ?>
            <span class="badge bg-secondary mb-2"><?php echo strtoupper(htmlspecialchars($q->coding_language)); ?></span>
            <div class="border rounded p-2 bg-dark text-light font-monospace small" style="min-height:4rem">Code editor (preview)</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php $this->load->view('partials/footer'); ?>
