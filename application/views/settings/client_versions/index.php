<?php $this->load->view('partials/header', array('title' => 'Client Versions')); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-1"><i class="bi bi-tag me-2"></i>Client Versions</h1>
    <p class="text-muted small mb-0">Versions for the Clients module only. Used on client create, edit, and list filters.</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('settings'); ?>"><i class="bi bi-gear me-1"></i>Settings</a>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('settings/client-versions/create'); ?>"><i class="bi bi-plus-lg"></i> Add Version</a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger py-2"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success py-2"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <?php if (empty($versions)): ?>
      <p class="text-muted mb-0">No versions found. <a href="<?php echo site_url('settings/client-versions/create'); ?>">Create one</a></p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th>Code</th>
              <th>Order</th>
              <th>Active</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($versions as $row): ?>
              <tr>
                <td>
                  <strong><?php echo esc_view($row->name); ?></strong>
                  <?php if (!empty($row->description)): ?>
                    <br><small class="text-muted"><?php echo esc_view($row->description); ?></small>
                  <?php endif; ?>
                </td>
                <td><code><?php echo esc_view($row->code); ?></code></td>
                <td><?php echo (int) $row->display_order; ?></td>
                <td><?php echo ((int) $row->is_active === 1) ? 'Yes' : 'No'; ?></td>
                <td class="text-end text-nowrap">
                  <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                    <a href="<?php echo site_url('settings/client-versions/' . (int) $row->id . '/edit'); ?>" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form method="post" action="<?php echo site_url('settings/client-versions/' . (int) $row->id . '/delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this version?');">
                      <button type="submit" class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
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
