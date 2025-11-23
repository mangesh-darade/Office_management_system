<?php $this->load->view('partials/header', array('title' => (isset($title) ? $title : 'Users'), 'active' => 'users')); ?>
<div class="row g-3">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0">Users</h5>
      <a href="<?php echo site_url('users/create'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add User</a>
    </div>
    <form class="row g-2 mb-3" method="get" action="<?php echo site_url('users'); ?>">
      <div class="col-auto">
        <input type="text" name="q" value="<?php echo htmlspecialchars(isset($q) ? $q : ''); ?>" class="form-control" placeholder="Search name or email">
      </div>
      <div class="col-auto">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
      </div>
    </form>

    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle datatable" data-order='[[0,"asc"]]'>
            <thead class="table-light">
              <tr>
                <th style="width:70px;">#</th>
                <th>Name</th>
                <th>Email</th>
                <th style="width:120px;">Role</th>
                <th style="width:120px;">Status</th>
                <th style="width:160px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($rows)) $i = 1; if (!empty($rows)) foreach ($rows as $r): ?>
                <tr>
                  <td>#<?php echo $i++; ?></td>
                  <td><?php echo htmlspecialchars(isset($r->name) ? $r->name : ''); ?></td>
                  <td><?php echo htmlspecialchars(isset($r->email) ? $r->email : ''); ?></td>
                  <td>
                    <?php
                      $roleLabel = '';
                      if (isset($r->role_id)) {
                        $map = [1=>'admin', 2=>'hr', 3=>'lead', 4=>'employee'];
                        $rid = (int)$r->role_id; $roleLabel = isset($map[$rid]) ? $map[$rid] : 'employee';
                      } else if (isset($r->role) && $r->role !== '') { $roleLabel = strtolower($r->role); }
                      else { $roleLabel = 'employee'; }
                      $roleLabelPretty = ucfirst($roleLabel);
                    ?>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($roleLabelPretty); ?></span>
                  </td>
                  <td>
                    <?php
                      $st = isset($r->status) ? $r->status : 0;
                      $is_active = false;
                      if (is_numeric($st)) { $is_active = ((int)$st) === 1; }
                      else if (is_string($st)) { $is_active = in_array(strtolower(trim($st)), ['active','enabled','true','yes'], true); }
                    ?>
                    <?php if ($is_active): ?>
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
