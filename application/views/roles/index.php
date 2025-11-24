<?php $this->load->view('partials/header', ['title' => isset($title) ? $title : 'Roles', 'active' => 'users']); ?>
<div class="row g-3">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0">Roles</h5>
      <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#roleModal">
        <i class="bi bi-plus-lg"></i> Add Role
      </button>
    </div>
    <div class="card">
      <div class="card-body p-0">
        <?php if (empty($rows)): ?>
          <div class="p-3 text-muted">No roles configured yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle datatable">
              <thead class="table-light">
                <tr>
                  <th style="width:70px;">#</th>
                  <th>Name</th>
                  <?php if ($this->db->field_exists('group_type', 'roles')): ?>
                  <th style="width:140px;">Group</th>
                  <?php endif; ?>
                  <?php if ($this->db->field_exists('is_active', 'roles')): ?>
                  <th style="width:100px;">Active</th>
                  <?php endif; ?>
                  <?php if ($this->db->field_exists('sort_order', 'roles')): ?>
                  <th style="width:120px;">Sort Order</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; foreach ($rows as $r): ?>
                  <tr>
                    <td>#<?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars(isset($r->name) ? $r->name : ''); ?></td>
                    <?php if ($this->db->field_exists('group_type', 'roles')): ?>
                    <td>
                      <?php
                        $gt = isset($r->group_type) ? strtolower((string)$r->group_type) : '';
                        echo $gt === 'admin' ? 'Admin Group' : 'User Group';
                      ?>
                    </td>
                    <?php endif; ?>
                    <?php if ($this->db->field_exists('is_active', 'roles')): ?>
                    <td>
                      <?php
                        $active = isset($r->is_active) ? (int)$r->is_active === 1 : true;
                      ?>
                      <?php if ($active): ?>
                        <span class="badge bg-success">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <?php if ($this->db->field_exists('sort_order', 'roles')): ?>
                    <td><?php echo isset($r->sort_order) ? (int)$r->sort_order : ''; ?></td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?php echo site_url('roles/store'); ?>" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Role Name</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Group</label>
          <select name="group_type" class="form-select" required>
            <option value="admin">Admin Group</option>
            <option value="user" selected>User Group</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
