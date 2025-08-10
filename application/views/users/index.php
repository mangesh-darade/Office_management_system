<?php $this->load->view('partials/header', ['title' => ($title ?? 'Users'), 'active' => 'users']); ?>
<div class="row g-3">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0">Users</h5>
      <a href="<?php echo site_url('users/create'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add User</a>
    </div>
    <form class="row g-2 mb-3" method="get" action="<?php echo site_url('users'); ?>">
      <div class="col-auto">
        <input type="text" name="q" value="<?php echo htmlspecialchars($q ?? ''); ?>" class="form-control" placeholder="Search name or email">
      </div>
      <div class="col-auto">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
      </div>
    </form>

    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle datatable" data-order='[[0,"desc"]]'>
            <thead class="table-light">
              <tr>
                <th style="width:70px;">ID</th>
                <th>Name</th>
                <th>Email</th>
                <th style="width:120px;">Role</th>
                <th style="width:120px;">Status</th>
                <th style="width:160px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($rows)) foreach ($rows as $r): ?>
                <tr>
                  <td>#<?php echo (int)$r->id; ?></td>
                  <td><?php echo htmlspecialchars($r->name ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($r->email ?? ''); ?></td>
                  <td><span class="badge bg-secondary"><?php echo htmlspecialchars($r->role ?? 'user'); ?></span></td>
                  <td>
                    <?php if ((int)($r->status ?? 0) === 1): ?>
                      <span class="badge bg-success">Active</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="<?php echo site_url('users/edit/'.(int)$r->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <a href="<?php echo site_url('users/delete/'.(int)$r->id); ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No users found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
