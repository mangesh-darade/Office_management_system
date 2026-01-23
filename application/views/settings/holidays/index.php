<?php $this->load->view('partials/header', ['title' => 'Holidays']); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">
    <i class="bi bi-calendar-event me-2"></i>Company Holidays
  </h1>
  <div class="d-flex gap-2">
    <a href="<?php echo site_url('settings'); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left"></i> Back to Settings
    </a>
    <a href="<?php echo site_url('settings/holidays/create'); ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Add Holiday
    </a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-header bg-light">
    <h5 class="card-title mb-0">
      <i class="bi bi-list-ul me-2"></i>Holiday List
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th style="width: 15%;">Date</th>
            <th style="width: 45%;">Name</th>
            <th style="width: 15%;">Status</th>
            <th style="width: 25%;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (isset($rows) && is_array($rows) && count($rows)): ?>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><?php echo htmlspecialchars($row->holiday_date); ?></td>
                <td><?php echo htmlspecialchars($row->name); ?></td>
                <td>
                  <?php if ($row->status === 'active'): ?>
                    <span class="badge bg-success">Active</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?php echo site_url('settings/holidays/'.$row->id.'/edit'); ?>" class="btn btn-sm btn-outline-primary me-2">
                    <i class="bi bi-pencil-square"></i> Edit
                  </a>
                  <?php if ($row->status === 'active'): ?>
                    <form method="post" action="<?php echo site_url('settings/holidays/'.$row->id.'/delete'); ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this holiday?');">
                      <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-x-circle"></i> Deactivate
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted small">Inactive</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                <i class="bi bi-info-circle me-1"></i>No holidays defined yet.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>

