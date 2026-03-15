<?php $this->load->view('partials/header', ['title' => 'Status Management']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Status Management</h1>
  <div>
    <?php if(function_exists('has_module_access') && (has_module_access('statuses') || (function_exists('is_admin_group') && is_admin_group()))): ?>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('statuses/create'); ?>">
      <i class="bi bi-plus-lg"></i> Add Status
    </a>
    <?php endif; ?>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($this->session->flashdata('error')); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($this->session->flashdata('success')); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<!-- Filter by Type -->
<div class="card shadow-soft mb-3">
  <div class="card-body">
    <form method="get" action="<?php echo site_url('statuses'); ?>" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Filter by Type</label>
        <select name="type" class="form-select" onchange="this.form.submit()">
          <option value="">All Types</option>
          <?php foreach ($types as $type): ?>
            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($selected_type) && $selected_type === $type) ? 'selected' : ''; ?>>
              <?php echo ucfirst($type); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <?php if (empty($statuses)): ?>
      <div class="text-center py-5">
        <p class="text-muted">No statuses found. <a href="<?php echo site_url('statuses/create'); ?>">Create one</a></p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Name</th>
              <th>Code</th>
              <th>Type</th>
              <th>Color</th>
              <th>Order</th>
              <th>Active</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($statuses as $status): ?>
              <tr>
                <td>
                  <strong><?php echo htmlspecialchars($status->name); ?></strong>
                  <?php if ($status->description): ?>
                    <br><small class="text-muted"><?php echo htmlspecialchars(mb_substr($status->description, 0, 50)); ?>...</small>
                  <?php endif; ?>
                </td>
                <td><code><?php echo htmlspecialchars($status->code); ?></code></td>
                <td>
                  <span class="badge bg-secondary"><?php echo ucfirst($status->type); ?></span>
                </td>
                <td>
                  <span class="badge" style="background-color: <?php echo htmlspecialchars($status->color); ?>; color: #fff;">
                    <?php echo htmlspecialchars($status->color); ?>
                  </span>
                </td>
                <td><?php echo (int)$status->display_order; ?></td>
                <td>
                  <?php if ($status->is_active): ?>
                    <span class="badge bg-success">Active</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a class="btn btn-outline-primary" href="<?php echo site_url('statuses/view/'.$status->id); ?>" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                    <?php if(function_exists('has_module_access') && (has_module_access('statuses') || (function_exists('is_admin_group') && is_admin_group()))): ?>
                    <a class="btn btn-outline-secondary" href="<?php echo site_url('statuses/edit/'.$status->id); ?>" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form method="post" action="<?php echo site_url('statuses/delete/'.$status->id); ?>" class="d-inline" onsubmit="return confirm('Delete this status? This action cannot be undone.');">
                      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                      <button type="submit" class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>

