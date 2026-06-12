<?php $this->load->view('partials/header', ['title' => 'Projects']); ?>
<?php $this->load->view('partials/import_errors'); ?>
<div class="container-fluid py-3">
<div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
  <div>
    <h1 class="h4 mb-1 fw-bold"><i class="bi bi-kanban text-primary me-2"></i>Projects</h1>
    <p class="text-muted small mb-0">Manage all your projects</p>
  </div>
  <div class="d-flex gap-2 mt-2 mt-sm-0">
    <?php if (function_exists('has_module_access') && (has_module_access('projects_matrix') || has_module_access('projects') || has_module_access('projects_list'))): ?>
    <a class="btn btn-outline-secondary btn-sm" title="Portfolio Matrix" href="<?php echo site_url('projects/matrix'); ?>"><i class="bi bi-grid-3x3-gap me-1"></i><span class="d-none d-sm-inline">Matrix</span></a>
    <?php endif; ?>
    <?php if(function_exists('has_module_access') && (has_module_access('projects_add') || has_module_access('projects'))): ?>
    <a class="btn btn-primary btn-sm" title="Create" href="<?php echo site_url('projects/create'); ?>"><i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">New Project</span></a>
    <?php endif; ?>
    <?php if(function_exists('has_module_access') && (has_module_access('projects_import') || has_module_access('projects'))): ?>
    <a class="btn btn-outline-secondary btn-sm" title="Import CSV" href="<?php echo site_url('projects/import'); ?>"><i class="bi bi-upload me-1"></i><span class="d-none d-sm-inline">Import</span></a>
    <?php endif; ?>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle datatable" data-order-col="2" data-order-dir="asc">
        <thead>
          <tr>
            <th>#</th>
            <th>Code</th>
            <th>Name</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!empty($projects)) foreach($projects as $p): ?>
            <tr>
              <td><?php echo (int)$p->id; ?></td>
              <td><?php echo htmlspecialchars($p->code); ?></td>
              <td><?php echo htmlspecialchars($p->name); ?></td>
              <td><span class="badge bg-secondary"><?php echo htmlspecialchars($p->status); ?></span></td>
              <td class="text-end">
                <a class="btn btn-light btn-sm" title="View" href="<?php echo site_url('projects/'.$p->id); ?>"><i class="bi bi-eye"></i></a>
                <?php if(function_exists('has_module_access') && (has_module_access('projects_edit') || has_module_access('projects'))): ?>
                <a class="btn btn-primary btn-sm" title="Edit" href="<?php echo site_url('projects/'.$p->id.'/edit'); ?>"><i class="bi bi-pencil"></i></a>
                <?php endif; ?>
                <?php if(function_exists('has_module_access') && (has_module_access('projects_delete') || has_module_access('projects'))): ?>
                <form method="post" action="<?php echo site_url('projects/'.$p->id.'/delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this project?');">
                  <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                  <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
