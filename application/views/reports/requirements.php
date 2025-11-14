<?php $this->load->view('partials/header', ['title' => 'Requirements Report']); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Requirements Report</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reports'); ?>">Back to Reports</a>
    </div>
  </div>

  <div class="card shadow-soft">
    <div class="card-body">
      <?php if (empty($rows)): ?>
        <div class="text-muted">No requirements found.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th style="width:22%">Project</th>
                <th style="width:34%">Requirement</th>
                <th style="width:18%">Owner</th>
                <th class="text-center" title="Pending">Pending</th>
                <th class="text-center" title="In Progress">In&nbsp;Progress</th>
                <th class="text-center" title="Completed">Completed</th>
                <th class="text-center" title="Blocked">Blocked</th>
                <th class="text-center">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r->project_name ?: '—'); ?></td>
                  <td>
                    <div class="fw-semibold text-truncate" title="<?php echo htmlspecialchars($r->title); ?>"><?php echo htmlspecialchars($r->title); ?></div>
                    <div class="small text-muted">#<?php echo (int)$r->id; ?></div>
                  </td>
                  <td><?php echo htmlspecialchars($r->owner ?: '—'); ?></td>
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
