<?php $this->load->view('partials/header', ['title' => 'Employees']); ?>
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0">Employees</h1>
    <a href="<?php echo site_url('employees/create'); ?>" class="btn btn-primary">Add Employee</a>
  </div>
  <form class="mb-3" method="get" action="<?php echo site_url('employees'); ?>">
    <div class="row g-2">
      <div class="col-12 col-sm-8 col-lg-6">
        <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars(isset($q) ? $q : ''); ?>" placeholder="Search by code, name, email">
      </div>
      <div class="col-12 col-sm-auto">
        <button class="btn btn-outline-secondary w-100" type="submit">Search</button>
      </div>
    </div>
  </form>

  <div class="card shadow-soft">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 datatable">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Emp Code</th>
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
                <a class="btn btn-primary btn-sm" title="Edit" href="<?php echo site_url('employees/'.$e->id.'/edit'); ?>"><i class="bi bi-pencil"></i></a>
                <form class="d-inline" method="post" action="<?php echo site_url('employees/'.$e->id.'/delete'); ?>" onsubmit="return confirm('Delete this employee?');">
                  <button class="btn btn-danger btn-sm" type="submit" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr class="no-data">
              <td class="text-center text-muted py-4">No employees found.</td>
              <td></td>
              <td></td>
              <td class="d-none d-md-table-cell"></td>
              <td class="d-none d-lg-table-cell"></td>
              <td class="d-none d-lg-table-cell"></td>
              <td class="text-end"></td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php $this->load->view('partials/footer'); ?>
