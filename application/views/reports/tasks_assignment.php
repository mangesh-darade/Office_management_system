<?php $this->load->view('partials/header', ['title' => 'Task Assignment Report']); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Task Assignment Report</h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reports'); ?>">Back to Reports</a>
  </div>
  <div class="card shadow-soft">
    <div class="card-body">
      <?php if (empty($rows)): ?>
        <div class="text-muted">No task data found.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th style="width:22%">Employee</th>
                <th style="width:48%">Title</th>
                <th class="text-center">Pending</th>
                <th class="text-center">In Progress</th>
                <th class="text-center">Completed</th>
                <th class="text-center">Blocked</th>
                <th class="text-center">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r->name); ?></td>
                  <td class="text-truncate" style="max-width:520px;" title="<?php echo htmlspecialchars(isset($r->titles)?$r->titles:''); ?>"><?php echo htmlspecialchars(isset($r->titles)?$r->titles:''); ?></td>
                  <td class="text-center"><span class="badge bg-secondary"><?php echo (int)$r->counts['pending']; ?></span></td>
                  <td class="text-center"><span class="badge bg-info text-dark"><?php echo (int)$r->counts['in_progress']; ?></span></td>
                  <td class="text-center"><span class="badge bg-success"><?php echo (int)$r->counts['completed']; ?></span></td>
                  <td class="text-center"><span class="badge bg-danger"><?php echo (int)$r->counts['blocked']; ?></span></td>
                  <td class="text-center fw-semibold"><?php echo (int)$r->total; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php $this->load->view('partials/footer'); ?>
