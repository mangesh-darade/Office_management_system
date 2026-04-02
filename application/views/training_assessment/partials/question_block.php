<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$sel = $ans && $ans->selected_option_id ? (int)$ans->selected_option_id : 0;
$atext = $ans && $ans->answer_text ? $ans->answer_text : '';
$code = $ans && $ans->code_submitted ? $ans->code_submitted : '';
$exout = $ans && $ans->execution_output ? $ans->execution_output : '';
?>
<div class="ta-question-body" data-qid="<?php echo (int)$q->id; ?>" data-qtype="<?php echo htmlspecialchars($q->question_type); ?>"
  <?php if ($q->question_type === 'coding'): ?>data-code-lang="<?php echo htmlspecialchars($q->coding_language); ?>"<?php endif; ?>>
  <p class="lead"><?php echo nl2br(htmlspecialchars($q->question_text)); ?></p>

  <?php if ($q->question_type === 'mcq'): ?>
    <div class="list-group">
      <?php foreach ($opts as $o): ?>
        <label class="list-group-item d-flex gap-2 align-items-center">
          <input class="form-check-input flex-shrink-0 ta-mcq-opt" type="radio" name="mcq_<?php echo (int)$q->id; ?>"
            value="<?php echo (int)$o->id; ?>" <?php echo $sel === (int)$o->id ? 'checked' : ''; ?>>
          <span><?php echo htmlspecialchars($o->option_text); ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  <?php elseif ($q->question_type === 'text'): ?>
    <textarea class="form-control ta-text-answer" rows="6" placeholder="Your answer"><?php echo htmlspecialchars($atext); ?></textarea>
  <?php else: ?>
    <div class="mb-2">
      <span class="badge bg-secondary"><?php echo strtoupper(htmlspecialchars($q->coding_language)); ?></span>
    </div>
    <textarea class="form-control font-monospace ta-code-input mb-2" rows="10" spellcheck="false" placeholder="Your code"><?php echo htmlspecialchars($code); ?></textarea>
    <div class="d-flex flex-wrap gap-2 mb-2">
      <button type="button" class="btn btn-outline-primary btn-sm ta-run-code"><i class="bi bi-play-fill me-1"></i>Run / preview</button>
    </div>
    <label class="form-label small text-muted">Output (saved with your answer)</label>
    <textarea class="form-control font-monospace ta-code-output" rows="4" readonly placeholder="Output appears here"><?php echo htmlspecialchars($exout); ?></textarea>
    <p class="small text-muted mt-2 mb-0">PHP runs on the server in a restricted preview. JavaScript runs in your browser only.</p>
  <?php endif; ?>
</div>
