<?php $this->load->view('partials/header', ['title' => 'Forgot Password', 'hide_navbar' => true, 'with_sidebar' => false, 'full_width' => true]); ?>

<section class="d-flex align-items-center justify-content-center min-vh-100 py-4">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
        <div class="card shadow-soft">
          <div class="card-body p-4">
            <h1 class="h4 mb-3 text-center">Forgot password</h1>
            <p class="text-muted small text-center mb-4">Enter your registered mobile number to receive an OTP on your email.</p>
            <?php if($this->session->flashdata('success')): ?>
              <div class="alert alert-success py-2"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
            <?php endif; ?>
            <?php if($this->session->flashdata('error')): ?>
              <div class="alert alert-danger py-2"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
            <?php endif; ?>
            <form method="post" novalidate>
              <div class="mb-3">
                <label class="form-label">Mobile number</label>
                <input type="text" name="phone" class="form-control" placeholder="Enter mobile number" required autocomplete="tel">
              </div>
              <button class="btn btn-primary w-100" type="submit">Send OTP</button>
            </form>
            <div class="text-center mt-3 small">
              Remembered your password?
              <a href="<?php echo site_url('login'); ?>">Back to login</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php $this->load->view('partials/footer'); ?>
