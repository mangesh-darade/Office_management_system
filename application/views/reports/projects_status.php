<?php $this->load->view('partials/header', ['title' => 'Projects by Status']); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Projects by Status</h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reports'); ?>">Back to Reports</a>
  </div>
  <div class="card shadow-soft">
    <div class="card-body">
      <?php if (empty($rows)): ?>
        <div class="text-muted">No project data available.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th style="width:50%">Project</th>
                <th style="width:30%">Status</th>
                <th class="text-center" style="width:20%">Count</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars(isset($r->project_name)?$r->project_name:''); ?></td>
                  <td><?php echo htmlspecialchars(isset($r->status)?$r->status:''); ?></td>
                  <td class="text-center"><?php echo (int)$r->cnt; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php $this->load->view('partials/footer'); ?>
