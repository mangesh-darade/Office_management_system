<?php $this->load->view('partials/header', ['title' => 'Status Details']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Status Details</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('statuses'); ?>">Back</a>
    <?php if(function_exists('has_module_access') && (has_module_access('statuses') || (function_exists('is_admin_group') && is_admin_group()))): ?>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('statuses/edit/'.$status->id); ?>">Edit</a>
    <form method="post" action="<?php echo site_url('statuses/delete/'.$status->id); ?>" class="d-inline" onsubmit="return confirm('Delete this status?');">
      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
      <button type="submit" class="btn btn-danger btn-sm">Delete</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="text-muted small">Name</div>
        <div class="fw-semibold fs-5"><?php echo htmlspecialchars($status->name); ?></div>
      </div>
      <div class="col-md-6">
        <div class="text-muted small">Code</div>
        <div><code class="fs-5"><?php echo htmlspecialchars($status->code); ?></code></div>
      </div>
      <div class="col-md-6">
        <div class="text-muted small">Type</div>
        <div><span class="badge bg-secondary fs-6"><?php echo ucfirst($status->type); ?></span></div>
      </div>
      <div class="col-md-6">
        <div class="text-muted small">Color</div>
        <div>
          <span class="badge fs-6" style="background-color: <?php echo htmlspecialchars($status->color); ?>; color: #fff;">
            <?php echo htmlspecialchars($status->color); ?>
          </span>
        </div>
      </div>
      <div class="col-md-6">
        <div class="text-muted small">Icon</div>
        <div>
          <?php if ($status->icon): ?>
            <i class="bi bi-<?php echo htmlspecialchars($status->icon); ?>"></i> <?php echo htmlspecialchars($status->icon); ?>
          <?php else: ?>
            <span class="text-muted">None</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="text-muted small">Display Order</div>
        <div class="fw-semibold"><?php echo (int)$status->display_order; ?></div>
      </div>
      <div class="col-md-6">
        <div class="text-muted small">Status</div>
        <div>
          <?php if ($status->is_active): ?>
            <span class="badge bg-success">Active</span>
          <?php else: ?>
            <span class="badge bg-secondary">Inactive</span>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($status->description): ?>
        <div class="col-md-12">
          <div class="text-muted small">Description</div>
          <div><?php echo nl2br(htmlspecialchars($status->description)); ?></div>
        </div>
      <?php endif; ?>
      <div class="col-md-6">
        <div class="text-muted small">Created</div>
        <div><?php echo $status->created_at ? date('Y-m-d H:i:s', strtotime($status->created_at)) : 'N/A'; ?></div>
      </div>
      <div class="col-md-6">
        <div class="text-muted small">Updated</div>
        <div><?php echo $status->updated_at ? date('Y-m-d H:i:s', strtotime($status->updated_at)) : 'N/A'; ?></div>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>

