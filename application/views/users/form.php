<?php $this->load->view('partials/header', array('title' => (isset($title) ? $title : 'User'), 'active' => 'users')); ?>
<div class="row g-3">
  <div class="col-12 col-lg-8">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0"><?php echo htmlspecialchars(isset($title) ? $title : 'User'); ?></h5>
      <a href="<?php echo site_url('users'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <form method="post" action="<?php echo $is_edit ? site_url('users/update/'.(int)$row->id) : site_url('users/store'); ?>">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars(isset($row->name) ? $row->name : ''); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars(isset($row->email) ? $row->email : ''); ?>" required>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Role</label>
              <select name="role" class="form-select">
                <?php $role = isset($row->role) ? $row->role : 'user'; ?>
                <option value="user" <?php echo $role==='user'?'selected':''; ?>>User</option>
                <option value="admin" <?php echo $role==='admin'?'selected':''; ?>>Admin</option>
                <option value="manager" <?php echo $role==='manager'?'selected':''; ?>>Manager</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <?php $st = (int)(isset($row->status) ? $row->status : 1); ?>
              <select name="status" class="form-select">
                <option value="1" <?php echo $st===1?'selected':''; ?>>Active</option>
                <option value="0" <?php echo $st===0?'selected':''; ?>>Inactive</option>
              </select>
            </div>
          </div>
          <div class="mb-3 mt-3">
            <label class="form-label"><?php echo $is_edit ? 'Reset Password (optional)' : 'Password'; ?></label>
            <input type="password" name="password" class="form-control" <?php echo $is_edit ? '' : 'required'; ?> autocomplete="new-password">
            <?php if ($is_edit): ?><div class="form-text">Leave blank to keep current password.</div><?php endif; ?>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Save</button>
            <a class="btn btn-outline-secondary" href="<?php echo site_url('users'); ?>">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
