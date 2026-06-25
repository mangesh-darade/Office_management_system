<?php $this->load->view('partials/header', array('title' => 'Assignment — My submissions')); ?>
<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('training'); ?>">Module</a></li>
      <li class="breadcrumb-item active" aria-current="page">Assignment</li>
    </ol>
  </nav>
  <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:0.72rem;">Assignment</div>
  <h1 class="h4 mb-3">My submissions</h1>
  <div class="table-responsive card shadow-sm border-0">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Module</th>
          <th>Topic</th>
          <th>Assignment</th>
          <th>File</th>
          <th>Submitted</th>
          <th>Status</th>
          <th>Score</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="text-center py-4 text-muted">No submissions yet.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?php echo esc_view($r->module_title); ?></td>
              <td><?php echo esc_view($r->topic_name); ?></td>
              <td><?php echo esc_view($r->assignment_name); ?></td>
              <td class="small"><?php echo esc_view($r->original_filename); ?></td>
              <td class="small"><?php echo esc_view($r->submitted_at); ?></td>
              <td>
                <?php
                $st = $r->status;
                if ($st === 'pending') {
                    echo '<span class="badge bg-warning text-dark">Pending</span>';
                } elseif ($st === 'submitted') {
                    echo '<span class="badge bg-primary">Submitted</span>';
                } else {
                    echo '<span class="badge bg-success">Assessed</span>';
                }
                ?>
              </td>
              <td><?php echo $r->score !== null ? esc_view($r->score) : '—'; ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('training/download/' . (int) $r->id); ?>">Download</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
