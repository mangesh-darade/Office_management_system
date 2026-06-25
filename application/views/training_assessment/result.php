<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$isCand = empty($au->user_id);
$name = $isCand ? $au->candidate_name : (!empty($au->assignee_user_name) ? $au->assignee_user_name : 'Employee');
$this->load->view('partials/header', array(
  'title' => 'Assessment result',
  'with_sidebar' => (bool)(int)$this->session->userdata('user_id'),
  'extra_css' => array('assets/css/lms-ui.css'),
));
$showRetake = !empty($show_retake);
$showCorrect = !empty($show_correct);
$scorePct = $result ? (float)$result->score_percent : 0;
?>
<style>
  .ta-result-head { background:linear-gradient(135deg, rgba(59,130,246,.14), rgba(255,255,255,.95)); }
  .ta-kpi .label { color:#64748b; font-size:.78rem; text-transform:uppercase; letter-spacing:.06em; }
  .ta-kpi .value { font-weight:800; font-size:1.35rem; color:#0f172a; line-height:1.2; }
  .ta-kpi .card-body { padding:.85rem .75rem; }
  .ta-q-card { border:1px solid #e2e8f0; border-radius:10px; background:#fff; }
  .ta-ok { color:#22c55e; }
  .ta-bad { color:#ef4444; }
  /* Mobile: compact single-line KPI rows */
  @media (max-width: 575.98px) {
    .ta-result-wrap.lms-soft-wrap { padding:10px !important; }
    .ta-kpi-row { --bs-gutter-y: 0.35rem; }
    .ta-kpi .card-body {
      padding: .45rem .6rem !important;
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
      text-align: left !important;
      gap: .5rem;
      min-height: 0;
    }
    .ta-kpi .label {
      font-size: .62rem;
      letter-spacing: .04em;
      margin: 0;
      flex-shrink: 0;
      max-width: 42%;
    }
    .ta-kpi .value,
    .ta-kpi .fs-4,
    .ta-kpi .ta-kpi-learner-name {
      font-size: .95rem;
      font-weight: 700;
      text-align: right;
      flex: 1;
      min-width: 0;
    }
    .ta-kpi .fs-4 { font-size: 1rem !important; margin: 0; font-weight: 400; }
    .ta-kpi .badge { font-size: .7rem; padding: .2em .45em; }
    .ta-kpi .small { font-size: .72rem !important; line-height: 1.2; }
    .ta-kpi-learner-name {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .ta-progress-mobile .small { font-size: .75rem !important; }
    .ta-progress-mobile .progress { height: 6px !important; }
  }
  @media (max-width: 991.98px) {
    .ta-result-table { display:none; }
    .ta-q-mobile { display:block !important; }
  }
  @media (min-width: 992px) {
    .ta-q-mobile { display:none !important; }
  }
</style>
<div class="container py-4">
  <div class="ta-result-wrap lms-soft-wrap">
  <?php if ($this->session->flashdata('ta_submit_notice')): ?>
    <div class="alert alert-info"><?php echo esc_view($this->session->flashdata('ta_submit_notice')); ?></div>
  <?php endif; ?>
  <h2 class="h5 mb-3">Result: <?php echo esc_view($au->assessment_title); ?></h2>
  <?php if (!$result): ?>
    <div class="alert alert-warning">No result record found.</div>
  <?php else: ?>
    <div class="row g-3 mb-4 ta-kpi-row">
      <div class="col-6 col-md-3">
        <div class="card ta-soft-card h-100 ta-kpi">
          <div class="card-body text-center">
            <div class="label">Score</div>
            <div class="value"><?php echo esc_view(number_format((float)$result->score_percent, 1)); ?>%</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card ta-soft-card h-100 ta-kpi">
          <div class="card-body text-center">
            <div class="label">Total marks</div>
            <div class="value"><?php echo esc_view($result->earned_points); ?> / <?php echo esc_view($result->total_points); ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card ta-soft-card h-100 ta-kpi">
          <div class="card-body text-center">
            <div class="label">Pass / Fail</div>
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
      <div class="col-6 col-md-3">
        <div class="card ta-soft-card h-100 ta-kpi">
          <div class="card-body text-center">
            <div class="label">Learner</div>
            <div class="small fw-semibold ta-kpi-learner-name"><?php echo esc_view($name); ?></div>
            <?php if ($isCand && !empty($au->candidate_email)): ?>
              <div class="small text-muted d-none d-sm-block"><?php echo esc_view($au->candidate_email); ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="mb-3 ta-progress-mobile">
      <div class="progress" style="height:8px; border-radius:999px;">
        <div class="progress-bar bg-primary" role="progressbar" style="width:<?php echo max(0, min(100, $scorePct)); ?>%"></div>
      </div>
      <div class="small text-muted mt-1">Overall score progress: <?php echo esc_view(number_format($scorePct, 1)); ?>%</div>
    </div>

    <?php if ($showRetake): ?>
      <?php echo form_open('training-assessment/retake-assessment', array('class' => 'mb-4', 'onsubmit' => 'return confirm("Start a new attempt? Your previous answers will be cleared.");')); ?>
      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
      <input type="hidden" name="access_token" value="<?php echo esc_view($au->access_token); ?>">
      <button type="submit" class="btn btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Retake assessment</button>
      <?php echo form_close(); ?>
    <?php endif; ?>

    <?php if ($result && !empty($au->id)): ?>
      <p class="mb-3">
        <?php if ((int) $this->session->userdata('role_id') === 1): ?>
          <a class="btn btn-outline-dark btn-sm" href="<?php echo site_url('training-assessment/screenshots/' . (int) $au->id); ?>"><i class="bi bi-camera me-1"></i>View screenshots</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener" href="<?php echo site_url('training-assessment/certificate/' . (int) $au->id); ?>"><i class="bi bi-award me-1"></i>Print certificate</a>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print();"><i class="bi bi-file-earmark-pdf me-1"></i>Download Result (PDF)</button>
      </p>
    <?php endif; ?>

    <h2 class="h6 text-muted mb-2">Question breakdown</h2>
    <?php if ($showCorrect): ?>
      <p class="small text-muted">Correct answers and feedback are shown because this assessment has &quot;training mode&quot; enabled.</p>
    <?php endif; ?>
    <?php if (empty($details)): ?>
    <p class="text-muted small">No saved answers for this attempt.</p>
    <?php else: ?>
    <div class="table-responsive ta-soft-card ta-result-table">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Question</th>
            <th>Type</th>
            <th>Selected option(s)</th>
            <?php if ($showCorrect): ?>
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
              echo nl2br(esc_view($sn));
            ?>
            <?php if (!empty($d->ta_option_rows) && is_array($d->ta_option_rows)): ?>
              <div class="mt-2 small">
                <?php $optIndex = 0; foreach ($d->ta_option_rows as $opt): ?>
                  <?php $optLabel = chr(65 + $optIndex); ?>
                  <span class="me-3 d-inline-block">
                    <strong><?php echo $optLabel; ?>)</strong>
                    <?php echo esc_view(isset($opt['text']) ? $opt['text'] : ''); ?>
                    <?php if ($showCorrect && !empty($opt['is_selected'])): ?><span class="badge bg-primary-subtle text-primary-emphasis">Selected</span><?php endif; ?>
                    <?php if ($showCorrect && !empty($opt['is_correct'])): ?><span class="badge bg-success-subtle text-success-emphasis">Correct</span><?php endif; ?>
                  </span>
                <?php $optIndex++; endforeach; ?>
              </div>
            <?php endif; ?>
            </td>
            <td><span class="badge bg-secondary"><?php echo esc_view(strtoupper($d->question_type)); ?></span></td>
            <td class="small">
              <?php if ((string)$d->question_type === 'mcq'): ?>
                <?php echo esc_view(isset($d->ta_selected_summary) ? $d->ta_selected_summary : '—'); ?>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
            <?php if ($showCorrect): ?>
            <td class="small"><?php echo isset($d->ta_correct_summary) ? nl2br(esc_view($d->ta_correct_summary)) : '—'; ?></td>
            <?php endif; ?>
            <td><?php echo esc_view($d->points_earned); ?> / <?php echo esc_view($d->question_points); ?></td>
            <td>
              <?php if ((int)$d->is_graded_correct === 1): ?>
                <span class="ta-ok" aria-label="Correct"><i class="bi bi-check-circle-fill"></i> Correct</span>
              <?php elseif ((int)$d->is_graded_correct === 0): ?>
                <span class="ta-bad" aria-label="Incorrect"><i class="bi bi-x-circle-fill"></i> Wrong</span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="ta-q-mobile mt-3">
      <?php foreach ($details as $d): ?>
      <div class="ta-q-card p-3 mb-2">
        <div class="fw-semibold mb-1"><?php echo nl2br(esc_view(strip_tags($d->question_text))); ?></div>
        <?php if (!empty($d->ta_option_rows) && is_array($d->ta_option_rows)): ?>
        <div class="small mb-1">
          <?php $optIndex = 0; foreach ($d->ta_option_rows as $opt): ?>
            <?php $optLabel = chr(65 + $optIndex); ?>
            <span class="me-2 d-inline-block">
              <strong><?php echo $optLabel; ?>)</strong> <?php echo esc_view(isset($opt['text']) ? $opt['text'] : ''); ?>
              <?php if ($showCorrect && !empty($opt['is_selected'])): ?><span class="badge bg-primary-subtle text-primary-emphasis">Selected</span><?php endif; ?>
              <?php if ($showCorrect && !empty($opt['is_correct'])): ?><span class="badge bg-success-subtle text-success-emphasis">Correct</span><?php endif; ?>
            </span>
          <?php $optIndex++; endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="small text-muted mb-1">Type: <?php echo esc_view(strtoupper($d->question_type)); ?></div>
        <div class="small"><strong>Selected option(s):</strong>
          <?php if ((string)$d->question_type === 'mcq'): ?>
            <?php echo esc_view(isset($d->ta_selected_summary) ? $d->ta_selected_summary : '—'); ?>
          <?php else: ?>
            —
          <?php endif; ?>
        </div>
        <?php if ($showCorrect): ?>
        <div class="small"><strong>Correct:</strong> <?php echo isset($d->ta_correct_summary) ? nl2br(esc_view($d->ta_correct_summary)) : '—'; ?></div>
        <?php endif; ?>
        <div class="small"><strong>Points:</strong> <?php echo esc_view($d->points_earned); ?> / <?php echo esc_view($d->question_points); ?></div>
        <div class="small mt-1">
          <?php if ((int)$d->is_graded_correct === 1): ?>
            <span class="ta-ok"><i class="bi bi-check-circle-fill"></i> Correct</span>
          <?php elseif ((int)$d->is_graded_correct === 0): ?>
            <span class="ta-bad"><i class="bi bi-x-circle-fill"></i> Wrong</span>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="mt-4">
    <?php if ((int)$this->session->userdata('user_id')): ?>
      <a href="<?php echo site_url('training-assessment'); ?>" class="btn btn-outline-secondary">Dashboard</a>
    <?php endif; ?>
  </div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
