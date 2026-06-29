<?php $this->load->view('partials/header', array('title' => isset($question) && $question ? 'Edit question' : 'Add question')); ?>
<div class="oms-form-compact">
<div class="container py-4">
  <h1 class="h4 mb-3"><?php echo isset($question) && $question ? 'Edit question' : 'Add question'; ?></h1>
  <p class="text-muted small">Assessment: <strong><?php echo esc_view($assessment->title); ?></strong></p>

  <?php echo form_open('training-assessment/question/save'); ?>
  <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
  <input type="hidden" name="assessment_id" value="<?php echo (int)$assessment->id; ?>">
  <?php if (isset($question) && $question): ?>
    <input type="hidden" name="question_id" value="<?php echo (int)$question->id; ?>">
  <?php else: ?>
    <input type="hidden" name="sort_order" value="0">
  <?php endif; ?>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label">Question type</label>
        <select name="question_type" id="qtype" class="form-select" required>
          <?php
          $t = isset($question) && $question ? $question->question_type : 'mcq';
          ?>
          <option value="mcq" <?php echo $t === 'mcq' ? 'selected' : ''; ?>>Multiple choice (MCQ)</option>
          <option value="text" <?php echo $t === 'text' ? 'selected' : ''; ?>>Text answer</option>
          <option value="coding" <?php echo $t === 'coding' ? 'selected' : ''; ?>>Coding (PHP / JS)</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Question text</label>
        <textarea name="question_text" class="form-control" rows="4" required><?php echo isset($question) && $question ? esc_view($question->question_text) : ''; ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Points</label>
        <input type="number" name="points" class="form-control" step="0.01" min="0.01" required
          value="<?php echo isset($question) && $question ? esc_view($question->points) : '1'; ?>">
      </div>

      <div id="block-mcq" class="qblock">
        <h6 class="text-muted">MCQ options</h6>
        <p class="small text-muted">Tick one or more correct options.</p>
        <?php
        $opts = isset($options) ? $options : array();
        $correctMap = array();
        if (!empty($opts)) {
          foreach ($opts as $ix => $ox) {
            if ((int)$ox->is_correct === 1) {
              $correctMap[(int)$ix] = true;
            }
          }
        }
        for ($i = 0; $i < 6; $i++):
          $o = isset($opts[$i]) ? $opts[$i] : null;
        ?>
        <div class="input-group mb-2">
          <div class="input-group-text">
            <input class="form-check-input mt-0 correct-radio" type="checkbox" name="correct_indexes[]" value="<?php echo $i; ?>" <?php echo isset($correctMap[$i]) ? 'checked' : ''; ?>>
          </div>
          <input type="text" name="option_text[]" class="form-control" placeholder="Option <?php echo $i + 1; ?>"
            value="<?php echo $o ? esc_view($o->option_text) : ''; ?>">
        </div>
        <?php endfor; ?>
      </div>

      <div id="block-text" class="qblock" style="display:none">
        <label class="form-label">Model answer (optional — improves auto-scoring)</label>
        <textarea name="model_answer" class="form-control" rows="3" placeholder="Keywords or sample answer"><?php echo (isset($question) && $question && $question->question_type === 'text') ? esc_view($question->model_answer) : ''; ?></textarea>
        <div class="row g-2 mt-1">
          <div class="col-md-6">
            <label class="form-label">Keyword pass threshold (%)</label>
            <input type="number" name="text_keyword_pass_percent" class="form-control" min="1" max="100" step="0.01"
              value="<?php echo (isset($question) && $question && isset($question->text_keyword_pass_percent)) ? esc_view($question->text_keyword_pass_percent) : '50'; ?>">
          </div>
        </div>
        <p class="small text-warning mb-0 mt-2"><i class="bi bi-info-circle me-1"></i>Use comma/new-line separated keywords for keyword scoring. If only one line is provided, similarity scoring is used.</p>
      </div>

      <div id="block-coding" class="qblock" style="display:none">
        <div class="mb-2">
          <label class="form-label">Language</label>
          <select name="coding_language" class="form-select">
            <?php $cl = (isset($question) && $question && $question->question_type === 'coding') ? $question->coding_language : 'php'; ?>
            <option value="php" <?php echo $cl === 'php' ? 'selected' : ''; ?>>PHP</option>
            <option value="js" <?php echo $cl === 'js' ? 'selected' : ''; ?>>JavaScript</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Expected output (trimmed compare for auto-score)</label>
          <input type="text" name="coding_expected_output" class="form-control"
            value="<?php echo (isset($question) && $question && $question->question_type === 'coding') ? esc_view($question->coding_expected_output) : ''; ?>"
            placeholder="e.g. olleh">
        </div>
      </div>
    </div>
    <div class="card-footer bg-transparent">
      <button type="submit" class="btn btn-primary">Save question</button>
      <a href="<?php echo site_url('training-assessment/questions/' . (int)$assessment->id); ?>" class="btn btn-outline-secondary">Back</a>
    </div>
  </div>
  <?php echo form_close(); ?>
</div>
<script>
(function() {
  function sync() {
    var v = document.getElementById('qtype').value;
    document.getElementById('block-mcq').style.display = (v === 'mcq') ? 'block' : 'none';
    document.getElementById('block-text').style.display = (v === 'text') ? 'block' : 'none';
    document.getElementById('block-coding').style.display = (v === 'coding') ? 'block' : 'none';
  }
  document.getElementById('qtype').addEventListener('change', sync);
  sync();
})();
</script>
</div>
<?php $this->load->view('partials/footer'); ?>
