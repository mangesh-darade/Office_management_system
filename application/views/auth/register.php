<?php $this->load->view('partials/header', ['title' => 'Register', 'hide_navbar' => true]); ?>
<div class="row justify-content-center">
  <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
    <div class="card shadow-soft">
      <div class="card-body p-4">
        <h1 class="h4 mb-3 text-center">Create account</h1>
        <p class="text-muted small text-center mb-4">Register a new user</p>
        <?php if($this->session->flashdata('error')): ?>
          <div class="alert alert-danger py-2"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role_id" class="form-select" required>
              <option value="">Select role</option>
              <option value="1">Admin</option>
              <option value="2">HR</option>
              <option value="3">Lead</option>
              <option value="4">Employee</option>
            </select>
          </div>
          <button class="btn btn-primary w-100" type="submit">Register</button>
        </form>
        <div class="text-center mt-3 small">
          Already have an account?
          <a href="<?php echo site_url('login'); ?>">Sign in</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
