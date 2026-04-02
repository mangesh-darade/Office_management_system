<?php $this->load->view('partials/header', array('title' => 'Submissions — ' . $topic->name)); ?>
<div class="container-fluid py-4">
  <nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('training-lms-admin'); ?>">LMS Admin</a></li>
      <li class="breadcrumb-item"><a href="<?php echo site_url('training-lms-admin/topics/' . (int) $topic->module_id); ?>">Topics</a></li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars($topic->name); ?></li>
    </ol>
  </nav>
  <h1 class="h4 mb-1">Assignment submissions</h1>
  <p class="text-muted small mb-3"><?php echo htmlspecialchars($assignment->name); ?></p>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <div class="table-responsive card shadow-sm border-0">
    <table class="table table-sm align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>User</th>
          <th>File</th>
          <th>Submitted</th>
          <th>Status</th>
          <th>Score</th>
          <th>Review</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($submissions)): ?>
          <tr><td colspan="6" class="text-center py-4 text-muted">No submissions.</td></tr>
        <?php else: ?>
          <?php foreach ($submissions as $s): ?>
            <tr>
              <td class="small">
                <?php echo htmlspecialchars(trim($s->user_name . ' / ' . $s->user_email)); ?>
              </td>
              <td class="small"><?php echo htmlspecialchars($s->original_filename); ?></td>
              <td class="small"><?php echo htmlspecialchars($s->submitted_at); ?></td>
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
              <td><?php echo $s->score !== null ? htmlspecialchars($s->score) : '—'; ?></td>
              <td>
                <a class="btn btn-sm btn-outline-secondary mb-1" href="<?php echo site_url('training-lms-admin/download/' . (int) $s->id); ?>">Download</a>
                <?php echo form_open('training-lms-admin/submission/save', array('class' => 'border rounded p-2 bg-light')); ?>
                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                <input type="hidden" name="submission_id" value="<?php echo (int) $s->id; ?>">
                <div class="row g-1">
                  <div class="col-12">
                    <select name="status" class="form-select form-select-sm">
                      <option value="pending" <?php echo $s->status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                      <option value="submitted" <?php echo $s->status === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                      <option value="assessed" <?php echo $s->status === 'assessed' ? 'selected' : ''; ?>>Assessed</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <input type="text" name="score" class="form-control form-control-sm" placeholder="Score" value="<?php echo $s->score !== null ? htmlspecialchars($s->score) : ''; ?>">
                  </div>
                  <div class="col-12">
                    <textarea name="feedback" class="form-control form-control-sm" rows="2" placeholder="Feedback"><?php echo htmlspecialchars((string) $s->feedback); ?></textarea>
                  </div>
                  <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Save</button>
                  </div>
                </div>
                <?php echo form_close(); ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
