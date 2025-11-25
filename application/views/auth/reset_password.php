<?php $this->load->view('partials/header', ['title' => 'Reset Password', 'hide_navbar' => true, 'with_sidebar' => false, 'full_width' => true]); ?>

<section class="d-flex align-items-center justify-content-center min-vh-100 py-4">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
        <div class="card shadow-soft">
          <div class="card-body p-4">
            <h1 class="h4 mb-3 text-center">Reset password</h1>
            <p class="text-muted small text-center mb-4">Enter the OTP sent to your email and choose a new password.</p>
            <?php if($this->session->flashdata('error')): ?>
              <div class="alert alert-danger py-2"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
            <?php endif; ?>
            <form method="post" novalidate>
              <div class="mb-3">
                <label class="form-label">OTP</label>
                <input type="text" name="code" class="form-control" placeholder="Enter OTP" required autocomplete="one-time-code">
              </div>
              <div class="mb-3">
                <label class="form-label">New password</label>
                <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required autocomplete="new-password">
              </div>
              <div class="mb-3">
                <label class="form-label">Confirm new password</label>
                <input type="password" name="password_confirm" class="form-control" placeholder="Re-enter new password" required autocomplete="new-password">
              </div>
              <button class="btn btn-primary w-100" type="submit">Update password</button>
            </form>
            <div class="text-center mt-3 small">
              Back to
              <a href="<?php echo site_url('login'); ?>">login</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php $this->load->view('partials/footer'); ?>
