<?php $this->load->view('partials/header', ['title' => 'Leave Types']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">
    <i class="bi bi-calendar-x me-2"></i>Leave Types Management
  </h1>
  <div class="d-flex gap-2">
    <?php if ($show_deleted): ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('settings/leave-types'); ?>">
        <i class="bi bi-list me-1"></i>Show Active
      </a>
    <?php else: ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('settings/leave-types?show_deleted=1'); ?>">
        <i class="bi bi-archive me-1"></i>Show Deleted
      </a>
    <?php endif; ?>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('settings/leave-types/create'); ?>">
      <i class="bi bi-plus-lg me-1"></i>Create Leave Type
    </a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <?php echo esc_view($this->session->flashdata('error')); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>
    <?php echo esc_view($this->session->flashdata('success')); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if ($show_deleted): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle me-2"></i>
  Showing deleted leave types. You can restore them if needed.
</div>
<?php endif; ?>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Annual Quota</th>
            <th>Is Paid</th>
            <th>Status</th>
            <th>Deleted At</th>
            <th style="width:200px" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-4">
                <?php echo $show_deleted ? 'No deleted leave types found.' : 'No leave types found. <a href="' . site_url('settings/leave-types/create') . '">Create one</a>'; ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr class="<?php echo (isset($r->status) && $r->status === 'inactive') ? 'table-warning' : ''; ?>">
                <td><?php echo (int)$r->id; ?></td>
                <td>
                  <strong><?php echo esc_view($r->name); ?></strong>
                </td>
                <td>
                  <?php echo !empty($r->description) ? esc_view($r->description) : '<span class="text-muted">—</span>'; ?>
                </td>
                <td>
                  <span class="badge bg-info"><?php echo number_format((float)$r->annual_quota, 1); ?> days</span>
                </td>
                <td>
                  <?php if ((int)$r->is_paid === 1): ?>
                    <span class="badge bg-success">Paid</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Unpaid</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-<?php echo (isset($r->status) && $r->status === 'inactive') ? 'danger' : 'success'; ?>">
                    <?php echo esc_view(isset($r->status) ? ucfirst($r->status) : 'Active'); ?>
                  </span>
                </td>
                <td>
                  <?php echo isset($r->deleted_at) && !empty($r->deleted_at) ? '<span class="text-muted small">' . date('M j, Y H:i', strtotime($r->deleted_at)) . '</span>' : '<span class="text-muted">—</span>'; ?>
                </td>
                <td class="text-end">
                  <?php if (isset($r->status) && $r->status === 'inactive'): ?>
                    <form method="post" action="<?php echo site_url('settings/leave-types/' . (int)$r->id . '/restore'); ?>" class="d-inline" onsubmit="return confirm('Restore this leave type?');">
                      <button type="submit" class="btn btn-outline-success btn-sm" title="Restore">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                      </button>
                    </form>
                  <?php else: ?>
                    <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('settings/leave-types/' . (int)$r->id . '/edit'); ?>" title="Edit">
                      <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                    <form method="post" action="<?php echo site_url('settings/leave-types/' . (int)$r->id . '/delete'); ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this leave type? It will be marked as inactive and can be restored later.');">
                      <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                        <i class="bi bi-trash me-1"></i>Delete
                      </button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="mt-3">
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('settings'); ?>">
    <i class="bi bi-arrow-left me-1"></i>Back to Settings
  </a>
</div>

<?php $this->load->view('partials/footer'); ?>

