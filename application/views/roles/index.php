<?php $this->load->view('partials/header', ['title' => isset($title) ? $title : 'Roles', 'active' => 'users']); ?>
<div class="container-fluid py-4">
  <div class="row g-3">
    <div class="col-12">
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
        <div>
          <h5 class="mb-1 fw-bold"><i class="bi bi-person-gear text-primary me-2"></i>Roles</h5>
          <p class="text-muted small mb-0">Define user roles and their group types</p>
        </div>
        <?php if(function_exists('has_module_access') && (has_module_access('roles') || is_admin_group())): ?>
        <button type="button" class="btn btn-primary btn-sm mt-2 mt-sm-0" data-bs-toggle="modal" data-bs-target="#roleModal">
          <i class="bi bi-plus-lg me-1"></i>Add Role
        </button>
        <?php endif; ?>
      </div>
      <div class="card shadow-sm border-0">
        <div class="card-body p-0">
          <?php if (empty($rows)): ?>
            <div class="empty-state">
              <div class="empty-icon"><i class="bi bi-person-gear"></i></div>
              <h5>No roles configured yet</h5>
              <p>Add your first role to get started with access control.</p>
            </div>
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
                  <th style="width:130px;" class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; foreach ($rows as $r): ?>
                  <?php $groupType = isset($r->group_type) ? strtolower((string)$r->group_type) : 'user'; ?>
                  <tr>
                    <td>#<?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars(isset($r->name) ? $r->name : ''); ?></td>
                    <?php if ($this->db->field_exists('group_type', 'roles')): ?>
                    <td>
                      <?php
                        echo $groupType === 'admin' ? 'Admin Group' : 'User Group';
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
                    <td class="text-center">
                      <?php if(function_exists('has_module_access') && (has_module_access('roles') || is_admin_group())): ?>
                      <div class="btn-group btn-group-sm" role="group">
                        <button type="button"
                                class="btn btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#roleEditModal"
                                data-id="<?php echo (int)$r->id; ?>"
                                data-name="<?php echo htmlspecialchars(isset($r->name) ? $r->name : ''); ?>"
                                data-group="<?php echo $groupType; ?>">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <form method="post" action="<?php echo site_url('roles/delete/' . (int)$r->id); ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this role?');">
                          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                          <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                      </div>
                      <?php endif; ?>
                    </td>
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
</div>

<div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?php echo site_url('roles/store'); ?>" class="modal-content">
      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
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

<div class="modal fade" id="roleEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?php echo site_url('roles/update'); ?>" class="modal-content">
      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
      <div class="modal-header">
        <h5 class="modal-title">Edit Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" value="">
        <div class="mb-3">
          <label class="form-label">Role Name</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Group</label>
          <select name="group_type" class="form-select" required>
            <option value="admin">Admin Group</option>
            <option value="user">User Group</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var editModalEl = document.getElementById('roleEditModal');
  if (!editModalEl) return;

  editModalEl.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    if (!button) return;

    var id = button.getAttribute('data-id');
    var name = button.getAttribute('data-name') || '';
    var group = button.getAttribute('data-group') || 'user';

    var inputId = editModalEl.querySelector('input[name=\"id\"]');
    var inputName = editModalEl.querySelector('input[name=\"name\"]');
    var selectGroup = editModalEl.querySelector('select[name=\"group_type\"]');

    if (inputId) inputId.value = id;
    if (inputName) inputName.value = name;
    if (selectGroup) selectGroup.value = (group === 'admin' ? 'admin' : 'user');
  });
});
</script>

<?php $this->load->view('partials/footer'); ?>
