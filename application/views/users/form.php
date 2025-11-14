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
        <form method="post" enctype="multipart/form-data" action="<?php echo $is_edit ? site_url('users/update/'.(int)$row->id) : site_url('users/store'); ?>">
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
              <?php
                $rid = isset($row->role_id) ? (int)$row->role_id : null;
                if (!$rid && isset($row->role)) {
                  $map = ['admin'=>1,'hr'=>2,'lead'=>3,'employee'=>4,'user'=>4,'manager'=>3];
                  $key = strtolower((string)$row->role);
                  $rid = isset($map[$key]) ? (int)$map[$key] : 4;
                }
                if (!$rid) { $rid = 4; }
              ?>
              <select name="role_id" class="form-select">
                <option value="1" <?php echo $rid===1?'selected':''; ?>>Admin</option>
                <option value="2" <?php echo $rid===2?'selected':''; ?>>HR</option>
                <option value="3" <?php echo $rid===3?'selected':''; ?>>Lead</option>
                <option value="4" <?php echo $rid===4?'selected':''; ?>>Employee</option>
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
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars(isset($row->phone) ? $row->phone : ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Verified</label>
              <?php $ver = (int)(isset($row->is_verified) ? $row->is_verified : 0); ?>
              <select name="is_verified" class="form-select">
                <option value="1" <?php echo $ver===1?'selected':''; ?>>Yes</option>
                <option value="0" <?php echo $ver===0?'selected':''; ?>>No</option>
              </select>
            </div>
          </div>
          <div class="mb-3 mt-3">
            <label class="form-label">Avatar</label>
            <input type="file" name="avatar" accept="image/*" class="form-control">
            <?php if (!empty($row->avatar)): ?>
              <div class="form-text">Current: <a href="<?php echo base_url(trim($row->avatar, '/')); ?>" target="_blank">View</a></div>
            <?php endif; ?>
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
