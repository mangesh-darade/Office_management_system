<?php
$ed = isset($assessment) && $assessment;
$this->load->view('partials/header', array('title' => $ed ? 'Edit assessment' : 'New assessment'));
?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <h1 class="h4 mb-3"><?php echo $ed ? 'Edit assessment' : 'Create assessment'; ?></h1>
      <?php echo form_open('training-assessment/save'); ?>
      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
      <?php if ($ed): ?><input type="hidden" name="id" value="<?php echo (int)$assessment->id; ?>"><?php endif; ?>

      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required maxlength="255"
              value="<?php echo $ed ? esc_view($assessment->title) : ''; ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?php echo $ed ? esc_view($assessment->description) : ''; ?></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Time limit (minutes)</label>
              <input type="number" name="time_limit_minutes" class="form-control" min="1" max="600" required
                value="<?php echo $ed ? (int)$assessment->time_limit_minutes : 30; ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Passing marks (%)</label>
              <input type="number" name="passing_marks" class="form-control" step="0.01" min="0" max="100" required
                value="<?php echo $ed ? esc_view($assessment->passing_marks) : '60'; ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Max attempts</label>
              <input type="number" name="max_attempts" class="form-control" min="0" title="0 = unlimited"
                value="<?php echo $ed ? (int)$assessment->max_attempts : 1; ?>">
            </div>
          </div>
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="randomize_questions" value="1" id="rq"
                  <?php echo ($ed && (int)$assessment->randomize_questions) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="rq">Randomize question order per attempt</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="shuffle_options" value="1" id="so"
                  <?php echo ($ed && (int)$assessment->shuffle_options) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="so">Shuffle MCQ options</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="allow_retake" value="1" id="ar"
                  <?php echo ($ed && (int)$assessment->allow_retake) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="ar">Allow retake (within max attempts)</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="show_correct_after_submit" value="1" id="sc"
                  <?php echo ($ed && isset($assessment->show_correct_after_submit) && (int)$assessment->show_correct_after_submit) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sc">Show correct answers &amp; feedback on result (training mode)</label>
              </div>
              <div class="mb-2 mt-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                  <option value="active" <?php echo (!$ed || $assessment->status === 'active') ? 'selected' : ''; ?>>Active</option>
                  <option value="inactive" <?php echo ($ed && $assessment->status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
              </div>
            </div>
          </div>
          <p class="small text-muted mt-3 mb-0">Total questions is determined by how many questions you add on the next screen.</p>
        </div>
        <div class="card-footer bg-transparent">
          <button type="submit" class="btn btn-primary">Save</button>
          <a href="<?php echo site_url('training-assessment'); ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
