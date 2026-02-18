<?php $this->load->view('partials/header', ['title' => 'Employees']); ?>
<div class="container-fluid py-4">
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div>
      <h1 class="h4 mb-1 fw-bold"><i class="bi bi-people text-primary me-2"></i>Employees</h1>
      <p class="text-muted small mb-0">Manage your team members</p>
    </div>
    <?php if(function_exists('has_module_access') && (has_module_access('employees_add') || has_module_access('employees'))): ?>
    <a href="<?php echo site_url('employees/create'); ?>" class="btn btn-primary mt-2 mt-sm-0"><i class="bi bi-plus-lg me-1"></i>Add Employee</a>
    <?php endif; ?>
  </div>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-3">
      <form method="get" action="<?php echo site_url('employees'); ?>">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-sm-8 col-lg-6">
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
              <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars(isset($q) ? $q : ''); ?>" placeholder="Search by code, name, email...">
            </div>
          </div>
          <div class="col-12 col-sm-auto">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i>Search</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 datatable">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Code</th>
              <th>Name</th>
              <th class="d-none d-md-table-cell">Email</th>
              <th class="d-none d-lg-table-cell">Department</th>
              <th class="d-none d-lg-table-cell">Designation</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($employees)): foreach ($employees as $e): ?>
            <tr>
              <td><?php echo (int)$e->id; ?></td>
              <td><span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars($e->emp_code); ?></span></td>
              <td><?php echo htmlspecialchars(trim((isset($e->first_name) ? $e->first_name : '').' '.(isset($e->last_name) ? $e->last_name : ''))); ?></td>
              <td class="d-none d-md-table-cell"><?php echo htmlspecialchars(isset($e->email) ? $e->email : ''); ?></td>
              <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars(isset($e->department) ? $e->department : ''); ?></td>
              <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars(isset($e->designation) ? $e->designation : ''); ?></td>
              <td class="text-end table-actions">
                <a class="btn btn-light btn-sm" title="View" href="<?php echo site_url('employees/'.$e->id); ?>"><i class="bi bi-eye"></i></a>
                <?php if(function_exists('has_module_access') && (has_module_access('employees_edit') || has_module_access('employees'))): ?>
                <a class="btn btn-primary btn-sm" title="Edit" href="<?php echo site_url('employees/'.$e->id.'/edit'); ?>"><i class="bi bi-pencil"></i></a>
                <?php endif; ?>
                <?php if(function_exists('has_module_access') && (has_module_access('employees_delete') || has_module_access('employees'))): ?>
                <form class="d-inline" method="post" action="<?php echo site_url('employees/'.$e->id.'/delete'); ?>" onsubmit="return confirm('Delete this employee?');">
                  <button class="btn btn-danger btn-sm" type="submit" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr>
              <td colspan="7" class="text-center py-5">
                <div class="empty-state">
                  <div class="empty-icon"><i class="bi bi-people"></i></div>
                  <h5>No employees found</h5>
                  <p>Try adjusting your search or add a new employee.</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
