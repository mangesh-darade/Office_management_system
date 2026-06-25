<?php $this->load->view('partials/header', array('title' => 'Module Types')); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-1"><i class="bi bi-ui-checks-grid me-2"></i>Module Type Management</h1>
    <p class="text-muted small mb-0">Manage types for My Works, Clients, Projects, Requirements, and Employees.</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('settings'); ?>"><i class="bi bi-gear me-1"></i>Settings</a>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('settings/types/create'); ?>"><i class="bi bi-plus-lg"></i> Add Type</a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger py-2"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success py-2"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <form method="get" action="<?php echo site_url('settings/types'); ?>" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Module</label>
        <select name="module" class="form-select" onchange="this.form.submit()">
          <option value="">All modules</option>
          <?php foreach ($modules as $key => $label): ?>
            <option value="<?php echo esc_view($key); ?>" <?php echo (isset($selected_module) && $selected_module === $key) ? 'selected' : ''; ?>><?php echo esc_view($label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <?php if (empty($types)): ?>
      <p class="text-muted mb-0">No types found. <a href="<?php echo site_url('settings/types/create'); ?>">Create one</a></p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th>Code</th>
              <th>Module</th>
              <th>Order</th>
              <th>Active</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($types as $type): ?>
              <tr>
                <td>
                  <strong><?php echo esc_view($type->name); ?></strong>
                  <?php if (!empty($type->description)): ?>
                    <br><small class="text-muted"><?php echo esc_view($type->description); ?></small>
                  <?php endif; ?>
                </td>
                <td><code><?php echo esc_view($type->code); ?></code></td>
                <td><span class="badge bg-secondary"><?php echo esc_view(isset($modules[$type->module]) ? $modules[$type->module] : $type->module); ?></span></td>
                <td><?php echo (int) $type->display_order; ?></td>
                <td><?php echo (int) $type->is_active === 1 ? 'Yes' : 'No'; ?></td>
                <td class="text-end text-nowrap">
                  <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('settings/types/' . (int) $type->id . '/edit'); ?>">Edit</a>
                  <form method="post" class="d-inline" action="<?php echo site_url('settings/types/' . (int) $type->id . '/delete'); ?>" onsubmit="return confirm('Delete this type?');">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
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
