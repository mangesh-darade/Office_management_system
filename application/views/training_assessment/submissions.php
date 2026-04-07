<?php $this->load->view('partials/header', array('title' => 'Assessment submissions')); ?>
<div class="container-fluid py-4">
  <nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('training-assessment'); ?>">Training &amp; Assessment</a></li>
      <li class="breadcrumb-item active">Assessment submissions</li>
    </ol>
  </nav>

  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1 fw-bold">Assessment submissions</h1>
      <p class="text-muted small mb-0">
        <?php if (!empty($show_all_submissions)): ?>
          You are viewing all submitted assessment attempts.
        <?php else: ?>
          You are viewing your own submitted assessment attempts.
        <?php endif; ?>
      </p>
    </div>
  </div>

  <div class="table-responsive card shadow-sm border-0">
    <table class="table table-hover table-sm align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Assessment</th>
          <th>Submitted by</th>
          <th>Submitted at</th>
          <th>Score %</th>
          <th>Result</th>
          <th class="text-end text-nowrap">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="6" class="text-center py-4 text-muted">No submitted assessments yet.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="small"><?php echo htmlspecialchars((string) $r->assessment_title); ?></td>
              <td class="small">
                <?php
                  $name = !empty($r->user_name) ? (string) $r->user_name : (string) $r->candidate_name;
                  $email = !empty($r->user_email) ? (string) $r->user_email : (string) $r->candidate_email;
                  echo htmlspecialchars(trim($name) !== '' ? $name : '—');
                  if (trim($email) !== '') {
                      echo '<br><span class="text-muted">' . htmlspecialchars($email) . '</span>';
                  }
                ?>
              </td>
              <td class="small text-nowrap"><?php echo !empty($r->submitted_at) ? htmlspecialchars((string) $r->submitted_at) : '—'; ?></td>
              <td class="small"><?php echo ($r->score_percent !== null && $r->score_percent !== '') ? htmlspecialchars(number_format((float) $r->score_percent, 1)) : '—'; ?></td>
              <td>
                <?php if (isset($r->passed) && $r->passed !== null): ?>
                  <?php if ((int)$r->passed === 1): ?>
                    <span class="badge bg-success">Pass</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Fail</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge bg-secondary">—</span>
                <?php endif; ?>
              </td>
              <td class="text-end text-nowrap">
                <?php if (!empty($r->access_token) && !empty($r->id)): ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('training-assessment/result/' . (int) $r->id); ?>">
                    <i class="bi bi-eye me-1"></i>View
                  </a>
                  <?php if ((int) $this->session->userdata('role_id') === 1): ?>
                    <a class="btn btn-sm btn-outline-dark" href="<?php echo site_url('training-assessment/screenshots/' . (int) $r->id); ?>">
                      <i class="bi bi-camera me-1"></i>Screenshots
                    </a>
                  <?php endif; ?>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
