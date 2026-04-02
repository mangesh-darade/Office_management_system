<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('partials/header', array('title' => 'Team assessment progress'));
?>
<div class="container py-4">
  <h1 class="h4 mb-2">Team assessment progress</h1>
  <p class="text-muted small mb-4">
    Assignments for employees in your department (excluding you). Use this as a quick snapshot; detailed reporting is under Report.
  </p>
  <?php if ($department === ''): ?>
    <div class="alert alert-info">Your employee profile has no department set, so peers cannot be listed. Ask HR to set your department, or use the full report.</div>
  <?php elseif (empty($rows)): ?>
    <div class="alert alert-secondary">No assessment assignments found for colleagues in <strong><?php echo htmlspecialchars($department); ?></strong>.</div>
  <?php else: ?>
    <p class="small text-muted mb-2">Department: <strong><?php echo htmlspecialchars($department); ?></strong></p>
    <div class="table-responsive card shadow-sm border-0">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Colleague</th>
            <th>Assessment</th>
            <th>Assigned</th>
            <th>Status</th>
            <th class="text-end">Score</th>
            <th class="text-end">Link</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <?php
              $who = !empty($r->user_name) ? trim($r->user_name) : '—';
              $mail = !empty($r->user_email) ? $r->user_email : '';
              $done = !empty($r->completed_at);
            ?>
            <tr>
              <td>
                <div class="fw-semibold"><?php echo htmlspecialchars($who); ?></div>
                <?php if ($mail !== ''): ?><div class="small text-muted"><?php echo htmlspecialchars($mail); ?></div><?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($r->assessment_title); ?></td>
              <td class="small text-muted"><?php echo htmlspecialchars($r->assigned_at); ?></td>
              <td>
                <?php if ($done): ?>
                  <span class="badge bg-success">Completed</span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark">Pending</span>
                <?php endif; ?>
              </td>
              <td class="text-end small">
                <?php if ($done && $r->score_percent !== null && $r->score_percent !== ''): ?>
                  <?php echo htmlspecialchars(number_format((float) $r->score_percent, 1)); ?>%
                  <?php if (isset($r->passed)): ?>
                    <span class="badge bg-<?php echo (int) $r->passed === 1 ? 'success' : 'danger'; ?> ms-1"><?php echo (int) $r->passed === 1 ? 'Pass' : 'Fail'; ?></span>
                  <?php endif; ?>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td class="text-end text-nowrap">
                <?php if ($done && !empty($r->signed_result_url)): ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($r->signed_result_url); ?>">Result</a>
                <?php elseif (!$done && !empty($r->access_token)): ?>
                  <span class="small text-muted">Take link with assignee</span>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  <a href="<?php echo site_url('training-assessment/report'); ?>" class="btn btn-outline-secondary mt-4">Full report</a>
</div>
<?php $this->load->view('partials/footer'); ?>
