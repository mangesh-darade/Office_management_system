<?php $this->load->view('partials/header', array('title' => 'All assignment submissions — LMS')); ?>
<div class="container-fluid py-4">
  <nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('training-lms-admin'); ?>">LMS Admin</a></li>
      <li class="breadcrumb-item active">Assignment submissions</li>
    </ol>
  </nav>

  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1 fw-bold">Assignment submissions</h1>
      <p class="text-muted small mb-0">Private list for managers (requires LMS admin access). Use <em>Review</em> to score and change status.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('training-lms-admin/office-feed/export/submissions'); ?>"><i class="bi bi-download me-1"></i>Export CSV</a>
      <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('training-lms-admin/office-feed'); ?>">Office feeds</a>
    </div>
  </div>

  <div class="table-responsive card shadow-sm border-0">
    <table class="table table-hover table-sm align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Topic</th>
          <th>Assignment name</th>
          <th>Submitted by</th>
          <th>Submitted at</th>
          <th>Attachment</th>
          <th>Assessment score</th>
          <th>Assessed by</th>
          <th>Status</th>
          <th class="text-end text-nowrap">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="9" class="text-center py-4 text-muted">No submissions yet.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="small">
                <span class="d-block fw-medium"><?php echo htmlspecialchars($r->topic_name); ?></span>
                <?php if (!empty($r->module_name)): ?>
                  <span class="text-muted"><?php echo htmlspecialchars($r->module_name); ?></span>
                <?php endif; ?>
              </td>
              <td class="small"><?php echo htmlspecialchars($r->assignment_name); ?></td>
              <td class="small">
                <?php echo htmlspecialchars(trim((string) $r->submitted_by_name)); ?>
                <?php if (!empty($r->submitted_by_email)): ?>
                  <br><span class="text-muted"><?php echo htmlspecialchars($r->submitted_by_email); ?></span>
                <?php endif; ?>
              </td>
              <td class="small text-nowrap"><?php echo $r->submitted_at ? htmlspecialchars($r->submitted_at) : '—'; ?></td>
              <td class="small">
                <?php if (!empty($r->attachment_filename) && isset($r->submission_id)): ?>
                  <a href="<?php echo site_url('training-lms-admin/download/' . (int) $r->submission_id); ?>"><?php echo htmlspecialchars($r->attachment_filename); ?></a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td class="small"><?php echo ($r->assignment_score !== null && $r->assignment_score !== '') ? htmlspecialchars($r->assignment_score) : '—'; ?></td>
              <td class="small"><?php echo !empty($r->assessed_by_name) ? htmlspecialchars($r->assessed_by_name) : '—'; ?></td>
              <td>
                <?php
                $st = isset($r->status) ? $r->status : '';
                if ($st === 'pending') {
                    echo '<span class="badge bg-warning text-dark">Pending</span>';
                } elseif ($st === 'submitted') {
                    echo '<span class="badge bg-primary">Submitted</span>';
                } elseif ($st === 'assessed') {
                    echo '<span class="badge bg-success">Assessed</span>';
                } else {
                    echo '<span class="badge bg-secondary">' . htmlspecialchars((string) $st) . '</span>';
                }
                ?>
              </td>
              <td class="text-end text-nowrap small">
                <?php if (!empty($r->topic_id)): ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('training-lms-admin/submissions/' . (int) $r->topic_id); ?>">Review</a>
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
