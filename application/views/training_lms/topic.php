<?php $this->load->view('partials/header', array('title' => 'Topic — ' . $topic->name)); ?>
<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('training'); ?>">Module</a></li>
      <li class="breadcrumb-item"><a href="<?php echo site_url('training/module/' . (int) $topic->module_id); ?>"><?php echo esc_view($topic->module_title); ?></a></li>
      <li class="breadcrumb-item active" aria-current="page"><?php echo esc_view($topic->name); ?></li>
    </ol>
  </nav>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:0.72rem;">Topic</div>
  <h1 class="h4 mb-2"><?php echo esc_view($topic->name); ?></h1>
  <p class="text-muted small mb-3">
    <span class="me-3"><i class="bi bi-clock me-1"></i><?php echo esc_view(number_format((float) $topic->duration_hours, 1)); ?> hours</span>
  </p>

  <?php if ((int) $topic->has_assessment === 1): ?>
  <div class="mb-4">
    <h2 class="h6 text-uppercase text-muted mb-2">Assessment</h2>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <?php if ($assessment_row): ?>
        <span class="small text-muted me-2"><strong><?php echo esc_view($assessment_row->title); ?></strong></span>
        <?php if (!empty($my_assessment_assignment) && !empty($my_assessment_assignment->completed_at)): ?>
          <?php $mau = $my_assessment_assignment; ?>
          <a class="btn btn-success btn-sm" href="<?php echo esc_view(!empty($mau->result_url) ? $mau->result_url : site_url('training-assessment/result-token/' . rawurlencode($mau->access_token))); ?>"><i class="bi bi-check-circle me-1"></i>Assessment result</a>
        <?php else: ?>
          <a class="btn btn-primary btn-sm" href="<?php echo site_url('training/start-assessment/' . (int) $topic->id); ?>"><i class="bi bi-play-fill me-1"></i><?php echo !empty($my_assessment_assignment) ? 'Resume assessment' : 'Start assessment'; ?></a>
        <?php endif; ?>
      <?php else: ?>
        <span class="small text-muted">Assessment is enabled but the linked test was not found.</span>
      <?php endif; ?>
      <a class="btn btn-outline-secondary btn-sm ms-auto" href="<?php echo site_url('training-assessment'); ?>"><i class="bi bi-mortarboard me-1"></i>All my assessments</a>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($assignment && (int) $topic->has_assignment === 1): ?>
  <div class="mb-4">
    <h2 class="h6 text-uppercase text-muted mb-2">Assignment</h2>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-warning btn-sm" href="#lms-assignment-submit"><i class="bi bi-upload me-1"></i>Scroll to assignment upload</a>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($completions_enabled)): ?>
    <?php if (empty($topic_completed)): ?>
      <?php echo form_open('training/complete-topic', array('class' => 'mb-3')); ?>
      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
      <input type="hidden" name="topic_id" value="<?php echo (int) $topic->id; ?>">
      <button type="submit" class="btn btn-outline-success btn-sm"><i class="bi bi-check2-square me-1"></i>Mark topic as complete</button>
      <span class="small text-muted ms-2">Required before some later topics unlock.</span>
      <?php echo form_close(); ?>
    <?php else: ?>
      <div class="alert alert-success py-2 small mb-3"><i class="bi bi-check-circle me-1"></i>You marked this topic complete.</div>
    <?php endif; ?>
  <?php endif; ?>

  <?php if (!empty($topic->description)): ?>
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold">Description</div>
      <div class="card-body"><?php echo nl2br(esc_view($topic->description)); ?></div>
    </div>
  <?php endif; ?>
  <?php if (!empty($topic->prerequisites)): ?>
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold">Prerequisites</div>
      <div class="card-body"><?php echo nl2br(esc_view($topic->prerequisites)); ?></div>
    </div>
  <?php endif; ?>

  <?php if ($assignment && (int) $topic->has_assignment === 1): ?>
    <div id="lms-assignment-submit" class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white">
        <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:0.7rem;">Assignment</div>
        <div class="fw-semibold"><?php echo esc_view($assignment->name); ?></div>
      </div>
      <div class="card-body">
        <?php if (!empty($assignment->details)): ?>
          <div class="mb-3"><?php echo nl2br(esc_view($assignment->details)); ?></div>
        <?php endif; ?>
        <?php if (!empty($submission_quota)): ?>
          <?php
          $sq = $submission_quota;
          $maxQ = (int) $sq['max'];
          $usedQ = (int) $sq['used'];
          ?>
          <p class="small text-muted mb-3">
            <?php if ($maxQ < 1): ?>
              You may submit multiple files (no limit set).
            <?php else: ?>
              Submissions used: <strong><?php echo (int) $usedQ; ?></strong> of <strong><?php echo (int) $maxQ; ?></strong>.
              <?php if ($usedQ >= $maxQ): ?>
                <span class="text-danger">You have reached the maximum for this assignment.</span>
              <?php endif; ?>
            <?php endif; ?>
          </p>
        <?php endif; ?>
        <?php if (!empty($submission_quota) && (int) $submission_quota['max'] > 0 && (int) $submission_quota['used'] >= (int) $submission_quota['max']): ?>
          <p class="text-muted small mb-0">To submit again, ask an administrator to raise &ldquo;Max submissions per user&rdquo; on this topic&rsquo;s assignment.</p>
        <?php else: ?>
        <?php echo form_open_multipart('training/submit-assignment'); ?>
        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
        <input type="hidden" name="topic_id" value="<?php echo (int) $topic->id; ?>">
        <div class="mb-3">
          <label class="form-label">Upload file (PDF, Office, OpenDocument, images, zip, audio/video — max 50 MB)</label>
          <input type="file" name="userfile" class="form-control" required accept=".pdf,.doc,.docx,.dot,.dotx,.rtf,.odt,.txt,.csv,.xls,.xlsx,.xlsm,.ods,.ppt,.pptx,.pptm,.odp,.jpg,.jpeg,.png,.gif,.webp,.bmp,.tif,.tiff,.svg,.heic,.zip,.rar,.7z,.mp4,.mov,.webm,.mkv,.mp3,.wav,.m4a">
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload me-1"></i>Submit assignment file</button>
        <?php echo form_close(); ?>
        <?php endif; ?>
      </div>
    </div>

    <h2 class="h6 text-uppercase text-muted mb-2">Assignment — submission history</h2>
    <?php if (empty($my_submissions)): ?>
      <p class="text-muted small">No submissions yet.</p>
    <?php else: ?>
      <div class="table-responsive card shadow-sm border-0">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>File</th>
              <th>Submitted</th>
              <th>Status</th>
              <th>Score</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($my_submissions as $s): ?>
              <tr>
                <td>
                  <?php echo esc_view($s->original_filename); ?>
                  <?php if (!empty($s->feedback)): ?>
                    <div class="small text-muted mt-1"><strong>Feedback:</strong> <?php echo nl2br(esc_view($s->feedback)); ?></div>
                  <?php endif; ?>
                </td>
                <td class="small"><?php echo esc_view($s->submitted_at); ?></td>
                <td>
                  <?php
                  $st = $s->status;
                  if ($st === 'pending') {
                      echo '<span class="badge bg-warning text-dark">Pending</span>';
                  } elseif ($st === 'submitted') {
                      echo '<span class="badge bg-primary">Submitted</span>';
                  } else {
                      echo '<span class="badge bg-success">Assessed</span>';
                  }
                  ?>
                </td>
                <td><?php echo $s->score !== null ? esc_view($s->score) : '—'; ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('training/download/' . (int) $s->id); ?>">Download</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php $this->load->view('partials/footer'); ?>
