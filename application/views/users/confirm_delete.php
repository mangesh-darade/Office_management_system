<?php $this->load->view('partials/header', array('title' => 'Delete User', 'active' => 'users')); ?>
<div class="row g-3">
  <div class="col-12 col-lg-8">
    <div class="card border-danger">
      <div class="card-header bg-danger text-white">
        <i class="bi bi-exclamation-triangle me-1"></i> Confirm Delete
      </div>
      <div class="card-body">
        <p>Are you sure you want to delete this user?</p>
        <ul>
          <li><strong>ID:</strong> #<?php echo (int)$row->id; ?></li>
          <li><strong>Name:</strong> <?php echo htmlspecialchars(isset($row->name) ? $row->name : ''); ?></li>
          <li><strong>Email:</strong> <?php echo htmlspecialchars(isset($row->email) ? $row->email : ''); ?></li>
        </ul>
        <div class="d-flex gap-2 mt-3">
          <a href="<?php echo site_url('users/destroy/'.(int)$row->id); ?>" class="btn btn-danger"><i class="bi bi-trash"></i> Delete</a>
          <a href="<?php echo site_url('users'); ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
