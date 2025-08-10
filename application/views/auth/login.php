<?php 
  // Hide navbar and sidebar for login page, and allow full-width control
  $this->load->view('partials/header', ['title' => 'Login', 'hide_navbar' => true, 'with_sidebar' => false, 'full_width' => true]); 
?>

<section class="d-flex align-items-center justify-content-center min-vh-100 py-4">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
        <div class="card shadow-soft">
          <div class="card-body p-4">
            <h1 class="h4 mb-3 text-center">Welcome back</h1>
            <p class="text-muted small text-center mb-4">Sign in to continue</p>
            <?php if($this->session->flashdata('error')): ?>
              <div class="alert alert-danger py-2"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
            <?php endif; ?>
            <form method="post" novalidate>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required autocomplete="email">
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
              </div>
              <button class="btn btn-primary w-100" type="submit">Login</button>
            </form>
            <a class="btn btn-outline-primary w-100 mt-2" href="<?php echo site_url('auth/register'); ?>">Sign up</a>
            <div class="text-center mt-3 small">
              Don't have an account?
              <a href="<?php echo site_url('auth/register'); ?>">Create one</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php $this->load->view('partials/footer'); ?>
